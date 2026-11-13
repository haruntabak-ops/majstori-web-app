<?php
require_once __DIR__ . '/../../utils/exceptions.php';
require_once __DIR__ . '/../../utils/Validator.php';

/**
 * VERSION 3 (FINAL) – BaseService: common business logic for every entity.
 *
 * Problems are signalled with EXCEPTIONS (utils/exceptions.php) that carry an HTTP status code.
 *
 * Child services override validate() with their own rules and can override
 * any CRUD method when an entity needs special behaviour.
 * Services are the ONLY layer that calls the DAOs.
 *
 * +
 * Verzija preuzeta iz pokazne aplikacije campus-events-app 
 * i dopunjena dodatnim komentarima radi jednostavnijeg razumjevanja programskog koda
 */


/**
 * Zajednička osnova za standardne CRUD servise.
 *
 * Sadrži uobičajene operacije čitanja, dodavanja, izmjene i brisanja. Konkretni servisi nasljeđuju ove metode ili ih
 * prilagođavaju kada imaju posebna poslovna pravila. Jedino servisni sloj treba pozivati DAO, a SQL ostaje u DAO klasama.
 */
class BaseService
{
    protected $dao;
    /** Used in error messages, e.g. "Event not found." */
    protected $entity_name = 'Entity';

    /**
     * Povezuje osnovni servis s DAO objektom za odgovarajuću tabelu.
     *
     * Svaki konkretni servis koristi ovaj DAO za čitanje i upis podataka. Tako servis ne mora sam pisati SQL upite.
     *
     * Ulaz: $dao – već pripremljen DAO objekat.
     * Izlaz: Nema povratne vrijednosti; DAO se pamti u svojstvu $this->dao.
     */
    public function __construct($dao)
    {
        // Čuvamo DAO kako bi sve metode ovog servisa mogle koristiti istu vezu s podacima.
        $this->dao = $dao;
    }

    /**
     * Dohvata sve zapise iz tabele kojom upravlja konkretni servis.
     *
     * Ne dodaje filtere niti provjerava posebna pravila. Jednostavno poziva get_all() odgovarajućeg DAO-a. Konkretnom servisu je
     * dozvoljeno da ovu metodu prilagodi kada treba dodatno obraditi rezultat.
     *
     * Ulaz: Nema parametara.
     * Izlaz: Niz zapisa koje vrati DAO.
     */
    public function get_all()
    {
        // Nema dodatne obrade: DAO direktno vraća listu redova iz baze.
        return $this->dao->get_all();
    }

    /**
     * Pronalazi jedan zapis na osnovu njegovog ID-a.
     *
     * DAO prvo pokušava učitati zapis. Ako zapis ne postoji, baca se NotFoundException, koji će API ruta kasnije pretvoriti u
     * odgovor HTTP 404.
     *
     * Ulaz: $id – ID traženog zapisa.
     * Izlaz: Pronađeni zapis ili greška 404 ako ga nema.
     */
    public function get_by_id($id)
    {
        // Prvo pokušavamo učitati zapis po primarnom ključu.
        $entity = $this->dao->get_by_id($id);
        // Ako zapis ne postoji, izuzetak će ruta kasnije pretvoriti u HTTP 404.
        if (!$entity) {
            throw new NotFoundException("{$this->entity_name} not found.");
        }
        return $entity;
    }

    /**
     * Dodaje novi zapis koristeći zajednički postupak koji slijede svi standardni servisi.
     *
     * Prvo poziva validate() konkretnog servisa da provjeri unesene podatke i poslovna pravila. Tek zatim DAO upisuje zapis i
     * vraća novi ID, nakon čega učitavamo kompletan spremljeni zapis.
     *
     * Ulaz: $entity – asocijativni niz podataka koje želimo upisati.
     * Izlaz: Novokreirani zapis iz baze; neispravan unos izaziva izuzetak.
     */
    public function add($entity)
    {
        // Prije pisanja u bazu provjeravamo poslovna pravila konkretnog servisa.
        $this->validate($entity);
        // DAO kreira red i vrati njegov novi ID; zatim učitavamo puni zapis.
        $created = $this->dao->add($entity);
        return $this->dao->get_by_id($created['id']);
    }

    /**
     * Mijenja postojeći zapis koristeći zajednički postupak provjere i spremanja.
     *
     * Prvo provjerava postoji li ID, zatim poziva validate() za podatke izmjene. Nakon uspješne validacije DAO izvršava UPDATE i
     * servis ponovo učitava ažurirani zapis. Konkretni servis može dodatno ograničiti koja se polja smiju mijenjati.
     *
     * Ulaz: $entity – podaci za izmjenu; $id – ID postojećeg zapisa.
     * Izlaz: Ažurirani zapis ili izuzetak ako zapis ne postoji ili podaci nisu ispravni.
     */
    public function update($entity, $id)
    {
        // Ne smijemo mijenjati zapis koji ne postoji.
        $this->get_by_id($id);              // Ako zapis ne postoji, get_by_id() baca 404.
        // Validacija pri izmjeni dobija i ID da razlikuje postojeći zapis od duplikata.
        $this->validate($entity, $id);
        $this->dao->update($entity, $id);
        return $this->dao->get_by_id($id);
    }

    /**
     * Briše postojeći zapis koristeći zajedničku DAO metodu.
     *
     * Prvo provjerava postojanje zapisa kako bi nepostojeći ID dao jasan odgovor 404, a zatim poziva DAO za brisanje. Ako postoje
     * posebna pravila brisanja, konkretni servis treba prepisati ovu metodu.
     *
     * Ulaz: $id – ID zapisa za brisanje.
     * Izlaz: Ne vraća podatke; greška 404 ako zapis ne postoji.
     */
    public function delete($id)
    {
        // Provjera postojanja prethodi pozivu DAO metode za brisanje.
        $this->get_by_id($id);
        $this->dao->delete($id);
    }

    /**
     * Throw a ValidationException when $entity breaks a rule.
     * $id is null when creating and the entity id when updating.
     *
     * Predstavlja zajedničko mjesto za provjeru podataka prije dodavanja ili izmjene.
     *
     * U osnovnoj klasi metoda nema pravila. Svaki konkretni servis je može prepisati i provjeriti obavezna polja, veze s drugim
     * tabelama ili poslovna ograničenja. Neispravan unos prijavljuje se izuzetkom.
     *
     * Ulaz: $entity – uneseni podaci; $id – ID pri izmjeni ili null pri dodavanju.
     * Izlaz: Nema povratne vrijednosti; podklasa može baciti ValidationException.
     */
    protected function validate($entity, $id = null)
    {
    }

    /** Turn empty strings into NULL for optional columns ("" is not a valid year or date).
     *
     * Pretvara prazne tekstualne vrijednosti izabranih opcionalnih polja u NULL.
     *
     * Prolazi samo kroz polja navedena u $fields. Ako je neko od njih poslano kao prazan tekst ili razmaci, zamjenjuje ga
     * vrijednošću null, što je prikladnije za opcionalne datume i brojeve u bazi.
     *
     * Ulaz: $entity – podaci za obradu; $fields – nazivi opcionalnih polja.
     * Izlaz: Isti niz podataka, uz pretvorene prazne vrijednosti.
     */
    protected function empty_to_null($entity, $fields)
    {
        // Prolazimo samo kroz opcionalna polja navedena u listi $fields.
        foreach ($fields as $field) {
            if (array_key_exists($field, $entity) && trim((string) $entity[$field]) === '') {
                $entity[$field] = null;
            }
        }
        return $entity;
    }
}