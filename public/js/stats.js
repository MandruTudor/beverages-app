/**
 * stats.js
 * Logica paginii de statistici.
 *
 * Ce face:
 *  - Încarcă statisticile și băuturile de la API
 *  - Randează carduri cu totaluri
 *  - Generează grafice bar chart în SVG pur (fără librării externe)
 *  - Permite descărcarea graficelor ca fișiere .svg
 *  - Permite exportul datelor în CSV și JSON
 */

/* ================================================================
   Utilitare API
   ================================================================ */

/**
 * api(url)
 * Execută un GET și returnează datele JSON parsate.
 */
async function api(url) {
    // fetch() trimite cererea HTTP GET
    const res  = await fetch(url);
    // Parsăm răspunsul JSON
    const data = await res.json();
    // Dacă serverul a returnat eroare, aruncăm un Error
    if (!res.ok) throw new Error(data.error ?? 'Eroare server.');
    return data;
}

/* ================================================================
   Inițializare
   ================================================================ */

document.addEventListener('DOMContentLoaded', async () => {
    // Încărcăm în paralel statisticile și băuturile (pentru export CSV)
    await Promise.all([loadStats(), loadBeveragesForExport()]);

    // Atașăm listeneri pe butoanele de export
    initExportButtons();
});

/* ================================================================
   Statistici principale
   ================================================================ */

/**
 * loadStats()
 * Încarcă summary-ul (totale, by_category, by_season) și top_rated.
 */
async function loadStats() {
    try {
        // Cerem în paralel summary-ul și top-rated-ul — mai rapid
        const [summary, topData] = await Promise.all([
            api('/beverages-app/api/stats.php?action=summary'),
            api('/beverages-app/api/stats.php?action=top_rated&limit=10'),
        ]);

        // Randăm cardurile cu totaluri
        renderStatCards(summary.totals);

        // Grafic 1: băuturi per categorie
        renderBarChart(
            document.getElementById('chart-category'),
            summary.by_category.map(r => ({ label: r.category, value: parseInt(r.count) })),
            '#2563eb'
        );

        // Grafic 2: distribuție pe sezoane
        const seasonLabel = { all: 'Tot anul', winter: 'Iarnă', spring: 'Primăvară', summer: 'Vară', autumn: 'Toamnă', necunoscut: 'Necunoscut' };
        renderBarChart(
            document.getElementById('chart-season'),
            summary.by_season.map(r => ({ label: seasonLabel[r.season] ?? r.season, value: parseInt(r.count) })),
            '#0ea5e9'
        );

        // Grafic 3: băuturi per regiune
        renderBarChart(
            document.getElementById('chart-region'),
            (summary.by_region ?? []).map(r => ({ label: r.region, value: parseInt(r.count) })),
            '#16a34a'
        );

        // Grafic 4: frecvența alergenilor
        renderBarChart(
            document.getElementById('chart-allergen'),
            (summary.by_allergen ?? []).filter(r => parseInt(r.count) > 0).map(r => ({ label: r.allergen, value: parseInt(r.count) })),
            '#dc2626'
        );

        // Statistici de preț
        renderPriceStats(summary.price_stats ?? {});

        // Clasament top rated
        renderTopRated(topData.top_rated ?? []);

    } catch (err) {
        ['chart-category','chart-season','chart-region','chart-allergen','top-rated-container'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = `<p class="error-text">${err.message}</p>`;
        });
    }
}

/* ================================================================
   Carduri totale
   ================================================================ */

/**
 * renderStatCards(totals)
 * Înlocuiește scheletele de loading cu carduri reale.
 */
function renderStatCards(totals) {
    // Definim cardurile: [etichetă, valoare, emoji, culoare accent]
    const cards = [
        { label: 'Băuturi',     value: totals.beverages,   icon: '🍵', color: '#2563eb' },
        { label: 'Categorii',   value: totals.categories,  icon: '📂', color: '#0ea5e9' },
        { label: 'Utilizatori', value: totals.users,       icon: '👥', color: '#16a34a' },
        { label: 'Preferințe',  value: totals.preferences, icon: '⭐', color: '#f59e0b' },
    ];

    // Generăm HTML-ul pentru toate cardurile
    document.getElementById('stats-cards').innerHTML = cards.map(c => `
        <div class="stat-card">
            <!-- Iconița în pătrat colorat -->
            <div class="stat-card__icon" style="background:${c.color}20;color:${c.color}">${c.icon}</div>
            <!-- Valoarea numerică mare -->
            <div class="stat-card__value">${c.value}</div>
            <!-- Eticheta descriptivă -->
            <div class="stat-card__label">${c.label}</div>
        </div>
    `).join('');
}

