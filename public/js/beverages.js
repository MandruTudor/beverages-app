/**
 * beverages.js
 * Logica paginii de băuturi (beverages.php).
 *
 * Responsabilități:
 *  - Încarcă categoriile și băuturile de la API via fetch() (AJAX)
 *  - Randează grid-ul de carduri
 *  - Gestionează filtrele (căutare text, categorie, sezon)
 *  - Afișează modalul de detalii la click pe card
 *  - Admin: modal formular pentru adăugare/editare
 *  - Admin: confirmare și ștergere băutură
 */

/* ================================================================
   State global al paginii
   Variabilele de mai jos păstrează datele între funcții.
   ================================================================ */

// Lista completă de băuturi primită de la API (înainte de filtrare client-side)
let allBeverages = [];

// Lista de categorii — folosită pentru dropdown-urile de filtrare și formular
let categories = [];

// Timer-ul pentru debounce la căutare (evită request-uri la fiecare tastă)
let searchTimer = null;

/* ================================================================
   Utilitare API
   ================================================================ */

/**
 * api(method, url, body)
 * Funcție generică pentru cereri HTTP REST (GET, POST, PATCH, DELETE).
 * Returnează un obiect { ok, status, data }.
 *
 * @param {string} method - Metoda HTTP: 'GET', 'POST', 'PATCH', 'DELETE'
 * @param {string} url    - URL-ul endpoint-ului API
 * @param {object|null} body - Datele de trimis ca JSON (null pentru GET/DELETE fără body)
 * @returns {Promise<{ok: boolean, status: number, data: object}>}
 */
async function api(method, url, body = null) {
    // Construim opțiunile cererii fetch
    const opts = {
        method,                                               // Metoda HTTP
        headers: { 'Content-Type': 'application/json' },     // Anunțăm serverul că trimitem JSON
    };
    // Adăugăm body-ul JSON numai dacă există date de trimis
    if (body !== null) opts.body = JSON.stringify(body);

    // fetch() execută cererea HTTP asincron; await suspendă până la răspuns
    const res  = await fetch(url, opts);
    // Parsăm răspunsul JSON primit de la server
    const data = await res.json();
    // ok este true dacă statusul HTTP e în intervalul 200-299
    return { ok: res.ok, status: res.status, data };
}

/* ================================================================
   Inițializare — rulat când pagina e complet încărcată
   ================================================================ */

document.addEventListener('DOMContentLoaded', async () => {
    // 1. Încărcăm categoriile (pentru dropdown-urile de filtrare și formular)
    await loadCategories();
    // 2. Încărcăm băuturile și le afișăm în grid
    await loadBeverages();
    // 3. Activăm filtrele (event listeneri pe căutare + dropdown-uri)
    initFilters();
    // 4. Activăm butonul de adăugare (admin) dacă există în DOM
    initAddButton();
    // 5. Activăm submiterea formularului de creare/editare
    initFormSubmit();
    // 6. Activăm închiderea modalelor la click pe fundal sau Escape
    initModalClose();
});

/* ================================================================
   Categorii
   ================================================================ */

/**
 * loadCategories()
 * Încarcă lista de categorii de la API și populează dropdown-urile.
 * Este apelată o singură dată la inițializare.
 */
async function loadCategories() {
    // Facem un GET la endpoint-ul de categorii
    const { ok, data } = await api('GET', '/beverages-app/api/categories.php');
    // Dacă cererea a eșuat, nu facem nimic (dropdown-urile rămân goale)
    if (!ok) return;

    // Salvăm categoriile în variabila globală
    categories = data.categories ?? [];

    // Selectăm dropdown-urile din DOM
    const filterSelect = document.getElementById('filter-category'); // Filtrul din bara de sus
    const formSelect   = document.getElementById('form-category');   // Selectul din formular

    // Adăugăm câte o opțiune pentru fiecare categorie în ambele dropdown-uri
    categories.forEach(cat => {
        // new Option(text, value) creează un element <option>
        filterSelect.add(new Option(cat.name, cat.id));
        // formSelect poate fi null dacă userul nu e admin (butonul nu e în DOM)
        if (formSelect) formSelect.add(new Option(cat.name, cat.id));
    });
}

/* ================================================================
   Băuturi — listare
   ================================================================ */

/**
 * loadBeverages()
 * Citește filtrele active, face un GET la API cu parametrii corespunzători,
 * și randează rezultatele în grid.
 */
