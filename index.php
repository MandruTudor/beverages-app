<?php
// ============================================================
// index.php — Punctul de intrare principal al aplicației.
// Vizitat când cineva accesează /beverages-app/ fără o pagină specifică.
// Redirecționează automat spre pagina potrivită.
// ============================================================

// Includem funcțiile de autentificare (is_logged_in, session_init etc.).
require_once __DIR__ . '/includes/auth.php';

// Pornim sesiunea PHP pentru a putea verifica dacă userul e logat.
session_init();

// Dacă există un utilizator autentificat (user_id în sesiune),
// îl trimitem spre dashboard — pagina principală post-login.
if (is_logged_in()) {
    // header('Location: ...') trimite un header HTTP de redirect (302 Found).
    // Browserul va naviga automat la URL-ul specificat.
    header('Location: /beverages-app/pages/dashboard.php');
} else {
    // Dacă nu e nimeni logat, îl trimitem spre pagina de login.
    header('Location: /beverages-app/pages/login.php');
}

// exit oprește execuția PHP după trimiterea header-ului de redirect.
// Fără exit, PHP-ul ar continua să execute cod după header() — risc de output neintenționat.
exit;
