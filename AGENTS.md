# AGENTS.md – upute za AI asistente

## 1. Svrha projekta

**Majstori Web App** je web aplikacija za pronalaženje majstora u Bosni i Hercegovini. Posjetioci pretražuju majstore prema kategorijama majstorluka i gradu, uz opcionalni radijus pretrage. Administratori upravljaju korisničkim računima, kategorijama i gradovima, a majstori svojim profilima i odabranim kategorijama.

## 2. Dokumentacija koju treba konsultovati

Prije rada na odgovarajućem dijelu aplikacije, koristi postojeću projektnu dokumentaciju:

- `README.md` – opći opis projekta i upute za pokretanje.
- `docs/Majstori_Web_App_dokumentacija.xlsx` – funkcionalni zahtjevi i poslovna pravila. Provjeri relevantne listove prije izmjene funkcionalnosti.
- `docs/ERD.md` – model baze podataka i odnosi između entiteta. Provjeri ga prije planiranja ili izmjene baze i backend pristupa podacima.
- `docs/AI_LOG.md` – evidencija korištenja AI alata i relevantnih primjera tokom razvoja.
- `radna-dokumentacija/` – pomoćne bilješke i radni materijali; koristi ih kao dodatni kontekst, a ne kao zamjenu za usvojenu projektnu dokumentaciju.
- `database/majstori_web_app.sql` – SQL skripta za kreiranje baze i početne testne podatke.
- `docs/FRONTEND_API_PLAN.md` – plan povezivanja funkcionalnosti frontenda s REST rutama, servisima i DAO metodama; koristiti kao referencu u narednim milestoneima.

Ako se dokumenti razlikuju ili nešto nije jasno, nemoj samostalno izmišljati pravilo. Navedi neslaganje i zatraži pojašnjenje kada je potrebno.

## 3. Tehnologije

### Frontend
- HTML5, CSS3 i JavaScript.
- jQuery 3 za postojeću SPA navigaciju i AJAX zahtjeve.
- Koristi postojeći CSS. **Bootstrap se ne koristi.**
- Ne uvodi nove biblioteke ili frameworke bez prethodnog dogovora.

### Backend
- PHP 8.2+, FlightPHP 3, PDO i MySQL 8 prema planiranom tehnološkom stacku.
- DAO sloj implementiran je u M2, a servisni sloj i REST API u M3.
- Composer za upravljanje PHP bibliotekama (FlightPHP i swagger-php).
- Swagger/OpenAPI za dokumentovanje i testiranje REST API-ja.
- Prije implementacije provjeri postojeće profesorove primjere, trenutni kod i `composer.json`.

## 4. Struktura projekta

```text
MajstoriWebApp/
├── frontend/                       # frontend aplikacije (SPA)
│   ├── assets/
│   │   └── img/                    # folder za slike (trenutno README.txt)
│   ├── css/
│   │   └── style.css               # stilovi i responzivni izgled
│   ├── js/
│   │   └── app.js                  # SPA navigacija i JavaScript logika
│   ├── views/                      # HTML prikazi pojedinačnih ekrana
│   ├── index.html                  # početna HTML stranica
│   └── README.txt                  # dodatne informacije o frontendu
├── backend/                        # PHP backend i REST API
│   ├── config/
│   │   └── config.example.php      # primjer konfiguracije backenda
│   ├── logs/                       # logovi aplikacije
│   ├── middleware/                 # folder za middleware
│   ├── public/
│   │   └── v1/
│   │       └── docs/              # Swagger/OpenAPI dokumentacija
│   │           ├── paths/         # OpenAPI opisi REST ruta
│   │           ├── doc_setup.php  # OpenAPI sheme
│   │           ├── index.html     # Swagger UI
│   │           └── swagger.php    # generisanje OpenAPI JSON-a
│   ├── rest/
│   │   ├── dao/                    # pristup MySQL bazi
│   │   ├── services/               # poslovna logika i testovi servisa
│   │   └── routes/                 # REST rute i dokumentacija testiranja
│   ├── utils/                      # pomoćne klase i izuzeci
│   ├── .htaccess                   # Apache konfiguracija za backend
│   ├── composer.json               # Composer zavisnosti
│   ├── composer.lock               # tačne verzije zavisnosti
│   └── index.php                   # glavna ulazna tačka backenda
├── database/
│   ├── majstori_web_app.sql        # kreiranje baze i početni podaci
│   └── README_baza.md              # dokumentacija baze
├── docs/                           # projektna dokumentacija, ERD i AI evidencija
├── radna-dokumentacija/            # pomoćni radni materijali
├── AGENTS.md                       # uputstva za AI asistente
├── .gitignore
├── server.php                      # lokalno usmjeravanje HTTP zahtjeva
└── README.md
```

Prije bilo kakvih izmjena potrebno je provjeriti stvarne fajlove i foldere u repozitoriju.

## 5. Pravila koja AI mora slijediti

1. **Odvajanje koda:** Ne miješati frontend i backend. Ne stavljati PHP u `frontend/` niti praviti frontend prikaze direktnim ispisivanjem HTML-a iz backenda; backend treba izlagati API odgovore.
2. **Zaštita tajni:** Ne prikazivati niti commitati stvarne lozinke, tokene ili druge tajne. Ne commitati `backend/config/config.php` ni lokalne konfiguracije koje sadrže tajne. U repozitoriju koristiti `config.example.php` kao predložak.
3. **Konfiguracija:** Kada postoji dogovorena konfiguracijska klasa, koristiti njen interfejs (`Config::...()`) i ne izmišljati alternativni način pristupa konfiguraciji.
4. **Postojeći stil:** Pratiti postojeću strukturu, stil i profesorove primjere. Ne dodavati biblioteke bez pitanja.
5. **Objašnjavanje:** Objasniti svaku važnu izmjenu – šta je promijenjeno, zašto i kako radi – tako da student može samostalno objasniti i odbraniti rješenje.
6. **Provjera prije izmjene:** Pregledati relevantne fajlove i dokumentaciju prije predlaganja promjena. Ne tvrditi da je nešto provjereno ili testirano ako to nije urađeno.
7. **Poslovna pravila:** Ne mijenjati poslovnu logiku bez razloga. Prije rada na funkcionalnosti konsultovati Excel dokumentaciju i, kada je relevantno, ER model.
8. **Rad postepeno:** Ne implementirati unaprijed funkcionalnosti koje još nisu dio trenutnog zadatka. Prvo dogovoriti opseg, zatim raditi i objasniti izmjene.

