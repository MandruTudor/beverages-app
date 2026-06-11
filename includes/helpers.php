<?php

function json_response(mixed $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE); // preserves Romanian chars as-is
    exit;
}

function json_error(string $message, int $status = 400): void {
    json_response(['error' => $message], $status);
}

function sanitize(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function sanitize_array(array $data): array {
    return array_map(fn($v) => is_string($v) ? sanitize($v) : $v, $data);
}

function post(string $key): ?string {
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

function get_param(string $key): ?string {
    return isset($_GET[$key]) ? trim($_GET[$key]) : null;
}

function json_body(): array {
    $decoded = json_decode(file_get_contents('php://input'), true);
    return is_array($decoded) ? $decoded : [];
}

function missing_field(array $data, array $required): ?string {
    foreach ($required as $field) {
        if (empty($data[$field])) return $field;
    }
    return null;
}

function http_method(): string {
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}
