<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/GradDao.php';
require_once __DIR__ . '/../dao/MajstorDao.php';

/**
 * Servis za upravljanje gradovima i njihovim koordinatama.
 *
 * Provjerava naziv i koordinate, odbija duplikate te štiti gradove koje već koriste profili majstora. Nominatim služi samo za
 * pronalaženje koordinata, a GradService sprema već potvrđene podatke.
 */
class GradService extends BaseService
{
    protected $entity_name = 'Grad';
    private $majstor_dao;

    /**
     * Priprema pristup gradovima i profilima majstora.
     *
     * GradDao služi za čitanje i spremanje gradova, a MajstorDao za provjeru koristi li neki majstor određeni grad. Kada objekti
     * nisu proslijeđeni, kreiraju se podrazumijevani DAO objekti.
     *
     * Ulaz: $dao – opcionalni GradDao; $majstor_dao – opcionalni MajstorDao.
     * Izlaz: Nema povratne vrijednosti.
     */
    public function __construct($dao = null, $majstor_dao = null)
    {
        // Pozivamo konstruktor BaseService i dodjeljujemo DAO za gradove.
        parent::__construct($dao ?? new GradDao());
        $this->majstor_dao = $majstor_dao ?? new MajstorDao();
    }

    /**
     * Dodaje novi grad u našu bazu, nakon provjere naziva i koordinata.
     *
     * Prvo zadržava samo dozvoljena polja i uklanja suvišne razmake iz naziva. BaseService zatim poziva validate(), koja
     * provjerava obavezna polja, raspone koordinata i duplikate. Administrator treba prethodno potvrditi lokaciju na karti (P009,
     * V004); ova metoda ne provjerava samu radnju klika na potvrdu.
     *
     * Ulaz: $grad – niz s nazivom, latitude i longitude.
     * Izlaz: Novi grad iz baze ili ValidationException za neispravne podatke.
     *
     * Povezana poslovna pravila:
     *   P009 – Administrator dodaje grad nakon pregleda pronađene lokacije.
     *   V004 – Lokacija treba biti potvrđena prije slanja podataka na spremanje.
     */
    public function add($grad)
    {
        // Izbacujemo polja koja ne pripadaju tabeli i čistimo naziv.
        $grad = $this->normalize($grad);
        // BaseService sada radi validaciju i pravi upis preko GradDao.
        return parent::add($grad);
    }

    /**
     * Ažurira grad preko API-ja samo kada promjena ne narušava postojeće profile majstora.
     *
     * Prvo učitava grad, zatim spaja stare i nove podatke. Ako se mijenja naziv ili koordinate grada koji koristi neki majstor,
     * izmjena se odbija greškom 409. U suprotnom se podaci validiraju i spremaju. Naš postojeći frontend nema ovu mogućnost, u
     * skladu s pravilom P010.
     *
     * Ulaz: $grad – nova polja grada; $id – ID postojećeg grada.
     * Izlaz: Ažurirani grad ili izuzetak 404/409/400.
     *
     * Povezana poslovna pravila:
     *   P010 – U postojećem GUI-ju nije predviđena izmjena već spremljenog grada; API operacija je zasebno ograničena.
     */
    public function update($grad, $id)
    {
        // Učitavamo trenutno stanje radi poređenja sa traženim izmjenama.
        $postojeci = $this->get_by_id($id);
        $promjene = $this->normalize($grad);
        // Spajamo stare podatke s poljima koja su stvarno poslata.
        $podaci = array_merge($postojeci, $promjene);
        $promijenjena_lokacija = $podaci['naziv'] !== $postojeci['naziv'] ||
            (float) $podaci['latitude'] !== (float) $postojeci['latitude'] ||
            (float) $podaci['longitude'] !== (float) $postojeci['longitude'];
        // P010: ne mijenjamo lokaciju koju već koriste profili majstora.
        // Ako bi se lokacija promijenila, a neki majstor koristi grad, odbijamo izmjenu.
        if ($promijenjena_lokacija && $this->koristi_se($id)) {
            throw new ConflictException('Grad koriste profili majstora i ne može se mijenjati.');
        }
        return parent::update($podaci, $id);
    }

