<?php
require_once __DIR__ . '/BaseDao.php';

class MajstorDao extends BaseDao
{
    /**
     * Povezuje DAO s tabelom MAJSTORI i dozvoljenim kolonama profila.
     * @return void
     */
    public function __construct()
    {
        parent::__construct('MAJSTORI', ['korisnik_id', 'grad_id', 'ime', 'prezime', 'telefon', 'opis']);
    }

    /**
     * Vraća sve profile majstora zajedno s nazivom grada i e-mail adresom.
     * @return array Niz profila s grad_naziv i email.
     */
    public function get_all()
    {
        return $this->query(
            'SELECT m.*, g.naziv AS grad_naziv, k.email
             FROM MAJSTORI m
             JOIN GRADOVI g ON g.id = m.grad_id
             JOIN KORISNICI k ON k.id = m.korisnik_id
             ORDER BY m.id'
        );
    }

    /**
     * Dohvata jedan profil majstora prema ID-u profila.
     * @param int $id ID profila majstora.
     * @return array|null Profil s nazivom grada i e-mailom ili null.
     */
    public function get_by_id($id)
    {
        return $this->query_unique(
            'SELECT m.*, g.naziv AS grad_naziv, k.email
             FROM MAJSTORI m
             JOIN GRADOVI g ON g.id = m.grad_id
             JOIN KORISNICI k ON k.id = m.korisnik_id
             WHERE m.id = :id',
            ['id' => $id]
        );
    }

    /**
     * Dohvata profil povezan s konkretnim korisničkim računom.
     * @param int $korisnik_id ID računa iz KORISNICI.
     * @return array|null Profil ili null ako još nije kreiran.
     */
    public function get_by_korisnik_id($korisnik_id)
    {
        return $this->query_unique(
            'SELECT m.*, g.naziv AS grad_naziv, k.email
             FROM MAJSTORI m
             JOIN GRADOVI g ON g.id = m.grad_id
             JOIN KORISNICI k ON k.id = m.korisnik_id
             WHERE m.korisnik_id = :korisnik_id',
            ['korisnik_id' => $korisnik_id]
        );
    }

    /**
     * Dohvata majstore direktno povezane s tačno jednom kategorijom.
     * @param int $kategorija_id ID konkretne kategorije.
     * @return array Niz majstora; metoda sama ne obuhvata potomke kategorije ni udaljenost.
     */
    public function get_by_kategorija_id($kategorija_id)
    {
        return $this->query(
            'SELECT DISTINCT m.*, g.naziv AS grad_naziv, k.email
             FROM MAJSTORI m
             JOIN GRADOVI g ON g.id = m.grad_id
             JOIN KORISNICI k ON k.id = m.korisnik_id
             JOIN MAJSTOR_KATEGORIJE mk ON mk.majstor_id = m.id
             WHERE mk.kategorija_id = :kategorija_id
             ORDER BY m.id',
            ['kategorija_id' => $kategorija_id]
        );
    }

