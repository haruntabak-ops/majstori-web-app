<?php
/**
 * REST rute za direktne veze majstor–kategorija (M3).
 *
 * Tabela MAJSTOR_KATEGORIJE nema obični id; jednu vezu određuju majstor_id i kategorija_id.
 * Zato su GET jedne veze i DELETE definisani s OBA ID-a, a PUT mijenja kompletan izbor kategorija jednog majstora.
 * MajstorKategorijaService provjerava da majstor i kategorija postoje, odbija duplikate i mijenja izbor u transakciji (P004).
 *
 * PUT rute su administratorske za potrebe CRUD testiranja; /moji-majstorluci pravit ćemo u M4 uz JWT identitet.
 * U M4 sve /admin rute treba zaštititi provjerom uloge.
 * Swagger opisi: backend/public/v1/docs/paths/MajstorKategorijaPaths.php.
 */

/**
 * GET /admin/majstor-kategorije – vraća sve direktne parove majstor_id/kategorija_id iz vezne tabele.
 * Ulaz: nema. Izlaz: JSON niz veza, HTTP 200.
 */
Flight::route('GET /admin/majstor-kategorije', function () {
    Flight::json(Flight::majstor_kategorija_service()->get_all());
});

/**
 * GET /majstori/{majstor_id}/majstorluci – vraća direktno odabrane kategorije jednog javnog profila majstora.
 * Ulaz: ID majstora. Izlaz: JSON niz direktnih veza (200) ili greška 404.
 * Ovdje ne dodajemo automatski kategorije potomaka (P004).
 */
Flight::route('GET /majstori/@majstor_id:[0-9]+/majstorluci', function ($majstor_id) {
    Flight::json(Flight::majstor_kategorija_service()->get_by_majstor_id($majstor_id));
});

/**
 * GET /admin/majstor-kategorije/kategorija/{id} – vraća sve direktne veze s odabranom kategorijom.
 * Ulaz: ID kategorije. Izlaz: JSON niz veza, HTTP 200; nepostojeća kategorija daje 404.
 */
Flight::route('GET /admin/majstor-kategorije/kategorija/@id:[0-9]+', function ($id) {
    Flight::json(Flight::majstor_kategorija_service()->get_by_kategorija_id($id));
});

/**
 * GET /admin/majstor-kategorije/{majstor_id}/{kategorija_id} – vraća jedan par iz vezne tabele.
 * Ulaz: oba dijela složenog ključa. Izlaz: JSON objekt veze (200) ili greška 404.
 */
Flight::route('GET /admin/majstor-kategorije/@majstor_id:[0-9]+/@kategorija_id:[0-9]+', function ($majstor_id, $kategorija_id) {
    Flight::json(Flight::majstor_kategorija_service()->get_by_keys($majstor_id, $kategorija_id));
});

/**
 * POST /admin/majstor-kategorije – dodaje jednu direktnu vezu između profila i kategorije.
 * Ulaz: JSON {"majstor_id": broj, "kategorija_id": broj}. Izlaz: novi par (201) ili greška 400/404.
 * Servis odbija duplikat i ne označava automatski potkategorije (P004).
 */
Flight::route('POST /admin/majstor-kategorije', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::majstor_kategorija_service()->add($data), 201);
});

/**
 * PUT /admin/majstori/{majstor_id}/majstorluci – zamjenjuje kompletan izbor direktnih kategorija jednog majstora.
 * Ulaz: ID majstora i JSON {"kategorija_ids": [12, 15, 18]}; prazan niz [] uklanja sve veze.
 * Izlaz: JSON niz konačnih veza (200) ili greška 400/404. Servis briše uklonjene i dodaje nove veze u jednoj transakciji.
 * Za veznu tabelu nema običnog PUT /{id}, jer ne postoji jednostavni primarni ključ.
 */
Flight::route('PUT /admin/majstori/@majstor_id:[0-9]+/majstorluci', function ($majstor_id) {
    $data = Flight::request()->data->getData();
    // Servis odlučuje koje veze treba dodati ili obrisati; ruta samo čita niz poslanih ID-jeva.
    Flight::json(Flight::majstor_kategorija_service()->zamijeni_izbor($majstor_id, $data['kategorija_ids'] ?? null));
});

/**
 * DELETE /admin/majstor-kategorije/{majstor_id}/{kategorija_id} – briše tačno jednu vezu.
 * Ulaz: oba dijela složenog ključa. Izlaz: poruka (200) ili greška 404.
 * Profil majstora i sama kategorija ostaju u bazi; briše se samo njihov odnos.
 */
Flight::route('DELETE /admin/majstor-kategorije/@majstor_id:[0-9]+/@kategorija_id:[0-9]+', function ($majstor_id, $kategorija_id) {
    Flight::majstor_kategorija_service()->delete_by_keys($majstor_id, $kategorija_id);
    Flight::json(['message' => 'Veza majstora i kategorije je obrisana.']);
});
