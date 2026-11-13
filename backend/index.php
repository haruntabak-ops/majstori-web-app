<?php
/**
 * Ulazna tačka našeg REST API-ja (po uzoru na Week 06 backend/index.php).
 *
 * HTTP zahtjev -> ruta -> servis -> DAO -> MySQL
 *
 * Ovdje učitavamo servise, registrujemo ih u FlightPHP i centralno
 * pretvaramo greške u JSON odgovore i učitavamo sve REST rute.
 */
require __DIR__ . '/vendor/autoload.php';

// Lokalna konfiguracija s podacima za bazu ne ide na GitHub.
if (!file_exists(__DIR__ . '/config/config.php')) {
    http_response_code(500);
    exit('Nedostaje backend/config/config.php. Kopirajte config.example.php i unesite podatke za bazu.');
}

/* ---------- Učitavanje servisa ---------- */
require_once __DIR__ . '/rest/services/GradService.php';
require_once __DIR__ . '/rest/services/KategorijaService.php';
require_once __DIR__ . '/rest/services/KorisnikService.php';
require_once __DIR__ . '/rest/services/MajstorService.php';
require_once __DIR__ . '/rest/services/MajstorKategorijaService.php';
require_once __DIR__ . '/rest/services/PretragaService.php';
require_once __DIR__ . '/rest/services/StatistikaService.php';
require_once __DIR__ . '/rest/services/NominatimService.php';

/* ---------- Registracija servisa u FlightPHP ---------- */
// Nakon registracije ruta može pozvati npr. Flight::grad_service()->get_all().
Flight::register('grad_service', 'GradService');
Flight::register('kategorija_service', 'KategorijaService');
Flight::register('korisnik_service', 'KorisnikService');
Flight::register('majstor_service', 'MajstorService');
Flight::register('majstor_kategorija_service', 'MajstorKategorijaService');
Flight::register('pretraga_service', 'PretragaService');
Flight::register('statistika_service', 'StatistikaService');
Flight::register('nominatim_service', 'NominatimService');

/* ---------- Centralna obrada grešaka ---------- */
// Servisne HttpException greške čuvaju svoj status (400, 404, 409, 503...).
// Ostale greške se prijavljuju serveru, ali se detalji ne šalju korisniku.
Flight::map('error', function (Throwable $e) {
    if ($e instanceof HttpException) {
        $status = $e->get_status();
        $poruka = $e->getMessage();
    } else {
        $status = 500;
        $poruka = 'Došlo je do greške na serveru. Pokušajte ponovo kasnije.';
        error_log($e);
    }

    Flight::response()
        ->clearBody()
        ->status($status)
        ->header('Content-Type', 'application/json')
        ->write(json_encode(['error' => $poruka]))
        ->send();
});

// Za URL za koji ne postoji definisana FlightPHP ruta.
Flight::map('notFound', function () {
    Flight::response()
        ->clearBody()
        ->status(404)
        ->header('Content-Type', 'application/json')
        ->write(json_encode(['error' => 'Tražena API ruta ne postoji.']))
        ->send();
});

/* ---------- Početna probna ruta ---------- */
Flight::route('GET /', function () {
    Flight::json([
        'app' => 'MajstoriWebApp API',
        'message' => 'Backend je pokrenut.'
    ]);
});

/* ---------- Učitavanje REST ruta ---------- */
// Svaki fajl poziva Flight::route() i registruje pravila za svoje URL adrese.
// Registracija rute ne izvršava odmah servisnu metodu. Metoda se poziva tek kada stigne odgovarajući zahtjev.
require_once __DIR__ . '/rest/routes/GradRoutes.php';
require_once __DIR__ . '/rest/routes/KategorijaRoutes.php';
require_once __DIR__ . '/rest/routes/KorisnikRoutes.php';
require_once __DIR__ . '/rest/routes/MajstorRoutes.php';
require_once __DIR__ . '/rest/routes/MajstorKategorijaRoutes.php';
require_once __DIR__ . '/rest/routes/PretragaRoutes.php';
require_once __DIR__ . '/rest/routes/StatistikaRoutes.php';
require_once __DIR__ . '/rest/routes/NominatimRoutes.php';

// PAŽNJA: /admin putanje još nisu zaštićene prijavom i ulogama.
// U M3 ih koristimo samo lokalno. Autentikaciju i autorizaciju dodajemo u M4.

Flight::start();
