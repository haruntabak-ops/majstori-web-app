<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/KorisnikDao.php';
require_once __DIR__ . '/../dao/MajstorDao.php';
require_once __DIR__ . '/../../utils/Roles.php';

/**
 * Servis za korisničke račune i provjeru lozinki.
 *
 * Provjerava e-mail i uloge admin/majstor, sprema bcrypt hash umjesto obične lozinke i uklanja hash iz podataka koje vraća drugim
 * dijelovima aplikacije. Autentikacijski JWT dio dolazi u M4.
 */
class KorisnikService extends BaseService
{
    protected $entity_name = 'Korisnik';
    private $majstor_dao;

    /**
     * Priprema servis za rad s korisničkim računima i povezanim profilima majstora.
     *
     * KorisnikDao pristupa tabeli KORISNICI, a MajstorDao služi za dopunu pregleda podacima profila. Ako DAO objekti nisu zadani,
     * servis ih sam kreira.
     *
     * Ulaz: $dao – opcionalni KorisnikDao; $majstor_dao – opcionalni MajstorDao.
     * Izlaz: Nema povratne vrijednosti.
     */
    public function __construct($dao = null, $majstor_dao = null)
    {
        // Pripremamo DAO za korisničke račune i profile majstora.
        parent::__construct($dao ?? new KorisnikDao());
        $this->majstor_dao = $majstor_dao ?? new MajstorDao();
    }

    /**
     * Vraća listu svih korisničkih računa bez osjetljivih hash vrijednosti lozinki.
     *
     * DAO pročita sve korisnike. Za svakog pozivamo bez_hasha() i uklanjamo lozinka_hash prije vraćanja podataka koje će kasnije
     * API ruta pretvoriti u JSON.
     *
     * Ulaz: Nema parametara.
     * Izlaz: Niz korisnika bez polja lozinka_hash.
     */
    public function get_all()
    {
        $rezultat = [];
        // Svaki rezultat pojedinačno prolazi kroz uklanjanje osjetljivog polja.
        foreach ($this->dao->get_all() as $korisnik) {
            $rezultat[] = $this->bez_hasha($korisnik);
        }
        return $rezultat;
    }

    /**
     * Dohvata jednog korisnika po ID-u bez otkrivanja hash vrijednosti njegove lozinke.
     *
     * BaseService provjerava postoji li račun; zatim bez_hasha() uklanja lozinka_hash iz rezultata. Nepostojeći ID dovodi do
     * greške 404.
     *
     * Ulaz: $id – ID korisnika.
     * Izlaz: Korisnik bez hasha ili NotFoundException.
     */
    public function get_by_id($id)
    {
        // Čitamo preko osnovnog servisa i odmah uklanjamo hash lozinke.
        return $this->bez_hasha(parent::get_by_id($id));
    }

    /**
     * Traži korisnički račun po e-mail adresi za pregled podataka bez lozinke.
     *
     * E-mail pretvara u mala slova i uklanja okolne razmake. DAO nalazi račun, ali servis uklanja hash prije vraćanja rezultata.
     * Ako račun ne postoji, vraća null umjesto greške.
     *
     * Ulaz: $email – unesena e-mail adresa.
     * Izlaz: Korisnik bez lozinka_hash ili null ako nema podudaranja.
     */
    public function get_by_email($email)
    {
        // Email pretvaramo u mala slova i uklanjamo okolne razmake.
        $korisnik = $this->dao->get_by_email(strtolower(trim((string) $email)));
        return $korisnik ? $this->bez_hasha($korisnik) : null;
    }

    /**
     * Dohvata korisničke račune jedne dozvoljene uloge.
     *
     * Prvo provjerava da je uloga admin ili majstor. Zatim poziva DAO upit koji već vraća samo ID, e-mail i ulogu, bez
     * lozinka_hash.
     *
     * Ulaz: $uloga – tekst admin ili majstor.
     * Izlaz: Niz odgovarajućih korisnika bez hash vrijednosti.
     */
    public function get_by_uloga($uloga)
    {
        // Prihvatamo samo uloge navedene u Roles::all().
        Validator::one_of($uloga, 'uloga', Roles::all());
        return $this->dao->get_by_uloga($uloga);
    }

