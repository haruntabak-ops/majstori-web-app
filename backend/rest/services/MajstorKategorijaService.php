<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/MajstorKategorijaDao.php';
require_once __DIR__ . '/../dao/MajstorDao.php';
require_once __DIR__ . '/../dao/KategorijaDao.php';

/**
 * Servis za direktne veze majstora i kategorija usluga.
 *
 * Tabela MAJSTOR_KATEGORIJE ima složeni ključ (majstor_id, kategorija_id) i nema obični id. Servis čuva samo direktno izabrane
 * kategorije te u jednoj transakciji može zamijeniti cijeli izbor.
																				  
 */
class MajstorKategorijaService
{
    private $dao;
    private $majstor_dao;
    private $kategorija_dao;

    /**
     * Povezuje servis za direktne majstorluke s tri potrebna DAO objekta.
     *
     * MajstorKategorijaDao čita i mijenja veze, MajstorDao provjerava postojanje majstora, a KategorijaDao postojanje kategorije.
     * Ovaj servis ne nasljeđuje BaseService jer vezna tabela nema obični id.
     *
     * Ulaz: $dao – DAO za veze; $majstor_dao – DAO za majstore; $kategorija_dao – DAO za kategorije.
     * Izlaz: Nema povratne vrijednosti.
     */
    public function __construct($dao = null, $majstor_dao = null, $kategorija_dao = null)
    {
        // DAO objekti rade nad tri postojeće tabele; ovaj servis nema svoj id.
        $this->dao = $dao ?? new MajstorKategorijaDao();
        $this->majstor_dao = $majstor_dao ?? new MajstorDao();
        $this->kategorija_dao = $kategorija_dao ?? new KategorijaDao();
    }

    /**
     * Dohvata sve postojeće direktne veze između majstora i kategorija.
     *
     * Svaka veza je jedan zapis iz MAJSTOR_KATEGORIJE s dva polja ključa: majstor_id i kategorija_id. Ne prikazuje automatski
     * potomke iz stabla kategorija (P004).
     *
     * Ulaz: Nema parametara.
     * Izlaz: Niz svih zapisa veze.
     *
     * Povezana poslovna pravila:
     *   P004 – Čuvaju se samo kategorije koje je majstor direktno odabrao.
     */
    public function get_all()
    {
        // Čitamo već zapisane direktne parove majstor–kategorija.
        return $this->dao->get_all();
    }

    /**
     * Dohvata sve kategorije koje je određeni majstor direktno odabrao.
     *
     * Provjerava da je ID pozitivan cijeli broj i da majstor postoji. Zatim DAO vraća njegove direktne veze, bez automatskog
     * dodavanja podkategorija (P004).
     *
     * Ulaz: $majstor_id – ID profila majstora.
     * Izlaz: Niz veza s odabranim kategorijama ili izuzetak za neispravan ID.
     *
     * Povezana poslovna pravila:
     *   P004 – Direktni odabir kategorije ne znači izbor njene djece.
     */
    public function get_by_majstor_id($majstor_id)
    {
        // Provjeravamo ID i postojanje majstora prije čitanja njegovih veza.
        Validator::integer($majstor_id, 'majstor_id', 1);
        $this->provjeri_majstora($majstor_id);
        return $this->dao->get_by_majstor_id($majstor_id);
    }

    /**
     * Dohvata veze svih majstora koji su direktno odabrali jednu kategoriju.
     *
     * Prvo provjerava da je ID kategorije ispravan i da kategorija postoji, a zatim koristi DAO upit po kategorija_id. Rezultat
     * su direktne veze, ne majstori koje bi pretraga pronašla posredno preko potomaka.
     *
     * Ulaz: $kategorija_id – ID tražene kategorije.
     * Izlaz: Niz veza s tom kategorijom.
     */
    public function get_by_kategorija_id($kategorija_id)
    {
        // Provjeravamo ID i postojanje kategorije prije čitanja veza.
        Validator::integer($kategorija_id, 'kategorija_id', 1);
        $this->provjeri_kategoriju($kategorija_id);
        return $this->dao->get_by_kategorija_id($kategorija_id);
    }

