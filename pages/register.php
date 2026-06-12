<?php
// ============================================================
// register.php — Pagina de înregistrare cont nou.
// ============================================================

// Includem funcțiile de sesiune și autentificare.
require_once __DIR__ . '/../includes/auth.php';

// Pornim sesiunea PHP.
session_init();

// Dacă utilizatorul e deja logat, nu are sens să se re-înregistreze.
// Îl trimitem direct la dashboard.
if (is_logged_in()) {
    header('Location: /beverages-app/pages/dashboard.php');
    exit;
}
// Dacă ajungem aici, userul NU este logat → afișăm formularul de înregistrare.
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Înregistrare — Beverages App</title>
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
    <link rel="alternate" type="application/rss+xml" title="Top Băuturi — Beverages App" href="/beverages-app/api/rss.php">
</head>
<body>

<!-- auth-wrapper: aceeași structură cu două coloane ca la login.php -->
<div class="auth-wrapper">

    <!-- Panoul stâng de brand (identic cu login.php) -->
    <div class="auth-brand">
        <div class="brand-icon">🧃</div>
        <h1>Beverages App</h1>
        <p>Creează-ți contul și începe să explorezi lumea băuturilor.</p>
        <ul class="brand-features">
            <li>Catalogul complet de băuturi</li>
            <li>Liste de cumpărături</li>
            <li>Rating-uri și preferințe</li>
            <li>Statistici și exporturi</li>
            <li>Grupuri și liste partajate</li>
        </ul>
    </div>

    <!-- Panoul drept: formularul de înregistrare -->
    <div class="auth-form-panel">
        <div class="auth-card">
            <h2>Creează cont</h2>
            <p class="subtitle">Completează datele de mai jos pentru a te înregistra.</p>

            <!-- Zona de alertă pentru mesaje de eroare sau succes -->
            <div id="alert" class="alert alert-error" role="alert"></div>

            <!-- id="register-form" este folosit de JavaScript ca selector -->
            <form id="register-form" novalidate>

                <!-- Câmpul username -->
                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="numeleTau"
                        autocomplete="username"
                        required
                    >
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <!-- Câmpul email -->
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="exemplu@email.com"
                        autocomplete="email"
                        required
                    >
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <!-- Câmpul parolă cu indicator de putere -->
                <div class="form-group">
                    <label for="password">Parolă</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minim 8 caractere"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="toggle-pw" aria-label="Arată/ascunde parola">👁</button>
                    </div>

                    <!-- Indicatorul de putere al parolei.
                         Ascuns inițial; JavaScript îl afișează când userul scrie.
                         id="pw-strength" este selectat în initPasswordStrength(). -->
                    <div id="pw-strength" class="pw-strength">
                        <!-- Bara grafică: lățimea și culoarea sunt setate de JS -->
                        <div class="pw-strength-bar">
                            <div id="pw-strength-fill" class="pw-strength-fill"></div>
                        </div>
                        <!-- Eticheta textuală: "Slabă", "Medie", "Excelentă" etc. -->
                        <span id="pw-strength-label" class="pw-strength-label"></span>
                    </div>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <!-- Câmpul de confirmare parolă -->
                <div class="form-group">
                    <label for="password2">Confirmă parola</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password2"
                            name="password2"
                            placeholder="Repetă parola"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="toggle-pw" aria-label="Arată/ascunde parola">👁</button>
                    </div>
                    <!-- Eroarea "parolele nu coincid" apare live pe măsură ce userul scrie -->
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <!-- Butonul de submit cu spinner de loading -->
                <button type="submit" class="btn btn-primary">
                    <span class="spinner"></span>
                    <span class="btn-text">Creează cont</span>
                </button>
            </form>

            <div class="auth-divider">sau</div>

            <!-- Link spre pagina de login pentru cei cu cont existent -->
            <div class="auth-footer">
                Ai deja cont?
                <a href="/beverages-app/pages/login.php">Autentifică-te</a>
            </div>
        </div>
    </div>

</div>
<script src="/beverages-app/public/js/auth.js"></script>
</body>
</html>
