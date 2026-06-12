let allBeverages   = [];
let categories     = [];
let dietaryRegimes = [];
let userLists      = [];
let myPreferences  = {};
let searchTimer    = null;
let currentFiltered     = [];
let currentPage         = 1;
let detailCurrentRating = 0;
const ITEMS_PER_PAGE = 9;

async function api(method, url, body = null) {
    const opts  = { method, headers: { 'Content-Type': 'application/json' } };
    const token = localStorage.getItem('jwt_token');
    if (token) opts.headers['Authorization'] = 'Bearer ' + token;
    if (body !== null) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    const data = await res.json();
    return { ok: res.ok, status: res.status, data };
}

document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([loadCategories(), loadAllergens(), loadDietaryRegimes(), loadRegions(), loadShoppingLists(), loadMyPreferences()]);
    await loadBeverages();
    initFilters();
    initAddButton();
    initFormSubmit();
    initModalClose();
    initImagePreview();
});

async function loadCategories() {
    const { ok, data } = await api('GET', '/beverages-app/api/categories.php');
    if (!ok) return;
    categories = data.categories ?? [];
    const filterSelect = document.getElementById('filter-category');
    const formSelect   = document.getElementById('form-category');
    categories.forEach(cat => {
        filterSelect.add(new Option(cat.name, cat.id));
        if (formSelect) formSelect.add(new Option(cat.name, cat.id));
    });
}

async function loadAllergens() {
    const { ok, data } = await api('GET', '/beverages-app/api/allergens.php');
    if (!ok) return;
    const select = document.getElementById('filter-allergen');
    if (!select) return;
    (data.allergens ?? []).forEach(a => select.add(new Option(`Fără ${a.name}`, a.id)));
}

async function loadDietaryRegimes() {
    const { ok, data } = await api('GET', '/beverages-app/api/dietary_regimes.php');
    if (!ok) return;
    dietaryRegimes = data.dietary_regimes ?? [];
    const filterSelect = document.getElementById('filter-regime');
    if (filterSelect) dietaryRegimes.forEach(r => filterSelect.add(new Option(r.name, r.id)));
    buildRegimeCheckboxes([]);
}

function buildRegimeCheckboxes(selectedIds) {
    const wrap = document.getElementById('form-regimes');
    if (!wrap) return;
    wrap.innerHTML = dietaryRegimes.map(r => `
        <label>
            <input type="checkbox" name="regime" value="${r.id}"
                   ${selectedIds.includes(r.id) ? 'checked' : ''}>
            ${escHtml(r.name)}
        </label>
    `).join('');
}

async function loadRegions() {
    const { ok, data } = await api('GET', '/beverages-app/api/beverages.php?action=regions');
    if (!ok) return;
    const select = document.getElementById('filter-region');
    if (!select) return;
    (data.regions ?? []).forEach(r => select.add(new Option(r, r)));
}

async function loadShoppingLists() {
    const { ok, data } = await api('GET', '/beverages-app/api/shopping_lists.php');
    if (!ok) return;
    userLists = data.lists ?? [];
}

async function loadMyPreferences() {
    const { ok, data } = await api('GET', '/beverages-app/api/preferences.php');
    if (!ok) return;
    myPreferences = {};
    (data.preferences ?? []).forEach(p => { myPreferences[p.beverage_id] = p; });
}

async function loadBeverages() {
    const categoryId = document.getElementById('filter-category').value;
    const season     = document.getElementById('filter-season').value;
    const allergenId = document.getElementById('filter-allergen')?.value ?? '';
    const regimeId   = document.getElementById('filter-regime')?.value   ?? '';
    const region     = document.getElementById('filter-region')?.value   ?? '';
    const search     = document.getElementById('search-input').value.trim().toLowerCase();

    const params = new URLSearchParams();
    if (categoryId) params.set('category_id', categoryId);
    if (season)     params.set('season', season);
    if (allergenId) params.set('exclude_allergen_id', allergenId);
    if (regimeId)   params.set('dietary_regime_id', regimeId);
    if (region)     params.set('region', region);

    const { ok, data } = await api('GET', '/beverages-app/api/beverages.php?' + params.toString());
    if (!ok) { showGridError(); return; }

    allBeverages = data.beverages ?? [];
    currentPage  = 1;
    applySearchAndRender();
}

