<?php
// ============================================================
// lists.php — Gestionarea listelor de cumpărături.
// Utilizatorii pot crea, vizualiza și gestiona liste personale.
// ============================================================

require_once __DIR__ . '/includes/auth.php';

// Pornim sesiunea PHP
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
    <title>Liste de cumpărături — Beverages App</title>
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
            <li><a href="/beverages-app/lists.php" class="active">Liste</a></li>
            <li><a href="/beverages-app/groups.php">Grupuri</a></li>
            <li><a href="/beverages-app/stats.php">Statistici</a></li>
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

    <!-- Titlul paginii + formular de creare listă -->
    <div class="page-header">
        <div>
            <h2>Liste de cumpărături</h2>
            <p class="page-subtitle">Crează și gestionează listele tale de băuturi.</p>
        </div>
    </div>

    <!-- Formular creare listă nouă -->
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

    <!-- Layout principal: sidebar cu liste + panou detalii -->
    <div class="lists-layout">

        <!-- Coloana stângă: cardurile listelor utilizatorului -->
        <aside>
            <h3 class="dash-section-title">Listele mele</h3>
            <!-- Populat de JavaScript după loadLists() -->
            <div id="lists-sidebar" class="lists-sidebar"></div>
        </aside>

        <!-- Coloana dreaptă: detaliile listei selectate -->
        <section id="list-detail-panel">

            <!-- Starea inițială: nicio listă selectată -->
            <div id="no-list-selected" class="list-empty-state">
                <p>🛒 Selectează o listă din stânga sau creează una nouă.</p>
            </div>

            <!-- Panoul de detalii — ascuns până la selectarea unei liste -->
            <div id="list-detail" class="list-detail" style="display:none">

                <!-- Header-ul panoului -->
                <div class="list-detail__header">
                    <div>
                        <!-- Titlul listei selectate — completat de JS -->
                        <div class="list-detail__title" id="detail-title"></div>
                        <!-- Numărul de iteme — completat de JS -->
                        <div class="list-detail__meta" id="detail-meta"></div>
                    </div>
                    <!-- Butoanele de acțiune ale listei -->
                    <div class="list-detail__actions">
                        <button id="btn-delete-list" class="btn btn-danger btn-sm">🗑 Șterge lista</button>
                    </div>
                </div>

                <!-- Feedback mesaj (succes/eroare) -->
                <div id="list-feedback" class="list-feedback" role="alert"></div>

                <!-- Bara de adăugare a unui item nou din catalog -->
                <div class="list-add-bar">
                    <!-- Dropdown cu toate băuturile din catalog — populat de JS -->
                    <select id="add-beverage-select" aria-label="Selectează băutură">
                        <option value="">— Alege băutură —</option>
                    </select>
                    <!-- Câmpul pentru cantitate -->
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

                <!-- Sugestii bazate pe preferinte -->
                <div id="suggestions-bar" style="margin:.75rem 0;display:none">
                    <div style="font-size:.82rem;color:var(--clr-muted);margin-bottom:.4rem">
                        ⭐ Sugerate pentru tine:
                    </div>
                    <div id="suggestions-list" style="display:flex;flex-wrap:wrap;gap:.4rem"></div>
                </div>

                <!-- Tabelul cu itemele listei -->
                <table class="list-items-table">
                    <thead>
                        <tr>
                            <th>Băutură</th>
                            <th>Cant.</th>
                            <th>Preț</th>
                            <th style="text-align:center">Bifat</th>
                            <th></th><!-- Coloana pentru butoanele de acțiune -->
                        </tr>
                    </thead>
                    <!-- Corpul tabelului — populat de JS -->
                    <tbody id="items-tbody"></tbody>
                </table>

            </div>
        </section>

    </div>

</main>

<script src="/beverages-app/public/js/lists.js"></script>
</body>
</html>
