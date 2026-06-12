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
    <title>Băuturi — Beverages App</title>
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
    <link rel="alternate" type="application/rss+xml" title="Top Băuturi — Beverages App" href="/beverages-app/api/rss.php">
</head>
<body data-role="<?= htmlspecialchars($user['role']) ?>">

<header class="topbar">
    <div class="topbar-brand">🧃 Beverages App</div>
    <nav>
        <ul class="topbar-nav">
            <li><a href="/beverages-app/pages/dashboard.php">Dashboard</a></li>
            <li><a href="/beverages-app/pages/beverages.php" class="active">Băuturi</a></li>
            <li><a href="/beverages-app/pages/lists.php">Liste</a></li>
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
            <h2>Băuturi non-alcoolice</h2>
            <p class="page-subtitle">Catalogul complet de băuturi disponibile.</p>
        </div>
        <?php if ($user['role'] === 'admin'): ?>
        <button class="btn btn-primary btn-auto" id="btn-add">+ Adaugă băutură</button>
        <?php endif; ?>
    </div>

    <div class="filters-bar">
        <input
            type="search"
            id="search-input"
            class="filter-search"
            placeholder="🔍 Caută băuturi..."
            aria-label="Caută băuturi"
        >
        <select id="filter-category" class="filter-select" aria-label="Filtrează după categorie">
            <option value="">Toate categoriile</option>
        </select>
        <select id="filter-season" class="filter-select" aria-label="Filtrează după sezon">
            <option value="">Orice sezon</option>
            <option value="all">Tot anul</option>
            <option value="winter">Iarnă</option>
            <option value="spring">Primăvară</option>
            <option value="summer">Vară</option>
            <option value="autumn">Toamnă</option>
        </select>
        <select id="filter-allergen" class="filter-select" aria-label="Excludeți alergenul">
            <option value="">Fără restricții alergeni</option>
        </select>
        <select id="filter-regime" class="filter-select" aria-label="Filtrați după regim alimentar">
            <option value="">Orice regim</option>
        </select>
        <select id="filter-region" class="filter-select" aria-label="Filtrați după regiune">
            <option value="">Orice regiune</option>
        </select>
    </div>

    <div id="beverages-grid" class="beverages-grid">
        <p class="loading-text">Se încarcă băuturile...</p>
    </div>

    <div id="pagination" class="pagination-bar"></div>

</main>

<div id="modal-detail" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="detail-title-el">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-detail')" aria-label="Închide detalii">✕</button>
        <div id="detail-body" class="modal-body"></div>
    </div>
</div>

<div id="modal-form" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="form-title">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-form')" aria-label="Închide formular">✕</button>
        <h3 id="form-title" class="modal-title">Adaugă băutură</h3>

        <div id="form-alert" class="alert alert-error" role="alert"></div>

        <form id="beverage-form" novalidate>

            <input type="hidden" id="form-id">

            <div class="form-group">
                <label for="form-name">Nume *</label>
                <input type="text" id="form-name" class="form-control" required placeholder="ex: Ceai de mușețel">
                <span class="field-error" aria-live="polite"></span>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="form-category">Categorie</label>
                    <select id="form-category" class="form-control">
                        <option value="">— Selectează —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="form-price">Preț (lei)</label>
                    <input type="number" id="form-price" step="0.01" min="0" placeholder="ex: 9.50" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label for="form-description">Descriere</label>
                <textarea id="form-description" class="form-control" rows="3" placeholder="Descriere scurtă a băuturii..."></textarea>
            </div>

            <div class="form-group">
                <label for="form-image">Imagine (max 2MB)</label>
                <input type="file" id="form-image" accept="image/jpeg,image/png,image/webp,image/gif" class="form-control">
                <div id="image-preview-wrap" class="form-image-preview" style="display:none">
                    <img id="image-preview" src="" alt="Preview imagine">
                    <p id="image-current"></p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="form-season">Sezon</label>
                    <select id="form-season" class="form-control">
                        <option value="">— Selectează —</option>
                        <option value="all">Tot anul</option>
                        <option value="winter">Iarnă</option>
                        <option value="spring">Primăvară</option>
                        <option value="summer">Vară</option>
                        <option value="autumn">Toamnă</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="form-region">Regiune</label>
                    <input type="text" id="form-region" class="form-control" placeholder="ex: România">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="form-perishability">Perisabilitate (zile)</label>
                    <input type="number" id="form-perishability" min="0" class="form-control" placeholder="ex: 7">
                </div>
                <div class="form-group">
                    <label for="form-valid-until">Valabil până la</label>
                    <input type="date" id="form-valid-until" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label>Regimuri alimentare</label>
                <div id="form-regimes" class="regime-checkboxes"></div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modal-form')">Anulează</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <span class="spinner"></span>
                    <span class="btn-text">Salvează</span>
                </button>
            </div>

        </form>
    </div>
</div>

<script src="/beverages-app/public/js/beverages.js"></script>
</body>
</html>
