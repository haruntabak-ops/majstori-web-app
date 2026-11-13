<?php
use OpenApi\Attributes as OA;

/*
 * OpenAPI documentation of rest/routes/PretragaRoutes.php.
 * swagger-php reads the #[OA\...] attributes; the methods are empty – they only carry the documentation.
 * Keep it in sync with the routes: one method per endpoint.
 */

/*
 * Prethodno navedeni princip preuzet je iz campus-events-app (week-06)
 * i prilagođen potrebama aplikacije MajstoriWebApp.
 */
class PretragaPaths
{
    #[OA\Get(
        path: '/majstori',
        tags: ['pretraga'],
        summary: 'Pretraga majstora po kategorijama, gradu i radijusu',
        description: "**Svrha:** Pretražuje profile majstora prema odabranim majstorlucima i opcionalnoj lokaciji.\n\n" .
            "**Kategorije:** Parametar kategorije prima ID-jeve odvojene zarezima (npr. 15,18), a API " .
            "podržava i niz ID-jeva. Traže se majstori koji su direktno označili bilo koju od izabranih " .
            "kategorija ili njene potomke. Sama oznaka nadređene kategorije ne znači da je majstor označio i " .
            "svaku potkategoriju.\n\n" .
            "**Grad i radijus:** Bez grad_id pretraga nije geografski ograničena. Ako je zadan grad_id bez " .
            "radijus_km, traže se majstori baš iz tog grada. Ako su oba zadana, traže se majstori na zračnoj " .
            "udaljenosti do navedenog broja kilometara. Radijus bez grada nije dozvoljen.\n\n" .
            "**Primjeri:** 7a: kategorije=15,18. 7b: kategorije=12. 7c: " .
            "kategorije=12&grad_id=4&radijus_km=100.\n\n" .
            "**Rezultat:** HTTP 200 i objekt s nizom majstori te brojem count. Svaki rezultat može " .
            "sadržavati naziv grada, e-mail, nazive direktnih majstorluka i udaljenost_km (null ako radijus " .
            "nije korišten). Neispravni filteri daju HTTP 400.\n\n" .
            "**Važno:** Udaljenost se računa iz koordinata gradova spremljenih u bazi, a Nominatim se ne " .
            "poziva pri ovoj pretrazi.",
        parameters: [
            new OA\Parameter(
                name: 'kategorije',
                in: 'query',
                description: "Opcionalni ID-jevi kategorija odvojeni zarezima, npr. 15,18. Pretražuju se i potomci.",
                required: false,
                example: '15,18',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'grad_id',
                in: 'query',
                description: "Opcionalni ID grada. Bez radijusa filtrira samo taj grad; uz radijus služi kao centar kruga.",
                required: false,
                example: 4,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'radijus_km',
                in: 'query',
                description: "Opcionalna nenegativna udaljenost u kilometrima (zračna linija); zahtijeva grad_id.",
                required: false,
                example: 100,
                schema: new OA\Schema(type: 'number')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/PretragaRezultat')
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getMajstoriPretraga() {}

}