async function loadBeverages() {
    // Citim valorile filtrelor din DOM
    const categoryId = document.getElementById('filter-category').value; // '' sau ID numeric
    const season     = document.getElementById('filter-season').value;   // '' sau 'winter' etc.
    const search     = document.getElementById('search-input').value.trim().toLowerCase();

    // Construim query string-ul URL-ului (ex: ?category_id=2&season=winter)
    const params = new URLSearchParams();
    if (categoryId) params.set('category_id', categoryId); // Adăugăm doar dacă e selectat ceva
    if (season)     params.set('season', season);

    // Facem cererea GET la API
    const { ok, data } = await api('GET', '/beverages-app/api/beverages.php?' + params.toString());
    // La eroare, afișăm un mesaj de eroare în grid
    if (!ok) { showGridError(); return; }

    // Salvăm lista completă în variabila globală (fără filtrul de text)
    allBeverages = data.beverages ?? [];

    // Aplicăm filtrul de căutare text pe client (fără request suplimentar)
    // Căutăm în denumire și descriere, case-insensitive
    const filtered = search
        ? allBeverages.filter(b =>
            b.name.toLowerCase().includes(search) ||
            (b.description ?? '').toLowerCase().includes(search)
          )
        : allBeverages; // Dacă nu e text de căutare, afișăm toate

    // Randăm lista filtrată în grid
    renderBeverages(filtered);
}

/**
 * renderBeverages(list)
 * Inserează cardurile de băuturi în grid-ul din DOM.
 * Dacă lista e goală, afișează mesaj informativ.
 *
 * @param {Array} list - Lista de băuturi de afișat
 */
function renderBeverages(list) {
    const grid = document.getElementById('beverages-grid');

    // Cazul în care nu există băuturi de afișat
    if (list.length === 0) {
        grid.innerHTML = `
            <div class="empty-state">
                <span>🧃</span>
                <p>Nicio băutură găsită pentru filtrele selectate.</p>
            </div>`;
        return;
    }

    // Generăm HTML-ul pentru toate cardurile și îl inserăm dintr-o dată
    // (mai eficient decât appendChild repetat — un singur reflow)
    grid.innerHTML = list.map(b => beverageCardHTML(b)).join('');

    // Adăugăm event listeneri de click pe fiecare card
    // Fiecare card are data-id cu ID-ul băuturii
    grid.querySelectorAll('.beverage-card').forEach(card => {
        card.addEventListener('click', () => openDetail(parseInt(card.dataset.id)));
        // Accesibilitate: Enter și Space deschid detaliile (card are role="button")
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openDetail(parseInt(card.dataset.id));
            }
        });
    });
}

/**
 * beverageCardHTML(b)
 * Generează string-ul HTML pentru un card de băutură.
 *
 * @param {object} b - Obiectul băutură primit de la API
 * @returns {string} HTML-ul cardului
 */
function beverageCardHTML(b) {
    // Mapare coduri sezon → etichete românești
    const seasonLabel = {
        all:    'Tot anul',
        winter: 'Iarnă',
        spring: 'Primăvară',
        summer: 'Vară',
        autumn: 'Toamnă',
    };

    // Formatăm prețul: dacă există, afișăm cu 2 zecimale; altfel "—"
    const price = b.price
        ? parseFloat(b.price).toFixed(2) + ' lei'
        : '—';

    // Construim HTML-ul cardului
    return `
    <article
        class="beverage-card"
        data-id="${b.id}"
        tabindex="0"
        role="button"
        aria-label="Vezi detalii pentru ${escHtml(b.name)}"
    >
        <!-- Rândul de sus: badges pentru categorie și sezon -->
        <div class="card-header-row">
            <span class="badge badge-category">${escHtml(b.category_name ?? 'Fără categorie')}</span>
            ${b.season ? `<span class="badge badge-season">${escHtml(seasonLabel[b.season] ?? b.season)}</span>` : ''}
        </div>

        <!-- Denumirea băuturii -->
        <h3 class="card-title">${escHtml(b.name)}</h3>

        <!-- Descrierea — truncată la 3 rânduri prin CSS (-webkit-line-clamp) -->
        ${b.description ? `<p class="card-desc">${escHtml(b.description)}</p>` : ''}

        <!-- Rândul de jos: preț și regiune -->
        <div class="card-footer-row">
            <span class="card-price">${price}</span>
            ${b.region ? `<span class="card-region">📍 ${escHtml(b.region)}</span>` : ''}
        </div>
    </article>`;
}

