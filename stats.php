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
</head>
<body>

<!-- Bara de navigare fixă -->
<header class="topbar">
    <div class="topbar-brand">🧃 Beverages App</div>
    <nav>
        <ul class="topbar-nav">
            <li><a href="/beverages-app/dashboard.php">Dashboard</a></li>
            <li><a href="/beverages-app/beverages.php">Băuturi</a></li>
            <li><a href="/beverages-app/lists.php">Liste</a></li>
            <!-- clasa 'active' marchează pagina curentă -->
            <li><a href="/beverages-app/stats.php" class="active">Statistici</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><a href="#">Admin</a></li>
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

    <!-- Titlul paginii + butoane de export -->
    <div class="page-header">
        <div>
            <h2>Statistici</h2>
            <p class="page-subtitle">Distribuția și clasamentul băuturilor din catalog.</p>
        </div>
        <!-- Butoane de export — CSV și JSON -->
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <button class="btn btn-outline btn-auto" id="btn-export-csv">⬇ Export CSV</button>
            <button class="btn btn-outline btn-auto" id="btn-export-json">⬇ Export JSON</button>
        </div>
    </div>

    <!-- ============================================================
         Carduri cu totaluri generale
         Populate de JavaScript după apelul API /stats.php
         ============================================================ -->
    <div class="stats-cards" id="stats-cards">
        <!-- Schelet loading — înlocuit de JS după încărcare -->
        <div class="stat-card stat-card--loading"></div>
        <div class="stat-card stat-card--loading"></div>
        <div class="stat-card stat-card--loading"></div>
        <div class="stat-card stat-card--loading"></div>
    </div>

    <!-- ============================================================
         Grafice SVG — două coloane pe desktop
         ============================================================ -->
    <div class="charts-grid">

        <!-- Graficul 1: Băuturi per categorie -->
        <div class="chart-container">
            <div class="chart-header">
                <h3>Băuturi per categorie</h3>
                <!-- Buton download SVG pentru graficul de categorii -->
                <button class="btn btn-outline btn-sm" id="btn-svg-category">⬇ SVG</button>
            </div>
            <!-- SVG-ul este generat și inserat de JavaScript -->
            <div id="chart-category"></div>
        </div>

        <!-- Graficul 2: Distribuție pe sezoane -->
        <div class="chart-container">
            <div class="chart-header">
                <h3>Distribuție pe sezoane</h3>
                <!-- Buton download SVG pentru graficul de sezoane -->
                <button class="btn btn-outline btn-sm" id="btn-svg-season">⬇ SVG</button>
            </div>
            <!-- SVG-ul este generat și inserat de JavaScript -->
            <div id="chart-season"></div>
        </div>

    </div>

    <!-- ============================================================
         Clasamentul celor mai bine evaluate băuturi
         ============================================================ -->
    <section class="page-section">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1.5px solid var(--clr-border)">
            🏆 Clasament băuturi populare
        </h3>
        <!-- Tabelul este populat de JavaScript -->
        <div id="top-rated-container"></div>
    </section>

</main>

<script src="/beverages-app/public/js/stats.js"></script>
</body>
</html>
