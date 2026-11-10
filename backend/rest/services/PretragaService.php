<?php
require_once __DIR__ . '/BaseService.php';
require_once __DIR__ . '/../dao/MajstorDao.php';
require_once __DIR__ . '/../dao/KategorijaDao.php';
require_once __DIR__ . '/../dao/GradDao.php';

/**
 * Servis za provjeru filtera i pretragu majstora.
 *
 * Prima majstorluke, grad i radijus. Odbija neispravne filtere i poziva MajstorDao::pretrazi(), gdje već postoje SQL provjera
 * hijerarhije i proračun udaljenosti iz baze.
 */
class PretragaService
{
    private $majstor_dao;
    private $kategorija_dao;
    private $grad_dao;

    /**
     * Priprema DAO objekte potrebne za filtriranje majstora.
     *
     * MajstorDao već ima SQL pretragu, KategorijaDao služi da provjerimo postoje li odabrani majstorluci, a GradDao da provjerimo
     * izabrani grad.
     *
     * Ulaz: $majstor_dao, $kategorija_dao, $grad_dao – opcionalni DAO objekti.
     * Izlaz: Nema povratne vrijednosti.
     */
    public function __construct($majstor_dao = null, $kategorija_dao = null, $grad_dao = null)
    {
        // DAO za majstore već sadrži kompletnu SQL pretragu iz M2.
        $this->majstor_dao = $majstor_dao ?? new MajstorDao();
        $this->kategorija_dao = $kategorija_dao ?? new KategorijaDao();
        $this->grad_dao = $grad_dao ?? new GradDao();
    }

    /**
     * Pretražuje majstore prema traženim majstorlucima, gradu i opcionalnom radijusu.
     *
     * Kategorije mogu stići kao niz ID-jeva ili tekst poput „15,18“. Servis provjerava sve ID-jeve, uklanja duplikate i
     * provjerava grad. Ako je zadan radijus, mora postojati grad i radijus mora biti nenegativan broj kilometara. Tek potom
     * poziva postojeći MajstorDao::pretrazi() iz M2. DAO provjerava hijerarhiju kategorija i računa zračnu udaljenost iz
     * spremljenih koordinata, bez pozivanja Nominatima.
     *
     * Ulaz: $kategorije – niz ID-jeva ili tekst „15,18“; $grad_id – ID grada ili null; $radijus_km – broj km ili null.
     * Izlaz: Niz s ključevima majstori (pronađeni profili) i count (broj rezultata).
     *
     * Povezana poslovna pravila:
     *   P004 – Pretraga po hijerarhiji koristi direktne kategorije koje su majstori odabrali.
     *   P005 – Geografska pretraga koristi koordinate već spremljenih gradova.
     */
    public function pretrazi($kategorije = [], $grad_id = null, $radijus_km = null)
    {
        // Query parametar može stići kao tekst „15,18“, pa ga pretvaramo u niz.
        if (is_string($kategorije)) {
            $kategorije = trim($kategorije) === '' ? [] : explode(',', $kategorije);
        }
        if (!is_array($kategorije)) {
            throw new ValidationException('Kategorije moraju biti niz ili lista ID-jeva odvojena zarezima.');
        }

        // Provjeravamo svaki traženi ID kategorije i uklanjamo duplikate.
        $ids = [];
        foreach ($kategorije as $id) {
            $id = trim((string) $id);
            Validator::integer($id, 'kategorija_id', 1);
            if (!$this->kategorija_dao->get_by_id($id)) {
                throw new ValidationException('Odabrana kategorija ne postoji.');
            }
            $ids[] = (int) $id;
        }
        $ids = array_values(array_unique($ids));

        // Prazne vrijednosti iz HTTP zahtjeva tretiramo kao neizabrane filtere.
        if ($grad_id === '') {
            $grad_id = null;
        }
        if ($radijus_km === '') {
            $radijus_km = null;
        }
        // Ako je grad zadan, mora postojati u bazi.
        if ($grad_id !== null) {
            Validator::integer($grad_id, 'grad_id', 1);
            if (!$this->grad_dao->get_by_id($grad_id)) {
                throw new ValidationException('Odabrani grad ne postoji.');
            }
            $grad_id = (int) $grad_id;
        }
        // Radijus ima smisla samo uz grad i mora biti broj od najmanje 0.
        if ($radijus_km !== null) {
            if ($grad_id === null) {
                throw new ValidationException('Za pretragu po radijusu prvo odaberite grad.');
            }
            if (!is_numeric($radijus_km) || !is_finite((float) $radijus_km) || (float) $radijus_km < 0) {
                throw new ValidationException('Radijus mora biti nenegativan broj kilometara.');
            }
            $radijus_km = (float) $radijus_km;
        }

        // Jedini SQL poziv za pretragu: postojeća DAO metoda iz M2.
        // DAO računa hijerarhiju kategorija i zračnu udaljenost na osnovu koordinata iz baze; ne zove Nominatim.
        $majstori = $this->majstor_dao->pretrazi($ids, $grad_id, $radijus_km);
        return ['majstori' => $majstori, 'count' => count($majstori)];
    }
}
