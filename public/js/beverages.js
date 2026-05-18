/**
 * public/js/beverages.js
 *
 * Logica JavaScript pentru pagina beverages.php.
 * Responsabilități:
 *   - Încărcarea și afișarea băuturilor din API
 *   - Filtrarea băuturilor după categorie, sezon, regiune
 *   - Modalul de adăugare și editare (doar admin)
 *   - Ștergerea băuturilor (doar admin)
 */

// ============================================================
// STATE — starea globală a paginii
// Variabile JavaScript care trăiesc cât timp pagina e deschisă.
// ============================================================

// Lista categoriilor încărcată o dată din API — reutilizată în dropdown-uri.
let categories = [];

// Lista băuturilor afișate curent în grilă — folosită la editare (fără extra-apel API).
let currentBeverages = [];

// ID-ul băuturii care se editează. null = formularul creează o băutură nouă.
let editingId = null;

// ============================================================
// REFERINȚE DOM
// Salvăm referințele la elementele HTML în variabile,
// ca să nu le căutăm de fiecare dată în DOM (mai eficient).
// document.getElementById() returnează elementul cu acel id="" din HTML.
// ============================================================

const grid          = document.getElementById('beverages-grid');   // grila de carduri
const pageAlert     = document.getElementById('page-alert');        // bannerul de mesaje
const overlay       = document.getElementById('modal-overlay');     // fundalul întunecat al modalului
const modalTitle    = document.getElementById('modal-title');        // titlul modalului

// Butoanele paginii principale
const btnAdd        = document.getElementById('btn-add-beverage');  // "+ Adaugă băutură"
const btnFilter     = document.getElementById('btn-filter');        // "Filtrează"
const btnClear      = document.getElementById('btn-clear-filters'); // "Resetează"

// Butoanele modalului
const btnClose      = document.getElementById('btn-close-modal');   // X din colțul drept
const btnCancel     = document.getElementById('btn-cancel-modal');  // "Anulează"
const btnSave       = document.getElementById('btn-save-modal');    // "Salvează"

// Filtrele din bara de sus
const filterCategory = document.getElementById('filter-category');
const filterSeason   = document.getElementById('filter-season');
const filterRegion   = document.getElementById('filter-region');

// Câmpurile formularului din modal
const fId            = document.getElementById('form-id');
const fName          = document.getElementById('form-name');
const fCategory      = document.getElementById('form-category');
const fPrice         = document.getElementById('form-price');
const fDescription   = document.getElementById('form-description');
const fSeason        = document.getElementById('form-season');
const fRegion        = document.getElementById('form-region');
const fPerishability = document.getElementById('form-perishability');

// Mesajul de eroare de sub câmpul Nume
const errName = document.getElementById('err-name');

// ============================================================
// INIT — punctul de intrare al scriptului
// DOMContentLoaded se declanșează când HTML-ul e complet încărcat,
// dar înainte ca imaginile și fonturile să fie descărcate.
// ============================================================

document.addEventListener('DOMContentLoaded', async () => {
    // Încărcăm categoriile și băuturile în PARALEL cu Promise.all().
    // Promise.all([a, b]) pornește ambele cereri simultan și așteaptă
    // ca AMBELE să se termine — mai rapid decât await a; await b; (secvențial).
    await Promise.all([loadCategories(), loadBeverages()]);

    // Înregistrăm event listeners după ce DOM-ul și datele sunt gata.
    bindEvents();
});

// ============================================================
// API — funcții pentru comunicarea cu serverul
// ============================================================

/**
 * Construiește un URL absolut cu query parameters dintr-un obiect.
 * Exemplu: buildUrl('/api/beverages.php', { season: 'summer', category_id: 3 })
 *          → 'http://localhost/beverages-app/api/beverages.php?season=summer&category_id=3'
 *
 * URL() primește un path relativ și un origin (baza URL-ului curent).
 * searchParams.set() adaugă parametrii; îi omitem pe cei goali (filtre necompletate).
 */