function applySearchAndRender() {
    const search = document.getElementById('search-input').value.trim().toLowerCase();
    currentFiltered = search
        ? allBeverages.filter(b =>
            b.name.toLowerCase().includes(search) ||
            (b.description ?? '').toLowerCase().includes(search))
        : allBeverages;
    renderBeverages();
}

function renderBeverages() {
    const grid = document.getElementById('beverages-grid');
    if (!currentFiltered.length) {
        grid.innerHTML = `<div class="empty-state"><span>🧃</span><p>Nicio băutură găsită pentru filtrele selectate.</p></div>`;
        renderPagination();
        return;
    }

    const totalPages = Math.ceil(currentFiltered.length / ITEMS_PER_PAGE);
    if (currentPage > totalPages) currentPage = totalPages;

    const start     = (currentPage - 1) * ITEMS_PER_PAGE;
    const pageItems = currentFiltered.slice(start, start + ITEMS_PER_PAGE);

    grid.innerHTML = pageItems.map(b => beverageCardHTML(b)).join('');
    grid.querySelectorAll('.beverage-card').forEach(card => {
        card.addEventListener('click', () => openDetail(parseInt(card.dataset.id)));
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openDetail(parseInt(card.dataset.id)); }
        });
    });

    renderPagination();
}

function renderPagination() {
    const container = document.getElementById('pagination');
    if (!container) return;

    const total      = currentFiltered.length;
    const totalPages = Math.ceil(total / ITEMS_PER_PAGE);

    if (totalPages <= 1) {
        container.innerHTML = total > 0
            ? `<p class="pagination-info">${total} băutur${total === 1 ? 'ă' : 'i'}</p>`
            : '';
        return;
    }

    const start = (currentPage - 1) * ITEMS_PER_PAGE + 1;
    const end   = Math.min(currentPage * ITEMS_PER_PAGE, total);

    let html = `<div class="pagination">`;
    html += `<button class="btn btn-outline btn-sm" onclick="goToPage(${currentPage - 1})"
             ${currentPage === 1 ? 'disabled' : ''} aria-label="Pagina anterioară">‹</button>`;

    for (const p of getPageNumbers(currentPage, totalPages)) {
        if (p === '...') {
            html += `<span class="pagination-ellipsis">…</span>`;
        } else {
            html += `<button class="btn btn-sm ${p === currentPage ? 'btn-primary' : 'btn-outline'}"
                     onclick="goToPage(${p})" aria-label="Pagina ${p}"
                     ${p === currentPage ? 'aria-current="page"' : ''}>${p}</button>`;
        }
    }

    html += `<button class="btn btn-outline btn-sm" onclick="goToPage(${currentPage + 1})"
             ${currentPage === totalPages ? 'disabled' : ''} aria-label="Pagina următoare">›</button>`;
    html += `</div>`;
    html += `<p class="pagination-info">${start}–${end} din ${total} băuturi</p>`;

    container.innerHTML = html;
}

function getPageNumbers(current, total) {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
    if (current >= total - 3) return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
    return [1, '...', current - 1, current, current + 1, '...', total];
}

