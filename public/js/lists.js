/**
 * lists.js
 * Logica paginii de liste de cumpărături.
 *
 * Funcționalități:
 *  - Creare / ștergere liste personale
 *  - Selectare listă și vizualizare iteme
 *  - Adăugare băutură din dropdown în lista selectată
 *  - Bifarea/debifarea unui item (PATCH checked)
 *  - Ștergerea unui item din listă (DELETE remove_item)
 */

/* ================================================================
   State global
   ================================================================ */

// ID-ul listei deschise în panoul drept (null dacă niciuna)
let activeListId = null;

// Lista completă de băuturi — încărcată o dată, folosită pentru dropdown
let allBeverages = [];

/* ================================================================
   Utilitare API
   ================================================================ */

/**
 * api(method, url, body)
 * Funcție generică REST. Aruncă o eroare dacă statusul HTTP nu e 2xx.
 */
async function api(method, url, body = null) {
    // Construim opțiunile cererii
    const opts = { method, headers: { 'Content-Type': 'application/json' } };
    if (body !== null) opts.body = JSON.stringify(body);

    const res  = await fetch(url, opts);
    const data = await res.json();

    // Dacă serverul a returnat eroare, aruncăm un Error cu mesajul primit
    if (!res.ok) throw new Error(data.error ?? 'Eroare server.');
    return data;
}

/* ================================================================
   Inițializare
   ================================================================ */

document.addEventListener('DOMContentLoaded', async () => {
    // Atașăm event listeneri pe formular și butoanele fixe
    initCreateForm();
    initDetailActions();

    // Încărcăm în paralel listele și catalogul de băuturi (mai rapid)
    await Promise.all([loadLists(), loadBeverages()]);
});

/* ================================================================
   Creare listă
   ================================================================ */

/**
 * initCreateForm()
 * Atașează listener pe formularul de creare a unei noi liste.
 */
