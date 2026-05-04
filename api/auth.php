<?php
/**
 * api/auth.php
 * Endpoint pentru autentificare.
 *
 * POST ?action=register  -> inregistrare utilizator nou
 * POST ?action=login     -> autentificare
 * POST ?action=logout    -> deconectare
 * GET  ?action=me        -> date utilizator curent
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = get_param('action') ?? '';

match ($action) {
    'register' => handle_register(),
    'login'    => handle_login(),
    'logout'   => handle_logout(),
    'me'       => handle_me(),
    default    => json_error('Actiune invalida.', 404)
};

// ------------------------------------------------------------

function handle_register(): void {
    $data = json_body();

    // Validare campuri obligatorii
    $missing = missing_field($data, ['username', 'email', 'password']);
    if ($missing) {
        json_error("Campul '$missing' este obligatoriu.");
    }

    $username = trim($data['username']);
    $email    = trim($data['email']);
    $password = $data['password'];

    // Validare email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Adresa de email este invalida.');
    }

    // Validare lungime parola
    if (strlen($password) < 8) {
        json_error('Parola trebuie sa aiba cel putin 8 caractere.');
    }

    $pdo = get_db();

    // Verifica daca username sau email exista deja
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        json_error('Username-ul sau emailul este deja folosit.', 409);
    }

    // Salveaza utilizatorul cu parola hash-uita
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hash]);

    $user_id = (int) $pdo->lastInsertId();

    json_response([
        'message' => 'Cont creat cu succes.',
        'user'    => ['id' => $user_id, 'username' => $username, 'email' => $email]
    ], 201);
}

function handle_login(): void {
    $data = json_body();

    $missing = missing_field($data, ['email', 'password']);
    if ($missing) {
        json_error("Campul '$missing' este obligatoriu.");
    }

    $pdo  = get_db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([trim($data['email'])]);
    $user = $stmt->fetch();

    // Verifica parola (password_verify previne timing attacks)
    if (!$user || !password_verify($data['password'], $user['password_hash'])) {
        json_error('Email sau parola incorecte.', 401);
    }

    login_user($user);

    json_response([
        'message' => 'Autentificare reusita.',
        'user'    => [
            'id'       => $user['id'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role'],
        ]
    ]);
}

function handle_logout(): void {
    logout_user();
    json_response(['message' => 'Deconectare reusita.']);
}

function handle_me(): void {
    require_auth();
    json_response(['user' => current_user()]);
}
