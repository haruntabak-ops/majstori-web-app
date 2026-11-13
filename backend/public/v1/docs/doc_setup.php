<?php
use OpenApi\Attributes as OA;

/*
 * Opće informacije o MajstoriWebApp API-ju i zajedničke Swagger sheme.
 * Paths fajlovi koriste ove sheme preko OpenAPI referenci na njihove nazive.
 * Po uzoru na profesoricin Week 06: PHP atributi opisuju podatke, a klasa na kraju je prazna.
 */
#[OA\Info(
    title: 'MajstoriWebApp API',
    description: 'REST API za gradove, kategorije majstorluka, korisnike i profile majstora, pretragu i statistiku.',
    version: '1.0'
)]
#[OA\Server(url: API_BASE_URL, description: 'Trenutni server')]

/* Zajednički odgovori */
#[OA\Schema(
    schema: 'Error',
    properties: [
        new OA\Property(property: 'error', type: 'string',
            description: 'Poruka greške zavisi od rute i razloga greške.')
    ]
)]
#[OA\Schema(
    schema: 'Message',
    properties: [
        new OA\Property(property: 'message', type: 'string',
            description: 'Poruka uspjeha zavisi od izvršene operacije.')
    ]
)]

/* Gradovi */
#[OA\Schema(
    schema: 'GradInput',
    required: ['naziv', 'latitude', 'longitude'],
    properties: [
        new OA\Property(property: 'naziv', type: 'string', example: 'Sarajevo'),
        new OA\Property(property: 'latitude', type: 'number', minimum: -90, maximum: 90, example: 43.8563),
        new OA\Property(property: 'longitude', type: 'number', minimum: -180, maximum: 180, example: 18.4131)
    ]
)]
#[OA\Schema(
    schema: 'GradUpdateInput',
    description: 'Pri izmjeni se šalju samo polja koja se mijenjaju.',
    properties: [
        new OA\Property(property: 'naziv', type: 'string', example: 'Test grad Y'),
        new OA\Property(property: 'latitude', type: 'number', minimum: -90, maximum: 90, example: 6),
        new OA\Property(property: 'longitude', type: 'number', minimum: -180, maximum: 180, example: 7)
    ]
)]
#[OA\Schema(
    schema: 'Grad',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'naziv', type: 'string', example: 'Sarajevo'),
        // MySQL DECIMAL polja PDO obično vraća kao tekst sa šest decimalnih mjesta.
        new OA\Property(property: 'latitude', type: 'string', example: '43.856300'),
        new OA\Property(property: 'longitude', type: 'string', example: '18.413100')
    ]
)]

/* Kategorije */
#[OA\Schema(
    schema: 'KategorijaInput',
    required: ['naziv'],
    properties: [
        new OA\Property(property: 'naziv', type: 'string', example: 'Niska struja'),
        new OA\Property(property: 'roditelj_id', type: 'integer', nullable: true, example: 10,
            description: 'ID roditeljske kategorije; null označava korijensku kategoriju.')
    ]
)]
#[OA\Schema(
    schema: 'KategorijaUpdateInput',
    description: 'Pri izmjeni je dovoljno poslati samo naziv ili roditelj_id.',
    properties: [
        new OA\Property(property: 'naziv', type: 'string', example: 'Niska struja'),
        new OA\Property(property: 'roditelj_id', type: 'integer', nullable: true, example: 10,
            description: 'ID novog roditelja ili null za korijensku kategoriju.')
    ]
)]
#[OA\Schema(
    schema: 'Kategorija',
    allOf: [new OA\Schema(ref: '#/components/schemas/KategorijaInput')],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12)
    ]
)]

/* Korisnički računi (lozinka i njen hash nikada se ne prikazuju u odgovorima) */
#[OA\Schema(
    schema: 'KorisnikInput',
    required: ['email', 'lozinka'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'novi.majstor@primjer.ba'),
        new OA\Property(property: 'lozinka', type: 'string', format: 'password', example: 'PrimjerLozinka123',
            description: 'Obavezna pri kreiranju korisnika.'),
        new OA\Property(property: 'uloga', type: 'string', enum: ['admin', 'majstor'], example: 'majstor',
            description: 'Ako nije navedena pri kreiranju, podrazumijeva se majstor.')
    ]
)]
#[OA\Schema(
    schema: 'KorisnikUpdateInput',
    description: 'Pri izmjeni je dovoljno poslati samo polja koja se mijenjaju; lozinka je opcionalna.',
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'novi.majstor@primjer.ba'),
        new OA\Property(property: 'lozinka', type: 'string', format: 'password', example: 'NovaLozinka123',
            description: 'Ako nije navedena, postojeća lozinka ostaje nepromijenjena.'),
        new OA\Property(property: 'uloga', type: 'string', enum: ['admin', 'majstor'], example: 'majstor')
    ]
)]
#[OA\Schema(
    schema: 'Korisnik',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'amir.elektrovic@primjer.ba'),
        new OA\Property(property: 'uloga', type: 'string', enum: ['admin', 'majstor'], example: 'majstor')
    ]
)]

