let activeListId = null;
let allBeverages = [];
let userGroups   = [];
let activeItems  = [];

async function api(method, url, body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body !== null) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Eroare server.');
    return data;
}

document.addEventListener('DOMContentLoaded', async () => {
    initCreateForm();
    initDetailActions();
    initSortControl();
    await Promise.all([loadLists(), loadBeverages(), loadGroups()]);
});

function initCreateForm() {
    const form = document.getElementById('create-form');
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const input = document.getElementById('list-name');
        const errEl = document.getElementById('create-error');
        const btn   = form.querySelector('button[type=submit]');

        errEl.textContent = '';
        errEl.classList.remove('visible');

        const name = input.value.trim();
        if (!name) {
            errEl.textContent = 'Completează numele listei.';
            errEl.classList.add('visible');
            return;
        }

        btn.disabled = true;
        btn.classList.add('loading');

        try {
            const groupId = parseInt(document.getElementById('list-group-id').value) || null;
            const body    = groupId ? { name, group_id: groupId } : { name };
            const data    = await api('POST', '/beverages-app/api/shopping_lists.php', body);
            input.value   = '';
            await loadLists();
            if (data.id) selectList(data.id);
        } catch (err) {
            errEl.textContent = err.message;
            errEl.classList.add('visible');
        } finally {
            btn.disabled = false;
            btn.classList.remove('loading');
        }
    });
}

async function loadLists() {
    const sidebar = document.getElementById('lists-sidebar');
    sidebar.innerHTML = '<p style="color:var(--clr-muted);font-size:.88rem">Se încarcă...</p>';
    try {
        const data  = await api('GET', '/beverages-app/api/shopping_lists.php');
        const lists = data.lists ?? [];
        if (!lists.length) {
            sidebar.innerHTML = `<div class="list-empty-state" style="padding:1.5rem 0"><p>Nu ai nicio listă. Creează una mai sus.</p></div>`;
            return;
        }
        sidebar.innerHTML = lists.map(l => listCardHTML(l)).join('');
        sidebar.querySelectorAll('.list-card').forEach(card => {
            card.addEventListener('click', () => selectList(parseInt(card.dataset.id)));
        });
        highlightActiveCard();
    } catch (err) {
        sidebar.innerHTML = `<p style="color:var(--clr-danger);font-size:.88rem">${err.message}</p>`;
    }
}

function listCardHTML(list) {
    const count      = parseInt(list.item_count ?? 0);
    const countLabel = count === 1 ? '1 item' : `${count} iteme`;
    const active     = list.id == activeListId ? 'active' : '';
    const groupBadge = list.group_name
        ? `<span style="font-size:.75rem;color:var(--clr-primary);margin-left:.25rem">👥 ${escHtml(list.group_name)}</span>`
        : '';
    return `
    <div class="list-card ${active}" data-id="${list.id}" role="button" tabindex="0"
         aria-label="Deschide lista ${escHtml(list.name)}">
        <div class="list-card__name">${escHtml(list.name)}${groupBadge}</div>
        <div class="list-card__meta">${countLabel}</div>
    </div>`;
}

function highlightActiveCard() {
    document.querySelectorAll('.list-card').forEach(card => {
        card.classList.toggle('active', parseInt(card.dataset.id) === activeListId);
    });
}

async function selectList(id) {
    activeListId = id;
    highlightActiveCard();
    document.getElementById('no-list-selected').style.display = 'none';
    document.getElementById('list-detail').style.display      = 'block';
    await Promise.all([loadListDetail(id), loadSuggestions()]);
}

async function loadSuggestions() {
    const bar  = document.getElementById('suggestions-bar');
    const list = document.getElementById('suggestions-list');
    try {
        const res  = await fetch('/beverages-app/api/preferences.php?action=recommendations&limit=6');
        const data = await res.json();
        const recs = data.recommendations ?? [];
        if (!recs.length) { bar.style.display = 'none'; return; }
        bar.style.display = '';
        list.innerHTML = recs.map(b => `
            <button class="btn btn-outline btn-sm" style="font-size:.8rem"
                    onclick="addSuggestion(${b.id}, '${escHtml(b.name)}')">
                + ${escHtml(b.name)}
            </button>
        `).join('');
    } catch {
        bar.style.display = 'none';
    }
}

