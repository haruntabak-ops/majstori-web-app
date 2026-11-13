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
funkcionalnosti frontend stranica s REST rutama, servisima i postojećim
DAO metodama, organizovan po korisničkim ulogama. Tokom M2 korišten je
za provjeru kompletnosti DAO sloja, a u M3 kao osnova za planiranje
REST API-ja. Povezivanje postojećeg frontenda s backendom slijedi u
narednoj fazi.

Primjeri ručnog testiranja REST ruta prije Swaggera dokumentovani su u
[`backend/rest/routes/README_testiranje_ruta.md`](backend/rest/routes/README_testiranje_ruta.md).
Za M3 postoje i PHP testovi servisnog sloja s pripadajućim TXT rezultatima
u `backend/rest/services/`. Swagger/OpenAPI dokumentacija (v1) nalazi se u
`backend/public/v1/docs/`.

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

### Backend (M2 i M3)

-   PHP i FlightPHP
-   MySQL baza podataka
-   PDO za pristup bazi
-   REST API
-   Composer za upravljanje PHP bibliotekama (FlightPHP i swagger-php)
-   Swagger/OpenAPI za dokumentovanje i testiranje REST API-ja

### Planirano povezivanje

-   Povezivanje postojećeg frontenda s REST API-jem
-   JWT autentifikacija i kontrola pristupa prema korisničkim ulogama

## Vanjski API -- trenutna demonstracija

Poziv vanjskog API-ja već je vidljiv u frontend demonstraciji na
administratorskom ekranu **„Gradovi"**, kada administrator dodaje novi
grad. Administrator unese ili potraži naziv grada, a aplikacija šalje
zahtjev Nominatim servisu za pronalaženje odgovarajuće lokacije.

Na osnovu odgovora prikazuju se geografske koordinate i mapa preko
OpenStreetMap-a. Administrator zatim vizuelno provjerava da li prikazana
lokacija odgovara traženom gradu prije nego što potvrdi unos.

Ova demonstracija pokazuje korištenje vanjskog API-ja iz frontenda.
U M3 implementiran je i backend poziv prema Nominatimu kroz
`NominatimService.php` i REST rutu za dohvat koordinata. Dodavanje
grada u bazu moguće je kroz REST API, dok povezivanje postojećeg
frontend prikaza s tim rutama ostaje za narednu fazu.

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

## Status projekta i Milestone 3

Za Milestone 3 implementiran je **servisni sloj (Business Logic Layer)**
i **REST API** pomoću FlightPHP-a, oslonjeni na DAO sloj iz M2.

### Instalacija Composer zavisnosti

Postojeći `backend/composer.json` dopunjen je zavisnostima za biblioteke
FlightPHP i swagger-php. Composer je prilikom instalacije automatski kreirao
`backend/composer.lock`, koji bilježi tačne verzije instaliranih biblioteka
i njihovih zavisnosti.

Za instalaciju potrebnih PHP biblioteka na drugom računaru potrebno je
otvoriti terminal u folderu `backend/` i pokrenuti:

```bash
composer install
```

Composer na osnovu `composer.json` i `composer.lock` instalira potrebne
biblioteke u folder `backend/vendor/`, koji nije uključen u Git repozitorij.


### Servisni sloj (Business Logic)

Servisni sloj nalazi se u `backend/rest/services/` i implementiran je po
uzoru na pokazni projekat Campus Events App (Week 06). Servisi obrađuju
poslovnu logiku i, gdje je potrebno, koriste odgovarajuće DAO metode.

Implementacija servisnog sloja obuhvata **9 servisnih klasa** u
`backend/rest/services/` i koristi **3 pomoćna PHP fajla** u `backend/utils/`.

