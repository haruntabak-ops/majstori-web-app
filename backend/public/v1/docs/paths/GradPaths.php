<?php
use OpenApi\Attributes as OA;

/*
 * OpenAPI documentation of rest/routes/GradRoutes.php.
 * swagger-php reads the #[OA\...] attributes; the methods are empty – they only carry the documentation.
 * Keep it in sync with the routes: one method per endpoint.
 */

/*
 * Prethodno navedeni princip preuzet je iz campus-events-app (week-06)
 * i prilagođen potrebama aplikacije MajstoriWebApp.
 */
class GradPaths
{
    #[OA\Get(
        path: '/gradovi',
        tags: ['gradovi'],
        summary: 'Dohvat svih gradova',
        description: "**Svrha:** Vraća listu svih gradova upisanih u bazu, zajedno s njihovim ID-jem, nazivom i " .
            "koordinatama.\n\n" .
            "**Ulaz:** Nisu potrebni parametri.\n\n" .
            "**Upotreba:** Gradovi služe za izbor mjesta u pretrazi majstora i prilikom kreiranja profila.\n\n" .
            "**Rezultat:** HTTP 200 i JSON niz gradova. Prazna tabela daje prazan niz.",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Grad'))
            )
        ]
    )]
    public function getGradovi() {}

    #[OA\Get(
        path: '/gradovi/{id}',
        tags: ['gradovi'],
        summary: 'Dohvat jednog grada',
        description: "**Svrha:** Vraća podatke o jednom gradu prema njegovom ID-u.\n\n" .
            "**Ulaz:** U URL-u se šalje postojeći pozitivan ID grada, npr. 1 za Sarajevo u početnoj testnoj " .
            "bazi.\n\n" .
            "**Rezultat:** HTTP 200 i grad s koordinatama. Ako grad ne postoji, vraća se HTTP 404.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID grada iz tabele GRADOVI (npr. 1 = Sarajevo u testnoj bazi).",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/Grad')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getGradById() {}

    #[OA\Post(
        path: '/admin/gradovi',
        tags: ['gradovi'],
        summary: 'Dodavanje grada',
        description: "**Svrha:** Dodaje novi grad u bazu, kako bi ga aplikacija mogla ponuditi pri izboru grada i " .
            "geografskom filtriranju.\n\n" .
            "**Ulaz:** JSON s obaveznim poljima naziv, latitude i longitude. Naziv mora imati 2 do 100 " .
            "znakova; latitude je od -90 do 90, a longitude od -180 do 180.\n\n" .
            "**Pravila:** Naziv grada mora biti jedinstven. Ova ruta NE traži koordinate od Nominatima; " .
            "koordinate se šalju u JSON tijelu. Nominatim ima zasebnu GET rutu.\n\n" .
            "**Rezultat:** HTTP 201 i novi grad s ID-jem koji je dodijelila baza. Neispravni ili duplirani " .
            "podaci daju HTTP 400.\n\n" .
            "**Pristup:** Administratorska namjena; u trenutnoj M3 verziji /admin putanje još nisu zaštićene " .
            "prijavom i ulogama.",
        requestBody: new OA\RequestBody(
            description: "JSON s nazivom i koordinatama novog grada.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/GradInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kreirani zapis',
                content: new OA\JsonContent(ref: '#/components/schemas/Grad')
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function postGradovi() {}

    #[OA\Put(
        path: '/admin/gradovi/{id}',
        tags: ['gradovi'],
        summary: 'Izmjena grada',
        description: "**Svrha:** Mijenja naziv i/ili koordinate postojećeg grada.\n\n" .
            "**Ulaz:** ID grada u URL-u i JSON samo s poljima koja želimo promijeniti. Preostale vrijednosti " .
            "ostaju sačuvane.\n\n" .
            "**Pravila:** Ako grad koristi barem jedan profil majstora, promjena naziva ili koordinata nije " .
            "dozvoljena (HTTP 409). Provjeravaju se naziv, jedinstvenost i raspon koordinata kao pri " .
            "dodavanju.\n\n" .
            "**Rezultat:** HTTP 200 i ažurirani grad; HTTP 404 ako ID ne postoji, HTTP 400 za neispravne " .
            "podatke.\n\n" .
            "**Pristup:** Administratorska ruta; provjera prijave i uloge planirana je za narednu fazu.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID postojećeg grada koji se mijenja ili briše.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: "JSON samo s poljima koja se mijenjaju; ostala polja ostaju nepromijenjena.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/GradUpdateInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/Grad')
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
                response: 409,
                description: 'Sukob s poslovnim pravilima',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function putGradById() {}

    #[OA\Delete(
        path: '/admin/gradovi/{id}',
        tags: ['gradovi'],
        summary: 'Brisanje grada',
        description: "**Svrha:** Briše grad iz baze.\n\n" .
            "**Preduslovi:** Grad mora postojati i ne smije biti izabran ni u jednom profilu majstora.\n\n" .
            "**Pravila:** Ako grad koriste profili majstora, brisanje se odbija (HTTP 409). Profili majstora " .
            "se ne brišu.\n\n" .
            "**Rezultat:** HTTP 200 i poruka o brisanju; HTTP 404 ako grad ne postoji.\n\n" .
            "**Pristup:** Administratorska ruta; u M3 još nije zaštićena autentikacijom.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID postojećeg grada koji se mijenja ili briše.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Poruka o uspjehu',
                content: new OA\JsonContent(ref: '#/components/schemas/Message')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 409,
                description: 'Sukob s poslovnim pravilima',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function deleteGradById() {}

}
