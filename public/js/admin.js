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

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    initImport();
    initJsonImport();
});

async function loadUsers() {
    const wrap = document.getElementById('users-table-wrap');
    wrap.innerHTML = '<p style="color:var(--clr-muted);font-size:.88rem">Se încarcă...</p>';
    try {
        const data  = await api('GET', '/beverages-app/api/users.php');
        const users = data.users ?? [];
        if (!users.length) {
            wrap.innerHTML = '<p style="color:var(--clr-muted)">Niciun utilizator înregistrat.</p>';
            return;
        }
        wrap.innerHTML = `
        <table class="list-items-table">
            <thead><tr><th>#</th><th>Username</th><th>Email</th><th>Rol</th><th>Înregistrat</th><th></th></tr></thead>
            <tbody>${users.map(u => userRowHTML(u)).join('')}</tbody>
        </table>`;
        attachUserListeners();
    } catch (err) {
        wrap.innerHTML = `<p style="color:var(--clr-danger)">${err.message}</p>`;
    }
}

function userRowHTML(u) {
    const date = u.created_at ? u.created_at.split(' ')[0] : '—';
    const roleBadge = u.role === 'admin'
        ? `<span class="badge" style="background:#fef3c7;color:#92400e">admin</span>`
        : `<span class="badge" style="background:#eff6ff;color:#1d4ed8">user</span>`;
    return `
    <tr data-user-id="${u.id}">
        <td style="color:var(--clr-muted)">${u.id}</td>
        <td style="font-weight:600">${escHtml(u.username)}</td>
        <td style="color:var(--clr-muted)">${escHtml(u.email)}</td>
        <td>${roleBadge}</td>
        <td style="color:var(--clr-muted);font-size:.85rem">${date}</td>
        <td>
            <div class="item-actions">
                <button class="btn btn-outline btn-sm btn-role"
                        data-id="${u.id}" data-role="${u.role}"
                        title="${u.role === 'admin' ? 'Demotează la user' : 'Promovează la admin'}">
                    ${u.role === 'admin' ? '↓ User' : '↑ Admin'}
                </button>
                <button class="btn btn-danger btn-sm btn-del-user"
                        data-id="${u.id}" data-name="${escHtml(u.username)}"
                        title="Șterge utilizatorul">🗑</button>
            </div>
        </td>
    </tr>`;
}

function attachUserListeners() {
    document.querySelectorAll('.btn-role').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id      = parseInt(btn.dataset.id);
            const newRole = btn.dataset.role === 'admin' ? 'user' : 'admin';
            const label   = newRole === 'admin' ? 'Promovezi' : 'Demotezi';
            if (!confirm(`${label} acest utilizator la rolul "${newRole}"?`)) return;
            try {
                await api('PATCH', `/beverages-app/api/users.php?id=${id}&action=set_role`, { role: newRole });
                showFeedback(`Rolul a fost actualizat la "${newRole}".`, 'success');
                await loadUsers();
            } catch (err) {
                showFeedback(err.message, 'error');
            }
        });
    });
    document.querySelectorAll('.btn-del-user').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id   = parseInt(btn.dataset.id);
            const name = btn.dataset.name;
            if (!confirm(`Ștergi utilizatorul "${name}"? Acțiunea este ireversibilă.`)) return;
            try {
                await api('DELETE', `/beverages-app/api/users.php?id=${id}`);
                showFeedback(`Utilizatorul "${name}" a fost șters.`, 'success');
                await loadUsers();
            } catch (err) {
                showFeedback(err.message, 'error');
            }
        });
    });
}

function initImport() {
    const fileInput = document.getElementById('csv-file');
    const dropZone  = document.getElementById('import-drop');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragging'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragging'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragging');
        const file = e.dataTransfer.files[0];
        if (file) handleCSVFile(file);
    });
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) handleCSVFile(fileInput.files[0]);
    });
}

function handleCSVFile(file) {
    if (!file.name.endsWith('.csv')) { showImportFeedback('Selectează un fișier cu extensia .csv.', 'error'); return; }
    const reader = new FileReader();
    reader.onload = e => {
        const rows = parseCSV(e.target.result);
        if (rows.length < 2) { showImportFeedback('Fișierul CSV trebuie să conțină cel puțin un rând de date + antet.', 'error'); return; }
        const [header, ...data] = rows;
        showImportPreview(header, data, file.name);
    };
    reader.readAsText(file, 'UTF-8');
}

