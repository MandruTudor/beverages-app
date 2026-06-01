<?php
// ============================================================
// beverages.php — Catalogul de băuturi non-alcoolice.
// Pagina listează băuturile și permite filtrarea și căutarea.
// Adminii pot adăuga, edita și șterge băuturi din această pagină.
// ============================================================

// Includem funcțiile de sesiune și autentificare
require_once __DIR__ . '/includes/auth.php';

// Pornim sesiunea PHP pentru a verifica dacă userul e logat
session_init();

// Protecție server-side: utilizatori neautentificați sunt trimiși la login
if (!is_logged_in()) {
    header('Location: /beverages-app/login.php');
    exit;
}

// Preluăm datele utilizatorului curent din sesiune
$user = current_user();

// Prima literă din username pentru avatar (ex: "Tudor" → "T")
$initial = strtoupper(substr($user['username'], 0, 1));
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <!-- Encoding UTF-8 pentru caractere românești -->
    <meta charset="UTF-8">
    <!-- Viewport responsive: pagina se adaptează la lățimea dispozitivului -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Băuturi — Beverages App</title>
    <!-- Foaia de stiluri principală -->
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
</head>
<!--
    data-role transmite rolul utilizatorului spre JavaScript fără un request API suplimentar.
    JS citește document.body.dataset.role pentru a decide ce butoane să afișeze.
-->
<body data-role="<?= htmlspecialchars($user['role']) ?>">

<!-- ============================================================
     Bara de navigare fixă (topbar)
     Identică pe toate paginile post-login.
     ============================================================ -->
<header class="topbar">

    <!-- Logo și numele aplicației -->
    <div class="topbar-brand">🧃 Beverages App</div>

    <!-- Meniu de navigare principal -->
    <nav>
        <ul class="topbar-nav">
            <li><a href="/beverages-app/dashboard.php">Dashboard</a></li>
            <!-- clasa 'active' marchează pagina curentă -->
            <li><a href="/beverages-app/beverages.php" class="active">Băuturi</a></li>
            <li><a href="/beverages-app/lists.php">Liste</a></li>
            <li><a href="#">Statistici</a></li>
            <?php if ($user['role'] === 'admin'): ?>
            <!-- Vizibil doar pentru administratori -->
            <li><a href="#">Admin</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Zona utilizator: avatar + username + buton deconectare -->
    <div class="topbar-user">
        <!-- Avatar circular cu inițiala utilizatorului -->
        <span class="avatar"><?= htmlspecialchars($initial) ?></span>
        <!-- Username sanitizat cu htmlspecialchars pentru prevenirea XSS -->
        <span><?= htmlspecialchars($user['username']) ?></span>
        <a href="/beverages-app/logout.php">Ieșire</a>
    </div>
</header>

<!-- ============================================================
     Conținutul principal al paginii
     ============================================================ -->
<main class="page-main">

    <!-- Titlul paginii + buton adăugare (vizibil doar pentru admin) -->
    <div class="page-header">
        <div>
            <h2>Băuturi non-alcoolice</h2>
            <p class="page-subtitle">Catalogul complet de băuturi disponibile.</p>
        </div>
        <?php if ($user['role'] === 'admin'): ?>
        <!--
            Butonul de adăugare e randat de PHP doar pentru admin.
            JavaScript primește semnalul și prin data-role, dar dubla verificare
            côté server previne afișarea accidentală dacă JS e dezactivat.
        -->
        <button class="btn btn-primary btn-auto" id="btn-add">+ Adaugă băutură</button>
        <?php endif; ?>
    </div>

    <!-- Bara de filtrare și căutare
         JavaScript ascultă schimbările și reîncarcă băuturile automat -->
    <div class="filters-bar">
        <!-- Câmp de căutare text cu debounce în JS (300ms) -->
        <input
            type="search"
            id="search-input"
            class="filter-search"
            placeholder="🔍 Caută băuturi..."
            aria-label="Caută băuturi"
        >
        <!-- Dropdown categorii — opțiunile sunt populate dinamic de JS -->
        <select id="filter-category" class="filter-select" aria-label="Filtrează după categorie">
            <option value="">Toate categoriile</option>
            <!-- opțiunile sunt adăugate de beverages.js după ce încarcă /api/categories.php -->
        </select>
        <!-- Dropdown sezoane — opțiunile sunt statice, definite direct în HTML -->
        <select id="filter-season" class="filter-select" aria-label="Filtrează după sezon">
            <option value="">Orice sezon</option>
            <option value="all">Tot anul</option>
            <option value="winter">Iarnă</option>
            <option value="spring">Primăvară</option>
            <option value="summer">Vară</option>
            <option value="autumn">Toamnă</option>
        </select>
    </div>

    <!-- Grid-ul de carduri de băuturi
         Afișează inițial "Se încarcă..." până când JS populează datele -->
    <div id="beverages-grid" class="beverages-grid">
        <p class="loading-text">Se încarcă băuturile...</p>
    </div>

