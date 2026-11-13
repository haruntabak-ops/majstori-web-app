<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/MajstorDao.php';
require_once __DIR__ . '/../dao/KorisnikDao.php';
require_once __DIR__ . '/../dao/GradDao.php';
require_once __DIR__ . '/../dao/KategorijaDao.php';
require_once __DIR__ . '/../dao/MajstorKategorijaDao.php';
require_once __DIR__ . '/MajstorKategorijaService.php';
require_once __DIR__ . '/../../utils/Roles.php';

/**
 * Servis za profile majstora i podatke koji se prikazuju posjetiocima.
 *
 * Svaki profil pripada postojećem korisniku s ulogom majstor i koristi grad iz baze. Servis omogućava uređivanje profila i
 * pridružuje mu direktno izabrane majstorluke.
 */
class MajstorService extends BaseService
{
    protected $entity_name = 'Majstor';
    private $korisnik_dao;
    private $grad_dao;
    private $kategorija_dao;
    private $majstor_kategorija_dao;
    private $majstorluci_service;

    /**
     * Priprema sve DAO objekte i servis za povezivanje profila s majstorlucima.
     *
     * Koristi MajstorDao za profile, KorisnikDao za provjeru vlasnika, GradDao za izbor grada, te KategorijaDao i
     * MajstorKategorijaDao za prikaz kategorija. Posebni MajstorKategorijaService izvršava transakcijsku zamjenu izbora.
     *
     * Ulaz: $dao i ostali opcionalni DAO/servis objekti – koriste se ako su proslijeđeni.
     * Izlaz: Nema povratne vrijednosti.
     */
    public function __construct($dao = null, $korisnik_dao = null, $grad_dao = null,
                                $kategorija_dao = null, $majstor_kategorija_dao = null,
                                $majstorluci_service = null)
    {
        // Osnovni DAO vodi profil, a dodatni DAO objekti provjeravaju korisnika, grad i kategorije.
        parent::__construct($dao ?? new MajstorDao());
        $this->korisnik_dao = $korisnik_dao ?? new KorisnikDao();
        $this->grad_dao = $grad_dao ?? new GradDao();
        $this->kategorija_dao = $kategorija_dao ?? new KategorijaDao();
        $this->majstor_kategorija_dao = $majstor_kategorija_dao ?? new MajstorKategorijaDao();
        // Za spremanje izbora majstorluka koristimo zaseban servis za veznu tabelu.
        $this->majstorluci_service = $majstorluci_service ?? new MajstorKategorijaService(
            $this->majstor_kategorija_dao, $this->dao, $this->kategorija_dao
        );
    }

    /**
     * Dohvata javni profil jednog majstora zajedno s direktno odabranim majstorlucima.
     *
     * Prvo učitava profil preko BaseService, a zatim dodaj_majstorluke() dodaje listu ID-jeva kategorija i njihove nazive.
     * Prikazuje samo ono što je majstor stvarno odabrao, bez automatskog nasljeđivanja potkategorija (P004).
     *
     * Ulaz: $id – ID profila majstora.
     * Izlaz: Profil obogaćen poljima kategorija_ids i majstorluci ili greška 404.
     *
     * Povezana poslovna pravila:
     *   P004 – Prikazuju se direktno izabrane kategorije usluga.
     */
    public function get_by_id($id)
    {
        // Pored osnovnog profila vraćamo i direktno odabrane kategorije.
        return $this->dodaj_majstorluke(parent::get_by_id($id));
    }

    /**
     * Pronalazi profil majstora prema ID-u korisničkog računa kojem pripada.
     *
     * Provjerava ID računa, koristi MajstorDao da nađe njegov profil i zatim dodaje informacije o majstorlucima. Ako korisnik još
     * nema profil, vraća grešku 404.
     *
     * Ulaz: $korisnik_id – ID vlasnika profila.
     * Izlaz: Profil s direktnim majstorlucima ili NotFoundException.
     */
    public function get_by_korisnik_id($korisnik_id)
    {
        // Provjeravamo pozitivan ID vlasnika profila.
        Validator::integer($korisnik_id, 'korisnik_id', 1);
        $profil = $this->dao->get_by_korisnik_id($korisnik_id);
        // Ako korisnik još nema profil majstora, javlja se 404.
        if (!$profil) {
            throw new NotFoundException('Profil majstora nije pronađen.');
        }
        return $this->dodaj_majstorluke($profil);
    }

