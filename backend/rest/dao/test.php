<?php
/**
 * A3 - Jednostavan test DAO klasa, po uzoru na profesoricin testv4.php.
 * Pokretanje iz korijena projekta: php backend/rest/dao/test.php
 *
 * Rezultati se ISPISUJU i rucno uporeduju s ocekivanim vrijednostima.
 * Nema automatskih provjera ispravnosti rezultata.
 *
 * CRUD koristi iskljucivo nove testne zapise u svih pet tabela.
 * Na kraju se radi ROLLBACK, pa testni podaci ne ostaju u bazi.
 * Napomena: MySQL AUTO_INCREMENT brojevi mogu se povecati i nakon ROLLBACK-a.
 */

if (PHP_SAPI !== 'cli') {
    exit('Pokreni test iz komandne linije.');
}

require_once __DIR__ . '/KorisnikDao.php';
require_once __DIR__ . '/GradDao.php';
require_once __DIR__ . '/MajstorDao.php';
require_once __DIR__ . '/KategorijaDao.php';
require_once __DIR__ . '/MajstorKategorijaDao.php';

function naslov($tekst)
{
    echo "\n=== {$tekst} ===\n";
}

$korisnici = new KorisnikDao();
$gradovi = new GradDao();
$majstori = new MajstorDao();
$kategorije = new KategorijaDao();
$veze = new MajstorKategorijaDao();