/* ================================================================
   Modal detalii
   ================================================================ */

/**
 * openDetail(id)
 * Deschide modalul de detalii pentru o băutură.
 * Face un GET suplimentar pentru a obține ingredientele, alergenii și localurile.
 *
 * @param {number} id - ID-ul băuturii
 */
async function openDetail(id) {
    const modal = document.getElementById('modal-detail');
    const body  = document.getElementById('detail-body');

    // Deschidem modalul imediat și afișăm un spinner de loading
    modal.classList.add('open');
    body.innerHTML = '<p class="loading-text">Se încarcă detaliile...</p>';

    // Cerem detaliile complete (cu ingrediente, alergeni, localuri) de la API
    const { ok, data } = await api('GET', `/beverages-app/api/beverages.php?id=${id}`);
    if (!ok) {
        body.innerHTML = '<p class="error-text">Eroare la încărcarea detaliilor.</p>';
        return;
    }

    const b = data.beverage;

    // Verificăm dacă userul curent e admin (citim atributul setat pe <body> de PHP)
    const isAdmin    = document.body.dataset.role === 'admin';
    const seasonLabel = {
        all: 'Tot anul', winter: 'Iarnă', spring: 'Primăvară', summer: 'Vară', autumn: 'Toamnă',
    };

    // Construim HTML-ul complet al modalului de detalii
    body.innerHTML = `
        <!-- Titlul băuturii -->
        <h2 class="detail-title" id="detail-title-el">${escHtml(b.name)}</h2>

        <!-- Badges: categorie și sezon -->
        <div class="detail-badges">
            <span class="badge badge-category">${escHtml(b.category_name ?? '—')}</span>
            ${b.season ? `<span class="badge badge-season">${escHtml(seasonLabel[b.season] ?? b.season)}</span>` : ''}
        </div>

        <!-- Descrierea completă (fără truncare, spre deosebire de card) -->
        ${b.description ? `<p class="detail-desc">${escHtml(b.description)}</p>` : ''}

        <!-- Tabel cu detaliile principale (dl = description list) -->
        <dl class="detail-dl">
            <dt>Preț</dt>
            <dd>${b.price ? parseFloat(b.price).toFixed(2) + ' lei' : '—'}</dd>
            <dt>Regiune</dt>
            <dd>${escHtml(b.region ?? '—')}</dd>
            <dt>Perisabilitate</dt>
            <dd>${b.perishability_days ? b.perishability_days + ' zile' : '—'}</dd>
        </dl>

        <!-- Secțiunea Ingrediente — afișată doar dacă există -->
        ${b.ingredients?.length ? `
        <div class="detail-section">
            <h4>Ingrediente</h4>
            <ul class="tag-list">
                ${b.ingredients.map(i =>
                    `<li class="tag">${escHtml(i.name)}${i.quantity ? ` <span>(${escHtml(i.quantity)})</span>` : ''}</li>`
                ).join('')}
            </ul>
        </div>` : ''}

        <!-- Secțiunea Alergeni — afișată doar dacă există; stilizat cu roșu (tag-danger) -->
        ${b.allergens?.length ? `
        <div class="detail-section">
            <h4>Alergeni</h4>
            <ul class="tag-list">
                ${b.allergens.map(a => `<li class="tag tag-danger">⚠ ${escHtml(a.name)}</li>`).join('')}
            </ul>
        </div>` : ''}

        <!-- Secțiunea Localuri — afișată doar dacă există -->
        ${b.venues?.length ? `
        <div class="detail-section">
            <h4>Localuri disponibile</h4>
            <ul class="venue-list">
                ${b.venues.map(v =>
                    `<li>${escHtml(v.name)}${v.location ? ` <span class="venue-loc">— ${escHtml(v.location)}</span>` : ''}</li>`
                ).join('')}
            </ul>
        </div>` : ''}

        <!-- Butoane admin: Editează și Șterge — vizibile doar pentru admin -->
        ${isAdmin ? `
        <div class="detail-admin-actions">
            <button class="btn btn-outline btn-sm" onclick="openForm(${b.id})">✏️ Editează</button>
            <button class="btn btn-danger btn-sm" onclick="deleteBeverage(${b.id}, ${JSON.stringify(b.name)})">🗑 Șterge</button>
        </div>` : ''}
    `;
}

/* ================================================================
   Modal formular adăugare/editare (admin)
   ================================================================ */

