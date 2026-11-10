<?php
/**
 * REST ruta za vanjski Nominatim API (M3) – pronalaženje koordinata grada prije njegovog spremanja.
 *
 * Administrator unese naziv grada, a naš backend preko NominatimService pozove vanjski servis i vrati pronađenu lokaciju.
 * Frontend će u M4 pozivati ovu rutu UMJESTO direktnog poziva Nominatimu i prikazati koordinate na postojećoj OSM karti.
 * Ova ruta NE UPISUJE grad u bazu: tek poslije administratorove potvrde koristi se POST /admin/gradovi (P009, V004).
 *
 * Timeout, cURL greške i provjeru odgovora obrađuje NominatimService; ruta ne kontaktira internet direktno.
 * Swagger opis: backend/public/v1/docs/paths/NominatimPaths.php.
 */

/**
 * GET /lokacije/pretraga?naziv=Sarajevo – traži grad preko Nominatima.
 * Ulaz: tekstualni query parametar naziv. Izlaz: JSON {naziv, display_name, latitude, longitude}, HTTP 200.
 * Prazan naziv daje 400, nepostojeća lokacija 404, a prekid/greška vanjskog API-ja 503.
 */
Flight::route('GET /lokacije/pretraga', function () {
    $naziv = Flight::request()->query['naziv'] ?? '';
    Flight::json(Flight::nominatim_service()->pronadji_grad($naziv));
});
