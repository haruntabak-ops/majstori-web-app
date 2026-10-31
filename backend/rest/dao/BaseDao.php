<?php
require_once __DIR__ . '/../../config/config.php';

/**
 * VERSION 4 (FINAL) – BaseDao: reusable CRUD for every table.
 *
 * uz dodatno MOJE DORADE:
 *
 *  1) Dodana komentari na ranije metode zbog preglednosti i jednostavnijeg snalaženja.
 *
 *  2) Dodana je zaštita za veznu tabelu MAJSTOR_KATEGORIJE, koja nema kolonu id kao primarni ključ,
 *  nego koristi složeni primarni ključ (majstor_id, kategorija_id).
 *
 *    Zaštita se odnosi na generičke metode get_by_id(), add(), update() i delete().
 *    Ove metode nisu namijenjene za MAJSTOR_KATEGORIJE jer pretpostavljaju kolonu id ili vraćaju rezultat koji sadrži id. 
 *    Ako se neka od njih pozove za ovu tabelu, metoda baca Exception s objašnjenjem.
 *    Za veznu tabelu koriste se namjenske metode iz klase MajstorKategorijaDao.
 *
 * Fixed compared to BaseDaov1:
 *  + INSERT/UPDATE/DELETE are generic – built from the array keys, written only once
 *  + only whitelisted columns can be written
 *  + ONE shared connection for all DAOs
 *  + transactions
 *
 * Child classes only say WHICH table they use and WHICH columns may be written:
 *
 *   class CategoryDao extends BaseDao {
 *       public function __construct() { parent::__construct('categories', ['name', 'description']); }
 *   }
 */
class BaseDao
{
    /** One PDO connection shared by all DAOs (created on first use). */
    private static $connection = null;

    protected $table_name;
    /** Columns that may be inserted/updated – everything else is ignored (protects against injected column names). */
    protected $columns;

    /**
     * Inicijalizira naziv tabele i dozvoljene kolone za upis.
     * @param string $table_name Naziv tabele.
     * @param array $columns Kolone koje se smiju mijenjati.
     * @return void
     */
    public function __construct($table_name, $columns = [])
    {
        $this->table_name = $table_name;
        $this->columns = $columns;
    }