    /**
     * Pronalazi jednu vezu prema složenom ključu koji čine ID majstora i ID kategorije.
     *
     * Najprije provjerava majstora i učitava njegove veze. Među njima traži zapis s traženim kategorija_id. Ako veza ne postoji,
     * vraća se greška 404; tabela nema zasebnu kolonu id.
     *
     * Ulaz: $majstor_id – ID profila; $kategorija_id – ID kategorije.
     * Izlaz: Tačno jedna veza ili NotFoundException (404).
     */
    public function get_by_keys($majstor_id, $kategorija_id)
    {
        // Učitavamo sve veze tog majstora.
        $veze = $this->get_by_majstor_id($majstor_id);
        // Tražimo par koji ima i odgovarajući kategorija_id.
        foreach ($veze as $veza) {
            if ((int) $veza['kategorija_id'] === (int) $kategorija_id) {
                return $veza;
            }
        }
        throw new NotFoundException('Tražena veza majstora i kategorije ne postoji.');
    }

    /**
     * Dodaje kategoriju u direktan izbor majstorluka određenog majstora.
     *
     * Provjerava da postoje oba ID-a i da su ispravni. Zatim odbija duplikat, odnosno pokušaj da se ista veza majstor–kategorija
     * upiše drugi put. Tek onda poziva DAO add(). Ne dodaje automatski potkategorije (P004).
     *
     * Ulaz: $veza – niz sa majstor_id i kategorija_id.
     * Izlaz: Novokreirana veza ili ValidationException za neispravne podatke.
     *
     * Povezana poslovna pravila:
     *   P004 – Majstor samostalno bira koje će kategorije direktno povezati sa svojim profilom.
     */
    public function add($veza)
    {
        if (!is_array($veza)) {
            throw new ValidationException('Podaci veze moraju biti objekt.');
        }
        // Oba dijela složenog ključa su obavezna.
        Validator::required($veza, ['majstor_id', 'kategorija_id']);
        Validator::integer($veza['majstor_id'], 'majstor_id', 1);
        Validator::integer($veza['kategorija_id'], 'kategorija_id', 1);
        // Veza se smije dodati samo ako oba povezana zapisa postoje.
        $this->provjeri_majstora($veza['majstor_id']);
        $this->provjeri_kategoriju($veza['kategorija_id']);

        // Duplu vezu ne dodajemo drugi put.
        foreach ($this->dao->get_by_majstor_id($veza['majstor_id']) as $postojeca) {
            if ((int) $postojeca['kategorija_id'] === (int) $veza['kategorija_id']) {
                throw new ValidationException('Majstor je već odabrao ovu kategoriju.');
            }
        }
        // Nakon provjera DAO upisuje novi par ID-jeva.
        return $this->dao->add($veza);
    }

    /**
     * Uklanja jednu konkretnu kategoriju iz direktnog izbora određenog majstora.
     *
     * Pošto vezna tabela nema obični id, veza se prepoznaje po oba ključa. Najprije provjerava da postoji, a zatim DAO briše
     * upravo taj par majstor_id i kategorija_id.
     *
     * Ulaz: $majstor_id – ID profila; $kategorija_id – ID kategorije.
     * Izlaz: Ne vraća zapis; greška 404 ako veza nije pronađena.
     */
    public function delete_by_keys($majstor_id, $kategorija_id)
    {
        // Prije brisanja potvrđujemo da baš ova veza postoji.
        $this->get_by_keys($majstor_id, $kategorija_id);
        $this->dao->delete_by_keys($majstor_id, $kategorija_id);
    }

