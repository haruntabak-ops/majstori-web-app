<?php
require_once __DIR__ . '/BaseDao.php';

/**
 * DAO za veznu tabelu MAJSTOR_KATEGORIJE.
 * Tabela nema kolonu id, nego složeni primarni ključ (majstor_id, kategorija_id).
 * Zato se za ovu tabelu ne koriste generičke BaseDao CRUD metode koje pretpostavljaju
 * kolonu id (get_by_id(), add() iz BaseDao, update() i delete($id)). Koriste se metode ove klase.
 */
class MajstorKategorijaDao extends BaseDao
{
    /**
     * Povezuje DAO s veznom tabelom MAJSTOR_KATEGORIJE.
     * @return void
     */
    public function __construct()
    {
        parent::__construct('MAJSTOR_KATEGORIJE', ['majstor_id', 'kategorija_id']);
    }

    /**
     * Dohvata sve direktne veze majstora i kategorija.
     * @return array Niz parova majstor_id i kategorija_id.
     */
    public function get_all()
    {
        return $this->query(
            'SELECT majstor_id, kategorija_id
             FROM MAJSTOR_KATEGORIJE
             ORDER BY majstor_id, kategorija_id'
        );
    }

    /**
     * Dohvata direktno odabrane kategorije jednog majstora.
     * @param int $majstor_id ID profila majstora.
     * @return array Niz veza prema kategorijama.
     */
    public function get_by_majstor_id($majstor_id)
    {
        return $this->query(
            'SELECT majstor_id, kategorija_id FROM MAJSTOR_KATEGORIJE
             WHERE majstor_id = :majstor_id ORDER BY kategorija_id',
            ['majstor_id' => $majstor_id]
        );
    }

    /**
     * Dohvata sve direktne veze za jednu kategoriju.
     * @param int $kategorija_id ID kategorije.
     * @return array Niz parova koji sadrže tu kategoriju.
     */
    public function get_by_kategorija_id($kategorija_id)
    {
        return $this->query(
            'SELECT majstor_id, kategorija_id FROM MAJSTOR_KATEGORIJE
             WHERE kategorija_id = :kategorija_id ORDER BY majstor_id',
            ['kategorija_id' => $kategorija_id]
        );
    }

    /**
     * Dodaje direktnu vezu majstora i kategorije pomoću složenog ključa.
     * @param array $entity Mora sadržavati majstor_id i kategorija_id.
     * @return array Upisani par identifikatora.
     * @throws InvalidArgumentException Ako nedostaje jedan od identifikatora.
     */
    public function add($entity)
    {
        $majstor_id = $entity['majstor_id'] ?? null;
        $kategorija_id = $entity['kategorija_id'] ?? null;
        if ($majstor_id === null || $kategorija_id === null) {
            throw new InvalidArgumentException('Potrebni su majstor_id i kategorija_id.');
        }

        $this->execute(
            'INSERT INTO MAJSTOR_KATEGORIJE (majstor_id, kategorija_id)
             VALUES (:majstor_id, :kategorija_id)',
            ['majstor_id' => $majstor_id, 'kategorija_id' => $kategorija_id]
        );

        return ['majstor_id' => (int) $majstor_id, 'kategorija_id' => (int) $kategorija_id];
    }

    /**
     * Briše jednu direktnu vezu prema oba dijela složenog ključa.
     * @param int $majstor_id ID profila majstora.
     * @param int $kategorija_id ID kategorije.
     * @return int Broj obrisanih veza.
     */
    public function delete_by_keys($majstor_id, $kategorija_id)
    {
        return $this->execute(
            'DELETE FROM MAJSTOR_KATEGORIJE
             WHERE majstor_id = :majstor_id AND kategorija_id = :kategorija_id',
            ['majstor_id' => $majstor_id, 'kategorija_id' => $kategorija_id]
        );
    }
}
