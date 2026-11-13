<?php
/**
 * TEST 1 – ČITANJE PODATAKA, VALIDACIJE I PRETRAGA MAJSTORA (M3).
 *
 * Svrha: provjeriti da naši servisi ispravno čitaju postojeće podatke, vraćaju očekivane strukture
 * i odbijaju neispravne ulaze. Servisi dalje koriste DAO sloj i MySQL bazu iz M2.
 *
 * Način testiranja preuzet je kao ideja iz profesoricinog week-06/testv3.php: direktno pozivamo servise,
 * bez FlightPHP ruta i Swaggera. Za naš projekat dodali smo pomoćne funkcije za pregledniji ispis.
 *
 * Ovdje NE NAMJERAVAMO MIJENJATI BAZU: rade se čitanja i provjere ulaza za koje servis treba odbiti zahtjev
 * prije upisa. Negativni testovi pozivaju metode kao što su add(), ali očekuju da budu odbijene validacijom.
 *
 * Posebno se ispisuju M2 pretrage 7a, 7b i 7c: traženi majstorluci, grad, radijus, očekivani majstori
 * i stvarni rezultat. Imena i ID-jeve upoređujemo RUČNO; uspješan poziv ne garantuje podudaranje s očekivanjem.
 * Očekivani rezultati važe za početne M2 podatke i mogu se promijeniti ako izmijenimo sadržaj baze.
 *
 * Pokretanje iz glavnog foldera MajstoriWebApp:
 *     php backend/rest/services/test_1_dohvat.php
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
require_once __DIR__ . '/StatistikaService.php';
require_once __DIR__ . '/NominatimService.php';

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

/**
 * POMOĆNA FUNKCIJA: pregledno ispisuje jednu pretragu majstora iz našeg M2 testa (7a, 7b ili 7c).
 *
 * Svrha: usporedo prikazati filtere, očekivane majstore i ono što PretragaService stvarno vrati.
 * Prvo ispisujemo opis testnog slučaja, ID-jeve kategorija, grad i radijus, pa očekivane rezultate.
 * Zatim pozivamo PretragaService::pretrazi(), provjerimo osnovnu strukturu odgovora ('majstori', 'count')
 * i ispišemo svakog pronađenog majstora: ID, ime, prezime, grad, odabrane kategorije i udaljenost ako je tražena.
 *
 * Ovdje NAMJERNO NE POREĐUJEMO automatski očekivane i stvarne ID-jeve ili broj pronađenih majstora.
 * Ispis 'izvršeno bez greške' potvrđuje samo da je servis vratio ispravno oblikovan odgovor.
 * Student ručno provjerava jesu li majstori i udaljenosti u skladu s očekivanjima iz M2.
 *
 * Ulaz:
 *   $oznaka         - oznaka testa, npr. '7a'.
 *   $filteri         - opis odabranih kategorija i lokacije za prikaz.
 *   $kategorija_ids  - niz ID-jeva traženih kategorija, npr. [15, 18].
 *   $grad_id         - ID polaznog grada ili null ako nema ograničenja grada.
 *   $radijus_km      - radijus u kilometrima ili null ako nema radijusa.
 *   $ocekivano       - tekst s očekivanim majstorima iz M2.
 *   $ne_ocekivano    - tekst s majstorima koji ne trebaju biti u rezultatu.
 *   $objasnjenje     - objašnjenje zašto očekujemo baš takve rezultate.
 *   $pretraga        - objekat PretragaService preko kojeg se izvodi pretraga.
 *
 * Izlaz: ne vraća vrijednost; ispisuje rezultate u terminal i ažurira brojače testova.
 */
