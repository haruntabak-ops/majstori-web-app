# MajstoriWebApp — plan povezivanja frontenda, REST ruta, servisa i DAO sloja

**Status:** plan povezivanja za naredne milestonee; REST rute i servisi tek slijede.  
**Polazište:** postojeći milestone1 frontend i funkcionalnosti iz projektne dokumentacije  
**Pristup:** **uloga → stranica → funkcionalnost → REST ruta → servis → postojeći DAO → komentar**.

## Uvod

- Ovaj dokument prikazuje plan povezivanja ranije izrađenih frontend stranica aplikacije **MajstoriWebApp** sa budućim REST rutama i servisnim slojem. 
- Namijenjen je i kao jednostavniji pregled cjelokupne planirane arhitekture aplikacije: organizovan je po korisničkim ulogama, stranicama i funkcionalnostima, a za svaku funkcionalnost navodi predloženu rutu, servis, DAO metodu i kratko objašnjenje toka obrade. 
- Služi kao provjera da li DAO sloj pripremljen za milestone2 (**M2**) pokriva funkcionalnosti potrebne za naredne milestonee.

## Kako čitati dokument

- **REST ruta (prijedlog)** znači da će se tek naknadno odlučiti konačan naziv i implementirati ruta u FlightPHP-u (`Flight::route(...)`). Imena servisa u nastavku su također **predložena**; nisu postojeće PHP klase.
- **Postojeći DAO** označava klase i metode koje **postoje u dopunjenoj M2 verziji** u `backend/rest/dao/`, uključujući metode naslijeđene iz `BaseDao`. Oznaka **—** znači da DAO nije potreban.
- **Nema REST poziva** znači da se akcija obavlja u JavaScriptu preglednika, bez pristupanja bazi za svaki klik. AJAX učitavanje `views/*.html` već postoji i nije isto što i budući REST API.
- **Pristup:** javne rute mogu koristiti anonimni posjetioci; `/moj-profil` i `/moji-majstorluci` zahtijevaju prijavljenog majstora; `/promjena-lozinke` prijavljenog korisnika; `/admin/...` samo administrator. Pravo pristupa mora provjeriti **backend**, a ne samo prikaz menija.
- Jedna ruta može opslužiti više prikaza. Servis može kombinovati više DAO metoda i primijeniti poslovna pravila. Za efikasnu SQL pretragu majstora dodana je jedna namjenska metoda `MajstorDao::pretrazi()`; nije potrebna nova DAO metoda za svaki UI događaj.
- Rute su prikazane relativno prema REST API-ju (npr. `GET /kategorije`); konkretan osnovni URL utvrdit ćemo pri integraciji.

---

# 1. ANONIMNI KORISNIK (posjetilac)

## 1.1. Početna — `pocetna.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Prikaz broja majstora, kategorija i gradova | `GET /statistika` | `StatistikaService` | `MajstorDao::count_all()`; `KategorijaDao::count_all()`; `GradDao::count_all()` | Servis pozove tri postojeća `count_all()` i vrati brojeve kao JSON; JS popunjava kartice umjesto M1 demo vrijednosti. |
| Dugme/link „Pretraži majstore“ | Nema REST poziva | Frontend JS | — | SPA prelazak na `#pretraga`; sama stranica pretrage naknadno učitava potrebne podatke. |