function parseCSV(text) {
    const clean = text.replace(/^﻿/, ''); // strip UTF-8 BOM
    const lines = clean.split(/\r?\n/).filter(l => l.trim());
    return lines.map(line => {
        const fields = [];
        let current  = '';
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
            const ch = line[i], next = line[i + 1];
            if (inQuotes) {
                if (ch === '"' && next === '"') { current += '"'; i++; }
                else if (ch === '"')             { inQuotes = false; }
                else                             { current += ch; }
            } else {
                if (ch === '"')       { inQuotes = true; }
                else if (ch === ',')  { fields.push(current.trim()); current = ''; }
                else                  { current += ch; }
            }
        }
        fields.push(current.trim());
        return fields;
    });
}

function showImportPreview(header, data, filename) {
    const preview     = document.getElementById('import-preview');
    const previewRows = data.slice(0, 5);
    preview.style.display = 'block';
    preview.innerHTML = `
    <div style="margin-top:1rem">
        <p style="font-size:.85rem;color:var(--clr-muted);margin-bottom:.5rem">
            📄 <strong>${escHtml(filename)}</strong> — ${data.length} rânduri detectate. Previzualizare (primele 5):
        </p>
        <div style="overflow-x:auto">
            <table class="list-items-table" style="font-size:.82rem">
                <thead><tr>${header.map(h => `<th>${escHtml(h)}</th>`).join('')}</tr></thead>
                <tbody>${previewRows.map(row => `<tr>${row.map(cell => `<td>${escHtml(cell)}</td>`).join('')}</tr>`).join('')}</tbody>
            </table>
        </div>
        <div style="margin-top:1rem;display:flex;gap:.5rem">
            <button class="btn btn-primary btn-auto" id="btn-do-import">
                <span class="spinner"></span><span class="btn-text">Importă ${data.length} rânduri</span>
            </button>
            <button class="btn btn-outline btn-auto" id="btn-cancel-import">Anulează</button>
        </div>
    </div>`;
    document.getElementById('btn-do-import').addEventListener('click', () => importRows(header, data));
    document.getElementById('btn-cancel-import').addEventListener('click', () => {
        preview.style.display = 'none';
        document.getElementById('csv-file').value = '';
    });
}

async function importRows(header, rows) {
    const btn = document.getElementById('btn-do-import');
    btn.disabled = true;
    btn.classList.add('loading');

    const normalizedHeader = header.map(h => h.toLowerCase().trim().replace(/\s+/g, '_'));
    let success = 0, errors = 0;

    for (const row of rows) {
        const obj = {};
        normalizedHeader.forEach((key, i) => { obj[key] = row[i]?.trim() || null; });
        if (obj.price)              obj.price              = parseFloat(obj.price)  || null;
        if (obj.category_id)        obj.category_id        = parseInt(obj.category_id) || null;
        if (obj.perishability_days) obj.perishability_days = parseInt(obj.perishability_days) || null;

        // ID-urile separate prin '|' sunt convertite în array de obiecte {id: N}
        obj.allergens       = obj.allergens       ? obj.allergens.split('|').map(id => parseInt(id.trim())).filter(Boolean).map(id => ({ id }))       : [];
        obj.dietary_regimes = obj.dietary_regimes ? obj.dietary_regimes.split('|').map(id => parseInt(id.trim())).filter(Boolean).map(id => ({ id })) : [];

        try { await api('POST', '/beverages-app/api/beverages.php', obj); success++; }
        catch { errors++; }
    }

    btn.disabled = false;
    btn.classList.remove('loading');
    showImportFeedback(`Import finalizat: ${success} adăugate cu succes${errors ? `, ${errors} erori` : ''}.`, errors ? 'error' : 'success');
    document.getElementById('import-preview').style.display = 'none';
    document.getElementById('csv-file').value = '';
}

function initJsonImport() {
    const fileInput = document.getElementById('json-file');
    const dropZone  = document.getElementById('import-json-drop');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragging'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragging'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragging');
        const file = e.dataTransfer.files[0];
        if (file) handleJsonFile(file);
    });
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) handleJsonFile(fileInput.files[0]);
    });
}