function prikazi_pretragu_m2($oznaka, $filteri, $kategorija_ids, $grad_id, $radijus_km,
                            $ocekivano, $ne_ocekivano, $objasnjenje, $pretraga)
{
    global $uspjesno, $neuspjesno;

    // Prvo prikazujemo koji M2 slučaj testiramo i koje filtere šaljemo servisu.
    echo "\n--- PRETRAGA {$oznaka}: {$filteri} ---\n";
    echo "ULAZ: kategorije = " . json_encode($kategorija_ids) . ", grad_id = "
        . ($grad_id === null ? 'NULL' : $grad_id) . ", radijus_km = "
        . ($radijus_km === null ? 'NULL' : $radijus_km) . "\n";
    // Očekivani rezultati su zapisani kao tekst iz M2 testa; ne koriste se u automatskom poređenju.
    echo "OČEKIVANO (prema M2 početnim podacima): {$ocekivano}\n";
    echo "NE TREBA BITI: {$ne_ocekivano}\n";
    echo "OBJAŠNJENJE: {$objasnjenje}\n";
    echo "STVARNI REZULTATI SERVISA:\n";

    try {
        // Poziv ide SERVISU; on validira filtere i zove MajstorDao::pretrazi().
        $odgovor = $pretraga->pretrazi($kategorija_ids, $grad_id, $radijus_km);
        // Prvo provjeravamo samo oblik odgovora: lista majstora i ukupan broj rezultata moraju postojati.
        if (!isset($odgovor['majstori'], $odgovor['count']) || !is_array($odgovor['majstori'])) {
            throw new RuntimeException('Servis nije vratio očekivana polja majstori i count.');
        }

        // Svaki majstor se ispisuje kao u M2: ID, ime, grad i direktni majstorluci.
        // Ako je zadan radijus, ispisuje se i zračna udaljenost u kilometrima.
        if (count($odgovor['majstori']) === 0) {
            echo "  (nema pronađenih majstora)\n";
        }
        foreach ($odgovor['majstori'] as $m) {
            echo '  ' . ($m['id'] ?? '?') . ' - '
                . ($m['ime'] ?? '?') . ' ' . ($m['prezime'] ?? '')
                . ' (' . ($m['grad_naziv'] ?? 'grad nepoznat') . ')'
                . ' | Odabrani majstorluci: ' . ($m['majstorluci_nazivi'] ?? '(nema)');
            if ($radijus_km !== null) {
                echo ' | Udaljenost: ' . ($m['udaljenost_km'] ?? '?') . ' km';
            }
            echo "\n";
        }
        echo 'UKUPNO: ' . $odgovor['count'] . " pronađenih majstora.\n";
        // Ovdje ne zaključujemo da je pretraga sadržajno tačna; samo potvrđujemo da je poziv uspio.
        echo "STATUS POZIVA: izvršeno bez greške; rezultate ručno uporedi s OČEKIVANO.\n";
        $uspjesno++;
    } catch (Throwable $e) {
        echo 'GREŠKA POZIVA: ' . get_class($e) . ': ' . $e->getMessage() . "\n";
        $neuspjesno++;
    }
}

// Za svaki dio aplikacije pravimo po jedan objekat servisa.
// Testni pozivi idu direktno prema tim objektima; FlightPHP rute nam za ove provjere nisu potrebne.
$gradovi = new GradService();
$kategorije = new KategorijaService();
$korisnici = new KorisnikService();
$majstori = new MajstorService();
$veze = new MajstorKategorijaService();
$pretraga = new PretragaService();
$statistika = new StatistikaService();

// =====================================================================
// 1) READ – čitanje postojećih zapisa i provjera dodatnih metoda za pregled podataka.
// Primjeri: svi gradovi, jedna kategorija, javni profil, statistika i korisnici bez hashova lozinke.
// =====================================================================
echo "\n=== 1. ČITANJE PODATAKA I POSTOJEĆE DAO METODE ===\n";
$lista_gradova = testiraj('GradService: GET svi gradovi', fn() => $gradovi->get_all());
$lista_kategorija = testiraj('KategorijaService: GET sve kategorije', fn() => $kategorije->get_all());
$lista_korisnika = testiraj('KorisnikService: GET svi korisnici bez hashova', function () use ($korisnici) {
    $niz = $korisnici->get_all();
    foreach ($niz as $red) {
        mora_biti(!array_key_exists('lozinka_hash', $red), 'Hash ne smije biti u odgovoru.');
    }
    return $niz;
});
$lista_majstora = testiraj('MajstorService: GET svi profili', fn() => $majstori->get_all());
testiraj('MajstorKategorijaService: GET sve veze', fn() => $veze->get_all());
testiraj('StatistikaService: brojači početne stranice', function () use ($statistika) {
    $s = $statistika->get_stats();
    return mora_biti(isset($s['gradovi'], $s['kategorije'], $s['majstori']), 'Nedostaje brojač.');
});
testiraj('PretragaService: bez filtera (broj odgovara listi)', function () use ($pretraga) {
    $rezultat = $pretraga->pretrazi();
    return mora_biti(isset($rezultat['majstori'], $rezultat['count']) &&
        $rezultat['count'] === count($rezultat['majstori']), 'Broj rezultata nije ispravan.');
});