## 1.2. Pretraga majstora — `pretraga.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Učitavanje stabla kategorija | `GET /kategorije` | `KategorijaService` | `KategorijaDao::get_all()` | Servis vraća `id`, `naziv`, `roditelj_id`; JS gradi hijerarhiju kao što sada radi funkcija `nacrtajStablo(...)`. |
| Širenje i skupljanje grana stabla | Nema REST poziva | Frontend JS | — | Klik mijenja lokalno stanje otvorenih čvorova; podaci su već učitani. |
| Odabir jedne ili više kategorija (checkboxovi) | Nema REST poziva pri kliku | Frontend JS | — | JS pamti direktno označene kategorije; tek pritiskom „Prikaži rezultate“ šalje kriterije za pretragu. |
| Učitavanje liste gradova za filter | `GET /gradovi` | `GradService` | `GradDao::get_all()` | JS puni padajuću listu gradova iz API odgovora umjesto `demoGradoviM1`. |
| Odabir grada i opcionalnog radijusa (10/25/50/100 km ili bez ograničenja) | Nema REST poziva pri odabiru | Frontend JS | — | Odabir se čuva u poljima forme; koristi se pri predaji pretrage. |
| Pretraga i prikaz odgovarajućih majstora (broj rezultata, kartice, kontakt i usluge) | `GET /majstori?kategorije=...&grad_id=...&radijus_km=...` | `PretragaService` | **`MajstorDao::pretrazi($kategorija_ids, $grad_id, $radijus_km)` — nova metoda** | JS šalje odabrane kategorije te opcionalno grad i radijus. Servis validira kriterije i poziva `pretrazi()`. DAO jednim SQL upitom preko **`WITH RECURSIVE`** nalazi tražene kategorije i njihove potomke; `EXISTS` provjerava **eksplicitno spremljene odabire** iz `MAJSTOR_KATEGORIJE` (samo roditelj nije dovoljan). Ako je grad odabran bez radijusa, traže se majstori baš iz tog grada; uz radijus računa se zračna udaljenost prema koordinatama gradova. Bez grada nema prostornog filtera. DAO vraća profile, kontakte, gradove i nazive odabranih majstorluka. Servis vraća **jedan odgovor** s nizom majstora i brojem rezultata (`count` niza); JS prikazuje broj i kartice. Nema posebne rute za crtanje rezultata. |
| Poništavanje kriterija („Poništi“) | Nema REST poziva | Frontend JS | — | Očisti checkboxove, izbor grada i radijusa; po potrebi ponovo prikaže početno stanje rezultata prema postojećem UI ponašanju. |
| Klik „Pogledaj profil“ | Nema REST poziva pri samoj navigaciji | Frontend JS | — | JS pamti/prosljeđuje **ID odabranog majstora** i otvara javni profil; taj profil zatim poziva vlastitu GET rutu. |

## 1.3. Javni profil majstora — `profil-majstora.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Učitavanje javnih podataka odabranog majstora: ime, grad, telefon, opis i kontakt | `GET /majstori/{id}` | `MajstorService` | `MajstorDao::get_by_id($id)` | Servis vrati podatke za konkretni ID iz rezultata pretrage; JS popuni prikaz umjesto uvijek prvog demo majstora. |
| Prikaz kategorija/usluga odabranog majstora | U istom odgovoru `GET /majstori/{id}` | `MajstorService` | `MajstorKategorijaDao::get_by_majstor_id($id)`; `KategorijaDao::get_all()` | Servis poveže ID-jeve direktno odabranih kategorija s njihovim nazivima i uključi ih u odgovor za profil. |
| Dugme „Nazad na pretragu“ | Nema REST poziva | Frontend JS | — | Samo povratak na stranicu pretrage. |

## 1.4. Prijava — `prijava.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Unos e-maila i lozinke | Nema REST poziva tokom unosa | Frontend JS | — | Frontend prikuplja i šalje podatke tek kada korisnik potvrdi formu. |
| Prijavljivanje | `POST /prijava` | `AuthService` | `KorisnikDao::get_by_email($email)` | Servis pronalazi račun, provjerava unesenu lozinku prema `lozinka_hash`, uspostavlja budući autentikacijski mehanizam i vraća ulogu (`admin` ili `majstor`). JS nakon uspjeha otvara odgovarajući dio aplikacije; ne čuvati lozinku u JS-u. |

---

# 2. MAJSTOR (prijavljeni korisnik)