    /**
     * Sprema kompletan novi izbor majstorluka jednog majstora u jednoj transakciji.
     *
     * Prvo provjerava majstora i sve poslane kategorije te uklanja duplikate iz novih ID-jeva. Zatim upoređuje stari i novi
     * izbor: veze koje više nisu izabrane briše, nepromijenjene ostavlja, a nove dodaje (P004). Brisanje i dodavanje obavljaju se
     * u jednoj transakciji; pri grešci se poništavaju sve izmjene. Prazan niz znači ukloniti sve odabrane kategorije.
     *
     * Ulaz: $majstor_id – ID profila; $kategorija_ids – niz ID-jeva odabranih kategorija.
     * Izlaz: Konačna lista direktnih veza nakon uspješnog spremanja.
     *
     * Povezana poslovna pravila:
     *   P004 – Spremaju se samo direktni odabiri majstora, bez automatskog izbora potomaka.
     */
    public function zamijeni_izbor($majstor_id, $kategorija_ids)
    {
        // Najprije provjeravamo da majstor postoji.
        Validator::integer($majstor_id, 'majstor_id', 1);
        $this->provjeri_majstora($majstor_id);
        if (!is_array($kategorija_ids)) {
            throw new ValidationException('Majstorluci moraju biti niz ID-jeva.');
        }

        // Pripremamo listu novih kategorija i provjeravamo svaku pojedinačno.
        $novi_ids = [];
        foreach ($kategorija_ids as $kategorija_id) {
            Validator::integer($kategorija_id, 'kategorija_id', 1);
            $this->provjeri_kategoriju($kategorija_id);
            $novi_ids[] = (int) $kategorija_id;
        }
        // Uklanjamo ponovljene ID-jeve iz primljenog izbora.
        $novi_ids = array_values(array_unique($novi_ids));

        // Dohvatamo trenutni izbor kako bismo računali samo razlike.
        $stare_veze = $this->dao->get_by_majstor_id($majstor_id);
        $stari_ids = [];
        foreach ($stare_veze as $veza) {
            $stari_ids[] = (int) $veza['kategorija_id'];
        }

        // Sve izmjene izvršavamo u jednoj transakciji: ili uspiju sve ili nijedna.
        $this->dao->begin_transaction();
        try {
            // Prvo uklanjamo odabire koji više nisu na novoj listi.
            foreach ($stari_ids as $stari_id) {
                if (!in_array($stari_id, $novi_ids, true)) {
                    $this->dao->delete_by_keys($majstor_id, $stari_id);
                }
            }
            // Zatim dodajemo samo nove odabire; stare, neizmijenjene veze ostaju.
            foreach ($novi_ids as $novi_id) {
                if (!in_array($novi_id, $stari_ids, true)) {
                    $this->dao->add(['majstor_id' => $majstor_id, 'kategorija_id' => $novi_id]);
                }
            }
            // Potvrđujemo transakciju nakon svih uspješnih upisa i brisanja.
            $this->dao->commit();
        } catch (Throwable $e) {
            // Pri grešci vraćamo prethodni izbor majstorluka.
            $this->dao->rollback();
            throw $e;
        }
        return $this->dao->get_by_majstor_id($majstor_id);
    }

    /**
     * Provjerava da profil majstora naveden u vezi zaista postoji.
     *
     * Koristi MajstorDao::get_by_id() i odbija nepostojeći profil prije rada s veznom tabelom. Ova pomoćna metoda se poziva u
     * više javnih operacija.
     *
     * Ulaz: $majstor_id – ID profila majstora.
     * Izlaz: Nema povratne vrijednosti; ValidationException ako profil ne postoji.
     */
    private function provjeri_majstora($majstor_id)
    {
        // DAO provjerava postoji li majstor s dostavljenim ID-jem.
        if (!$this->majstor_dao->get_by_id($majstor_id)) {
            throw new ValidationException('Odabrani majstor ne postoji.');
        }
    }

    /**
     * Provjerava da kategorija navedena u vezi zaista postoji.
     *
     * Koristi KategorijaDao::get_by_id() i odbija nepostojeću kategoriju prije dodavanja ili zamjene direktnih odabira.
     *
     * Ulaz: $kategorija_id – ID kategorije.
     * Izlaz: Nema povratne vrijednosti; ValidationException ako kategorija ne postoji.
     */
    private function provjeri_kategoriju($kategorija_id)
    {
        // DAO provjerava postoji li kategorija s dostavljenim ID-jem.
        if (!$this->kategorija_dao->get_by_id($kategorija_id)) {
            throw new ValidationException('Odabrana kategorija ne postoji.');
        }
    }
}
