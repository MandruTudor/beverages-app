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
    initJsonImport();
    initOFF();
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
   Import JSON
   ================================================================ */

/**
 * initJsonImport()
 * Inițializează zona de upload JSON și logica de parsare/import.
 * Funcționează identic cu CSV dar fișierul sursă e JSON.
 */
function initJsonImport() {
    const fileInput = document.getElementById('json-file');
    const dropZone  = document.getElementById('import-json-drop');

    // Click pe zonă → deschide selectorul de fișiere
    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); fileInput.click(); }
    });

    // Drag-and-drop
    dropZone.addEventListener('dragover', e => {
        e.preventDefault();
        dropZone.classList.add('dragging');
    });
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

/**
 * handleJsonFile(file)
 * Citește fișierul JSON, validează structura și afișează previzualizare.
 */
function handleJsonFile(file) {
    if (!file.name.endsWith('.json')) {
        showJsonFeedback('Selectează un fișier cu extensia .json.', 'error');
        return;
    }

    const reader = new FileReader();
    reader.onload = e => {
        let data;
        try {
            data = JSON.parse(e.target.result);
        } catch {
            showJsonFeedback('Fișierul nu este JSON valid.', 'error');
            return;
        }

        // Acceptăm fie un array direct, fie un obiect cu cheie "beverages"
        const rows = Array.isArray(data) ? data : (Array.isArray(data.beverages) ? data.beverages : null);
        if (!rows || rows.length === 0) {
            showJsonFeedback('JSON-ul trebuie să fie un array de obiecte (sau {"beverages": [...]}).', 'error');
            return;
        }

        // Validăm că primul obiect are câmpul obligatoriu "name"
        if (!rows[0]?.name) {
            showJsonFeedback('Fiecare obiect trebuie să aibă câmpul "name".', 'error');
            return;
        }

        showJsonPreview(rows, file.name);
    };
    reader.readAsText(file, 'UTF-8');
}

/**
 * showJsonPreview(rows, filename)
 * Afișează previzualizarea primelor 5 obiecte și butonul de import.
 */