try {
    // 1. READ - broj postojecih zapisa, kao u profesoricinom testu.
    naslov('1. READ - broj zapisa u svih pet tabela');
    echo 'KORISNICI: ' . count($korisnici->get_all()) . "\n";
    echo 'GRADOVI: ' . count($gradovi->get_all()) . "\n";
    echo 'MAJSTORI: ' . count($majstori->get_all()) . "\n";
    echo 'KATEGORIJE: ' . count($kategorije->get_all()) . "\n";
    echo 'MAJSTOR_KATEGORIJE: ' . count($veze->get_all()) . "\n";

    // Dodatni primjeri postojecih READ metoda. Nista se ne mijenja u bazi.
    naslov('2. READ - primjeri namjenskih DAO metoda');
    echo "Korisnik po e-mailu (prikazuje i hash lozinke):\n";
    print_r($korisnici->get_by_email('amir.elektrovic@primjer.ba'));
    echo "Grad Sarajevo:\n";
    print_r($gradovi->get_by_naziv('Sarajevo'));
    echo "Profil majstora za korisnicki nalog ID 2 (Amir):\n";
    print_r($majstori->get_by_korisnik_id(2));
    echo "Korijenske kategorije:\n";
    print_r($kategorije->get_children());
    echo "Izabrani majstorluci majstora ID 1 (Amir):\n";
    print_r($veze->get_by_majstor_id(1));

    // 7a. Traze se Alarmni sistemi (15) i Video-nadzor (18).
    // OCEKIVANO: Amir (1) - IP kamere su potomak kategorije 18;
    //            Adis (4) - direktno odabrao kategoriju 15;
    //            Eldin (6) - IP kamere i Analogni sistemi su potomci 18.
    // NE OCEKUJEMO: Alen (11) - odabrao je samo pretke: 10 i 12.
    naslov('3. PRETRAGA 7a - kategorije 15 i 18, bez grada i radijusa');
    echo "OCEKIVANO: Amir (1), Adis (4), Eldin (6).\n";
    echo "NE TREBA BITI: Alen (11), jer je oznacio samo roditeljske kategorije.\n";
    echo "STVARNI REZULTATI:\n";
    $rezultati = $majstori->pretrazi([15, 18], null, null);
    foreach ($rezultati as $m) {
        echo $m['id'] . ' - ' . $m['ime'] . ' ' . $m['prezime']
            . ' (' . $m['grad_naziv'] . ') | Odabrani majstorluci: '
            . $m['majstorluci_nazivi'] . "\n";
    }

    // 7b. Sada se trazi sama roditeljska kategorija Niska struja (12).
    // OCEKIVANO: Amir (1), Adis (4), Eldin (6) zbog potomaka,
    //            i Alen (11) jer je eksplicitno odabrao bas kategoriju 12.
    naslov('4. PRETRAGA 7b - kategorija 12, bez grada i radijusa');
    echo "OCEKIVANO: Amir (1), Adis (4), Eldin (6), Alen (11).\n";
    echo "OBJASNJENJE: Alen sada ulazi jer je izabrao upravo kategoriju 12.\n";
    echo "STVARNI REZULTATI:\n";
    $rezultati = $majstori->pretrazi([12], null, null);
    foreach ($rezultati as $m) {
        echo $m['id'] . ' - ' . $m['ime'] . ' ' . $m['prezime']
            . ' (' . $m['grad_naziv'] . ') | Odabrani majstorluci: '
            . $m['majstorluci_nazivi'] . "\n";
    }

    // 7c. Niska struja (12), polazni grad Tuzla (4), radijus 100 km.
    // OCEKIVANO: Amir (1) - Sarajevo, oko 78.5 km;
    //            Adis (4) i Alen (11) - Tuzla, 0 km.
    // NE OCEKUJEMO: Eldin (6) - Mostar je izvan 100 km od Tuzle.
    naslov('5. PRETRAGA 7c - kategorija 12, Tuzla, radijus 100 km');
    echo "OCEKIVANO: Amir (1, oko 78.5 km), Adis (4, 0 km), Alen (11, 0 km).\n";
    echo "NE TREBA BITI: Eldin (6), zbog udaljenosti Mostara od Tuzle.\n";
    echo "STVARNI REZULTATI:\n";
    $rezultati = $majstori->pretrazi([12], 4, 100);
    foreach ($rezultati as $m) {
        echo $m['id'] . ' - ' . $m['ime'] . ' ' . $m['prezime']
            . ' (' . $m['grad_naziv'] . ') | Odabrani majstorluci: '
            . $m['majstorluci_nazivi'] . ' | Udaljenost: '
            . $m['udaljenost_km'] . " km\n";
    }

    // 6. CRUD - sve radimo s potpuno NOVIM zapisima.
    // Transakcija se ponistava na kraju (cak i ako test prekine greska).
    naslov('6. CRUD - testni podaci unutar transakcije');
    $korisnici->begin_transaction();
    $oznaka = 'DAO_TEST_' . bin2hex(random_bytes(4));
    echo "Testna oznaka: {$oznaka}\n";

    // KORISNICI: CREATE, READ, UPDATE; DELETE je na kraju zbog FK veze s MAJSTORI.
    naslov('6a. KORISNICI - CREATE, READ, UPDATE');
    $email = strtolower($oznaka) . '@primjer.ba';
    $noviKorisnik = $korisnici->add([
        'email' => $email,
        'lozinka_hash' => password_hash('testna_lozinka', PASSWORD_DEFAULT),
        'uloga' => 'majstor'
    ]);
    $korisnikId = $noviKorisnik['id'];
    echo "CREATE - ID: {$korisnikId}\n";
    echo "READ:\n";
    print_r($korisnici->get_by_email($email));
    $korisnici->update(['email' => strtolower($oznaka) . '_novo@primjer.ba'], $korisnikId);
    echo "UPDATE - promijenjen email:\n";
    print_r($korisnici->get_by_id($korisnikId));

    // GRADOVI: testni grad se koristi samo za novog testnog majstora.
    naslov('6b. GRADOVI - CREATE, READ, UPDATE');
    $nazivGrada = $oznaka . '_Grad';
    $noviGrad = $gradovi->add([
        'naziv' => $nazivGrada,
        'latitude' => 43.80,
        'longitude' => 18.40
    ]);
    $gradId = $noviGrad['id'];
    echo "CREATE - ID: {$gradId}\n";
    echo "READ:\n";
    print_r($gradovi->get_by_naziv($nazivGrada));
    $gradovi->update(['naziv' => $oznaka . '_NoviGrad'], $gradId);
    echo "UPDATE - promijenjen naziv:\n";
    print_r($gradovi->get_by_id($gradId));

    // MAJSTORI: novi profil, povezan SAMO s novim korisnikom i gradom.
    naslov('6c. MAJSTORI - CREATE, READ, UPDATE');
    $noviMajstor = $majstori->add([
        'korisnik_id' => $korisnikId,
        'grad_id' => $gradId,
        'ime' => 'Testni',
        'prezime' => 'Majstor',
        'telefon' => '000-000',
        'opis' => 'A3 testni profil'
    ]);
    $majstorId = $noviMajstor['id'];
    echo "CREATE - ID: {$majstorId}\n";
    echo "READ:\n";
    print_r($majstori->get_by_korisnik_id($korisnikId));
    $majstori->update(['ime' => 'Izmijenjeni', 'telefon' => '000-001'], $majstorId);
    echo "UPDATE - promijenjeno ime i telefon:\n";
    print_r($majstori->get_by_id($majstorId));

    // KATEGORIJE: novi roditelj i dijete; postojeca hijerarhija se ne dira.
    naslov('6d. KATEGORIJE - CREATE, READ, UPDATE');
    $roditelj = $kategorije->add([
        'roditelj_id' => null,
        'naziv' => $oznaka . '_Roditelj'
    ]);
    $roditeljId = $roditelj['id'];
    $dijete = $kategorije->add([
        'roditelj_id' => $roditeljId,
        'naziv' => $oznaka . '_Dijete'
    ]);
    $dijeteId = $dijete['id'];
    echo "CREATE - roditelj ID {$roditeljId}, dijete ID {$dijeteId}\n";
    echo "READ - djeca novog roditelja:\n";
    print_r($kategorije->get_children($roditeljId));
    $kategorije->update(['naziv' => $oznaka . '_IzmijenjenoDijete'], $dijeteId);
    echo "UPDATE - promijenjen naziv djeteta:\n";
    print_r($kategorije->get_by_id($dijeteId));

    // MAJSTOR_KATEGORIJE: ova tabela nema klasicni UPDATE (slozeni PK).
    // Promjena veze se radi DELETE stare veze + CREATE nove veze.
    naslov('6e. MAJSTOR_KATEGORIJE - CREATE, READ, zamjena veze');
    $veze->add(['majstor_id' => $majstorId, 'kategorija_id' => $dijeteId]);
    echo "CREATE - veza majstor {$majstorId} -> kategorija {$dijeteId}\n";
    echo "READ - po majstoru:\n";
    print_r($veze->get_by_majstor_id($majstorId));
    echo "READ - po kategoriji:\n";
    print_r($veze->get_by_kategorija_id($dijeteId));
    echo "READ - majstori za kategoriju (MajstorDao):\n";
    print_r($majstori->get_by_kategorija_id($dijeteId));
    $veze->delete_by_keys($majstorId, $dijeteId);
    $veze->add(['majstor_id' => $majstorId, 'kategorija_id' => $roditeljId]);
    echo "ZAMJENA VEZE - majstor sada ima roditeljsku kategoriju:\n";
    print_r($veze->get_by_majstor_id($majstorId));

    // DELETE: prvo veza, pa dijete i roditelj, majstor, grad i korisnik.
    // Time postujemo strane kljuceve i brisemo ISKLJUCIVO novokreirane zapise.
    naslov('7. DELETE - brisanje svih novih testnih zapisa');
    echo "DELETE veza - obrisanih redova: " . $veze->delete_by_keys($majstorId, $roditeljId) . "\n";
    echo "READ nakon DELETE veze:\n";
    print_r($veze->get_by_majstor_id($majstorId));

    echo "DELETE dijete - obrisanih redova: " . $kategorije->delete($dijeteId) . "\n";
    echo "DELETE roditelj - obrisanih redova: " . $kategorije->delete($roditeljId) . "\n";
    echo "READ nakon DELETE kategorija:\n";
    var_dump($kategorije->get_by_id($roditeljId));

    echo "DELETE majstor - obrisanih redova: " . $majstori->delete($majstorId) . "\n";
    echo "READ nakon DELETE majstora:\n";
    var_dump($majstori->get_by_id($majstorId));

    echo "DELETE grad - obrisanih redova: " . $gradovi->delete($gradId) . "\n";
    echo "READ nakon DELETE grada:\n";
    var_dump($gradovi->get_by_id($gradId));

    echo "DELETE korisnik - obrisanih redova: " . $korisnici->delete($korisnikId) . "\n";
    echo "READ nakon DELETE korisnika:\n";
    var_dump($korisnici->get_by_id($korisnikId));

    echo "\nOCEKIVANO: svaki DELETE ispisuje 1, a READ nakon brisanja vraca prazan niz ili NULL.\n";
    echo "OCEKIVANO: svi novi testni zapisi su obrisani; pocetni podaci nisu dirani.\n";

} catch (Throwable $e) {
    echo "\nGRESKA PRI IZVRSAVANJU: " . $e->getMessage() . "\n";
} finally {
    // Ovo nije automatska provjera, nego zastita testne baze.
    // Ako transakcija nije pocela, rollback() ne radi nista.
    $korisnici->rollback();
    naslov('8. ROLLBACK - testni upisi nisu trajno sacuvani');
    echo "KRAJ TESTA - uporedi ispis s navedenim ocekivanim rezultatima.\n";
}