function handleJsonFile(file) {
    if (!file.name.endsWith('.json')) { showJsonFeedback('Selectează un fișier cu extensia .json.', 'error'); return; }
    const reader = new FileReader();
    reader.onload = e => {
        let data;
        try { data = JSON.parse(e.target.result); }
        catch { showJsonFeedback('Fișierul nu este JSON valid.', 'error'); return; }

        const rows = Array.isArray(data) ? data : (Array.isArray(data.beverages) ? data.beverages : null);
        if (!rows || rows.length === 0) { showJsonFeedback('JSON-ul trebuie să fie un array de obiecte (sau {"beverages": [...]}).', 'error'); return; }
        if (!rows[0]?.name)             { showJsonFeedback('Fiecare obiect trebuie să aibă câmpul "name".', 'error'); return; }
        showJsonPreview(rows, file.name);
    };
    reader.readAsText(file, 'UTF-8');
}

function showJsonPreview(rows, filename) {
    const preview = document.getElementById('import-json-preview');
    const cols    = ['name', 'category_id', 'price', 'description', 'season', 'region'];
    preview.style.display = 'block';
    preview.innerHTML = `
    <div style="margin-top:1rem">
        <p style="font-size:.85rem;color:var(--clr-muted);margin-bottom:.5rem">
            📋 <strong>${escHtml(filename)}</strong> — ${rows.length} obiecte detectate. Previzualizare (primele 5):
        </p>
        <div style="overflow-x:auto">
            <table class="list-items-table" style="font-size:.82rem">
                <thead><tr>${cols.map(c => `<th>${escHtml(c)}</th>`).join('')}</tr></thead>
                <tbody>
                    ${rows.slice(0, 5).map(row =>
                        `<tr>${cols.map(c => `<td>${escHtml(String(row[c] ?? ''))}</td>`).join('')}</tr>`
                    ).join('')}
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;display:flex;gap:.5rem">
            <button class="btn btn-primary btn-auto" id="btn-do-json-import">
                <span class="spinner"></span><span class="btn-text">Importă ${rows.length} obiecte</span>
            </button>
            <button class="btn btn-outline btn-auto" id="btn-cancel-json-import">Anulează</button>
        </div>
    </div>`;
    document.getElementById('btn-do-json-import').addEventListener('click', () => importJsonRows(rows));
    document.getElementById('btn-cancel-json-import').addEventListener('click', () => {
        preview.style.display = 'none';
        document.getElementById('json-file').value = '';
    });
}

async function importJsonRows(rows) {
    const btn = document.getElementById('btn-do-json-import');
    btn.disabled = true;
    btn.classList.add('loading');

    let success = 0, errors = 0;
    for (const raw of rows) {
        const obj = { ...raw };
        ['category_id', 'perishability_days'].forEach(f => {
            if (obj[f] != null && obj[f] !== '') obj[f] = parseInt(obj[f]) || null;
        });
        ['price'].forEach(f => {
            if (obj[f] != null && obj[f] !== '') obj[f] = parseFloat(obj[f]) || null;
        });
        try { await api('POST', '/beverages-app/api/beverages.php', obj); success++; }
        catch { errors++; }
    }

    btn.disabled = false;
    btn.classList.remove('loading');
    showJsonFeedback(`Import JSON finalizat: ${success} adăugate${errors ? `, ${errors} erori` : ''}.`, errors ? 'error' : 'success');
    document.getElementById('import-json-preview').style.display = 'none';
    document.getElementById('json-file').value = '';
}

function showFeedback(message, type = 'success') {
    const el = document.getElementById('users-feedback');
    el.textContent = message;
    el.className   = `list-feedback visible ${type}`;
    setTimeout(() => el.classList.remove('visible'), 4000);
}

function showImportFeedback(message, type = 'success') {
    const el = document.getElementById('import-feedback');
    el.textContent = message;
    el.className   = `list-feedback visible ${type}`;
    setTimeout(() => el.classList.remove('visible'), 5000);
}

function showJsonFeedback(message, type = 'success') {
    const el = document.getElementById('import-json-feedback');
    el.textContent = message;
    el.className   = `list-feedback visible ${type}`;
    if (message) setTimeout(() => el.classList.remove('visible'), 5000);
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