## 2.1. Moj profil — `moj-profil.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Učitavanje vlastitih podataka profila | `GET /moj-profil` | `MajstorService` | `MajstorDao::get_by_korisnik_id($korisnik_id)` | Identitet se uzima iz prijave/sesije, a **ne** iz proizvoljnog ID-a koji pošalje JS. Servis vraća ime, prezime, telefon, opis i grad; JS popunjava polja. |
| Učitavanje gradova za izbor u profilu | `GET /gradovi` | `GradService` | `GradDao::get_all()` | Frontend puni izbor gradova; majstor bira samo već postojeći `grad_id`, bez samostalnog unosa koordinata (P005). |
| Izmjena polja u formi (ime, prezime, telefon, opis, grad) | Nema REST poziva pri unosu | Frontend JS | — | JS čuva trenutne vrijednosti forme; trenutni klikovi/pisanje ne mijenjaju bazu. |
| Spremanje izmjena profila | `PUT /moj-profil` | `MajstorService` | `MajstorDao::get_by_korisnik_id()`; `MajstorDao::update()`; po potrebi `MajstorDao::add()`; `GradDao::get_by_id()` | Servis provjerava podatke i grad iz postojeće liste; ažurira **samo profil prijavljenog majstora**. Ako račun još nema profil, servis kreira prvi profil pomoću `add()` kada su prikupljena obavezna polja. JS prikazuje uspjeh ili grešku. |

## 2.2. Moji majstorluci — `moji-majstorluci.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Prikaz kompletnog stabla kategorija | `GET /kategorije` | `KategorijaService` | `KategorijaDao::get_all()` | Vraća cijelo stablo kao podatke s `roditelj_id`. Frontend ga crta postojećom funkcijom `nacrtajStablo(...)`. |
| Učitavanje prethodno odabranih majstorluka | `GET /moji-majstorluci` | `MajstorService` | `MajstorDao::get_by_korisnik_id()`; `MajstorKategorijaDao::get_by_majstor_id()` | Servis utvrđuje ID prijavljenog majstora i vraća njegove **direktne odabire**. JS koristi te ID-jeve za označavanje checkboxova. **Preporuka:** sačekati rezultate oba GET zahtjeva i tek tada nacrtati označeno stablo. |
| Širenje/skupljanje grana stabla | Nema REST poziva | Frontend JS | — | Samo lokalno mijenja prikaz otvorenih grana. |
| Označavanje i odznačavanje kategorija klikom po checkboxovima | Nema REST poziva pri kliku | Frontend JS | — | JS pamti konačni izbor. Označavanje roditelja **ne označava automatski djecu** (P004). |
| Spremanje novog izbora („Sačuvaj majstorluke“) | `PUT /moji-majstorluci` | `MajstorService` | `MajstorDao::get_by_korisnik_id()`; `KategorijaDao::get_all()`; `MajstorKategorijaDao::get_by_majstor_id()`; `MajstorKategorijaDao::add()`; `MajstorKategorijaDao::delete_by_keys()`; `BaseDao::begin_transaction()/commit()/rollback()` | JS šalje niz konačno odabranih ID-jeva. Servis provjeri da kategorije postoje, učita stare veze, uporedi skupove: **dodaje nove**, **briše uklonjene**, a neizmijenjene ostavlja. Sve upisne operacije izvrši u jednoj transakciji. Nema `BaseDao::update()` za veznu tabelu sa složenim ključem. |

## 2.3. Promjena lozinke — `promjena-lozinke.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Unos trenutne, nove i potvrđene lozinke | Nema REST poziva pri unosu | Frontend JS | — | JS provjeri podudaranje nove i potvrđene lozinke radi poruke korisniku; backend ponavlja potrebne provjere. |
| Promjena vlastite lozinke | `PUT /promjena-lozinke` | `AuthService` | `KorisnikDao::get_by_id($korisnik_id)`; `KorisnikDao::update(['lozinka_hash' => ...], $korisnik_id)` | Servis koristi ID prijavljenog korisnika, provjerava trenutnu lozinku preko hash vrijednosti, provjeri novu i sprema **novi hash**. JS prikazuje rezultat. |

