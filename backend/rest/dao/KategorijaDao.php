<?php
require_once __DIR__ . '/BaseDao.php';

class KategorijaDao extends BaseDao
{
    /**
     * Povezuje DAO s tabelom KATEGORIJE i upisivim kolonama.
     * @return void
     */
    public function __construct()
    {
        parent::__construct('KATEGORIJE', ['roditelj_id', 'naziv']);
    }

    /**
     * Vraća sve kategorije s vezama prema roditeljima.
     * @return array Niz kategorija za prikaz/obradu stabla.
     */
    public function get_all()
    {
        return $this->query(
            'SELECT id, roditelj_id, naziv FROM KATEGORIJE ORDER BY id'
        );
    }

    /**
     * Vraća prvi zapis kategorije s odgovarajućim nazivom.
     * @param string $naziv Naziv kategorije.
     * @return array|null Prvi odgovarajući zapis ili null; naziv nije UNIQUE u bazi.
     */
    public function get_by_naziv($naziv)
    {
        return $this->query_unique(
            'SELECT id, roditelj_id, naziv FROM KATEGORIJE WHERE naziv = :naziv',
            ['naziv' => $naziv]
        );
    }

    /**
     * Dohvata direktnu djecu kategorije ili korijenske kategorije kada je parametar null.
     * @param int|null $roditelj_id ID roditelja ili null za korijen.
     * @return array Niz direktnih podkategorija/korijenskih kategorija.
     */
    public function get_children($roditelj_id = null)
    {
        if ($roditelj_id === null) {
            return $this->query(
                'SELECT id, roditelj_id, naziv FROM KATEGORIJE WHERE roditelj_id IS NULL ORDER BY id'
            );
        }

        return $this->query(
            'SELECT id, roditelj_id, naziv FROM KATEGORIJE WHERE roditelj_id = :roditelj_id ORDER BY id',
            ['roditelj_id' => $roditelj_id]
        );
    }
}
