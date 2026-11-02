# Baza podataka

- `MajstoriWebApp\database\majstori_web_app.sql` — glavna SQL skripta za kreiranje baze podataka projekta **Majstori Web App** i unos početnih testnih podataka. Sadrži strukturu potrebnih tabela, njihove veze i početne zapise, u skladu sa zahtjevima zadatka.
- `MajstoriWebApp\radna-dokumentacija\02_Pomocni_inicijalno_kreiranje_DB_scheme_i_DB_objekata.sql` — pomoćna radna skripta s **9 testnih SQL upita**: prvih 6 služi za pregled i provjeru početnih podataka u svih 5 tabela, a naredna 3 (7a, 7b i 7c) provjeravaju različite scenarije pretrage majstora prema hijerarhiji kategorija:
  - **7a:** pretraga po dvije kategorije istog roditelja (`Alarmni sistemi` i `Video-nadzor`), uključujući njihove potomke; majstor koji je odabrao samo nadređenu kategoriju ne ulazi u rezultate.
  - **7b:** pretraga po nadređenoj kategoriji (`Niska struja`) uključuje majstore koji su odabrali tu kategoriju ili bilo kojeg njenog potomka.
  - **7c:** isti hijerarhijski kriterij uz opcionalne geografske filtere — polazni grad Tuzla i radijus 100 km zračne udaljenosti (Sarajevo ulazi, Mostar ne).
- `MajstoriWebApp\docs\ERD.md` — sadrži ranije prikazani ERD model baze podataka.
- `MajstoriWebApp\docs\ERD_slika.jpg` — slikovni prikaz ERD modela baze podataka.
