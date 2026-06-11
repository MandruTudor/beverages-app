<?php

define('JWT_SECRET', 'beverages-app-jwt-secret-2025');
define('JWT_TTL',    3600); // 1 oră

function jwt_generate(array $payload): string {
    $header          = _b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat']  = time();
    $payload['exp']  = time() + JWT_TTL;
    $body            = _b64url(json_encode($payload));
    $sig             = _b64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$sig";
}

function jwt_validate(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $body, $sig] = $parts;

    $expected = _b64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;

    $data = json_decode(_b64url_decode($body), true);
    if (!is_array($data))             return null;
    if (($data['exp'] ?? 0) < time()) return null;

    return $data;
}

function jwt_from_request(): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$header && function_exists('getallheaders')) {
        $header = getallheaders()['Authorization'] ?? '';
    }
    if (!str_starts_with($header, 'Bearer ')) return null;
    return jwt_validate(substr($header, 7));
}

function _b64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function _b64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}
