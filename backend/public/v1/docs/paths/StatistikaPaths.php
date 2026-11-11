<?php
use OpenApi\Attributes as OA;

/*
 * OpenAPI documentation of rest/routes/StatistikaRoutes.php.
 * swagger-php reads the #[OA\...] attributes; the methods are empty – they only carry the documentation.
 * Keep it in sync with the routes: one method per endpoint.
 */

/*
 * Prethodno navedeni princip preuzet je iz campus-events-app (week-06)
 * i prilagođen potrebama aplikacije MajstoriWebApp.
 */
class StatistikaPaths
{
    #[OA\Get(
        path: '/statistika',
        tags: ['statistika'],
        summary: 'Osnovna statistika aplikacije',
        description: "**Svrha:** Vraća osnovne zbirne podatke za informativni prikaz aplikacije.\n\n" .
            "**Rezultat:** JSON objekt s tri ukupna broja: majstori (broj profila), kategorije (broj " .
            "kategorija) i gradovi (broj gradova). To nisu korisnički računi ni broj pojedinačnih veza " .
            "majstor–kategorija.\n\n" .
            "**Napomena:** Brojevi se čitaju iz baze pri pozivu i zato se mijenjaju nakon dodavanja ili " .
            "brisanja podataka.",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/Statistika')
            )
        ]
    )]
    public function getStatistika() {}

}
