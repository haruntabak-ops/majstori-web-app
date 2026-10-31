<?php
require_once __DIR__ . '/BaseDao.php';

class GradDao extends BaseDao
{
    /**
     * Povezuje DAO s tabelom GRADOVI i upisivim kolonama.
     * @return void
     */
    public function __construct()
    {
        parent::__construct('GRADOVI', ['naziv', 'latitude', 'longitude']);
    }

    /**
     * Dohvata sve gradove s koordinatama, sortirane po nazivu.
     * @return array Niz gradova.
     */
    public function get_all()
    {
        return $this->query('SELECT id, naziv, latitude, longitude FROM GRADOVI ORDER BY naziv');
    }

    /**
     * Dohvata grad prema tačnom nazivu.
     * @param string $naziv Naziv grada.
     * @return array|null Pronađeni grad ili null.
     */
    public function get_by_naziv($naziv)
    {
        return $this->query_unique(
            'SELECT id, naziv, latitude, longitude FROM GRADOVI WHERE naziv = :naziv',
            ['naziv' => $naziv]
        );
    }
}
