const BASE      = '/beverages-app/api/groups.php';
const PREF_BASE = '/beverages-app/api/group_preferences.php';

let activeGroupId = null;
let activeGroup   = null;
let allBeverages  = [];

async function api(method, url, body = null) {
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body !== null) opts.body = JSON.stringify(body);
    const res  = await fetch(url, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Eroare server');
    return data;
}

function showFeedback(message, type = 'success') {
    const el = document.getElementById('group-feedback');
    el.textContent = message;
    el.className   = `list-feedback list-feedback--${type} visible`;
    setTimeout(() => el.classList.remove('visible'), 3000);
}

async function loadGroups() {
    const sidebar = document.getElementById('groups-sidebar');
    try {
        const data = await api('GET', BASE);
        renderSidebar(data.groups);
    } catch (err) {
        sidebar.innerHTML = `<p class="loading-text" style="color:var(--clr-danger)">${err.message}</p>`;
    }
}

function renderSidebar(groups) {
    const sidebar = document.getElementById('groups-sidebar');
    if (!groups.length) {
        sidebar.innerHTML = '<p class="loading-text">Nu faci parte din niciun grup.</p>';
        return;
    }
    sidebar.innerHTML = groups.map(g => `
        <div class="list-card${g.id === activeGroupId ? ' active' : ''}"
             data-id="${g.id}" role="button" tabindex="0">
            <strong class="list-card__name">${escHtml(g.name)}</strong>
            <span class="list-card__meta">Owner: ${escHtml(g.owner_name)}</span>
        </div>
    `).join('');
    sidebar.querySelectorAll('.list-card').forEach(card => {
        card.addEventListener('click', () => selectGroup(Number(card.dataset.id)));
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') selectGroup(Number(card.dataset.id));
        });
    });
}

