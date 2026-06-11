<?php

require_once __DIR__ . '/jwt.php';

function session_init(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function is_logged_in(): bool {
    session_init();
    return isset($_SESSION['user_id']);
}

function current_user(): ?array {
    session_init();
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'],
        'role'     => $_SESSION['role'],
    ];
}

function is_admin(): bool {
    session_init();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function login_user(array $user): void {
    session_init();
    session_regenerate_id(true); // Prevents session fixation
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
}

function logout_user(): void {
    session_init();
    $_SESSION = [];
    session_destroy();
}

function require_auth(): void {
    // Verifică JWT Bearer token primul — permite acces fără cookie de sesiune
    $jwt = jwt_from_request();
    if ($jwt) {
        session_init();
        $_SESSION['user_id']  = $jwt['user_id'];
        $_SESSION['username'] = $jwt['username'];
        $_SESSION['email']    = $jwt['email'];
        $_SESSION['role']     = $jwt['role'];
        return;
    }

    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentificare necesara.']);
        exit;
    }
}

function require_admin(): void {
    require_auth();
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acces interzis. Doar adminii pot efectua aceasta actiune.']);
        exit;
    }
}
