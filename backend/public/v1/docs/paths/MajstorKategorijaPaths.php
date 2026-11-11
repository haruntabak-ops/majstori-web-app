<?php
use OpenApi\Attributes as OA;

/*
 * OpenAPI documentation of rest/routes/MajstorKategorijaRoutes.php.
 * swagger-php reads the #[OA\...] attributes; the methods are empty – they only carry the documentation.
 * Keep it in sync with the routes: one method per endpoint.
 */

/*
 * Prethodno navedeni princip preuzet je iz campus-events-app (week-06)
 * i prilagođen potrebama aplikacije MajstoriWebApp.
 */
class MajstorKategorijaPaths
{
    #[OA\Get(
        path: '/admin/majstor-kategorije',
        tags: ['majstor-kategorije'],
        summary: 'Dohvat svih veza majstor–kategorija',
        description: "**Svrha:** Dohvata sve direktno spremljene veze između profila majstora i kategorija.\n\n" .
            "**Podaci:** Svaki red ima majstor_id i kategorija_id. To su stvarno odabrane kategorije, a ne " .
            "sve nadređene kategorije izvedene iz stabla.\n\n" .
            "**Rezultat:** HTTP 200 i JSON niz veza.",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/MajstorKategorija'))
            )
        ]
    )]
    public function getMajstorKategorije() {}

    #[OA\Get(
        path: '/majstori/{majstor_id}/majstorluci',
        tags: ['majstor-kategorije'],
        summary: 'Dohvat direktnih majstorluka jednog majstora',
        description: "**Svrha:** Vraća direktno odabrane kategorije konkretnog profila majstora.\n\n" .
            "**Ulaz:** majstor_id je ID PROFILA iz tabele MAJSTORI, ne korisnik_id računa.\n\n" .
            "**Važno:** Ne vraća automatski roditeljske ni sve podređene kategorije. Svaki zapis sadrži samo " .
            "majstor_id i kategorija_id; nazive kategorija možeš vidjeti u javnom profilu majstora.\n\n" .
            "**Rezultat:** HTTP 200 i niz veza, uključujući prazan niz ako majstor nema odabranih " .
            "kategorija. Nepostojeći ID majstora servis prijavljuje kao HTTP 400.",
        parameters: [
            new OA\Parameter(
                name: 'majstor_id',
                in: 'path',
                description: "ID profila majstora, ne ID korisničkog računa.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/MajstorKategorija'))
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravan ili nepostojeći ID',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getMajstorMajstorluci() {}

    #[OA\Get(
        path: '/admin/majstor-kategorije/kategorija/{id}',
        tags: ['majstor-kategorije'],
        summary: 'Dohvat veza prema kategoriji',
        description: "**Svrha:** Prikazuje sve direktne veze majstora s jednom zadatom kategorijom.\n\n" .
            "**Ulaz:** ID kategorije u URL-u.\n\n" .
            "**Važno:** Ovdje se ne pretražuju potomci kategorije; prikazuju se samo izričito sačuvane veze " .
            "s tom kategorijom.\n\n" .
            "**Rezultat:** HTTP 200 i niz veza; ako nema povezanih majstora, niz je prazan. Nepostojeća " .
            "kategorija daje HTTP 400.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID kategorije za koju tražimo direktne veze s majstorima.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/MajstorKategorija'))
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravan ili nepostojeći ID',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getMajstorKategorijeByKategorija() {}

    #[OA\Get(
        path: '/admin/majstor-kategorije/{majstor_id}/{kategorija_id}',
        tags: ['majstor-kategorije'],
        summary: 'Dohvat jedne veze majstor–kategorija',
        description: "**Svrha:** Provjerava postoji li tačno jedna veza između određenog majstora i kategorije.\n\n" .
            "**Ulaz:** Dva ID-a u URL-u zajedno čine složeni ključ veze (majstor_id, kategorija_id).\n\n" .
            "**Rezultat:** HTTP 200 i veza ako postoji. Ako veza ne postoji, HTTP 404; ako sam majstor ne " .
            "postoji, HTTP 400.",
        parameters: [
            new OA\Parameter(
                name: 'majstor_id',
                in: 'path',
                description: "ID profila majstora, prvi dio složenog ključa.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'kategorija_id',
                in: 'path',
                description: "ID kategorije, drugi dio složenog ključa.",
                required: true,
                example: 12,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/MajstorKategorija')
            ),
            new OA\Response(
                response: 400,
                description: 'Nepostojeći ili neispravan ID majstora',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getMajstorKategorijaByKeys() {}

    #[OA\Post(
        path: '/admin/majstor-kategorije',
        tags: ['majstor-kategorije'],
        summary: 'Dodavanje veze majstor–kategorija',
        description: "**Svrha:** Dodaje jednu direktnu kategoriju (majstorluk) postojećem profilu majstora.\n\n" .
            "**Ulaz:** JSON s majstor_id i kategorija_id.\n\n" .
            "**Preduslovi:** Profil majstora i kategorija moraju postojati, a potpuno ista veza ne smije već " .
            "biti spremljena.\n\n" .
            "**Pravila:** Ne dodaju se automatski roditeljske ni potomci odabrane kategorije. Duplikat veze " .
            "ili neispravan/nepostojeći ID uzrokuje HTTP 400.\n\n" .
            "**Rezultat:** HTTP 201 i novi par ID-jeva. Majstor, korisnički račun i kategorija ostaju " .
            "nepromijenjeni.",
        requestBody: new OA\RequestBody(
            description: "JSON s ID-jevima majstora i kategorije za jednu novu direktnu vezu.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MajstorKategorijaInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kreirani zapis',
                content: new OA\JsonContent(ref: '#/components/schemas/MajstorKategorija')
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function postMajstorKategorije() {}

    #[OA\Put(
        path: '/admin/majstori/{majstor_id}/majstorluci',
        tags: ['majstor-kategorije'],
        summary: 'Zamjena svih direktnih majstorluka',
        description: "**Svrha:** Zamjenjuje KOMPLETAN izbor direktnih majstorluka jednog majstora.\n\n" .
            "**Ulaz:** ID profila majstora u URL-u i JSON s poljem kategorija_ids: niz ID-jeva svih " .
            "kategorija koje želimo zadržati.\n\n" .
            "**Preduslovi:** Profil i sve navedene kategorije moraju postojati; ID-jevi moraju biti " .
            "pozitivni cijeli brojevi. Ponavljanja istog ID-ja se uklanjaju.\n\n" .
            "**Pravila:** Veze kojih nema u novom nizu brišu se, nove veze se dodaju, a postojeće koje su i " .
            "dalje odabrane ostaju. Prazan niz [] uklanja SVE direktne veze tog majstora. Promjene se " .
            "izvršavaju u jednoj transakciji.\n\n" .
            "**Važno:** Ovo nije dodavanje samo jedne veze: obavezno poslati cijeli željeni izbor. Ne brišu " .
            "se majstor ni kategorije.\n\n" .
            "**Rezultat:** HTTP 200 i novi niz direktnih veza; HTTP 400 za neispravan izbor.",
        parameters: [
            new OA\Parameter(
                name: 'majstor_id',
                in: 'path',
                description: "ID profila majstora čiji kompletan izbor mijenjamo.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: "JSON s kompletnom listom kategorija_ids; [] uklanja sve veze odabranog majstora.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/IzborMajstorlukaInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/MajstorKategorija'))
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function putMajstorMajstorluci() {}

    #[OA\Delete(
        path: '/admin/majstor-kategorije/{majstor_id}/{kategorija_id}',
        tags: ['majstor-kategorije'],
        summary: 'Brisanje jedne veze majstor–kategorija',
        description: "**Svrha:** Briše samo jednu postojeću vezu majstor–kategorija (jedan majstorluk iz izbora).\n\n" .
            "**Ulaz:** U URL-u se šalju majstor_id i kategorija_id kao složeni ključ veze.\n\n" .
            "**Preduslov:** Veza mora postojati. Brisanje nepostojeće veze daje HTTP 404; nepostojeći " .
            "majstor HTTP 400.\n\n" .
            "**Posljedica:** Uklanja se samo odgovarajući red iz MAJSTOR_KATEGORIJE. Ne briše se ni profil " .
            "majstora, ni korisnički račun, ni sama kategorija. Ne primjenjuje se pravilo lista, jer se ne " .
            "briše kategorija nego veza.\n\n" .
            "**Rezultat:** HTTP 200 i potvrdna poruka.",
        parameters: [
            new OA\Parameter(
                name: 'majstor_id',
                in: 'path',
                description: "ID profila majstora, prvi dio složenog ključa.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'kategorija_id',
                in: 'path',
                description: "ID kategorije, drugi dio složenog ključa.",
                required: true,
                example: 12,
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
                description: 'Nepostojeći ili neispravan ID majstora',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function deleteMajstorKategorijaByKeys() {}

}