function goToPage(page) {
    const totalPages = Math.ceil(currentFiltered.length / ITEMS_PER_PAGE);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderBeverages();
    document.getElementById('beverages-grid').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function beverageCardHTML(b) {
    const seasonLabel = { all: 'Tot anul', winter: 'Iarnă', spring: 'Primăvară', summer: 'Vară', autumn: 'Toamnă' };
    const price = b.price ? parseFloat(b.price).toFixed(2) + ' lei' : '—';
    return `
    <article class="beverage-card" data-id="${b.id}" tabindex="0" role="button"
             aria-label="Vezi detalii pentru ${escHtml(b.name)}">
        ${b.image_path ? `<div class="card-image"><img src="${escHtml(b.image_path)}" alt="${escHtml(b.name)}" loading="lazy"></div>` : ''}
        <div class="card-header-row">
            <span class="badge badge-category">${escHtml(b.category_name ?? 'Fără categorie')}</span>
            ${b.season ? `<span class="badge badge-season">${escHtml(seasonLabel[b.season] ?? b.season)}</span>` : ''}
            ${parseInt(b.allergen_count) > 0 ? '<span class="badge badge-allergen" title="Conține alergeni">⚠ Alergeni</span>' : ''}
            ${parseInt(b.regime_count) > 0 ? `<span class="badge badge-regime" title="${escHtml(b.regime_names ?? 'Regimuri speciale')}">🌿 ${b.regime_count} regim${parseInt(b.regime_count) !== 1 ? 'uri' : ''}</span>` : ''}
        </div>
        <h3 class="card-title">${escHtml(b.name)}</h3>
        ${b.description ? `<p class="card-desc">${escHtml(b.description)}</p>` : ''}
        <div class="card-footer-row">
            <span class="card-price">${price}</span>
            ${b.region ? `<span class="card-region">📍 ${escHtml(b.region)}</span>` : ''}
        </div>
    </article>`;
}

async function openDetail(id) {
    const modal = document.getElementById('modal-detail');
    const body  = document.getElementById('detail-body');
    modal.classList.add('open');
    body.innerHTML = '<p class="loading-text">Se încarcă detaliile...</p>';

    const [bevRes, listRes] = await Promise.all([
        api('GET', `/beverages-app/api/beverages.php?id=${id}`),
        api('GET', '/beverages-app/api/shopping_lists.php'),
    ]);
    if (!bevRes.ok) { body.innerHTML = '<p class="error-text">Eroare la încărcarea detaliilor.</p>'; return; }

    userLists = listRes.ok ? (listRes.data.lists ?? []) : userLists;
    const b = bevRes.data.beverage;
    const isAdmin = document.body.dataset.role === 'admin';
    const seasonLabel = { all: 'Tot anul', winter: 'Iarnă', spring: 'Primăvară', summer: 'Vară', autumn: 'Toamnă' };
    const currentPref = myPreferences[id] ?? null;

    body.innerHTML = `
        <h2 class="detail-title" id="detail-title-el">${escHtml(b.name)}</h2>
        <div class="detail-badges">
            <span class="badge badge-category">${escHtml(b.category_name ?? '—')}</span>
            ${b.season ? `<span class="badge badge-season">${escHtml(seasonLabel[b.season] ?? b.season)}</span>` : ''}
        </div>
        ${b.image_path ? `<div class="detail-image"><img src="${escHtml(b.image_path)}" alt="${escHtml(b.name)}"></div>` : ''}
        ${b.description ? `<p class="detail-desc">${escHtml(b.description)}</p>` : ''}
        <dl class="detail-dl">
            <dt>Preț</dt><dd>${b.price ? parseFloat(b.price).toFixed(2) + ' lei' : '—'}</dd>
            <dt>Regiune</dt><dd>${escHtml(b.region ?? '—')}</dd>
            <dt>Perisabilitate</dt><dd>${b.perishability_days ? b.perishability_days + ' zile' : '—'}</dd>
            <dt>Valabil până la</dt><dd>${b.valid_until ? new Date(b.valid_until).toLocaleDateString('ro-RO') : '—'}</dd>
        </dl>
        ${b.ingredients?.length ? `
        <div class="detail-section"><h4>Ingrediente</h4>
            <ul class="tag-list">
                ${b.ingredients.map(i => `<li class="tag">${escHtml(i.name)}${i.quantity ? ` <span>(${escHtml(i.quantity)})</span>` : ''}</li>`).join('')}
            </ul></div>` : ''}
        ${b.allergens?.length ? `
        <div class="detail-section"><h4>Alergeni</h4>
            <ul class="tag-list">
                ${b.allergens.map(a => `<li class="tag tag-danger">⚠ ${escHtml(a.name)}</li>`).join('')}
            </ul></div>` : ''}
        ${b.dietary_regimes?.length ? `
        <div class="detail-section"><h4>Regimuri alimentare</h4>
            <ul class="tag-list">
                ${b.dietary_regimes.map(r => `<li class="tag badge-regime">🌿 ${escHtml(r.name)}</li>`).join('')}
            </ul></div>` : ''}
        ${b.venues?.length ? `
        <div class="detail-section"><h4>Localuri disponibile</h4>
            <ul class="venue-list">
                ${b.venues.map(v => `<li>${escHtml(v.name)}${v.location ? ` <span class="venue-loc">— ${escHtml(v.location)}</span>` : ''}</li>`).join('')}
            </ul></div>` : ''}
        <div class="detail-section">
            <h4>Adaugă în listă de cumpărături</h4>
            ${userLists.length ? `
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
                <select id="detail-list-select" class="form-control" style="flex:1;min-width:150px">
                    <option value="">— Alege lista —</option>
                    ${userLists.map(l => `<option value="${l.id}">${escHtml(l.name)}</option>`).join('')}
                </select>
                <input type="number" id="detail-qty" value="1" min="1" max="99"
                       class="form-control" style="width:70px" aria-label="Cantitate">
                <button class="btn btn-primary btn-sm"
                        onclick="addToShoppingList(${b.id}, '${escHtml(b.name)}')">+ Adaugă</button>
            </div>
            <p id="detail-list-feedback" style="font-size:.82rem;margin-top:.35rem;min-height:1.2em"></p>
            ` : `<p style="font-size:.85rem;color:var(--clr-muted)">
                Nu ai liste de cumpărături. <a href="/beverages-app/pages/lists.php">Creează una</a>.
            </p>`}
        </div>
        <div class="detail-section" id="detail-rating-section">
            <h4>Evaluarea ta</h4>
            <div class="star-picker" id="detail-star-picker" role="radiogroup" aria-label="Rating 1-5 stele"></div>
            <input type="hidden" id="detail-pref-rating" value="0">
            <textarea id="detail-pref-notes" class="form-control" rows="2"
                      placeholder="Notițe personale (opțional)..." style="margin-top:.75rem">${escHtml(currentPref?.notes ?? '')}</textarea>
            <div id="detail-rating-actions" style="display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap"></div>
            <p id="detail-rating-feedback" style="font-size:.82rem;margin-top:.4rem;min-height:1.2em"></p>
        </div>
        ${isAdmin ? `
        <div class="detail-admin-actions">
            <button class="btn btn-outline btn-sm" onclick="openForm(${b.id})">✏️ Editează</button>
            <button class="btn btn-danger btn-sm" onclick="deleteBeverage(${b.id}, ${escHtml(JSON.stringify(b.name))})">🗑 Șterge</button>
        </div>` : ''}
    `;

    initDetailStarPicker(currentPref ? parseInt(currentPref.rating) : 0);
    updateDetailRatingButtons(b.id, !!currentPref);
}

async function openForm(id = null) {
    document.getElementById('modal-detail').classList.remove('open');
    const modal = document.getElementById('modal-form');
    const title = document.getElementById('form-title');
    const form  = document.getElementById('beverage-form');
    const alert = document.getElementById('form-alert');

    form.reset();
    document.getElementById('form-id').value = '';
    alert.classList.remove('visible');
    alert.textContent = '';

    const previewWrap  = document.getElementById('image-preview-wrap');
    const previewImg   = document.getElementById('image-preview');
    const currentLabel = document.getElementById('image-current');
    if (previewWrap) { previewWrap.style.display = 'none'; previewImg.src = ''; currentLabel.textContent = ''; }
    buildRegimeCheckboxes([]);

    if (id) {
        title.textContent = 'Editează băutură';
        modal.classList.add('open');
        const { ok, data } = await api('GET', `/beverages-app/api/beverages.php?id=${id}`);
        if (!ok) return;
        const b = data.beverage;
        document.getElementById('form-id').value            = b.id;
        document.getElementById('form-name').value          = b.name        ?? '';
        document.getElementById('form-category').value      = b.category_id ?? '';
        document.getElementById('form-price').value         = b.price       ?? '';
        document.getElementById('form-description').value   = b.description ?? '';
        document.getElementById('form-season').value        = b.season      ?? '';
        document.getElementById('form-region').value        = b.region      ?? '';
        document.getElementById('form-perishability').value = b.perishability_days ?? '';
        document.getElementById('form-valid-until').value   = b.valid_until         ?? '';
        if (previewWrap && b.image_path) {
            previewImg.src           = b.image_path;
            currentLabel.textContent = 'Imaginea curentă — selectează un fișier nou pentru a o înlocui';
            previewWrap.style.display = 'block';
        }
        buildRegimeCheckboxes((b.dietary_regimes ?? []).map(r => r.id));
    } else {
        title.textContent = 'Adaugă băutură';
        modal.classList.add('open');
    }
}

function initFormSubmit() {
    document.getElementById('beverage-form').addEventListener('submit', async e => {
        e.preventDefault();
        const alert = document.getElementById('form-alert');
        alert.classList.remove('visible');

        const id      = document.getElementById('form-id').value;
        const nameVal = document.getElementById('form-name').value.trim();
        if (!nameVal) {
            const nameInput = document.getElementById('form-name');
            nameInput.classList.add('error');
            nameInput.focus();
            return;
        }

        const btn = e.target.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.classList.add('loading');

        let imagePath = null;
        const imageFile = document.getElementById('form-image')?.files[0];
        if (imageFile) {
            try {
                imagePath = await uploadImage(imageFile);
            } catch (err) {
                alert.textContent = err.message;
                alert.classList.add('visible');
                btn.disabled = false;
                btn.classList.remove('loading');
                return;
            }
        }

        const selectedRegimes = [...document.querySelectorAll('#form-regimes input[type=checkbox]:checked')]
            .map(cb => ({ id: parseInt(cb.value) }));

        const body = {
            name:               nameVal,
            category_id:        document.getElementById('form-category').value            || null,
            price:              document.getElementById('form-price').value               || null,
            description:        document.getElementById('form-description').value.trim()  || null,
            season:             document.getElementById('form-season').value              || null,
            region:             document.getElementById('form-region').value.trim()       || null,
            perishability_days: document.getElementById('form-perishability').value       || null,
            valid_until:        document.getElementById('form-valid-until').value          || null,
            dietary_regimes:    selectedRegimes,
        };
        if (imagePath !== null) body.image_path = imagePath;

        const url    = id ? `/beverages-app/api/beverages.php?id=${id}` : '/beverages-app/api/beverages.php';
        const method = id ? 'PATCH' : 'POST';
        const { ok, data } = await api(method, url, body);

        btn.disabled = false;
        btn.classList.remove('loading');

        if (ok) {
            closeModal('modal-form');
            await loadBeverages();
        } else {
            alert.textContent = data.error ?? 'A apărut o eroare. Încearcă din nou.';
            alert.classList.add('visible');
        }
    });
}

async function deleteBeverage(id, name) {
    if (!confirm(`Ești sigur că vrei să ștergi "${name}"?\nAcțiunea este ireversibilă.`)) return;
    const { ok, data } = await api('DELETE', `/beverages-app/api/beverages.php?id=${id}`);
    if (ok) {
        closeModal('modal-detail');
        await loadBeverages();
    } else {
        alert(data.error ?? 'Eroare la ștergerea băuturii.');
    }
}

function initFilters() {
    document.getElementById('filter-category').addEventListener('change', loadBeverages);
    document.getElementById('filter-season').addEventListener('change', loadBeverages);
    document.getElementById('filter-allergen')?.addEventListener('change', loadBeverages);
    document.getElementById('filter-regime')?.addEventListener('change', loadBeverages);
    document.getElementById('filter-region')?.addEventListener('change', loadBeverages);
    document.getElementById('search-input').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { currentPage = 1; applySearchAndRender(); }, 300);
    });
}