    /**
     * Kreira profil majstora za postojeći korisnički račun uloge majstor.
     *
     * Prvo izdvaja dozvoljena polja, zatim validira podatke profila, postojanje korisnika i odabranog grada (P003, P005). Odbija
     * pokušaj da isti korisnik dobije drugi profil. DAO kreira profil, a servis vraća puni prikaz s majstorlucima.
     *
     * Ulaz: $majstor – korisnik_id, grad_id, ime, prezime, telefon i opis.
     * Izlaz: Novi profil s majstorlucima ili ValidationException.
     *
     * Povezana poslovna pravila:
     *   P003 – Majstor ima svoj profil koji može uređivati.
     *   P005 – Grad se bira isključivo iz liste unaprijed spremljenih gradova.
     */
    public function add($majstor)
    {
        // Kod kreiranja dozvoljen je i korisnik_id, koji označava vlasnika.
        $majstor = $this->normalize($majstor, true);
        // Provjeravamo obavezna polja, korisničku ulogu i izabrani grad.
        $this->validate($majstor);
        // Jedan korisnik smije imati samo jedan profil majstora.
        if ($this->dao->get_by_korisnik_id($majstor['korisnik_id'])) {
            throw new ValidationException('Ovaj korisnik već ima profil majstora.');
        }
        $novi = $this->dao->add($majstor);
        return $this->get_by_id($novi['id']);
    }

    /**
     * Mijenja podatke postojećeg profila bez dopuštanja promjene vlasnika.
     *
     * Učitava trenutni profil i odbija izmjenu korisnik_id. Zatim priprema samo dozvoljena polja, spaja ih sa starim radi potpune
     * validacije i provjerava izabrani grad (P003, P005). DAO dobija samo polja izmjene; nakon spremanja vraća se obnovljeni
     * javni profil.
     *
     * Ulaz: $majstor – željene izmjene; $id – ID profila.
     * Izlaz: Ažurirani profil s majstorlucima ili odgovarajući izuzetak.
     *
     * Povezana poslovna pravila:
     *   P003 – Majstor može mijenjati podatke svog profila.
     *   P005 – Grad profila mora postojati u administratorskoj listi.
     */
    public function update($majstor, $id)
    {
        // Pronalazimo postojeći profil radi provjere vlasnika i spajanja podataka.
        $postojeci = $this->get_by_id($id);
        // Promjena korisnik_id nije dopuštena, čak i ako ostatak profila mijenjamo.
        if (is_array($majstor) && array_key_exists('korisnik_id', $majstor) &&
            (int) $majstor['korisnik_id'] !== (int) $postojeci['korisnik_id']) {
            throw new ValidationException('Nije dozvoljena promjena vlasnika profila.');
        }
        // Pri izmjeni ne prihvatamo polje korisnik_id.
        $promjene = $this->normalize($majstor, false);
        $podaci = array_merge($postojeci, $promjene);
        $this->validate($podaci, $id);
        // DAO dobija samo stvarno promijenjena dozvoljena polja.
        $this->dao->update($promjene, $id);
        return $this->get_by_id($id);
    }

    /**
     * Sprema profil određenog korisnika, bilo da ga kreira prvi put ili ažurira postojeći.
     *
     * Na osnovu korisnik_id provjerava postoji li profil. Ako postoji, poziva update(); ako ne postoji, dodaje korisnik_id
     * podacima i poziva add(). Ova metoda priprema funkcionalnost „Moj profil“ (P003); kontrola ko je prijavljen dodaje se u
     * rutama/middleware-u u M4.
     *
     * Ulaz: $korisnik_id – ID vlasnika; $podaci – podaci profila.
     * Izlaz: Novokreirani ili ažurirani profil.
     *
     * Povezana poslovna pravila:
     *   P003 – Majstor upravlja vlastitim profilom nakon prijave.
     */
    public function sacuvaj_moj_profil($korisnik_id, $podaci)
    {
        Validator::integer($korisnik_id, 'korisnik_id', 1);
        // Provjeravamo ima li ovaj korisnik već profil.
        $postojeci = $this->dao->get_by_korisnik_id($korisnik_id);
        // Postojeći profil se ažurira; inače slijedi kreiranje novog.
        if ($postojeci) {
            return $this->update($podaci, $postojeci['id']);
        }
        if (!is_array($podaci)) {
            throw new ValidationException('Podaci profila moraju biti objekt.');
        }
        $podaci['korisnik_id'] = $korisnik_id;
        return $this->add($podaci);
    }

