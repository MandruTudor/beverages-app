async function api(url) {
    const res  = await fetch(url);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error ?? 'Eroare server.');
    return data;
}

document.addEventListener('DOMContentLoaded', async () => {
    await Promise.all([loadStats(), loadBeveragesForExport()]);
    initExportButtons();
});

async function loadStats() {
    try {
        const [summary, topData] = await Promise.all([
            api('/beverages-app/api/stats.php?action=summary'),
            api('/beverages-app/api/stats.php?action=top_rated&limit=10'),
        ]);

        renderStatCards(summary.totals);

        const seasonLabel = { all: 'Tot anul', winter: 'Iarnă', spring: 'Primăvară', summer: 'Vară', autumn: 'Toamnă', necunoscut: 'Necunoscut' };
        renderBarChart(document.getElementById('chart-category'),
            summary.by_category.map(r => ({ label: r.category, value: parseInt(r.count) })), '#2563eb');
        renderBarChart(document.getElementById('chart-season'),
            summary.by_season.map(r => ({ label: seasonLabel[r.season] ?? r.season, value: parseInt(r.count) })), '#0ea5e9');
        renderBarChart(document.getElementById('chart-region'),
            (summary.by_region ?? []).map(r => ({ label: r.region, value: parseInt(r.count) })), '#16a34a');
        renderBarChart(document.getElementById('chart-allergen'),
            (summary.by_allergen ?? []).filter(r => parseInt(r.count) > 0).map(r => ({ label: r.allergen, value: parseInt(r.count) })), '#dc2626');

        renderPriceStats(summary.price_stats ?? {});
        renderTopRated(topData.top_rated ?? []);

    } catch (err) {
        ['chart-category','chart-season','chart-region','chart-allergen','top-rated-container'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = `<p class="error-text">${err.message}</p>`;
        });
    }
}

function renderStatCards(totals) {
    const cards = [
        { label: 'Băuturi',     value: totals.beverages,   icon: '🍵', color: '#2563eb' },
        { label: 'Categorii',   value: totals.categories,  icon: '📂', color: '#0ea5e9' },
        { label: 'Utilizatori', value: totals.users,       icon: '👥', color: '#16a34a' },
        { label: 'Preferințe',  value: totals.preferences, icon: '⭐', color: '#f59e0b' },
    ];
    document.getElementById('stats-cards').innerHTML = cards.map(c => `
        <div class="stat-card">
            <div class="stat-card__icon" style="background:${c.color}20;color:${c.color}">${c.icon}</div>
            <div class="stat-card__value">${c.value}</div>
            <div class="stat-card__label">${c.label}</div>
        </div>
    `).join('');
}

