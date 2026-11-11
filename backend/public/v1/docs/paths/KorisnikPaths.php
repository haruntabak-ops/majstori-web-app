<?php
use OpenApi\Attributes as OA;

/*
 * OpenAPI documentation of rest/routes/KorisnikRoutes.php.
 * swagger-php reads the #[OA\...] attributes; the methods are empty – they only carry the documentation.
 * Keep it in sync with the routes: one method per endpoint.
 */

/*
 * Prethodno navedeni princip preuzet je iz campus-events-app (week-06)
 * i prilagođen potrebama aplikacije MajstoriWebApp.
 */
class KorisnikPaths
{
    #[OA\Get(
        path: '/admin/korisnici',
        tags: ['korisnici'],
        summary: 'Dohvat korisnika i njihovih profila',
        description: "**Svrha:** Vraća listu korisničkih računa, a uz svaki račun i njegov profil majstora ako " .
            "postoji.\n\n" .
            "**Podaci:** Račun sadrži ID, e-mail i ulogu (admin ili majstor). Polje profil je null ako " .
            "profil nije kreiran; za postojeći profil vraćaju se podaci iz tabele MAJSTORI.\n\n" .
            "**Sigurnost:** Lozinka i lozinka_hash se ne vraćaju u JSON odgovoru.\n\n" .
            "**Rezultat:** HTTP 200 i niz korisnika s profilima. Ruta je namijenjena administraciji; u M3 " .
            "još nema provjere prijave.",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Niz rezultata',
                content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/KorisnikSaProfilom'))
            )
        ]
    )]
    public function getKorisnici() {}

    #[OA\Get(
        path: '/admin/korisnici/{id}',
        tags: ['korisnici'],
        summary: 'Dohvat korisnika i njegovog profila',
        description: "**Svrha:** Vraća jedan korisnički račun i njegov eventualni profil majstora.\n\n" .
            "**Ulaz:** ID korisnika u URL-u; to nije isto što i ID profila majstora.\n\n" .
            "**Podaci:** Profil može biti null, naročito za administratora ili majstora koji još nije " .
            "napravio profil. Lozinka i njen hash se ne vraćaju.\n\n" .
            "**Rezultat:** HTTP 200 i korisnik s poljem profil; HTTP 404 ako korisnik ne postoji.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID korisničkog računa, ne ID profila majstora.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/KorisnikSaProfilom')
            ),
            new OA\Response(
                response: 404,
                description: 'Zapis nije pronađen',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function getKorisnikById() {}

    #[OA\Post(
        path: '/admin/korisnici',
        tags: ['korisnici'],
        summary: 'Kreiranje korisničkog računa',
        description: "**Svrha:** Kreira novi korisnički račun (bez automatskog kreiranja profila majstora).\n\n" .
            "**Ulaz:** Obavezni su email i lozinka. Uloga je admin ili majstor; ako izostane, dodjeljuje se " .
            "majstor.\n\n" .
            "**Pravila:** E-mail se normalizuje i mora biti jedinstven. Lozinka se validira i u bazi čuva " .
            "samo kao bcrypt hash (nikada kao običan tekst).\n\n" .
            "**Rezultat:** HTTP 201 i novi račun bez lozinke ili hasha; HTTP 400 za neispravne podatke ili " .
            "postojeći e-mail.\n\n" .
            "**Pristup:** Administratorska ruta; autentikacija i autorizacija još nisu uključene u M3.",
        requestBody: new OA\RequestBody(
            description: "JSON s e-mailom, lozinkom i opcionalnom ulogom.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/KorisnikInput')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kreirani zapis',
                content: new OA\JsonContent(ref: '#/components/schemas/Korisnik')
            ),
            new OA\Response(
                response: 400,
                description: 'Neispravni podaci',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            )
        ]
    )]
    public function postKorisnici() {}

    #[OA\Put(
        path: '/admin/korisnici/{id}',
        tags: ['korisnici'],
        summary: 'Izmjena korisničkog računa',
        description: "**Svrha:** Mijenja e-mail, ulogu i/ili lozinku postojećeg korisničkog računa.\n\n" .
            "**Ulaz:** ID korisničkog računa i JSON samo s poljima koja mijenjamo; lozinka nije obavezna.\n\n" .
            "**Pravila:** E-mail mora biti ispravan i jedinstven, a uloga admin ili majstor. Ako lozinka " .
            "izostane ili je prazna, stari hash ostaje; ako je unesena nova, servis je validira i pohranjuje " .
            "njen bcrypt hash.\n\n" .
            "**Važno:** Ovo je administratorska izmjena računa; nije posebna ruta za promjenu vlastite " .
            "lozinke provjerom stare lozinke.\n\n" .
            "**Rezultat:** HTTP 200 i ažurirani račun bez hasha; HTTP 404 ako račun ne postoji; HTTP 400 za " .
            "neispravan unos.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID korisničkog računa, ne ID profila majstora.",
                required: true,
                example: 1,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        requestBody: new OA\RequestBody(
            description: "JSON s e-mailom, ulogom i/ili novom lozinkom; nije potrebno slati sva polja.",
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/KorisnikUpdateInput')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Odgovor',
                content: new OA\JsonContent(ref: '#/components/schemas/Korisnik')
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
    public function putKorisnikById() {}

    #[OA\Delete(
        path: '/admin/korisnici/{id}',
        tags: ['korisnici'],
        summary: 'Brisanje korisničkog računa',
        description: "**Svrha:** Briše korisnički račun.\n\n" .
            "**Preduslov:** Račun s navedenim ID-jem mora postojati.\n\n" .
            "**Posljedica:** Prema pravilima naše MySQL baze (ON DELETE CASCADE), uklanja se i njegov profil " .
            "majstora, ako postoji, te pripadajuće veze majstor–kategorija. To ne briše gradove ni same " .
            "kategorije.\n\n" .
            "**Oprez:** Za razliku od brisanja profila majstora, ovdje se uklanja i korisnički račun.\n\n" .
            "**Rezultat:** HTTP 200 i poruka; HTTP 404 ako račun ne postoji.",
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                description: "ID korisničkog računa, ne ID profila majstora.",
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
        ]
    )]
    public function deleteKorisnikById() {}

}
