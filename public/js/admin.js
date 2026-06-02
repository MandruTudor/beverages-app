/**
 * admin.js
 * Logica panoului de administrare.
 *
 * Funcționalități:
 *  - Listare utilizatori cu roluri
 *  - Promovare/demotare (admin ↔ user)
 *  - Ștergere utilizator cu confirmare
 *  - Import băuturi din fișier CSV
 */

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

document.addEventListener('DOMContentLoaded', () => {
    loadUsers();
    initImport();
});

/* ================================================================
   Gestionare utilizatori
   ================================================================ */

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

        // Generăm tabelul cu utilizatorii
        wrap.innerHTML = `
        <table class="list-items-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Înregistrat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                ${users.map(u => userRowHTML(u)).join('')}
            </tbody>
        </table>`;

        // Atașăm listeneri pe butoanele din tabel
        attachUserListeners();

    } catch (err) {
        wrap.innerHTML = `<p style="color:var(--clr-danger)">${err.message}</p>`;
    }
}

/**
 * userRowHTML(u)
 * Generează HTML-ul pentru un rând din tabelul de utilizatori.
 */
function userRowHTML(u) {
    // Formatăm data de înregistrare
    const date = u.created_at ? u.created_at.split(' ')[0] : '—';
    // Badge colorat pentru rol
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
                <!-- Buton promovare/demotare -->
                <button class="btn btn-outline btn-sm btn-role"
                        data-id="${u.id}" data-role="${u.role}"
                        title="${u.role === 'admin' ? 'Demotează la user' : 'Promovează la admin'}">
                    ${u.role === 'admin' ? '↓ User' : '↑ Admin'}
                </button>
                <!-- Buton ștergere -->
                <button class="btn btn-danger btn-sm btn-del-user"
                        data-id="${u.id}" data-name="${escHtml(u.username)}"
                        title="Șterge utilizatorul">
                    🗑
                </button>
            </div>
        </td>
    </tr>`;
}

/**
 * attachUserListeners()
 * Atașează listeneri pe butoanele de rol și ștergere din tabel.
 */
function attachUserListeners() {
    // Butoanele de promovare/demotare
    document.querySelectorAll('.btn-role').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id      = parseInt(btn.dataset.id);
            // Inversăm rolul curent
            const newRole = btn.dataset.role === 'admin' ? 'user' : 'admin';
            const label   = newRole === 'admin' ? 'promovezi' : 'demotezi';
            if (!confirm(`${label.charAt(0).toUpperCase() + label.slice(1)} acest utilizator la rolul "${newRole}"?`)) return;

            try {
                await api('PATCH', `/beverages-app/api/users.php?id=${id}&action=set_role`, { role: newRole });
                showFeedback(`Rolul a fost actualizat la "${newRole}".`, 'success');
                await loadUsers(); // Reîncărcăm tabelul
            } catch (err) {
                showFeedback(err.message, 'error');
            }
        });
    });

    // Butoanele de ștergere
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

/* ================================================================
   Import CSV
   ================================================================ */

/**
 * initImport()
 * Inițializează zona de upload CSV și logica de parsare/import.
 */
function initImport() {
    const fileInput = document.getElementById('csv-file');
    const dropZone  = document.getElementById('import-drop');

    // Click pe zonă → deschide selectorul de fișiere
    dropZone.addEventListener('click', () => fileInput.click());
    // Accesibilitate: Enter/Space activează click-ul
    dropZone.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });

    // Drag-and-drop: fișierul tras peste zonă
    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('dragging');
    });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragging'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragging');
        const file = e.dataTransfer.files[0];
        if (file) handleCSVFile(file);
    });

    // Selectare fișier prin dialog
    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) handleCSVFile(fileInput.files[0]);
    });
}

/**
 * handleCSVFile(file)
 * Citește fișierul CSV, îl parsează și afișează previzualizare + buton import.
 */
function handleCSVFile(file) {
    // Verificăm că e fișier CSV
    if (!file.name.endsWith('.csv')) {
        showImportFeedback('Selectează un fișier cu extensia .csv.', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        // Parsăm conținutul CSV
        const rows = parseCSV(e.target.result);
        if (rows.length < 2) {
            showImportFeedback('Fișierul CSV trebuie să conțină cel puțin un rând de date + antet.', 'error');
            return;
        }

        // Primul rând = antet, restul = date
        const [header, ...data] = rows;
        showImportPreview(header, data, file.name);
    };
    // Citim fișierul ca text UTF-8
    reader.readAsText(file, 'UTF-8');
}

/**
 * parseCSV(text)
 * Parsează un string CSV în array de array-uri.
 * Gestionează câmpuri înfășurate în ghilimele (RFC 4180).
 */
function parseCSV(text) {
    // Eliminăm BOM-ul UTF-8 dacă există
    const clean = text.replace(/^﻿/, '');
    // Împărțim pe rânduri (gestionăm atât \r\n cât și \n)
    const lines = clean.split(/\r?\n/).filter(l => l.trim());
    return lines.map(line => {
        const fields = [];
        let current  = '';
        let inQuotes = false;

        for (let i = 0; i < line.length; i++) {
            const ch   = line[i];
            const next = line[i + 1];

            if (inQuotes) {
                if (ch === '"' && next === '"') { current += '"'; i++; } // Ghilimea escapată ""
                else if (ch === '"')             { inQuotes = false; }   // Sfârșitul câmpului
                else                             { current += ch; }
            } else {
                if (ch === '"')  { inQuotes = true; }
                else if (ch === ',') { fields.push(current.trim()); current = ''; }
                else { current += ch; }
            }
        }
        fields.push(current.trim()); // Ultimul câmp
        return fields;
    });
}

/**
 * showImportPreview(header, data, filename)
 * Afișează previzualizarea primelor 5 rânduri și butonul de import.
 */
function showImportPreview(header, data, filename) {
    const preview = document.getElementById('import-preview');
    const previewRows = data.slice(0, 5); // Primele 5 rânduri

    preview.style.display = 'block';
    preview.innerHTML = `
    <div style="margin-top:1rem">
        <p style="font-size:.85rem;color:var(--clr-muted);margin-bottom:.5rem">
            📄 <strong>${escHtml(filename)}</strong> — ${data.length} rânduri detectate. Previzualizare (primele 5):
        </p>
        <div style="overflow-x:auto">
            <table class="list-items-table" style="font-size:.82rem">
                <thead>
                    <tr>${header.map(h => `<th>${escHtml(h)}</th>`).join('')}</tr>
                </thead>
                <tbody>
                    ${previewRows.map(row => `<tr>${row.map(cell => `<td>${escHtml(cell)}</td>`).join('')}</tr>`).join('')}
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;display:flex;gap:.5rem">
            <button class="btn btn-primary btn-auto" id="btn-do-import">
                <span class="spinner"></span>
                <span class="btn-text">Importă ${data.length} rânduri</span>
            </button>
            <button class="btn btn-outline btn-auto" id="btn-cancel-import">Anulează</button>
        </div>
    </div>`;

    // Buton confirm import
    document.getElementById('btn-do-import').addEventListener('click', () => importRows(header, data));
    // Buton anulare
    document.getElementById('btn-cancel-import').addEventListener('click', () => {
        preview.style.display = 'none';
        document.getElementById('csv-file').value = '';
    });
}

/**
 * importRows(header, rows)
 * Trimite fiecare rând CSV la API ca un POST pentru crearea unei băuturi.
 */
async function importRows(header, rows) {
    const btn = document.getElementById('btn-do-import');
    btn.disabled = true;
    btn.classList.add('loading');

    // Normalizăm antetele (lowercase, fără spații)
    const normalizedHeader = header.map(h => h.toLowerCase().trim().replace(/\s+/g, '_'));

    let success = 0;
    let errors  = 0;

    // Importăm rând cu rând — secvențial pentru a evita supraîncărcarea serverului
    for (const row of rows) {
        // Construim obiectul din perechile cheie-valoare
        const obj = {};
        normalizedHeader.forEach((key, i) => {
            // Valori goale → null (nu trimitem string gol la server)
            obj[key] = row[i]?.trim() || null;
        });

        // Convertim câmpurile numerice
        if (obj.price)              obj.price              = parseFloat(obj.price)  || null;
        if (obj.category_id)        obj.category_id        = parseInt(obj.category_id) || null;
        if (obj.perishability_days) obj.perishability_days = parseInt(obj.perishability_days) || null;

        try {
            // Trimitem POST la API-ul de băuturi
            await api('POST', '/beverages-app/api/beverages.php', obj);
            success++;
        } catch {
            errors++;
        }
    }

    btn.disabled = false;
    btn.classList.remove('loading');

    // Afișăm rezultatul importului
    const msg = `Import finalizat: ${success} adăugate cu succes${errors ? `, ${errors} erori` : ''}.`;
    showImportFeedback(msg, errors ? 'error' : 'success');

    // Ascundem previzualizarea
    document.getElementById('import-preview').style.display = 'none';
    document.getElementById('csv-file').value = '';
}

/* ================================================================
   Feedback
   ================================================================ */

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

/* ================================================================
   Utilitare
   ================================================================ */

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
