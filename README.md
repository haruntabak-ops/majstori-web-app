# Majstori Web App

## Opis projekta

Majstori Web App je web aplikacija za pronalaženje majstora u Bosni i
Hercegovini. Posjetioci mogu pretraživati majstore prema hijerarhijski
organizovanim kategorijama usluga i gradu, uz mogućnost da opcionalno
ograniče rezultate prema udaljenosti. Rezultati pretrage zavise od
odabranih kategorija i lokacije, a posjetioci mogu pregledati kontaktne
podatke majstora.

Majstori mogu uređivati vlastiti profil i odabrati kategorije
majstorluka koje nude. Administrator upravlja korisničkim računima,
kategorijama majstorluka i listom gradova. Administrator nema vlastiti
profil majstora. Može pregledati određene podatke profila majstora, ali
taj prikaz služi samo za čitanje: administrator kroz njega ne uređuje
profil niti mijenja odabrane majstorluke.

## Dokumentacija projekta

**Detaljniji opis funkcionalnosti aplikacije i do sada identifikovanih
poslovnih pravila nalazi se u Excel dokumentu
`docs/Majstori_Web_App_dokumentacija.xlsx`.**

Dokument sadrži tri sheeta: `Uvod`, `Opis_aplikacije` i
`Poslovna_pravila`. Sheet `Opis_aplikacije` detaljnije opisuje
funkcionalnosti i ponašanje aplikacije, dok `Poslovna_pravila` sadrži do
sada definisana pravila i ograničenja sistema.

Opis strukture baze podataka, ERD dijagram i veze između entiteta nalaze
se u `docs/ERD.md`, a slikovni prikaz dijagrama u `docs/ERD_slika.jpg`.

Folder `radna-dokumentacija/` služi kao pomoćni radni prostor za čuvanje
ličnih ideja, bilješki i prijedloga koji nastaju tokom razvoja projekta.
On je namijenjen organizaciji radnih materijala i nije zamjena za
zvaničnu dokumentaciju projekta.

README pruža kratak pregled projekta, dok navedeni dokumenti
predstavljaju detaljniju referencu za funkcionalnosti, poslovna pravila,
plan baze podataka i radne bilješke.

## Tehnologije

### Trenutni frontend

-   HTML
-   CSS
-   JavaScript
-   jQuery
-   AJAX za učitavanje sadržaja i komunikaciju u frontend demonstraciji

### Planirani backend i povezivanje

-   PHP i FlightPHP
-   MySQL baza podataka
-   PDO za pristup bazi
-   REST API
-   JWT autentifikacija

## Vanjski API -- trenutna demonstracija

Poziv vanjskog API-ja već je vidljiv u frontend demonstraciji na
administratorskom ekranu **„Gradovi"**, kada administrator dodaje novi
grad. Administrator unese ili potraži naziv grada, a aplikacija šalje
zahtjev Nominatim servisu za pronalaženje odgovarajuće lokacije.

Na osnovu odgovora prikazuju se geografske koordinate i mapa preko
OpenStreetMap-a. Administrator zatim vizuelno provjerava da li prikazana
lokacija odgovara traženom gradu prije nego što potvrdi unos.

Ova demonstracija pokazuje korištenje vanjskog API-ja iz frontenda.
Povezivanje s backendom i trajno čuvanje grada i koordinata u bazi
podataka planirano je za naredne milestone-e.

## Korisničke uloge

-   **Posjetilac:** pretražuje majstore prema kategorijama, gradu i
    opcionalnoj udaljenosti te pregledava njihove kontaktne podatke.
-   **Majstor:** prijavljuje se pomoću dodijeljenih pristupnih podataka,
    uređuje vlastiti profil i bira kategorije majstorluka koje nudi.
-   **Administrator:** kreira i upravlja korisničkim računima, upravlja
    hijerarhijom kategorija i gradovima te može pregledati određene
    podatke profila majstora u režimu samo za čitanje. Administrator
    nema vlastiti profil majstora.

Samostalna javna registracija majstora nije predviđena u trenutnoj
verziji aplikacije; račune majstora kreira administrator.

## Status projekta i Milestone 1

Za Milestone 1 implementirana je interaktivna demonstracijska verzija
frontenda sa svim predviđenim ekranima. U ovoj fazi podaci za prikaz i
demonstraciju funkcionalnosti definisani su unutar JavaScript fajlova,
pa se izmjene i rad s podacima trenutno odvijaju u okviru frontenda.

U narednim milestoneima planirano je povezivanje frontenda s backendom i
bazom podataka. Tada će se podaci učitavati i mijenjati putem API-ja
umjesto da budu definisani kao demonstracijski podaci unutar JavaScript
fajlova.

Trenutni frontend obuhvata sljedeće glavne fajlove i direktorije:

-   `index.html` --- početna HTML stranica aplikacije.
-   `css/style.css` --- stilovi i responzivni izgled.
-   `js/app.js` --- SPA navigacija, ponašanje ekrana i demonstracijski
    podaci.
-   `views/` --- HTML prikazi pojedinačnih ekrana aplikacije.
-   `assets/` --- statički resursi aplikacije, uključujući slike.

Struktura backenda prati organizaciju iz pokazne aplikacije s predmeta,
prilagođenu potrebama Majstori Web App. Backend će se razvijati u
narednim milestoneima.

## Autor

Harun Tabak · IBU · 2026