/**
 * openForm(id)
 * Deschide modalul cu formularul de creare sau editare a unei băuturi.
 * Dacă id este null → mod "adăugare" (formular gol).
 * Dacă id este un număr → mod "editare" (formular pre-completat).
 *
 * @param {number|null} id - ID-ul băuturii de editat, sau null pentru adăugare
 */
async function openForm(id = null) {
    // Închidem modalul de detalii dacă era deschis (ex: buton Editează din detalii)
    document.getElementById('modal-detail').classList.remove('open');

    const modal = document.getElementById('modal-form');
    const title = document.getElementById('form-title');
    const form  = document.getElementById('beverage-form');
    const alert = document.getElementById('form-alert');

    // Resetăm formularul la starea inițială (golim toate câmpurile)
    form.reset();
    // Ștergem ID-ul ascuns (îl vom completa mai jos dacă suntem la editare)
    document.getElementById('form-id').value = '';
    // Ascundem alerta de eroare din formularul anterior
    alert.classList.remove('visible');
    alert.textContent = '';

    if (id) {
        // Mod EDITARE: actualizăm titlul și pre-completăm câmpurile
        title.textContent = 'Editează băutură';
        modal.classList.add('open');

        // Cerem datele băuturii de la API pentru a pre-completa formularul
        const { ok, data } = await api('GET', `/beverages-app/api/beverages.php?id=${id}`);
        if (!ok) return;
        const b = data.beverage;

        // Completăm câmpul ascuns cu ID-ul (folosit la PATCH)
        document.getElementById('form-id').value            = b.id;
        document.getElementById('form-name').value          = b.name        ?? '';
        document.getElementById('form-category').value      = b.category_id ?? '';
        document.getElementById('form-price').value         = b.price       ?? '';
        document.getElementById('form-description').value   = b.description ?? '';
        document.getElementById('form-season').value        = b.season      ?? '';
        document.getElementById('form-region').value        = b.region      ?? '';
        document.getElementById('form-perishability').value = b.perishability_days ?? '';
    } else {
        // Mod ADĂUGARE: titlul implicit și formular gol
        title.textContent = 'Adaugă băutură';
        modal.classList.add('open');
    }
}

/**
 * initFormSubmit()
 * Activează event listener-ul pe submit-ul formularului de băutură.
 * Trimite POST (adăugare) sau PATCH (editare) la API.
 */
function initFormSubmit() {
    document.getElementById('beverage-form').addEventListener('submit', async e => {
        // Prevenim reload-ul implicit al paginii la submit
        e.preventDefault();

        const alert = document.getElementById('form-alert');
        // Ascundem alerta din submit-ul anterior
        alert.classList.remove('visible');

        // Citim ID-ul ascuns: prezent la editare, gol la adăugare
        const id = document.getElementById('form-id').value;

        // Construim obiectul cu datele din formular
        const body = {
            name:              document.getElementById('form-name').value.trim()         || null,
            category_id:       document.getElementById('form-category').value            || null,
            price:             document.getElementById('form-price').value               || null,
            description:       document.getElementById('form-description').value.trim()  || null,
            season:            document.getElementById('form-season').value              || null,
            region:            document.getElementById('form-region').value.trim()       || null,
            perishability_days: document.getElementById('form-perishability').value      || null,
        };

        // Validare minimă client-side: numele e obligatoriu
        if (!body.name) {
            const nameInput = document.getElementById('form-name');
            nameInput.classList.add('error');
            nameInput.focus();
            return;
        }

        // Dacă id există → editare (PATCH la /api/beverages.php?id=X)
        // Dacă nu → adăugare (POST la /api/beverages.php)
        const url    = id ? `/beverages-app/api/beverages.php?id=${id}` : '/beverages-app/api/beverages.php';
        const method = id ? 'PATCH' : 'POST';

        // Activăm starea de loading pe butonul de submit
        const btn = e.target.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.classList.add('loading');

        const { ok, data } = await api(method, url, body);

        // Dezactivăm loading indiferent de rezultat
        btn.disabled = false;
        btn.classList.remove('loading');

        if (ok) {
            // Succes: închidem modalul și reîncărcăm lista de băuturi
            closeModal('modal-form');
            await loadBeverages();
        } else {
            // Eroare de la server: afișăm mesajul în alerta din formular
            alert.textContent = data.error ?? 'A apărut o eroare. Încearcă din nou.';
            alert.classList.add('visible');
        }
    });
}

/* ================================================================
   Ștergere băutură
   ================================================================ */