    /**
     * Priprema pregled korisničkih računa zajedno s profilima majstora kada postoje.
     *
     * Odvojeno čita korisnike bez hashova i sve profile majstora. Za svaki račun traži profil s odgovarajućim korisnik_id; ako ga
     * nema, polje profil ostaje null. Ovaj pregled je namijenjen administraciji.
     *
     * Ulaz: Nema parametara.
     * Izlaz: Niz korisnika s dodatnim poljem profil, koje je profil ili null.
     */
    public function get_all_sa_profilima()
    {
        // Prvi korak: čitanje korisnika bez hashova.
        $korisnici = $this->get_all();
        // Drugi korak: čitamo profile majstora samo jednom.
        $profili = $this->majstor_dao->get_all();
        // Svakom korisniku dodajemo odgovarajući profil ili null.
        foreach ($korisnici as &$korisnik) {
            $korisnik['profil'] = null;
            foreach ($profili as $profil) {
                if ((int) $profil['korisnik_id'] === (int) $korisnik['id']) {
                    $korisnik['profil'] = $profil;
                    break;
                }
            }
        }
        unset($korisnik);
        return $korisnici;
    }

    /**
     * Vraća jedan korisnički račun zajedno s njegovim profilom, ako ga ima.
     *
     * Najprije dobija korisnika bez hasha, a potom preko MajstorDao pronalazi profil po korisnik_id. Admin račun ili majstor bez
     * profila dobit će vrijednost null za profil.
     *
     * Ulaz: $id – ID korisničkog računa.
     * Izlaz: Korisnik bez hasha s dodatnim poljem profil.
     */
    public function get_by_id_sa_profilom($id)
    {
        // Najprije učitavamo korisnika bez hash vrijednosti.
        $korisnik = $this->get_by_id($id);
        $korisnik['profil'] = $this->majstor_dao->get_by_korisnik_id($id);
        return $korisnik;
    }

    /**
     * Kreira novi korisnički račun i sprema samo hash lozinke, a ne običnu lozinku.
     *
     * Prvo izdvaja dozvoljena polja, postavlja ulogu majstor ako nije zadana i provjerava e-mail, ulogu i jačinu lozinke. Lozinku
     * pretvara u bcrypt hash, pa DAO-u šalje samo hash. Nakon upisa vraća korisnika bez osjetljivog podatka (P001, P002). Profil
     * majstora se ovdje ne kreira.
     *
     * Ulaz: $korisnik – email, lozinka i opcionalna uloga.
     * Izlaz: Novokreirani korisnik bez lozinka_hash ili izuzetak za neispravne podatke.
     *
     * Povezana poslovna pravila:
     *   P001 – U početnoj verziji administrator kreira račune majstora s jedinstvenim e-mailom.
     *   P002 – Majstor se kasnije prijavljuje dodijeljenim podacima.
     */
    public function add($korisnik)
    {
        if (!is_array($korisnik)) {
            throw new ValidationException('Podaci korisnika moraju biti objekt.');
        }
        // Iz zahtjeva zadržavamo dozvoljena polja; direktni unos lozinka_hash nije dozvoljen.
        $korisnik = $this->normalize($korisnik);
        $korisnik['uloga'] = $korisnik['uloga'] ?? Roles::MAJSTOR;
        // Provjeravamo email, ulogu i eventualno duplicirani email.
        $this->validate($korisnik);
        Validator::required($korisnik, ['lozinka']);
        Validator::password($korisnik['lozinka'], 'lozinka');

        // Prije slanja DAO-u običnu lozinku pretvaramo u bcrypt hash.
        $novi = $this->dao->add([
            'email' => $korisnik['email'],
            'uloga' => $korisnik['uloga'],
            'lozinka_hash' => password_hash($korisnik['lozinka'], PASSWORD_BCRYPT)
        ]);
        // Korisniku vraćamo podatke bez hash vrijednosti.
        return $this->get_by_id($novi['id']);
    }