function showJsonPreview(rows, filename) {
    const preview     = document.getElementById('import-json-preview');
    const previewRows = rows.slice(0, 5);
    // Coloanele pe care le arătăm în previzualizare
    const cols        = ['name', 'category_id', 'price', 'description', 'season', 'region'];

    preview.style.display = 'block';
    preview.innerHTML = `
    <div style="margin-top:1rem">
        <p style="font-size:.85rem;color:var(--clr-muted);margin-bottom:.5rem">
            📋 <strong>${escHtml(filename)}</strong> — ${rows.length} obiecte detectate. Previzualizare (primele 5):
        </p>
        <div style="overflow-x:auto">
            <table class="list-items-table" style="font-size:.82rem">
                <thead>
                    <tr>${cols.map(c => `<th>${escHtml(c)}</th>`).join('')}</tr>
                </thead>
                <tbody>
                    ${previewRows.map(row => `
                        <tr>${cols.map(c => `<td>${escHtml(String(row[c] ?? ''))}</td>`).join('')}</tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem;display:flex;gap:.5rem">
            <button class="btn btn-primary btn-auto" id="btn-do-json-import">
                <span class="spinner"></span>
                <span class="btn-text">Importă ${rows.length} obiecte</span>
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

/**
 * importJsonRows(rows)
 * Trimite fiecare obiect JSON la POST /api/beverages.php.
 * Câmpurile numerice sunt normalizate; câmpurile necunoscute sunt ignorate de DTO.
 */
async function importJsonRows(rows) {
    const btn = document.getElementById('btn-do-json-import');
    btn.disabled = true;
    btn.classList.add('loading');

    // Câmpurile numerice care trebuie convertite din string dacă sunt trimise ca text
    const intFields   = ['category_id', 'perishability_days'];
    const floatFields = ['price'];

    let success = 0;
    let errors  = 0;

    for (const raw of rows) {
        // Facem o copie curată cu tipurile corecte
        const obj = { ...raw };
        intFields.forEach(f => {
            if (obj[f] !== undefined && obj[f] !== null && obj[f] !== '')
                obj[f] = parseInt(obj[f]) || null;
        });
        floatFields.forEach(f => {
            if (obj[f] !== undefined && obj[f] !== null && obj[f] !== '')
                obj[f] = parseFloat(obj[f]) || null;
        });

        try {
            await api('POST', '/beverages-app/api/beverages.php', obj);
            success++;
        } catch {
            errors++;
        }
    }

    btn.disabled = false;
    btn.classList.remove('loading');

    const msg = `Import JSON finalizat: ${success} adăugate${errors ? `, ${errors} erori` : ''}.`;
    showJsonFeedback(msg, errors ? 'error' : 'success');
    document.getElementById('import-json-preview').style.display = 'none';
    document.getElementById('json-file').value = '';
}

/* ================================================================
   Open Food Facts
   ================================================================ */

/**
 * initOFF()
 * Inițializează secțiunea de căutare Open Food Facts.
 * Două moduri: căutare text și căutare după cod de bare.
 */
function initOFF() {
    const searchBtn  = document.getElementById('off-search-btn');
    const barcodeBtn = document.getElementById('off-barcode-btn');
    const searchIn   = document.getElementById('off-search-input');
    const barcodeIn  = document.getElementById('off-barcode-input');

    // Căutare după cuvânt cheie
    searchBtn.addEventListener('click', () => {
        const q = searchIn.value.trim();
        if (q.length < 2) { showOFFFeedback('Introdu cel puțin 2 caractere.', 'error'); return; }
        offSearch(q);
    });
    searchIn.addEventListener('keydown', e => {
        if (e.key === 'Enter') searchBtn.click();
    });

    // Căutare după cod de bare
    barcodeBtn.addEventListener('click', () => {
        const b = barcodeIn.value.trim();
        if (!/^\d{8,14}$/.test(b)) { showOFFFeedback('Cod de bare invalid (8-14 cifre).', 'error'); return; }
        offGetByBarcode(b);
    });
    barcodeIn.addEventListener('keydown', e => {
        if (e.key === 'Enter') barcodeBtn.click();
    });
}

/**
 * offSearch(q)
 * Trimite cerere de căutare la proxy-ul nostru PHP și afișează rezultatele.
 */
async function offSearch(q) {
    const btn = document.getElementById('off-search-btn');
    btn.disabled = true;
    btn.classList.add('loading');
    showOFFFeedback('', '');
    document.getElementById('off-results').innerHTML = '';

    try {
        const data = await api('GET', `/beverages-app/api/openfoodfacts.php?action=search&q=${encodeURIComponent(q)}`);
        renderOFFResults(data.products ?? []);
        if (!data.products?.length) showOFFFeedback('Niciun produs găsit pentru această căutare.', 'error');
    } catch (err) {
        showOFFFeedback(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.classList.remove('loading');
    }
}

/**
 * offGetByBarcode(barcode)
 * Obține un singur produs după codul de bare și îl afișează.
 */
async function offGetByBarcode(barcode) {
    const btn = document.getElementById('off-barcode-btn');
    btn.disabled = true;
    btn.classList.add('loading');
    showOFFFeedback('', '');
    document.getElementById('off-results').innerHTML = '';

    try {
        const data = await api('GET', `/beverages-app/api/openfoodfacts.php?action=get&barcode=${encodeURIComponent(barcode)}`);
        renderOFFResults([data.product]);
    } catch (err) {
        showOFFFeedback(err.message, 'error');
    } finally {
        btn.disabled = false;
        btn.classList.remove('loading');
    }
}

/**
 * renderOFFResults(products)
 * Afișează produsele OFF ca un grid de carduri cu buton de import.
 */
function renderOFFResults(products) {
    const wrap = document.getElementById('off-results');
    if (!products.length) { wrap.innerHTML = ''; return; }

    wrap.innerHTML = `
    <p style="font-size:.85rem;color:var(--clr-muted);margin-bottom:.75rem">
        ${products.length} produs${products.length !== 1 ? 'e' : ''} găsit${products.length !== 1 ? 'e' : ''}.
        Click pe <strong>Importă</strong> pentru a adăuga în catalog.
    </p>
    <div class="off-grid">
        ${products.map((p, idx) => offCardHTML(p, idx)).join('')}
    </div>`;

    // Atașăm listeneri pe butoanele de import
    products.forEach((p, idx) => {
        document.getElementById(`off-import-${idx}`)?.addEventListener('click', () => importOFFProduct(p, idx));
    });
}

/**
 * offCardHTML(p, idx)
 * Generează HTML-ul unui card pentru un produs OFF.
 */
function offCardHTML(p, idx) {
    const allergenNames = (p.allergens ?? []).length
        ? `<span style="color:var(--clr-danger);font-size:.8rem">⚠ ${p.allergens.length} alergen${p.allergens.length !== 1 ? 'i' : ''} detectat${p.allergens.length !== 1 ? 'i' : ''}</span>`
        : '';

    const img = p.image_path
        ? `<img src="${escHtml(p.image_path)}" alt="${escHtml(p.name)}"
               style="width:100%;height:120px;object-fit:contain;background:#f8f9fa;border-radius:4px;margin-bottom:.5rem"
               loading="lazy" onerror="this.style.display='none'">`
        : `<div style="width:100%;height:120px;background:var(--clr-surface);border-radius:4px;display:flex;align-items:center;justify-content:center;margin-bottom:.5rem;color:var(--clr-muted);font-size:2rem">🧃</div>`;

    const barcode = p._barcode
        ? `<span style="font-size:.75rem;color:var(--clr-muted)">Cod: ${escHtml(p._barcode)}</span>`
        : '';

    return `
    <div class="off-card" id="off-card-${idx}">
        ${img}
        <div style="font-weight:700;font-size:.95rem;margin-bottom:.25rem">${escHtml(p.name)}</div>
        ${p.region ? `<div style="font-size:.82rem;color:var(--clr-muted)">📍 ${escHtml(p.region)}</div>` : ''}
        ${p.description ? `<div style="font-size:.8rem;color:var(--clr-muted);margin:.25rem 0;max-height:3em;overflow:hidden">${escHtml(p.description.slice(0, 120))}${p.description.length > 120 ? '…' : ''}</div>` : ''}
        ${allergenNames}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:.5rem">
            ${barcode}
            <button class="btn btn-primary btn-sm" id="off-import-${idx}">
                <span class="spinner"></span>
                <span class="btn-text">Importă</span>
            </button>
        </div>
    </div>`;
}

/**
 * importOFFProduct(p, idx)
 * Trimite produsul OFF la POST /api/beverages.php și marchează cardul ca importat.
 */
async function importOFFProduct(p, idx) {
    const btn  = document.getElementById(`off-import-${idx}`);
    const card = document.getElementById(`off-card-${idx}`);
    if (!btn || !card) return;

    btn.disabled = true;
    btn.classList.add('loading');

    // Construim payload-ul pentru API — excludem câmpul intern _barcode
    const payload = {
        name:        p.name,
        category_id: p.category_id  ?? null,
        description: p.description  ?? null,
        image_path:  p.image_path   ?? null,
        region:      p.region       ?? null,
        allergens:   p.allergens    ?? [],
    };

    try {
        await api('POST', '/beverages-app/api/beverages.php', payload);
        // Marcăm cardul ca importat cu succes
        btn.classList.remove('loading');
        btn.textContent = '✓ Importat';
        btn.style.background = 'var(--clr-success, #16a34a)';
        btn.style.borderColor = 'transparent';
        btn.style.color = '#fff';
        card.style.opacity = '0.65';
    } catch (err) {
        btn.disabled = false;
        btn.classList.remove('loading');
        showOFFFeedback(`Eroare la importul "${p.name}": ${err.message}`, 'error');
    }
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

function showJsonFeedback(message, type = 'success') {
    const el = document.getElementById('import-json-feedback');
    el.textContent = message;
    el.className   = `list-feedback visible ${type}`;
    if (message) setTimeout(() => el.classList.remove('visible'), 5000);
}

function showOFFFeedback(message, type = 'success') {
    const el = document.getElementById('off-feedback');
    el.textContent = message;
    el.className   = `list-feedback visible ${type}`;
    if (message) setTimeout(() => el.classList.remove('visible'), 5000);
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
