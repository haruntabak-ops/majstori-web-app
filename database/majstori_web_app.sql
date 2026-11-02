-- =====================================================================
-- Majstori Web App – DDL i početni testni podaci (MySQL 8.0.39)
-- Namjena: kreiranje tabela i popunjavanje početnih/testnih podataka.
-- Lozinke testnih naloga su u formatu Ime123!, ali se u bazi čuvaju isključivo njihovi hashovi, a ne običan tekst.
--
-- Testni nalozi (lozinka u zagradi):
--   harun.tabak@primjer.ba         (Harun123!)  – admin
--   amir.elektrovic@primjer.ba     (Amir123!)   – majstor
--   suljo.grijanjic@primjer.ba     (Suljo123!)  – majstor
--   mirza.kablovic@primjer.ba      (Mirza123!)  – majstor
--   adis.alarmovic@primjer.ba      (Adis123!)   – majstor
--   tarik.strujic@primjer.ba       (Tarik123!)  – majstor
--   eldin.kameric@primjer.ba       (Eldin123!)  – majstor
--   faris.vodenic@primjer.ba       (Faris123!)  – majstor
--   kenan.radijatorovic@primjer.ba (Kenan123!) – majstor
--   damir.podovic@primjer.ba       (Damir123!)  – majstor
--   nedim.zidaric@primjer.ba       (Nedim123!)  – majstor
--   alen.elektrovic@primjer.ba     (Alen123!)   – majstor
--   jasmin.curic@primjer.ba        (Jasmin123!) – majstor
--
-- Napomena: lozinke se u bazi čuvaju kao bcrypt hashovi.
-- =====================================================================

-- Koristi UTF-8 kako bi se ispravno sačuvala slova č, ć, š, ž i đ.
SET NAMES utf8mb4;

-- Kreiranje šeme. Ako je šema majstori_web_app ranije već napravljena linija ispod se može zakomentarisati prije pokretanja skripte.
CREATE SCHEMA IF NOT EXISTS majstori_web_app
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Sve naredne tabele i podaci kreiraju se u ovoj šemi.
USE majstori_web_app;

-- ---------------------------------------------------------------------
-- 1. DDL: kreiranje tabela, primarnih i stranih ključeva
-- ---------------------------------------------------------------------

