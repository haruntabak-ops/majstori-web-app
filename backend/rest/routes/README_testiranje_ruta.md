# Testiranje REST ruta prije Swaggera — MajstoriWebApp (M3)

Prije uvođenja Swaggera ručno je testiran **dio REST ruta** preko PowerShella i browsera. 

Potpunije testiranje **servisnog sloja** prethodno je obavljeno PHP testnim fajlovima u `backend/rest/services/`, a rezultati su sačuvani u istoj mapi:

- `test_1_dohvat.php` → `test_1_dohvat_php_rezultati.txt`
- `test_2_crud.php` → `test_2_crud_php_rezultati.txt`
- `test_3_vanjski_api.php` → `test_3_vanjski_api_php_rezultati.txt`

## Pokretanje servera

U **prvom PowerShell prozoru**, iz glavnog foldera `MajstoriWebApp`:

```powershell
php -S localhost:8000 server.php
```

Prvi prozor ostaje otvoren. Testne komande izvršavaju se u **drugom PowerShell prozoru**. GET zahtjevi mogu se testirati i direktnim otvaranjem URL-a u browseru.

## Primjeri testiranja REST ruta

| Svrha testa | Način 1 — PowerShell (drugi prozor) | Način 2 — Browser (URL) |
|---|---|---|
| GET — pokretanje backenda | `Invoke-RestMethod -Uri "http://localhost:8000/backend/" -Method GET` | `http://localhost:8000/backend/` |
| GET — svi gradovi | `Invoke-RestMethod -Uri "http://localhost:8000/backend/gradovi" -Method GET` | `http://localhost:8000/backend/gradovi` |
| GET — grad ID 1 (Sarajevo) | `Invoke-RestMethod -Uri "http://localhost:8000/backend/gradovi/1" -Method GET` | `http://localhost:8000/backend/gradovi/1` |
| GET — sve kategorije | `Invoke-RestMethod -Uri "http://localhost:8000/backend/kategorije" -Method GET` | `http://localhost:8000/backend/kategorije` |
| GET — direktne potkategorije kategorije 12 | `Invoke-RestMethod -Uri "http://localhost:8000/backend/kategorije/12/djeca" -Method GET` | `http://localhost:8000/backend/kategorije/12/djeca` |
| GET — korijenske kategorije | `Invoke-RestMethod -Uri "http://localhost:8000/backend/kategorije/korijeni" -Method GET` | `http://localhost:8000/backend/kategorije/korijeni` |
| GET — kategorija ID 12 (Niska struja) | `Invoke-RestMethod -Uri "http://localhost:8000/backend/kategorije/12" -Method GET` | `http://localhost:8000/backend/kategorije/12` |
| GET — test 7a: kategorije 15 i 18, s potkategorijama | `Invoke-RestMethod -Uri "http://localhost:8000/backend/majstori?kategorije=15,18" -Method GET` | `http://localhost:8000/backend/majstori?kategorije=15,18` |
| GET — test 7b: kategorija 12, s potkategorijama | `Invoke-RestMethod -Uri "http://localhost:8000/backend/majstori?kategorije=12" -Method GET` | `http://localhost:8000/backend/majstori?kategorije=12` |
| GET — test 7c: kategorija 12, Tuzla (ID 4), radijus 100 km | `Invoke-RestMethod -Uri "http://localhost:8000/backend/majstori?kategorije=12&grad_id=4&radijus_km=100" -Method GET` | `http://localhost:8000/backend/majstori?kategorije=12&grad_id=4&radijus_km=100` |
| GET — statistika | `Invoke-RestMethod -Uri "http://localhost:8000/backend/statistika" -Method GET` | `http://localhost:8000/backend/statistika` |
| GET — Nominatim: koordinate Sarajeva | `Invoke-RestMethod -Uri "http://localhost:8000/backend/lokacije/pretraga?naziv=Sarajevo" -Method GET` | `http://localhost:8000/backend/lokacije/pretraga?naziv=Sarajevo` |
| GET — profil majstora ID 1 (Amir) | `Invoke-RestMethod -Uri "http://localhost:8000/backend/majstori/1" -Method GET` | `http://localhost:8000/backend/majstori/1` |
| GET — majstorluci majstora ID 1 | `Invoke-RestMethod -Uri "http://localhost:8000/backend/majstori/1/majstorluci" -Method GET` | `http://localhost:8000/backend/majstori/1/majstorluci` |
| POST — dodavanje probnog grada „Test grad X“ (5, 4) | `Invoke-RestMethod -Uri "http://localhost:8000/backend/admin/gradovi" -Method POST -ContentType "application/json" -Body '{"naziv":"Test grad X","latitude":5,"longitude":4}'` |  |
| GET — provjera da je probni grad dodat | `Invoke-RestMethod -Uri "http://localhost:8000/backend/gradovi" -Method GET` | `http://localhost:8000/backend/gradovi` |
| DELETE — brisanje probnog grada (u našem testu ID 10) | `Invoke-RestMethod -Uri "http://localhost:8000/backend/admin/gradovi/10" -Method DELETE` |  |
| GET — provjera da je probni grad obrisan | `Invoke-RestMethod -Uri "http://localhost:8000/backend/gradovi" -Method GET` | `http://localhost:8000/backend/gradovi` |

## Završne napomene

- **Prethodno provedeni testovi dali su očekivane rezultate.**