/* ================================================================
   Grafic bar chart SVG
   ================================================================ */

/**
 * renderBarChart(container, data, barColor)
 * Generează un grafic bar chart complet în SVG și îl inserează în container.
 * Nu folosește nicio librărie externă — SVG pur.
 *
 * @param {HTMLElement} container - Elementul DOM unde se inserează SVG-ul
 * @param {Array}       data      - Array de obiecte { label: string, value: number }
 * @param {string}      barColor  - Culoarea barelor (ex: '#2563eb')
 */
function renderBarChart(container, data, barColor = '#2563eb') {
    if (!container) return; // Container absent din DOM — ignorăm silențios
    if (!data.length) {
        container.innerHTML = '<p class="loading-text">Nu există date de afișat.</p>';
        return;
    }

    // ---- Dimensiunile graficului ----
    const W          = 480;  // Lățimea totală a SVG-ului (viewBox)
    const H          = 260;  // Înălțimea totală
    const PADDING_L  = 40;   // Spațiu stânga (pentru valorile de pe axa Y)
    const PADDING_B  = 50;   // Spațiu jos (pentru etichetele de pe axa X)
    const PADDING_T  = 20;   // Spațiu sus
    const PADDING_R  = 10;   // Spațiu dreapta

    // Zona efectivă a graficului (fără padding-uri)
    const chartW = W - PADDING_L - PADDING_R;
    const chartH = H - PADDING_T - PADDING_B;

    // Valoarea maximă din date — folosită pentru scalare
    const maxVal = Math.max(...data.map(d => d.value), 1);

    // Lățimea unei bare + spațiul dintre bare
    const barCount = data.length;
    const barW     = Math.floor(chartW / barCount * 0.6); // 60% lățime, 40% spațiu
    const step     = chartW / barCount;                   // Pasul orizontal per bară

    // ---- Construim SVG-ul ----

    // Linia axei X
    let svg = `<svg xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 ${W} ${H}"
                    width="100%" height="${H}"
                    font-family="'Segoe UI',system-ui,sans-serif">`;

    // Fundal alb
    svg += `<rect width="${W}" height="${H}" fill="#fff" rx="8"/>`;

    // Linii orizontale de ghidaj (grid) — 4 linii la intervale egale
    for (let i = 1; i <= 4; i++) {
        // Valoarea afișată pe axa Y pentru această linie
        const yVal  = Math.round(maxVal / 4 * i);
        // Poziția Y a liniei (de jos în sus)
        const yPos  = PADDING_T + chartH - (chartH * i / 4);

        // Linia orizontală de ghidaj (gri deschis)
        svg += `<line x1="${PADDING_L}" y1="${yPos}" x2="${W - PADDING_R}" y2="${yPos}"
                      stroke="#e2e8f0" stroke-width="1"/>`;

        // Valoarea pe axa Y (stânga)
        svg += `<text x="${PADDING_L - 5}" y="${yPos + 4}"
                      font-size="10" fill="#94a3b8" text-anchor="end">${yVal}</text>`;
    }

    // Axa X (linie orizontală la baza graficului)
    svg += `<line x1="${PADDING_L}" y1="${PADDING_T + chartH}"
                  x2="${W - PADDING_R}" y2="${PADDING_T + chartH}"
                  stroke="#cbd5e1" stroke-width="1.5"/>`;

    // ---- Barele și etichetele ----
    data.forEach((d, i) => {
        // Înălțimea barei proporțională cu valoarea
        const barH = Math.max((d.value / maxVal) * chartH, 2); // minimum 2px vizibil

        // Poziția X a centrului barei
        const cx = PADDING_L + step * i + step / 2;
        // Poziția X a colțului stâng al barei
        const bx = cx - barW / 2;
        // Poziția Y a vârfului barei (de sus în jos în sistemul SVG)
        const by = PADDING_T + chartH - barH;

        // Bara propriu-zisă cu colțuri rotunjite sus
        svg += `<rect x="${bx}" y="${by}" width="${barW}" height="${barH}"
                      fill="${barColor}" rx="3" opacity="0.85"/>`;

        // Valoarea deasupra barei
        svg += `<text x="${cx}" y="${by - 5}"
                      font-size="11" fill="${barColor}"
                      font-weight="600" text-anchor="middle">${d.value}</text>`;

        // Eticheta sub axa X — trunchiată dacă e prea lungă
        const labelShort = d.label.length > 10 ? d.label.slice(0, 9) + '…' : d.label;
        svg += `<text x="${cx}" y="${PADDING_T + chartH + 18}"
                      font-size="10" fill="#64748b"
                      text-anchor="middle">${labelShort}</text>`;
    });

    // Închidem tag-ul SVG
    svg += `</svg>`;

    // Inserăm SVG-ul în container
    container.innerHTML = svg;
}

