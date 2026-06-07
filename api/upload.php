<?php
// ============================================================
// api/upload.php — Upload imagine pentru o băutură.
// Acceptă multipart/form-data POST cu câmpul 'image'.
// Returnează calea relativă salvată în baza de date.
// Accesibil exclusiv adminilor.
// ============================================================

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_init();
require_admin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metoda invalida. Foloseste POST.', 405);
}

if (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    json_error('Niciun fisier primit.', 400);
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    json_error('Eroare la upload (cod ' . $file['error'] . ').', 400);
}

// Limita de 2MB
if ($file['size'] > 2 * 1024 * 1024) {
    json_error('Fisierul depaseste limita de 2MB.', 400);
}

// Detectăm MIME-ul real din conținut — nu din extensia declarată de client
$finfo   = new finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->file($file['tmp_name']);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

if (!isset($allowed[$mime])) {
    json_error('Format nesuportat. Folositi JPEG, PNG, WebP sau GIF.', 400);
}

$ext      = $allowed[$mime];
// uniqid cu more_entropy = true generează un ID suficient de unic pentru a evita coliziuni
$filename = uniqid('bev_', true) . '.' . $ext;
$dir      = __DIR__ . '/../public/images/beverages/';
$dest     = $dir . $filename;

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_error('Eroare la salvarea imaginii pe server.', 500);
}

// Calculăm calea URL dinamic față de rădăcina aplicației.
// dirname(dirname(SCRIPT_NAME)) urcă 2 nivele: /beverages-app/api/upload.php → /beverages-app
// Funcționează atât cu XAMPP (rădăcina e beverages-app/) cât și cu PHP built-in din directorul părinte.
$appRoot   = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$imagePath = $appRoot . '/public/images/beverages/' . $filename;

json_response(['image_path' => $imagePath]);
