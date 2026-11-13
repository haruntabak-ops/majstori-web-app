<?php
/**
 * REST rute za gradove (M3) – po uzoru na profesoricin Week 06, uz naše nazive i pravila.
 *
 * Javne GET rute služe za prikaz gradova u pretrazi i izbor lokacije profila majstora.
 * Administratorske rute služe za CRUD. Postojeći frontend nudi dodavanje grada tek poslije provjere lokacije na karti (P009, V004),
 * ali M3 zahtijeva i PUT/DELETE operacije. GradService odbija izmjenu ili brisanje grada koji već koriste profili majstora.
 *
 * PUT i DELETE nisu predviđeni kao dugmad u administratorskom GUI-ju (P010). U M4 ćemo /admin rute zaštititi autorizacijom.
 * Swagger opise ovih ruta dodat ćemo u backend/public/v1/docs/paths/GradPaths.php.
 */

/**
 * GET /gradovi – vraća sve gradove zajedno s koordinatama.
 * Ulaz: nema parametara. Izlaz: JSON niz gradova, HTTP 200.
 * Poziva GradService::get_all(), koji čita podatke kroz GradDao.
 */
Flight::route('GET /gradovi', function () {
    Flight::json(Flight::grad_service()->get_all());
});

/**
 * GET /gradovi/{id} – vraća jedan grad na osnovu njegovog ID-a.
 * Ulaz: ID grada u URL-u. Izlaz: JSON objekt grada (200) ili greška 404 ako grad ne postoji.
 */
Flight::route('GET /gradovi/@id:[0-9]+', function ($id) {
    Flight::json(Flight::grad_service()->get_by_id($id));
});

/**
 * POST /admin/gradovi – dodaje novi grad s nazivom i koordinatama.
 * Ulaz: JSON {"naziv": "...", "latitude": broj, "longitude": broj}.
 * Izlaz: novi grad i HTTP 201; neispravni podaci daju 400. Servis provjerava duplikate i raspone koordinata.
 * Napomena (P009, V004): admin prethodno provjerava lokaciju na karti; sama ruta ne može provjeriti je li kliknuo potvrdu.
 */
Flight::route('POST /admin/gradovi', function () {
    // Tijelo HTTP zahtjeva pretvaramo u PHP niz i prosljeđujemo servisu; ovdje nema SQL-a ni validacije.
    $data = Flight::request()->data->getData();
    Flight::json(Flight::grad_service()->add($data), 201);
});

/**
 * PUT /admin/gradovi/{id} – mijenja naziv ili koordinate postojećeg grada.
 * Ulaz: ID u URL-u i JSON polja koja mijenjamo. Izlaz: ažurirani grad (200) ili greška 400/404/409.
 * GradService odbija promjenu lokacije koja se već koristi na profilima majstora; GUI ovu radnju ne nudi (P010).
 */
Flight::route('PUT /admin/gradovi/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::grad_service()->update($data, $id));
});

/**
 * DELETE /admin/gradovi/{id} – briše grad samo ako ga ne koristi nijedan profil majstora.
 * Ulaz: ID grada. Izlaz: poruka o uspjehu (200) ili greška 404/409.
 * Provjeru povezanosti s majstorima radi GradService, a ne ruta; GUI brisanje ne nudi (P010).
 */
Flight::route('DELETE /admin/gradovi/@id:[0-9]+', function ($id) {
    Flight::grad_service()->delete($id);
    Flight::json(['message' => 'Grad je obrisan.']);
});