function initAddButton() {
    const btn = document.getElementById('btn-add');
    if (!btn) return;
    btn.addEventListener('click', () => openForm());
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

function initModalClose() {
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
        }
    });
}

function showGridError() {
    document.getElementById('beverages-grid').innerHTML = `
        <div class="empty-state">
            <span>⚠️</span>
            <p>Eroare la încărcarea băuturilor. Verifică conexiunea și încearcă din nou.</p>
        </div>`;
}

async function addToShoppingList(beverageId, beverageName) {
    const select   = document.getElementById('detail-list-select');
    const qtyInput = document.getElementById('detail-qty');
    const feedback = document.getElementById('detail-list-feedback');
    const listId   = parseInt(select?.value);
    const quantity = parseInt(qtyInput?.value) || 1;

    if (!listId) {
        feedback.textContent = 'Selectează o listă mai întâi.';
        feedback.style.color = 'var(--clr-danger)';
        return;
    }

    const { ok, data } = await api('POST',
        `/beverages-app/api/shopping_lists.php?id=${listId}&action=add_item`,
        { beverage_id: beverageId, quantity }
    );
    if (ok) {
        feedback.textContent = `"${beverageName}" adăugat în listă!`;
        feedback.style.color = 'var(--clr-success)';
    } else {
        feedback.textContent = data.error ?? 'Eroare la adăugare.';
        feedback.style.color = 'var(--clr-danger)';
    }
}