/**
 * deleteBeverage(id, name)
 * Cere confirmare utilizatorului și trimite un request DELETE la API.
 *
 * @param {number} id   - ID-ul băuturii
 * @param {string} name - Denumirea băuturii (pentru mesajul de confirmare)
 */
async function deleteBeverage(id, name) {
    // confirm() afișează o casetă nativă de confirmare; returnează true/false
    if (!confirm(`Ești sigur că vrei să ștergi "${name}"?\nAcțiunea este ireversibilă.`)) return;

    const { ok, data } = await api('DELETE', `/beverages-app/api/beverages.php?id=${id}`);

    if (ok) {
        // Închidem modalul de detalii și reîncărcăm lista
        closeModal('modal-detail');
        await loadBeverages();
    } else {
        // Eroare: afișăm mesajul primit de la server
        alert(data.error ?? 'Eroare la ștergerea băuturii.');
    }
}

/* ================================================================
   Filtre
   ================================================================ */

/**
 * initFilters()
 * Atașează event listeneri pe elementele de filtrare.
 * Căutarea text folosește debounce pentru a limita request-urile.
 */
function initFilters() {
    // Dropdown categorie: reload imediat la schimbare
    document.getElementById('filter-category').addEventListener('change', loadBeverages);
    // Dropdown sezon: reload imediat la schimbare
    document.getElementById('filter-season').addEventListener('change', loadBeverages);

    // Căutare text: debounce de 300ms — așteptăm să termine de scris
    document.getElementById('search-input').addEventListener('input', () => {
        // clearTimeout anulează timer-ul anterior dacă userul mai scrie
        clearTimeout(searchTimer);
        // setTimeout repornește numărătoarea; după 300ms fără tastare, se execută
        searchTimer = setTimeout(loadBeverages, 300);
    });
}

/**
 * initAddButton()
 * Atașează listener pe butonul "+ Adaugă băutură".
 * Butonul există în DOM doar dacă PHP-ul a detectat rol admin.
 */
function initAddButton() {
    const btn = document.getElementById('btn-add');
    // Dacă butonul nu există (userul nu e admin), nu facem nimic
    if (!btn) return;
    btn.addEventListener('click', () => openForm()); // null = adăugare
}

/* ================================================================
   Modale — deschidere/închidere
   ================================================================ */

/**
 * closeModal(id)
 * Închide un modal eliminând clasa 'open'.
 *
 * @param {string} id - ID-ul elementului modal-overlay
 */
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

/**
 * initModalClose()
 * Activează închiderea modalelor prin:
 *  - Click pe fundal (overlay), nu pe conținut
 *  - Tasta Escape
 */
function initModalClose() {
    // Click pe fundal întunecat (overlay) — nu pe modal-box din interior
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            // e.target e elementul pe care s-a dat click
            // dacă e chiar overlay-ul (nu un copil al lui), închidem
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // Tasta Escape închide orice modal deschis
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open')
                    .forEach(m => m.classList.remove('open'));
        }
    });
}

/* ================================================================
   Stare de eroare pentru grid
   ================================================================ */

/**
 * showGridError()
 * Afișează un mesaj de eroare în grid dacă request-ul API eșuează.
 */
function showGridError() {
    document.getElementById('beverages-grid').innerHTML = `
        <div class="empty-state">
            <span>⚠️</span>
            <p>Eroare la încărcarea băuturilor. Verifică conexiunea și încearcă din nou.</p>
        </div>`;
}

/* ================================================================
   Utilitare generale
   ================================================================ */

/**
 * escHtml(str)
 * Sanitizează un string pentru inserare sigură în innerHTML.
 * Previne atacurile XSS prin escaparea caracterelor HTML speciale.
 *
 * @param {string|null} str - String-ul de sanitizat
 * @returns {string} String-ul cu caracterele periculoase escapate
 */
function escHtml(str) {
    if (str === null || str === undefined) return '';
    // Convertim la string și înlocuim caracterele periculoase
    return String(str)
        .replace(/&/g,  '&amp;')   // & → &amp;  (trebuie primul, altfel escapăm și &amp; de mai jos)
        .replace(/</g,  '&lt;')    // < → &lt;   (previne injecție de taguri HTML)
        .replace(/>/g,  '&gt;')    // > → &gt;
        .replace(/"/g,  '&quot;')  // " → &quot; (previne spargerea atributelor)
        .replace(/'/g,  '&#39;');  // ' → &#39;  (previne spargerea atributelor cu ghilimele simple)
}
