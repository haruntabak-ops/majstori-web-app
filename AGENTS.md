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

Ako se dokumenti razlikuju ili nešto nije jasno, nemoj samostalno izmišljati pravilo. Navedi neslaganje i zatraži pojašnjenje kada je potrebno.

## 3. Tehnologije

### Frontend
- HTML5, CSS3 i JavaScript.
- jQuery 3 za postojeću SPA navigaciju i AJAX zahtjeve.
- Koristi postojeći CSS. **Bootstrap se ne koristi.**
- Ne uvodi nove biblioteke ili frameworke bez prethodnog dogovora.

### Backend
- PHP 8.2+, FlightPHP 3, PDO i MySQL 8 prema planiranom tehnološkom stacku.
- Ova lista opisuje planirane tehnologije; ne znači da su sve već instalirane ili implementirane.
- Prije implementacije provjeri postojeće profesorove primjere, trenutni kod i `composer.json`.

## 4. Struktura projekta

```text
MajstoriWebApp/
├── frontend/       # SPA: HTML, CSS, JavaScript, pogledi i resursi
├── backend/        # PHP backend i REST API
├── docs/           # projektna dokumentacija, ERD i AI evidencija
├── radna-dokumentacija/  # pomoćni radni materijali
├── AGENTS.md       # ova uputstva
├── .gitignore
├── server.php
└── README.md
```

Unutar `backend/` nalaze se konfiguracija, logovi, middleware te `rest/dao/`, `rest/routes/` i `rest/services/`. Unutar `frontend/` nalaze se CSS, JavaScript, pogledi i resursi. Ova struktura je namjerno sažeta; prije izmjene provjeri stvarne fajlove i foldere u repozitoriju.

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
