<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/KategorijaDao.php';
require_once __DIR__ . '/../dao/MajstorKategorijaDao.php';

/**
 * Servis za upravljanje hijerarhijskim stablom majstorluka.
 *
 * Omogućava dodavanje, premještanje i brisanje kategorija, sprječava cikluse i dozvoljava brisanje samo kategorija bez
 * potkategorija. Prije brisanja lista uklanja njegove veze s majstorima u jednoj transakciji.
 */
class KategorijaService extends BaseService
{
    protected $entity_name = 'Kategorija';
    private $majstor_kategorija_dao;

    /**
     * Priprema rad sa stablom kategorija i njihovim vezama prema majstorima.
     *
     * KategorijaDao koristi se za kategorije, a MajstorKategorijaDao za zapise iz vezne tabele. Oba objekta možemo proslijediti
     * izvana ili koristiti podrazumijevane.
     *
     * Ulaz: $dao – opcionalni KategorijaDao; $majstor_kategorija_dao – opcionalni DAO za veze.
     * Izlaz: Nema povratne vrijednosti.
     */
    public function __construct($dao = null, $majstor_kategorija_dao = null)
    {
        // Prvi DAO obrađuje kategorije, drugi njihove veze s majstorima.
        parent::__construct($dao ?? new KategorijaDao());
        $this->majstor_kategorija_dao = $majstor_kategorija_dao ?? new MajstorKategorijaDao();
    }

    /**
     * Dodaje novu kategoriju na odgovarajuće mjesto u stablu majstorluka.
     *
     * Prvo čisti podatke. Ako nije poslan roditelj_id, postavlja ga na null, pa nastaje korijenska kategorija, odnosno ona koja
     * nema roditelja. Zatim BaseService provjerava naziv, roditelja i pravila hijerarhije prije upisa (P007).
     *
     * Ulaz: $kategorija – naziv i opcionalni roditelj_id.
     * Izlaz: Nova kategorija iz baze ili ValidationException za neispravne podatke.
     *
     * Povezana poslovna pravila:
     *   P007 – Administrator može dodavati i uređivati kategorije u stablu majstorluka.
     */
    public function add($kategorija)
    {
        // Izdvajamo samo naziv i roditelj_id i čistimo unesene vrijednosti.
        $kategorija = $this->normalize($kategorija);
        // Bez roditelja nova kategorija je korijenska.
        if (!array_key_exists('roditelj_id', $kategorija)) {
            $kategorija['roditelj_id'] = null;
        }
        return parent::add($kategorija);
    }

    /**
     * Mijenja naziv kategorije ili je premješta pod drugu roditeljsku kategoriju.
     *
     * Prvo učitava postojeću kategoriju i spaja je s poslanim izmjenama. Provjera validate() odbija nepostojećeg roditelja i
     * ciklus, odnosno situaciju u kojoj bi kategorija postala vlastiti predak (V002). Mijenja se samo zapis kategorije, a
     * direktni odabiri majstora ostaju isti (P008).
     *
     * Ulaz: $kategorija – polja koja mijenjamo; $id – ID kategorije.
     * Izlaz: Ažurirana kategorija ili izuzetak ako izmjena nije dozvoljena.
     *
     * Povezana poslovna pravila:
     *   P007 – Administrator mijenja naziv ili položaj kategorije.
     *   P008 – Premještanje ne mijenja direktne veze majstora s kategorijama.
     *   V002 – Zabranjeni su ciklusi u hijerarhiji kategorija.
     */
    public function update($kategorija, $id)
    {
        // Čitamo postojeći zapis kako bismo podržali djelimičnu izmjenu polja.
        $postojeca = $this->get_by_id($id);
        $promjene = $this->normalize($kategorija);
        // BaseService poziva validate(), koji odbija nepostojećeg roditelja i ciklus.
        return parent::update(array_merge($postojeca, $promjene), $id);
    }