    /**
     * Pretražuje majstore po kategorijama, gradu i radijusu.
     * Majstor odgovara samo ako je eksplicitno odabrao traženu kategoriju
     * ili nekog njenog potomka (samo odabrani roditelj nije dovoljan).
     *
     * @param array $kategorija_ids ID-jevi traženih kategorija; [] = bez filtera kategorije.
     * @param int|null $grad_id Polazni grad ili null za sve gradove.
     * @param float|null $radijus_km Radijus zračne udaljenosti u km; null uz grad = samo taj grad.
     * @return array Majstori s nazivom grada, e-mailom, odabranim majstorlucima i udaljenosti.
     */
    public function pretrazi($kategorija_ids = [], $grad_id = null, $radijus_km = null)
    {
        // Kratka provjera ulaznih parametara.
        if (!is_array($kategorija_ids)) {
            throw new InvalidArgumentException('Kategorije moraju biti niz ID-jeva.');
        }
        foreach ($kategorija_ids as $id) {
            if (!ctype_digit((string) $id) || (int) $id <= 0) {
                throw new InvalidArgumentException('ID kategorije mora biti pozitivan cijeli broj.');
            }
        }
        if ($grad_id !== null && (!ctype_digit((string) $grad_id) || (int) $grad_id <= 0)) {
            throw new InvalidArgumentException('ID grada mora biti pozitivan cijeli broj.');
        }
        if ($radijus_km !== null) {
            if ($grad_id === null) {
                throw new InvalidArgumentException('Za radijus mora biti odabran grad.');
            }
            if (!is_numeric($radijus_km) || !is_finite((float) $radijus_km) || (float) $radijus_km < 0) {
                throw new InvalidArgumentException('Radijus mora biti nenegativan broj kilometara.');
            }
        }

        // SQL prati strukturu testnih upita 7a, 7b i 7c iz radne dokumentacije.
        // 'filteri' samo zamjenjuje DataGrip varijable (@kategorije, @grad_id, @radijus_km)
        // pripremljenim PDO parametrima koje upit koristi na više mjesta.
        $sql = <<<'SQL'
WITH RECURSIVE
filteri AS (
    SELECT :kategorije AS kategorije,
           :grad_id AS grad_id,
           :radijus_km AS radijus_km
),
podstablo AS (
    -- Tražene kategorije.
    SELECT k.id
    FROM KATEGORIJE k
    CROSS JOIN filteri f
    JOIN JSON_TABLE(
        f.kategorije, '$[*]' COLUMNS (id INT PATH '$')
    ) t ON t.id = k.id

    UNION DISTINCT

    -- Svi potomci traženih kategorija (nikada roditelji).
    SELECT k.id
    FROM KATEGORIJE k
    JOIN podstablo p ON k.roditelj_id = p.id
),
rezultati AS (
    SELECT
        m.*,
        g.naziv AS grad_naziv,
        ku.email,

        -- Majstorluci koje je majstor STVARNO označio.
        (
            SELECT GROUP_CONCAT(kat.naziv ORDER BY kat.naziv SEPARATOR ', ')
            FROM MAJSTOR_KATEGORIJE mk
            JOIN KATEGORIJE kat ON kat.id = mk.kategorija_id
            WHERE mk.majstor_id = m.id
        ) AS majstorluci_nazivi,

        -- Udaljenost zračnom linijom između gradova (km).
        6371 * 2 * ASIN(LEAST(1, SQRT(
            POWER(SIN(RADIANS(g.latitude - pg.latitude) / 2), 2)
            + COS(RADIANS(pg.latitude)) * COS(RADIANS(g.latitude))
            * POWER(SIN(RADIANS(g.longitude - pg.longitude) / 2), 2)
        ))) AS udaljenost_tacna_km

    FROM MAJSTORI m
    JOIN GRADOVI g ON g.id = m.grad_id
    JOIN KORISNICI ku ON ku.id = m.korisnik_id
    CROSS JOIN filteri f
    LEFT JOIN GRADOVI pg ON pg.id = f.grad_id

    WHERE (
        JSON_LENGTH(f.kategorije) = 0
        OR EXISTS (
            SELECT 1
            FROM MAJSTOR_KATEGORIJE mk
            JOIN podstablo p ON p.id = mk.kategorija_id
            WHERE mk.majstor_id = m.id
        )
    )
)
SELECT
    r.id, r.korisnik_id, r.grad_id, r.ime, r.prezime, r.telefon, r.opis,
    r.grad_naziv, r.email, r.majstorluci_nazivi,
    CASE WHEN f.radijus_km IS NOT NULL
         THEN ROUND(r.udaljenost_tacna_km, 1)
         ELSE NULL
    END AS udaljenost_km
FROM rezultati r
CROSS JOIN filteri f
WHERE f.grad_id IS NULL
   OR (f.radijus_km IS NULL AND r.grad_id = f.grad_id)
   OR (f.radijus_km IS NOT NULL AND r.udaljenost_tacna_km <= f.radijus_km)
ORDER BY r.id
SQL;

        return $this->query($sql, [
            'kategorije' => json_encode(array_values(array_unique(array_map('intval', $kategorija_ids)))),
            'grad_id' => $grad_id === null ? null : (int) $grad_id,
            'radijus_km' => $radijus_km === null ? null : (float) $radijus_km
        ]);
    }
}