    /**
     * Vraća zajedničku PDO konekciju; otvara je pri prvom pozivu.
     * @return PDO PDO konekcija prema MySQL bazi.
     */
    public static function connection()
    {
        if (self::$connection === null) {
            self::$connection = new PDO(
                "mysql:host=" . Config::DB_HOST() . ";port=" . Config::DB_PORT() . ";dbname=" . Config::DB_NAME() . ";charset=utf8mb4",
                Config::DB_USER(),
                Config::DB_PASSWORD(),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }
        return self::$connection;
    }

    /* ---------- helpers for child classes ---------- */

    /** Run a SELECT and return all rows. */
    /**
     * Izvršava parametrizirani SELECT i vraća sve pronađene redove.
     * @param string $query SQL SELECT upit.
     * @param array $params Vrijednosti SQL parametara.
     * @return array Niz redova; prazan niz ako nema rezultata.
     */
    protected function query($query, $params = [])
    {
        $stmt = self::connection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Run a SELECT and return the first row (or null). */
    /**
     * Izvršava SELECT i vraća prvi red ili null ako nema rezultata.
     * @param string $query SQL SELECT upit.
     * @param array $params Vrijednosti SQL parametara.
     * @return array|null Prvi pronađeni red ili null.
     */
    protected function query_unique($query, $params = [])
    {
        $results = $this->query($query, $params);
        return $results[0] ?? null;
    }

    /** Run INSERT/UPDATE/DELETE and return the number of affected rows. */
    /**
     * Izvršava INSERT, UPDATE ili DELETE pomoću pripremljenog upita.
     * @param string $query SQL upit.
     * @param array $params Vrijednosti SQL parametara.
     * @return int Broj zahvaćenih redova.
     */
    protected function execute($query, $params = [])
    {
        $stmt = self::connection()->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /* ---------- CRUD ---------- */

    /**
     * Dohvata sve redove tabele sortirane po koloni id.
     * @return array Redovi tabele (pretpostavlja da tabela ima id).
     */
    public function get_all()
    {
        return $this->query("SELECT * FROM {$this->table_name} ORDER BY id");
    }

    /**
     * Dohvata red prema primarnom ključu id. Ne koristi se za veznu tabelu.
     * @param int $id Primarni ključ reda.
     * @return array|null Pronađeni red ili null.
     * @throws Exception Ako se pozove za MAJSTOR_KATEGORIJE.
     */
    public function get_by_id($id)
    {
        // DORADA: ova generička metoda zahtijeva kolonu id, koju vezna tabela nema.
        if ($this->table_name === 'MAJSTOR_KATEGORIJE') {
            throw new Exception('Metoda get_by_id() ne može se koristiti za veznu tabelu MAJSTOR_KATEGORIJE.');
        }

        return $this->query_unique("SELECT * FROM {$this->table_name} WHERE id = :id", ['id' => $id]);
    }

    /**
     * Broji sve redove u tabeli.
     * @return int Ukupan broj redova.
     */
    public function count_all()
    {
        return (int) $this->query_unique("SELECT COUNT(*) AS total FROM {$this->table_name}")['total'];
    }

    /**
     * INSERT INTO events (title, location) VALUES (:title, :location)
     */
    /**
     * Dodaje novi red koristeći isključivo dozvoljene kolone.
     * @param array $entity Podaci za upis (ključ je naziv kolone).
     * @return array Upisani podaci s novim id.
     * @throws Exception Ako se pozove za MAJSTOR_KATEGORIJE.
     */
    public function add($entity)
    {
        // DORADA: vezna tabela ima vlastitu add() metodu jer nema kolonu id.
        if ($this->table_name === 'MAJSTOR_KATEGORIJE') {
            throw new Exception('Metoda add() ne može se koristiti za veznu tabelu MAJSTOR_KATEGORIJE.');
        }

        $entity = $this->only_columns($entity);
        $columns = array_keys($entity);

        $query = "INSERT INTO {$this->table_name} (" . implode(', ', $columns) . ")"
               . " VALUES (:" . implode(', :', $columns) . ")";
        $this->execute($query, $entity);

        $entity['id'] = (int) self::connection()->lastInsertId();
        return $entity;
    }

    /**
     * UPDATE events SET title = :title, location = :location WHERE id = :id
     */
    /**
     * Mijenja dozvoljene kolone reda određenog ID-om.
     * @param array $entity Kolone i nove vrijednosti.
     * @param int $id ID reda koji se mijenja.
     * @return int Broj zahvaćenih redova; 0 ako nema dozvoljenih kolona.
     * @throws Exception Ako se pozove za MAJSTOR_KATEGORIJE.
     */
    public function update($entity, $id)
    {
        // DORADA: ova generička metoda koristi WHERE id = :id, koji ne postoji u veznoj tabeli.
        if ($this->table_name === 'MAJSTOR_KATEGORIJE') {
            throw new Exception('Metoda update() ne može se koristiti za veznu tabelu MAJSTOR_KATEGORIJE.');
        }

        $entity = $this->only_columns($entity);
        if (count($entity) === 0) {
            return 0;
        }

        $set = [];
        foreach (array_keys($entity) as $column) {
            $set[] = "$column = :$column";
        }
        $query = "UPDATE {$this->table_name} SET " . implode(', ', $set) . " WHERE id = :id";

        $entity['id'] = $id;
        return $this->execute($query, $entity);
    }

    /**
     * Briše red prema ID-u.
     * @param int $id ID reda za brisanje.
     * @return int Broj obrisanih redova.
     * @throws Exception Ako se pozove za MAJSTOR_KATEGORIJE.
     */
    public function delete($id)
    {
        // DORADA: ova generička metoda koristi WHERE id = :id, koji ne postoji u veznoj tabeli.
        if ($this->table_name === 'MAJSTOR_KATEGORIJE') {
            throw new Exception('Metoda delete() ne može se koristiti za veznu tabelu MAJSTOR_KATEGORIJE.');
        }

        return $this->execute("DELETE FROM {$this->table_name} WHERE id = :id", ['id' => $id]);
    }

    /* ---------- transactions (used when one action changes several tables) ---------- */

    /**
     * Započinje baznu transakciju za povezane upise/brisanja.
     * @return void
     */
    public function begin_transaction() { self::connection()->beginTransaction(); }
    /**
     * Potvrđuje aktivnu transakciju.
     * @return void
     */
    public function commit()            { self::connection()->commit(); }
    /**
     * Poništava aktivnu transakciju, ako postoji.
     * @return void
     */
    public function rollback()          { if (self::connection()->inTransaction()) self::connection()->rollBack(); }

    /** Keep only keys that are real, writable columns of this table. */
    /**
     * Zadržava samo kolone dopuštene konstruktorom DAO klase.
     * @param array $entity Ulazni asocijativni niz.
     * @return array Filtrirani podaci bez nedozvoljenih kolona.
     */
    protected function only_columns($entity)
    {
        return array_intersect_key($entity, array_flip($this->columns));
    }
}