/* ================================================================
   Statistici de preț
   ================================================================ */

function renderPriceStats(ps) {
    const el = document.getElementById('price-stats-container');
    if (!el) return;
    if (!ps.avg_price) {
        el.innerHTML = '<p class="loading-text">Nu există prețuri înregistrate.</p>';
        return;
    }
    el.innerHTML = `
        <div style="display:flex;gap:1rem;flex-wrap:wrap">
            <div class="stat-card" style="flex:1;min-width:140px">
                <div class="stat-card__label">Preț minim</div>
                <div class="stat-card__value" style="font-size:1.4rem;color:#16a34a">${parseFloat(ps.min_price).toFixed(2)} lei</div>
            </div>
            <div class="stat-card" style="flex:1;min-width:140px">
                <div class="stat-card__label">Preț mediu</div>
                <div class="stat-card__value" style="font-size:1.4rem;color:#2563eb">${parseFloat(ps.avg_price).toFixed(2)} lei</div>
            </div>
            <div class="stat-card" style="flex:1;min-width:140px">
                <div class="stat-card__label">Preț maxim</div>
                <div class="stat-card__value" style="font-size:1.4rem;color:#dc2626">${parseFloat(ps.max_price).toFixed(2)} lei</div>
            </div>
        </div>`;
}

/* ================================================================
   Clasament top rated
   ================================================================ */

/**
 * renderTopRated(items)
 * Randează un tabel cu băuturile cel mai bine evaluate.
 * Dacă nu există evaluări, afișează un mesaj.
 */
function renderTopRated(items) {
    const container = document.getElementById('top-rated-container');

    if (!items.length) {
        // Nicio evaluare în sistem încă
        container.innerHTML = `
            <div class="list-empty-state">
                <p>⭐ Nu există evaluări de băuturi încă. Adaugă preferințe pentru a vedea clasamentul.</p>
            </div>`;
        return;
    }

    // Generăm un tabel cu medalii pentru primele 3 locuri
    const medals = ['🥇', '🥈', '🥉'];

    container.innerHTML = `
    <table class="list-items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Băutură</th>
                <th>Preț</th>
                <th>Rating mediu</th>
                <th>Evaluări</th>
            </tr>
        </thead>
        <tbody>
            ${items.map((item, i) => `
            <tr>
                <!-- Medalii pentru primele 3, număr pentru restul -->
                <td style="font-size:1.1rem">${medals[i] ?? (i + 1)}</td>
                <td style="font-weight:600">${escHtml(item.name)}</td>
                <td>${item.price ? parseFloat(item.price).toFixed(2) + ' lei' : '—'}</td>
                <!-- Bara vizuală de rating (din 5) -->
                <td>
                    <div style="display:flex;align-items:center;gap:.5rem">
                        <div style="width:80px;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden">
                            <div style="width:${(item.avg_rating/5*100).toFixed(0)}%;height:100%;background:#f59e0b;border-radius:3px"></div>
                        </div>
                        <span style="font-weight:700;color:#f59e0b">${parseFloat(item.avg_rating).toFixed(1)}</span>
                    </div>
                </td>
                <td style="color:var(--clr-muted)">${item.rating_count} evaluări</td>
            </tr>`).join('')}
        </tbody>
    </table>`;
}