/* Profili majstora */
#[OA\Schema(
    schema: 'MajstorInput',
    required: ['korisnik_id', 'grad_id', 'ime', 'prezime', 'telefon', 'opis'],
    properties: [
        new OA\Property(property: 'korisnik_id', type: 'integer', example: 2),
        new OA\Property(property: 'grad_id', type: 'integer', example: 1),
        new OA\Property(property: 'ime', type: 'string', example: 'Amir'),
        new OA\Property(property: 'prezime', type: 'string', example: 'Elektrović'),
        new OA\Property(property: 'telefon', type: 'string', example: '061 000 101'),
        new OA\Property(property: 'opis', type: 'string', example: 'Bavim se ugradnjom IP kamera i mrežnim instalacijama.')
    ]
)]
#[OA\Schema(
    schema: 'MajstorUpdateInput',
    properties: [
        new OA\Property(property: 'grad_id', type: 'integer', example: 1),
        new OA\Property(property: 'ime', type: 'string', example: 'Amir'),
        new OA\Property(property: 'prezime', type: 'string', example: 'Elektrović'),
        new OA\Property(property: 'telefon', type: 'string', example: '061 000 101'),
        new OA\Property(property: 'opis', type: 'string', example: 'Ugradnja i održavanje video-nadzora.')
    ]
)]
#[OA\Schema(
    schema: 'Majstor',
    allOf: [new OA\Schema(ref: '#/components/schemas/MajstorUpdateInput')],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'korisnik_id', type: 'integer', example: 2),
        new OA\Property(property: 'grad_naziv', type: 'string', example: 'Sarajevo'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'amir.elektrovic@primjer.ba')
    ]
)]
#[OA\Schema(
    schema: 'MajstorlukNaziv',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 27),
        new OA\Property(property: 'naziv', type: 'string', example: 'IP kamere')
    ]
)]
#[OA\Schema(
    schema: 'MajstorProfil',
    allOf: [new OA\Schema(ref: '#/components/schemas/Majstor')],
    properties: [
        new OA\Property(property: 'kategorija_ids', type: 'array',
            items: new OA\Items(type: 'integer'), example: [20, 27]),
        new OA\Property(property: 'majstorluci', type: 'array',
            items: new OA\Items(ref: '#/components/schemas/MajstorlukNaziv'))
    ]
)]
#[OA\Schema(
    schema: 'KorisnikSaProfilom',
    allOf: [new OA\Schema(ref: '#/components/schemas/Korisnik')],
    properties: [
        new OA\Property(property: 'profil', nullable: true, ref: '#/components/schemas/Majstor',
            description: 'Profil majstora ili null ako korisnik nema profil.')
    ]
)]

/* Direktne veze majstor–kategorija */
#[OA\Schema(
    schema: 'MajstorKategorijaInput',
    required: ['majstor_id', 'kategorija_id'],
    properties: [
        new OA\Property(property: 'majstor_id', type: 'integer', example: 1),
        new OA\Property(property: 'kategorija_id', type: 'integer', example: 27)
    ]
)]
#[OA\Schema(
    schema: 'MajstorKategorija',
    allOf: [new OA\Schema(ref: '#/components/schemas/MajstorKategorijaInput')]
)]
#[OA\Schema(
    schema: 'IzborMajstorlukaInput',
    required: ['kategorija_ids'],
    properties: [
        new OA\Property(property: 'kategorija_ids', type: 'array',
            description: 'Novi kompletan izbor direktnih kategorija; prazna lista uklanja sve veze.',
            items: new OA\Items(type: 'integer'), example: [20, 27])
    ]
)]

/* Pretraga i statistika */
#[OA\Schema(
    schema: 'MajstorPretraga',
    allOf: [new OA\Schema(ref: '#/components/schemas/Majstor')],
    properties: [
        new OA\Property(property: 'majstorluci_nazivi', type: 'string', nullable: true,
            example: 'IP kamere, Mrežne instalacije'),
        new OA\Property(property: 'udaljenost_km', type: 'number', nullable: true, example: 78.5,
            description: 'Zračna udaljenost u km; null ako nije zatražena pretraga po radijusu.')
    ]
)]
#[OA\Schema(
    schema: 'PretragaRezultat',
    properties: [
        new OA\Property(property: 'majstori', type: 'array',
            items: new OA\Items(ref: '#/components/schemas/MajstorPretraga')),
        new OA\Property(property: 'count', type: 'integer', example: 3)
    ]
)]
#[OA\Schema(
    schema: 'Statistika',
    properties: [
        new OA\Property(property: 'majstori', type: 'integer', example: 12),
        new OA\Property(property: 'kategorije', type: 'integer', example: 19),
        new OA\Property(property: 'gradovi', type: 'integer', example: 5)
    ]
)]

/* Rezultat vanjskog Nominatim API-ja */
#[OA\Schema(
    schema: 'NominatimLokacija',
    properties: [
        new OA\Property(property: 'naziv', type: 'string', example: 'Sarajevo'),
        new OA\Property(property: 'display_name', type: 'string', example: 'Sarajevo, Bosna i Hercegovina'),
        new OA\Property(property: 'latitude', type: 'number', example: 43.857071),
        new OA\Property(property: 'longitude', type: 'number', example: 18.412615)
    ]
)]
class OpenApiSpec {}