    /**
     * Briše kategoriju iz stabla majstorluka samo ako ona nema potkategorija.
     *
     * Prvo provjerava postoji li kategorija i je li list, odnosno kategorija bez potkategorija (V001). Ako ima potkategorije,
     * brisanje se odbija. Ako je list, smije se obrisati čak i kada su ga majstori odabrali: prvo se u transakciji uklanjaju veze
     * iz MAJSTOR_KATEGORIJE, a zatim sama kategorija (A001). Profili i korisnički računi majstora ostaju sačuvani. Ako dođe do
     * greške, rollback poništava započete promjene.
     *
     * Ulaz: $id – ID kategorije koju želimo obrisati.
     * Izlaz: Nema povratnog zapisa; neuspjeh se prijavljuje odgovarajućim izuzetkom.
     *
     * Povezana poslovna pravila:
     *   P007 – Administrator može brisati kategorije uz propisane provjere.
     *   V001 – Brisati se mogu samo listovi, kategorije bez potkategorija.
     *   A001 – Prvo se uklanjaju veze s majstorima, zatim kategorija, sve u jednoj transakciji.
     */
    public function delete($id)
    {
        // Korak 1: kategorija mora postojati.
        $this->get_by_id($id);
        // Korak 2: ako postoje potkategorije, ovo nije list i brisanje se odbija.
        if (count($this->dao->get_children($id)) > 0) {
            throw new ValidationException('Prvo obrišite potkategorije; samo kategorija bez djece može se obrisati.');
        }

        // Korak 3: otvaramo transakciju da veze i kategorija budu obrisane zajedno.
        $this->dao->begin_transaction();
        try {
            // Korak 4: dohvatamo sve majstore koji su direktno odabrali ovu kategoriju.
            $veze = $this->majstor_kategorija_dao->get_by_kategorija_id($id);
            // Korak 5: brišemo veze iz MAJSTOR_KATEGORIJE; profili majstora ostaju.
            foreach ($veze as $veza) {
                $this->majstor_kategorija_dao->delete_by_keys($veza['majstor_id'], $id);
            }
            // Korak 6: kada više nema veza, brišemo samu kategoriju.
            $this->dao->delete($id);
            // Ako su svi koraci prošli, potvrđujemo cijelu transakciju.
            $this->dao->commit();
        } catch (Throwable $e) {
            // Ako dođe do greške, poništavamo sve promjene u toj transakciji.
            $this->dao->rollback();
            throw $e;
        }
    }

    /**
     * Dohvata direktne potkategorije određene kategorije ili početne (korijenske) kategorije našeg stabla majstorluka.
     *
     * Ako proslijedimo roditelj_id, prvo provjerava je li ID ispravan i postoji li roditeljska kategorija. Zatim dohvaća samo
     * njenu direktnu djecu, jedan nivo niže, a ne sve potomke. Ako je roditelj_id = null, vraća kategorije bez roditelja, koje se
     * nalaze na vrhu stabla. U našim početnim podacima to je „Sva majstorstva“ (ID 1); trenutni kod ne zabranjuje da postoji više
     * korijenskih kategorija.
     *
     * Ulaz: $roditelj_id – ID roditeljske kategorije ili null za kategorije bez roditelja.
     * Izlaz: Niz direktnih potkategorija ili korijenskih kategorija; prazan niz ako ih nema.
     */
    public function get_children($roditelj_id = null)
    {
        // Za konkretnog roditelja provjeravamo ID i njegovo postojanje.
        if ($roditelj_id !== null) {
            Validator::integer($roditelj_id, 'roditelj_id', 1);
            $this->get_by_id($roditelj_id);
        }
        // DAO vraća samo direktnu djecu; null označava korijenske kategorije.
        return $this->dao->get_children($roditelj_id);
    }

