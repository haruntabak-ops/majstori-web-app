<?php
/**
 * Javna REST ruta za osnovnu statistiku početne stranice (M3), prema docs/FRONTEND_API_PLAN.md.
 *
 * StatistikaService poziva postojeće DAO brojače majstora, kategorija i gradova.
 * Ovdje nemamo novu tabelu niti upisujemo podatke; samo vraćamo tri brojčane vrijednosti.
 * Swagger opis: backend/public/v1/docs/paths/StatistikaPaths.php.
 */

/**
 * GET /statistika – vraća brojeve koji se prikazuju na početnoj stranici.
 * Ulaz: nema. Izlaz: JSON {"majstori": broj, "kategorije": broj, "gradovi": broj}, HTTP 200.
 */
Flight::route('GET /statistika', function () {
    Flight::json(Flight::statistika_service()->get_stats());
});
