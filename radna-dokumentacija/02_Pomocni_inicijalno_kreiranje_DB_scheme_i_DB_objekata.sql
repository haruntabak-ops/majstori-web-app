-- Pomocni radni fajl za kreiranje i razvoj baze podataka projekta Majstori Web App i njeno testiranje.
-- Fajl ce se postepeno dopunjavati SQL naredbama za kreiranje i izmjene sheme majstori_web_app i njenih objekata tokom razvoja projekta.
-- Shema se trenutno kreira na lokalnom MySQL serveru na mom racunaru.

CREATE SCHEMA IF NOT EXISTS majstori_web_app
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE majstori_web_app;

-- 1. Provjera korisnika: prikazuje naloge i njihove uloge.
SELECT id, email, uloga, now() as datum_i_vrijeme_eksporta
FROM KORISNICI
ORDER BY id;
/*
1,harun.tabak@primjer.ba,admin,2026-10-30 22:36:34
2,amir.elektrovic@primjer.ba,majstor,2026-10-30 22:36:34
3,suljo.grijanjic@primjer.ba,majstor,2026-10-30 22:36:34
4,mirza.kablovic@primjer.ba,majstor,2026-10-30 22:36:34
5,adis.alarmovic@primjer.ba,majstor,2026-10-30 22:36:34
6,tarik.strujic@primjer.ba,majstor,2026-10-30 22:36:34
7,eldin.kameric@primjer.ba,majstor,2026-10-30 22:36:34
8,faris.vodenic@primjer.ba,majstor,2026-10-30 22:36:34
9,kenan.radijatorovic@primjer.ba,majstor,2026-10-30 22:36:34
10,damir.podovic@primjer.ba,majstor,2026-10-30 22:36:34
11,nedim.zidaric@primjer.ba,majstor,2026-10-30 22:36:34
12,alen.elektrovic@primjer.ba,majstor,2026-10-30 22:36:34
13,jasmin.curic@primjer.ba,majstor,2026-10-30 22:36:34
 */


-- 2. Provjera gradova: prikazuje gradove i njihove geografske koordinate.
SELECT id, naziv, latitude, longitude, now() as datum_i_vrijeme_eksporta
FROM GRADOVI
ORDER BY id;
/*
1,Sarajevo,43.856300,18.413100,2026-10-30 22:37:11
2,Zenica,44.203400,17.907700,2026-10-30 22:37:11
3,Mostar,43.343800,17.807800,2026-10-30 22:37:11
4,Tuzla,44.538400,18.667100,2026-10-30 22:37:11
5,Bihać,44.816700,15.870800,2026-10-30 22:37:11
 */


-- 3. Provjera hijerarhije majstorluka: prikazuje kategorije uvučene prema nivou stabla.
WITH RECURSIVE stablo AS (
    SELECT
        id,
        roditelj_id,
        naziv,
        0 AS nivo,
        CAST(LPAD(id, 10, '0') AS CHAR(2000)) AS redoslijed
    FROM KATEGORIJE
    WHERE roditelj_id IS NULL
    UNION ALL
    SELECT
        k.id,
        k.roditelj_id,
        k.naziv,
        s.nivo + 1,
        CONCAT(s.redoslijed, '.', LPAD(k.id, 10, '0'))
    FROM KATEGORIJE k
    INNER JOIN stablo s ON k.roditelj_id = s.id
)
SELECT
    CONCAT(REPEAT('    ', nivo), naziv) AS hijerarhija,
    id,
    roditelj_id,
    nivo,
    now() as datum_i_vrijeme_eksporta
FROM stablo
ORDER BY redoslijed;
/*
Sva majstorstva,1,,0,2026-10-30 22:37:41
    Električar,10,1,1,2026-10-30 22:37:41
        Niska struja,12,10,2,2026-10-30 22:37:41
            Alarmni sistemi,15,12,3,2026-10-30 22:37:41
            Video-nadzor,18,12,3,2026-10-30 22:37:41
                IP kamere,27,18,4,2026-10-30 22:37:41
                Analogni sistemi,29,18,4,2026-10-30 22:37:41
        Mrežne instalacije,20,10,2,2026-10-30 22:37:41
            LAN kabliranje,21,20,3,2026-10-30 22:37:41
            Mrežni ormari,22,20,3,2026-10-30 22:37:41
        Visoka struja,30,10,2,2026-10-30 22:37:41
    Vodoinstalater,40,1,1,2026-10-30 22:37:41
        Vodovodne instalacije,42,40,2,2026-10-30 22:37:41
            Ugradnja cijevi,43,42,3,2026-10-30 22:37:41
            Popravka curenja,44,42,3,2026-10-30 22:37:41
    Grijanje,45,1,1,2026-10-30 22:37:41
        Radijatori,46,45,2,2026-10-30 22:37:41
        Podno grijanje,47,45,2,2026-10-30 22:37:41
    Zidar,60,1,1,2026-10-30 22:37:41
*/