function initCreateForm() {
    const form = document.getElementById('create-form');
    form.addEventListener('submit', async e => {
        e.preventDefault(); // Prevenim reload-ul paginii

        const input = document.getElementById('list-name');
        const errEl = document.getElementById('create-error');
        const btn   = form.querySelector('button[type=submit]');

        // Golim eroarea anterioară
        errEl.textContent = '';
        errEl.classList.remove('visible');

        const name = input.value.trim();
        // Validare: numele nu poate fi gol
        if (!name) {
            errEl.textContent = 'Completează numele listei.';
            errEl.classList.add('visible');
            return;
        }

        // Activăm loading pe buton
        btn.disabled = true;
        btn.classList.add('loading');

        try {
            // Trimitem POST la API pentru a crea lista
            const data = await api('POST', '/beverages-app/api/shopping_lists.php', { name });
            input.value = ''; // Golim câmpul după creare
            await loadLists(); // Reîncărcăm lista de carduri
            // Deschidem automat lista nou creată
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

/* ================================================================
   Listele utilizatorului (sidebar stâng)
   ================================================================ */

/**
 * loadLists()
 * Încarcă toate listele userului de la API și le randează în sidebar.
 */
async function loadLists() {
    const sidebar = document.getElementById('lists-sidebar');
    sidebar.innerHTML = '<p style="color:var(--clr-muted);font-size:.88rem">Se încarcă...</p>';

    try {
        const data  = await api('GET', '/beverages-app/api/shopping_lists.php');
        const lists = data.lists ?? [];

        if (!lists.length) {
            // Stare goală: nicio listă creată încă
            sidebar.innerHTML = `
                <div class="list-empty-state" style="padding:1.5rem 0">
                    <p>Nu ai nicio listă. Creează una mai sus.</p>
                </div>`;
            return;
        }

        // Generăm câte un card pentru fiecare listă
        sidebar.innerHTML = lists.map(l => listCardHTML(l)).join('');

        // Adăugăm event listeneri pe fiecare card după inserare în DOM
        sidebar.querySelectorAll('.list-card').forEach(card => {
            // Click pe card → selectăm lista (deschide panoul drept)
            card.addEventListener('click', () => selectList(parseInt(card.dataset.id)));
        });

        // Evidențiem cardul listei active (dacă există)
        highlightActiveCard();

    } catch (err) {
        sidebar.innerHTML = `<p style="color:var(--clr-danger);font-size:.88rem">${err.message}</p>`;
    }
}

/**
 * listCardHTML(list)
 * Generează HTML-ul pentru un card de listă în sidebar.
 */
function listCardHTML(list) {
    // Numărul de iteme cu text singular/plural
    const count  = parseInt(list.item_count ?? 0);
    const countLabel = count === 1 ? '1 item' : `${count} iteme`;

    // Clasa 'active' e adăugată dacă lista e cea selectată curent
    const active = list.id == activeListId ? 'active' : '';

    return `
    <div class="list-card ${active}" data-id="${list.id}" role="button" tabindex="0"
         aria-label="Deschide lista ${escHtml(list.name)}">
        <div class="list-card__name">${escHtml(list.name)}</div>
        <div class="list-card__meta">${countLabel}</div>
    </div>`;
}

/**
 * highlightActiveCard()
 * Adaugă clasa 'active' pe cardul listei selectate și o elimină de pe celelalte.
 */
function highlightActiveCard() {
    document.querySelectorAll('.list-card').forEach(card => {
        card.classList.toggle('active', parseInt(card.dataset.id) === activeListId);
    });
}

/* ================================================================
   Selectare și afișare detalii listă
   ================================================================ */

/**
 * selectList(id)
 * Setează lista activă și încarcă detaliile ei în panoul drept.
 */
async function selectList(id) {
    // Actualizăm state-ul global
    activeListId = id;
    // Evidențiem cardul corespunzător în sidebar
    highlightActiveCard();
    // Afișăm panoul de detalii (ascundem mesajul inițial)
    document.getElementById('no-list-selected').style.display = 'none';
    document.getElementById('list-detail').style.display     = 'block';

    await loadListDetail(id);
}

/**
 * loadListDetail(id)
 * Încarcă detaliile (itemele) listei cu id-ul dat și le randează în tabel.
 */
async function loadListDetail(id) {
    const tbody    = document.getElementById('items-tbody');
    const titleEl  = document.getElementById('detail-title');
    const metaEl   = document.getElementById('detail-meta');

    // Afișăm loading în timp ce așteptăm răspunsul
    tbody.innerHTML = `<tr><td colspan="5" class="list-empty-state">Se încarcă...</td></tr>`;

    try {
        const data = await api('GET', `/beverages-app/api/shopping_lists.php?id=${id}`);
        const list = data.list;

        // Completăm header-ul panoului
        titleEl.textContent = list.name;
        metaEl.textContent  = `${list.items.length} item${list.items.length === 1 ? '' : 'e'}`;

        // Randăm itemele
        if (!list.items.length) {
            // Tabel gol
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="list-empty-state">
                        Lista e goală. Adaugă băuturi folosind bara de mai sus.
                    </td>
                </tr>`;
        } else {
            // Generăm câte un rând pentru fiecare item
            tbody.innerHTML = list.items.map(item => itemRowHTML(item)).join('');
            // Atașăm listeneri pe butoanele din rânduri
            attachItemListeners();
        }

    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="color:var(--clr-danger);padding:1rem 1.25rem">${err.message}</td></tr>`;
    }
}

/**
 * itemRowHTML(item)
 * Generează HTML-ul pentru un rând din tabelul de iteme.
 */
function itemRowHTML(item) {
    // Rândul bifat primește clasa 'checked' pentru text tăiat
    const checkedClass = item.checked == 1 ? 'checked' : '';
    // Iconița butonului de bifare se schimbă în funcție de starea curentă
    const checkIcon    = item.checked == 1 ? '↩ Debifează' : '✔ Bifează';
    const price        = item.price ? parseFloat(item.price).toFixed(2) + ' lei' : '—';

    return `
    <tr class="${checkedClass}" data-item-id="${item.id}">
        <!-- Denumirea băuturii, cu text tăiat dacă e bifată -->
        <td><span class="item-name">${escHtml(item.beverage_name)}</span></td>
        <!-- Cantitate -->
        <td>${escHtml(String(item.quantity ?? 1))}</td>
        <!-- Prețul unitar al băuturii -->
        <td>${price}</td>
        <!-- Statusul bifat: ✔ sau — -->
        <td style="text-align:center">
            ${item.checked == 1
                ? '<span style="color:var(--clr-success);font-weight:700">✔</span>'
                : '<span style="color:var(--clr-muted)">—</span>'}
        </td>
        <!-- Butoanele de acțiune: Bifează + Elimină -->
        <td>
            <div class="item-actions">
                <!-- Butonul togglare bifat/nebifat -->
                <button class="btn btn-outline btn-sm btn-check"
                        data-item-id="${item.id}"
                        data-checked="${item.checked == 1 ? '1' : '0'}"
                        title="${checkIcon}">
                    ${item.checked == 1 ? '↩' : '✔'}
                </button>
                <!-- Butonul de eliminare item din listă -->
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

/**
 * attachItemListeners()
 * Atașează event listeneri pe butoanele de bifare și eliminare din rânduri.
 * Apelat după fiecare randare a tabelului.
 */
function attachItemListeners() {
    // Butoanele de bifare/debifare
    document.querySelectorAll('.btn-check').forEach(btn => {
        btn.addEventListener('click', async () => {
            const itemId  = parseInt(btn.dataset.itemId);
            // Inversăm starea curentă: 0 → 1, 1 → 0
            const checked = btn.dataset.checked === '1' ? 0 : 1;
            await toggleItem(itemId, checked);
        });
    });

    // Butoanele de eliminare
    document.querySelectorAll('.btn-remove').forEach(btn => {
        btn.addEventListener('click', async () => {
            const itemId = parseInt(btn.dataset.itemId);
            const name   = btn.dataset.name;
            if (confirm(`Elimini "${name}" din listă?`)) {
                await removeItem(itemId);
            }
        });
    });
}

/* ================================================================
   Acțiuni pe lista activă (butoane din header)
   ================================================================ */

/**
 * initDetailActions()
 * Atașează listeneri pe butonul "Șterge lista" și "Adaugă item".
 */
function initDetailActions() {
    // Butonul de ștergere a listei întregi
    document.getElementById('btn-delete-list').addEventListener('click', async () => {
        if (!activeListId) return;
        // Citim titlul listei pentru mesajul de confirmare
        const title = document.getElementById('detail-title').textContent;
        if (!confirm(`Ștergi lista "${title}"? Acțiunea este ireversibilă.`)) return;

        try {
            await api('DELETE', `/beverages-app/api/shopping_lists.php?id=${activeListId}`);
            // Resetăm starea și reîncărcăm sidebar-ul
            activeListId = null;
            document.getElementById('list-detail').style.display     = 'none';
            document.getElementById('no-list-selected').style.display = 'block';
            await loadLists();
        } catch (err) {
            showFeedback(err.message, 'error');
        }
    });

    // Butonul de adăugare item din bara superioară
    document.getElementById('btn-add-item').addEventListener('click', addItemFromBar);
}

/**
 * addItemFromBar()
 * Citește băutura și cantitatea selectate în bara de adăugare și trimite la API.
 */
async function addItemFromBar() {
    const select   = document.getElementById('add-beverage-select');
    const qtyInput = document.getElementById('add-quantity');
    const btn      = document.getElementById('btn-add-item');

    const beverageId = parseInt(select.value);
    const quantity   = parseInt(qtyInput.value) || 1;

    // Validare: trebuie să fie selectată o băutură
    if (!beverageId) {
        showFeedback('Alege o băutură din dropdown.', 'error');
        return;
    }

    btn.disabled = true;

    try {
        // Trimitem POST cu action=add_item la API
        await api('POST', `/beverages-app/api/shopping_lists.php?id=${activeListId}&action=add_item`, {
            beverage_id: beverageId,
            quantity,
        });
        // Resetăm cantitatea la 1 pentru adăugarea următoare
        qtyInput.value = 1;
        // Reîncărcăm detaliile listei și sidebar-ul (pentru actualizarea numărului de iteme)
        await Promise.all([loadListDetail(activeListId), loadLists()]);
        showFeedback('Băutura a fost adăugată.', 'success');
    } catch (err) {
        showFeedback(err.message, 'error');
    } finally {
        btn.disabled = false;
    }
}

/* ================================================================
   Acțiuni pe iteme individuale
   ================================================================ */

/**
 * toggleItem(itemId, checked)
 * Bifează sau debifează un item (PATCH update_item).
 */
async function toggleItem(itemId, checked) {
    try {
        await api('PATCH',
            `/beverages-app/api/shopping_lists.php?action=update_item&item_id=${itemId}`,
            { checked }
        );
        // Reîncărcăm tabelul pentru a reflecta noua stare
        await loadListDetail(activeListId);
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

/**
 * removeItem(itemId)
 * Elimină un item din lista curentă (DELETE remove_item).
 */
async function removeItem(itemId) {
    try {
        await api('DELETE',
            `/beverages-app/api/shopping_lists.php?action=remove_item&item_id=${itemId}`
        );
        // Reîncărcăm tabelul și sidebar-ul pentru actualizarea numărului
        await Promise.all([loadListDetail(activeListId), loadLists()]);
        showFeedback('Itemul a fost eliminat.', 'success');
    } catch (err) {
        showFeedback(err.message, 'error');
    }
}

/* ================================================================
   Catalogul de băuturi (dropdown de adăugare)
   ================================================================ */

/**
 * loadBeverages()
 * Încarcă toate băuturile și populează dropdown-ul din bara de adăugare.
 */
async function loadBeverages() {
    try {
        const data  = await api('GET', '/beverages-app/api/beverages.php');
        allBeverages = data.beverages ?? [];

        const select = document.getElementById('add-beverage-select');
        // Adăugăm câte o opțiune pentru fiecare băutură
        allBeverages.forEach(b => {
            const price = b.price ? ` — ${parseFloat(b.price).toFixed(2)} lei` : '';
            // Option text: "Ceai de musetel — 8.00 lei"
            select.add(new Option(`${b.name}${price}`, b.id));
        });
    } catch {
        // Eșecul la încărcarea băuturilor nu blochează restul paginii
    }
}

/* ================================================================
   Feedback (mesaj de succes/eroare în panoul de detalii)
   ================================================================ */

/**
 * showFeedback(message, type)
 * Afișează un mesaj de feedback în panoul de detalii.
 * type: 'success' (verde) sau 'error' (roșu)
 */
function showFeedback(message, type = 'success') {
    const el = document.getElementById('list-feedback');
    el.textContent = message;
    // Aplicăm clasa CSS corespunzătoare tipului de mesaj
    el.className = `list-feedback visible ${type}`;
    // Ascundem automat mesajul după 3 secunde
    setTimeout(() => el.classList.remove('visible'), 3000);
}

/* ================================================================
   Utilitare
   ================================================================ */

/**
 * escHtml(str)
 * Sanitizează string-ul pentru inserare sigură în innerHTML (anti-XSS).
 */
function escHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g,  '&amp;')
        .replace(/</g,  '&lt;')
        .replace(/>/g,  '&gt;')
        .replace(/"/g,  '&quot;')
        .replace(/'/g,  '&#39;');
}
