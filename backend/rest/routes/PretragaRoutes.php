<?php
/**
 * Javna REST ruta za listu i pretragu majstora (M3), prema docs/FRONTEND_API_PLAN.md.
 *
 * Isti GET /majstori služi za pretragu bez filtera i za filtere po više kategorija, gradu i radijusu.
 * PretragaService provjerava filtere, a MajstorDao::pretrazi() računa kategorije/potomke i udaljenosti iz naše baze.
 * Nominatim se NE koristi za računanje udaljenosti između gradova.
 * Swagger opis: backend/public/v1/docs/paths/PretragaPaths.php.
 */

/**
 * GET /majstori – vraća majstore koji odgovaraju filterima pretrage.
 * Ulaz (sve opcionalno):
 *   kategorije=15,18 – ID-jevi kategorija odvojeni zarezima; moguće je poslati i kategorije[]=15&kategorije[]=18.
 *   grad_id=4        – grad iz kojeg tražimo majstore.
 *   radijus_km=100   – krug u km oko izabranog grada; bez radijusa traži se samo izabrani grad.
 * Izlaz: JSON {"majstori": [...], "count": broj}, HTTP 200; neispravni filteri daju 400.
 * Naš frontend koristi isti JSON oblik i kada nije postavljen nijedan filter.
 */
Flight::route('GET /majstori', function () {
    // Query parametre samo čitamo; njihovu validaciju i pretvaranje u brojeve obavlja PretragaService.
    $kategorije = Flight::request()->query['kategorije'] ?? [];
    $grad_id = Flight::request()->query['grad_id'] ?? null;
    $radijus_km = Flight::request()->query['radijus_km'] ?? null;

    Flight::json(Flight::pretraga_service()->pretrazi($kategorije, $grad_id, $radijus_km));
});