-- 4. Provjera profila majstora: korisnik, ime i prezime majstora te grad u kojem radi.
 */
SELECT
    m.id AS majstor_id,
    k.email,
    m.ime,
    m.prezime,
    m.telefon,
    g.naziv AS grad,
    m.opis,
    now() as datum_i_vrijeme_eksporta
FROM MAJSTORI m
    INNER JOIN KORISNICI k ON k.id = m.korisnik_id
    INNER JOIN GRADOVI g ON g.id = m.grad_id
ORDER BY m.prezime, m.ime;
/*
4,adis.alarmovic@primjer.ba,Adis,Alarmović,061 000 104,Tuzla,"Ugrađujem i podešavam alarmne sisteme za kuće, stanove i poslovne prostore. Pomažem pri odabiru odgovarajuće opreme i provjeravam da li sistem pravilno funkcioniše.",2026-10-30 22:38:29
12,jasmin.curic@primjer.ba,Jasmin,Curić,061 000 112,Bihać,Pomažem pri otkrivanju i popravci curenja na postojećim vodovodnim instalacijama. Pregledam problematične spojeve i dijelove instalacije te nastojim otkloniti kvar bez nepotrebnih radova na ostatku sistema.,2026-10-30 22:38:29
11,alen.elektrovic@primjer.ba,Alen,Elektrović,061 000 111,Tuzla,"Bavim se električarskim radovima i instalacijama niske struje, uključujući postavljanje i provjeru opreme za slabostrujne sisteme. Vodim računa o pravilnom povezivanju i pouzdanom radu instalacija.",2026-10-30 22:38:29
1,amir.elektrovic@primjer.ba,Amir,Elektrović,061 000 101,Sarajevo,"Bavim se ugradnjom i podešavanjem IP kamera za video-nadzor, kao i mrežnim instalacijama. Pomažem pri postavljanju mrežne opreme i povezivanju uređaja kako bi sistem radio pouzdano i stabilno.",2026-10-30 22:38:29
2,suljo.grijanjic@primjer.ba,Suljo,Grijanjić,061 000 102,Zenica,"Bavim se poslovima vezanim za grijanje, održavanjem sistema grijanja i otklanjanjem problema u njihovom radu. Također popravljam curenja na postojećim vodovodnim instalacijama. Ne nudim ugradnju novih cijevi.",2026-10-30 22:38:29
3,mirza.kablovic@primjer.ba,Mirza,Kablović,061 000 103,Sarajevo,"Bavim se postavljanjem LAN kablova, povezivanjem mrežnih uređaja i organizacijom mrežnih ormara. Pomažem pri uspostavljanju uredne i pouzdane lokalne računarske mreže.",2026-10-30 22:38:29
6,eldin.kameric@primjer.ba,Eldin,Kamerić,061 000 106,Mostar,"Bavim se ugradnjom IP kamera i analognih sistema video-nadzora. Pomažem pri postavljanju kamera, povezivanju opreme i provjeri kvaliteta slike.",2026-10-30 22:38:29
9,damir.podovic@primjer.ba,Damir,Podović,061 000 109,Mostar,Bavim se ugradnjom i održavanjem sistema podnog grijanja. Pomažem pri postavljanju sistema i provjeri njegovog rada kako bi toplota bila ravnomjerno raspoređena.,2026-10-30 22:38:29
8,kenan.radijatorovic@primjer.ba,Kenan,Radijatorović,061 000 108,Sarajevo,"Bavim se poslovima vezanim za radijatorsko grijanje. Pomažem pri montaži i zamjeni radijatora, provjeri njihovog rada i otklanjanju uobičajenih problema sa zagrijavanjem prostorija.",2026-10-30 22:38:29
5,tarik.strujic@primjer.ba,Tarik,Strujić,061 000 105,Zenica,"Izvodim električarske radove na instalacijama visoke struje, uključujući postavljanje i održavanje odgovarajuće električne opreme. Vodim računa o sigurnosti i urednoj izvedbi radova.",2026-10-30 22:38:29
7,faris.vodenic@primjer.ba,Faris,Vodenić,061 000 107,Bihać,"Bavim se vodovodnim instalacijama i ugradnjom cijevi za dovod i odvod vode. Radim na novim instalacijama i zamjeni dotrajalih dijelova, uz pažnju na kvalitet spojeva.",2026-10-30 22:38:29
10,nedim.zidaric@primjer.ba,Nedim,Zidarić,061 000 110,Zenica,"Izvodim zidarske radove na stambenim i drugim objektima. Bavim se zidanjem, popravkama zidova i pripremom površina za nastavak građevinskih radova.",2026-10-30 22:38:29
*/