function buildUrl(path, params = {}) {
    const url = new URL(window.APP.basePath + path, window.location.origin);
    // Object.entries() transformă obiectul în array de perechi [cheie, valoare].
    // forEach iterează prin fiecare pereche.
    Object.entries(params).forEach(([key, value]) => {
        // Adăugăm parametrul în URL doar dacă valoarea nu e goală.
        // Filtrele necompletate nu trebuie să apară în URL.
        if (value) url.searchParams.set(key, value);
    });
    return url.toString(); // returnăm URL-ul ca string
}

/**
 * Funcție generică pentru cereri fetch (GET, POST, PATCH, DELETE).
 * Aruncă o eroare cu mesajul din JSON dacă serverul răspunde cu status non-2xx.
 *
 * options = obiect cu method, body etc. — documentat la MDN fetch API.
 */
async function apiFetch(path, options = {}) {
    const res = await fetch(window.APP.basePath + path, {
        // Setăm Content-Type implicit la JSON pentru toate cererile.
        // Spread (...options) suprascrie dacă options conține propriul header.
        headers: { 'Content-Type': 'application/json' },
        ...options
    });

    // Parsăm răspunsul ca JSON indiferent de status (avem nevoie de mesajul de eroare).
    const json = await res.json();

    // Dacă statusul HTTP e 4xx sau 5xx, aruncăm o eroare cu mesajul din răspuns.
    // Eroarea va fi prinsă de try/catch din funcțiile apelante.
    if (!res.ok) throw new Error(json.error || 'Eroare necunoscuta.');

    return json;
}

/**
 * Încarcă categoriile din API și populează dropdown-urile din filtru și formular.
 */
async function loadCategories() {
    try {
        const data = await apiFetch('/api/categories.php');
        categories = data.categories; // stocăm în variabila globală
        populateCategoryDropdowns();  // actualizăm UI-ul
    } catch (e) {
        // Nu oprim pagina dacă categoriile nu se pot încărca — afișăm doar un avertisment.
        showAlert('Nu s-au putut încărca categoriile.', 'error');
    }
}

/**
 * Încarcă băuturile din API, opțional cu filtre, și le afișează în grilă.
 * filters = obiect cu cheile category_id, season, region (toate opționale).
 */
async function loadBeverages(filters = {}) {
    // Afișăm starea de "Se încarcă..." cât timp așteptăm răspunsul API.
    grid.innerHTML = '<div class="loading-state">Se încarcă băuturile…</div>';

    try {
        // buildUrl() construiește URL-ul cu filtrele ca query params.
        const url = buildUrl('/api/beverages.php', filters);

        // Folosim fetch() direct (nu apiFetch) ca să gestionăm mai ușor grila.
        const res  = await fetch(url);
        const data = await res.json();

        // Salvăm lista curentă — folosită la openEditModal pentru a evita extra-apel API.
        currentBeverages = data.beverages || [];

        // Afișăm cardurile în grilă.
        renderBeverages(currentBeverages);
    } catch (e) {
        grid.innerHTML = '<div class="empty-state">Eroare la încărcarea băuturilor. Reîncarcă pagina.</div>';
    }
}

/**
 * Trimite cererea de salvare (POST sau PATCH) după validarea formularului.
 */
