// PRIVREMENI PODACI ZA M1: kasnije će se ovaj format popunjavati odgovorom PHP API-ja.
// Ovdje su podaci o kategorijama, a ne u HTML-u, zato što se isto stablo koristi na više stranica.
const kategorijeM1 = [
  { id: 1, naziv: "Sva majstorstva", roditelj_id: null },
  { id: 10, naziv: "Električar", roditelj_id: 1 },
  { id: 12, naziv: "Niska struja", roditelj_id: 10 },
  { id: 18, naziv: "Video-nadzor", roditelj_id: 12 },
  { id: 27, naziv: "IP kamere", roditelj_id: 18 },
  { id: 29, naziv: "Analogni sistemi", roditelj_id: 18 },
  { id: 15, naziv: "Alarmni sistemi", roditelj_id: 12 },
  { id: 20, naziv: "Mrežne instalacije", roditelj_id: 10 },
  { id: 21, naziv: "LAN kabliranje", roditelj_id: 20 },
  { id: 22, naziv: "Mrežni ormari", roditelj_id: 20 },
  { id: 30, naziv: "Visoka struja", roditelj_id: 10 },
  { id: 40, naziv: "Vodoinstalater", roditelj_id: 1 },
  { id: 42, naziv: "Vodovodne instalacije", roditelj_id: 40 },
  { id: 43, naziv: "Ugradnja cijevi", roditelj_id: 42 },
  { id: 44, naziv: "Popravka curenja", roditelj_id: 42 },
  { id: 45, naziv: "Grijanje", roditelj_id: 1 },
  { id: 46, naziv: "Radijatori", roditelj_id: 45 },
  { id: 47, naziv: "Podno grijanje", roditelj_id: 45 },
  { id: 60, naziv: "Zidar", roditelj_id: 1 }
];
const otvoreneKategorijeM1 = new Set([1, 10, 12, 18, 40, 42, 45]);
const odabraniMajstorluciM1 = [10, 27, 20, 45];


// Demonstracijski podaci su odvojeni od HTML-a. U kasnijoj fazi isti prikazi
// mogu dobiti podatke iz PHP API-ja umjesto ovih privremenih nizova.
const demoGradoviM1 = [
  { naziv: "Sarajevo", sirina: 43.8563, duzina: 18.4131 },
  { naziv: "Zenica", sirina: 44.2034, duzina: 17.9077 },
  { naziv: "Mostar", sirina: 43.3438, duzina: 17.8078 },
  { naziv: "Tuzla", sirina: 44.5384, duzina: 18.6671 },
  { naziv: "Bihać", sirina: 44.8167, duzina: 15.8708 }
];

const demoMajstoriM1 = [
  {
    id: "amir",
    ime: "Amir Hadžić",
    email: "amir.hadzic@primjer.ba",
    grad: "Sarajevo",
    telefon: "061 000 111",
    sazetak: "Električarske usluge i manje popravke u domaćinstvu.",
    opis: "Električarske usluge i manje popravke u domaćinstvu.",
    kategorije: ["Električar", "Popravke"]
  },
  {
    id: "adnan",
    ime: "Adnan Kovač",
    email: "adnan.kovac@primjer.ba",
    grad: "Zenica",
    telefon: "061 000 222",
    sazetak: "Vodoinstalaterski radovi u domaćinstvu.",
    opis: "Vodoinstalaterski radovi u domaćinstvu.",
    kategorije: ["Vodoinstalater", "Održavanje"]
  }
];

const demoKorisniciM1 = [
  { id: "amir", email: "amir.hadzic@primjer.ba", uloga: "artisan", ime: "Amir Hadžić", grad: "Sarajevo", telefon: "061 000 111" },
  { id: "admin", email: "admin@primjer.ba", uloga: "admin", ime: "", grad: "", telefon: "" }
];

// Brojevi na početnoj su zasebne demo vrijednosti, a ne rezultat upita nad bazom.
const demoStatistikaM1 = { majstori: 12, kategorije: 19, gradovi: 5 };

