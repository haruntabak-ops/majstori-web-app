# AI usage log

> Ova evidencija se vodi za svaki milestone. Bilježe se AI alati koji su stvarno korišteni, sažeci najvažnijih zahtjeva te slučajevi u kojima je AI predložio pogrešno ili nepotpuno rješenje, kako je problem prepoznat i kako je ispravljen. Promptovi su sažeti i nisu predstavljeni kao doslovni citati.

## Milestone 1 – Početna struktura, statički frontend i plan baze podataka

**AI alati korišteni:** ChatGPT – pomoć pri izradi i doradi statičkog frontenda, organizaciji projektne dokumentacije, provjeri poslovnih pravila i pripremi strukture projekta. Prijedlozi su pregledani i prilagođavani prema zahtjevima predmeta i potrebama aplikacije Majstori Web App.

### Najvažniji zahtjevi upućeni AI alatu

- Izraditi statički frontend za aplikaciju za pronalaženje majstora u Bosni i Hercegovini, s prikazima prilagođenim ulogama posjetioca, majstora i administratora.
- Koristiti postojeću strukturu projekta i jQuery SPA navigaciju, bez uvođenja Bootstrapa ili dodatnih biblioteka bez dogovora.
- Uskladiti ponašanje ekrana s prethodno definisanim poslovnim pravilima, uključujući hijerarhiju kategorija, izbor majstorluka i pretragu majstora.
- Pripremiti projektnu dokumentaciju, uključujući `AGENTS.md`, `README.md`, ERD dokumentaciju i ovu evidenciju AI korištenja.

### Slučajevi u kojima je AI predložio pogrešno ili nepotpuno rješenje

1. **Demonstracijski podaci nisu bili dosljedno odvojeni od HTML prikaza.**  
   **Šta je bilo problematično:** U toku izrade frontenda razmatrali smo odvajanje demonstracijskih podataka od HTML prikaza kako bi buduće povezivanje s bazom i API-jem zahtijevalo manje izmjena prikaza. 
   **Kako je problem prepoznat:** Pregledom trenutnih fajlova uočeno je da se demonstracijski sadržaj nalazi na oba mjesta.  
   **Kako je pristupljeno rješenju:** Podaci potrebni za dinamičko crtanje stabla kategorija i prikaz probnih izbora definisani su u JavaScriptu. Preostali demonstracijski sadržaj u HTML-u može se refaktorisati kada bude potrebno za povezivanje s API-jem. U narednim milestone-ima demonstracijske podatke treba postepeno zamijeniti podacima koji se učitavaju preko backenda i baze.

2. **Prikaz „Moji majstorluci” nije omogućavao pravilan izbor iz cijelog stabla kategorija.**  
   **Šta je bilo pogrešno:** Prijedlog je polazio od već zabilježenih veza između majstora i kategorija, umjesto da majstoru prikaže cijelo stablo kategorija i označi njegove trenutne izbore. Takav pristup majstoru ne bi omogućio jednostavan izbor dodatnih ili novih kategorija.  
   **Kako je problem prepoznat:** Rješenje je upoređeno s poslovnim pravilom da majstor iz stabla svih kategorija (majstorluka) može birati više kategorija, uključujući kategorije koje ranije nije odabrao.  
   **Kako je ispravljeno:** Prikaz je prilagođen tako da se prikaže cijelo stablo kategorija, da postojeći izbori budu označeni i da majstor može označiti ili poništiti izbor te sačuvati izmjene.

3. **U pretragu je dodato polje „Dodatna riječ” koje nije bilo dio dogovorenih uslova pretrage.**  
   **Šta je bilo pogrešno:** AI je predložio dodatni parametar pretrage koji nije bio predviđen projektnim pravilima i nije imao definisanu svrhu.  
   **Kako je problem prepoznat:** Polje je upoređeno s prethodno utvrđenim zahtjevima za pretragu, koji se zasnivaju na kategorijama majstorluka, gradu i opcionalnom radijusu.  
   **Kako je ispravljeno:** Polje „Dodatna riječ” je uklonjeno, a pretraga je usklađena s definisanim parametrima.

4. **U administratorskom prikazu kategorija nedostajala je funkcionalnost brisanja kategorije.**  
   **Šta je bilo nepotpuno:** Početno rješenje je podržavalo dodavanje, uređivanje i premještanje kategorija, ali nije uključivalo brisanje.  
   **Kako je problem prepoznat:** Prilikom provjere administratorskih funkcionalnosti uočeno je da brisanje nije pokriveno, iako je potrebno za upravljanje hijerarhijom kategorija.  
   **Kako je ispravljeno:** Dodana je funkcionalnost brisanja kategorije, uz ograničenje da se mogu brisati samo kategorije bez podkategorija, u skladu s definisanim pravilima.

### Napomene za naredne milestone-e

- Prije prihvatanja AI prijedloga provjeriti ih u odnosu na Excel dokumentaciju poslovnih pravila, ERD i postojeći kod.
- Ne predstavljati demonstracijske podatke kao trajno pohranjene podatke iz baze. Njihova zamjena stvarnim API pozivima planirana je za kasnije milestone-e.
- U narednim milestone-ima dodavati nove zapise samo za stvarno korištene AI alate, stvarne promptove ili njihove sažetke te probleme koji su zaista uočeni i ispravljeni.
