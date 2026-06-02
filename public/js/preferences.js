/**
 * preferences.js
 * Logica paginii de preferințe (rating-uri și notițe).
 *
 * Fiecare utilizator poate:
 *  - Vedea toate băuturile din catalog
 *  - Acorda un rating de 1-5 stele fiecărei băuturi
 *  - Adăuga notițe personale
 *  - Filtra: toate / evaluate / neevaluate
 *  - Șterge un rating existent
 */

/* ================================================================
   State global
   ================================================================ */

let allBeverages  = []; // Toate băuturile din catalog
let myPreferences = {}; // Map beverage_id → {rating, notes} — preferințele userului curent
let activeFilter  = 'all'; // Filtrul activ: 'all' | 'rated' | 'unrated'
let currentRating = 0;     // Rating-ul selectat curent în modal

/* ================================================================
   Utilitare API
   ================================================================ */

async function api(method, url, body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body !== null) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Eroare server.');
    return data;
}

/* ================================================================
   Inițializare
   ================================================================ */

document.addEventListener('DOMContentLoaded', async () => {
    // Încărcăm în paralel băuturile și preferințele existente
    await Promise.all([loadBeverages(), loadPreferences()]);
    renderGrid();
    initFilters();
    initForm();
    initModal();
});

/* ================================================================
   Încărcare date
   ================================================================ */

async function loadBeverages() {
    const data = await api('GET', '/beverages-app/api/beverages.php');
    allBeverages = data.beverages ?? [];
}

async function loadPreferences() {
    const data = await api('GET', '/beverages-app/api/preferences.php');
    // Transformăm array-ul în map beverage_id → preferință pentru acces rapid
    myPreferences = {};
    (data.preferences ?? []).forEach(p => {
        myPreferences[p.beverage_id] = p;
    });
}

/* ================================================================
   Grid de carduri
   ================================================================ */

function renderGrid() {
    const grid = document.getElementById('pref-grid');

    // Aplicăm filtrul activ
    const visible = allBeverages.filter(b => {
        if (activeFilter === 'rated')   return !!myPreferences[b.id];
        if (activeFilter === 'unrated') return !myPreferences[b.id];
        return true; // 'all'
    });

    if (!visible.length) {
        grid.innerHTML = `<div class="empty-state"><span>⭐</span><p>Nicio băutură de afișat pentru filtrul selectat.</p></div>`;
        return;
    }

    grid.innerHTML = visible.map(b => prefCardHTML(b)).join('');

    // Atașăm listener pe fiecare card pentru a deschide modalul de editare
    grid.querySelectorAll('.pref-card').forEach(card => {
        card.addEventListener('click', () => openModal(parseInt(card.dataset.id)));
    });
}

/**
 * prefCardHTML(b)
 * Generează HTML-ul pentru un card de preferință.
 */
function prefCardHTML(b) {
    const pref  = myPreferences[b.id]; // Preferința existentă (sau undefined)
    const rated = !!pref;              // Dacă există rating
    const stars = rated ? renderStars(pref.rating) : '<span style="color:var(--clr-muted);font-size:.85rem">Neevaluat</span>';
    const price = b.price ? parseFloat(b.price).toFixed(2) + ' lei' : '—';

    return `
    <div class="pref-card ${rated ? 'pref-card--rated' : ''}"
         data-id="${b.id}" role="button" tabindex="0"
         aria-label="Evaluează ${escHtml(b.name)}">
        <!-- Badge categorie -->
        <div class="card-header-row">
            <span class="badge badge-category">${escHtml(b.category_name ?? 'Fără categorie')}</span>
        </div>
        <!-- Numele băuturii -->
        <h3 class="card-title">${escHtml(b.name)}</h3>
        <!-- Stelele de rating sau "Neevaluat" -->
        <div class="pref-stars">${stars}</div>
        <!-- Notița scurtă dacă există -->
        ${pref?.notes ? `<p class="pref-notes-preview">${escHtml(pref.notes)}</p>` : ''}
        <!-- Prețul -->
        <div class="card-footer-row">
            <span class="card-price">${price}</span>
            <span class="pref-action-hint">${rated ? '✏️ Editează' : '+ Evaluează'}</span>
        </div>
    </div>`;
}

/**
 * renderStars(rating)
 * Generează HTML cu stele colorate pentru un rating 1-5.
 */
function renderStars(rating) {
    // Stele pline (★) pentru ratingul acordat, stele goale (☆) pentru restul
    return Array.from({ length: 5 }, (_, i) =>
        `<span style="color:${i < rating ? '#f59e0b' : '#cbd5e1'};font-size:1.1rem">${i < rating ? '★' : '☆'}</span>`
    ).join('');
}

/* ================================================================
   Filtre
   ================================================================ */

function initFilters() {
    document.querySelectorAll('.pref-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            // Schimbăm filtrul activ
            activeFilter = btn.dataset.filter;
            // Actualizăm stilul butoanelor (active/inactive)
            document.querySelectorAll('.pref-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            // Re-randăm grid-ul cu noul filtru
            renderGrid();
        });
    });
}