async function addSuggestion(beverageId, name) {
    try {
        await api('POST', `/beverages-app/api/shopping_lists.php?id=${activeListId}&action=add_item`, {
            beverage_id: beverageId,
            quantity: 1,
        });
        await Promise.all([loadListDetail(activeListId), loadLists()]);
        showFeedback(`"${name}" a fost adăugat în listă.`);
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

async function loadListDetail(id) {
    const tbody   = document.getElementById('items-tbody');
    const titleEl = document.getElementById('detail-title');
    const metaEl  = document.getElementById('detail-meta');
    tbody.innerHTML = `<tr><td colspan="5" class="list-empty-state">Se încarcă...</td></tr>`;
    try {
        const data = await api('GET', `/beverages-app/api/shopping_lists.php?id=${id}`);
        const list = data.list;
        titleEl.textContent = list.name;
        metaEl.textContent  = `${list.items.length} item${list.items.length === 1 ? '' : 'e'}`;
        activeItems = list.items;
        renderItems();
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="color:var(--clr-danger);padding:1rem 1.25rem">${err.message}</td></tr>`;
    }
}

function renderItems() {
    const tbody = document.getElementById('items-tbody');
    if (!tbody) return;
    const sorted = sortItems([...activeItems]);
    if (!sorted.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="list-empty-state">Lista e goală. Adaugă băuturi folosind bara de mai sus.</td></tr>`;
    } else {
        tbody.innerHTML = sorted.map(item => itemRowHTML(item)).join('');
        attachItemListeners();
    }
}

function sortItems(items) {
    const mode = document.getElementById('sort-items')?.value ?? 'default';
    switch (mode) {
        case 'name':       return items.sort((a, b) => a.beverage_name.localeCompare(b.beverage_name, 'ro'));
        case 'name-desc':  return items.sort((a, b) => b.beverage_name.localeCompare(a.beverage_name, 'ro'));
        case 'price':      return items.sort((a, b) => (parseFloat(a.price) || 0) - (parseFloat(b.price) || 0));
        case 'price-desc': return items.sort((a, b) => (parseFloat(b.price) || 0) - (parseFloat(a.price) || 0));
        case 'unchecked':  return items.sort((a, b) => parseInt(a.checked) - parseInt(b.checked));
        case 'checked':    return items.sort((a, b) => parseInt(b.checked) - parseInt(a.checked));
        default:           return items;
    }
}

function initSortControl() {
    document.getElementById('sort-items')?.addEventListener('change', renderItems);
}

function itemRowHTML(item) {
    const checkedClass = item.checked == 1 ? 'checked' : '';
    const checkIcon    = item.checked == 1 ? '↩ Debifează' : '✔ Bifează';
    // Afișăm prețul total = cantitate × preț unitar (nu prețul per bucată)
    const qty   = parseInt(item.quantity) || 1;
    const total = item.price ? (parseFloat(item.price) * qty).toFixed(2) + ' lei' : '—';
    return `
    <tr class="${checkedClass}" data-item-id="${item.id}">
        <td><span class="item-name">${escHtml(item.beverage_name)}</span></td>
        <td>${escHtml(String(qty))}</td>
        <td>${total}</td>
        <td style="text-align:center">
            ${item.checked == 1
                ? '<span style="color:var(--clr-success);font-weight:700">✔</span>'
                : '<span style="color:var(--clr-muted)">—</span>'}
        </td>
        <td>
            <div class="item-actions">
                <button class="btn btn-outline btn-sm btn-check"
                        data-item-id="${item.id}"
                        data-checked="${item.checked == 1 ? '1' : '0'}"
                        title="${checkIcon}">
                    ${item.checked == 1 ? '↩' : '✔'}
                </button>
                <button class="btn btn-danger btn-sm btn-remove"
                        data-item-id="${item.id}"
                        data-name="${escHtml(item.beverage_name)}"
                        title="Elimină din listă">
                    ✕
                </button>
            </div>
        </td>
    </tr>`;
}

function attachItemListeners() {
    document.querySelectorAll('.btn-check').forEach(btn => {
        btn.addEventListener('click', async () => {
            const itemId  = parseInt(btn.dataset.itemId);
            const checked = btn.dataset.checked === '1' ? 0 : 1;
            await toggleItem(itemId, checked);
        });
    });
    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', async () => {
            const itemId = parseInt(btn.dataset.itemId);
            const name   = btn.dataset.name;
            if (confirm(`Elimini "${name}" din listă?`)) await removeItem(itemId);
        });
    });
}

function initDetailActions() {
    document.getElementById('btn-delete-list').addEventListener('click', async () => {
        if (!activeListId) return;
        const title = document.getElementById('detail-title').textContent;
        if (!confirm(`Ștergi lista "${title}"? Acțiunea este ireversibilă.`)) return;
        try {
            await api('DELETE', `/beverages-app/api/shopping_lists.php?id=${activeListId}`);
            activeListId = null;
            document.getElementById('list-detail').style.display      = 'none';
            document.getElementById('no-list-selected').style.display = 'block';
            await loadLists();
        } catch (err) {
            showFeedback(err.message, 'error');
        }
    });
    document.getElementById('btn-add-item').addEventListener('click', addItemFromBar);
}

