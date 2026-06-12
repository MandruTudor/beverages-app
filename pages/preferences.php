<?php

require_once __DIR__ . '/../includes/auth.php';

session_init();

if (!is_logged_in()) {
    header('Location: /beverages-app/pages/login.php');
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
    <title>Preferințe — Beverages App</title>
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
            <li><a href="/beverages-app/pages/preferences.php" class="active">Preferințe</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <li><a href="/beverages-app/pages/admin.php">Admin</a></li>
            <?php endif; ?>
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
            <h2>Recenziile mele</h2>
            <p class="page-subtitle">Băuturile evaluate de tine. Deschide orice băutură pentru a modifica sau adăuga o recenzie.</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <button class="btn btn-outline btn-sm pref-filter" data-filter="all">Toate</button>
            <button class="btn btn-outline btn-sm pref-filter active" data-filter="rated">Evaluate</button>
            <button class="btn btn-outline btn-sm pref-filter" data-filter="unrated">Neevaluate</button>
        </div>
    </div>

    <div id="pref-grid" class="pref-grid">
        <p class="loading-text">Se încarcă...</p>
    </div>

</main>

<div id="modal-pref" class="modal-overlay" role="dialog" aria-modal="true">
    <div class="modal-box" style="max-width:440px">
        <button class="modal-close" onclick="closeModal()" aria-label="Închide">✕</button>
        <h3 class="modal-title" id="pref-modal-title">Evaluează băutura</h3>

        <form id="pref-form" novalidate>
            <input type="hidden" id="pref-beverage-id">

            <div class="form-group">
                <label>Rating</label>
                <div class="star-picker" id="star-picker" role="radiogroup" aria-label="Rating 1-5 stele"></div>
                <input type="hidden" id="pref-rating" value="0">
            </div>

            <div class="form-group">
                <label for="pref-notes">Notițe personale</label>
                <textarea id="pref-notes" class="form-control" rows="3"
                          placeholder="Impresii, sugestii, combinații..."></textarea>
            </div>

            <div id="pref-alert" class="alert alert-error" role="alert"></div>

            <div class="form-actions">
                <!-- apare doar dacă există deja un rating salvat -->
                <button type="button" id="btn-delete-pref" class="btn btn-danger btn-sm"
                        style="display:none;margin-right:auto">🗑 Șterge</button>
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal()">Anulează</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <span class="spinner"></span>
                    <span class="btn-text">Salvează</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/beverages-app/public/js/preferences.js"></script>
</body>
</html>
