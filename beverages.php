<?php
// ============================================================
// beverages.php — Pagina de management a băuturilor.
// Accesibilă doar utilizatorilor autentificați.
// Adminii pot adăuga, edita și șterge băuturi.
// Utilizatorii obișnuiți pot doar vedea și filtra.
// ============================================================

// Includem funcțiile de sesiune și autentificare.
require_once __DIR__ . '/includes/auth.php';

// Pornim sesiunea PHP — obligatoriu înainte de a citi $_SESSION.
session_init();

// Protecție server-side: redirecționăm la login dacă userul nu e autentificat.
if (!is_logged_in()) {
    header('Location: /beverages-app/login.php');
    exit;
}

// Preluăm datele utilizatorului curent din sesiune.
$user     = current_user();
// Prima literă din username, cu majusculă — pentru avatarul circular.
$initial  = strtoupper(substr($user['username'], 0, 1));
// Stocăm dacă userul e admin — folosit pentru a afișa/ascunde butoanele de editare.
$is_admin = ($user['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Băuturi — Beverages App</title>
    <!-- Foaia de stiluri comună tuturor paginilor aplicației -->
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
</head>
<body>

<!-- ============================================================
     Bara de navigare superioară — identică pe toate paginile post-login
     ============================================================ -->
<header class="topbar">

    <!-- Logo + numele aplicației -->
    <div class="topbar-brand">🧃 Beverages App</div>

    <!-- Meniu principal de navigare -->
    <nav>
        <ul class="topbar-nav">
            <li><a href="/beverages-app/dashboard.php">Dashboard</a></li>
            <!-- clasa 'active' marchează pagina curentă -->
            <li><a href="/beverages-app/beverages.php" class="active">Băuturi</a></li>
            <li><a href="#">Liste</a></li>
            <li><a href="#">Statistici</a></li>
            <?php if ($is_admin): ?>
            <!-- Link-ul Admin e vizibil DOAR pentru admini -->
            <li><a href="#">Admin</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Zona utilizator: avatar + username + logout -->
    <div class="topbar-user">
        <!-- Avatar circular cu inițiala username-ului -->
        <span class="avatar"><?= htmlspecialchars($initial) ?></span>
        <!-- htmlspecialchars() previne XSS dacă username-ul conține < sau > -->
        <span><?= htmlspecialchars($user['username']) ?></span>
        <a href="/beverages-app/logout.php">Ieșire</a>
    </div>
</header>

<!-- ============================================================
     Conținutul principal al paginii
     ============================================================ -->
<main class="page-main">

    <!-- Antetul paginii: titlu + butonul de adăugare (doar pentru admin) -->
    <div class="page-header">
        <div>
            <h2 class="page-title">Băuturi</h2>
            <p class="page-subtitle">Catalogul de băuturi non-alcoolice</p>
        </div>
        <?php if ($is_admin): ?>
        <!-- Butonul de adăugare e generat în PHP, dar activat de JavaScript.
             Apare DOAR dacă userul curent are rolul 'admin'. -->
        <button class="btn btn-primary btn-sm" id="btn-add-beverage">+ Adaugă băutură</button>
        <?php endif; ?>
    </div>

    <!-- Bara de filtrare: categorie, sezon, regiune -->
    <div class="filters-bar">

        <!-- Dropdown categorie — populat dinamic de JS din api/categories.php -->
        <select id="filter-category" class="filter-select">
            <option value="">Toate categoriile</option>
        </select>

        <!-- Dropdown sezon — valori fixe, definite în schema SQL -->
        <select id="filter-season" class="filter-select">
            <option value="">Toate sezoanele</option>
            <option value="spring">Primăvară</option>
            <option value="summer">Vară</option>
            <option value="autumn">Toamnă</option>
            <option value="winter">Iarnă</option>
            <option value="all">Tot anul</option>
        </select>

        <!-- Câmp text pentru filtrare după regiune geografică -->
        <input type="text" id="filter-region" class="filter-input" placeholder="Caută după regiune…">

        <!-- Butonul care aplică filtrele -->
        <button class="btn btn-outline btn-sm" id="btn-filter">Filtrează</button>

        <!-- Butonul care resetează toate filtrele -->
        <button class="btn btn-ghost btn-sm" id="btn-clear-filters">Resetează</button>
    </div>

    <!-- Banner de mesaje (succes sau eroare) — gestionat de JS.
         aria-live="polite" anunță modificările și cititorilor de ecran (accesibilitate). -->
    <div id="page-alert" class="alert" role="alert" aria-live="polite"></div>

    <!-- Grila de carduri cu băuturile — conținut generat complet de JavaScript.
         Afișăm inițial un mesaj de "Se încarcă..." până sosesc datele de la API. -->
    <div id="beverages-grid" class="beverages-grid">
        <div class="loading-state">Se încarcă băuturile…</div>
    </div>

</main>

<!-- ============================================================
     Modalul de adăugare / editare băutură
     Vizibil doar când JS adaugă clasa 'visible' pe overlay.
     Hidden by default prin clasa CSS 'hidden'.
     ============================================================ -->
<div id="modal-overlay" class="modal-overlay hidden"
     role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal">

        <!-- Header modal: titlu + buton de închidere -->
        <div class="modal-header">
            <!-- Titlul se schimbă dinamic: "Adaugă" sau "Editează" -->
            <h3 id="modal-title">Adaugă băutură</h3>
            <!-- &times; = simbolul × (înmulțire) folosit ca buton de închidere (X) -->
            <button class="modal-close" id="btn-close-modal" aria-label="Închide">&times;</button>
        </div>

        <!-- Corpul modalului: formularul de adăugare/editare -->
        <div class="modal-body">
            <form id="beverage-form" novalidate>
                <!-- Câmp ascuns care stochează ID-ul băuturii când edităm.
                     Gol la adăugare, populat de JS la editare. -->
                <input type="hidden" id="form-id">

                <!-- Câmpul Nume — obligatoriu -->
                <div class="form-group">
                    <label for="form-name">Nume *</label>
                    <input type="text" id="form-name" placeholder="ex: Ceai de mentă" required>
                    <!-- Mesajul de eroare apare sub câmp când validarea eșuează -->
                    <span class="field-error" id="err-name"></span>
                </div>

                <!-- Rând cu două coloane: Categorie și Preț -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="form-category">Categorie</label>
                        <!-- Opțiunile se populează dinamic de JS din api/categories.php -->
                        <select id="form-category" class="filter-select" style="width:100%">
                            <option value="">— fără categorie —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="form-price">Preț (lei)</label>
                        <!-- step="0.01" permite valori cu două zecimale (ex: 12.50) -->
                        <input type="number" id="form-price" placeholder="0.00" min="0" step="0.01">
                    </div>
                </div>

                <!-- Descriere — câmp text multi-linie -->
                <div class="form-group">
                    <label for="form-description">Descriere</label>
                    <!-- textarea este o casetă de text cu mai multe rânduri.
                         rows="3" setează înălțimea inițială vizibilă. -->
                    <textarea id="form-description" class="form-textarea"
                              placeholder="Descriere scurtă a băuturii…" rows="3"></textarea>
                </div>

                <!-- Rând cu două coloane: Sezon și Regiune -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="form-season">Sezon</label>
                        <select id="form-season" class="filter-select" style="width:100%">
                            <option value="">— selectează —</option>
                            <option value="all">Tot anul</option>
                            <option value="spring">Primăvară</option>
                            <option value="summer">Vară</option>
                            <option value="autumn">Toamnă</option>
                            <option value="winter">Iarnă</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="form-region">Regiune geografică</label>
                        <input type="text" id="form-region" placeholder="ex: Moldova">
                    </div>
                </div>

                <!-- Perisabilitate: câte zile se poate păstra după deschidere -->
                <div class="form-group">
                    <label for="form-perishability">Perisabilitate (zile)</label>
                    <input type="number" id="form-perishability" placeholder="ex: 7" min="0">
                </div>

            </form>
        </div>

        <!-- Footer modal: butoanele de Anulare și Salvare -->
        <div class="modal-footer">
            <button class="btn btn-ghost btn-sm" id="btn-cancel-modal">Anulează</button>
            <button class="btn btn-primary btn-sm" id="btn-save-modal">
                <!-- Spinner-ul apare în locul textului când cererea e în curs.
                     Gestionat prin clasa CSS 'loading' adăugată de JS pe buton. -->
                <span class="spinner"></span>
                <span class="btn-text">Salvează</span>
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     Transmitem date din PHP către JavaScript în mod sigur.
     json_encode() serializează valorile PHP ca JSON valid.
     window.APP = obiect global accesibil din beverages.js.
     ============================================================ -->
<script>
    window.APP = {
        // Rolul utilizatorului — JS îl folosește pentru a afișa/ascunde butoanele de editare.
        // json_encode(true) → "true" în JavaScript; json_encode(false) → "false".
        isAdmin:  <?= json_encode($is_admin) ?>,
        // Prefixul URL al aplicației — folosit la construirea URL-urilor API.
        basePath: '/beverages-app'
    };
</script>
<!-- Scriptul paginii — încărcat la final pentru a garanta că DOM-ul e complet. -->
<script src="/beverages-app/public/js/beverages.js"></script>

</body>
</html>