## 2.4. Odjava — `odjava.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Odjava prijavljenog majstora | `POST /odjava` | `AuthService` | — | Backend završava sesiju ili na drugi način poništava autentikaciju (zavisno od kasnije odabranog pristupa); JS uklanja prikaz prijavljenog stanja. |
| „Vrati se na početnu“ | Nema REST poziva | Frontend JS | — | Navigacija na javnu početnu stranicu. |

---

# 3. ADMINISTRATOR (prijavljeni korisnik)

## 3.1. Korisnici — `korisnici.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Prikaz liste računa (e-mail, uloga i dostupni podaci profila) | `GET /admin/korisnici` | `KorisnikService` | `KorisnikDao::get_all()`; `MajstorDao::get_all()` ili `MajstorDao::get_by_korisnik_id()` | Servis spaja račune s postojećim profilima majstora; admin nema majstorski profil, a novi majstorski račun ga možda još nema. JS popunjava tabelu. |
| Otvaranje detalja korisnika („Pregled“) | `GET /admin/korisnici/{id}` | `KorisnikService` | `KorisnikDao::get_by_id($id)`; ako je uloga majstor: `MajstorDao::get_by_korisnik_id($id)` | Servis vrati račun i, kada postoji, podatke profila. JS prikazuje **samo čitanje**; odabrani majstorluci nisu dio trenutnog administratorskog prikaza. Ako su svi detalji već u listi, može se prikazati i bez dodatnog GET-a. |
| Otvaranje/zatvaranje forme „Dodaj korisnika“ | Nema REST poziva | Frontend JS | — | Samo prikaz ili skrivanje već postojeće forme. |
| Unos e-maila, privremene lozinke i uloge (majstor/admin) | Nema REST poziva tokom unosa | Frontend JS | — | Forma priprema podatke koje kasnije predaje backendu. |
| Kreiranje korisničkog računa | `POST /admin/korisnici` | `KorisnikService` | `KorisnikDao::get_by_email()`; `KorisnikDao::add()` | Servis provjerava jedinstvenost e-maila (P001), dozvoljenu ulogu, hashira početnu lozinku i kreira račun; SQL `UNIQUE` dodatno štiti e-mail. **Ne kreira automatski nepuna polja profila** ako ih forma ne prikuplja. |

## 3.2. Kategorije — `kategorije.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Učitavanje i prikaz hijerarhije kategorija | `GET /kategorije` | `KategorijaService` | `KategorijaDao::get_all()` | Isti javni API i ista JS logika crtanja stabla kao u pretrazi, ali s administratorskim dugmadima. |
| Širenje/skupljanje grana stabla | Nema REST poziva | Frontend JS | — | Koristi već preuzete kategorije; samo mijenja lokalni prikaz. |
| Otvaranje forme za dodavanje kategorije i izbor roditelja | Nema REST poziva nakon učitanog stabla | Frontend JS | — | JS nudi postojeće kategorije kao moguće roditelje; prikuplja naziv i `roditelj_id`. |
| Spremanje nove kategorije | `POST /admin/kategorije` | `KategorijaService` | `KategorijaDao::get_all()`; `KategorijaDao::get_by_id()`; `KategorijaDao::add()` | Servis provjerava naziv, duplikat i postojanje roditelja; `get_all()` je dovoljan za poređenje naziva. Potom poziva `add()` i vraća novi zapis, a JS osvježi stablo. |
| Otvaranje forme za preimenovanje/premještanje kategorije | Nema REST poziva ako je stablo učitano | Frontend JS | — | JS popuni trenutno ime i roditelja; iz liste mogućih novih roditelja uklanja sam čvor i njegove potomke. |
| Spremanje promjene naziva i/ili roditelja | `PUT /admin/kategorije/{id}` | `KategorijaService` | `KategorijaDao::get_by_id()`; `KategorijaDao::get_all()`; `KategorijaDao::update()` | Servis provjerava naziv i da novi roditelj nije sam čvor niti njegov potomak (**V002**). Mijenja `naziv` i/ili `roditelj_id`; pri premještanju **ne mijenja** direktne veze majstora (**P008**). |
| Klik na „Obriši“, upozorenje i potvrda | Nema REST poziva do potvrde | Frontend JS | — | JS može unaprijed prikazati upozorenje; **stvarnu** provjeru uslova brisanja ponavlja backend. |
| Brisanje kategorije bez djece | `DELETE /admin/kategorije/{id}` | `KategorijaService` | `KategorijaDao::get_by_id()`; `KategorijaDao::get_children($id)`; `MajstorKategorijaDao::get_by_kategorija_id($id)`; `MajstorKategorijaDao::delete_by_keys()`; `KategorijaDao::delete($id)`; transakcije `BaseDao` | Servis odbija brisanje ako ima podkategorija (**V001**). Ako je list, u **jednoj transakciji** ukloni veze te kategorije iz `MAJSTOR_KATEGORIJE`, pa samu kategoriju (**A001**); profili i računi ostaju. Nakon uspjeha JS osvježi stablo. |
| Odustajanje od dodavanja/uređivanja | Nema REST poziva | Frontend JS | — | Zatvara formu, bez promjene baze. |