## 6. Konvencije imenovanja

- PHP klase: `PascalCase`, npr. `MajstorService`.
- PHP metode i varijable: `snake_case`, npr. `get_by_id`.
- JavaScript klase/konstruktori: `PascalCase`.
- JavaScript funkcije i varijable: `camelCase`, npr. `loadView`, `selectedCategory`.
- Nazivi tabela i kolona baze: `snake_case`; nazive tabela uskladiti s postojećim ER modelom.
- HTML pogledi: pratiti nazive koji već postoje; za nove fajlove koristiti `kebab-case.html` ako se ne dogovori drugačije.

Ne preimenovati postojeće elemente samo radi kozmetičkog ujednačavanja. Prije preimenovanja provjeriti sve njihove upotrebe.

## 7. Dodatne upute za Milestone 2 – Baza podataka i DAO sloj

1. **Baza podataka:** Pridržavati se ERD modela i funkcionalne specifikacije projekta. Ne uvoditi nepotrebne izmjene baze niti nove funkcionalnosti.

2. **DAO arhitektura:** Slijediti pristup iz pokazne aplikacije **Campus Events App** – zajednička klasa `BaseDao` za generičke CRUD operacije i posebna DAO klasa za svaku tabelu.

3. **Planiranje, arhitektura i stil:** Prije implementacije DAO metoda analizirati funkcionalnosti postojećeg frontenda i projektne specifikacije te osmisliti plan njihovog povezivanja s budućim REST rutama, servisima i potrebnim DAO metodama. Plan dokumentovati u `docs/FRONTEND_API_PLAN.md`. Poštovati razdvajanje DAO, Service i REST sloja te slijediti jednostavan PHP stil iz pokazne aplikacije **Campus Events App**, bez nepotrebnih apstrakcija.

4. **Vezna tabela:** Voditi računa da `MAJSTOR_KATEGORIJE` ima složeni primarni ključ umjesto standardnog `id`. Prilagoditi njenu DAO klasu i zaštititi `BaseDao` od neodgovarajućih generičkih operacija.

5. **Pretraga majstora:** Osmisliti efikasnu pretragu prema hijerarhiji kategorija, uz opcionalni grad i radijus. Majstor odgovara traženoj kategoriji samo ako je eksplicitno odabrao nju ili nekog njenog potomka, a ne samo roditelja.

## 8. Dodatne upute za Milestone 3 – Servisni sloj, REST API i Swagger

1. **Arhitektura:** Slijediti pristup iz pokazne aplikacije **Campus Events App (Week 06)** i poštovati razdvajanje REST, Service i DAO sloja. REST rute obrađuju HTTP zahtjeve i pozivaju servise, servisi implementiraju poslovnu logiku, a DAO klase pristupaju bazi podataka. Ne pisati SQL upite direktno u servisima ili REST rutama.

2. **Servisni sloj:** Koristiti postojeći `BaseService` za zajedničke CRUD operacije i posebne servise za poslovna pravila. Voditi računa da `MajstorKategorijaService` zbog složenog primarnog ključa ne nasljeđuje `BaseService` i koristi posebne metode za upravljanje vezama.

3. **Poslovna pravila i validacija:** Poštovati postojeća poslovna pravila i koristiti pomoćne fajlove `Validator.php`, `Roles.php` i `exceptions.php`. Prije dodavanja novih provjera pregledati postojeću implementaciju kako se poslovna logika ne bi nepotrebno ponavljala.

4. **REST rute:** Koristiti postojeću FlightPHP strukturu i odgovarajuće servise. Prije dodavanja ili izmjene endpointa provjeriti postojeće rute, servisne metode i plan `docs/FRONTEND_API_PLAN.md`.

5. **Swagger/OpenAPI:** Pri dodavanju ili izmjeni REST ruta ažurirati odgovarajuću dokumentaciju u `backend/public/v1/docs/paths/` i, kada je potrebno, sheme u `doc_setup.php`. Dokumentacija treba odgovarati stvarnoj implementaciji API-ja.

6. **Testiranje:** Koristiti postojeće PHP testove servisnog sloja i provjeravati REST rute. CRUD testove pokretati samo na razvojnoj/testnoj bazi podataka jer tokom izvršavanja mogu mijenjati podatke. Ne navoditi da je test uspješan ako nije izvršen i provjeren.

7. **Vanjski API:** Za pronalaženje koordinata koristiti postojeći `NominatimService` i konfiguracijske postavke. Ne isključivati SSL provjeru radi zaobilaženja problema s HTTPS certifikatima.

8. **Sigurnost:** JWT autentifikacija i zaštita administratorskih ruta još nisu implementirane. Do njihove implementacije administratorske rute koristiti samo lokalno, na razvojnoj/testnoj bazi podataka.

9. **Postojeći kod i stil:** Zadržati jednostavan PHP stil, postojeću arhitekturu i već implementirane metode. Ne uvoditi nepotrebne klase, biblioteke ili promjene koje nisu dio dogovorenog zadatka.