-- 5. Primjer: prikazuje majstorluke (kategorije) koje pripadaju jednom majstoru.
-- Promijeni email u WHERE uslovu ako želiš provjeriti drugog majstora.
SELECT
    k.email,
    m.ime,
    m.prezime,
    kat.naziv AS majstorluk,
    now() as datum_i_vrijeme_eksporta
FROM MAJSTORI m
    INNER JOIN KORISNICI k ON k.id = m.korisnik_id
    INNER JOIN MAJSTOR_KATEGORIJE mk ON mk.majstor_id = m.id
    INNER JOIN KATEGORIJE kat ON kat.id = mk.kategorija_id
WHERE k.email = 'amir.elektrovic@primjer.ba'
ORDER BY kat.naziv;
/*
amir.elektrovic@primjer.ba,Amir,Elektrović,IP kamere,2026-10-30 22:38:47
amir.elektrovic@primjer.ba,Amir,Elektrović,Mrežne instalacije,2026-10-30 22:38:47
*/

-- 6. Provjera svih veza između majstora i kategorija, uključujući grad.
SELECT
    m.id AS majstor_id,
    CONCAT(m.ime, ' ', m.prezime) AS majstor,
    g.naziv AS grad,
    kat.naziv AS majstorluk,
    now() as datum_i_vrijeme_eksporta
FROM MAJSTOR_KATEGORIJE mk
    INNER JOIN MAJSTORI m ON m.id = mk.majstor_id
    INNER JOIN GRADOVI g ON g.id = m.grad_id
    INNER JOIN KATEGORIJE kat ON kat.id = mk.kategorija_id
ORDER BY m.prezime, m.ime, kat.naziv;
/*
4,Adis Alarmović,Tuzla,Alarmni sistemi,2026-10-30 22:39:08
12,Jasmin Curić,Bihać,Popravka curenja,2026-10-30 22:39:08
11,Alen Elektrović,Tuzla,Električar,2026-10-30 22:39:08
11,Alen Elektrović,Tuzla,Niska struja,2026-10-30 22:39:08
1,Amir Elektrović,Sarajevo,IP kamere,2026-10-30 22:39:08
1,Amir Elektrović,Sarajevo,Mrežne instalacije,2026-10-30 22:39:08
2,Suljo Grijanjić,Zenica,Grijanje,2026-10-30 22:39:08
2,Suljo Grijanjić,Zenica,Popravka curenja,2026-10-30 22:39:08
3,Mirza Kablović,Sarajevo,LAN kabliranje,2026-10-30 22:39:08
3,Mirza Kablović,Sarajevo,Mrežni ormari,2026-10-30 22:39:08
6,Eldin Kamerić,Mostar,Analogni sistemi,2026-10-30 22:39:08
6,Eldin Kamerić,Mostar,IP kamere,2026-10-30 22:39:08
9,Damir Podović,Mostar,Podno grijanje,2026-10-30 22:39:08
8,Kenan Radijatorović,Sarajevo,Radijatori,2026-10-30 22:39:08
5,Tarik Strujić,Zenica,Visoka struja,2026-10-30 22:39:08
7,Faris Vodenić,Bihać,Ugradnja cijevi,2026-10-30 22:39:08
10,Nedim Zidarić,Zenica,Zidar,2026-10-30 22:39:08
*/


-- 7a. Provjera pretrage majstora po stablu majstorluka - dio prvi (2 kategorije istog roditelja)

-- PARAMETRI PRETRAGE
SET @kategorije = JSON_ARRAY(15,18); -- Lista ID-jeva kategorija (15-Alarmni sistemi, 18-Video nadzor)
SET @grad_id = NULL;              -- NULL = svi gradovi
SET @radijus_km = NULL;           -- NULL = bez radijusa

