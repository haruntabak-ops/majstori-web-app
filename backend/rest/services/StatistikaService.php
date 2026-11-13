<?php
require_once __DIR__ . '/../dao/MajstorDao.php';
require_once __DIR__ . '/../dao/KategorijaDao.php';
require_once __DIR__ . '/../dao/GradDao.php';

/**
 * Servis za jednostavne brojače na početnoj stranici.
 *
 * Preko postojećih DAO metoda dohvaća ukupne brojeve majstora, kategorija i gradova, bez novih tabela ili upisa.
 */
class StatistikaService
{
    private $majstor_dao;
    private $kategorija_dao;
    private $grad_dao;

    /**
     * Priprema DAO objekte koji broje majstore, kategorije i gradove.
     *
     * Ovaj servis nema vlastitu tabelu. Samo povezuje postojeće DAO metode za potrebe prikaza osnovne statistike na početnoj
     * stranici.
     *
     * Ulaz: $majstor_dao, $kategorija_dao, $grad_dao – opcionalni DAO objekti.
     * Izlaz: Nema povratne vrijednosti.
     */
    public function __construct($majstor_dao = null, $kategorija_dao = null, $grad_dao = null)
    {
        // Pamtimo tri DAO objekta kako bismo pročitali ukupne brojeve zapisa.
        $this->majstor_dao = $majstor_dao ?? new MajstorDao();
        $this->kategorija_dao = $kategorija_dao ?? new KategorijaDao();
        $this->grad_dao = $grad_dao ?? new GradDao();
    }

    /**
     * Dohvata broj majstora, kategorija i gradova za početnu stranicu aplikacije.
     *
     * Poziva count_all() za svaku od tri tabele i vraća njihove brojeve objedinjene u jedan niz. Ne izvršava upise niti računa
     * statistiku pomoću posebnih SQL upita u samom servisu.
     *
     * Ulaz: Nema parametara.
     * Izlaz: Asocijativni niz s ključevima majstori, kategorije i gradovi.
     */
    public function get_stats()
    {
        // Pozivamo postojeće count_all() metode i sastavljamo jedan odgovor za frontend.
        return [
            'majstori' => $this->majstor_dao->count_all(),
            'kategorije' => $this->kategorija_dao->count_all(),
            'gradovi' => $this->grad_dao->count_all()
        ];
    }
}
