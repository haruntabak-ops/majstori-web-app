<?php
require_once __DIR__ . '/BaseDao.php';

class KorisnikDao extends BaseDao
{
    /**
     * Povezuje DAO s tabelom KORISNICI i njenim upisivim kolonama.
     * @return void
     */
    public function __construct()
    {
        parent::__construct('KORISNICI', ['email', 'lozinka_hash', 'uloga']);
    }

    /**
     * Traži korisnički račun prema e-mail adresi, uključujući hash radi provjere prijave u servisu.
     * @param string $email E-mail adresa korisnika.
     * @return array|null Korisnik ili null ako ne postoji.
     */
    public function get_by_email($email)
    {
        return $this->query_unique(
            'SELECT id, email, lozinka_hash, uloga FROM KORISNICI WHERE email = :email',
            ['email' => $email]
        );
    }

    /**
     * Vraća račune jedne uloge bez hash vrijednosti lozinki.
     * @param string $uloga Vrijednost uloge iz baze (admin ili majstor).
     * @return array Niz odgovarajućih korisničkih računa.
     */
    public function get_by_uloga($uloga)
    {
        return $this->query(
            'SELECT id, email, uloga FROM KORISNICI WHERE uloga = :uloga ORDER BY id',
            ['uloga' => $uloga]
        );
    }
}