-- PRETRAGA MAJSTORA
WITH RECURSIVE podstablo AS (
    -- Odabrane kategorije
    SELECT k.id
    FROM KATEGORIJE k
    JOIN JSON_TABLE(
        @kategorije,
        '$[*]' COLUMNS (id INT PATH '$')
    ) t ON t.id = k.id
    UNION DISTINCT
    -- Svi potomci odabranih kategorija
    SELECT k.id
    FROM KATEGORIJE k
    JOIN podstablo p ON k.roditelj_id = p.id
),
rezultati AS (
    SELECT
        m.id,
        m.ime,
        m.prezime,
        g.naziv AS grad,
        -- Stvarno označeni majstorluci
        (
            SELECT GROUP_CONCAT(
                kat.naziv ORDER BY kat.naziv SEPARATOR ', '
            )
            FROM MAJSTOR_KATEGORIJE mk
            JOIN KATEGORIJE kat ON kat.id = mk.kategorija_id
            WHERE mk.majstor_id = m.id
        ) AS majstorluci,
        -- Udaljenost od odabranog grada (km)
        ROUND(
            6371 * 2 * ASIN(LEAST(1, SQRT(
                POWER(SIN(RADIANS(g.latitude - pg.latitude) / 2), 2)
                + COS(RADIANS(pg.latitude))
                * COS(RADIANS(g.latitude))
                * POWER(SIN(RADIANS(g.longitude - pg.longitude) / 2), 2)
            ))),
            1
        ) AS udaljenost_km,
        now() as datum_i_vrijeme_eksporta
    FROM MAJSTORI m
    JOIN GRADOVI g ON g.id = m.grad_id
    LEFT JOIN GRADOVI pg ON pg.id = @grad_id
    WHERE (
        JSON_LENGTH(@kategorije) = 0
        OR EXISTS (
            SELECT 1
            FROM MAJSTOR_KATEGORIJE mk
            JOIN podstablo p ON p.id = mk.kategorija_id
            WHERE mk.majstor_id = m.id
        )
    )
)
SELECT *
FROM rezultati
WHERE
    @grad_id IS NULL
    OR (
        @radijus_km IS NULL
        AND grad = (SELECT naziv FROM GRADOVI WHERE id = @grad_id)
    )
    OR (
        @radijus_km IS NOT NULL
        AND udaljenost_km <= @radijus_km
    )
ORDER BY id;
/*
1,Amir,Elektrović,Sarajevo,"IP kamere, Mrežne instalacije",,2026-10-31 20:46:17
4,Adis,Alarmović,Tuzla,Alarmni sistemi,,2026-10-31 20:46:17
6,Eldin,Kamerić,Mostar,"Analogni sistemi, IP kamere",,2026-10-31 20:46:17
*/

-- 7b. Provjera pretrage majstora po stablu majstorluka - dio drugi (1 nadredjena roditeljska kategorija)

-- PARAMETRI PRETRAGE
SET @kategorije = JSON_ARRAY(12); -- Lista ID-jeva kategorija (12-Niska struja)
SET @grad_id = NULL;              -- NULL = svi gradovi
SET @radijus_km = NULL;           -- NULL = bez radijusa

-- PRETRAGA MAJSTORA
WITH RECURSIVE podstablo AS (
    -- Odabrane kategorije
    SELECT k.id
    FROM KATEGORIJE k
    JOIN JSON_TABLE(
        @kategorije,
        '$[*]' COLUMNS (id INT PATH '$')
    ) t ON t.id = k.id
    UNION DISTINCT
    -- Svi potomci odabranih kategorija
    SELECT k.id
    FROM KATEGORIJE k
    JOIN podstablo p ON k.roditelj_id = p.id
),
rezultati AS (
    SELECT
        m.id,
        m.ime,
        m.prezime,
        g.naziv AS grad,
        -- Stvarno označeni majstorluci
        (
            SELECT GROUP_CONCAT(
                kat.naziv ORDER BY kat.naziv SEPARATOR ', '
            )
            FROM MAJSTOR_KATEGORIJE mk
            JOIN KATEGORIJE kat ON kat.id = mk.kategorija_id
            WHERE mk.majstor_id = m.id
        ) AS majstorluci,
        -- Udaljenost od odabranog grada (km)
        ROUND(
            6371 * 2 * ASIN(LEAST(1, SQRT(
                POWER(SIN(RADIANS(g.latitude - pg.latitude) / 2), 2)
                + COS(RADIANS(pg.latitude))
                * COS(RADIANS(g.latitude))
                * POWER(SIN(RADIANS(g.longitude - pg.longitude) / 2), 2)
            ))),
            1
        ) AS udaljenost_km,
        now() as datum_i_vrijeme_eksporta
    FROM MAJSTORI m
    JOIN GRADOVI g ON g.id = m.grad_id
    LEFT JOIN GRADOVI pg ON pg.id = @grad_id
    WHERE (
        JSON_LENGTH(@kategorije) = 0
        OR EXISTS (
            SELECT 1
            FROM MAJSTOR_KATEGORIJE mk
            JOIN podstablo p ON p.id = mk.kategorija_id
            WHERE mk.majstor_id = m.id
        )
    )
)
SELECT *
FROM rezultati
WHERE
    @grad_id IS NULL
    OR (
        @radijus_km IS NULL
        AND grad = (SELECT naziv FROM GRADOVI WHERE id = @grad_id)
    )
    OR (
        @radijus_km IS NOT NULL
        AND udaljenost_km <= @radijus_km
    )
