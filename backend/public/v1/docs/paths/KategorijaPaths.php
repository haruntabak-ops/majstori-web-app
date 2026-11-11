<?php
use OpenApi\Attributes as OA;

/*
 * OpenAPI documentation of rest/routes/KategorijaRoutes.php.
 * swagger-php reads the #[OA\...] attributes; the methods are empty – they only carry the documentation.
 * Keep it in sync with the routes: one method per endpoint.
 */

/*
 * Prethodno navedeni princip preuzet je iz campus-events-app (week-06)
 * i prilagođen potrebama aplikacije MajstoriWebApp.
 */
class KategorijaPaths
{
    #[OA\Get(
        path: '/kategorije',
        tags: ['kategorije'],
        summary: 'Dohvat svih kategorija',
        description: "**Svrha:** Vraća sve kategorije majstorluka, uključujući korijenske, roditeljske i podređene " .
            "kategorije.\n\n" .
            "**Podaci:** Svaki zapis sadrži id, naziv i roditelj_id; vrijednost null u roditelj_id znači " .
            "korijensku kategoriju.\n\n" .
            "**Rezultat:** HTTP 200 i JSON niz. Stablo se na frontendu može izgraditi pomoću roditelj_id.",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Kategorija'))
            )
        ]
    )]
    public function getKategorije() {}

    #[OA\Get(
        path: '/kategorije/korijeni',
        tags: ['kategorije'],
        summary: 'Dohvat korijenskih kategorija',
        description: "**Svrha:** Vraća samo kategorije bez roditelja (roditelj_id = null), odnosno početne čvorove " .
            "stabla kategorija.\n\n" .
            "**Ulaz:** Nema parametara.\n\n" .
            "**Rezultat:** HTTP 200 i niz korijenskih kategorija, npr. Sva majstorstva.",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Kategorija'))
            )
        ]
    )]
    public function getKategorijeKorijeni() {}

    #[OA\Get(
        path: '/kategorije/{id}/djeca',
        tags: ['kategorije'],
        summary: 'Dohvat direktnih potkategorija',
        description: "**Svrha:** Vraća samo neposredne potkategorije zadane roditeljske kategorije.\n\n" .
            "**Ulaz:** ID postojeće roditeljske kategorije u URL-u, npr. 12 za Niska struja.\n\n" .
            "**Važno:** Ne vraća sve dublje potomke odjednom. Na primjer, za Niska struja vraća Alarmni " .
            "sistemi i Video-nadzor, ali ne i IP kamere, jer su dijete Video-nadzora.\n\n" .
            "**Rezultat:** HTTP 200 i niz direktne djece (može biti prazan); HTTP 404 ako roditelj ne " .
            "postoji.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID roditeljske kategorije čiju direktnu djecu želimo.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Kategorija'))
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getKategorijaDjeca() {}

    #[OA\Get(
        path: '/kategorije/{id}',
        tags: ['kategorije'],
        summary: 'Dohvat jedne kategorije',
        description: "**Svrha:** Pronalazi jednu kategoriju po ID-u.\n\n" .
            "**Rezultat:** HTTP 200 i podaci id, naziv, roditelj_id; HTTP 404 ako kategorija nije pronađena.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID kategorije iz tabele KATEGORIJE.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/Kategorija')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getKategorijaById() {}

    #[OA\Post(
        path: '/admin/kategorije',
        tags: ['kategorije'],
        summary: 'Dodavanje kategorije',
        description: "**Svrha:** Dodaje novu kategoriju majstorluka u hijerarhijsko stablo.\n\n" .
            "**Ulaz:** JSON s obaveznim nazivom (2 do 100 znakova) i opcionalnim roditelj_id.\n\n" .
            "**Pravila:** Ako roditelj_id izostane ili je null, nastaje korijenska kategorija. Ako je " .
            "naveden, roditelj mora postojati. Naziv kategorije mora biti jedinstven bez obzira na velika i " .
            "mala slova.\n\n" .
            "**Rezultat:** HTTP 201 i novi zapis; HTTP 400 kada naziv nije valjan, već postoji ili roditelj " .
            "nije ispravan.\n\n" .
            "**Pristup:** Administratorska ruta; u M3 /admin još nije zaštićen prijavom.",
        requestBody: new OA\RequestBody(
            description: "JSON s nazivom i opcionalnim roditelj_id.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/KategorijaInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kreirani zapis',
                content: new OA\JsonContent(ref: '#/components/schemas/Kategorija')
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function postKategorije() {}

    #[OA\Put(
        path: '/admin/kategorije/{id}',
        tags: ['kategorije'],
        summary: 'Izmjena ili premještanje kategorije',
        description: "**Svrha:** Mijenja naziv kategorije i/ili je premješta pod drugog roditelja.\n\n" .
            "**Ulaz:** ID postojeće kategorije i JSON s nazivom i/ili roditelj_id; nije potrebno ponovo " .
            "slati oba polja.\n\n" .
            "**Pravila:** Novi roditelj mora postojati, kategorija ne smije postati sama sebi roditelj niti " .
            "potomak vlastitog potomka (nema ciklusa, V002). Null roditelj_id premješta kategoriju u " .
            "korijen. Nazivi ostaju jedinstveni.\n\n" .
            "**Posljedica:** Premještanje mijenja strukturu stabla, ali ne dodaje i ne briše direktne veze " .
            "majstora s kategorijama.\n\n" .
            "**Rezultat:** HTTP 200 i ažurirana kategorija; HTTP 400 za kršenje pravila; HTTP 404 ako " .
            "kategorija za izmjenu ne postoji.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID kategorije koju mijenjamo, premještamo ili brišemo.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: "JSON s nazivom i/ili roditelj_id; null postavlja kategoriju u korijen.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/KategorijaUpdateInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/Kategorija')
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
            )
        ]
    )]
    public function putKategorijaById() {}

    #[OA\Delete(
        path: '/admin/kategorije/{id}',
        tags: ['kategorije'],
        summary: 'Brisanje kategorije',
        description: "**Svrha:** Briše jednu kategoriju iz stabla.\n\n" .
            "**Preduslov:** Kategorija mora biti list: ne smije imati nijednu direktnu potkategoriju " .
            "(pravilo V001). Ako ima djecu, prvo treba obrisati ili premjestiti tu djecu.\n\n" .
            "**Posljedica:** Za kategoriju koja je list prvo se uklanjaju njene veze u tabeli " .
            "MAJSTOR_KATEGORIJE, zatim se briše kategorija (A001). Rad se izvršava u transakciji: u slučaju " .
            "greške promjene se poništavaju.\n\n" .
            "**Važno:** Majstori i njihovi korisnički računi OSTAJU u bazi. Ovo je druga operacija od " .
            "brisanja samo jedne veze majstor–kategorija.\n\n" .
            "**Rezultat:** HTTP 200 i poruka; HTTP 400 ako kategorija ima djecu; HTTP 404 ako ne postoji.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID kategorije koju mijenjamo, premještamo ili brišemo.",
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
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function deleteKategorijaById() {}

}
