let allBeverages  = [];
let myPreferences = {}; // beverage_id → {rating, notes}
let activeFilter  = 'rated';
let currentRating = 0;

async function api(method, url, body = null) {
    const opts  = { method, headers: { 'Content-Type': 'application/json' } };
    const token = localStorage.getItem('jwt_token');
    if (token) opts.headers['Authorization'] = 'Bearer ' + token;
    if (body !== null) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Eroare server.');
    return data;
}

document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([loadBeverages(), loadPreferences()]);
    renderGrid();
    initFilters();
    initForm();
    initModal();
});

async function loadBeverages() {
    const data = await api('GET', '/beverages-app/api/beverages.php');
    allBeverages = data.beverages ?? [];
}

async function loadPreferences() {
    const data = await api('GET', '/beverages-app/api/preferences.php');
    myPreferences = {};
    (data.preferences ?? []).forEach(p => { myPreferences[p.beverage_id] = p; });
}

function renderGrid() {
    const grid = document.getElementById('pref-grid');
    const visible = allBeverages.filter(b => {
        if (activeFilter === 'rated')   return !!myPreferences[b.id];
        if (activeFilter === 'unrated') return !myPreferences[b.id];
        return true;
    });
    if (!visible.length) {
        grid.innerHTML = `<div class="empty-state"><span>⭐</span><p>Nicio băutură de afișat pentru filtrul selectat.</p></div>`;
        return;
    }
    grid.innerHTML = visible.map(b => prefCardHTML(b)).join('');
    grid.querySelectorAll('.pref-card').forEach(card => {
        card.addEventListener('click', () => openModal(parseInt(card.dataset.id)));
    });
}

function prefCardHTML(b) {
    const pref  = myPreferences[b.id];
    const rated = !!pref;
    const stars = rated ? renderStars(pref.rating) : '<span style="color:var(--clr-muted);font-size:.85rem">Neevaluat</span>';
    const price = b.price ? parseFloat(b.price).toFixed(2) + ' lei' : '—';
    return `
    <div class="pref-card ${rated ? 'pref-card--rated' : ''}"
         data-id="${b.id}" role="button" tabindex="0"
         aria-label="Evaluează ${escHtml(b.name)}">
        <div class="card-header-row">
            <span class="badge badge-category">${escHtml(b.category_name ?? 'Fără categorie')}</span>
        </div>
        <h3 class="card-title">${escHtml(b.name)}</h3>
        <div class="pref-stars">${stars}</div>
        ${pref?.notes ? `<p class="pref-notes-preview">${escHtml(pref.notes)}</p>` : ''}
        <div class="card-footer-row">
            <span class="card-price">${price}</span>
            <span class="pref-action-hint">${rated ? '✏️ Editează' : '+ Evaluează'}</span>
        </div>
    </div>`;
}

function renderStars(rating) {
    return Array.from({ length: 5 }, (_, i) =>
        `<span style="color:${i < rating ? '#f59e0b' : '#cbd5e1'};font-size:1.1rem">${i < rating ? '★' : '☆'}</span>`
    ).join('');
}

function initFilters() {
    document.querySelectorAll('.pref-filter').forEach(btn => {
        btn.addEventListener('click', () => {
            activeFilter = btn.dataset.filter;
            document.querySelectorAll('.pref-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderGrid();
        });
    });
}

function openModal(beverageId) {
    const bev  = allBeverages.find(b => b.id == beverageId);
    const pref = myPreferences[beverageId];
    document.getElementById('pref-modal-title').textContent = bev?.name ?? 'Evaluează băutura';
    document.getElementById('pref-beverage-id').value       = beverageId;
    document.getElementById('pref-notes').value             = pref?.notes ?? '';
    const alert = document.getElementById('pref-alert');
    alert.textContent = '';
    alert.classList.remove('visible');
    currentRating = pref ? parseInt(pref.rating) : 0;
    renderStarPicker(currentRating);
    document.getElementById('btn-delete-pref').style.display = pref ? 'inline-flex' : 'none';
    document.getElementById('modal-pref').classList.add('open');
}

function renderStarPicker(selected) {
    const picker = document.getElementById('star-picker');
    picker.innerHTML = Array.from({ length: 5 }, (_, i) => {
        const val = i + 1;
        return `<button type="button" class="star-btn ${val <= selected ? 'active' : ''}"
                        data-value="${val}" aria-label="${val} stele" title="${val} stele">
                    ${val <= selected ? '★' : '☆'}
                </button>`;
    }).join('');

    picker.querySelectorAll('.star-btn').forEach(btn => {
        btn.addEventListener('mouseenter', () => highlightStars(parseInt(btn.dataset.value)));
        btn.addEventListener('mouseleave', () => highlightStars(currentRating));
        btn.addEventListener('click', () => {
            currentRating = parseInt(btn.dataset.value);
            document.getElementById('pref-rating').value = currentRating;
            renderStarPicker(currentRating);
        });
    });
}

function highlightStars(upTo) {
    document.querySelectorAll('.star-btn').forEach(btn => {
        const active = parseInt(btn.dataset.value) <= upTo;
        btn.classList.toggle('active', active);
        btn.textContent = active ? '★' : '☆';
    });
}

function initForm() {
    const form = document.getElementById('pref-form');
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const beverageId = parseInt(document.getElementById('pref-beverage-id').value);
        const notes      = document.getElementById('pref-notes').value.trim() || null;
        const alert      = document.getElementById('pref-alert');
        const btn        = form.querySelector('button[type=submit]');
        alert.classList.remove('visible');

        if (currentRating < 1) {
            alert.textContent = 'Selectează un rating între 1 și 5 stele.';
            alert.classList.add('visible');
            return;
        }

        btn.disabled = true;
        btn.classList.add('loading');
        try {
            await api('POST', '/beverages-app/api/preferences.php', {
                beverage_id: beverageId, rating: currentRating, notes,
            });
            closeModal();
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

function closeModal() {
    document.getElementById('modal-pref').classList.remove('open');
}

function initModal() {
    document.getElementById('modal-pref').addEventListener('click', e => {
        if (e.target === e.currentTarget) closeModal();
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeModal();
    });
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
