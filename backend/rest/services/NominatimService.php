<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../utils/exceptions.php';
require_once __DIR__ . '/../../utils/Validator.php';

/**
 * Servis koji preko interneta pronalazi koordinate grada.
														
														
													  
																 
 *
 * Za zadani naziv poziva Nominatim API, provjerava status, vrijeme čekanja i sadržaj odgovora te vraća lokaciju za prikaz na
 * karti. Ne sprema grad u MySQL; to je zaseban korak nakon potvrde administratora.
															
 */
class NominatimService
{
    const UNAVAILABLE = 'Servis za pronalaženje gradova trenutno nije dostupan. Pokušajte kasnije.';

    /**
     * Traži lokaciju grada preko vanjskog Nominatim API-ja i vraća koordinate za prikaz na karti.
     *
     * Prvo provjerava uneseni naziv, zatim sastavlja URL za pretragu mjesta u Bosni i Hercegovini. Od dobijenih rezultata daje
     * prednost gradovima i naseljima, provjerava koordinate i vraća samo podatke koji trebaju frontendu. Ova metoda ne upisuje
     * ništa u bazu; administrator kasnije zasebno potvrđuje i sprema grad (P009, V004).
     *
     * Ulaz: $naziv – naziv mjesta koji je administrator unio.
     * Izlaz: Naziv, display_name, latitude i longitude; ili izuzetak ako nema valjanog rezultata.
     *
     * Povezana poslovna pravila:
     *   P009 – Administrator provjerava lokaciju prije dodavanja grada.
     *   V004 – Grad se sprema tek nakon potvrde pronađene lokacije.
     */
    public function pronadji_grad($naziv)
    {
        // Prije poziva vanjskog servisa čistimo i validiramo uneseni naziv.
        $naziv = trim((string) $naziv);
        Validator::length($naziv, 'naziv', 2, 100);

        // Koristimo isti osnovni način pretrage kao naš postojeći M1 frontend.
        // Sastavljamo URL s parametrima za pretragu mjesta u Bosni i Hercegovini.
        $url = rtrim(Config::NOMINATIM_URL(), '/') . '/search?' . http_build_query([
            'format' => 'jsonv2',
            'limit' => 5,
            'addressdetails' => 1,
            'countrycodes' => 'ba',
            'q' => $naziv . ', Bosnia and Herzegovina'
        ]);
        // Poziv vanjskog API-ja obavlja pomoćna metoda koja provjerava greške.
        $podaci = $this->request($url);
        if (count($podaci) === 0) {
            throw new NotFoundException('Grad nije pronađen. Provjerite naziv i pokušajte ponovo.');
        }

        // Prednost imaju gradovi i naselja (city/town/municipality/village), kao u našem JavaScriptu.
        $grad = null;
        foreach ($podaci as $mjesto) {
            $tip = $mjesto['type'] ?? $mjesto['addresstype'] ?? '';
            if (in_array($tip, ['city', 'town', 'municipality', 'village'], true)) {
                $grad = $mjesto;
                break;
            }
        }
        // Ako nijedan rezultat nema željeni tip, uzimamo prvi pronađeni.
        if ($grad === null) {
            $grad = $podaci[0];
        }
        // Provjeravamo da je Nominatim zaista vratio brojčane koordinate.
        if (!isset($grad['lat'], $grad['lon']) || !is_numeric($grad['lat']) || !is_numeric($grad['lon'])) {
            error_log('Nominatim je vratio nepotpun rezultat bez koordinata.');
            throw new ExternalServiceException(self::UNAVAILABLE);
        }

        // Vraćamo samo osnovne podatke potrebne za kartu; ne dodajemo grad u bazu.
        return [
            'naziv' => $naziv,
            'display_name' => $grad['display_name'] ?? $naziv,
            'latitude' => round((float) $grad['lat'], 6),
            'longitude' => round((float) $grad['lon'], 6)
        ];
    }

    /**
     * Šalje HTTP GET zahtjev Nominatimu i provjerava je li odgovor valjan.
     *
     * cURL koristi podešenu URL adresu, User-Agent i vremensko ograničenje. Nakon slanja provjerava mrežne greške i HTTP status
     * 200, a zatim pokušava pretvoriti JSON odgovor u PHP niz. Problem bilježi kroz error_log() i vraća razumljivu servisnu
     * grešku, umjesto nejasnog tehničkog odgovora.
     *
     * Ulaz: $url – potpuna URL adresa HTTP zahtjeva.
     * Izlaz: Dekodirani PHP niz ili ExternalServiceException kada vanjski servis zakaže.
     */
    private function request($url)
    {
        // Pripremamo HTTP poziv preko PHP cURL biblioteke.
        $curl = curl_init($url);
        // Postavljamo prihvat JSON-a, User-Agent i vremensko ograničenje.
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => (int) Config::NOMINATIM_TIMEOUT(),
            CURLOPT_USERAGENT => Config::NOMINATIM_USER_AGENT(),
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: bs']
        ]);

        // Izvršavamo zahtjev, zatim čitamo eventualnu grešku i HTTP status.
        $body = curl_exec($curl);
        $greska = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // Mrežna greška ili istek vremena se pretvara u razumljivu servisnu grešku.
        if ($body === false) {
            error_log("Nominatim poziv nije uspio: $greska");
            throw new ExternalServiceException(self::UNAVAILABLE);
        }
        // Provjeravamo da je vanjski server vratio uspješan HTTP status 200.
        if ($status !== 200) {
            error_log("Nominatim je vratio HTTP status $status");
            throw new ExternalServiceException(self::UNAVAILABLE);
        }
        // Nakon uspješnog odgovora pokušavamo pročitati JSON kao PHP niz.
        $podaci = json_decode($body, true);
        if (!is_array($podaci)) {
            error_log('Nominatim je vratio neispravan JSON.');
            throw new ExternalServiceException(self::UNAVAILABLE);
        }
        return $podaci;
    }
}
