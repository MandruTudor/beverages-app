<?php
// ============================================================
// api/auth.php
// Endpoint HTTP pentru toate operațiunile de autentificare.
// URL-ul este: /beverages-app/api/auth.php?action=<actiune>
//
// Rute disponibile:
//   POST ?action=register  → înregistrare utilizator nou
//   POST ?action=login     → autentificare cu email + parolă
//   POST ?action=logout    → deconectare (distruge sesiunea)
//   GET  ?action=me        → returnează datele utilizatorului curent
// ============================================================

// Includem conexiunea la baza de date (funcția get_db()).
require_once __DIR__ . '/../includes/db.php';

// Includem funcțiile de sesiune (is_logged_in, login_user etc.).
require_once __DIR__ . '/../includes/auth.php';

// Includem funcțiile helper (json_response, json_error etc.).
require_once __DIR__ . '/../includes/helpers.php';

// Setăm headerul implicit pentru toate răspunsurile acestui fișier.
// Orice echo din acest fișier va fi interpretat ca JSON de client.
header('Content-Type: application/json; charset=utf-8');

// Citim parametrul 'action' din URL (?action=login etc.).
// get_param() returnează null dacă parametrul lipsește, deci ?? '' dă șir gol.
$action = get_param('action') ?? '';

// match este similar cu switch, dar:
//   - compară cu === (strict)
//   - fiecare ramură trebuie să aibă o expresie (nu instrucțiuni multiple)
//   - aruncă UnhandledMatchError dacă nicio ramură nu potrivește (dacă nu e 'default')
// Apelăm funcția corespunzătoare acțiunii cerute.
match ($action) {
    'register' => handle_register(), // înregistrare cont nou
    'login'    => handle_login(),    // autentificare
    'logout'   => handle_logout(),   // deconectare
    'me'       => handle_me(),       // date utilizator curent
    default    => json_error('Actiune invalida.', 404) // acțiune necunoscută → 404
};

// ============================================================
// Funcțiile handler — câte una pentru fiecare acțiune
// ============================================================

// ------------------------------------------------------------
// handle_register()
// Creează un cont nou de utilizator.
// Așteaptă în body JSON: { username, email, password }
// ------------------------------------------------------------
function handle_register(): void {
    // Citim body-ul cererii HTTP interpretat ca JSON.
    // $data va fi un array asociativ cu cheile trimise de client.
    $data = json_body();

    // Verificăm că toate câmpurile obligatorii sunt prezente.
    // missing_field() returnează numele primului câmp lipsă, sau null.
    $missing = missing_field($data, ['username', 'email', 'password']);
    if ($missing) {
        // Dacă lipsește un câmp, trimitem eroare 400 și oprim execuția.
        json_error("Campul '$missing' este obligatoriu.");
    }

    // Extragem și curățăm câmpurile din date.
    // trim() elimină spațiile de la margini (ex: "  ion  " → "ion").
    $username = trim($data['username']);
    $email    = trim($data['email']);
    $password = $data['password']; // parola nu se trimte, deci nu trim()

    // Validăm formatul email-ului cu filtrul built-in PHP.
    // FILTER_VALIDATE_EMAIL returnează false dacă formatul e invalid.
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Adresa de email este invalida.');
    }

    // Impunem o lungime minimă pentru parolă: 8 caractere.
    // strlen() numără bytes, nu caractere Unicode — suficient pentru ASCII.
    if (strlen($password) < 8) {
        json_error('Parola trebuie sa aiba cel putin 8 caractere.');
    }

    // Obținem instanța PDO (conexiunea la SQLite).
    $pdo = get_db();

    // Verificăm dacă username-ul SAU email-ul sunt deja folosite.
    // prepare() creează un prepared statement — ? sunt placeholders.
    // Prepared statements previn SQL injection: valorile nu sunt
    // concatenate în SQL, ci trimise separat la motorul bazei de date.
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');

    // execute() leagă valorile la placeholders și rulează query-ul.
    // Ordinea din array trebuie să corespundă cu ordinea ? din SQL.
    $stmt->execute([$username, $email]);

    // fetch() returnează primul rând găsit, sau false dacă nu există.
    // Dacă găsim ceva, înseamnă că username/email există deja.
    if ($stmt->fetch()) {
        // 409 Conflict = resursa există deja
        json_error('Username-ul sau emailul este deja folosit.', 409);
    }

    // Hashuim parola cu bcrypt înainte de a o stoca.
    // NICIODATĂ nu stocăm parole în clar în baza de date!
    // password_hash() generează automat un salt unic și
    // aplică bcrypt cu un cost implicit (10 runde de hashing).
    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Inserăm utilizatorul nou în baza de date.
    // Câmpul 'role' nu e specificat → va lua valoarea DEFAULT 'user' din schema SQL.
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);

    // lastInsertId() returnează ID-ul auto-generat al ultimei inserări.
    // (int) îl convertim la integer pentru consistență.
    $user_id = (int) $pdo->lastInsertId();

    // Trimitem răspuns de succes cu HTTP 201 Created.
    // Returnăm datele publice ale userului, NU parola sau hash-ul.
    json_response([
        'message' => 'Cont creat cu succes.',
        'user'    => ['id' => $user_id, 'username' => $username, 'email' => $email]
    ], 201);
}

