<?php
/**
 * REST rute za stablo kategorija / majstorluka (M3).
 *
 * Javne rute vraćaju cijelo stablo ili pojedine dijelove hijerarhije; administratorske rute omogućavaju CRUD (P007).
 * KategorijaService provjerava duplikate i cikluse (V002), dozvoljava brisanje samo listova (V001) i pri brisanju lista uklanja
 * povezane zapise iz MAJSTOR_KATEGORIJE u transakciji (A001), ali ne briše profile majstora.
 *
 * U M4 će /admin rute zahtijevati administratorsku autorizaciju.
 * Swagger opisi: backend/public/v1/docs/paths/KategorijaPaths.php.
 */

/**
 * GET /kategorije – vraća cijelo stablo u obliku ravne liste (id, naziv, roditelj_id).
 * Ulaz: nema. Izlaz: JSON niz kategorija, HTTP 200. Frontend iz roditelj_id sam crta hijerarhiju.
 */
Flight::route('GET /kategorije', function () {
    Flight::json(Flight::kategorija_service()->get_all());
});

/**
 * GET /kategorije/korijeni – vraća kategorije bez roditelja, tj. one na vrhu stabla.
 * Ulaz: nema. Izlaz: JSON niz korijenskih kategorija. U našim početnim podacima to je „Sva majstorstva“.
 */
Flight::route('GET /kategorije/korijeni', function () {
    Flight::json(Flight::kategorija_service()->get_children());
});

/**
 * GET /kategorije/{id}/djeca – vraća samo direktne potkategorije, ne sve potomke na nižim nivoima.
 * Ulaz: ID roditeljske kategorije. Izlaz: JSON niz djece (200) ili greška 404 ako roditelj ne postoji.
 */
Flight::route('GET /kategorije/@id:[0-9]+/djeca', function ($id) {
    Flight::json(Flight::kategorija_service()->get_children($id));
});

/**
 * GET /kategorije/{id} – vraća jednu kategoriju na osnovu njenog ID-a.
 * Ulaz: ID kategorije. Izlaz: JSON objekt (200) ili greška 404.
 */
Flight::route('GET /kategorije/@id:[0-9]+', function ($id) {
    Flight::json(Flight::kategorija_service()->get_by_id($id));
});

/**
 * POST /admin/kategorije – dodaje novu kategoriju pod izabranog roditelja ili na vrh stabla.
 * Ulaz: JSON {"naziv": "...", "roditelj_id": broj ili null}. Izlaz: novi zapis (201) ili greška 400.
 * KategorijaService provjerava naziv, duplikate i postojanje roditelja (P007).
 */
Flight::route('POST /admin/kategorije', function () {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::kategorija_service()->add($data), 201);
});

/**
 * PUT /admin/kategorije/{id} – mijenja naziv kategorije ili je premješta pod drugog roditelja.
 * Ulaz: ID kategorije i JSON s izmjenama. Izlaz: ažurirana kategorija (200) ili greška 400/404.
 * Servis odbija cikluse (V002) i ne mijenja direktno odabrane majstorluke postojećih majstora (P008).
 */
Flight::route('PUT /admin/kategorije/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::kategorija_service()->update($data, $id));
});

/**
 * DELETE /admin/kategorije/{id} – briše samo kategoriju bez potkategorija (list u stablu).
 * Ulaz: ID kategorije. Izlaz: poruka o brisanju (200) ili greška 400/404.
 * Ako je list povezan s majstorima, servis prvo uklanja njegove veze, pa kategoriju; sve u transakciji (V001, A001).
 */
Flight::route('DELETE /admin/kategorije/@id:[0-9]+', function ($id) {
    // Cijeli postupak provjere, brisanja veza i transakcije obavlja KategorijaService::delete().
    Flight::kategorija_service()->delete($id);
    Flight::json(['message' => 'Kategorija je obrisana.']);
});
