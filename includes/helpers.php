<?php
/**
 * helpers.php
 * Functii utilitare folosite in toata aplicatia.
 */

/**
 * Seteaza headerele corecte si trimite un raspuns JSON.
 * Opreste executia dupa trimitere.
 *
 * @param mixed $data    Datele de returnat
 * @param int   $status  Codul HTTP (default 200)
 */
function json_response(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Returneaza un raspuns de eroare JSON si opreste executia.
 *
 * @param string $message Mesajul de eroare
 * @param int    $status  Codul HTTP (default 400)
 */
function json_error(string $message, int $status = 400): void {
    json_response(['error' => $message], $status);
}

/**
 * Sanitizeaza un string pentru afisare in HTML (previne XSS).
 */
function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizeaza recursiv un array de valori string.
 */
function sanitize_array(array $data): array {
    return array_map(fn($v) => is_string($v) ? sanitize($v) : $v, $data);
}

/**
 * Returneaza valoarea unui camp POST sau null daca nu exista.
 */
function post(string $key): ?string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

/**
 * Returneaza valoarea unui param GET sau null daca nu exista.
 */
function get_param(string $key): ?string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : null;
}

/**
 * Citeste body-ul cererii ca JSON si il returneaza ca array.
 * Returneaza array gol daca body-ul nu e JSON valid.
 */
function json_body(): array {
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Verifica daca toate campurile obligatorii sunt prezente intr-un array.
 * Returneaza numele primului camp lipsa sau null daca totul e ok.
 */
function missing_field(array $data, array $required): ?string {
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return $field;
        }
    }
    return null;
}

/**
 * Returneaza metoda HTTP a cererii curente (GET, POST, PUT, DELETE).
 */
function http_method(): string {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}
