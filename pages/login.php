<?php
require_once __DIR__ . '/../includes/auth.php';

session_init();

if (is_logged_in()) {
    header('Location: /beverages-app/pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentificare — Beverages App</title>
    <link rel="stylesheet" href="/beverages-app/public/css/main.css">
    <link rel="alternate" type="application/rss+xml" title="Top Băuturi — Beverages App" href="/beverages-app/api/rss.php">
</head>
<body>

<div class="auth-wrapper">

    <div class="auth-brand">
        <div class="brand-icon">🧃</div>
        <h1>Beverages App</h1>
        <p>Gestionează preferințele tale de băuturi non-alcoolice.</p>
        <ul class="brand-features">
            <li>Catalogul complet de băuturi</li>
            <li>Liste de cumpărături</li>
            <li>Rating-uri și preferințe</li>
            <li>Statistici și exporturi</li>
            <li>Grupuri și liste partajate</li>
        </ul>
    </div>

    <div class="auth-form-panel">
        <div class="auth-card">
            <h2>Bun revenit!</h2>
            <p class="subtitle">Autentifică-te pentru a continua.</p>

            <div id="alert" class="alert alert-error" role="alert"></div>

            <form id="login-form" novalidate>

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

                <div class="form-group">
                    <label for="password">Parolă</label>
                    <div class="password-wrapper">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Parola ta"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-pw" aria-label="Arată/ascunde parola">👁</button>
                    </div>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span class="spinner"></span>
                    <span class="btn-text">Autentificare</span>
                </button>
            </form>

            <div class="auth-divider">sau</div>

            <div class="auth-footer">
                Nu ai cont?
                <a href="/beverages-app/pages/register.php">Înregistrează-te</a>
            </div>
        </div>
    </div>

</div>
<script src="/beverages-app/public/js/auth.js"></script>
</body>
</html>