    /**
     * Mijenja e-mail i ulogu postojećeg korisnika, a po potrebi i njegovu lozinku.
     *
     * Prvo provjerava postojanje računa. Zatim spaja trenutne podatke s dozvoljenim promjenama i provjerava e-mail i ulogu.
     * Lozinku ažurira samo ako je poslata nova neprazna vrijednost, i tada je pretvara u bcrypt hash. DAO-u nikad ne predaje
     * uneseni lozinka_hash.
     *
     * Ulaz: $korisnik – polja izmjene; $id – ID korisničkog računa.
     * Izlaz: Ažurirani korisnik bez hash vrijednosti ili odgovarajući izuzetak.
     */
    public function update($korisnik, $id)
    {
        // Pronalazimo postojeći račun i prijavljujemo 404 ako ga nema.
        $postojeci = $this->dao->get_by_id($id);
        if (!$postojeci) {
            throw new NotFoundException('Korisnik nije pronađen.');
        }
        $promjene = $this->normalize($korisnik);
        // Spajamo stare vrijednosti s dozvoljenim novim vrijednostima.
        $podaci = array_merge($postojeci, $promjene);
        $this->validate($podaci, $id);

        $upis = [
            'email' => $podaci['email'],
            'uloga' => $podaci['uloga']
        ];
        // Lozinku mijenjamo samo kada je korisnik zaista poslao novu.
        if (isset($promjene['lozinka']) && $promjene['lozinka'] !== '') {
            Validator::password($promjene['lozinka'], 'lozinka');
            $upis['lozinka_hash'] = password_hash($promjene['lozinka'], PASSWORD_BCRYPT);
        }
        // DAO dobija samo email/ulogu i eventualno novi hash, nikad običnu lozinku.
        $this->dao->update($upis, $id);
        return $this->get_by_id($id);
    }

    /**
     * Omogućava korisniku da promijeni lozinku nakon provjere postojeće lozinke.
     *
     * Učitava račun s hashom, provjerava staru lozinku pomoću password_verify(), a zatim validira novu lozinku. U bazu šalje samo
     * hash nove lozinke. Identitet prijavljenog korisnika i dozvole provjeravat će ruta/middleware u M4 (P006).
     *
     * Ulaz: $korisnik_id – ID računa; $stara_lozinka – trenutna; $nova_lozinka – željena lozinka.
     * Izlaz: Korisnik bez hash vrijednosti ili izuzetak ako provjera ne uspije.
     *
     * Povezana poslovna pravila:
     *   P006 – Majstor može promijeniti svoju lozinku, a baza čuva samo njen hash.
     */
    public function promijeni_lozinku($korisnik_id, $stara_lozinka, $nova_lozinka)
    {
        // Učitavamo korisnički račun i njegov trenutni hash.
        $korisnik = $this->dao->get_by_id($korisnik_id);
        if (!$korisnik) {
            throw new NotFoundException('Korisnik nije pronađen.');
        }
        // Upoređujemo unesenu staru lozinku s pohranjenim hashom.
        if (!password_verify((string) $stara_lozinka, $korisnik['lozinka_hash'])) {
            throw new ValidationException('Trenutna lozinka nije ispravna.');
        }
        Validator::password($nova_lozinka, 'nova_lozinka');
        // Tek poslije provjere spremamo bcrypt hash nove lozinke.
        $this->dao->update(['lozinka_hash' => password_hash($nova_lozinka, PASSWORD_BCRYPT)], $korisnik_id);
        return $this->get_by_id($korisnik_id);
    }

    /**
     * Provjerava odgovaraju li uneseni e-mail i lozinka postojećem računu.
     *
     * DAO pronađe korisnika po normalizovanom e-mailu i vrati hash koji služi samo za password_verify(). Ako račun ne postoji ili
     * lozinka nije tačna, prijavljuje UnauthorizedException (401). U M3 ova metoda ne izdaje JWT token; prijava s JWT-om dolazi u
     * M4 (P002).
     *
     * Ulaz: $email – e-mail prijave; $lozinka – unesena lozinka.
     * Izlaz: Korisnik bez hasha ili greška HTTP 401.
     *
     * Povezana poslovna pravila:
     *   P002 – Majstor se prijavljuje e-mailom i dodijeljenom lozinkom.
     */
    public function provjeri_prijavu($email, $lozinka)
    {
        // Pronalazimo korisnika preko normalizovane email adrese.
        $korisnik = $this->dao->get_by_email(strtolower(trim((string) $email)));
        // Ne odajemo je li greška u emailu ili lozinci.
        if (!$korisnik || !password_verify((string) $lozinka, $korisnik['lozinka_hash'])) {
            throw new UnauthorizedException('Neispravan email ili lozinka.');
        }
        return $this->bez_hasha($korisnik);
    }

