/* dashboard.js — Recomandări personalizate */

const SEASONS = {
    spring: 'Primăvară', summer: 'Vară',
    autumn: 'Toamnă',   winter: 'Iarnă', all: 'Tot anul',
};

async function loadRecommendations() {
    const grid = document.getElementById('recommendations-grid');

    try {
        const res  = await fetch('/beverages-app/api/preferences.php?action=recommendations&limit=6');
        const data = await res.json();

        if (!res.ok) throw new Error(data.error || 'Eroare server');

        const recs = data.recommendations ?? [];

        if (!recs.length) {
            grid.innerHTML = `
                <p class="loading-text" style="grid-column:1/-1">
                    Evaluează câteva băuturi (4-5 ⭐) pentru a primi recomandări personalizate.
                </p>`;
            return;
        }

        grid.innerHTML = recs.map(b => {
            const price  = b.price ? parseFloat(b.price).toFixed(2) + ' lei' : '';
            const season = SEASONS[b.season] ?? '';
            const meta   = [b.category_name, season, b.region].filter(Boolean).join(' · ');

            return `
            <a href="/beverages-app/beverages.php" class="dash-card" style="text-align:left;gap:.4rem">
                <div class="dash-card__icon">🍵</div>
                <strong class="dash-card__title">${escHtml(b.name)}</strong>
                ${price ? `<span style="font-size:.85rem;color:var(--clr-primary);font-weight:600">${price}</span>` : ''}
                ${meta  ? `<span class="dash-card__desc" style="font-size:.78rem">${escHtml(meta)}</span>` : ''}
                ${b.description ? `<span class="dash-card__desc">${escHtml(b.description)}</span>` : ''}
            </a>`;
        }).join('');

    } catch (err) {
        grid.innerHTML = `<p class="loading-text" style="color:var(--clr-danger);grid-column:1/-1">${err.message}</p>`;
    }
}

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', loadRecommendations);
