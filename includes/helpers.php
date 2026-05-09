<?php
// ============================================================
// includes/helpers.php
// Funcții utilitare generale, fără legătură cu autentificarea
// sau baza de date. Folosite în toată aplicația.
// ============================================================

// ------------------------------------------------------------
// json_response(mixed $data, int $status = 200)
// Trimite un răspuns HTTP cu corp JSON și oprește execuția.
// Este funcția principală de răspuns pentru toate endpoint-urile API.
// ------------------------------------------------------------
function json_response(mixed $data, int $status = 200): void {
    // Setăm codul de status HTTP (200 OK, 201 Created, 400 Bad Request etc.).
    // Trebuie apelat ÎNAINTE de echo, altfel headerul nu mai poate fi trimis.
    http_response_code($status);

    // Adăugăm headerul Content-Type ca browserul/clientul să știe
    // că primește JSON, nu HTML sau text. charset=utf-8 e obligatoriu
    // pentru caractere românești.
    header('Content-Type: application/json; charset=utf-8');

    // Serializăm $data în șir JSON.
    // JSON_UNESCAPED_UNICODE păstrează caracterele românești (ă, î, â etc.)
    // ca atare, în loc să le escape-uiască ca ã.
    echo json_encode($data, JSON_UNESCAPED_UNICODE);

    // Oprim execuția PHP — nimic nu mai rulează după răspuns.
    exit;
}

// ------------------------------------------------------------
// json_error(string $message, int $status = 400)
// Shortcut pentru a trimite un răspuns de eroare JSON.
// Înfășoară mesajul într-un array cu cheia 'error'.
// ------------------------------------------------------------
function json_error(string $message, int $status = 400): void {
    // Apelăm json_response cu structura standard de eroare.
    // 400 Bad Request este codul implicit pentru erori de validare.
    json_response(['error' => $message], $status);
}

// ------------------------------------------------------------
// sanitize(string $value): string
// Curăță un string pentru afișare sigură în HTML.
// Previne atacurile XSS (Cross-Site Scripting).
// ------------------------------------------------------------
function sanitize(string $value): string {
    // htmlspecialchars() convertește caracterele periculoase HTML:
    //   <  →  &lt;      (previne injecție de taguri)
    //   >  →  &gt;
    //   "  →  &quot;    (previne spargerea atributelor HTML)
    //   '  →  &#039;
    //   &  →  &amp;
    // ENT_QUOTES: escape-uiește și ghilimelele simple, nu doar duble.
    // 'UTF-8': specifică encoding-ul pentru a gestiona corect caracterele românești.
    // trim() elimină spațiile de la începutul și sfârșitul șirului.
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// ------------------------------------------------------------
// sanitize_array(array $data): array
// Aplică sanitize() pe fiecare element string dintr-un array.
// Util pentru a curăța bulk date venite din formular.
// ------------------------------------------------------------
function sanitize_array(array $data): array {
    // array_map() aplică o funcție pe fiecare element al array-ului.
    // fn($v) => ... este sintaxa "arrow function" din PHP 7.4+.
    // Dacă elementul e string, îl sanitizăm; altfel (int, bool etc.) îl lăsăm neatins.
    return array_map(fn($v) => is_string($v) ? sanitize($v) : $v, $data);
}

// ------------------------------------------------------------
// post(string $key): ?string
// Returnează o valoare trimisă prin POST (formular HTML clasic).
// Returnează null dacă câmpul nu există.
// '?string' = string sau null (nullable).
// ------------------------------------------------------------
function post(string $key): ?string {
    // isset() verifică că cheia există și nu e null.
    // trim() elimină spațiile din jur.
    return isset($_POST[$key]) ? trim($_POST[$key]) : null;
}

// ------------------------------------------------------------
// get_param(string $key): ?string
// Returnează un parametru din URL (?key=valoare).
// Returnează null dacă parametrul lipsește.
// ------------------------------------------------------------
function get_param(string $key): ?string {
    // $_GET conține toți parametrii din query string-ul URL-ului.
    // Exemplu: /api/auth.php?action=login → $_GET['action'] = 'login'
    return isset($_GET[$key]) ? trim($_GET[$key]) : null;
}

// ------------------------------------------------------------
// json_body(): array
// Citește corpul cererii HTTP și îl interpretează ca JSON.
// Folosit de endpoint-urile API care primesc date JSON (fetch/axios).
// Returnează array gol dacă body-ul lipsește sau nu e JSON valid.
// ------------------------------------------------------------
function json_body(): array {
    // 'php://input' este un stream special PHP care conține
    // corpul brut al cererii HTTP, indiferent de Content-Type.
    // file_get_contents() citește tot stream-ul ca string.
    $raw = file_get_contents('php://input');

    // json_decode($raw, true) parsează JSON-ul în array PHP asociativ.
    // Al doilea argument 'true' face ca obiectele JSON {} să devină
    // array-uri asociative, nu obiecte stdClass.
    $decoded = json_decode($raw, true);

    // Dacă decodarea eșuează (JSON invalid) sau rezultatul nu e array,
    // returnăm array gol pentru a evita erori în restul codului.
    return is_array($decoded) ? $decoded : [];
}

// ------------------------------------------------------------
// missing_field(array $data, array $required): ?string
// Verifică dacă toate câmpurile obligatorii există într-un array.
// Returnează numele primului câmp lipsă, sau null dacă totul e ok.
// Folosit la validarea datelor din cereri API.
// ------------------------------------------------------------
function missing_field(array $data, array $required): ?string {
    // Iterăm prin lista de câmpuri obligatorii.
    foreach ($required as $field) {
        // empty() returnează true dacă valoarea nu există, e null,
        // e șirul gol "", sau este 0, false etc.
        // Deci un câmp cu valoarea "0" ar fi considerat "lipsă" — de reținut.
        if (empty($data[$field])) {
            // Returnăm imediat numele primului câmp lipsă.
            return $field;
        }
    }

    // Toate câmpurile sunt prezente — returnăm null (nicio problemă).
    return null;
}

// ------------------------------------------------------------
// http_method(): string
// Returnează metoda HTTP a cererii curente: GET, POST, PUT, DELETE etc.
// Util pentru endpoint-urile RESTful care rutează pe baza metodei.
// ------------------------------------------------------------
function http_method(): string {
    // $_SERVER['REQUEST_METHOD'] conține metoda HTTP ca string uppercase.
    // ?? 'GET' este operatorul null-coalescing: dacă REQUEST_METHOD
    // nu există (situație ipotetică), folosim 'GET' ca fallback.
    // strtoupper() normalizează la majuscule (deși e deja uppercase în PHP).
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}
