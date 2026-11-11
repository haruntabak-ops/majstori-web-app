<?php
use OpenApi\Attributes as OA;

/*
 * OpenAPI documentation of rest/routes/MajstorRoutes.php.
 * swagger-php reads the #[OA\...] attributes; the methods are empty – they only carry the documentation.
 * Keep it in sync with the routes: one method per endpoint.
 */

/*
 * Prethodno navedeni princip preuzet je iz campus-events-app (week-06)
 * i prilagođen potrebama aplikacije MajstoriWebApp.
 */
class MajstorPaths
{
    #[OA\Get(
        path: '/majstori/{id}',
        tags: ['majstori'],
        summary: 'Dohvat javnog profila majstora',
        description: "**Svrha:** Vraća javni profil jednog majstora na osnovu ID-a profila.\n\n" .
            "**Podaci:** Uz ime, prezime, telefon, opis, grad i e-mail uključuje kategorija_ids (ID-jevi " .
            "direktno odabranih kategorija) i majstorluci (ID i naziv svake direktno odabrane kategorije).\n\n" .
            "**Važno:** ID profila majstora nije isto što i korisnik_id njegovog korisničkog računa.\n\n" .
            "**Rezultat:** HTTP 200 i profil s majstorlucima; HTTP 404 ako profil ne postoji.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID profila majstora iz tabele MAJSTORI.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/MajstorProfil')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getMajstorById() {}

    #[OA\Get(
        path: '/admin/majstori',
        tags: ['majstori'],
        summary: 'Dohvat svih profila majstora',
        description: "**Svrha:** Dohvata listu svih profila majstora za administrativni pregled.\n\n" .
            "**Podaci:** Za svaki profil uključeni su ID profila, korisnik_id, grad_id, ime, prezime, " .
            "telefon, opis, naziv grada i e-mail. Ova ruta ne proširuje svaki profil listom direktnih " .
            "majstorluka.\n\n" .
            "**Rezultat:** HTTP 200 i niz profila. Prijava i uloge na /admin rutama još nisu uvedene u M3.",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Majstor'))
            )
        ]
    )]
    public function getAdminMajstori() {}

    #[OA\Post(
        path: '/admin/majstori',
        tags: ['majstori'],
        summary: 'Kreiranje profila majstora',
        description: "**Svrha:** Kreira profil majstora za već postojeći korisnički račun.\n\n" .
            "**Ulaz:** Obavezni su korisnik_id, grad_id, ime, prezime, telefon i opis.\n\n" .
            "**Preduslovi:** Korisnik mora postojati, imati ulogu majstor i ne smije već imati profil; " .
            "grad_id mora označavati postojeći grad.\n\n" .
            "**Pravila:** Provjeravaju se dužine tekstualnih polja i ID-jevi. Jedan korisnik može imati " .
            "najviše jedan profil. Odabir kategorija (majstorluka) obavlja se zasebnim rutama.\n\n" .
            "**Rezultat:** HTTP 201 i puni profil s praznim listama majstorluka ako veze još nisu dodane; " .
            "HTTP 400 pri kršenju pravila.",
        requestBody: new OA\RequestBody(
            description: "JSON s podacima profila za već postojeći korisnički račun uloge majstor.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MajstorInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kreirani zapis',
                content: new OA\JsonContent(ref: '#/components/schemas/MajstorProfil')
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function postMajstori() {}

    #[OA\Put(
        path: '/admin/majstori/{id}',
        tags: ['majstori'],
        summary: 'Izmjena profila majstora',
        description: "**Svrha:** Mijenja postojeći profil majstora.\n\n" .
            "**Ulaz:** ID PROFILA majstora u URL-u i JSON s promjenama grad_id, ime, prezime, telefon i/ili " .
            "opis.\n\n" .
            "**Pravila:** Grad mora postojati, a tekstualna polja moraju biti valjana. Korisnik_id (vlasnik " .
            "profila) ne može se promijeniti. Kategorije majstorluka se ne mijenjaju ovom rutom.\n\n" .
            "**Rezultat:** HTTP 200 i ažurirani profil s majstorlucima; HTTP 400 za neispravne podatke; HTTP " .
            "404 ako profil ne postoji.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID profila majstora iz tabele MAJSTORI, ne korisnik_id.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: "JSON s promjenama profila; vlasnik (korisnik_id) se ne može mijenjati.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MajstorUpdateInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/MajstorProfil')
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
    public function putMajstorById() {}

    #[OA\Delete(
        path: '/admin/majstori/{id}',
        tags: ['majstori'],
        summary: 'Brisanje profila majstora',
        description: "**Svrha:** Briše postojeći profil majstora.\n\n" .
            "**Preduslov:** Profil s navedenim ID-jem mora postojati.\n\n" .
            "**Posljedica:** Briše se red iz MAJSTORI i pripadajuće direktne veze u MAJSTOR_KATEGORIJE (ON " .
            "DELETE CASCADE). Korisnički račun, gradovi i kategorije ostaju sačuvani.\n\n" .
            "**Važno:** Za razliku od DELETE /admin/korisnici/{id}, ovom operacijom NE briše se korisnički " .
            "račun.\n\n" .
            "**Rezultat:** HTTP 200 i poruka; HTTP 404 ako profil ne postoji.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID profila majstora iz tabele MAJSTORI, ne korisnik_id.",
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
            )
        ]
    )]
    public function deleteMajstorById() {}

}