/* ================================================================
   Export CSV și JSON
   ================================================================ */

// Stocăm băuturile pentru export (încărcate la init)
let exportBeverages = [];

/**
 * loadBeveragesForExport()
 * Încarcă lista completă de băuturi de la API pentru exportul CSV/JSON.
 */
async function loadBeveragesForExport() {
    try {
        const data    = await api('/beverages-app/api/beverages.php');
        exportBeverages = data.beverages ?? [];
    } catch {
        // Eșecul nu blochează restul paginii
    }
}

/**
 * initExportButtons()
 * Atașează listeneri pe butoanele de export CSV, JSON și SVG.
 */
function initExportButtons() {
    document.getElementById('btn-export-csv').addEventListener('click', exportCSV);
    document.getElementById('btn-export-json').addEventListener('click', exportJSON);
    document.getElementById('btn-export-stats-csv').addEventListener('click', exportStatsCSV);

    document.getElementById('btn-svg-category').addEventListener('click', () => downloadSVG('chart-category',  'statistici-categorii.svg'));
    document.getElementById('btn-svg-season').addEventListener('click',   () => downloadSVG('chart-season',    'statistici-sezoane.svg'));
    document.getElementById('btn-svg-region').addEventListener('click',   () => downloadSVG('chart-region',    'statistici-regiuni.svg'));
    document.getElementById('btn-svg-allergen').addEventListener('click', () => downloadSVG('chart-allergen',  'statistici-alergeni.svg'));
}

/**
 * exportCSV()
 * Generează un fișier CSV cu toate băuturile și declanșează descărcarea.
 */
function exportCSV() {
    if (!exportBeverages.length) {
        alert('Nu există date de exportat.');
        return;
    }

    // Definim antetele coloanelor CSV
    const headers = ['ID', 'Nume', 'Categorie', 'Pret (lei)', 'Descriere', 'Sezon', 'Regiune', 'Perisabilitate (zile)', 'Valabil pana la'];

    const rows = exportBeverages.map(b => [
        b.id,
        b.name,
        b.category_name ?? '',
        b.price ?? '',
        (b.description ?? '').replace(/,/g, ';'),
        b.season ?? '',
        b.region ?? '',
        b.perishability_days ?? '',
        b.valid_until ?? '',
    ]);

    // Combinăm antetul cu rândurile, separând câmpurile cu virgulă
    // Câmpurile care conțin virgulă sau ghilimele sunt înfășurate în ghilimele duble
    const csvContent = [headers, ...rows]
        .map(row => row.map(cell => {
            const str = String(cell ?? '');
            // Dacă valoarea conține virgulă, ghilimea sau newline — o înfășurăm în ""
            return str.includes(',') || str.includes('"') || str.includes('\n')
                ? `"${str.replace(/"/g, '""')}"` // Escapăm ghilimelele duble
                : str;
        }).join(','))
        .join('\n');

    // Adăugăm BOM-ul UTF-8 pentru ca Excel să recunoască corect caracterele românești
    const blob = new Blob(['﻿' + csvContent], { type: 'text/csv;charset=utf-8;' });
    downloadBlob(blob, 'bauturi-export.csv');
}

/**
 * exportStatsCSV()
 * Generează un CSV cu toate datele statistice: categorii, sezoane, regiuni,
 * alergeni, prețuri și top rated — grupate în secțiuni separate.
 */
