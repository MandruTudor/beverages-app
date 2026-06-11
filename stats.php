<?php
// ============================================================
// stats.php — Statistici și exporturi.
// Afișează grafice SVG și permite exportul datelor în CSV/SVG.
// ============================================================

require_once __DIR__ . '/includes/auth.php';

session_init();

// Protecție: utilizatori neautentificați → login
if (!is_logged_in()) {
    header('Location: /beverages-app/login.php');
    exit;
}

$user    = current_user();
$initial = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistici — Beverages App</title>
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
    <link rel="alternate" type="application/rss+xml" title="Top Băuturi — Beverages App" href="/beverages-app/api/rss.php">
</head>
<!--
    data-role transmite rolul utilizatorului spre JavaScript.
    Admini văd toate statisticile + export; userii văd doar clasamentul de ratinguri.
-->
<body data-role="<?= htmlspecialchars($user['role']) ?>">

<!-- Bara de navigare fixă -->
<header class="topbar">
    <div class="topbar-brand">🧃 Beverages App</div>
    <nav>
        <ul class="topbar-nav">
            <li><a href="/beverages-app/dashboard.php">Dashboard</a></li>
            <li><a href="/beverages-app/beverages.php">Băuturi</a></li>
            <li><a href="/beverages-app/lists.php">Liste</a></li>
            <li><a href="/beverages-app/groups.php">Grupuri</a></li>
            <li><a href="/beverages-app/stats.php" class="active">Statistici</a></li>
            <li><a href="/beverages-app/preferences.php">Preferințe</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><a href="/beverages-app/admin.php">Admin</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <div class="topbar-user">
        <span class="avatar"><?= htmlspecialchars($initial) ?></span>
        <span><?= htmlspecialchars($user['username']) ?></span>
        <a href="/beverages-app/logout.php">Ieșire</a>
    </div>
</header>

<main class="page-main">

<?php if ($user['role'] === 'admin'): ?>
    <!-- ============================================================
         Secțiuni vizibile DOAR pentru admini
         ============================================================ -->

    <!-- Titlul paginii + butoane de export -->
    <div class="page-header">
        <div>
            <h2>Statistici</h2>
            <p class="page-subtitle">Distribuția și clasamentul băuturilor din catalog.</p>
        </div>
        <!-- Butoane export: CSV pentru toți, JSON doar pentru admini (conține toate datele) -->
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <button class="btn btn-outline btn-auto" id="btn-export-csv">⬇ Băuturi CSV</button>
            <button class="btn btn-outline btn-auto" id="btn-export-json">⬇ Băuturi JSON</button>
            <button class="btn btn-outline btn-auto" id="btn-export-stats-csv">⬇ Statistici CSV</button>
        </div>
    </div>

    <!-- Carduri cu totaluri generale — populate de JS după apelul API -->
    <div class="stats-cards" id="stats-cards">
        <div class="stat-card stat-card--loading"></div>
        <div class="stat-card stat-card--loading"></div>
        <div class="stat-card stat-card--loading"></div>
        <div class="stat-card stat-card--loading"></div>
    </div>

    <!-- Grafice SVG — două coloane pe desktop -->
    <div class="charts-grid">

        <div class="chart-container">
            <div class="chart-header">
                <h3>Băuturi per categorie</h3>
                <button class="btn btn-outline btn-sm" id="btn-svg-category">⬇ SVG</button>
            </div>
            <div id="chart-category"></div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h3>Distribuție pe sezoane</h3>
                <button class="btn btn-outline btn-sm" id="btn-svg-season">⬇ SVG</button>
            </div>
            <div id="chart-season"></div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h3>Băuturi per regiune</h3>
                <button class="btn btn-outline btn-sm" id="btn-svg-region">⬇ SVG</button>
            </div>
            <div id="chart-region"></div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h3>Frecvența alergenilor</h3>
                <button class="btn btn-outline btn-sm" id="btn-svg-allergen">⬇ SVG</button>
            </div>
            <div id="chart-allergen"></div>
        </div>

    </div>

    <!-- Statistici de preț -->
    <section class="page-section">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1.5px solid var(--clr-border)">
            💰 Statistici de preț
        </h3>
        <div id="price-stats-container"></div>
    </section>

<?php else: ?>
    <!-- Titlu simplificat pentru useri — fără export -->
    <div class="page-header">
        <div>
            <h2>Statistici</h2>
            <p class="page-subtitle">Clasamentul băuturilor evaluate de utilizatori.</p>
        </div>
    </div>
<?php endif; ?>

    <!-- ============================================================
         Clasamentul celor mai bine evaluate băuturi
         Vizibil pentru TOȚI utilizatorii autentificați
         ============================================================ -->
    <section class="page-section">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1.5px solid var(--clr-border)">
            <h3 style="font-size:1rem;font-weight:700;margin:0">🏆 Clasament băuturi populare</h3>
            <a href="/beverages-app/api/rss.php" target="_blank" rel="noopener"
               class="btn btn-outline btn-sm"
               title="Abonează-te la clasamentul băuturilor populare via RSS"
               style="display:flex;align-items:center;gap:.35rem;white-space:nowrap">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                    <circle cx="6" cy="18" r="2.5"/><path d="M4 4a16 16 0 0 1 16 16h-3A13 13 0 0 0 4 7V4z"/><path d="M4 11a9 9 0 0 1 9 9H10a6 6 0 0 0-6-6v-3z"/>
                </svg>
                RSS
            </a>
        </div>
        <div id="top-rated-container"></div>
    </section>

</main>

<script src="/beverages-app/public/js/stats.js"></script>
</body>
</html>
