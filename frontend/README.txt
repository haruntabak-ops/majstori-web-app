MAJSTORI WEB APP — M1 STATIČKI FRONTEND

Pokretanje:
1. Raspakujte ZIP.
2. Otvorite folder majstori_frontend u Visual Studio Code.
3. Pokrenite index.html pomoću ekstenzije Live Server.
   Nemojte otvarati index.html direktno preko file:// jer se stranice iz views/ učitavaju pomoću fetch().

Struktura:
- index.html: zajednički raspored, bočni meni, zaglavlje i ilustracija aplikacije.
- views/: stvarni HTML sadržaj svake stranice.
- js/app.js: navigacija, privremeni podaci kategorija i demo ponašanje. jQuery koristi se za AJAX učitavanje stranica i odabrane događaje interfejsa.
- css/style.css: izgled i responzivni dizajn.
- assets/img/: mjesto za buduće statičke slike.

Stablo majstorluka:
- Privremeni podaci su u js/app.js u nizu kategorijeM1.
- Stranica Pretraga majstora ima vlastito stablo s checkboxima.
- Stranica Moji majstorluci prikazuje cijelo stablo; odabrane stavke su unaprijed označene.
- Administracija > Kategorije prikazuje isto stablo.
- U M1 se podaci ne čuvaju u bazi. Kasnije će se niz kategorijeM1 zamijeniti odgovorom PHP API-ja, a funkcija prikaza stabla će koristiti isti format podataka.

Uloge:
- U gornjem zaglavlju postoji M1 simulator uloge: Anonimni, Majstor i Admin.
- Simulator služi samo za pregled menija. Nije stvarna prijava ni sigurnosna kontrola.

API:
- Geografski API i mapa još nisu povezani. Na stranici Gradovi je predviđeno mjesto za buduću integraciju.

Napomena:
Ovo je statički prototip M1. Obrasci i rezultati su demonstracijski; nema PHP backenda ni MySQL veze.