/* ================================================================
   Modal editare preferință
   ================================================================ */

/**
 * openModal(beverageId)
 * Deschide modalul de rating pre-completat cu datele existente (dacă există).
 */
function openModal(beverageId) {
    const bev  = allBeverages.find(b => b.id == beverageId);
    const pref = myPreferences[beverageId];

    // Completăm titlul modalului cu numele băuturii
    document.getElementById('pref-modal-title').textContent = bev?.name ?? 'Evaluează băutura';
    // Setăm ID-ul ascuns
    document.getElementById('pref-beverage-id').value = beverageId;
    // Pre-completăm notița
    document.getElementById('pref-notes').value = pref?.notes ?? '';
    // Resetăm alerta
    const alert = document.getElementById('pref-alert');
    alert.textContent = '';
    alert.classList.remove('visible');

    // Setăm rating-ul curent (sau 0 dacă nu există)
    currentRating = pref ? parseInt(pref.rating) : 0;
    renderStarPicker(currentRating);

    // Butonul de ștergere apare doar dacă există deja un rating
    document.getElementById('btn-delete-pref').style.display = pref ? 'inline-flex' : 'none';

    // Deschidem modalul
    document.getElementById('modal-pref').classList.add('open');
}

/**
 * renderStarPicker(selected)
 * Generează selectorul interactiv de stele în modal.
 */
function renderStarPicker(selected) {
    const picker = document.getElementById('star-picker');
    // Generăm 5 butoane stea
    picker.innerHTML = Array.from({ length: 5 }, (_, i) => {
        const val = i + 1;
        return `<button type="button" class="star-btn ${val <= selected ? 'active' : ''}"
                        data-value="${val}" aria-label="${val} stele" title="${val} stele">
                    ${val <= selected ? '★' : '☆'}
                </button>`;
    }).join('');

    // Hover: evidențiem stelele până la cea hover-uită
    picker.querySelectorAll('.star-btn').forEach(btn => {
        // Hover: colorăm stelele 1..hover
        btn.addEventListener('mouseenter', () => highlightStars(parseInt(btn.dataset.value)));
        // Mouse out: revenim la selecția curentă
        btn.addEventListener('mouseleave', () => highlightStars(currentRating));
        // Click: setăm rating-ul
        btn.addEventListener('click', () => {
            currentRating = parseInt(btn.dataset.value);
            document.getElementById('pref-rating').value = currentRating;
            renderStarPicker(currentRating);
        });
    });
}

/**
 * highlightStars(upTo)
 * Colorează stelele 1..upTo în galben, restul în gri.
 */
function highlightStars(upTo) {
    document.querySelectorAll('.star-btn').forEach(btn => {
        const val    = parseInt(btn.dataset.value);
        const active = val <= upTo;
        btn.classList.toggle('active', active);
        btn.textContent = active ? '★' : '☆';
    });
}

/* ================================================================
   Formular modal
   ================================================================ */

function initForm() {
    const form = document.getElementById('pref-form');

    // Submit: salvează preferința
    form.addEventListener('submit', async e => {
        e.preventDefault();

        const beverageId = parseInt(document.getElementById('pref-beverage-id').value);
        const notes      = document.getElementById('pref-notes').value.trim() || null;
        const alert      = document.getElementById('pref-alert');
        const btn        = form.querySelector('button[type=submit]');

        alert.classList.remove('visible');

        // Validare: trebuie selectat un rating
        if (currentRating < 1) {
            alert.textContent = 'Selectează un rating între 1 și 5 stele.';
            alert.classList.add('visible');
            return;
        }

        btn.disabled = true;
        btn.classList.add('loading');

        try {
            // Upsert: creare sau actualizare preferință
            await api('POST', '/beverages-app/api/preferences.php', {
                beverage_id: beverageId,
                rating:      currentRating,
                notes,
            });
            closeModal();
            // Reîncărcăm preferințele și re-randăm grid-ul
            await loadPreferences();
            renderGrid();
        } catch (err) {
            alert.textContent = err.message;
            alert.classList.add('visible');
        } finally {
            btn.disabled = false;
            btn.classList.remove('loading');
        }
    });

    // Buton ștergere preferință
    document.getElementById('btn-delete-pref').addEventListener('click', async () => {
        const beverageId = parseInt(document.getElementById('pref-beverage-id').value);
        if (!confirm('Ștergi evaluarea pentru această băutură?')) return;

        try {
            await api('DELETE', `/beverages-app/api/preferences.php?beverage_id=${beverageId}`);
            closeModal();
            await loadPreferences();
            renderGrid();
        } catch (err) {
            const alert = document.getElementById('pref-alert');
            alert.textContent = err.message;
            alert.classList.add('visible');
        }
    });
}

/* ================================================================
   Închidere modal
   ================================================================ */

function closeModal() {
    document.getElementById('modal-pref').classList.remove('open');
}

function initModal() {
    // Click pe overlay → închide
    document.getElementById('modal-pref').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal();
    });
    // Escape → închide
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });
}

/* ================================================================
   Utilitare
   ================================================================ */

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