// ------------------------------------------------------------
// handle_login()
// Autentifică un utilizator existent.
// Așteaptă în body JSON: { email, password }
// ------------------------------------------------------------
function handle_login(): void {
    // Citim body-ul JSON al cererii.
    $data = json_body();

    // Verificăm câmpurile obligatorii: email și parolă.
    $missing = missing_field($data, ['email', 'password']);
    if ($missing) {
        json_error("Campul '$missing' este obligatoriu.");
    }

    // Obținem conexiunea la baza de date.
    $pdo = get_db();

    // Căutăm utilizatorul după email.
    // SELECT * returnează toate coloanele, inclusiv password_hash.
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');

    // trim() pe email elimină spații accidentale.
    $stmt->execute([trim($data['email'])]);

    // Preluăm rândul complet al utilizatorului ca array asociativ.
    $user = $stmt->fetch(); // false dacă emailul nu există

    // Verificăm două condiții:
    //   1. $user există (emailul a fost găsit în baza de date)
    //   2. password_verify() confirmă că parola introdusă corespunde hash-ului
    // password_verify() compară în mod sigur — rezistent la timing attacks
    // (ia același timp indiferent dacă parola e corectă sau nu).
    // Mesajul de eroare este intenționat vag — nu spunem dacă emailul
    // sau parola e greșit/ă, pentru a nu ajuta un atacator.
    if (!$user || !password_verify($data['password'], $user['password_hash'])) {
        // 401 Unauthorized = credențiale invalide
        json_error('Email sau parola incorecte.', 401);
    }

    // Salvăm datele utilizatorului în sesiune.
    // Din acest moment is_logged_in() va returna true.
    login_user($user);

    // Trimitem răspuns de succes cu datele publice ale utilizatorului.
    // NU includem password_hash sau alte date sensibile.
    json_response([
        'message' => 'Autentificare reusita.',
        'user'    => [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'], // 'user' sau 'admin'
        ]
    ]);
}

// ------------------------------------------------------------
// handle_logout()
// Distruge sesiunea curentă.
// Nu necesită niciun body — suficient că cererea e POST.
// ------------------------------------------------------------
function handle_logout(): void {
    // Apelăm funcția din includes/auth.php care:
    //   1. Golește $_SESSION
    //   2. Distruge fișierul de sesiune de pe server
    logout_user();

    // Confirmăm deconectarea cu HTTP 200 implicit.
    json_response(['message' => 'Deconectare reusita.']);
}

// ------------------------------------------------------------
// handle_me()
// Returnează datele utilizatorului curent din sesiune.
// Util pentru JavaScript-ul din frontend care verifică
// dacă utilizatorul e logat și ce rol are.
// ------------------------------------------------------------
function handle_me(): void {
    // Dacă nu e autentificat, require_auth() oprește execuția cu 401.
    require_auth();

    // current_user() returnează array-ul cu datele din sesiune.
    json_response(['user' => current_user()]);
}
