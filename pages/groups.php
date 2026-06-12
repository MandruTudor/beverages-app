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
    <title>Grupuri — Beverages App</title>
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
            <li><a href="/beverages-app/pages/groups.php" class="active">Grupuri</a></li>
            <li><a href="/beverages-app/pages/stats.php">Statistici</a></li>
            <li><a href="/beverages-app/pages/preferences.php">Preferințe</a></li>
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
            <h2>Grupuri</h2>
            <p class="page-subtitle">Creează grupuri și gestionează preferințele împreună cu alții.</p>
        </div>
    </div>

    <section class="page-section">
        <h3>Grup nou</h3>
        <form id="create-group-form" class="form-stack" novalidate>
            <div class="form-group">
                <label for="group-name">Nume grup</label>
                <input type="text" id="group-name" class="form-control"
                       placeholder="ex: Familia Popescu" required autocomplete="off">
                <span id="create-group-error" class="field-error" aria-live="polite"></span>
            </div>
            <button type="submit" class="btn btn-primary btn-auto">
                <span class="spinner"></span>
                <span class="btn-text">Creează</span>
            </button>
        </form>
    </section>

    <div class="lists-layout">

        <aside>
            <h3 class="dash-section-title">Grupurile mele</h3>
            <div id="groups-sidebar" class="lists-sidebar">
                <p class="loading-text">Se încarcă...</p>
            </div>
        </aside>

        <section id="group-detail-panel">

            <div id="no-group-selected" class="list-empty-state">
                <p>👥 Selectează un grup din stânga sau creează unul nou.</p>
            </div>

            <div id="group-detail" style="display:none">

                <div class="list-detail__header">
                    <div>
                        <div class="list-detail__title" id="group-detail-name"></div>
                        <div class="list-detail__meta" id="group-detail-meta"></div>
                    </div>
                    <div class="list-detail__actions">
                        <button id="btn-delete-group" class="btn btn-danger btn-sm">🗑 Șterge grupul</button>
                    </div>
                </div>

                <div id="group-feedback" class="list-feedback" role="alert"></div>

                <div class="page-section" style="margin-top:1.5rem">
                    <h4 style="margin-bottom:.75rem">Adaugă un membru</h4>
                    <div class="list-add-bar">
                        <input type="text" id="member-search-input" class="form-control"
                               placeholder="Caută după username..." autocomplete="off">
                        <button id="btn-search-user" class="btn btn-outline btn-sm">Caută</button>
                    </div>
                    <div id="user-search-results" style="margin-top:.5rem"></div>
                </div>

                <div class="page-section" style="margin-top:1rem">
                    <h4 style="margin-bottom:.75rem">Membri</h4>
                    <div id="members-list"></div>
                </div>

                <div class="page-section" style="margin-top:1.5rem">
                    <h4 style="margin-bottom:.75rem">Preferințe de grup</h4>
                    <div class="list-add-bar" style="flex-wrap:wrap;gap:.5rem">
                        <select id="pref-beverage-select" class="form-control" style="flex:1;min-width:160px">
                            <option value="">— Alege băutură —</option>
                        </select>
                        <div id="pref-star-picker" class="star-picker" role="radiogroup" aria-label="Rating 1-5 stele"></div>
                        <input type="hidden" id="pref-rating" value="0">
                        <input type="text" id="pref-notes" class="form-control"
                               placeholder="Notițe (opțional)" style="flex:2;min-width:140px">
                        <button id="btn-save-group-pref" class="btn btn-primary btn-sm">Salvează</button>
                    </div>
                    <div id="group-prefs-list" style="margin-top:.75rem"></div>
                </div>

            </div>
        </section>

    </div>

</main>

<input type="hidden" id="current-user-id" value="<?= (int) $user['id'] ?>">
<script src="/beverages-app/public/js/groups.js"></script>
</body>
</html>