</main>

<!-- ============================================================
     Modal detalii băutură
     JavaScript adaugă clasa 'open' pentru a-l face vizibil.
     Click pe overlay (fundal întunecat) sau Escape îl închide.
     ============================================================ -->
<div id="modal-detail" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="detail-title-el">
    <div class="modal-box">
        <!-- Buton de închidere în colțul dreapta-sus -->
        <button class="modal-close" onclick="closeModal('modal-detail')" aria-label="Închide detalii">✕</button>
        <!-- Corpul modalului — populat complet de JavaScript după fetch -->
        <div id="detail-body" class="modal-body">
            <!-- JS inserează: titlu, badges, descriere, dl cu detalii,
                 secțiuni ingrediente/alergeni/localuri, butoane admin -->
        </div>
    </div>
</div>

<!-- ============================================================
     Modal formular adăugare/editare (vizibil doar pentru admin)
     ============================================================ -->
<div id="modal-form" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="form-title">
    <div class="modal-box">
        <!-- Buton de închidere -->
        <button class="modal-close" onclick="closeModal('modal-form')" aria-label="Închide formular">✕</button>
        <!-- Titlul modalului — JS îl schimbă între "Adaugă" și "Editează" -->
        <h3 id="form-title" class="modal-title">Adaugă băutură</h3>

        <!-- Alertă de eroare în formular (ascunsă inițial) -->
        <div id="form-alert" class="alert alert-error" role="alert"></div>

        <!-- Formularul de creare/editare
             novalidate dezactivează validarea nativă a browserului -->
        <form id="beverage-form" novalidate>

            <!-- Câmp ascuns cu ID-ul băuturii: gol la adăugare, completat la editare -->
            <input type="hidden" id="form-id">

            <!-- Câmpul Nume — obligatoriu -->
            <div class="form-group">
                <label for="form-name">Nume *</label>
                <input type="text" id="form-name" class="form-control" required placeholder="ex: Ceai de mușețel">
                <span class="field-error" aria-live="polite"></span>
            </div>

            <!-- Rând cu două câmpuri: Categorie și Preț -->
            <div class="form-row">
                <div class="form-group">
                    <label for="form-category">Categorie</label>
                    <!-- Opțiunile sunt populate de JS după loadCategories() -->
                    <select id="form-category" class="form-control">
                        <option value="">— Selectează —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="form-price">Preț (lei)</label>
                    <!-- step="0.01" permite valori cu 2 zecimale (ex: 9.50) -->
                    <input type="number" id="form-price" step="0.01" min="0" placeholder="ex: 9.50" class="form-control">
                </div>
            </div>

            <!-- Descriere — câmp textarea multi-linie -->
            <div class="form-group">
                <label for="form-description">Descriere</label>
                <textarea id="form-description" class="form-control" rows="3" placeholder="Descriere scurtă a băuturii..."></textarea>
            </div>

            <!-- Rând cu Sezon și Regiune -->
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

            <!-- Perisabilitate în zile -->
            <div class="form-group">
                <label for="form-perishability">Perisabilitate (zile)</label>
                <input type="number" id="form-perishability" min="0" class="form-control" placeholder="ex: 7">
            </div>

            <!-- Butoane Anulează / Salvează -->
            <div class="form-actions">
                <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('modal-form')">Anulează</button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <!-- Spinner CSS — vizibil doar când butonul are clasa 'loading' -->
                    <span class="spinner"></span>
                    <span class="btn-text">Salvează</span>
                </button>
            </div>

        </form>
    </div>
</div>

<!-- Scriptul paginii de băuturi — încărcat după HTML pentru a putea accesa DOM-ul -->
<script src="/beverages-app/public/js/beverages.js"></script>
</body>
</html>