async function uploadImage(file) {
    const form = new FormData();
    form.append('image', file);
    const res  = await fetch('/beverages-app/api/upload.php', { method: 'POST', body: form });
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Eroare la upload imagine.');
    return data.image_path;
}

function initImagePreview() {
    const input = document.getElementById('form-image');
    if (!input) return;
    const previewWrap  = document.getElementById('image-preview-wrap');
    const previewImg   = document.getElementById('image-preview');
    const currentLabel = document.getElementById('image-current');
    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;
        previewImg.src           = URL.createObjectURL(file);
        currentLabel.textContent = file.name;
        previewWrap.style.display = 'block';
    });
}

function initDetailStarPicker(selected) {
    detailCurrentRating = selected;
    const picker = document.getElementById('detail-star-picker');
    if (!picker) return;
    picker.innerHTML = Array.from({ length: 5 }, (_, i) => {
        const val = i + 1;
        return `<button type="button" class="star-btn ${val <= selected ? 'active' : ''}"
                        data-value="${val}" aria-label="${val} stele">${val <= selected ? '★' : '☆'}</button>`;
    }).join('');
    picker.querySelectorAll('.star-btn').forEach(btn => {
        btn.addEventListener('mouseenter', () => highlightDetailStars(parseInt(btn.dataset.value)));
        btn.addEventListener('mouseleave', () => highlightDetailStars(detailCurrentRating));
        btn.addEventListener('click', () => {
            detailCurrentRating = parseInt(btn.dataset.value);
            document.getElementById('detail-pref-rating').value = detailCurrentRating;
            initDetailStarPicker(detailCurrentRating);
        });
    });
}