async function selectGroup(id) {
    activeGroupId = id;
    document.querySelectorAll('#groups-sidebar .list-card').forEach(c => {
        c.classList.toggle('active', Number(c.dataset.id) === id);
    });
    document.getElementById('no-group-selected').style.display = 'none';
    document.getElementById('group-detail').style.display      = 'block';
    try {
        const data  = await api('GET', `${BASE}?id=${id}`);
        activeGroup = data.group;
        renderGroupDetail(data.group);
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

function renderGroupDetail(group) {
    document.getElementById('group-detail-name').textContent = group.name;
    document.getElementById('group-detail-meta').textContent =
        `Owner: ${group.owner_name} · ${group.members.length} ${group.members.length === 1 ? 'membru' : 'membri'}`;

    const btnDelete = document.getElementById('btn-delete-group');
    btnDelete.style.display = group.created_by === getCurrentUserId() ? '' : 'none';

    renderMembers(group);

    document.getElementById('member-search-input').value = '';
    document.getElementById('user-search-results').innerHTML = '';
    document.getElementById('pref-beverage-select').value = '';
    document.getElementById('pref-rating').value          = '0';
    document.getElementById('pref-notes').value           = '';
    initStarPicker();
    loadGroupPreferences(group.id);
}

function renderMembers(group) {
    const el      = document.getElementById('members-list');
    const isOwner = group.created_by === getCurrentUserId();
    if (!group.members.length) { el.innerHTML = '<p class="loading-text">Niciun membru.</p>'; return; }
    el.innerHTML = group.members.map(m => `
        <div class="list-card" style="display:flex;align-items:center;gap:.75rem;cursor:default">
            <span class="avatar" style="width:2rem;height:2rem;font-size:.9rem">${escHtml(m.username[0].toUpperCase())}</span>
            <span style="flex:1">${escHtml(m.username)}</span>
            ${isOwner && m.id !== group.created_by
                ? `<button class="btn btn-danger btn-sm" data-uid="${m.id}" onclick="removeMember(${m.id})">Elimină</button>`
                : ''}
            ${m.id === group.created_by ? '<span style="font-size:.75rem;color:var(--clr-muted)">owner</span>' : ''}
        </div>
    `).join('');
}

document.getElementById('create-group-form').addEventListener('submit', async e => {
    e.preventDefault();
    const input = document.getElementById('group-name');
    const errEl = document.getElementById('create-group-error');
    const btn   = e.target.querySelector('button[type=submit]');
    const name  = input.value.trim();

    errEl.textContent = '';
    errEl.classList.remove('visible');
    if (!name) { errEl.textContent = 'Numele grupului este obligatoriu.'; errEl.classList.add('visible'); return; }

    btn.disabled = true;
    btn.classList.add('loading');
    try {
        const data = await api('POST', BASE, { name });
        input.value = '';
        await loadGroups();
        selectGroup(data.id);
    } catch (err) {
        errEl.textContent = err.message;
        errEl.classList.add('visible');
    } finally {
        btn.disabled = false;
        btn.classList.remove('loading');
    }
});

document.getElementById('btn-delete-group').addEventListener('click', async () => {
    if (!activeGroupId) return;
    if (!confirm('Ești sigur că vrei să ștergi acest grup? Acțiunea este ireversibilă.')) return;
    try {
        await api('DELETE', `${BASE}?id=${activeGroupId}`);
        activeGroupId = null;
        activeGroup   = null;
        document.getElementById('group-detail').style.display      = 'none';
        document.getElementById('no-group-selected').style.display = '';
        await loadGroups();
    } catch (err) {
        showFeedback(err.message, 'error');
    }
});

document.getElementById('btn-search-user').addEventListener('click', searchUsers);
document.getElementById('member-search-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); searchUsers(); }
});

async function searchUsers() {
    const q       = document.getElementById('member-search-input').value.trim();
    const results = document.getElementById('user-search-results');
    if (q.length < 2) {
        results.innerHTML = '<p style="font-size:.85rem;color:var(--clr-muted)">Scrie cel puțin 2 caractere.</p>';
        return;
    }
    try {
        const data = await api('GET', `${BASE}?action=search_users&q=${encodeURIComponent(q)}`);
        renderSearchResults(data.users);
    } catch (err) {
        results.innerHTML = `<p style="color:var(--clr-danger);font-size:.85rem">${err.message}</p>`;
    }
}

function renderSearchResults(users) {
    const results  = document.getElementById('user-search-results');
    const memberIds = new Set((activeGroup?.members || []).map(m => m.id));
    if (!users.length) { results.innerHTML = '<p style="font-size:.85rem;color:var(--clr-muted)">Niciun utilizator găsit.</p>'; return; }
    results.innerHTML = users.map(u => `
        <div style="display:flex;align-items:center;gap:.5rem;padding:.4rem 0;border-bottom:1px solid var(--clr-border)">
            <span style="flex:1;font-size:.9rem">${escHtml(u.username)}</span>
            ${memberIds.has(u.id)
                ? '<span style="font-size:.8rem;color:var(--clr-muted)">deja membru</span>'
                : `<button class="btn btn-primary btn-sm" onclick="addMember(${u.id}, '${escHtml(u.username)}')">+ Adaugă</button>`}
        </div>
    `).join('');
}

async function addMember(userId, username) {
    try {
        await api('POST', `${BASE}?id=${activeGroupId}&action=add_member`, { user_id: userId });
        showFeedback(`${username} a fost adăugat în grup.`);
        const data  = await api('GET', `${BASE}?id=${activeGroupId}`);
        activeGroup = data.group;
        renderGroupDetail(data.group);
        document.querySelector(`[data-id="${activeGroupId}"] .list-card__meta`).textContent =
            `Owner: ${data.group.owner_name}`;
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

async function removeMember(userId) {
    if (!confirm('Elimini acest membru din grup?')) return;
    try {
        await api('DELETE', `${BASE}?id=${activeGroupId}&action=remove_member`, { user_id: userId });
        const data  = await api('GET', `${BASE}?id=${activeGroupId}`);
        activeGroup = data.group;
        renderGroupDetail(data.group);
        showFeedback('Membrul a fost eliminat.');
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

// Reads the current user ID from a hidden input injected by PHP
function getCurrentUserId() {
    return Number(document.getElementById('current-user-id')?.value ?? 0);
}

async function loadGroupPreferences(groupId) {
    const container = document.getElementById('group-prefs-list');
    container.innerHTML = '<p class="loading-text">Se încarcă...</p>';
    try {
        const data = await api('GET', `${PREF_BASE}?group_id=${groupId}`);
        renderGroupPreferences(data.preferences);
    } catch (err) {
        container.innerHTML = `<p style="color:var(--clr-danger);font-size:.85rem">${err.message}</p>`;
    }
}

function renderGroupPreferences(prefs) {
    const container = document.getElementById('group-prefs-list');
    if (!prefs.length) { container.innerHTML = '<p class="loading-text">Nicio preferință de grup. Adaugă una mai sus.</p>'; return; }
    container.innerHTML = prefs.map(p => `
        <div class="list-card" style="display:flex;align-items:center;gap:.75rem;cursor:default">
            <div style="flex:1">
                <strong>${escHtml(p.beverage_name)}</strong>
                <span style="margin-left:.5rem;color:var(--clr-warning)">${'★'.repeat(p.rating)}${'☆'.repeat(5 - p.rating)}</span>
                ${p.notes ? `<div style="font-size:.8rem;color:var(--clr-muted);margin-top:.2rem">${escHtml(p.notes)}</div>` : ''}
            </div>
            <button class="btn btn-danger btn-sm" onclick="deleteGroupPreference(${p.beverage_id})">✕</button>
        </div>
    `).join('');
}

async function deleteGroupPreference(beverageId) {
    if (!confirm('Ștergi această preferință de grup?')) return;
    try {
        await api('DELETE', `${PREF_BASE}?group_id=${activeGroupId}&beverage_id=${beverageId}`);
        showFeedback('Preferința a fost ștearsă.');
        await loadGroupPreferences(activeGroupId);
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

function initStarPicker() {
    const container = document.getElementById('pref-star-picker');
    const hidden    = document.getElementById('pref-rating');
    container.innerHTML = '';
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('button');
        star.type        = 'button';
        star.textContent = '☆';
        star.dataset.val = i;
        star.addEventListener('click', () => {
            hidden.value = i;
            container.querySelectorAll('button').forEach((s, idx) => { s.textContent = idx < i ? '★' : '☆'; });
        });
        container.appendChild(star);
    }
}

document.getElementById('btn-save-group-pref').addEventListener('click', async () => {
    const beverageId = parseInt(document.getElementById('pref-beverage-select').value);
    const rating     = parseInt(document.getElementById('pref-rating').value);
    const notes      = document.getElementById('pref-notes').value.trim() || null;
    if (!beverageId) { showFeedback('Alege o băutură.', 'error'); return; }
    if (!rating)     { showFeedback('Alege un rating (1-5 stele).', 'error'); return; }
    try {
        await api('POST', PREF_BASE, { group_id: activeGroupId, beverage_id: beverageId, rating, notes });
        document.getElementById('pref-beverage-select').value = '';
        document.getElementById('pref-rating').value          = '0';
        document.getElementById('pref-notes').value           = '';
        initStarPicker();
        showFeedback('Preferința de grup a fost salvată.');
        await loadGroupPreferences(activeGroupId);
    } catch (err) {
        showFeedback(err.message, 'error');
    }
});

async function loadBeverages() {
    try {
        const data   = await api('GET', '/beverages-app/api/beverages.php');
        allBeverages = data.beverages ?? [];
        const select = document.getElementById('pref-beverage-select');
        allBeverages.forEach(b => {
            const price = b.price ? ` — ${parseFloat(b.price).toFixed(2)} lei` : '';
            select.add(new Option(`${b.name}${price}`, b.id));
        });
    } catch { /* non-critical */ }
}

document.addEventListener('DOMContentLoaded', () => {
    loadGroups();
    loadBeverages();
    initStarPicker();
});

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