## 3.3. Gradovi — `gradovi.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Prikaz sačuvanih gradova i koordinata | `GET /gradovi` | `GradService` | `GradDao::get_all()` | Servis vraća `id`, naziv, latitude i longitude; JS puni tabelu. |
| Otvaranje forme „Dodaj grad“ i unos naziva | Nema REST poziva | Frontend JS | — | JS otvara formu i priprema naziv za pronalaženje lokacije. |
| Pronalaženje grada preko Nominatim/OSM API-ja | Nema **naše** REST rute u postojećem M1 pristupu | Frontend JS + vanjski Nominatim API | — | Postojeći JS šalje zahtjev vanjskom servisu, dobija naziv/koordinate i prikazuje pronađenu lokaciju na OSM mapi. Ovo nije poziv našeg DAO-a. |
| Pregled pronađenih koordinata i lokacije na mapi | Nema REST poziva prema našem backendu | Frontend JS | — | JS prikazuje rezultat vanjskog API-ja; administrator provjerava lokaciju prije potvrde (**P009, V004**). |
| Potvrda i dodavanje grada u našu bazu | `POST /admin/gradovi` | `GradService` | `GradDao::get_by_naziv()`; `GradDao::add()` | Nakon potvrde JS šalje naziv i provjerene koordinate. Servis provjerava podatke, eventualni duplikat i sprema grad; API vrati zapis pa JS ažurira tabelu. Gradovi se kroz ovaj GUI **ne uređuju i ne brišu** (**P010**). |

## 3.4. Odjava — `odjava.html`

| Funkcionalnost | REST ruta (prijedlog) | Servis (plan) | Postojeći DAO | Komentar — osnovna implementacija |
|---|---|---|---|---|
| Odjava administratora | `POST /odjava` | `AuthService` | — | **Ista ruta i logika** kao za odjavu majstora; ne duplicirati servis. |
| „Vrati se na početnu“ | Nema REST poziva | Frontend JS | — | Navigacija na javnu početnu stranicu. |

---

## Važne napomene za implementaciju narednih milestonea