    /**
     * Briše korisnički račun iz baze preko zajedničkog postupka BaseService.
     *
     * BaseService provjerava postojanje i poziva DAO delete(). Prema našoj SQL šemi ON DELETE CASCADE, brisanjem računa
     * automatski se uklanjaju i njegov profil majstora i veze profila s kategorijama. Ovo nije brisanje samo profila.
     *
     * Ulaz: $id – ID korisničkog računa za brisanje.
     * Izlaz: Ne vraća podatke; nepostojeći račun izaziva grešku 404.
     */
    public function delete($id)
    {
        // Osnovni servis provjerava postojanje, a DAO briše račun; FK obrađuje veze.
        parent::delete($id);
    }

    /**
     * Provjerava ispravnost e-maila i korisničke uloge prije spremanja računa.
     *
     * E-mail i uloga su obavezni. E-mail mora imati odgovarajući format i dužinu; uloga mora biti admin ili majstor. DAO-om se
     * provjerava da drugi korisnik nema isti e-mail; kod izmjene vlastiti ID se ne smatra duplikatom (P001).
     *
     * Ulaz: $korisnik – podaci za provjeru; $id – ID pri izmjeni ili null pri kreiranju.
     * Izlaz: Nema povratne vrijednosti; ValidationException za neispravne podatke.
     *
     * Povezana poslovna pravila:
     *   P001 – Svaki korisnički račun mora imati jedinstvenu e-mail adresu.
     */
    protected function validate($korisnik, $id = null)
    {
        // Email i uloga su obavezni, a dozvoljene uloge dolaze iz Roles.php.
        Validator::required($korisnik, ['email', 'uloga']);
        Validator::length($korisnik['email'], 'email', 3, 100);
        Validator::email($korisnik['email']);
        Validator::one_of($korisnik['uloga'], 'uloga', Roles::all());

        // Provjeravamo da drugi račun već ne koristi istu email adresu.
        $postojeci = $this->dao->get_by_email(strtolower(trim($korisnik['email'])));
        if ($postojeci && (int) $postojeci['id'] !== (int) $id) {
            throw new ValidationException('Korisnički račun s ovim emailom već postoji.');
        }
    }

    /**
     * Uklanja hash lozinke iz jednog korisničkog zapisa prije slanja podataka dalje.
     *
     * DAO mora imati pristup hashu radi provjere prijave, ali prikaz korisnika i REST odgovor ne smiju ga sadržavati. Zato ova
     * metoda uklanja ključ lozinka_hash iz kopije niza.
     *
     * Ulaz: $korisnik – jedan zapis korisnika.
     * Izlaz: Isti korisnički podaci bez polja lozinka_hash.
     */
    private function bez_hasha($korisnik)
    {
        // Brisanje ključa iz PHP niza ne briše hash iz baze; samo skriva odgovor.
        unset($korisnik['lozinka_hash']);
        return $korisnik;
    }

    /**
     * Priprema korisničke podatke za validaciju i siguran upis.
     *
     * Prihvata samo email, uloga i lozinka; polje lozinka_hash iz HTTP zahtjeva odbacuje. E-mail prevodi u mala slova i uklanja
     * razmake, a kod uloge uklanja okolne razmake. Obična lozinka se ne sprema dok se kasnije ne pretvori u hash.
     *
     * Ulaz: $korisnik – ulazni asocijativni niz.
     * Izlaz: Niz dozvoljenih i pripremljenih polja.
     */
    private function normalize($korisnik)
    {
        if (!is_array($korisnik)) {
            throw new ValidationException('Podaci korisnika moraju biti objekt.');
        }
        // Zadržavamo samo polja koja API smije primiti.
        $podaci = array_intersect_key($korisnik, array_flip(['email', 'uloga', 'lozinka']));
        // Email se sprema u ujednačenom obliku radi lakšeg poređenja.
        if (array_key_exists('email', $podaci)) {
            $podaci['email'] = strtolower(trim((string) $podaci['email']));
        }
        if (array_key_exists('uloga', $podaci)) {
            $podaci['uloga'] = trim((string) $podaci['uloga']);
        }
        return $podaci;
    }
}