    /**
     * Vraća listu direktno odabranih majstorluka određenog korisnika.
     *
     * Prvo preko korisnik_id pronalazi profil majstora, jer se veze u bazi čuvaju prema majstor_id. Zatim
     * MajstorKategorijaService dohvaća njegove direktne veze (P004).
     *
     * Ulaz: $korisnik_id – ID vlasnika profila.
     * Izlaz: Niz direktnih veza profila i kategorija.
     *
     * Povezana poslovna pravila:
     *   P004 – Čuvaju se direktni izbori kategorija, bez automatskog dodavanja djece.
     */
    public function get_moji_majstorluci($korisnik_id)
    {
        // Korisnički ID najprije pretvaramo u ID njegovog profila majstora.
        $profil = $this->get_by_korisnik_id($korisnik_id);
        return $this->majstorluci_service->get_by_majstor_id($profil['id']);
    }

    /**
     * Sprema kompletan novi izbor majstorluka određenog korisnika.
     *
     * Pronalazi njegov profil, pa prosleđuje majstor_id i novu listu kategorija metodi zamijeni_izbor(). Ona u transakciji briše
     * uklonjene veze, ostavlja nepromijenjene i dodaje nove (P004).
     *
     * Ulaz: $korisnik_id – ID vlasnika; $kategorija_ids – nova lista ID-jeva kategorija.
     * Izlaz: Konačna lista direktnih veza.
     *
     * Povezana poslovna pravila:
     *   P004 – Majstor sam bira kategorije usluga koje direktno povezuje sa sobom.
     */
    public function sacuvaj_moje_majstorluke($korisnik_id, $kategorija_ids)
    {
        // Pronalaženje profila prethodi transakcijskoj zamjeni njegovog izbora.
        $profil = $this->get_by_korisnik_id($korisnik_id);
        return $this->majstorluci_service->zamijeni_izbor($profil['id'], $kategorija_ids);
    }

    /**
     * Briše profil majstora, ali ne briše njegov korisnički račun.
     *
     * BaseService prvo provjerava postoji li profil, a zatim DAO briše njegov zapis. Prema SQL pravilu ON DELETE CASCADE,
     * automatski se uklanjaju i veze tog profila s kategorijama; korisnički račun ostaje u tabeli KORISNICI.
     *
     * Ulaz: $id – ID profila majstora.
     * Izlaz: Nema povratnog zapisa; greška 404 ako profil ne postoji.
     */
    public function delete($id)
    {
        // Brisanjem profila baza automatski uklanja njegove veze s kategorijama.
        parent::delete($id);
    }

    /**
     * Provjerava jesu li podaci profila potpuni i povezani s postojećim korisnikom i gradom.
     *
     * Zahtijeva ID korisnika, ID grada, ime, prezime, telefon i opis te provjerava njihove tipove i dužine. Vlasnik mora biti
     * postojeći račun s ulogom majstor, a grad mora već biti u GRADOVI (P003, P005).
     *
     * Ulaz: $majstor – podaci profila; $id – opcionalni ID pri izmjeni.
     * Izlaz: Nema povratne vrijednosti; ValidationException ako podaci nisu ispravni.
     *
     * Povezana poslovna pravila:
     *   P003 – Profil sadrži podatke koje majstor uređuje.
     *   P005 – Majstor može birati samo gradove koje je administrator prethodno dodao.
     */
    protected function validate($majstor, $id = null)
    {
        // Provjeravamo da su uneseni svi podaci potrebni za profil.
        Validator::required($majstor, ['korisnik_id', 'grad_id', 'ime', 'prezime', 'telefon', 'opis']);
        Validator::integer($majstor['korisnik_id'], 'korisnik_id', 1);
        Validator::integer($majstor['grad_id'], 'grad_id', 1);
        Validator::length($majstor['ime'], 'ime', 2, 50);
        Validator::length($majstor['prezime'], 'prezime', 2, 70);
        Validator::length($majstor['telefon'], 'telefon', 3, 30);
        // Opis je TEXT u bazi: tražimo sadržaj, bez proizvoljnog malog limita.
        Validator::length($majstor['opis'], 'opis', 1, 65535);

        // Profil mora pripadati postojećem korisniku uloge majstor.
        $racun = $this->korisnik_dao->get_by_id($majstor['korisnik_id']);
        if (!$racun || $racun['uloga'] !== Roles::MAJSTOR) {
            throw new ValidationException('Profil može imati samo postojeći korisnik s ulogom majstor.');
        }
        // Grad mora već biti upisan u našu tabelu GRADOVI.
        if (!$this->grad_dao->get_by_id($majstor['grad_id'])) {
            throw new ValidationException('Odabrani grad ne postoji.');
        }
    }