1. **Razdvajanje slojeva:** `Routes` prima HTTP zahtjev i vraća JSON/status; `Service` provjerava pravila i kombinuje DAO pozive; `DAO` izvršava SQL. Ovo slijedi obrazac profesorice iz `campus-events-app/week-05`, npr. `CategoryRoutes.php` → `CategoryService.php` → `CategoryDao.php` (servis po potrebi koristi dodatni DAO).
2. **Jedna metoda, više stranica:** `GET /kategorije` služi za javnu pretragu, majstorov izbor usluga i administratorsko stablo; `GET /gradovi` služi za filter pretrage, profil majstora i tabelu gradova. **Nema potrebe za dupliciranim rutama.**
3. **Učitavanje „Mojih majstorluka“:** istovremeno poslati `GET /kategorije` i `GET /moji-majstorluci`; JS čeka oba odgovora pa **jednom** nacrta stablo s označenim direktnim odabirima. Trenutni M1 JS već razdvaja niz svih kategorija od niza odabranih i već ima `nacrtajStablo(...)`; pri integraciji zamijeniti izvor podataka, bez redizajna GUI-ja.
4. **Administratorska prava i vlasništvo:** rute `/admin/...` mora zaštititi backend za ulogu `admin`; `/moj-...` koriste ID prijavljenog majstora, ne tuđi ID iz zahtjeva. Samo skrivanje menija u JS-u nije zaštita.
5. **Razlika frontend/baza za ulogu:** M1 demo koristi `artisan`, dok SQL koristi `majstor`. Kasnije ujednačiti frontend na `majstor`, kako je dogovoreno; to ne traži novu DAO metodu.
6. **Razlika između M1 i tek planiranog ponašanja:** trenutna pretraga prikazuje probne kartice bez stvarnog filtriranja; javni profil prikazuje prvog demo majstora; korisnici, kategorije, gradovi i forme pamte promjene samo u pregledniku. Ove akcije postaju stvarne tek povezivanjem navedenih API ruta.
7. **Jedinstvenost i hijerarhija:** SQL ne ima `UNIQUE` na `KATEGORIJE.naziv`; provjera duplog naziva po pravilima sadašnjeg JS-a radi se u `KategorijaService` nad `get_all()`. Ciklus i zabrana brisanja nelistova provjeravaju se na backendu.
8. **Složeni ključ:** `MAJSTOR_KATEGORIJE` nema kolonu `id`; koristiti namjenske metode `MajstorKategorijaDao::add()` i `delete_by_keys()`, ne generički `BaseDao::update()` ili `delete()`.
9. **Transakcije:** spremanje više veza majstorluka i brisanje kategorije s njenim vezama izvršavati atomarno. Postojeće `BaseDao` transakcijske metode i zajednička PDO konekcija mogu podržati isti transakcijski tok kroz više DAO objekata.
10. **Dalja implementacija:** JSON oblik, validacije zahtjeva i HTTP statusi precizirat će se pri izradi REST sloja. Nova metoda za pretragu već je dodana u `MajstorDao`; ostale navedene rute i servisi su planirani.

## Kraća provjera pokrivenosti postojećim DAO metodama

| DAO klasa | Uloga u planu | Ocjena |
|---|---|---|
| `BaseDao` | Zajednički CRUD, brojanje, transakcije | Postoji |
| `KorisnikDao` | Prijava, promjena lozinke, administracija računa | Dovoljno |
| `MajstorDao` | Javni profil, SQL pretraga po kategorijama i lokaciji, vlastiti profil | Dovoljno, posjeduje i zasebnu metodu za pretragu `pretrazi()` |
| `KategorijaDao` | Stablo za tri korisnička konteksta i administratorski CRUD | Dovoljno uz servis |
| `MajstorKategorijaDao` | Direktne veze majstor–kategorija, spremanje odabira, brisanje veza | Dovoljno uz servis |
| `GradDao` | Lista gradova, odabir u profilu, dodavanje provjerenog grada, koordinate | Dovoljno uz servis |


## Korišteni projektni izvori

- Projektni frontend (`frontend/views/*.html`, `frontend/js/app.js`), DAO sloj (`backend/rest/dao/*.php`), SQL model (`database/majstori_web_app.sql`) i projektna dokumentacija (`docs/Majstori_Web_App_dokumentacija.xlsx`, listovi `Opis_aplikacije` i `Poslovna_pravila`).
- `web-programming-2026-main_week_01_05.zip`: `campus-events-app/week-05/backend/rest/routes/CategoryRoutes.php` i `services/CategoryService.php` samo kao **arhitekturni uzor**.