async function addItemFromBar() {
    const hiddenId = document.getElementById('add-beverage-id');
    const qtyInput = document.getElementById('add-quantity');
    const btn      = document.getElementById('btn-add-item');
    const beverageId = parseInt(hiddenId?.value);
    const quantity   = parseInt(qtyInput.value) || 1;

    if (!beverageId) { showFeedback('Caută și selectează o băutură din lista de sugestii.', 'error'); return; }

    btn.disabled = true;
    try {
        await api('POST', `/beverages-app/api/shopping_lists.php?id=${activeListId}&action=add_item`, {
            beverage_id: beverageId,
            quantity,
        });
        qtyInput.value = 1;
        const searchInput = document.getElementById('add-beverage-search');
        if (searchInput) searchInput.value = '';
        if (hiddenId)    hiddenId.value    = '';
        await Promise.all([loadListDetail(activeListId), loadLists()]);
        showFeedback('Băutura a fost adăugată.', 'success');
    } catch (err) {
        showFeedback(err.message, 'error');
    } finally {
        btn.disabled = false;
    }
}

async function toggleItem(itemId, checked) {
    try {
        await api('PATCH',
            `/beverages-app/api/shopping_lists.php?action=update_item&item_id=${itemId}`,
            { checked }
        );
        await loadListDetail(activeListId);
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

async function removeItem(itemId) {
    try {
        await api('DELETE',
            `/beverages-app/api/shopping_lists.php?action=remove_item&item_id=${itemId}`
        );
        await Promise.all([loadListDetail(activeListId), loadLists()]);
        showFeedback('Itemul a fost eliminat.', 'success');
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

async function loadGroups() {
    try {
        const data   = await api('GET', '/beverages-app/api/groups.php');
        userGroups   = data.groups ?? [];
        const select = document.getElementById('list-group-id');
        userGroups.forEach(g => select.add(new Option(g.name, g.id)));
    } catch { /* non-critical */ }
}

async function loadBeverages() {
    try {
        const data   = await api('GET', '/beverages-app/api/beverages.php');
        allBeverages = data.beverages ?? [];
        initBeverageSearch();
    } catch { /* non-critical */ }
}

function initBeverageSearch() {
    const searchInput = document.getElementById('add-beverage-search');
    const hiddenId    = document.getElementById('add-beverage-id');
    const resultsList = document.getElementById('beverage-results');
    if (!searchInput) return;

    let highlightIndex = -1;

    function highlight(index) {
        resultsList.querySelectorAll('li').forEach((li, i) => li.classList.toggle('highlighted', i === index));
        highlightIndex = index;
    }

    function selectItem(li) {
        hiddenId.value    = li.dataset.id;
        searchInput.value = li.dataset.name;
        resultsList.innerHTML = '';
        highlightIndex = -1;
    }

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim().toLowerCase();
        hiddenId.value = '';
        highlightIndex = -1;
        if (!query) { resultsList.innerHTML = ''; return; }

        const filtered = allBeverages.filter(b => b.name.toLowerCase().includes(query)).slice(0, 8);
        if (!filtered.length) { resultsList.innerHTML = ''; return; }

        resultsList.innerHTML = filtered.map(b => {
            const price = b.price ? ` <span>${parseFloat(b.price).toFixed(2)} lei</span>` : '';
            return `<li role="option" data-id="${b.id}" data-name="${escHtml(b.name)}">${escHtml(b.name)}${price}</li>`;
        }).join('');

        resultsList.querySelectorAll('li').forEach(li => li.addEventListener('click', () => selectItem(li)));
    });

    searchInput.addEventListener('keydown', e => {
        const items = resultsList.querySelectorAll('li');
        if (!items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); highlight(Math.min(highlightIndex + 1, items.length - 1)); }
        else if (e.key === 'ArrowUp')  { e.preventDefault(); highlight(Math.max(highlightIndex - 1, 0)); }
        else if (e.key === 'Enter')    { e.preventDefault(); if (highlightIndex >= 0 && items[highlightIndex]) selectItem(items[highlightIndex]); }
        else if (e.key === 'Escape')   { resultsList.innerHTML = ''; highlightIndex = -1; }
    });

    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target) && !resultsList.contains(e.target)) {
            resultsList.innerHTML = '';
            highlightIndex = -1;
        }
    });
}

function showFeedback(message, type = 'success') {
    const el = document.getElementById('list-feedback');
    el.textContent = message;
    el.className   = `list-feedback visible ${type}`;
    setTimeout(() => el.classList.remove('visible'), 3000);
}

function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