function renderBarChart(container, data, barColor = '#2563eb') {
    if (!container) return;
    if (!data.length) { container.innerHTML = '<p class="loading-text">Nu există date de afișat.</p>'; return; }

    const W = 480, H = 260, PL = 40, PB = 50, PT = 20, PR = 10;
    const chartW = W - PL - PR;
    const chartH = H - PT - PB;
    const maxVal = Math.max(...data.map(d => d.value), 1);
    const barW   = Math.floor(chartW / data.length * 0.6);
    const step   = chartW / data.length;

    let svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" width="100%" height="${H}"
                    font-family="'Segoe UI',system-ui,sans-serif">`;
    svg += `<rect width="${W}" height="${H}" fill="#fff" rx="8"/>`;

    for (let i = 1; i <= 4; i++) {
        const yVal = Math.round(maxVal / 4 * i);
        const yPos = PT + chartH - (chartH * i / 4);
        svg += `<line x1="${PL}" y1="${yPos}" x2="${W - PR}" y2="${yPos}" stroke="#e2e8f0" stroke-width="1"/>`;
        svg += `<text x="${PL - 5}" y="${yPos + 4}" font-size="10" fill="#94a3b8" text-anchor="end">${yVal}</text>`;
    }
    svg += `<line x1="${PL}" y1="${PT + chartH}" x2="${W - PR}" y2="${PT + chartH}" stroke="#cbd5e1" stroke-width="1.5"/>`;

    data.forEach((d, i) => {
        const barH = Math.max((d.value / maxVal) * chartH, 2);
        const cx   = PL + step * i + step / 2;
        const bx   = cx - barW / 2;
        const by   = PT + chartH - barH;
        svg += `<rect x="${bx}" y="${by}" width="${barW}" height="${barH}" fill="${barColor}" rx="3" opacity="0.85"/>`;
        svg += `<text x="${cx}" y="${by - 5}" font-size="11" fill="${barColor}" font-weight="600" text-anchor="middle">${d.value}</text>`;
        const labelShort = d.label.length > 10 ? d.label.slice(0, 9) + '…' : d.label;
        svg += `<text x="${cx}" y="${PT + chartH + 18}" font-size="10" fill="#64748b" text-anchor="middle">${labelShort}</text>`;
    });
    svg += `</svg>`;
    container.innerHTML = svg;
}

function renderPriceStats(ps) {
    const el = document.getElementById('price-stats-container');
    if (!el) return;
    if (!ps.avg_price) { el.innerHTML = '<p class="loading-text">Nu există prețuri înregistrate.</p>'; return; }
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

function renderTopRated(items) {
    const container = document.getElementById('top-rated-container');
    if (!items.length) {
        container.innerHTML = `<div class="list-empty-state"><p>⭐ Nu există evaluări de băuturi încă. Adaugă preferințe pentru a vedea clasamentul.</p></div>`;
        return;
    }
    const medals = ['🥇', '🥈', '🥉'];
    container.innerHTML = `
    <table class="list-items-table">
        <thead><tr><th>#</th><th>Băutură</th><th>Preț</th><th>Rating mediu</th><th>Evaluări</th></tr></thead>
        <tbody>
            ${items.map((item, i) => `
            <tr>
                <td style="font-size:1.1rem">${medals[i] ?? (i + 1)}</td>
                <td style="font-weight:600">${escHtml(item.name)}</td>
                <td>${item.price ? parseFloat(item.price).toFixed(2) + ' lei' : '—'}</td>
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

let exportBeverages = [];

async function loadBeveragesForExport() {
    try {
        const data    = await api('/beverages-app/api/beverages.php');
        exportBeverages = data.beverages ?? [];
    } catch { /* non-critical */ }
}

function initExportButtons() {
    document.getElementById('btn-export-csv').addEventListener('click', exportCSV);
    document.getElementById('btn-export-json').addEventListener('click', exportJSON);
    document.getElementById('btn-export-stats-csv').addEventListener('click', exportStatsCSV);
    document.getElementById('btn-svg-category').addEventListener('click', () => downloadSVG('chart-category',  'statistici-categorii.svg'));
    document.getElementById('btn-svg-season').addEventListener('click',   () => downloadSVG('chart-season',    'statistici-sezoane.svg'));
    document.getElementById('btn-svg-region').addEventListener('click',   () => downloadSVG('chart-region',    'statistici-regiuni.svg'));
    document.getElementById('btn-svg-allergen').addEventListener('click', () => downloadSVG('chart-allergen',  'statistici-alergeni.svg'));
}

function exportCSV() {
    if (!exportBeverages.length) { alert('Nu există date de exportat.'); return; }

    const headers = ['ID', 'Nume', 'Categorie', 'Pret (lei)', 'Descriere', 'Sezon', 'Regiune', 'Perisabilitate (zile)', 'Valabil pana la'];
    const rows = exportBeverages.map(b => [
        b.id, b.name, b.category_name ?? '', b.price ?? '',
        (b.description ?? '').replace(/,/g, ';'),
        b.season ?? '', b.region ?? '', b.perishability_days ?? '', b.valid_until ?? '',
    ]);

    const csvContent = [headers, ...rows].map(row =>
        row.map(cell => {
            const str = String(cell ?? '');
            return str.includes(',') || str.includes('"') || str.includes('\n')
                ? `"${str.replace(/"/g, '""')}"` : str;
        }).join(',')
    ).join('\n');

    // BOM prefix so Excel opens UTF-8 correctly
    downloadBlob(new Blob(['﻿' + csvContent], { type: 'text/csv;charset=utf-8;' }), 'bauturi-export.csv');
}

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

        const sl = { all: 'Tot anul', winter: 'Iarnă', spring: 'Primăvară', summer: 'Vară', autumn: 'Toamnă', necunoscut: 'Necunoscut' };
        lines.push('Secțiune,Categorie,Număr băuturi');
        (summary.by_category ?? []).forEach(r => lines.push(row(['Per categorie', r.category, r.count])));
        lines.push('');
        lines.push('Secțiune,Sezon,Număr băuturi');
        (summary.by_season ?? []).forEach(r => lines.push(row(['Per sezon', sl[r.season] ?? r.season, r.count])));
        lines.push('');
        lines.push('Secțiune,Regiune,Număr băuturi');
        (summary.by_region ?? []).forEach(r => lines.push(row(['Per regiune', r.region, r.count])));
        lines.push('');
        lines.push('Secțiune,Alergen,Număr băuturi afectate');
        (summary.by_allergen ?? []).forEach(r => lines.push(row(['Alergeni', r.allergen, r.count])));
        lines.push('');
        const ps = summary.price_stats ?? {};
        lines.push('Secțiune,Indicator,Valoare (lei)');
        if (ps.avg_price) {
            lines.push(row(['Prețuri', 'Minim', ps.min_price]));
            lines.push(row(['Prețuri', 'Mediu', ps.avg_price]));
            lines.push(row(['Prețuri', 'Maxim', ps.max_price]));
        }
        lines.push('');
        lines.push('Secțiune,Loc,Băutură,Rating mediu,Evaluări,Preț (lei)');
        (topData.top_rated ?? []).forEach((item, i) => lines.push(
            row(['Top evaluat', i + 1, item.name, item.avg_rating, item.rating_count, item.price ?? ''])
        ));

        downloadBlob(new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' }), 'statistici-export.csv');
    } catch (err) {
        alert('Eroare la exportul statisticilor: ' + err.message);
    }
}

function exportJSON() {
    window.open('/beverages-app/api/export.php', '_blank');
}

function downloadSVG(containerId, filename) {
    const svg = document.querySelector(`#${containerId} svg`);
    if (!svg) { alert('Graficul nu este disponibil.'); return; }
    const svgStr = new XMLSerializer().serializeToString(svg);
    downloadBlob(new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' }), filename);
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a   = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}