function highlightDetailStars(upTo) {
    document.querySelectorAll('#detail-star-picker .star-btn').forEach(btn => {
        const active = parseInt(btn.dataset.value) <= upTo;
        btn.classList.toggle('active', active);
        btn.textContent = active ? '★' : '☆';
    });
}

function updateDetailRatingButtons(beverageId, hasRating) {
    const el = document.getElementById('detail-rating-actions');
    if (!el) return;
    el.innerHTML = `
        <button class="btn btn-primary btn-sm" onclick="saveDetailRating(${beverageId})">
            ⭐ ${hasRating ? 'Actualizează' : 'Salvează'} evaluarea
        </button>
        ${hasRating ? `<button class="btn btn-danger btn-sm" onclick="deleteDetailRating(${beverageId})">🗑 Șterge</button>` : ''}
    `;
}

async function saveDetailRating(beverageId) {
    const rating   = parseInt(document.getElementById('detail-pref-rating').value);
    const notes    = document.getElementById('detail-pref-notes').value.trim() || null;
    const feedback = document.getElementById('detail-rating-feedback');

    if (!rating) {
        feedback.textContent = 'Selectează un rating între 1 și 5 stele.';
        feedback.style.color = 'var(--clr-danger)';
        return;
    }

    const { ok, data } = await api('POST', '/beverages-app/api/preferences.php', {
        beverage_id: beverageId, rating, notes,
    });

    if (ok) {
        myPreferences[beverageId] = { beverage_id: beverageId, rating, notes };
        updateDetailRatingButtons(beverageId, true);
        feedback.textContent = 'Evaluare salvată!';
        feedback.style.color = 'var(--clr-success)';
    } else {
        feedback.textContent = data.error ?? 'Eroare la salvare.';
        feedback.style.color = 'var(--clr-danger)';
    }
}

async function deleteDetailRating(beverageId) {
    if (!confirm('Ștergi evaluarea pentru această băutură?')) return;
    const feedback = document.getElementById('detail-rating-feedback');

    const { ok, data } = await api('DELETE', `/beverages-app/api/preferences.php?beverage_id=${beverageId}`);

    if (ok) {
        delete myPreferences[beverageId];
        detailCurrentRating = 0;
        initDetailStarPicker(0);
        document.getElementById('detail-pref-notes').value = '';
        updateDetailRatingButtons(beverageId, false);
        feedback.textContent = 'Evaluare ștearsă.';
        feedback.style.color = 'var(--clr-muted)';
    } else {
        feedback.textContent = data.error ?? 'Eroare la ștergere.';
        feedback.style.color = 'var(--clr-danger)';
    }
}

function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