async function saveBeverage() {
    // Validare client-side: câmpul Nume este obligatoriu.
    if (!fName.value.trim()) {
        // Adăugăm clasa 'error' pe input pentru borderul roșu.
        fName.classList.add('error');
        errName.textContent = 'Numele este obligatoriu.';
        // Clasa 'visible' face span-ul vizibil (display: block).
        errName.classList.add('visible');
        fName.focus(); // mutăm focus-ul pe câmpul invalid
        return; // oprim execuția — nu trimitem cererea
    }

    // Construim obiectul de date pentru body-ul cererii JSON.
    // null pentru câmpurile opționale lăsate goale — API-ul le acceptă.
    const payload = {
        name:               fName.value.trim(),
        category_id:        fCategory.value  ? parseInt(fCategory.value)       : null,
        price:              fPrice.value      ? parseFloat(fPrice.value)        : null,
        description:        fDescription.value.trim()                          || null,
        season:             fSeason.value                                      || null,
        region:             fRegion.value.trim()                               || null,
        perishability_days: fPerishability.value ? parseInt(fPerishability.value) : null,
    };

    // Activăm starea de "loading" pe buton: spinner vizibil, textul ascuns.
    btnSave.classList.add('loading');
    btnSave.disabled = true;

    try {
        if (editingId) {
            // PATCH — modificare băutură existentă cu ID-ul editingId.
            // JSON.stringify() serializează obiectul JS ca string JSON pentru body.
            await apiFetch(`/api/beverages.php?id=${editingId}`, {
                method: 'PATCH',
                body:   JSON.stringify(payload)
            });
            showAlert('Băutura a fost actualizată cu succes.', 'success');
        } else {
            // POST — creare băutură nouă.
            await apiFetch('/api/beverages.php', {
                method: 'POST',
                body:   JSON.stringify(payload)
            });
            showAlert('Băutura a fost adăugată cu succes.', 'success');
        }

        closeModal();                        // închidem formularul
        loadBeverages(getActiveFilters());   // reîncărcăm lista cu filtrele active
    } catch (e) {
        // Afișăm mesajul de eroare venit de la server.
        showAlert(e.message, 'error');
    } finally {
        // finally rulează ÎNTOTDEAUNA (indiferent de succes/eroare).
        // Dezactivăm starea de loading pe buton.
        btnSave.classList.remove('loading');
        btnSave.disabled = false;
    }
}

/**
 * Trimite cererea de ștergere pentru băutura cu ID-ul dat.
 * Cere confirmare înainte — acțiunea e ireversibilă.
 */
async function deleteBeverage(id) {
    // confirm() afișează un dialog nativ al browserului cu OK / Anulează.
    // Returnează true dacă userul apasă OK, false dacă apasă Anulează.
    if (!confirm('Ești sigur că vrei să ștergi această băutură? Acțiunea este ireversibilă.')) {
        return; // userul a anulat — nu facem nimic
    }

    try {
        // DELETE cu ID-ul băuturii în query string.
        await apiFetch(`/api/beverages.php?id=${id}`, { method: 'DELETE' });
        showAlert('Băutura a fost ștearsă.', 'success');
        // Reîncărcăm lista păstrând filtrele active.
        loadBeverages(getActiveFilters());
    } catch (e) {
        showAlert(e.message, 'error');
    }
}

// ============================================================
// RENDER — construieste HTML din datele primite
// ============================================================

/**
 * Populează dropdown-urile de categorie din filtru și formular
 * cu datele din variabila globală `categories`.
 */
function populateCategoryDropdowns() {
    // map() transformă array-ul de categorii în array de string-uri HTML.
    // join('') le unește într-un singur string fără separator.
    const options = categories
        .map(c => `<option value="${c.id}">${escHtml(c.name)}</option>`)
        .join('');

    // Adăugăm opțiunile după opțiunea implicită din fiecare dropdown.
    filterCategory.innerHTML = '<option value="">Toate categoriile</option>' + options;
    fCategory.innerHTML      = '<option value="">— fără categorie —</option>' + options;
}

// Dicționar pentru afișarea frumoasă a valorilor de sezon.
// Cheile corespund valorilor stocate în baza de date.
const SEASON_LABELS = {
    spring: '🌸 Primăvară',
    summer: '☀️ Vară',
    autumn: '🍂 Toamnă',
    winter: '❄️ Iarnă',
    all:    '🌍 Tot anul'
};

/**
 * Generează HTML-ul pentru grila de carduri și îl inserează în DOM.
 * beverages = array de obiecte băutură venite din API.
 */