function renderDemoData(pageKey) {
  // Ove funkcije popunjavaju prazne elemente u HTML-u nakon što jQuery učita stranicu.
  if (pageKey === "pocetna") {
    const artisans = document.getElementById("statArtisans");
    const categories = document.getElementById("statCategories");
    const cities = document.getElementById("statCities");
    if (artisans) artisans.textContent = demoStatistikaM1.majstori;
    if (categories) categories.textContent = kategorijeM1.length;
    if (cities) cities.textContent = demoGradoviM1.length;
  }

  if (pageKey === "pretraga") {
    const citySelect = document.getElementById("city");
    demoGradoviM1.forEach(grad => {
      const option = document.createElement("option");
      option.value = grad.naziv;
      option.textContent = grad.naziv;
      citySelect.appendChild(option);
    });

    const results = document.getElementById("sampleSearchResults");
    const resultCount = document.getElementById("searchResultCount");
    if (resultCount) resultCount.textContent = `${demoMajstoriM1.length} majstora`;
    if (results) {
      results.innerHTML = demoMajstoriM1.map(majstor => `
        <div class="card result-card">
          <div class="person-icon">♙</div>
          <div>
            <h3>${majstor.ime}</h3>
            <p>${majstor.sazetak}</p>
            <div class="meta"><span>⌖ ${majstor.grad}</span><span>☎ ${majstor.telefon}</span></div>
            <div class="tag-list">${majstor.kategorije.map(k => `<span class="tag">${k}</span>`).join("")}</div>
            <div style="margin-top:15px"><a class="button small" href="#profil-majstora" data-profile-id="${majstor.id}">Pogledaj profil</a></div>
          </div>
        </div>`).join("");
    }
  }

  if (pageKey === "korisnici") {
    const tbody = document.getElementById("usersTableBody");
    if (tbody) {
      tbody.innerHTML = demoKorisniciM1.map(korisnik => `
        <tr>
          <td>${korisnik.email}</td>
          <td><span class="pill ${korisnik.uloga === "admin" ? "gray" : ""}">${korisnik.uloga === "admin" ? "Admin" : "Majstor"}</span></td>
          <td>${korisnik.ime || "—"}</td>
          <td>${korisnik.grad || "—"}</td>
          <td><button class="button secondary small" data-user="${korisnik.id}">Pregled</button></td>
        </tr>`).join("");
    }
  }

  if (pageKey === "gradovi") {
    const tbody = document.getElementById("citiesTableBody");
    if (tbody) {
      tbody.innerHTML = demoGradoviM1.map(grad =>
        `<tr><td>${grad.naziv}</td><td>${Number(grad.sirina).toFixed(4)}</td><td>${Number(grad.duzina).toFixed(4)}</td></tr>`
      ).join("");
    }
  }

  if (pageKey === "moj-profil") {
    const profile = demoMajstoriM1[0];
    const firstName = document.getElementById("firstName");
    if (firstName) {
      firstName.value = profile.ime.split(" ")[0];
      document.getElementById("lastName").value = profile.ime.split(" ").slice(1).join(" ");
      document.getElementById("profilePhone").value = profile.telefon;
      document.getElementById("description").value = profile.opis;
      const citySelect = document.getElementById("profileCity");
      demoGradoviM1.forEach(grad => {
        const option = document.createElement("option");
        option.value = grad.naziv;
        option.textContent = grad.naziv;
        citySelect.appendChild(option);
      });
      citySelect.value = profile.grad;
    }
  }

  if (pageKey === "profil-majstora") {
    // Za ovu demonstraciju javni profil prikazuje prvog majstora iz demoMajstoriM1.
    const profile = demoMajstoriM1[0];
    const putText = (id, value) => {
      const element = document.getElementById(id);
      if (element) element.textContent = value;
    };
    putText("publicProfileName", profile.ime);
    putText("publicProfileCardName", profile.ime);
    putText("publicProfileSummary", profile.sazetak);
    putText("publicProfileCity", `⌖ ${profile.grad}`);
    putText("publicProfilePhone", `☎ ${profile.telefon}`);
    putText("publicProfileDescription", profile.opis);
    const tags = document.getElementById("publicProfileTags");
    if (tags) tags.innerHTML = profile.kategorije.map(k => `<span class="tag">${k}</span>`).join("");
    const phone = document.getElementById("publicContactPhone");
    const city = document.getElementById("publicContactCity");
    if (phone) phone.value = profile.telefon;
    if (city) city.value = profile.grad;
  }
}
// Vraća direktne podkategorije zadane kategorije na osnovu roditelj_id veze.
function djecaKategorije(id) { return kategorijeM1.filter(k => k.roditelj_id === id); }
// Rekurzivno crta hijerarhiju kategorija. Parametri određuju da li se prikazuju
// checkbox polja za majstora/pretragu i dugmad za uređivanje u administratorskom dijelu.
function nacrtajStablo(kontejner, odabrane = [], saCheckboxima = false, adminActions = false) {
  if (!kontejner) return;
  function grana(k, nivo) {
    const djeca = djecaKategorije(k.id);
    const otvoreno = otvoreneKategorijeM1.has(k.id);
    const ikona = djeca.length ? (otvoreno ? "📂" : "📁") : "📄";
    const oznaka = saCheckboxima && k.id !== 1 ? `<input class="tree-checkbox" type="checkbox" data-category-id="${k.id}" ${odabrane.includes(k.id) ? "checked" : ""} aria-label="Odaberi ${k.naziv}">` : "";
    const adminButtons = adminActions && k.id !== 1 ? `<span class="tree-admin-actions"><button type="button" data-edit-category="${k.id}">Uredi / premjesti</button><button type="button" class="button-danger" data-delete-category="${k.id}">Obriši</button></span>` : "";
    let html = `<div class="tree-node" style="--tree-level:${nivo}"><div class="tree-row">${djeca.length ? `<button class="tree-toggle" type="button" data-toggle-category="${k.id}" aria-label="${otvoreno ? "Skupi" : "Proširi"} ${k.naziv}">${otvoreno ? "▾" : "▸"}</button>` : `<span class="tree-toggle-placeholder"></span>`}<span class="tree-folder">${ikona}</span>${oznaka}<span class="tree-name">${k.naziv}</span><span class="tree-id">ID: ${k.id}</span>${adminButtons}</div>`;
    if (djeca.length && otvoreno) html += `<div class="tree-children">${djeca.map(d => grana(d, nivo + 1)).join("")}</div>`;
    return html + `</div>`;
  }
  kontejner.innerHTML = `<div class="category-tree">${grana(kategorijeM1[0], 0)}</div>`;
  kontejner.querySelectorAll("[data-toggle-category]").forEach(btn => btn.addEventListener("click", () => {
    const id = Number(btn.dataset.toggleCategory);
    if (otvoreneKategorijeM1.has(id)) otvoreneKategorijeM1.delete(id); else otvoreneKategorijeM1.add(id);
    const checked = [...kontejner.querySelectorAll(".tree-checkbox:checked")].map(input => Number(input.dataset.categoryId));
    nacrtajStablo(kontejner, checked, saCheckboxima, adminActions);
  }));
}