ORDER BY id;
/*
1,Amir,Elektrović,Sarajevo,"IP kamere, Mrežne instalacije",,2026-10-31 20:46:50
4,Adis,Alarmović,Tuzla,Alarmni sistemi,,2026-10-31 20:46:50
6,Eldin,Kamerić,Mostar,"Analogni sistemi, IP kamere",,2026-10-31 20:46:50
11,Alen,Elektrović,Tuzla,"Električar, Niska struja",,2026-10-31 20:46:50
*/

-- 7c. Provjera pretrage majstora po stablu majstorluka - (1 nadredjena roditeljska kategorija + grad tuzla i radijus vazdušne linije 100 km da se iskljuci Mostar a ostane Sarajevo)

-- PARAMETRI PRETRAGE
SET @kategorije = JSON_ARRAY(12); -- Lista ID-jeva kategorija (12-Niska struja)
SET @grad_id = 4;              -- NULL = svi gradovi (4 - Tuzla)
SET @radijus_km = 100;           -- NULL = bez radijusa

-- PRETRAGA MAJSTORA
WITH RECURSIVE podstablo AS (
    -- Odabrane kategorije
    SELECT k.id
    FROM KATEGORIJE k
    JOIN JSON_TABLE(
        @kategorije,
        '$[*]' COLUMNS (id INT PATH '$')
    ) t ON t.id = k.id
    UNION DISTINCT
    -- Svi potomci odabranih kategorija
    SELECT k.id
    FROM KATEGORIJE k
    JOIN podstablo p ON k.roditelj_id = p.id
),
rezultati AS (
    SELECT
        m.id,
        m.ime,
        m.prezime,
        g.naziv AS grad,
        -- Stvarno označeni majstorluci
        (
            SELECT GROUP_CONCAT(
                kat.naziv ORDER BY kat.naziv SEPARATOR ', '
            )
            FROM MAJSTOR_KATEGORIJE mk
            JOIN KATEGORIJE kat ON kat.id = mk.kategorija_id
            WHERE mk.majstor_id = m.id
        ) AS majstorluci,
        -- Udaljenost od odabranog grada (km)
        ROUND(
            6371 * 2 * ASIN(LEAST(1, SQRT(
                POWER(SIN(RADIANS(g.latitude - pg.latitude) / 2), 2)
                + COS(RADIANS(pg.latitude))
                * COS(RADIANS(g.latitude))
                * POWER(SIN(RADIANS(g.longitude - pg.longitude) / 2), 2)
            ))),
            1
        ) AS udaljenost_km,
        now() as datum_i_vrijeme_eksporta
    FROM MAJSTORI m
    JOIN GRADOVI g ON g.id = m.grad_id
    LEFT JOIN GRADOVI pg ON pg.id = @grad_id
    WHERE (
        JSON_LENGTH(@kategorije) = 0
        OR EXISTS (
            SELECT 1
            FROM MAJSTOR_KATEGORIJE mk
            JOIN podstablo p ON p.id = mk.kategorija_id
            WHERE mk.majstor_id = m.id
        )
    )
)
SELECT *
FROM rezultati
WHERE
    @grad_id IS NULL
    OR (
        @radijus_km IS NULL
        AND grad = (SELECT naziv FROM GRADOVI WHERE id = @grad_id)
    )
    OR (
        @radijus_km IS NOT NULL
        AND udaljenost_km <= @radijus_km
    )
ORDER BY id;
/*
1,Amir,Elektrović,Sarajevo,"IP kamere, Mrežne instalacije",78.5,2026-10-31 20:53:10
4,Adis,Alarmović,Tuzla,Alarmni sistemi,0,2026-10-31 20:53:10
11,Alen,Elektrović,Tuzla,"Električar, Niska struja",0,2026-10-31 20:53:10
*/