Servisni sloj obuhvata sljedeće klase:
- `BaseService.php` — zajednička klasa s osnovnim CRUD operacijama.
- `GradService.php` — upravljanje gradovima i provjera poslovnih pravila.
- `KategorijaService.php` — upravljanje kategorijama i njihovom hijerarhijom.
- `KorisnikService.php` — upravljanje korisničkim računima.
- `MajstorService.php` — upravljanje profilima majstora.
- `MajstorKategorijaService.php` — upravljanje vezama između majstora i kategorija majstorluka.
- `PretragaService.php` — pretraga majstora prema kategorijama, gradu i radijusu.
- `StatistikaService.php` — dohvat statističkih podataka.
- `NominatimService.php` — pronalaženje geografskih koordinata gradova putem vanjskog Nominatim API-ja.

U servisnom sloju implementirane su različite provjere poslovnih pravila.
Naprimjer, nije dozvoljeno brisanje grada koji koriste majstori, brisanje
kategorije koja ima potkategorije niti stvaranje kružnih veza pri izmjeni
hijerarhije kategorija (npr. postavljanje kategorije kao potkategorije
vlastitog potomka).

Zbog složenog primarnog ključa (`majstor_id`, `kategorija_id`) u tabeli
`MAJSTOR_KATEGORIJE`, `MajstorKategorijaService` ne nasljeđuje `BaseService`,
nego koristi posebne metode za upravljanje vezama i transakcijsku zamjenu
izbora majstorluka.

Pomoćni fajlovi nalaze se u `backend/utils/`. `Validator.php` preuzet je
bez izmjena iz pokaznog projekta Campus Events App (Week 06), `Roles.php`
prilagođen je ulogama `admin` i `majstor`, dok je `exceptions.php`
dopunjen izuzetkom `ConflictException` (HTTP 409) za slučajeve sukoba
s poslovnim pravilima, npr. pokušaj brisanja grada koji koriste majstori.

Servisni sloj testiran je pomoću:

-   `backend/rest/services/test_1_dohvat.php` — dohvat i pretraga;
    rezultat u `test_1_dohvat_php_rezultati.txt` (**34/34 provjere**).
-   `backend/rest/services/test_2_crud.php` — CRUD operacije i poslovna
    pravila; rezultat u `test_2_crud_php_rezultati.txt` (**36/36 provjera**).
-   `backend/rest/services/test_3_vanjski_api.php` — poziv Nominatima;
    rezultat u `test_3_vanjski_api_php_rezultati.txt` (uspješan dohvat
    koordinata Sarajeva).

CRUD testove potrebno je pokretati na razvojnoj/testnoj bazi podataka,
jer tokom izvršavanja mijenjaju podatke.

Testni PHP fajlovi pokreću se direktno, npr. iz glavnog foldera projekta:

```bash
php backend/rest/services/test_1_dohvat.php
```

### REST rute (Presentation Layer)

REST rute organizovane su u **8 PHP fajlova** u `backend/rest/routes/`
i obuhvataju ukupno **32 endpointa**:

- `GradRoutes.php` — REST rute za dohvat i upravljanje gradovima.
- `KategorijaRoutes.php` — REST rute za dohvat i upravljanje kategorijama.
- `KorisnikRoutes.php` — REST rute za upravljanje korisničkim računima.
- `MajstorRoutes.php` — REST rute za dohvat i upravljanje profilima majstora.
- `MajstorKategorijaRoutes.php` — REST rute za upravljanje vezama između majstora i kategorija majstorluka.
- `PretragaRoutes.php` — REST rute za pretragu majstora prema kategorijama, gradu i radijusu.
- `StatistikaRoutes.php` — REST rute za dohvat statističkih podataka.
- `NominatimRoutes.php` — REST ruta za pronalaženje koordinata gradova putem Nominatim API-ja.

Fajl `backend/index.php` učitava potrebne klase, registruje servise i rute
u FlightPHP-u i obrađuje HTTP zahtjeve i greške.

Dio REST ruta dodatno je ručno provjeren kroz browser i PowerShell,
uključujući pretrage **7a, 7b i 7c** te dodavanje i brisanje probnog grada.
Komande i URL-ovi zapisani su u
[`backend/rest/routes/README_testiranje_ruta.md`](backend/rest/routes/README_testiranje_ruta.md).

