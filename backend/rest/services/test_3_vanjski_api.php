<?php
/**
 * TEST 3 – STVARNI POZIV VANJSKOG API-JA (NOMINATIM) U M3.
 *
 * Svrha: provjeriti da NominatimService preko interneta može pronaći grad i vratiti koordinate za prikaz na karti.
 * Test direktno poziva SERVIS, kao u profesoricinom week-06/testv3.php. FlightPHP rute nam ovdje još nisu potrebne.
 *
 * Ovaj test NE MIJENJA MySQL bazu. Samo jednom šalje zahtjev javnom Nominatim API-ju i ispisuje odgovor.
 * Za test koristimo Sarajevo, čije približne koordinate već imamo u našim M2 početnim podacima.
 *
 * Pokretanje iz glavnog foldera MajstoriWebApp (PowerShell, CMD ili Git Bash):
 *     php backend/rest/services/test_3_vanjski_api.php
 *
 * Potrebni su internet, uključena PHP cURL ekstenzija i Nominatim postavke u backend/config/config.php.
 */

// Ova skripta služi za pokretanje u terminalu, ne kroz browser.
if (PHP_SAPI !== 'cli') {
    exit('Pokreni test iz PowerShella, CMD-a ili Git Basha.');
}

// Učitavamo servis koji testiramo. On sam učitava Config, Validator i klase izuzetaka.
require_once __DIR__ . '/NominatimService.php';

echo "\n=== TEST 3 – VANJSKI API: NOMINATIM ===\n";

// Ulaz u servis: naziv grada koji želimo pronaći. Promijeni ovaj naziv ako kasnije želiš isprobati drugi grad.
$naziv_grada = 'Sarajevo';

echo "ULAZ: naziv grada = {$naziv_grada}\n";
echo "OČEKIVANO: Nominatim pronalazi Sarajevo (BiH) i vraća naziv lokacije, geografsku širinu i dužinu.\n";
echo "ORIJENTACIJA: u našoj M2 bazi Sarajevo ima približno 43.8563 (latitude) i 18.4131 (longitude).\n";
echo "NAPOMENA: Nominatim može vratiti malo drugačije koordinate, zavisno od izabrane tačke na karti.\n\n";

try {
    // 1. Kreiramo servis. U njemu je implementiran stvarni HTTP poziv prema Nominatimu.
    $nominatim = new NominatimService();

    // 2. Pozivamo metodu servisa. Ona provjerava naziv, poziva Nominatim i obrađuje eventualne greške.
    //    Zahtjev se šalje samo JEDNOM, da nepotrebno ne opterećujemo vanjski servis.
    $rezultat = $nominatim->pronadji_grad($naziv_grada);

    // 3. Provjeravamo je li rezultat u obliku koji očekujemo za prikaz lokacije na frontendu.
    if (!is_array($rezultat) || !isset($rezultat['latitude'], $rezultat['longitude']) ||
        !is_numeric($rezultat['latitude']) || !is_numeric($rezultat['longitude'])) {
        throw new RuntimeException('Servis nije vratio ispravnu strukturu koordinata.');
    }

    // 4. Ispisujemo STVARNE podatke koje je servis vratio, da ih možemo ručno uporediti s očekivanjima.
    echo "STVARNI REZULTAT SERVISA:\n";
    echo '  Naziv unesenog grada: ' . ($rezultat['naziv'] ?? '(nije vraćen)') . "\n";
    echo '  Pronađena lokacija: ' . ($rezultat['display_name'] ?? '(nije vraćena)') . "\n";
    echo '  Latitude: ' . $rezultat['latitude'] . "\n";
    echo '  Longitude: ' . $rezultat['longitude'] . "\n";

    // Test bilježimo kao uspješan jer je poziv prošao i vratio koordinate.
    // Odgovara li lokacija zaista željenom gradu, provjeravamo ručno, kao administrator na karti.
    echo "\nREZULTAT: OK – vanjski API je odgovorio i servis je vratio koordinate.\n";
    echo "PROVJERA: uporedi pronađenu lokaciju i koordinate s očekivanim Sarajevom.\n";
    exit(0);
} catch (Throwable $e) {
    // Ako Nominatim ne pronađe grad, istekne vrijeme čekanja ili dođe do mrežne greške,
    // servis baca izuzetak. Ispisujemo njegov tip i poruku da znamo šta treba provjeriti.
    echo 'REZULTAT: GREŠKA – ' . get_class($e) . ': ' . $e->getMessage() . "\n";
    if ($e instanceof HttpException) {
        echo 'HTTP status izuzetka: ' . $e->get_status() . "\n";
    }
    exit(1);
}