    /**
     * Briše grad samo ako nije dodijeljen nijednom majstoru.
     *
     * Prvo provjerava postoji li grad, zatim poziva koristi_se() da pretraži profile majstora. Ako barem jedan profil koristi taj
     * grad, baca ConflictException (HTTP 409); inače DAO briše grad. Naš frontend ne nudi dugme za brisanje (P010).
     *
     * Ulaz: $id – ID grada koji želimo obrisati.
     * Izlaz: Nema povratnog zapisa; izuzetak 404 ako grad ne postoji ili 409 ako se koristi.
     *
     * Povezana poslovna pravila:
     *   P010 – Administratorski frontend ne nudi brisanje već spremljenih gradova.
     */
    public function delete($id)
    {
        // Najprije potvrdimo da grad postoji.
        $this->get_by_id($id);
        // Pravilo integriteta: grad povezan s profilima ne brišemo.
        if ($this->koristi_se($id)) {
            throw new ConflictException('Grad koriste profili majstora i ne može se obrisati.');
        }
        // Tek nezauzeti grad predajemo DAO-u za fizičko brisanje.
        $this->dao->delete($id);
    }

    /**
     * Provjerava je li neki profil majstora povezan sa zadanim gradom.
     *
     * Čita sve profile preko MajstorDao i poredi njihov grad_id sa traženim ID-jem. Odmah vraća true na prvom podudaranju; ako
     * nijedan majstor ne koristi grad, vraća false. Ova provjera se koristi prije izmjene i brisanja.
     *
     * Ulaz: $grad_id – ID grada koji provjeravamo.
     * Izlaz: true ako ga koristi barem jedan majstor; false ako ga niko ne koristi.
     */
    private function koristi_se($grad_id)
    {
        // Pregledamo profile majstora i tražimo podudaranje njihovog grad_id.
        foreach ($this->majstor_dao->get_all() as $majstor) {
            if ((int) $majstor['grad_id'] === (int) $grad_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Provjerava jesu li podaci grada ispravni i smije li se grad upisati.
     *
     * Naziv i obje koordinate su obavezni. Latitude mora biti između -90 i 90, a longitude između -180 i 180. Naziv mora imati
     * propisanu dužinu i ne smije već pripadati drugom gradu; kod izmjene isti ID nije duplikat.
     *
     * Ulaz: $grad – podaci grada; $id – ID postojećeg grada pri izmjeni ili null pri dodavanju.
     * Izlaz: Ne vraća podatke; baca ValidationException ako neka provjera ne uspije.
     */
    protected function validate($grad, $id = null)
    {
        // Sva tri podatka su obavezna pri spremanju grada.
        Validator::required($grad, ['naziv', 'latitude', 'longitude']);
        Validator::length($grad['naziv'], 'naziv', 2, 100);

        // Geografska širina mora biti broj u rasponu od -90 do 90.
        if (!is_numeric($grad['latitude']) || !is_finite((float) $grad['latitude']) ||
            (float) $grad['latitude'] < -90 || (float) $grad['latitude'] > 90) {
            throw new ValidationException('Latitude mora biti broj između -90 i 90.');
        }
        // Geografska dužina mora biti broj u rasponu od -180 do 180.
        if (!is_numeric($grad['longitude']) || !is_finite((float) $grad['longitude']) ||
            (float) $grad['longitude'] < -180 || (float) $grad['longitude'] > 180) {
            throw new ValidationException('Longitude mora biti broj između -180 i 180.');
        }

        // Na kraju provjeravamo da drugi grad nema isti naziv.
        $postojeci = $this->dao->get_by_naziv(trim($grad['naziv']));
        if ($postojeci && (int) $postojeci['id'] !== (int) $id) {
            throw new ValidationException('Grad s tim nazivom već postoji.');
        }
    }

    /**
     * Priprema podatke grada za validaciju i upis, bez prihvatanja nepoznatih polja.
     *
     * Provjerava da li je ulaz niz, zadržava samo naziv i koordinate, a iz naziva uklanja razmake na početku i kraju. Poslano
     * polje id ne može se ovim putem upisati ili mijenjati.
     *
     * Ulaz: $grad – ulazni niz s podacima grada.
     * Izlaz: Niz dozvoljenih polja sa očišćenim nazivom.
     */
    private function normalize($grad)
    {
        if (!is_array($grad)) {
            throw new ValidationException('Podaci grada moraju biti objekt.');
        }
        // Prihvatamo samo naziv i koordinate; dodatna polja poput id zanemarujemo.
        $podaci = array_intersect_key($grad, array_flip(['naziv', 'latitude', 'longitude']));
        if (isset($podaci['naziv'])) {
            $podaci['naziv'] = trim((string) $podaci['naziv']);
        }
        return $podaci;
    }
}