// Namjenske metode za pojedinačne zapise testiramo samo ako postoje početni podaci.
if (!empty($lista_gradova)) {
    testiraj('GradService: GET jedan postojeći grad', fn() => $gradovi->get_by_id($lista_gradova[0]['id']));
}
// Javni profil mora pored osnovnih polja prikazivati i odabrane majstorluke.
if (!empty($lista_majstora)) {
    testiraj('MajstorService: javni profil s majstorlucima', function () use ($majstori, $lista_majstora) {
        $profil = $majstori->get_by_id($lista_majstora[0]['id']);
        return mora_biti(isset($profil['majstorluci'], $profil['kategorija_ids']), 'Nedostaju kategorije.');
    });
    testiraj('MajstorKategorijaService: direktne veze profila', fn() => $veze->get_by_majstor_id($lista_majstora[0]['id']));
}
// Posebno provjeravamo da servis ne vraća lozinka_hash u javnim rezultatima.
// Ovo nije test samog hashiranja; samo zaštite osjetljivog polja pri dohvatu podataka.
if (!empty($lista_korisnika)) {
    testiraj('KorisnikService: GET korisnik bez hashova', function () use ($korisnici, $lista_korisnika) {
        $korisnik = $korisnici->get_by_id($lista_korisnika[0]['id']);
        return mora_biti(!isset($korisnik['lozinka_hash']), 'Hash je izložen.');
    });
}

// ---------------------------------------------------------------------
// DODATNE READ METODE: čitamo iste postojeće podatke, bez bilo kakvog upisa.
// Korijenske kategorije su one bez roditelja; get_children(ID) vraća samo direktne potkategorije.
// ---------------------------------------------------------------------
if (!empty($lista_kategorija)) {
    testiraj('KategorijaService: korijenske kategorije', fn() => $kategorije->get_children());
    testiraj('KategorijaService: djeca jedne kategorije', fn() => $kategorije->get_children($lista_kategorija[0]['id']));
    testiraj('MajstorKategorijaService: veze po kategoriji', fn() => $veze->get_by_kategorija_id($lista_kategorija[0]['id']));
}
if (!empty($lista_korisnika)) {
    testiraj('KorisnikService: GET po emailu bez hasha', function () use ($korisnici, $lista_korisnika) {
        $rez = $korisnici->get_by_email($lista_korisnika[0]['email']);
        return mora_biti($rez !== null && !isset($rez['lozinka_hash']), 'Email ne postoji ili je hash izložen.');
    });
    testiraj('KorisnikService: GET sa profilom bez hasha', function () use ($korisnici, $lista_korisnika) {
        $rez = $korisnici->get_by_id_sa_profilom($lista_korisnika[0]['id']);
        return mora_biti(array_key_exists('profil', $rez) && !isset($rez['lozinka_hash']), 'Nedostaje profil ili je hash izložen.');
    });
}
// Provjera pregleda po ulozi: i ovdje servis ne smije izložiti hash lozinke.
testiraj('KorisnikService: pregled po ulozi bez hasha', function () use ($korisnici) {
    $rez = $korisnici->get_by_uloga(Roles::MAJSTOR);
    foreach ($rez as $red) {
        mora_biti(!isset($red['lozinka_hash']), 'Hash je izložen kroz get_by_uloga().');
    }
    return true;
});
testiraj('KorisnikService: GET svi sa profilima', function () use ($korisnici) {
    $rez = $korisnici->get_all_sa_profilima();
    foreach ($rez as $red) {
        mora_biti(array_key_exists('profil', $red) && !isset($red['lozinka_hash']), 'Neispravan javni odgovor.');
    }
    return true;
});
if (!empty($lista_majstora)) {
    testiraj('MajstorService: GET po korisničkom ID-u', function () use ($majstori, $lista_majstora) {
        $profil = $majstori->get_by_korisnik_id($lista_majstora[0]['korisnik_id']);
        return mora_biti((int) $profil['id'] === (int) $lista_majstora[0]['id'], 'Nije vraćen traženi profil.');
    });
}

// ---------------------------------------------------------------------
// PRETRAGE 7a, 7b i 7c – IDENTIČNI FILTERI iz našeg DAO M2 test.php.
// Cilj je potvrditi da je servis zadržao već testiranu logiku M2, uključujući hijerarhiju kategorija.
// Posebno su važne jer provjeravaju hijerarhiju majstorluka i udaljenosti.
// Ovdje nema INSERT/UPDATE/DELETE, pa su ovi primjeri u testu za dohvat.
// Očekivanja se odnose na početne M2 testne podatke.
// ---------------------------------------------------------------------
echo "\n=== 2. PRETRAGA MAJSTORA – PRIMJERI 7a, 7b, 7c IZ M2 ===\n";

