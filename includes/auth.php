<?php
/**
 * auth.php
 * Helper-e pentru autentificare si autorizare bazate pe sesiuni PHP native.
 */

/**
 * Porneste sesiunea daca nu e deja pornita.
 */
function session_init(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Verifica daca utilizatorul curent este autentificat.
 */
function is_logged_in(): bool {
    session_init();
    return isset($_SESSION['user_id']);
}

/**
 * Returneaza datele utilizatorului din sesiune sau null.
 */
function current_user(): ?array {
    session_init();
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email'    => $_SESSION['email'],
        'role'     => $_SESSION['role'],
    ];
}

/**
 * Verifica daca utilizatorul curent are rol de admin.
 */
function is_admin(): bool {
    session_init();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Salveaza datele utilizatorului in sesiune dupa login reusit.
 */
function login_user(array $user): void {
    session_init();
    session_regenerate_id(true); // Previne session fixation
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email']    = $user['email'];
    $_SESSION['role']     = $user['role'];
}

/**
 * Distruge sesiunea curenta (logout).
 */
function logout_user(): void {
    session_init();
    $_SESSION = [];
    session_destroy();
}

/**
 * Opreste executia si returneaza 401 daca utilizatorul nu e autentificat.
 * Se foloseste la inceputul endpoint-urilor protejate.
 */
function require_auth(): void {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Autentificare necesara.']);
        exit;
    }
}

/**
 * Opreste executia si returneaza 403 daca utilizatorul nu e admin.
 */
function require_admin(): void {
    require_auth();
    if (!is_admin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Acces interzis. Doar adminii pot efectua aceasta actiune.']);
        exit;
    }
}