function renderBeverages(beverages) {
    // Dacă nu există băuturi (array gol), afișăm un mesaj informativ.
    if (!beverages.length) {
        grid.innerHTML = `
            <div class="empty-state">
                Nicio băutură găsită.<br>
                Încearcă să resetezi filtrele sau adaugă prima băutură.
            </div>`;
        return;
    }

    // map() generează un string HTML pentru fiecare băutură.
    // Template literals (backtick) permit interpolare cu ${expresie}.
    grid.innerHTML = beverages.map(b => `
        <div class="beverage-card">

            <!-- Antetul cardului: categoria și butoanele de acțiune (admin) -->
            <div class="beverage-card-header">
                <!-- Eticheta de categorie — "Fără categorie" dacă lipsește -->
                <span class="beverage-category">${escHtml(b.category_name || 'Fără categorie')}</span>

                ${window.APP.isAdmin ? `
                <!-- Butoanele de editare și ștergere — vizibile doar pentru admin.
                     Folosim onclick cu ID-ul (număr întreg) — sigur față de XSS
                     deoarece b.id vine din baza de date ca INTEGER. -->
                <div class="card-actions">
                    <button class="btn-icon" title="Editează" onclick="openEditModal(${b.id})">✏️</button>
                    <button class="btn-icon btn-icon-danger" title="Șterge" onclick="deleteBeverage(${b.id})">🗑️</button>
                </div>` : ''}
            </div>

            <!-- Numele băuturii — escHtml previne XSS dacă numele conține < > -->
            <h3 class="beverage-name">${escHtml(b.name)}</h3>

            <!-- Descrierea — afișată doar dacă există (truthy) -->
            ${b.description ? `<p class="beverage-desc">${escHtml(b.description)}</p>` : ''}

            <!-- Badge-uri cu metadatele importante -->
            <div class="beverage-meta">
                <!-- parseFloat().toFixed(2) formatează prețul cu exact 2 zecimale: 8 → "8.00" -->
                ${b.price              ? `<span class="badge badge-price">${parseFloat(b.price).toFixed(2)} lei</span>` : ''}
                <!-- SEASON_LABELS[b.season] returnează eticheta frumoasă sau b.season ca fallback -->
                ${b.season             ? `<span class="badge">${SEASON_LABELS[b.season] || escHtml(b.season)}</span>` : ''}
                ${b.region             ? `<span class="badge">📍 ${escHtml(b.region)}</span>` : ''}
                ${b.perishability_days ? `<span class="badge">⏱ ${b.perishability_days}z</span>` : ''}
            </div>
        </div>
    `).join(''); // join('') unește toate cardurile HTML într-un singur string
}

// ============================================================
// MODAL — deschidere, închidere, populare cu date
// ============================================================

/**
 * Deschide modalul în modul "Adaugă băutură nouă".
 * Resetează formularul și curăță erorile anterioare.
 */
function openAddModal() {
    editingId = null; // null = POST (creare), nu PATCH (modificare)
    modalTitle.textContent = 'Adaugă băutură';
    document.getElementById('beverage-form').reset(); // golește toate câmpurile
    clearFormErrors(); // ascunde mesajele de eroare
    overlay.classList.remove('hidden'); // afișează modalul
    fName.focus(); // focus pe primul câmp pentru UX mai bun
}

/**
 * Deschide modalul în modul "Editează" și populează câmpurile cu datele existente.
 * id = ID-ul băuturii de editat (primit din onclick="openEditModal(X)").
 */
function openEditModal(id) {
    // Căutăm băutura în lista curentă — evităm un extra-apel API.
    // Array.find() returnează primul element care satisface condiția.
    const b = currentBeverages.find(x => x.id === id);
    if (!b) return; // dacă nu e găsită (situație improbabilă), nu facem nimic

    editingId = id; // setăm ID-ul pentru cererea PATCH
    modalTitle.textContent = 'Editează băutură';
    clearFormErrors();

    // Populăm câmpurile formularului cu valorile băuturii.
    // || '' = dacă valoarea e null/undefined, folosim string gol (input-ul nu afișează "null").
    fId.value            = b.id;
    fName.value          = b.name              || '';
    fCategory.value      = b.category_id       || '';
    fPrice.value         = b.price             || '';
    fDescription.value   = b.description       || '';
    fSeason.value        = b.season            || '';
    fRegion.value        = b.region            || '';
    fPerishability.value = b.perishability_days || '';

    overlay.classList.remove('hidden');
    fName.focus();
}

/**
 * Închide modalul și resetează starea de editare.
 */
function closeModal() {
    overlay.classList.add('hidden');
    editingId = null;
}

/**
 * Ascunde mesajele de eroare din formular.
 * Apelată când se deschide modalul (ca erorile anterioare să nu persiste).
 */
function clearFormErrors() {
    fName.classList.remove('error');
    errName.textContent = '';
    errName.classList.remove('visible');
}

// ============================================================
// FILTERS — gestionarea filtrelor de căutare
// ============================================================