-- Korisnički nalozi za administratora i majstore.
CREATE TABLE KORISNICI (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    lozinka_hash VARCHAR(255) NOT NULL,
    uloga ENUM('admin', 'majstor') NOT NULL DEFAULT 'majstor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gradovi dostupni za izbor na profilu majstora.
CREATE TABLE GRADOVI (
    id INT AUTO_INCREMENT PRIMARY KEY,
    naziv VARCHAR(100) NOT NULL UNIQUE,
    latitude DECIMAL(9,6) NOT NULL,
    longitude DECIMAL(9,6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profil majstora je povezan s jednim korisničkim nalogom.
-- UNIQUE na korisnik_id osigurava najviše jedan profil po nalogu.
CREATE TABLE MAJSTORI (
    id INT AUTO_INCREMENT PRIMARY KEY,
    korisnik_id INT NOT NULL UNIQUE,
    grad_id INT NOT NULL,
    ime VARCHAR(50) NOT NULL,
    prezime VARCHAR(70) NOT NULL,
    telefon VARCHAR(30) NOT NULL,
    opis TEXT NOT NULL,
    CONSTRAINT fk_majstori_korisnici
        FOREIGN KEY (korisnik_id) REFERENCES KORISNICI(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_majstori_gradovi
        FOREIGN KEY (grad_id) REFERENCES GRADOVI(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hijerarhija kategorija: roditelj_id pokazuje na drugu kategoriju.
-- NULL označava korijensku kategoriju.
CREATE TABLE KATEGORIJE (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roditelj_id INT NULL,
    naziv VARCHAR(100) NOT NULL,
    CONSTRAINT fk_kategorije_roditelj
        FOREIGN KEY (roditelj_id) REFERENCES KATEGORIJE(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vezna tabela za odnos više-prema-više između majstora i kategorija.
-- Složeni primarni ključ sprečava dupliranje iste veze.
CREATE TABLE MAJSTOR_KATEGORIJE (
    majstor_id INT NOT NULL,
    kategorija_id INT NOT NULL,
    PRIMARY KEY (majstor_id, kategorija_id),
    CONSTRAINT fk_mk_majstori
        FOREIGN KEY (majstor_id) REFERENCES MAJSTORI(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mk_kategorije
        FOREIGN KEY (kategorija_id) REFERENCES KATEGORIJE(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. SEED PODACI: gradovi
-- Koordinate su preuzete iz demonstracijskih podataka frontenda.
-- ---------------------------------------------------------------------
INSERT INTO GRADOVI (id, naziv, latitude, longitude) VALUES
(1, 'Sarajevo', 43.856300, 18.413100),
(2, 'Zenica',   44.203400, 17.907700),
(3, 'Mostar',   43.343800, 17.807800),
(4, 'Tuzla',    44.538400, 18.667100),
(5, 'Bihać',    44.816700, 15.870800);

-- ---------------------------------------------------------------------
-- 3. SEED PODACI: cijelo stablo kategorija iz frontenda
-- Sačuvani su postojeći ID-jevi i roditeljske veze.
-- ---------------------------------------------------------------------
INSERT INTO KATEGORIJE (id, roditelj_id, naziv) VALUES
(1,  NULL, 'Sva majstorstva'),
(10, 1,    'Električar'),
(12, 10,   'Niska struja'),
(18, 12,   'Video-nadzor'),
(27, 18,   'IP kamere'),
(29, 18,   'Analogni sistemi'),
(15, 12,   'Alarmni sistemi'),
(20, 10,   'Mrežne instalacije'),
(21, 20,   'LAN kabliranje'),
(22, 20,   'Mrežni ormari'),
(30, 10,   'Visoka struja'),
(40, 1,    'Vodoinstalater'),
(42, 40,   'Vodovodne instalacije'),
(43, 42,   'Ugradnja cijevi'),
(44, 42,   'Popravka curenja'),
(45, 1,    'Grijanje'),
(46, 45,   'Radijatori'),
(47, 45,   'Podno grijanje'),
(60, 1,    'Zidar');

-- ---------------------------------------------------------------------
-- 4. SEED PODACI: korisnički nalozi
-- Lozinke za lokalno testiranje: Ime123! (npr. Harun123!, Amir123!).
-- Vrijednosti u koloni lozinka_hash su bcrypt hashovi.
-- E-mail adrese su testne i koriste domenu primjer.ba.
-- ---------------------------------------------------------------------
INSERT INTO KORISNICI (id, email, lozinka_hash, uloga) VALUES
(1,  'harun.tabak@primjer.ba',       '$2y$12$T0/250.6j4i.R1rcmw0pyugkPV9X3Pem7DZtwLgq9Gqw2WvyWVIqi', 'admin'),
(2,  'amir.elektrovic@primjer.ba',  '$2y$12$Ibq0/I6G5NPAjfgTv7g.vu3UPIN0gS209xPNy1SbxiefgnvFZ4ByK', 'majstor'),
(3,  'suljo.grijanjic@primjer.ba',  '$2y$12$3xFijKAQ/4PeGBqs1nbIJeOlKZ8LQNfiMxI6Cfls6NGnzWVY01vVW', 'majstor'),
(4,  'mirza.kablovic@primjer.ba',   '$2y$12$t.KyV44vvhEBc5MJicNrJ.cwR7mvEUuyrL8Uc7CGJ3SLcxlHG29Pe', 'majstor'),
(5,  'adis.alarmovic@primjer.ba',   '$2y$12$XfuX0R7xgsL4aXQhqVqvPOg8NL33ccIfsQUICpEbQb/ZBRbgr8blK', 'majstor'),
(6,  'tarik.strujic@primjer.ba',    '$2y$12$KScd0YwysEPQkcPZEk7/4OMjym.dA7Wnk4DMA5dsb4do4nAZO.vzO', 'majstor'),
(7,  'eldin.kameric@primjer.ba',   '$2y$12$DGudjIcl.kZwREA8YIs3f.QucjChbOfRmnTbp8UzhPWCI2A8fxr52', 'majstor'),
(8,  'faris.vodenic@primjer.ba',   '$2y$12$ETVxGx7.R5Ff3tPeTsct5.Pcl7CFD6SIXH4yAjJE2fsb63Rt45Igu', 'majstor'),
(9,  'kenan.radijatorovic@primjer.ba', '$2y$12$u5vjsIOlu4uThe2pSZyKN.8ho0S4aaG0PP.b6Ur4/ijesqV2inyuG', 'majstor'),
(10, 'damir.podovic@primjer.ba',    '$2y$12$6XjO4E43PYYRPhbz/WDmZOabRlt7O7yQUjj/RZJKaU6cL7HPplYvK', 'majstor'),
(11, 'nedim.zidaric@primjer.ba',   '$2y$12$hL4zrlmGEkyZRAm4XIyLpOEj606aBoNYhNhmWFaOYoYrQr9aJBpPq', 'majstor'),
(12, 'alen.elektrovic@primjer.ba', '$2y$12$BSQT2yGSzaNjarKVMZtei.OnXFs79ULJnWy1XQrk2wucrP5NlDeNa', 'majstor'),
(13, 'jasmin.curic@primjer.ba',    '$2y$12$.XijhJZxxo.dUMCneQ/lqep6Zko62G6bELodiln57ZvoIge6kOxAi', 'majstor');

-- ---------------------------------------------------------------------
-- 5. SEED PODACI: profili svih 12 majstora
-- Svaki profil je povezan s nalogom i jednim gradom.
-- Opisi su testni tekstovi usklađeni s uslugama koje nude.
-- ---------------------------------------------------------------------
INSERT INTO MAJSTORI (id, korisnik_id, grad_id, ime, prezime, telefon, opis) VALUES
(1,  2, 1, 'Amir',  'Elektrović',  '061 000 101', 'Bavim se ugradnjom i podešavanjem IP kamera za video-nadzor, kao i mrežnim instalacijama. Pomažem pri postavljanju mrežne opreme i povezivanju uređaja kako bi sistem radio pouzdano i stabilno.'),
(2,  3, 2, 'Suljo', 'Grijanjić',  '061 000 102', 'Bavim se poslovima vezanim za grijanje, održavanjem sistema grijanja i otklanjanjem problema u njihovom radu. Također popravljam curenja na postojećim vodovodnim instalacijama. Ne nudim ugradnju novih cijevi.'),
(3,  4, 1, 'Mirza', 'Kablović',   '061 000 103', 'Bavim se postavljanjem LAN kablova, povezivanjem mrežnih uređaja i organizacijom mrežnih ormara. Pomažem pri uspostavljanju uredne i pouzdane lokalne računarske mreže.'),
(4,  5, 4, 'Adis',  'Alarmović',  '061 000 104', 'Ugrađujem i podešavam alarmne sisteme za kuće, stanove i poslovne prostore. Pomažem pri odabiru odgovarajuće opreme i provjeravam da li sistem pravilno funkcioniše.'),
(5,  6, 2, 'Tarik', 'Strujić',    '061 000 105', 'Izvodim električarske radove na instalacijama visoke struje, uključujući postavljanje i održavanje odgovarajuće električne opreme. Vodim računa o sigurnosti i urednoj izvedbi radova.'),
(6,  7, 3, 'Eldin', 'Kamerić',    '061 000 106', 'Bavim se ugradnjom IP kamera i analognih sistema video-nadzora. Pomažem pri postavljanju kamera, povezivanju opreme i provjeri kvaliteta slike.'),
(7,  8, 5, 'Faris', 'Vodenić',    '061 000 107', 'Bavim se vodovodnim instalacijama i ugradnjom cijevi za dovod i odvod vode. Radim na novim instalacijama i zamjeni dotrajalih dijelova, uz pažnju na kvalitet spojeva.'),
(8,  9, 1, 'Kenan', 'Radijatorović','061 000 108', 'Bavim se poslovima vezanim za radijatorsko grijanje. Pomažem pri montaži i zamjeni radijatora, provjeri njihovog rada i otklanjanju uobičajenih problema sa zagrijavanjem prostorija.'),
(9, 10, 3, 'Damir', 'Podović',     '061 000 109', 'Bavim se ugradnjom i održavanjem sistema podnog grijanja. Pomažem pri postavljanju sistema i provjeri njegovog rada kako bi toplota bila ravnomjerno raspoređena.'),
(10,11, 2, 'Nedim', 'Zidarić',     '061 000 110', 'Izvodim zidarske radove na stambenim i drugim objektima. Bavim se zidanjem, popravkama zidova i pripremom površina za nastavak građevinskih radova.'),
(11,12, 4, 'Alen',  'Elektrović',  '061 000 111', 'Bavim se električarskim radovima i instalacijama niske struje, uključujući postavljanje i provjeru opreme za slabostrujne sisteme. Vodim računa o pravilnom povezivanju i pouzdanom radu instalacija.'),
(12,13, 5, 'Jasmin','Curić',       '061 000 112', 'Pomažem pri otkrivanju i popravci curenja na postojećim vodovodnim instalacijama. Pregledam problematične spojeve i dijelove instalacije te nastojim otkloniti kvar bez nepotrebnih radova na ostatku sistema.');

-- ---------------------------------------------------------------------
-- 6. SEED PODACI: veze majstora s pojedinačno označenim kategorijama
-- Roditeljske kategorije se ne označavaju automatski kada se označi dijete.
-- ---------------------------------------------------------------------
INSERT INTO MAJSTOR_KATEGORIJE (majstor_id, kategorija_id) VALUES
(1, 27), (1, 20),       -- Amir Elektrović: IP kamere, Mrežne instalacije
(2, 45), (2, 44),       -- Suljo Grijanjić: Grijanje, Popravka curenja
(3, 21), (3, 22),       -- Mirza Kablović: LAN kabliranje, Mrežni ormari
(4, 15),                -- Adis Alarmović: Alarmni sistemi
(5, 30),                -- Tarik Strujić: Visoka struja
(6, 27), (6, 29),       -- Eldin Kamerić: IP kamere, Analogni sistemi
(7, 43),                -- Faris Vodenić: Ugradnja cijevi
(8, 46),                -- Kenan Radijatorović: Radijatori
(9, 47),                -- Damir Podović: Podno grijanje
(10,60),                -- Nedim Zidarić: Zidar
(11,10), (11,12),       -- Alen Elektrović: Električar, Niska struja
(12,44);                -- Jasmin Curić: Popravka curenja

-- ---------------------------------------------------------------------
-- Kraj skripte. Za provjeru se mogu pregledati redovi svih pet tabela.
-- ---------------------------------------------------------------------