    /**
     * Dopunjuje prikaz profila nazivima i ID-jevima kategorija koje je majstor direktno odabrao.
     *
     * Prvo čita direktne veze profila i sve kategorije. Za svaku vezu pronalazi naziv odgovarajuće kategorije i popunjava dvije
     * liste: kategorija_ids za obradu i majstorluci za prikaz. Ne dodaje nadređene kategorije ili potomke koji nisu izabrani
     * (P004).
     *
     * Ulaz: $profil – asocijativni niz podataka jednog majstora.
     * Izlaz: Dopunjeni profil s poljima kategorija_ids i majstorluci.
     *
     * Povezana poslovna pravila:
     *   P004 – Direktni odabir ne podrazumijeva automatski odabir drugih kategorija.
     */
    private function dodaj_majstorluke($profil)
    {
        // Čitamo direktne veze koje je majstor izabrao, bez automatskog dodavanja potomaka.
        $veze = $this->majstor_kategorija_dao->get_by_majstor_id($profil['id']);
        $kategorije = $this->kategorija_dao->get_all();
        // Pripremamo posebno listu ID-jeva i listu parova ID/naziv za frontend.
        $profil['kategorija_ids'] = [];
        $profil['majstorluci'] = [];
        // Za svaku vezu pronalazimo odgovarajući naziv kategorije.
        foreach ($veze as $veza) {
            $profil['kategorija_ids'][] = (int) $veza['kategorija_id'];
            foreach ($kategorije as $kategorija) {
                if ((int) $kategorija['id'] === (int) $veza['kategorija_id']) {
                    $profil['majstorluci'][] = ['id' => (int) $kategorija['id'], 'naziv' => $kategorija['naziv']];
                    break;
                }
            }
        }
        return $profil;
    }

    /**
     * Izdvaja dozvoljena polja profila i uklanja višak razmaka iz teksta.
     *
     * Pri kreiranju prihvata i korisnik_id, jer se tada određuje vlasnik. Pri izmjeni taj podatak ne propušta, tako da se vlasnik
     * postojećeg profila ne može promijeniti. Čisti ime, prezime, telefon i opis.
     *
     * Ulaz: $majstor – ulazni podaci; $kreiranje – true pri kreiranju, false pri izmjeni.
     * Izlaz: Niz dozvoljenih i pripremljenih podataka.
     */
    private function normalize($majstor, $kreiranje)
    {
        if (!is_array($majstor)) {
            throw new ValidationException('Podaci profila moraju biti objekt.');
        }
        // Ograničavamo ulaz na polja profila koja smiju biti unesena.
        $polja = ['grad_id', 'ime', 'prezime', 'telefon', 'opis'];
        // Vlasnik profila smije se zadati samo pri prvom kreiranju.
        if ($kreiranje) {
            $polja[] = 'korisnik_id';
        }
        $podaci = array_intersect_key($majstor, array_flip($polja));
        // Uklanjamo okolne razmake iz tekstualnih polja.
        foreach (['ime', 'prezime', 'telefon', 'opis'] as $polje) {
            if (isset($podaci[$polje])) {
                $podaci[$polje] = trim((string) $podaci[$polje]);
            }
        }
        return $podaci;
    }
}
