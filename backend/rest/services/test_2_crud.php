<?php
/**
 * TEST 2 – CRUD OPERACIJE I POSLOVNA PRAVILA SERVISA (M3).
 *
 * Svrha: provjeriti da servisni sloj zaista dodaje, čita, mijenja i briše zapise te da poštuje
 * poslovna pravila (npr. zabrana ciklusa kategorija, hashiranje lozinki i zaštita zauzetog grada).
 *
 * Način rada slijedi profesoricin week-06/testv3.php: testiramo servise direktno, bez REST ruta.
 * Pomoćne funkcije za ispis, brojanje testova i provjeru očekivanih grešaka dopunili smo za naš projekat.
 *
																			 
 * PAŽNJA: ovaj fajl STVARNO MIJENJA MYSQL BAZU. Pokrećemo ga samo na lokalnoj/testnoj bazi uz rezervnu kopiju.
 * Kreiramo nove zapise s jedinstvenom oznakom, kroz njih provjeravamo CRUD, pa ih brišemo.
 * Na kraju se izvršava rezervno čišćenje u bloku finally, ali ono nije garancija pri prekidu procesa
 * ili ako i samo brisanje prijavi grešku. Zato pregledamo završni ispis testiranja.
 *
 * Ne otvaramo jednu zajedničku transakciju oko svih testova jer neke servisne metode
 * već same upravljaju transakcijama. Testove izvodimo nad zasebnim, privremenim zapisima.
 *
 * Pokretanje iz glavnog foldera MajstoriWebApp:
 *     php backend/rest/services/test_2_crud.php
 */
if (PHP_SAPI !== 'cli') {
    exit('Pokreni test iz PowerShella, CMD-a ili Git Basha.');
}

// Composer učitava biblioteke; zatim učitavamo servisne klase čije ćemo metode testirati.
// Ovdje NEMA FlightPHP ruta: test poziva objekte servisa direktno, a servisi dalje koriste DAO.
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/GradService.php';
require_once __DIR__ . '/KategorijaService.php';
require_once __DIR__ . '/KorisnikService.php';
require_once __DIR__ . '/MajstorService.php';
require_once __DIR__ . '/MajstorKategorijaService.php';
require_once __DIR__ . '/PretragaService.php';

// Brojači važe za cijelo pokretanje skripte. Očekivana greška koja je pravilno prijavljena također je uspješan test.
// Na kraju ćemo prikazati ukupan broj uspješnih i neuspješnih provjera.
$uspjesno = 0;
$neuspjesno = 0;

/**
 * POMOĆNA FUNKCIJA: izvršava jedan test i prikazuje je li njegov poziv uspio.
 *
 * Svrha: da ne ponavljamo isti try/catch, ispis i brojanje rezultata oko svakog servisnog poziva.
 * Prvo ispiše naziv testa, zatim pozove proslijeđenu akciju (najčešće metodu nekog servisa).
 * Ako poziv vrati vrijednost false, test se smatra neuspješnim; inače se ispisuje OK i vraća rezultat.
 * Ako dođe do izuzetka ili greške, ispisuje se njen naziv i poruka, a testiranje ostalih slučajeva se nastavlja.
 *
 * Ulaz:
 *   $opis  - naziv testa koji će se prikazati u terminalu.
 *   $akcija - PHP funkcija bez argumenata (npr. fn() => $gradovi->get_all()) koju želimo izvršiti.
 *
 * Izlaz: vraća rezultat akcije nakon uspjeha; pri grešci vraća null. Usput mijenja brojače uspjeha/neuspjeha.
 *
 * Napomena: ova funkcija NE provjerava automatski je li sadržaj vraćenih podataka tačan.
 * Za konkretnu provjeru koristimo mora_biti() unutar proslijeđene akcije.
 */
