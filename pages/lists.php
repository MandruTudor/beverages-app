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
    <title>Liste de cumpărături — Beverages App</title>
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
            <li><a href="/beverages-app/pages/lists.php" class="active">Liste</a></li>
            <li><a href="/beverages-app/pages/groups.php">Grupuri</a></li>
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
            <h2>Liste de cumpărături</h2>
            <p class="page-subtitle">Crează și gestionează listele tale de băuturi.</p>
        </div>
    </div>

    <section class="page-section">
        <h3>Listă nouă</h3>
        <form id="create-form" class="form-stack" novalidate>
            <div class="form-group">
                <label for="list-name">Nume</label>
                <input
                    type="text"
                    id="list-name"
                    class="form-control"
                    placeholder="ex: Cumpărături weekend"
                    required
                    autocomplete="off"
                >
                <span id="create-error" class="field-error" aria-live="polite"></span>
            </div>
            <div class="form-group">
                <label for="list-group-id">Grup (opțional)</label>
                <select id="list-group-id" class="form-control">
                    <option value="">— Personală —</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-auto">
                <span class="spinner"></span>
                <span class="btn-text">Creează</span>
            </button>
        </form>
    </section>

    <div class="lists-layout">

        <aside>
            <h3 class="dash-section-title">Listele mele</h3>
            <div id="lists-sidebar" class="lists-sidebar"></div>
        </aside>

        <section id="list-detail-panel">

            <div id="no-list-selected" class="list-empty-state">
                <p>🛒 Selectează o listă din stânga sau creează una nouă.</p>
            </div>

            <div id="list-detail" class="list-detail" style="display:none">

                <div class="list-detail__header">
                    <div>
                        <div class="list-detail__title" id="detail-title"></div>
                        <div class="list-detail__meta" id="detail-meta"></div>
                    </div>
                    <div class="list-detail__actions">
                        <button id="btn-delete-list" class="btn btn-danger btn-sm">🗑 Șterge lista</button>
                    </div>
                </div>

                <div id="list-feedback" class="list-feedback" role="alert"></div>

                <div class="list-add-bar">
                    <div style="position:relative;flex:1;min-width:180px">
                        <input
                            type="text"
                            id="add-beverage-search"
                            class="form-control"
                            placeholder="🔍 Caută băutură..."
                            autocomplete="off"
                            aria-label="Caută băutură"
                            aria-autocomplete="list"
                        >
                        <input type="hidden" id="add-beverage-id">
                        <ul id="beverage-results" class="beverage-search-results" role="listbox"></ul>
                    </div>
                    <input
                        type="number"
                        id="add-quantity"
                        value="1"
                        min="1"
                        max="99"
                        aria-label="Cantitate"
                    >
                    <button id="btn-add-item" class="btn btn-primary btn-sm">+ Adaugă</button>
                </div>

                <div id="suggestions-bar" style="margin:.75rem 0;display:none">
                    <div style="font-size:.82rem;color:var(--clr-muted);margin-bottom:.4rem">
                        ⭐ Sugerate pentru tine:
                    </div>
                    <div id="suggestions-list" style="display:flex;flex-wrap:wrap;gap:.4rem"></div>
                </div>

                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem">
                    <label for="sort-items" style="font-size:.85rem;color:var(--clr-muted);white-space:nowrap">Sortare:</label>
                    <select id="sort-items" class="filter-select" style="width:auto;padding:.35rem .7rem;font-size:.85rem">
                        <option value="default">Ordinea adăugării</option>
                        <option value="name">Alfabetic (A→Z)</option>
                        <option value="name-desc">Alfabetic (Z→A)</option>
                        <option value="price">Preț crescător</option>
                        <option value="price-desc">Preț descrescător</option>
                        <option value="unchecked">Nebifate primele</option>
                        <option value="checked">Bifate primele</option>
                    </select>
                </div>

                <table class="list-items-table">
                    <thead>
                        <tr>
                            <th>Băutură</th>
                            <th>Cant.</th>
                            <th>Preț total</th>
                            <th style="text-align:center">Bifat</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody"></tbody>
                </table>

            </div>
        </section>

    </div>

</main>

<script src="/beverages-app/public/js/lists.js"></script>
</body>
</html>
