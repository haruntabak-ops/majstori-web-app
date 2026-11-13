<?php
use OpenApi\Attributes as OA;

/*
 * OpenAPI documentation of rest/routes/NominatimRoutes.php.
 * swagger-php reads the #[OA\...] attributes; the methods are empty – they only carry the documentation.
 * Keep it in sync with the routes: one method per endpoint.
 */

/*
 * Prethodno navedeni princip preuzet je iz campus-events-app (week-06)
 * i prilagođen potrebama aplikacije MajstoriWebApp.
 */
class NominatimPaths
{
    #[OA\Get(
        path: '/lokacije/pretraga',
        tags: ['nominatim'],
        summary: 'Pronalaženje koordinata grada preko Nominatima',
        description: "**Svrha:** Pomoćna pretraga grada putem vanjskog Nominatim API-ja radi pronalaženja koordinata, " .
            "npr. kada administrator dodaje grad.\n\n" .
            "**Ulaz:** Obavezni query parametar naziv (2 do 100 znakova), npr. Sarajevo.\n\n" .
            "**Pravila:** Servis pretražuje lokacije u Bosni i Hercegovini, pokušava odabrati rezultat tipa " .
            "grad/naselje/općina i vraća pronađeni naziv, detaljniji opis i koordinate. Ako ne prepozna taj " .
            "tip, može uzeti prvi rezultat.\n\n" .
            "**Važno:** Ruta ništa ne upisuje u bazu. Koordinate se kasnije mogu poslati u POST " .
            "/admin/gradovi. Nominatim se ne koristi za izračunavanje udaljenosti u pretrazi majstora.\n\n" .
            "**Rezultat:** HTTP 200 s naziv, display_name, latitude i longitude. HTTP 400 za neispravan " .
            "naziv; HTTP 404 ako nema rezultata; HTTP 503 ako vanjski servis nije dostupan.",
        parameters: [
            new OA\Parameter(
                name: 'naziv',
                in: 'query',
                description: "Naziv grada/naselja u BiH koji tražimo radi koordinata (2–100 znakova).",
                required: true,
                example: 'Sarajevo',
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/NominatimLokacija')
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 503,
                description: 'Vanjski servis nije dostupan',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getLokacijePretraga() {}

}
