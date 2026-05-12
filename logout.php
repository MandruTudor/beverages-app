<?php
// ============================================================
// logout.php — Deconectare simplă.
// Accesat prin link-ul "Ieșire" din navbar sau direct ca URL.
// Nu are interfață vizuală — face redirect imediat după logout.
// ============================================================

// Includem funcțiile de autentificare.
require_once __DIR__ . '/includes/auth.php';

// logout_user() face trei lucruri:
//   1. session_init() — pornește sesiunea dacă nu e activă (ca s-o poată distruge)
//   2. $_SESSION = [] — golește toate datele din sesiunea curentă
//   3. session_destroy() — șterge fișierul de sesiune de pe server
logout_user();

// Redirecționăm spre pagina de login.
// Utilizatorul nu mai are sesiune activă, deci login.php va afișa formularul.
header('Location: /beverages-app/login.php');

// Oprim execuția după redirect.
exit;