function testiraj($opis, $akcija)
{
    // Pristupamo zajedničkim brojačima koji se prikazuju na kraju skripte.
    global $uspjesno, $neuspjesno;
    // Ispis naziva testa poravnat tačkama radi lakšeg pregledanja rezultata.
    echo str_pad($opis, 66, '.') . ' ';
    try {
        // Ovdje se zaista pokreće metoda servisa ili druga provjera koju smo proslijedili kao akciju.
        $rezultat = $akcija();
        // Samo strogo false tretiramo kao neuspjeh; drugačiji sadržaj provjerava se zasebno pomoću mora_biti().
        if ($rezultat === false) {
            throw new RuntimeException('Dobijeni rezultat nije očekivan.');
        }
        // Poziv je završio bez greške, pa povećavamo broj uspješnih testova i vraćamo dobijene podatke.
        echo "OK\n";
        $uspjesno++;
        return $rezultat;
    } catch (Throwable $e) {
        // Bilo koji izuzetak ili PHP greška označava neuspjeh ovog testa; ostali testovi se nastavljaju.
        echo 'GREŠKA: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
        $neuspjesno++;
        return null;
    }
}

/**
 * POMOĆNA FUNKCIJA: provjerava da servis odbija neispravan zahtjev očekivanom vrstom izuzetka.
 *
 * Svrha: testirati negativne slučajeve, npr. prazan naziv grada ili neispravnu lozinku.
 * Izvršimo akciju i očekujemo izuzetak. Ako izuzetak izostane, test je NEUSPJEŠAN.
 * Ako servis baci izuzetak traženog tipa, ispisujemo OK, naziv izuzetka i HTTP status ako je dostupan.
 * Ako servis baci neki drugi izuzetak, i to računamo kao neuspješan test.
 *
 * Ulaz:
 *   $opis   - naziv testa za ispis u terminalu.
 *   $akcija - PHP funkcija koja bi trebala prijaviti grešku.
 *   $tip    - očekivana klasa izuzetka, npr. ValidationException::class.
 *
 * Izlaz: nema povratne vrijednosti; ispisuje rezultat i ažurira brojače testova.
 *
 * Napomena: 'OK' ovdje znači da je servis ISPRAVNO ODBIO neispravan zahtjev, a ne da je upis uspio.
 */
function ocekuj_gresku($opis, $akcija, $tip)
{
    global $uspjesno, $neuspjesno;
    echo str_pad($opis, 66, '.') . ' ';
    try {
        // Pokrećemo poziv za koji očekujemo da servis prijavi grešku, npr. ValidationException.
        $akcija();
        // Ako nismo dobili nikakav izuzetak, servis nije odbio neispravan zahtjev kako smo očekivali.
        echo "GREŠKA: očekivani izuzetak nije bačen.\n";
        $neuspjesno++;
    } catch (Throwable $e) {
        // Poređenje vrste izuzetka: samo očekivani tip znači da je negativni test prošao.
        if ($e instanceof $tip) {
            echo 'OK (' . get_class($e);
            // Kod naših HTTP izuzetaka ispišemo i status (npr. 400, 404 ili 409).
            if ($e instanceof HttpException) {
                echo ' HTTP ' . $e->get_status();
            }
            echo ")\n";
            $uspjesno++;
        } else {
            // Dobijen je izuzetak, ali nije onaj koji je predviđen ovim testom.
            echo 'GREŠKA: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
            $neuspjesno++;
        }
    }
}

/**
 * POMOĆNA FUNKCIJA: provjerava konkretan uslov koji očekujemo u nekom testu.
 *
 * Svrha: nije dovoljno da metoda samo ne prijavi grešku; nekad moramo provjeriti vraćeni ID,
 * broj pronađenih zapisa, izmijenjeni naziv ili da javni odgovor ne sadrži hash lozinke.
 * Ako je uslov netačan, bacamo RuntimeException pa ga funkcija testiraj() evidentira kao neuspjeh.
 *
 * Ulaz:
 *   $uslov - provjera koja treba biti tačna (true).
 *   $poruka - objašnjenje koje će biti prikazano ako uslov nije ispunjen.
 *
 * Izlaz: true ako je uslov ispunjen; u suprotnom baca RuntimeException.
 */
function mora_biti($uslov, $poruka)
{
    // Ako provjera nije tačna, prekidamo ovu akciju i prepuštamo testiraj() da ispiše neuspjeh.
    if (!$uslov) {
        throw new RuntimeException($poruka);
    }
    return true;
}

// Za svaki dio aplikacije pravimo po jedan objekat servisa.
// Testni pozivi idu direktno prema tim objektima; FlightPHP rute nam za ove provjere nisu potrebne.
$gradovi = new GradService();
$kategorije = new KategorijaService();
$korisnici = new KorisnikService();
$majstori = new MajstorService();
$veze = new MajstorKategorijaService();
$pretraga = new PretragaService();

