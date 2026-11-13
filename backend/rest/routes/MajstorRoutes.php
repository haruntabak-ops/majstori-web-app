<?php
/**
 * REST rute za profile majstora (M3) – javni profil i administratorski CRUD.
 *
 * GET /majstori s filterima definisan je posebno u PretragaRoutes.php, jer vraća listu i broj rezultata pretrage.
 * MajstorService vodi računa o vlasniku korisničkog profila, ispravnosti grada i direktno izabranim majstorlucima (P003–P005).
 *
 * Rute /admin/... u M3 još NISU zaštićene: JWT i autorizaciju dodajemo u M4.
 * Rute /moj-profil i /moji-majstorluci čekaju M4, kada identitet uzimamo iz JWT-a, a ne iz proizvoljnog ID-a klijenta.
 * Swagger opisi: backend/public/v1/docs/paths/MajstorPaths.php.
 */

/**
 * GET /majstori/{id} – vraća javni profil izabranog majstora zajedno s direktno odabranim majstorlucima.
 * Ulaz: ID profila. Izlaz: JSON objekt profila (200) ili greška 404.
 */
Flight::route('GET /majstori/@id:[0-9]+', function ($id) {
    Flight::json(Flight::majstor_service()->get_by_id($id));
});

/**
 * GET /admin/majstori – vraća sve profile radi administratorskog pregleda i provjere CRUD operacija.
 * Ulaz: nema. Izlaz: JSON niz profila, HTTP 200.
 * Za javnu pretragu s parametrima koristimo GET /majstori iz PretragaRoutes.php.
 */
Flight::route('GET /admin/majstori', function () {
    Flight::json(Flight::majstor_service()->get_all());
});

/**
 * POST /admin/majstori – kreira profil za postojećeg korisnika uloge majstor.
 * Ulaz: JSON {"korisnik_id", "grad_id", "ime", "prezime", "telefon", "opis"}.
 * Izlaz: novokreirani profil s majstorlucima (201) ili greška 400; servis provjerava korisnika i grad (P003, P005).
 */
Flight::route('POST /admin/majstori', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::majstor_service()->add($data), 201);
});

/**
 * PUT /admin/majstori/{id} – mijenja postojeći profil, ali ne mijenja vlasnika korisničkog računa.
 * Ulaz: ID profila i JSON s izmjenama. Izlaz: ažurirani profil (200) ili greška 400/404.
 * Provjeru grada i zabranu izmjene korisnik_id obavlja MajstorService.
 */
Flight::route('PUT /admin/majstori/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::majstor_service()->update($data, $id));
});

/**
 * DELETE /admin/majstori/{id} – briše profil majstora, ali ne i njegov korisnički račun.
 * Ulaz: ID profila. Izlaz: poruka o uspjehu (200) ili greška 404.
 * Baza uklanja povezane direktne veze iz MAJSTOR_KATEGORIJE prema svojim stranim ključevima.
 */
Flight::route('DELETE /admin/majstori/@id:[0-9]+', function ($id) {
    Flight::majstor_service()->delete($id);
    Flight::json(['message' => 'Profil majstora je obrisan.']);
});
