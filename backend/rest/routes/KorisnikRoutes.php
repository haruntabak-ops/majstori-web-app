<?php
/**
 * REST rute za korisničke račune (M3) – administratorski pregled i CRUD.
 *
 * KorisnikService provjerava e-mail i ulogu, hashira lozinku i uklanja lozinka_hash iz JSON odgovora (P001, P002).
 * GET rute mogu vratiti i povezani profil majstora, ako je korisnik već napravio profil.
 *
 * VAŽNO: putanja /admin u M3 samo označava namjenu; nije još autentikacija ni autorizacija!
 * Ove rute koristimo samo u lokalnom/testnom okruženju dok ne dodamo JWT i provjere uloge u M4.
 * Prijava, JWT i promjena vlastite lozinke bit će obrađeni u M4, ne izlažemo ih sada kao nezaštićene rute.
 * Swagger opisi: backend/public/v1/docs/paths/KorisnikPaths.php.
 */

/**
 * GET /admin/korisnici – vraća sve račune i postojeće profile majstora.
 * Ulaz: nema. Izlaz: JSON niz korisnika s poljem profil; lozinka_hash se ne vraća, HTTP 200.
 */
Flight::route('GET /admin/korisnici', function () {
    Flight::json(Flight::korisnik_service()->get_all_sa_profilima());
});

/**
 * GET /admin/korisnici/{id} – vraća račun i njegov profil ako postoji.
 * Ulaz: ID korisnika. Izlaz: JSON objekt bez lozinka_hash (200) ili greška 404.
 */
Flight::route('GET /admin/korisnici/@id:[0-9]+', function ($id) {
    Flight::json(Flight::korisnik_service()->get_by_id_sa_profilom($id));
});

/**
 * POST /admin/korisnici – kreira korisnički račun.
 * Ulaz: JSON {"email": "...", "lozinka": "...", "uloga": "majstor" ili "admin"}.
 * Izlaz: novi korisnik bez hasha (201) ili greška 400. Servis hashira lozinku i provjerava jedinstven e-mail (P001).
 */
Flight::route('POST /admin/korisnici', function () {
    // Rute ne hashiraju lozinku – običnu lozinku provjerava i pretvara u hash KorisnikService.
    $data = Flight::request()->data->getData();
    Flight::json(Flight::korisnik_service()->add($data), 201);
});

/**
 * PUT /admin/korisnici/{id} – mijenja e-mail, ulogu ili lozinku postojećeg računa.
 * Ulaz: ID korisnika i JSON s poljima izmjene. Izlaz: korisnik bez hasha (200) ili greška 400/404.
 * Posebna funkcija „promjena vlastite lozinke uz staru lozinku“ nije ova administratorska ruta; dolazi u M4.
 */
Flight::route('PUT /admin/korisnici/@id:[0-9]+', function ($id) {
    $data = Flight::request()->data->getData();
    Flight::json(Flight::korisnik_service()->update($data, $id));
});

/**
 * DELETE /admin/korisnici/{id} – briše korisnički račun.
 * Ulaz: ID korisnika. Izlaz: poruka (200) ili greška 404.
 * Prema pravilima naše baze, brisanje korisnika uklanja i njegov profil majstora i veze profila.
 */
Flight::route('DELETE /admin/korisnici/@id:[0-9]+', function ($id) {
    Flight::korisnik_service()->delete($id);
    Flight::json(['message' => 'Korisnički račun je obrisan.']);
});