// 7a: Traže se Alarmni sistemi i Video-nadzor, bez ograničenja grada.
// Alen je odabrao samo njihove PRETKE, pa ne treba biti u rezultatu.
prikazi_pretragu_m2(
    '7a', 'Alarmni sistemi (15) + Video-nadzor (18), bez grada i radijusa',
    [15, 18], null, null,
    'Amir (ID 1), Adis (ID 4), Eldin (ID 6)',
    'Alen (ID 11)',
    'Majstor se pronađe ako je direktno odabrao traženu kategoriju ili njenog potomka; odabir pretka nije dovoljan.',
    $pretraga
);

// 7b: Traži se Niska struja (roditelj, ID 12). Alen je direktno odabrao 12,
// pa se sada pojavljuje uz majstore koji su odabrali njene potomke.
prikazi_pretragu_m2(
    '7b', 'Niska struja (12), bez grada i radijusa',
    [12], null, null,
    'Amir (ID 1), Adis (ID 4), Eldin (ID 6), Alen (ID 11)',
    'Niko od navedena četiri majstora',
    'Alen ulazi u rezultate jer je eksplicitno odabrao baš kategoriju 12.',
    $pretraga
);

// 7c: Ista kategorija kao 7b, ali iz Tuzle (grad ID 4), radijus 100 km.
// Udaljenost je zračna, iz koordinata u MySQL bazi – BEZ Nominatima.
prikazi_pretragu_m2(
    '7c', 'Niska struja (12), Tuzla (grad 4), radijus 100 km',
    [12], 4, 100,
    'Amir (ID 1, oko 78,5 km), Adis (ID 4, 0 km), Alen (ID 11, 0 km)',
    'Eldin (ID 6, Mostar je izvan 100 km od Tuzle)',
    'Tražene kategorije + krug 100 km oko Tuzle; SQL računa udaljenost iz baze.',
    $pretraga
);

// Negativni testovi: šaljemo podatke koje servisi moraju odbiti validacijom ili odgovarajućom greškom.
// Ako očekivani izuzetak nastane, to je OK; ove akcije ne bi trebale dovesti do upisa u bazu.
echo "\n=== 3. NEISPRAVNI PODACI (OČEKIVANE GREŠKE, BEZ UPISA) ===\n";
ocekuj_gresku('Grad: naziv je obavezan', fn() => $gradovi->add(['naziv' => '', 'latitude' => 43.8, 'longitude' => 18.4]), ValidationException::class);
ocekuj_gresku('Grad: latitude izvan raspona', fn() => $gradovi->add(['naziv' => 'TestGrad', 'latitude' => 100, 'longitude' => 18.4]), ValidationException::class);
ocekuj_gresku('Kategorija: prazan naziv', fn() => $kategorije->add(['naziv' => '']), ValidationException::class);
ocekuj_gresku('Korisnik: neispravan email', fn() => $korisnici->add(['email' => 'nije-email', 'lozinka' => 'Lozinka123']), ValidationException::class);
ocekuj_gresku('Korisnik: kratka lozinka', fn() => $korisnici->add(['email' => 'm3@example.com', 'lozinka' => '123']), ValidationException::class);
ocekuj_gresku('Majstor: nedostaju obavezna polja', fn() => $majstori->add(['ime' => 'Test']), ValidationException::class);
ocekuj_gresku('Pretraga: radijus bez grada', fn() => $pretraga->pretrazi([], null, 25), ValidationException::class);
ocekuj_gresku('Pretraga: neispravan ID kategorije', fn() => $pretraga->pretrazi(['abc']), ValidationException::class);
ocekuj_gresku('Nominatim: prazan naziv (bez internetskog poziva)', fn() => (new NominatimService())->pronadji_grad(''), ValidationException::class);
ocekuj_gresku('Grad: nepostojeći ID', fn() => $gradovi->get_by_id(999999999), NotFoundException::class);
// Ne smije se dozvoliti nepostojeća uloga, neispravan radijus ili nepostojeći grad.
ocekuj_gresku('Korisnik: nedozvoljena uloga student', fn() => $korisnici->add(['email' => 'm3-nova-uloga@primjer.ba', 'uloga' => 'student', 'lozinka' => 'TestLozinka123']), ValidationException::class);
ocekuj_gresku('Pretraga: negativan radijus', fn() => $pretraga->pretrazi([], 4, -1), ValidationException::class);

// Broj uspjeha/neuspjeha odnosi se na izvršavanje testova. Kod pretraga 7a–7c 'OK' znači uspješan POZIV,
// ne automatsku provjeru da su pronađeni baš očekivani majstori.
// Očekivana imena/ID-jeve i stvarni ispis treba ručno uporediti.
echo "\nREZULTAT: $uspjesno uspješno, $neuspjesno neuspješno.\n";
exit($neuspjesno > 0 ? 1 : 0);
