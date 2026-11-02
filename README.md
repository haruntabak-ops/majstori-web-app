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

Dodatne informacije o MySQL bazi, njenim SQL skriptama i testnim
upitima nalaze se u `database/README_baza.md`.

Dokument `docs/FRONTEND_API_PLAN.md` predstavlja plan povezivanja
funkcionalnosti frontend stranica s budućim REST rutama, servisima i
postojećim DAO metodama, organizovan po korisničkim ulogama.
Tokom rada na M2 korišten za provjeru kompletnosti DAO klasa i metoda.

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

## Status projekta i Milestone 2

Za Milestone 2 kreirana je MySQL baza podataka sa početnim testnim
podacima i implementiran DAO (Data Access Object) sloj u PHP-u pomoću
PDO-a. Baza sadrži pet povezanih tabela: `KORISNICI`, `GRADOVI`,
`MAJSTORI`, `KATEGORIJE` i `MAJSTOR_KATEGORIJE`.

DAO sloj implementiran je po uzoru na pokaznu aplikaciju **Campus Events App**.
Zajednička klasa `BaseDao` prilagođena je potrebama projekta, dopunjena
komentarima i zaštitom od neodgovarajućih generičkih CRUD operacija nad
tabelom `MAJSTOR_KATEGORIJE`, koja koristi složeni primarni ključ
(`majstor_id`, `kategorija_id`) umjesto standardne kolone `id`.
Za svaku tabelu implementirana je posebna DAO klasa s potrebnim namjenskim
metodama.

U `MajstorDao` implementirana je i metoda `pretrazi()` za pretragu
majstora po jednoj ili više kategorija, opcionalnom gradu i radijusu
izraženom u kilometrima (zračna udaljenost). Rekurzivni SQL upit
pronalazi tražene kategorije i sve njihove potomke. Majstor ulazi u
rezultate samo ako je **eksplicitno odabrao** traženu kategoriju ili
nekog njenog potomka; odabir samo nadređene kategorije nije dovoljan.

Trenutni backend obuhvata sljedeće glavne fajlove i direktorije:

-   `backend/index.php` --- ulazna tačka backend aplikacije.
-   `backend/composer.json` --- PHP zavisnosti projekta.
-   `backend/config/` --- konfiguracija backenda i pristupa bazi.
-   `backend/rest/dao/BaseDao.php` --- zajednička DAO klasa.
-   `backend/rest/dao/KorisnikDao.php` --- pristup korisničkim nalozima.
-   `backend/rest/dao/GradDao.php` --- pristup gradovima.
-   `backend/rest/dao/MajstorDao.php` --- pristup profilima majstora i pretraga.
-   `backend/rest/dao/KategorijaDao.php` --- pristup hijerarhiji kategorija.
-   `backend/rest/dao/MajstorKategorijaDao.php` --- veze između majstora i njihovih majstorluka.
-   `backend/rest/dao/test.php` --- provjera DAO metoda i CRUD operacija.
-   `backend/rest/dao/test_php_rezultat.txt` --- sačuvani ispis izvršenog testa.
-   `backend/rest/services/` i `backend/rest/routes/` --- direktoriji predviđeni
    za servisni sloj i REST rute u narednim milestoneima.

Glavna skripta za kreiranje baze i unos početnih podataka je
`database/majstori_web_app.sql`. Više informacija o bazi i pomoćnim
SQL upitima nalazi se u `database/README_baza.md`.

DAO sloj testiran je pomoću `backend/rest/dao/test.php`. Test obuhvata
čitanje podataka, CRUD operacije nad novim privremenim zapisima u svih
pet tabela i tri scenarija pretrage: **7a** (dvije kategorije), **7b**
(roditeljska kategorija) i **7c** (kategorija, grad Tuzla i radijus
100 km). Rezultati sva tri scenarija odgovarali su očekivanim, kao i
ispisane CRUD operacije. Test koristi transakciju i završni `ROLLBACK`
kako testni upisi ne bi ostali trajno sačuvani.

Test se pokreće iz korijena projekta naredbom:

```bash
php backend/rest/dao/test.php
```

Ispis uspješnog testiranja sačuvan je u
`backend/rest/dao/test_php_rezultat.txt`.

U narednim milestoneima planirano je implementirati servisni sloj i
REST rute, a zatim povezati postojeći frontend s backendom i bazom
podataka. Plan po ulogama, stranicama i funkcionalnostima nalazi se u
`docs/FRONTEND_API_PLAN.md`.

## Autor

Harun Tabak · IBU · 2026