    /**
     * Provjerava naziv kategorije, izabranog roditelja i ispravnost hijerarhije.
     *
     * Traži naziv odgovarajuće dužine i odbija dupli naziv bez obzira na velika i mala slova. Ako roditelj postoji, njegov ID
     * mora biti ispravan i stvarno postojati u bazi. Prati lanac roditelja prema vrhu stabla i odbija situaciju kada naiđe na
     * kategoriju koju premještamo ili na već posjećenog roditelja (V002).
     *
     * Ulaz: $kategorija – podaci za provjeru; $id – postojeći ID pri izmjeni, inače null.
     * Izlaz: Nema povratne vrijednosti; ValidationException ako je pravilo prekršeno.
     *
     * Povezana poslovna pravila:
     *   P007 – Naziv i roditeljske veze moraju ostati ispravni pri upravljanju kategorijama.
     *   V002 – Kategorija ne smije postati vlastiti roditelj ili potomak.
     */
    protected function validate($kategorija, $id = null)
    {
        // Kategorija mora imati naziv dozvoljene dužine.
        Validator::required($kategorija, ['naziv']);
        Validator::length($kategorija['naziv'], 'naziv', 2, 100);

        // Provjeravamo jedinstvenost naziva bez obzira na veličinu slova, jer SQL kolona nema UNIQUE ograničenje.
        foreach ($this->dao->get_all() as $postojeca) {
            if (mb_strtolower(trim($postojeca['naziv'])) === mb_strtolower(trim($kategorija['naziv'])) &&
                (int) $postojeca['id'] !== (int) $id) {
                throw new ValidationException('Kategorija s tim nazivom već postoji.');
            }
        }

        // Bez roditelja kategorija ostaje korijenska.
        $roditelj_id = $kategorija['roditelj_id'] ?? null;
        if ($roditelj_id === null) {
            return;
        }
        Validator::integer($roditelj_id, 'roditelj_id', 1);
        if (!$this->dao->get_by_id($roditelj_id)) {
            throw new ValidationException('Odabrana roditeljska kategorija ne postoji.');
        }

        // V002: pratimo lanac roditelja prema vrhu stabla i pazimo da se ne vratimo na istu kategoriju.
        $trenutni_id = (int) $roditelj_id;
        $posjeceni = [];
        while ($trenutni_id !== null) {
            // Ako ponovo dođemo do kategorije koju premještamo, nastao bi ciklus.
            if ($id !== null && $trenutni_id === (int) $id) {
                throw new ValidationException('Kategorija ne smije postati vlastiti roditelj ili potomak.');
            }
            if (in_array($trenutni_id, $posjeceni, true)) {
                throw new ValidationException('Postojeća hijerarhija sadrži ciklus.');
            }
            $posjeceni[] = $trenutni_id;
            $trenutna = $this->dao->get_by_id($trenutni_id);
            $trenutni_id = $trenutna['roditelj_id'] === null ? null : (int) $trenutna['roditelj_id'];
        }
    }

    /**
     * Izdvaja i čisti podatke koji se smiju spremiti za kategoriju.
     *
     * Prihvata samo naziv i roditelj_id. Uklanja razmake oko naziva, a prazan tekst u polju roditelj_id pretvara u null. Time
     * možemo odvojiti kategoriju od roditelja i učiniti je korijenskom bez slanja drugih polja iz zahtjeva.
     *
     * Ulaz: $kategorija – ulazni niz podataka.
     * Izlaz: Niz dozvoljenih polja spreman za narednu validaciju.
     */
    private function normalize($kategorija)
    {
        if (!is_array($kategorija)) {
            throw new ValidationException('Podaci kategorije moraju biti objekt.');
        }
        // Uklanjamo sva polja osim dozvoljenih za unos/izmjenu.
        $podaci = array_intersect_key($kategorija, array_flip(['naziv', 'roditelj_id']));
        if (isset($podaci['naziv'])) {
            $podaci['naziv'] = trim((string) $podaci['naziv']);
        }
        // Prazan roditelj_id znači da kategorija više nema roditelja.
        if (array_key_exists('roditelj_id', $podaci) && trim((string) $podaci['roditelj_id']) === '') {
            $podaci['roditelj_id'] = null;
        }
        return $podaci;
    }
}
