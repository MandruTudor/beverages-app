<?php
// ============================================================
// includes/auth.php
// Funcții helper pentru autentificare și autorizare.
// Toate se bazează pe sesiunile native PHP ($_SESSION).
// ============================================================

// ------------------------------------------------------------
// session_init()
// Pornește sesiunea PHP dacă nu este deja pornită.
// Trebuie apelată înainte de orice citire/scriere în $_SESSION.
// ------------------------------------------------------------
function session_init(): void {
    // session_status() returnează una din trei constante:
    //   PHP_SESSION_DISABLED — sesiunile sunt dezactivate în php.ini
    //   PHP_SESSION_NONE     — sesiunile sunt activate dar nicio sesiune nu rulează
    //   PHP_SESSION_ACTIVE   — o sesiune este deja activă
    // Pornim sesiunea doar dacă nu există deja una activă.
    if (session_status() === PHP_SESSION_NONE) {
        // session_start() trimite header-ul Set-Cookie cu PHPSESSID
        // și populează $_SESSION cu datele salvate pe server.
        session_start();
    }
}

// ------------------------------------------------------------
// is_logged_in()
// Returnează true dacă există un utilizator autentificat.
// Verificăm simplu dacă cheia 'user_id' există în sesiune —
// ea este pusă acolo de login_user() la autentificare reușită.
// ------------------------------------------------------------
function is_logged_in(): bool {
    // Ne asigurăm că sesiunea e pornită înainte să citim din ea.
    session_init();

    // isset() returnează true dacă cheia există ȘI nu e null.
    return isset($_SESSION['user_id']);
}

// ------------------------------------------------------------
// current_user()
// Returnează datele utilizatorului curent din sesiune, sau null
// dacă nimeni nu este autentificat.
// Tipul '?array' înseamnă "array sau null" (nullable).
// ------------------------------------------------------------
function current_user(): ?array {
    // Ne asigurăm că sesiunea e pornită.
    session_init();

    // Dacă nu există user_id în sesiune, nu e nimeni logat.
    if (!isset($_SESSION['user_id'])) {
        return null; // null înseamnă "niciun utilizator"
    }

    // Construim și returnăm un array cu datele din sesiune.
    // Nu returnăm tot $_SESSION, ci doar câmpurile relevante,
    // pentru a controla exact ce date expunem în restul aplicației.
    return [
        'id'       => $_SESSION['user_id'],   // ID-ul numeric din baza de date
        'username' => $_SESSION['username'],  // Numele de utilizator
        'email'    => $_SESSION['email'],     // Adresa de email
        'role'     => $_SESSION['role'],      // 'user' sau 'admin'
    ];
}

// ------------------------------------------------------------
// is_admin()
// Returnează true dacă utilizatorul curent are rol de admin.
// ------------------------------------------------------------
function is_admin(): bool {
    // Pornim sesiunea dacă nu e activă.
    session_init();

    // Verificăm două condiții simultan cu &&:
    //   1. Cheia 'role' există în sesiune
    //   2. Valoarea ei este exact șirul 'admin'
    // === este comparație strictă (tip + valoare), mai sigură decât ==.
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// ------------------------------------------------------------
// login_user(array $user)
// Salvează datele utilizatorului în sesiune după login reușit.
// Apelată din api/auth.php după verificarea parolei.
// ------------------------------------------------------------
function login_user(array $user): void {
    // Pornim sesiunea dacă nu e activă.
    session_init();

    // session_regenerate_id(true) creează un nou ID de sesiune și
    // șterge datele sesiunii vechi. Previne atacul "session fixation"
    // în care un atacator plantează un ID de sesiune cunoscut de el.
    session_regenerate_id(true);

    // Stocăm datele utilizatorului în $_SESSION.
    // Aceste valori vor fi disponibile în toate paginile până la logout.
    $_SESSION['user_id']  = $user['id'];       // Cheia după care verificăm autentificarea
    $_SESSION['username'] = $user['username']; // Afișat în interfață
    $_SESSION['email']    = $user['email'];    // Util pentru profilul utilizatorului
    $_SESSION['role']     = $user['role'];     // 'user' sau 'admin' — controlează accesul
}

// ------------------------------------------------------------
// logout_user()
// Distruge sesiunea curentă (deconectare).
// ------------------------------------------------------------
function logout_user(): void {
    // Pornim sesiunea dacă nu e activă (ca s-o putem distruge).
    session_init();

    // Golim array-ul $_SESSION — datele nu mai sunt accesibile
    // în cererea curentă, dar fișierul de sesiune de pe server încă există.
    $_SESSION = [];

    // Distrugem complet sesiunea de pe server (șterge fișierul).
    // Dacă nu am face asta, fișierul ar rămâne pe disc până expiră.
    session_destroy();
}

// ------------------------------------------------------------
// require_auth()
// Oprește execuția cu HTTP 401 dacă utilizatorul nu e autentificat.
// Se pune la începutul endpoint-urilor API care necesită login.
// ------------------------------------------------------------
function require_auth(): void {
    // Dacă nu e nimeni logat, trimitem răspuns de eroare și ieșim.
    if (!is_logged_in()) {
        // 401 Unauthorized = "trebuie să te autentifici mai întâi"
        http_response_code(401);

        // Trimitem corpul JSON al erorii.
        echo json_encode(['error' => 'Autentificare necesara.']);

        // exit oprește complet execuția PHP — codul de după nu mai rulează.
        exit;
    }
}

// ------------------------------------------------------------
// require_admin()
// Oprește execuția cu HTTP 403 dacă utilizatorul nu e admin.
// Se pune la endpoint-urile accesibile doar adminilor.
// ------------------------------------------------------------
function require_admin(): void {
    // Mai întâi verificăm că e autentificat (poate returna 401).
    require_auth();

    // Dacă e autentificat dar nu e admin, trimitem 403 Forbidden.
    if (!is_admin()) {
        // 403 Forbidden = "ești autentificat dar nu ai permisiunea"
        http_response_code(403);
        echo json_encode(['error' => 'Acces interzis. Doar adminii pot efectua aceasta actiune.']);
        exit;
    }
}
