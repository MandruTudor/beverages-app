<?php
// ============================================================
// admin.php — Panoul de administrare.
// Accesibil exclusiv utilizatorilor cu rolul 'admin'.
// ============================================================

require_once __DIR__ . '/includes/auth.php';

session_init();

if (!is_logged_in()) {
    header('Location: /beverages-app/login.php');
    exit;
}

// Dacă userul nu e admin, îl trimitem la dashboard cu 403
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
</head>
<body>

<header class="topbar">
    <div class="topbar-brand">🧃 Beverages App</div>
    <nav>
        <ul class="topbar-nav">
            <li><a href="/beverages-app/dashboard.php">Dashboard</a></li>
            <li><a href="/beverages-app/beverages.php">Băuturi</a></li>
            <li><a href="/beverages-app/lists.php">Liste</a></li>
            <li><a href="/beverages-app/groups.php">Grupuri</a></li>
            <li><a href="/beverages-app/stats.php">Statistici</a></li>
            <li><a href="/beverages-app/preferences.php">Preferințe</a></li>
            <li><a href="/beverages-app/admin.php" class="active">Admin</a></li>
        </ul>
    </nav>
    <div class="topbar-user">
        <span class="avatar"><?= htmlspecialchars($initial) ?></span>
        <span><?= htmlspecialchars($user['username']) ?></span>
        <a href="/beverages-app/logout.php">Ieșire</a>
    </div>
</header>

<main class="page-main">

    <!-- ============================================================
         Secțiunea: Gestionare utilizatori
         ============================================================ -->
    <div class="page-header">
        <div>
            <h2>Panou de administrare</h2>
            <p class="page-subtitle">Gestionează utilizatorii și importă date în catalog.</p>
        </div>
    </div>

    <!-- Tabelul cu utilizatorii -->
    <section class="page-section">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1.5px solid var(--clr-border)">
            👥 Utilizatori
        </h3>
        <!-- Feedback acțiuni (succes/eroare) -->
        <div id="users-feedback" class="list-feedback" role="alert"></div>
        <!-- Tabelul este populat de JavaScript -->
        <div id="users-table-wrap"></div>
    </section>

    <!-- ============================================================
         Secțiunea: Import CSV
         ============================================================ -->
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

        <!-- Zona de upload fișier CSV -->
        <div class="import-zone" id="import-zone">
            <input type="file" id="csv-file" accept=".csv" style="display:none">
            <!-- Click pe zonă → deschide selectorul de fișiere -->
            <div class="import-drop" id="import-drop" tabindex="0" role="button"
                 aria-label="Selectează fișier CSV">
                <span style="font-size:2rem">📄</span>
                <p>Trage fișierul CSV aici sau <strong>click pentru a selecta</strong></p>
                <p style="color:var(--clr-muted);font-size:.82rem">Doar fișiere .csv</p>
            </div>
            <!-- Previzualizarea fișierului selectat -->
            <div id="import-preview" style="display:none"></div>
        </div>

        <!-- Feedback import -->
        <div id="import-feedback" class="list-feedback" style="margin-top:.75rem" role="alert"></div>
    </section>

    <!-- ============================================================
         Secțiunea: Import JSON
         ============================================================ -->
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

        <!-- Zona de upload fișier JSON -->
        <div class="import-zone" id="import-json-zone">
            <input type="file" id="json-file" accept=".json" style="display:none">
            <div class="import-drop" id="import-json-drop" tabindex="0" role="button"
                 aria-label="Selectează fișier JSON">
                <span style="font-size:2rem">{ }</span>
                <p>Trage fișierul JSON aici sau <strong>click pentru a selecta</strong></p>
                <p style="color:var(--clr-muted);font-size:.82rem">Doar fișiere .json</p>
            </div>
            <!-- Previzualizarea primelor obiecte din JSON -->
            <div id="import-json-preview" style="display:none"></div>
        </div>

        <!-- Feedback import JSON -->
        <div id="import-json-feedback" class="list-feedback" style="margin-top:.75rem" role="alert"></div>
    </section>

    <!-- ============================================================
         Secțiunea: Open Food Facts
         ============================================================ -->
    <section class="page-section">
        <h3 style="font-size:1rem;font-weight:700;margin-bottom:1rem;padding-bottom:.5rem;border-bottom:1.5px solid var(--clr-border)">
            🌍 Import din Open Food Facts
        </h3>
        <p style="color:var(--clr-muted);font-size:.88rem;margin-bottom:1rem">
            Caută băuturi în baza de date publică
            <a href="https://world.openfoodfacts.org" target="_blank" rel="noopener">Open Food Facts</a>
            și importă-le direct în catalog. Categoria și alergenii sunt detectați automat.
        </p>

        <!-- Căutare după cuvânt cheie sau cod de bare -->
        <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
            <input type="text" id="off-search-input" class="form-input"
                   placeholder="Caută după nume (ex: ceai, limonadă)…"
                   style="flex:1;min-width:200px" maxlength="100">
            <button class="btn btn-primary btn-auto" id="off-search-btn">
                <span class="spinner"></span>
                <span class="btn-text">Caută</span>
            </button>
        </div>
        <div style="display:flex;gap:.5rem;margin-bottom:1rem;flex-wrap:wrap">
            <input type="text" id="off-barcode-input" class="form-input"
                   placeholder="Sau introdu cod de bare (ex: 3017620422003)"
                   style="flex:1;min-width:200px" maxlength="14"
                   pattern="\d{8,14}" inputmode="numeric">
            <button class="btn btn-outline btn-auto" id="off-barcode-btn">
                <span class="spinner"></span>
                <span class="btn-text">Caută cod</span>
            </button>
        </div>

        <!-- Feedback și rezultate -->
        <div id="off-feedback" class="list-feedback" role="alert"></div>
        <div id="off-results" style="margin-top:1rem"></div>
    </section>

</main>

<script src="/beverages-app/public/js/admin.js"></script>
</body>
</html>