Za lokalno pokretanje i testiranje REST ruta koristi se:
```bash
php -S localhost:8000 server.php
```

Pri tome `server.php` iz glavnog foldera projekta usmjerava zahtjeve prema glavnom fajlu backenda
(`backend/index.php`), koji ih dalje obrađuje pomoću FlightPHP-a.

### Swagger/OpenAPI dokumentacija (OpenAPI Documentation)

Swagger/OpenAPI dokumentacija (v1) napravljena je po uzoru na pokaznu
aplikaciju **Campus Events App**. Nalazi se u `backend/public/v1/docs/`:
`index.html` prikazuje Swagger UI, `swagger.php` generiše OpenAPI JSON,
`doc_setup.php` definiše sheme, a `paths/` sadrži opise REST ruta i
njihovih poslovnih pravila. Dokumentacija i odabrane rute provjerene su
kroz Swagger interfejs:

-   Swagger UI: `http://localhost:8000/backend/public/v1/docs/`
-   OpenAPI JSON: `http://localhost:8000/backend/public/v1/docs/swagger.php`

REST API je implementiran i testiran u lokalnom razvojnom okruženju.
Povezivanje postojećeg frontenda s njim, JWT autentifikacija i zaštita
administratorskih ruta ostaju za narednu fazu. Do tada administratorske
rute treba koristiti samo lokalno, na razvojnoj/testnoj bazi.

### Vanjski Nominatim API (External API) i napomena za pokretanje na drugom računaru
 
Prema ranije zamišljenoj funkcionalnosti aplikacije, administrator će prilikom
dodavanja grada moći provjeriti pronađenu lokaciju na mapi i potvrditi
da odgovara traženom gradu. Tek nakon potvrde grad će se dodavati u
bazu putem posebne REST rute. Nominatim ruta sama ne upisuje podatke
u bazu.

Za pronalazak koordinata grada implementiran je poziv vanjskom
**Nominatim API-ju** iz `NominatimService.php`. Njegove postavke
(`NOMINATIM_URL`, `NOMINATIM_TIMEOUT`, `NOMINATIM_USER_AGENT`) dodane su u
konfiguraciju backenda (`backend/config/config.example.php` i lokalni `backend/config/config.php`).

Za pristup Nominatim API-ju implementirana je REST ruta
`GET /lokacije/pretraga` u `backend/rest/routes/NominatimRoutes.php`.
Ruta prima naziv grada putem parametra `naziv`, poziva `NominatimService`
i vraća pronađene koordinate u JSON formatu.

Integracija s Nominatim API-jem testirana je pomoću
`backend/rest/services/test_3_vanjski_api.php`. Test je uspješno
dohvatio geografske koordinate Sarajeva, a rezultat je sačuvan u
`backend/rest/services/test_3_vanjski_api_php_rezultati.txt`.

Za HTTPS komunikaciju s Nominatim API-jem PHP mora imati pristup pouzdanim CA certifikatima.
Na razvojnom računaru problem s provjerom SSL certifikata riješen je
preuzimanjem datoteke `cacert.pem` sa zvanične stranice
https://curl.se/ca/cacert.pem i njenim smještanjem u folder u kojem je
instaliran PHP (na razvojnom računaru to je `C:\php\cacert.pem`).

Nakon toga su u lokalnom `php.ini` na razvojnom računaru podešene
sljedeće stavke kako bi PHP koristio preuzete CA certifikate:

```ini
curl.cainfo = "C:\php\cacert.pem"
openssl.cafile = "C:\php\cacert.pem"
```

Ako se isti problem pojavi na drugom računaru, potrebno je provjeriti
ove postavke i prilagoditi putanju stvarnoj lokaciji fajla `cacert.pem`.
Lokalni `php.ini` i `cacert.pem` nisu dio Git repozitorija.


## Autor

Harun Tabak · IBU · 2026