/**
 * Citește valorile curente din bara de filtrare și le returnează ca obiect.
 * Folosit la (re)încărcarea băuturilor după aplicarea sau resetarea filtrelor.
 */
function getActiveFilters() {
    return {
        category_id: filterCategory.value,  // ID-ul categoriei selectate (sau '')
        season:      filterSeason.value,    // sezonul selectat (sau '')
        region:      filterRegion.value.trim() // textul introdus pentru regiune
    };
}

// ============================================================
// ALERTS — mesaje de feedback pentru utilizator
// ============================================================

/**
 * Afișează un banner de mesaj în partea de sus a paginii.
 * type = 'success' (verde) sau 'error' (roșu) — corespunde claselor CSS.
 * Se ascunde automat după 4 secunde.
 */
function showAlert(message, type = 'error') {
    pageAlert.textContent = message;
    // Resetăm clasele și adăugăm cele noi.
    pageAlert.className = `alert alert-${type} visible`;

    // setTimeout() rulează o funcție după un delay în milisecunde.
    // 4000ms = 4 secunde.
    setTimeout(() => {
        pageAlert.classList.remove('visible'); // ascundem bannerul
    }, 4000);
}

// ============================================================
// EVENTS — înregistrarea event listeners
// ============================================================

/**
 * Înregistrează toate event listeners ale paginii.
 * Apelată o singură dată după inițializare.
 */
function bindEvents() {
    // Butonul "+ Adaugă băutură" (există doar pentru admin, deci verificăm cu ?.)
    // Operatorul ?. (optional chaining) previne eroarea dacă elementul e null.
    btnAdd?.addEventListener('click', openAddModal);

    // Butoanele de închidere a modalului
    btnClose.addEventListener('click', closeModal);
    btnCancel.addEventListener('click', closeModal);

    // Butonul Salvează din modal
    btnSave.addEventListener('click', saveBeverage);

    // Butoanele de filtrare
    btnFilter.addEventListener('click', () => loadBeverages(getActiveFilters()));
    btnClear.addEventListener('click', () => {
        // Resetăm toate filtrele la valorile implicite.
        filterCategory.value = '';
        filterSeason.value   = '';
        filterRegion.value   = '';
        loadBeverages(); // reîncărcăm fără filtre
    });

    // Închide modalul la click pe fundalul întunecat (în afara cardului modal).
    // e.target = elementul pe care s-a dat click.
    // Verificăm că e exact overlay-ul, nu un element copil din interiorul modalului.
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal();
    });

    // Permite filtrarea apăsând Enter pe câmpul de regiune.
    filterRegion.addEventListener('keydown', e => {
        if (e.key === 'Enter') loadBeverages(getActiveFilters());
    });

    // Curăță eroarea de validare când userul începe să scrie în câmpul Nume.
    fName.addEventListener('input', () => {
        fName.classList.remove('error');
        errName.classList.remove('visible');
    });
}

// ============================================================
// UTILS — funcții utilitare
// ============================================================

/**
 * Escape HTML — previne XSS când inserăm text dinamic în innerHTML.
 * Convertește caracterele speciale HTML în entități sigure.
 * Echivalentul JavaScript al htmlspecialchars() din PHP.
 *
 * Exemplu: escHtml('<script>') → '&lt;script&gt;'
 */
function escHtml(str) {
    if (str === null || str === undefined) return ''; // returnăm string gol pentru null
    return String(str)
        .replace(/&/g,  '&amp;')   // & primul, altfel am escapa entitățile deja escapate
        .replace(/</g,  '&lt;')    // < previne deschiderea de taguri HTML
        .replace(/>/g,  '&gt;')    // > previne închiderea de taguri HTML
        .replace(/"/g,  '&quot;')  // " previne spargerea atributelor HTML
        .replace(/'/g,  '&#039;'); // ' previne spargerea atributelor HTML cu ghilimele simple
}

// ============================================================
// EXPORT — funcții accesibile din atributele onclick="" din HTML
// Funcțiile definite cu "function" sunt locale scriptului și nu sunt
// accesibile din HTML inline (onclick). Le atașăm explicit pe window.
// ============================================================
window.openEditModal  = openEditModal;
window.deleteBeverage = deleteBeverage;
