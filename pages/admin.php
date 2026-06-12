<?php
require_once __DIR__ . '/../includes/auth.php';

session_init();

if (!is_logged_in()) {
    header('Location: /beverages-app/pages/login.php');
    exit;
}

require_admin();

$user    = current_user();
$initial = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Beverages App</title>
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
    <link rel="alternate" type="application/rss+xml" title="Top Băuturi — Beverages App" href="/beverages-app/api/rss.php">
</head>
<body>

<header class="topbar">
    <div class="topbar-brand">🧃 Beverages App</div>
    <nav>
        <ul class="topbar-nav">
            <li><a href="/beverages-app/pages/dashboard.php">Dashboard</a></li>
            <li><a href="/beverages-app/pages/beverages.php">Băuturi</a></li>
            <li><a href="/beverages-app/pages/lists.php">Liste</a></li>
            <li><a href="/beverages-app/pages/groups.php">Grupuri</a></li>
            <li><a href="/beverages-app/pages/stats.php">Statistici</a></li>
            <li><a href="/beverages-app/pages/preferences.php">Preferințe</a></li>
            <li><a href="/beverages-app/pages/admin.php" class="active">Admin</a></li>
        </ul>
    </nav>
    <div class="topbar-user">
        <span class="avatar"><?= htmlspecialchars($initial) ?></span>
        <span><?= htmlspecialchars($user['username']) ?></span>
        <a href="/beverages-app/pages/logout.php">Ieșire</a>
    </div>
</header>

<main class="page-main">

    <div class="page-header">
        <div>
            <h2>Panou de administrare</h2>
            <p class="page-subtitle">Gestionează utilizatorii și importă date în catalog.</p>
        </div>
    </div>

    <section class="page-section">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1.5px solid var(--clr-border)">
            👥 Utilizatori
        </h3>
        <div id="users-feedback" class="list-feedback" role="alert"></div>
        <div id="users-table-wrap"></div>
    </section>

    <section class="page-section">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1.5px solid var(--clr-border)">
            📥 Import băuturi din CSV
        </h3>
        <p style="color:var(--clr-muted);font-size:.88rem;margin-bottom:1rem">
            Fișierul CSV trebuie să aibă antetele:
            <code>name, category_id, price, description, season, region, perishability_days, allergens, dietary_regimes</code>.
            Coloanele <code>allergens</code> și <code>dietary_regimes</code> sunt opționale și acceptă ID-uri separate prin <code>|</code>
            (ex: <code>1|3</code>).
        </p>
        <div class="import-zone" id="import-zone">
            <input type="file" id="csv-file" accept=".csv" style="display:none">
            <!-- click pe zonă deschide selectorul de fișiere, gestionat în JS -->
            <div class="import-drop" id="import-drop" tabindex="0" role="button"
                 aria-label="Selectează fișier CSV">
                <span style="font-size:2rem">📄</span>
                <p>Trage fișierul CSV aici sau <strong>click pentru a selecta</strong></p>
                <p style="color:var(--clr-muted);font-size:.82rem">Doar fișiere .csv</p>
            </div>
            <div id="import-preview" style="display:none"></div>
        </div>
        <div id="import-feedback" class="list-feedback" style="margin-top:.75rem" role="alert"></div>
    </section>

    <section class="page-section">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1.5px solid var(--clr-border)">
            📋 Import băuturi din JSON
        </h3>
        <p style="color:var(--clr-muted);font-size:.88rem;margin-bottom:1rem">
            Fișierul JSON trebuie să fie un <strong>array de obiecte</strong> cu câmpurile:
            <code>name</code> (obligatoriu),
            <code>category_id, price, description, season, region, perishability_days, valid_until</code> (opționale).
            Opțional: <code>allergens</code> (array de <code>{"id": N}</code>),
            <code>dietary_regimes</code> (array de <code>{"id": N}</code>) și
            <code>ingredients</code> (array de <code>{"id": N, "quantity": "..."}</code>).
        </p>
        <div class="import-zone" id="import-json-zone">
            <input type="file" id="json-file" accept=".json" style="display:none">
            <div class="import-json-drop" id="import-json-drop" tabindex="0" role="button"
                 aria-label="Selectează fișier JSON">
                <span style="font-size:2rem">{ }</span>
                <p>Trage fișierul JSON aici sau <strong>click pentru a selecta</strong></p>
                <p style="color:var(--clr-muted);font-size:.82rem">Doar fișiere .json</p>
            </div>
            <div id="import-json-preview" style="display:none"></div>
        </div>
        <div id="import-json-feedback" class="list-feedback" style="margin-top:.75rem" role="alert"></div>
    </section>

</main>

<script src="/beverages-app/public/js/admin.js"></script>
</body>
</html>