async function exportStatsCSV() {
    try {
        const [summary, topData] = await Promise.all([
            api('/beverages-app/api/stats.php?action=summary'),
            api('/beverages-app/api/stats.php?action=top_rated&limit=20'),
        ]);

        const lines = [];
        const row   = cols => cols.map(c => {
            const s = String(c ?? '');
            return s.includes(',') || s.includes('"') || s.includes('\n')
                ? `"${s.replace(/"/g, '""')}"` : s;
        }).join(',');

        // Secțiunea: per categorie
        lines.push('Secțiune,Categorie,Număr băuturi');
        (summary.by_category ?? []).forEach(r => lines.push(row(['Per categorie', r.category, r.count])));
        lines.push('');

        // Secțiunea: per sezon
        lines.push('Secțiune,Sezon,Număr băuturi');
        const sl = { all: 'Tot anul', winter: 'Iarnă', spring: 'Primăvară', summer: 'Vară', autumn: 'Toamnă', necunoscut: 'Necunoscut' };
        (summary.by_season ?? []).forEach(r => lines.push(row(['Per sezon', sl[r.season] ?? r.season, r.count])));
        lines.push('');

        // Secțiunea: per regiune
        lines.push('Secțiune,Regiune,Număr băuturi');
        (summary.by_region ?? []).forEach(r => lines.push(row(['Per regiune', r.region, r.count])));
        lines.push('');

        // Secțiunea: alergeni
        lines.push('Secțiune,Alergen,Număr băuturi afectate');
        (summary.by_allergen ?? []).forEach(r => lines.push(row(['Alergeni', r.allergen, r.count])));
        lines.push('');

        // Secțiunea: prețuri
        const ps = summary.price_stats ?? {};
        lines.push('Secțiune,Indicator,Valoare (lei)');
        if (ps.avg_price) {
            lines.push(row(['Prețuri', 'Minim', ps.min_price]));
            lines.push(row(['Prețuri', 'Mediu', ps.avg_price]));
            lines.push(row(['Prețuri', 'Maxim', ps.max_price]));
        }
        lines.push('');

        // Secțiunea: top rated
        lines.push('Secțiune,Loc,Băutură,Rating mediu,Evaluări,Preț (lei)');
        (topData.top_rated ?? []).forEach((item, i) => lines.push(
            row(['Top evaluat', i + 1, item.name, item.avg_rating, item.rating_count, item.price ?? ''])
        ));

        const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
        downloadBlob(blob, 'statistici-export.csv');

    } catch (err) {
        alert('Eroare la exportul statisticilor: ' + err.message);
    }
}

/**
 * exportJSON()
 * Deschide endpoint-ul de export JSON al aplicației.
 * Serverul returnează JSON-ul complet cu toate băuturile și relațiile lor.
 */
function exportJSON() {
    // Deschidem URL-ul de export direct — browserul descarcă sau afișează JSON-ul
    window.open('/beverages-app/api/export.php', '_blank');
}

/**
 * downloadSVG(containerId, filename)
 * Extrage SVG-ul dintr-un container și îl descarcă ca fișier .svg.
 *
 * @param {string} containerId - ID-ul elementului care conține SVG-ul
 * @param {string} filename    - Numele fișierului descărcat
 */
function downloadSVG(containerId, filename) {
    const svg = document.querySelector(`#${containerId} svg`);
    if (!svg) { alert('Graficul nu este disponibil.'); return; }

    // Serializăm SVG-ul în string XML
    const serializer = new XMLSerializer();
    const svgStr     = serializer.serializeToString(svg);

    // Creăm un Blob cu tipul MIME corect pentru SVG
    const blob = new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' });
    downloadBlob(blob, filename);
}

/**
 * downloadBlob(blob, filename)
 * Creează un link de descărcare temporar pentru un Blob și îl activează.
 * Funcționează în toate browserele moderne fără a trimite date la server.
 *
 * @param {Blob}   blob     - Datele fișierului
 * @param {string} filename - Numele fișierului descărcat
 */
function downloadBlob(blob, filename) {
    // URL.createObjectURL creează un URL temporar pentru Blob
    const url = URL.createObjectURL(blob);
    // Creăm un link invizibil cu atributul download
    const a   = document.createElement('a');
    a.href     = url;
    a.download = filename;
    // Adăugăm în DOM, dăm click programatic, apoi îl ștergem
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    // Eliberăm memoria ocupată de URL-ul temporar
    URL.revokeObjectURL(url);
}

/* ================================================================
   Utilitare
   ================================================================ */

/**
 * escHtml(str)
 * Sanitizează string-ul pentru inserare sigură în innerHTML (anti-XSS).
 */
function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
