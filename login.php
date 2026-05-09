<?php
// ============================================================
// login.php — Pagina de autentificare.
// ============================================================

// Includem funcțiile de sesiune și autentificare.
require_once __DIR__ . '/includes/auth.php';

// Pornim sesiunea PHP.
session_init();

// Dacă utilizatorul e deja logat, nu are sens să vadă pagina de login.
// Îl trimitem direct la dashboard.
if (is_logged_in()) {
    header('Location: /beverages-app/dashboard.php');
    exit; // Oprim execuția după redirect
}
// Dacă ajungem aici, userul NU este logat → afișăm formularul.
?>
<!DOCTYPE html>
<!-- Declarăm că documentul este HTML5 -->
<html lang="ro">
<!-- lang="ro" ajută screen reader-ele și motoarele de căutare -->
<head>
    <!-- Setăm encoding-ul la UTF-8 pentru caracterele românești -->
    <meta charset="UTF-8">
    <!-- Viewport responsive: lățimea paginii = lățimea ecranului, zoom inițial 1 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Titlul afișat în tab-ul browserului -->
    <title>Autentificare — Beverages App</title>
    <!-- Includem foaia de stiluri CSS principală -->
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
</head>
<body>

<!-- auth-wrapper: container grid cu două coloane (brand | formular) -->
<div class="auth-wrapper">

    <!-- ======================================================
         Panoul stâng: brandul aplicației
         Vizibil pe desktop, ascuns pe mobil (vezi CSS media query)
    ====================================================== -->
    <div class="auth-brand">
        <!-- Iconița principală (emoji folosit ca logo simplu) -->
        <div class="brand-icon">🧃</div>
        <!-- Numele aplicației -->
        <h1>Beverages App</h1>
        <!-- Tagline scurt sub titlu -->
        <p>Gestionează preferințele tale de băuturi non-alcoolice.</p>
        <!-- Lista cu funcționalitățile principale ale aplicației -->
        <ul class="brand-features">
            <li>Catalogul complet de băuturi</li>
            <li>Liste de cumpărături</li>
            <li>Rating-uri și preferințe</li>
            <li>Statistici și exporturi</li>
            <li>Grupuri și liste partajate</li>
        </ul>
    </div>

    <!-- ======================================================
         Panoul drept: formularul de login
    ====================================================== -->
    <div class="auth-form-panel">
        <!-- auth-card: container centrat cu lățime maximă fixă -->
        <div class="auth-card">
            <!-- Titlul formularului -->
            <h2>Bun revenit!</h2>
            <!-- Subtitlu descriptiv -->
            <p class="subtitle">Autentifică-te pentru a continua.</p>

            <!-- Zona de alertă: ascunsă inițial, afișată de JavaScript
                 când apare o eroare sau succes.
                 role="alert" îl face accesibil pentru screen reader-e. -->
            <div id="alert" class="alert alert-error" role="alert"></div>

            <!-- Formularul de login.
                 novalidate: dezactivăm validarea nativă a browserului
                 (vrem să o facem noi în JavaScript pentru control total). -->
            <form id="login-form" novalidate>

                <!-- Grup câmp email -->
                <div class="form-group">
                    <!-- label for="email" asociază eticheta cu input-ul prin id -->
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="exemplu@email.com"
                        autocomplete="email"
                        required
                    >
                    <!-- Span pentru mesajul de eroare al acestui câmp.
                         Afișat/ascuns de JavaScript. aria-live anunță
                         modificările screen reader-elor în timp real. -->
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <!-- Grup câmp parolă -->
                <div class="form-group">
                    <label for="password">Parolă</label>
                    <!-- password-wrapper: container relativ pentru butonul de toggle -->
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Parola ta"
                            autocomplete="current-password"
                            required
                        >
                        <!-- Buton pentru a comuta între tip="password" și tip="text".
                             type="button" previne submiterea accidentală a formularului. -->
                        <button type="button" class="toggle-pw" aria-label="Arată/ascunde parola">👁</button>
                    </div>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <!-- Butonul de submit.
                     JavaScript adaugă clasa 'loading' în timpul cererii API,
                     care afișează spinner-ul și ascunde textul. -->
                <button type="submit" class="btn btn-primary">
                    <!-- Spinner CSS animat — vizibil doar când btn are clasa 'loading' -->
                    <span class="spinner"></span>
                    <!-- Textul normal al butonului — ascuns în timpul loading -->
                    <span class="btn-text">Autentificare</span>
                </button>
            </form>

            <!-- Separator vizual între formular și link-ul de înregistrare -->
            <div class="auth-divider">sau</div>

            <!-- Link spre pagina de înregistrare -->
            <div class="auth-footer">
                Nu ai cont?
                <a href="/beverages-app/register.php">Înregistrează-te</a>
            </div>

            <!-- Hint pentru contul de admin seeden în baza de date -->
            <div class="auth-footer" style="margin-top:.5rem;font-size:.8rem;color:var(--clr-muted)">
                Admin implicit: <code>admin@beverages.local</code> / <code>password</code>
            </div>
        </div>
    </div>

</div>
<!-- Includem fișierul JavaScript cu logica formularelor de autentificare -->
<script src="/beverages-app/public/js/auth.js"></script>
</body>
</html>