// Dozvoljene stranice i njihovi naslovi. Vrijednost ključa odgovara nazivu HTML fajla u views/.
const pages = {
  "pocetna": { title: "Početna" },
  "pretraga": { title: "Pretraga majstora" },
  "profil-majstora": { title: "Profil majstora" },
  "prijava": { title: "Prijava" },
  "moj-profil": { title: "Moj profil" },
  "moji-majstorluci": { title: "Moji majstorluci" },
  "promjena-lozinke": { title: "Promjena lozinke" },
  "korisnici": { title: "Korisnici" },
  "kategorije": { title: "Kategorije" },
  "gradovi": { title: "Gradovi" },
  "odjava": { title: "Odjava" }
};

const pageContent = document.getElementById("pageContent");
const pageTitle = document.getElementById("pageTitle");
const pageBreadcrumb = document.getElementById("pageBreadcrumb");
const navLinks = [...document.querySelectorAll(".nav-link[data-page]")];

// SPA navigacija: mijenja hash URL-a i jQuery AJAX-om učitava samo traženi view,
// bez ponovnog učitavanja cijelog index.html dokumenta.
function showPage(pageKey) {
  const key = pages[pageKey] ? pageKey : "pocetna";
  const page = pages[key];
  $("#pageContent").html('<div class="loading">Učitavanje stranice…</div>');
  $("#pageTitle, #pageBreadcrumb").text(page.title);
  $(".nav-link[data-page]").each(function () {
    $(this).toggleClass("active", this.dataset.page === key);
  });
  $(".sidebar").removeClass("open");
  window.scrollTo({ top: 0, behavior: "smooth" });

  // jQuery AJAX učitava samo sadržaj odabrane stranice; zajednički raspored ostaje.
  $.ajax({
    url: `views/${key}.html`,
    method: "GET",
    dataType: "html"
  }).done(function (markup) {
    $("#pageContent").html(markup);
    renderDemoData(key);
    if (key === "pretraga") nacrtajStablo(document.getElementById("searchCategoryTree"), [], true);
    if (key === "kategorije") nacrtajStablo(document.getElementById("adminCategoryTree"), [], false, true);
    if (key === "moji-majstorluci") nacrtajStablo(document.getElementById("myTradesTree"), odabraniMajstorluciM1, true);
    attachPageActions();
  }).fail(function () {
    $("#pageContent").html('<div class="notice">Stranice se učitavaju iz foldera views. Pokreni projekat preko VS Code Live Servera, a ne direktnim otvaranjem index.html.</div>');
  });
}
// AJAX zamijeni sadržaj stranice, pa se događaji za njene forme i dugmad
// moraju povezati nakon učitavanja. Zato se ova funkcija poziva za svaki view.
function attachPageActions() {
  // Pretraga je zasad demonstracijska: prikazuje izabrane kriterije, ali ne šalje upit bazi.
  const searchForm = document.getElementById("searchForm");
  if (searchForm) searchForm.addEventListener("submit", e => {
    e.preventDefault();
    const results = document.getElementById("searchResults");
    const odabrane = [...document.querySelectorAll("#searchCategoryTree .tree-checkbox:checked")].map(input => { const k = kategorijeM1.find(x => x.id === Number(input.dataset.categoryId)); return k ? `${k.naziv} (ID: ${k.id})` : ""; }).filter(Boolean);
    const grad = document.getElementById("city").value || "svi gradovi";
    const radijus = document.getElementById("radius").value;
    results.innerHTML = `<div class="notice"><strong>Odabrani kriteriji (M1 demonstracija):</strong><br>Kategorije: ${odabrane.length ? odabrane.join(", ") : "sve kategorije"}<br>Grad: ${grad}<br>Udaljenost: ${radijus ? radijus + " km" : "bez ograničenja"}.<br>Rezultati su probni; stvarna pretraga će koristiti podatke iz baze.</div>` + results.innerHTML;
  });
  // Sljedeće forme trenutno samo prikazuju poruku; još nisu povezane s backendom.
  const loginForm = document.getElementById("loginForm");
  if (loginForm) loginForm.addEventListener("submit", e => { e.preventDefault(); document.getElementById("loginMessage").textContent = "Forma je samo probna — prijava još nije povezana s bazom."; });
  const profileForm = document.getElementById("profileForm");
  if (profileForm) profileForm.addEventListener("submit", e => { e.preventDefault(); document.getElementById("profileMessage").textContent = "Promjene nisu sačuvane: ovo je statički prikaz za M1."; });
  const passwordForm = document.getElementById("passwordForm");
  if (passwordForm) passwordForm.addEventListener("submit", e => { e.preventDefault(); document.getElementById("passwordMessage").textContent = "Promjena lozinke još nije povezana s backendom."; });
  const saveTrades = document.getElementById("saveTrades");
  if (saveTrades) saveTrades.addEventListener("click", () => { document.getElementById("tradesMessage").textContent = "Odabrane kategorije nisu sačuvane u bazi u ovoj fazi."; });
  // Dodavanje korisnika: funkcionalna demonstracija bez trajnog spremanja.
  const addUser = document.getElementById("addUser");
  const newUserPanel = document.getElementById("newUserPanel");
  if (addUser && newUserPanel) addUser.addEventListener("click", () => { newUserPanel.hidden = !newUserPanel.hidden; });
  const cancelNewUser = document.getElementById("cancelNewUser");
  if (cancelNewUser) cancelNewUser.addEventListener("click", () => { newUserPanel.hidden = true; });
  const newUserForm = document.getElementById("newUserForm");
  if (newUserForm) newUserForm.addEventListener("submit", e => {
    e.preventDefault();
    const email = document.getElementById("newUserEmail").value.trim();
    const role = document.getElementById("newUserRole").value;
    const tbody = document.querySelector("#usersTable tbody");
    if ([...tbody.querySelectorAll("tr td:first-child")].some(td => td.textContent.trim().toLowerCase() === email.toLowerCase())) {
      document.getElementById("newUserMessage").textContent = "Korisnik s ovim e-mailom već postoji na probnoj listi."; return;
    }
    const roleLabel = role === "admin" ? "Admin" : "Majstor";
    // Dodajemo u memorijski niz i tabelu; nakon osvježavanja preglednika podaci nestaju.
    demoKorisniciM1.push({ id: `demo-${Date.now()}`, email, uloga: role, ime: "", grad: "", telefon: "" });
    const row = document.createElement("tr");
    row.innerHTML = `<td></td><td><span class="pill ${role === "admin" ? "gray" : ""}">${roleLabel}</span></td><td>—</td><td>—</td><td><span class="muted">Novi probni račun</span></td>`;
    row.cells[0].textContent = email; tbody.appendChild(row);
    document.getElementById("newUserMessage").textContent = `Probni račun ${email} (${roleLabel}) dodat je u tabelu. Nije sačuvan u bazi.`;
    newUserForm.reset();
  });

  // Administratorsko uređivanje stabla: dodavanje, preimenovanje i premještanje.
  const categoryPanel = document.getElementById("categoryEditor");
  const categoryForm = document.getElementById("categoryForm");
  let editingCategoryId = null;
  // Pronalazi kategoriju i sve njene potomke; koristi se da se kategorija ne premjesti
  // u vlastito podstablo, čime bi nastala kružna/neispravna hijerarhija.
  function descendantsOf(id) {
    const result = new Set([id]); let changed = true;
    while (changed) { changed = false; for (const k of kategorijeM1) if (result.has(k.roditelj_id) && !result.has(k.id)) { result.add(k.id); changed = true; } }
    return result;
  }
  // Popunjava izbor nadređene kategorije. Pri uređivanju iz liste se izuzimaju
  // sama kategorija i svi njeni potomci da se spriječi kružno povezivanje.
  function populateParentSelect(selectedId = 1, excludeId = null) {
    const select = document.getElementById("categoryParent"); select.innerHTML = "";
    const blocked = excludeId === null ? new Set() : descendantsOf(excludeId);
    kategorijeM1.filter(k => k.id !== 1 && !blocked.has(k.id)).forEach(k => {
      const option = document.createElement("option"); option.value = String(k.id); option.textContent = `${"— ".repeat(Math.max(0, depthOf(k.id)))}${k.naziv} (ID: ${k.id})`; select.appendChild(option);
    });
    const rootOption = document.createElement("option"); rootOption.value = "1"; rootOption.textContent = "Sva majstorstva (glavni nivo)"; select.insertBefore(rootOption, select.firstChild);
    select.value = String(selectedId);
  }
  function depthOf(id) { let depth = 0, node = kategorijeM1.find(k => k.id === id); while (node && node.roditelj_id !== null) { depth++; node = kategorijeM1.find(k => k.id === node.roditelj_id); } return depth; }
  function refreshAdminTree() { nacrtajStablo(document.getElementById("adminCategoryTree"), [], false, true); attachAdminTreeActions(); }
  function openCategoryEditor(id = null) {
    editingCategoryId = id; const item = id === null ? null : kategorijeM1.find(k => k.id === id);
    document.getElementById("categoryEditorTitle").textContent = item ? "Uredi / premjesti kategoriju" : "Dodavanje kategorije";
    document.getElementById("saveCategoryButton").textContent = item ? "Sačuvaj izmjene" : "Dodaj kategoriju";
    document.getElementById("categoryName").value = item ? item.naziv : "";
    populateParentSelect(item ? item.roditelj_id : 1, id);
    document.getElementById("categoryMessage").textContent = ""; categoryPanel.hidden = false; categoryPanel.scrollIntoView({behavior:"smooth", block:"center"});
  }
  // Jedan delegirani click događaj obrađuje dugmad u cijelom stablu, uključujući
  // čvorove koji se ponovo nacrtaju nakon dodavanja, uređivanja ili brisanja.
  function attachAdminTreeActions() {
    const tree = document.getElementById("adminCategoryTree");
    if (!tree || tree.dataset.actionsAttached === "true") return;
    tree.dataset.actionsAttached = "true";
    tree.addEventListener("click", event => {
      const editButton = event.target.closest("[data-edit-category]");
      if (editButton) {
        openCategoryEditor(Number(editButton.dataset.editCategory));
        return;
      }

      const deleteButton = event.target.closest("[data-delete-category]");
      if (!deleteButton) return;

      const id = Number(deleteButton.dataset.deleteCategory);
      const category = kategorijeM1.find(k => k.id === id);
      if (!category || id === 1) return;

      // Dozvoljeno je brisanje samo lista (kategorija bez djece), da se ne izgubi
      // cijelo podstablo jednim slučajnim klikom.
      const children = djecaKategorije(id);
      const message = document.getElementById("categoryMessage");
      if (children.length > 0) {
        message.textContent = `Kategoriju „${category.naziv}” nije moguće obrisati jer ima podkategorije. Prvo premjesti ili obriši njene podkategorije.`;
        message.scrollIntoView({behavior: "smooth", block: "center"});
        return;
      }

      if (!window.confirm(`Želiš li obrisati kategoriju „${category.naziv}”? Ova promjena je samo privremena u M1 demonstraciji.`)) return;

      const index = kategorijeM1.findIndex(k => k.id === id);
      if (index !== -1) kategorijeM1.splice(index, 1);

      const selectedIndex = odabraniMajstorluciM1.indexOf(id);
      if (selectedIndex !== -1) odabraniMajstorluciM1.splice(selectedIndex, 1);

      otvoreneKategorijeM1.delete(id);
      message.textContent = `Kategorija „${category.naziv}” je obrisana iz M1 demonstracije. Promjena nije sačuvana u bazi.`;
      refreshAdminTree();
    });
  }
  attachAdminTreeActions();
  const addCategory = document.getElementById("addCategory");
  if (addCategory && categoryPanel) addCategory.addEventListener("click", () => openCategoryEditor());
  const cancelCategory = document.getElementById("cancelCategory");
  if (cancelCategory) cancelCategory.addEventListener("click", () => { categoryPanel.hidden = true; editingCategoryId = null; });
  // Provjera naziva i roditelja prije promjene kategorije čuva jedinstvene nazive
  // i sprečava da čvor postane roditelj sam sebi ili vlastitom potomku.
  if (categoryForm) categoryForm.addEventListener("submit", e => {
    e.preventDefault(); const name = document.getElementById("categoryName").value.trim(); const parentId = Number(document.getElementById("categoryParent").value);
    if (!name) return;
    if (kategorijeM1.some(k => k.naziv.toLocaleLowerCase() === name.toLocaleLowerCase() && k.id !== editingCategoryId)) { document.getElementById("categoryMessage").textContent = "Kategorija s tim nazivom već postoji."; return; }
    if (editingCategoryId !== null) {
      const item = kategorijeM1.find(k => k.id === editingCategoryId);
      if (!item || parentId === editingCategoryId || descendantsOf(editingCategoryId).has(parentId)) { document.getElementById("categoryMessage").textContent = "Premještanje nije dozvoljeno jer bi nastala neispravna hijerarhija."; return; }
      item.naziv = name; item.roditelj_id = parentId;
    } else {
      const newId = Math.max(...kategorijeM1.map(k => k.id)) + 1;
      kategorijeM1.push({id:newId, naziv:name, roditelj_id:parentId}); otvoreneKategorijeM1.add(parentId);
    }
    categoryPanel.hidden = true; refreshAdminTree();
  });

  // Dodavanje grada: poziva Nominatim geokodirajući API i prikazuje pronađenu
  // lokaciju na OpenStreetMap mapi. Ovo je jedini dio koji traži internet vezu.
  const addCity = document.getElementById("addCity"); const newCityPanel = document.getElementById("newCityPanel");
  if (addCity && newCityPanel) addCity.addEventListener("click", () => { newCityPanel.hidden = !newCityPanel.hidden; });
  let foundCity = null;
  const newCityForm = document.getElementById("newCityForm");
  if (newCityForm) newCityForm.addEventListener("submit", async e => {
    e.preventDefault(); const cityName = document.getElementById("newCityName").value.trim();
    const message = document.getElementById("cityApiMessage"); const result = document.getElementById("cityCoordinates");
    result.hidden = true; foundCity = null; message.textContent = "Pozivam geokodirajući API…";
    try {
      const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=5&addressdetails=1&q=${encodeURIComponent(cityName + ", Bosnia and Herzegovina")}`;
      const response = await fetch(url, {headers: {"Accept":"application/json"}});
      if (!response.ok) throw new Error(`API je vratio status ${response.status}`);
      const places = await response.json();
      if (!places.length) { message.textContent = `API je pozvan, ali lokacija za „${cityName}“ nije pronađena. Provjeri naziv i pokušaj ponovo.`; return; }
      const place = places.find(x => /city|town|municipality|village/i.test(x.type || "") || /city|town|municipality/i.test(x.addresstype || "")) || places[0];
      foundCity = {name: cityName, displayName: place.display_name, lat: Number(place.lat), lon: Number(place.lon)};
      document.getElementById("foundCityName").textContent = `Pronađena lokacija: ${place.display_name}`;
      document.getElementById("foundCityCoords").textContent = `API poziv uspješan. Geografska širina (latitude): ${foundCity.lat.toFixed(6)} · Geografska dužina (longitude): ${foundCity.lon.toFixed(6)}`;
      const delta = 0.08; const bbox = `${foundCity.lon-delta},${foundCity.lat-delta},${foundCity.lon+delta},${foundCity.lat+delta}`;
      document.getElementById("cityMap").src = `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(bbox)}&layer=mapnik&marker=${foundCity.lat}%2C${foundCity.lon}`;
      result.hidden = false; message.textContent = "API poziv završen; pregledaj koordinate i označenu lokaciju na mapi.";
    } catch (err) { message.textContent = `API poziv nije uspio: ${err.message}. Provjeri internet vezu i pokušaj kasnije.`; }
  });
  // Potvrđena lokacija se dodaje u probni niz tek nakon korisnikove potvrde.
  const saveDemoCity = document.getElementById("saveDemoCity");
  if (saveDemoCity) saveDemoCity.addEventListener("click", () => {
    if (!foundCity) return; const tbody = document.querySelector("#citiesTable tbody");
    if ([...tbody.querySelectorAll("tr td:first-child")].some(td => td.textContent.trim().toLocaleLowerCase() === foundCity.name.toLocaleLowerCase())) { document.getElementById("cityApiMessage").textContent = "Ovaj grad je već na probnoj listi."; return; }
    // Grad se čuva samo u JavaScript memoriji za trenutnu demonstraciju.
    demoGradoviM1.push({ naziv: foundCity.name, sirina: foundCity.lat, duzina: foundCity.lon });
    const row = document.createElement("tr"); row.innerHTML = `<td></td><td>${foundCity.lat.toFixed(6)}</td><td>${foundCity.lon.toFixed(6)}</td>`; row.cells[0].textContent = foundCity.name; tbody.appendChild(row);
    document.getElementById("cityApiMessage").textContent = `Grad ${foundCity.name} dodat je na probnu listu. Podaci nisu trajno sačuvani u bazi.`;
  });
  // Prikaz detalja korisnika čita podatke iz demo nizova, a ne iz HTML redova.
  document.querySelectorAll("[data-user]").forEach(button => button.addEventListener("click", () => {
    const box = document.getElementById("userDetails");
    const korisnik = demoKorisniciM1.find(item => item.id === button.dataset.user);
    if (!box || !korisnik) return;

    box.style.display = "block";
    if (korisnik.uloga === "artisan") {
      const majstor = demoMajstoriM1.find(item => item.id === korisnik.id);
      box.innerHTML = `<h3>Pregled korisnika: ${korisnik.email}</h3>
        <div class="grid grid-2" style="margin-top:15px">
          <div class="field"><label>Uloga</label><input value="Majstor" readonly></div>
          <div class="field"><label>Ime i prezime</label><input value="${majstor ? majstor.ime : (korisnik.ime || "Nije uneseno")}" readonly></div>
          <div class="field"><label>Grad</label><input value="${majstor ? majstor.grad : (korisnik.grad || "Nije uneseno")}" readonly></div>
          <div class="field"><label>Telefon</label><input value="${majstor ? majstor.telefon : (korisnik.telefon || "Nije uneseno")}" readonly></div>
          <div class="field" style="grid-column:1/-1"><label>Opis</label><textarea readonly>${majstor ? majstor.opis : "Profil još nije popunjen."}</textarea></div>
        </div>
        <p style="margin-top:12px;color:var(--muted);font-size:11px">Podaci profila su samo za čitanje. Majstorluci nisu prikazani ovdje.</p>`;
    } else {
      box.innerHTML = `<h3>Pregled korisnika: ${korisnik.email}</h3>
        <div class="field" style="max-width:320px;margin-top:15px"><label>Uloga</label><input value="Admin" readonly></div>
        <p style="margin-top:12px;color:var(--muted);font-size:11px">Administrator nema profil majstora.</p>`;
    }
  }));
}

const demoRole = document.getElementById("demoRole");
// Prebacuje vidljivi meni prema odabranoj demo ulozi. Ovo nije prava autorizacija;
// stvarnu provjeru uloge mora raditi backend kada bude uveden.
function primijeniDemoUlogu() {
  const uloga = demoRole ? demoRole.value : "anonymous";
  document.querySelectorAll("[data-menu-section]").forEach(el => {
    el.hidden = el.dataset.menuSection !== uloga;
  });
  const logout = document.querySelector(".logout-link");
  if (logout) logout.hidden = uloga === "anonymous";
  const userName = document.querySelector(".account-info strong");
  const status = document.querySelector(".account-info span");
  if (userName && status) {
    userName.textContent = uloga === "admin" ? "Demo administrator" : uloga === "artisan" ? "Amir Hadžić" : "Anonimni korisnik";
    status.innerHTML = `<i class="role-dot"></i> ${uloga === "admin" ? "Prikaz za M1 · admin" : uloga === "artisan" ? "Prikaz za M1 · majstor" : "Prikaz za M1 · anonimni korisnik"}`;
  }
  const current = location.hash.replace("#", "") || "pocetna";
  const allowed = uloga === "anonymous" ? ["pocetna","pretraga","prijava","profil-majstora"] :
    uloga === "artisan" ? ["moj-profil","moji-majstorluci","promjena-lozinke"] :
    ["korisnici","kategorije","gradovi"];
  if (!allowed.includes(current)) location.hash = allowed[0];
}
if (demoRole) {
  $("#demoRole").on("change", primijeniDemoUlogu);
  primijeniDemoUlogu();
}

// Otvara/zatvara bočni meni na manjim ekranima.
$("#mobileMenu").on("click", function () {
  $(".sidebar").toggleClass("open");
});
// Kada se hash promijeni (klik na link ili dugme Nazad), učitaj odgovarajuću stranicu.
$(window).on("hashchange", function () {
  showPage(location.hash.replace("#", "") || "pocetna");
});
showPage(location.hash.replace("#", "") || "pocetna");