// =====================================================================
// CRUD – provjeravamo dodavanje (CREATE), čitanje (READ), izmjenu (UPDATE) i brisanje (DELETE).
// Istovremeno provjeravamo poslovna pravila koja odbijaju neispravne izmjene ili brisanje.
// U pitanju su samo novi testni zapisi; brisanje na kraju radimo u redoslijedu koji čuva strane ključeve.
// =====================================================================
echo "\n=== CRUD – SAMO NOVI, PRIVREMENI TESTNI ZAPISI ===\n";
echo "UPOZORENJE: test privremeno upisuje/izmjenjuje/briše podatke.\n";

// Pravimo jedinstvenu oznaku da nazivi novih testnih gradova/kategorija i email ne odgovaraju postojećim podacima.
// ID-jeve nastalih zapisa čuvamo zasebno: po njima ćemo pratiti zavisnosti i na kraju obrisati testne zapise.
$oznaka = 'M3' . substr(str_replace('.', '', uniqid('', true)), -10);
$email = 'test-' . strtolower($oznaka) . '@primjer.ba';
$grad_id = null;
$korisnik_id = null;
$majstor_id = null;
$roditelj_id = null;
$dijete_id = null;

try {
    // 1) Pripremamo osnovne testne podatke: grad i dvije povezane kategorije (roditelj + dijete).
    // Roditelj ima roditelj_id = null, tj. privremeno je korijenska kategorija; dijete dobija ID tog roditelja.
    // ID-jeve pamtimo da kasnije možemo pozivati UPDATE/DELETE i očistiti samo testne zapise.
    $grad = testiraj('Grad CRUD: CREATE', fn() => $gradovi->add([
        'naziv' => 'Testni grad ' . $oznaka, 'latitude' => 43.856300, 'longitude' => 18.413100
    ]));
    $grad_id = $grad['id'] ?? null;

    $roditelj = testiraj('Kategorija CRUD: CREATE roditelja', fn() => $kategorije->add([
        'naziv' => 'Testna kategorija ' . $oznaka, 'roditelj_id' => null
    ]));
    $roditelj_id = $roditelj['id'] ?? null;

    if ($roditelj_id !== null) {
        $dijete = testiraj('Kategorija CRUD: CREATE potkategorije', fn() => $kategorije->add([
            'naziv' => 'Testna potkategorija ' . $oznaka, 'roditelj_id' => $roditelj_id
        ]));
        $dijete_id = $dijete['id'] ?? null;
    }

    // 2) Dodajemo korisnika s ulogom majstor. Običnu lozinku šaljemo servisu koji je treba pretvoriti u hash.
    // Nakon dodavanja pamtimo njegov ID, jer će kasnije testni majstorski profil pripadati tom korisniku.
    $korisnik = testiraj('Korisnik CRUD: CREATE uz hash lozinke', fn() => $korisnici->add([
        'email' => $email, 'lozinka' => 'TestLozinka123', 'uloga' => Roles::MAJSTOR
    ]));
    $korisnik_id = $korisnik['id'] ?? null;

    // 3) Nad upravo kreiranim zapisima provjeravamo čitanje, izmjenu i odbijanje nedozvoljenih operacija.
    // Na primjer: grad se može preimenovati, ali duplikat naziva nije dozvoljen; roditelj s djetetom se ne briše.
    // Izmjena roditelja tako da postane vlastiti potomak mora biti odbijena zbog ciklusa u stablu kategorija.
    if ($grad_id !== null) {
        testiraj('Grad CRUD: READ', fn() => mora_biti($gradovi->get_by_id($grad_id)['naziv'] === 'Testni grad ' . $oznaka, 'Naziv ne odgovara.'));
        testiraj('Grad CRUD: UPDATE', fn() => mora_biti($gradovi->update(['naziv' => 'Testni grad promjena ' . $oznaka], $grad_id)['naziv'] === 'Testni grad promjena ' . $oznaka, 'Izmjena nije spremljena.'));
        ocekuj_gresku('Grad: dupli naziv', fn() => $gradovi->add(['naziv' => 'Testni grad promjena ' . $oznaka, 'latitude' => 43.8, 'longitude' => 18.4]), ValidationException::class);
    }

    if ($dijete_id !== null && $roditelj_id !== null) {
        testiraj('Kategorija CRUD: READ i UPDATE', fn() => mora_biti(
            $kategorije->update(['naziv' => 'Preimenovana potkategorija ' . $oznaka], $dijete_id)['naziv'] === 'Preimenovana potkategorija ' . $oznaka,
            'Potkategorija nije preimenovana.'
        ));
        ocekuj_gresku('Kategorija: zabrana ciklusa', fn() => $kategorije->update(['roditelj_id' => $dijete_id], $roditelj_id), ValidationException::class);
        ocekuj_gresku('Kategorija: zabrana brisanja roditelja', fn() => $kategorije->delete($roditelj_id), ValidationException::class);
    }

    // Provjere lozinki i korisničkog računa: hash nije vidljiv u javnom odgovoru, a prijava traži ispravnu lozinku.
    // Nakon promjene emaila/lozinke provjeravamo da se sa novom lozinkom može prijaviti, a pogrešna se odbija.
    if ($korisnik_id !== null) {
        testiraj('Korisnik: hash nije u javnom odgovoru', fn() => mora_biti(!isset($korisnici->get_by_id($korisnik_id)['lozinka_hash']), 'Hash je izložen.'));
        testiraj('Korisnik: provjera lozinke preko hash vrijednosti', fn() => mora_biti($korisnici->provjeri_prijavu($email, 'TestLozinka123')['id'] == $korisnik_id, 'Prijava nije prošla.'));
        testiraj('Korisnik CRUD: UPDATE emaila', fn() => mora_biti(
            $korisnici->update(['email' => 'promjena-' . $email], $korisnik_id)['email'] === 'promjena-' . $email,
            'Email nije promijenjen.'
        ));
        ocekuj_gresku('Korisnik: odbijanje pogrešne lozinke', fn() => $korisnici->provjeri_prijavu('promjena-' . $email, 'pogresna'), UnauthorizedException::class);
        // P006: promjena lozinke traži staru lozinku i sprema novi hash.
        testiraj('Korisnik: promjena vlastite lozinke', function () use ($korisnici, $korisnik_id) {
            $korisnici->promijeni_lozinku($korisnik_id, 'TestLozinka123', 'NovaLozinka123');
            return true;
        });
        testiraj('Korisnik: prijava novom lozinkom', function () use ($korisnici, $korisnik_id, $email) {
            $rez = $korisnici->provjeri_prijavu('promjena-' . $email, 'NovaLozinka123');
            return mora_biti((int) $rez['id'] === (int) $korisnik_id, 'Nova lozinka nije prihvaćena.');
        });
    }

    // 4) Dodajemo profil majstora, vezan isključivo za testnog korisnika i testni grad.
    // Tako kasnije možemo provjeriti pravilo da se grad NE SMIJE obrisati dok ga koristi neki majstor.
    if ($korisnik_id !== null && $grad_id !== null) {
        $profil = testiraj('Majstor CRUD: CREATE', fn() => $majstori->add([
            'korisnik_id' => $korisnik_id, 'grad_id' => $grad_id, 'ime' => 'Test',
            'prezime' => 'Majstor', 'telefon' => '061 000 000', 'opis' => 'Privremeni testni profil.'
        ]));
        $majstor_id = $profil['id'] ?? null;
    }

    if ($majstor_id !== null) {
        testiraj('Majstor CRUD: READ javnog profila', fn() => mora_biti($majstori->get_by_id($majstor_id)['ime'] === 'Test', 'Profil ne odgovara.'));
        testiraj('Majstor CRUD: UPDATE', fn() => mora_biti($majstori->update(['ime' => 'Testirano'], $majstor_id)['ime'] === 'Testirano', 'Ime se nije promijenilo.'));
        // Posebne metode za budući korisnički dio aplikacije: pronađi profil po korisnik_id i izmijeni njegov opis.
        // Ovo još ne testira JWT ni ovlaštenja iz M4; samo servisnu logiku koja će se kasnije pozivati iz ruta.
        testiraj('Majstor: moj profil po korisničkom ID-u', function () use ($majstori, $korisnik_id, $majstor_id) {
            $rez = $majstori->get_by_korisnik_id($korisnik_id);
            return mora_biti((int) $rez['id'] === (int) $majstor_id, 'Profil nije pronađen.');
        });
        testiraj('Majstor: sačuvaj moj profil (PUT)', function () use ($majstori, $korisnik_id) {
            $rez = $majstori->sacuvaj_moj_profil($korisnik_id, ['opis' => 'Opis promijenjen u testu.']);
            return mora_biti($rez['opis'] === 'Opis promijenjen u testu.', 'Opis nije spremljen.');
        });
        ocekuj_gresku('Grad: zabrana brisanja zauzetog grada (409)', fn() => $gradovi->delete($grad_id), ConflictException::class);
    }

    // 5) Povezujemo majstora i kategorije kroz MAJSTOR_KATEGORIJE, koja ima složeni ključ (majstor_id, kategorija_id).
    // Ne možemo koristiti obični BaseService UPDATE po jednom ID-ju: koristimo metode s oba ključa i zamijeni_izbor().
    // Provjeravamo i da pretraga roditeljske kategorije pronalazi majstora koji je odabrao njeno dijete.
    if ($majstor_id !== null && $dijete_id !== null && $roditelj_id !== null) {
        testiraj('Veza CRUD: CREATE', fn() => $veze->add(['majstor_id' => $majstor_id, 'kategorija_id' => $dijete_id]));
        testiraj('Veza CRUD: READ po složenom ključu', fn() => mora_biti($veze->get_by_keys($majstor_id, $dijete_id)['kategorija_id'] == $dijete_id, 'Veza nije pronađena.'));
        ocekuj_gresku('Veza: zabrana dupliciranja', fn() => $veze->add(['majstor_id' => $majstor_id, 'kategorija_id' => $dijete_id]), ValidationException::class);
        testiraj('Pretraga: roditelj pronalazi izabrano dijete', function () use ($pretraga, $roditelj_id, $grad_id, $majstor_id) {
            $rez = $pretraga->pretrazi([$roditelj_id], $grad_id, null);
            foreach ($rez['majstori'] as $red) {
                if ((int) $red['id'] === (int) $majstor_id) {
                    return true;
                }
            }
            return false;
        });
        testiraj('Pretraga: grad i radijus 0 km', function () use ($pretraga, $dijete_id, $grad_id, $majstor_id) {
            $rez = $pretraga->pretrazi([$dijete_id], $grad_id, 0);
            foreach ($rez['majstori'] as $red) {
                if ((int) $red['id'] === (int) $majstor_id) {
                    return true;
                }
            }
            return false;
        });
        // Zamjena izbora majstorluka mora biti cjelovita: stare veze se zamijene novim u jednoj transakciji.
        // Ako operacija ne uspije, servis/DAO treba poništiti djelimične promjene umjesto ostavljanja polovičnog stanja.
        testiraj('Veza UPDATE: transakcijska zamjena izbora', function () use ($veze, $majstor_id, $roditelj_id, $dijete_id) {
            $rez = $veze->zamijeni_izbor($majstor_id, [$roditelj_id, $dijete_id]);
            return mora_biti(count($rez) === 2, 'Nisu upisane obje veze.');
        });
        // Istu mogućnost provjeravamo kroz MajstorService, koji će se kasnije koristiti za profil prijavljenog majstora.
        // Time testiramo putanju koju ćemo povezati s frontend formom u M4, bez promjene postojeće M2 pretrage.
        testiraj('Majstor: sačuvaj moje majstorluke', function () use ($majstori, $korisnik_id, $roditelj_id, $dijete_id) {
            $rez = $majstori->sacuvaj_moje_majstorluke($korisnik_id, [$roditelj_id, $dijete_id]);
            return mora_biti(count($rez) === 2, 'Nisu sačuvane obje kategorije.');
        });
        testiraj('Majstor: pročitaj moje majstorluke', function () use ($majstori, $korisnik_id) {
            $rez = $majstori->get_moji_majstorluci($korisnik_id);
            return mora_biti(count($rez) === 2, 'Majstorluci nisu ispravno pročitani.');
        });
        testiraj('Veza DELETE: brisanje po oba ključa', function () use ($veze, $majstor_id, $roditelj_id) {
            $veze->delete_by_keys($majstor_id, $roditelj_id);
            return mora_biti(count($veze->get_by_majstor_id($majstor_id)) === 1, 'Brisanje veze nije uspjelo.');
        });
        $obrisano = testiraj('Kategorija DELETE: brisanje lista i pripadajuće veze', function () use ($kategorije, $veze, $dijete_id, $majstor_id) {
            $kategorije->delete($dijete_id);
            return mora_biti(count($veze->get_by_majstor_id($majstor_id)) === 0, 'Veza nije uklonjena.');
        });
        if ($obrisano !== null) {
            $dijete_id = null;
        }
    }

    // 6) Redovno uklanjamo testne zapise obrnutim redoslijedom njihovih veza.
    // Veze i potkategoriju smo već uklonili; zatim brišemo korijensku kategoriju, profil, grad i korisnika.
    // Grad možemo obrisati tek kada više nema majstora vezanih za njega. Postojeće korisničke podatke ne diramo.
    if ($roditelj_id !== null) {
        $obrisano = testiraj('Kategorija CRUD: DELETE korijena', function () use ($kategorije, $roditelj_id) {
            $kategorije->delete($roditelj_id);
            return true;
        });
        if ($obrisano !== null) {
            $roditelj_id = null;
        }
    }

    if ($majstor_id !== null) {
        $obrisano = testiraj('Majstor CRUD: DELETE', function () use ($majstori, $majstor_id) {
            $majstori->delete($majstor_id);
            return true;
        });
        if ($obrisano !== null) {
            $majstor_id = null;
        }
    }
    if ($grad_id !== null) {
        $obrisano = testiraj('Grad CRUD: DELETE (grad više niko ne koristi)', function () use ($gradovi, $grad_id) {
            $gradovi->delete($grad_id);
            return true;
        });
        if ($obrisano !== null) {
            $grad_id = null;
        }
    }
    if ($korisnik_id !== null) {
        $obrisano = testiraj('Korisnik CRUD: DELETE', function () use ($korisnici, $korisnik_id) {
            $korisnici->delete($korisnik_id);
            return true;
        });
        if ($obrisano !== null) {
            $korisnik_id = null;
        }
    }
} finally {
    // finally se izvršava i nakon izuzetka iz gornjeg try bloka, pa pokušavamo ukloniti preostale testne zapise.
    // Varijable ID-jeva koje su uspješno obrisane ranije postavljene su na null, zato ih ovdje preskačemo.
    // Čišćenje se odnosi samo na ID-jeve kreirane u ovoj skripti, a svaki pokušaj ima vlastiti try/catch.
    // Ako neka operacija čišćenja prijavi grešku, ispisujemo UPOZORENJE: zapis možda ostaje u bazi.
    // Ako proces bude nasilno prekinut, finally se ne mora izvršiti; zato koristimo lokalnu bazu i rezervnu kopiju.
    echo "\n=== REZERVNO ČIŠĆENJE TESTNIH ZAPISA ===\n";
    if ($majstor_id !== null) {
        try { $majstori->delete($majstor_id); echo "Testni majstor uklonjen.\n"; }
        catch (Throwable $e) { echo 'UPOZORENJE (majstor): ' . $e->getMessage() . "\n"; }
    }
    if ($dijete_id !== null) {
        try { $kategorije->delete($dijete_id); echo "Testna potkategorija uklonjena.\n"; }
        catch (Throwable $e) { echo 'UPOZORENJE (potkategorija): ' . $e->getMessage() . "\n"; }
    }
    if ($roditelj_id !== null) {
        try { $kategorije->delete($roditelj_id); echo "Testni roditelj uklonjen.\n"; }
        catch (Throwable $e) { echo 'UPOZORENJE (roditelj): ' . $e->getMessage() . "\n"; }
    }
    if ($grad_id !== null) {
        try { $gradovi->delete($grad_id); echo "Testni grad uklonjen.\n"; }
        catch (Throwable $e) { echo 'UPOZORENJE (grad): ' . $e->getMessage() . "\n"; }
    }
    if ($korisnik_id !== null) {
        try { $korisnici->delete($korisnik_id); echo "Testni korisnik uklonjen.\n"; }
        catch (Throwable $e) { echo 'UPOZORENJE (korisnik): ' . $e->getMessage() . "\n"; }
    }
}

echo "\nREZULTAT: $uspjesno uspješno, $neuspjesno neuspješno.\n";
// Ovdje je samo zbir uspješnih/neuspješnih CRUD provjera. Ispis pretraga 7a–7c nalazi se u test_1_dohvat.php.
// Neuspješan test uzrokovat će izlazni kod 1, što omogućava i komandnoj liniji prepoznavanje greške.
exit($neuspjesno > 0 ? 1 : 0);
