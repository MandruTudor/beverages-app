<?php
/**
 * api/categories.php
 *
 * Endpoint RESTful pentru resursa "categories" (categorii de băuturi).
 * Urmează același pattern ca api/beverages.php — rutare pe metoda HTTP.
 *
 * GET    /api/categories.php          -> lista tuturor categoriilor
 * GET    /api/categories.php?id=X     -> o singură categorie după ID
 * POST   /api/categories.php          -> adaugă categorie nouă (doar admin)
 * PATCH  /api/categories.php?id=X     -> modifică o categorie (doar admin)
 * DELETE /api/categories.php?id=X     -> șterge o categorie (doar admin)
 */

// Includem utilitarele partajate — aceleași ca în toate endpoint-urile API.
require_once __DIR__ . '/../includes/db.php';      // get_db() — conexiunea PDO la SQLite
require_once __DIR__ . '/../includes/auth.php';    // require_admin(), is_logged_in() etc.
require_once __DIR__ . '/../includes/helpers.php'; // json_response(), json_error() etc.

// Anunțăm browserul/clientul că răspundem cu JSON în UTF-8.
header('Content-Type: application/json; charset=utf-8');

// Citim metoda HTTP și parametrul ?id= din URL.
$method = http_method();
$id     = get_param('id') ? (int) get_param('id') : null;

// Routerul: direcționăm cererea către funcția corespunzătoare.
match (true) {
    $method === 'GET'    && $id === null => handle_list(),
    $method === 'GET'    && $id !== null => handle_get($id),
    $method === 'POST'                   => handle_create(),
    $method === 'PATCH'  && $id !== null => handle_update($id),
    $method === 'DELETE' && $id !== null => handle_delete($id),
    default => json_error('Metoda sau ruta invalida.', 405)
};

// ============================================================
// HANDLER: GET /api/categories.php
// Returnează lista tuturor categoriilor, sortate alfabetic.
// Accesibil tuturor utilizatorilor autentificați (și neautentificați —
// categoriile sunt date publice, necesare în dropdown-uri).
// ============================================================

function handle_list(): void {
    $pdo = get_db();

    // Selectăm toate categoriile, sortate alfabetic după nume.
    // ORDER BY name ASC = ascendent (A → Z).
    $stmt = $pdo->query('SELECT * FROM categories ORDER BY name ASC');

    // fetchAll() returnează toate rândurile ca array de array-uri asociative.
    json_response(['categories' => $stmt->fetchAll()]);
}

// ============================================================
// HANDLER: GET /api/categories.php?id=X
// Returnează o singură categorie după ID.
// ============================================================

function handle_get(int $id): void {
    $pdo = get_db();

    // Căutăm categoria cu ID-ul dat.
    // Folosim prepared statement chiar și pentru un simplu SELECT cu ID,
    // din principiul de a folosi întotdeauna parametrii legați (SQL Injection prevention).
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch(); // fetch() = un singur rând sau false

    // Dacă categoria nu există, răspundem cu 404 Not Found.
    if (!$category) {
        json_error('Categoria nu a fost gasita.', 404);
    }

    json_response(['category' => $category]);
}

// ============================================================
// HANDLER: POST /api/categories.php
// Creează o categorie nouă. Doar adminul poate face asta.
// ============================================================

function handle_create(): void {
    // Verificăm că utilizatorul are rol de admin.
    // require_admin() oprește execuția cu 401/403 dacă nu e cazul.
    require_admin();

    // Citim corpul cererii interpretat ca JSON.
    $data = json_body();

    // Verificăm că câmpul obligatoriu 'name' există și nu e gol.
    $missing = missing_field($data, ['name']);
    if ($missing) {
        json_error("Campul '$missing' este obligatoriu.");
    }

    $pdo = get_db();

    // Inserăm categoria nouă cu prepared statement.
    // 'description' este opțional — dacă lipsește din body, inserăm NULL.
    $stmt = $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
    $stmt->execute([
        trim($data['name']),         // trim() elimină spațiile de la capete
        $data['description'] ?? null // ?? null = NULL dacă cheia lipsește din array
    ]);

    // Returnăm 201 Created cu ID-ul noii categorii.
    $id = (int) $pdo->lastInsertId();
    json_response(['message' => 'Categoria adaugata cu succes.', 'id' => $id], 201);
}

// ============================================================
// HANDLER: PATCH /api/categories.php?id=X
// Modifică parțial o categorie existentă. Doar adminul poate face asta.
// ============================================================

function handle_update(int $id): void {
    require_admin();

    $pdo = get_db();

    // Verificăm că categoria există înainte de a încerca actualizarea.
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_error('Categoria nu a fost gasita.', 404);
    }

    $data = json_body();

    // Construim dinamic clauza SET — actualizăm doar câmpurile trimise.
    // Asta implementează semantica PATCH: câmpurile netrimise rămân neschimbate.
    $fields = []; // ex: ["name = ?", "description = ?"]
    $params = []; // valorile corespunzătoare

    foreach (['name', 'description'] as $col) {
        // array_key_exists verifică prezența cheii chiar dacă valoarea e null.
        // Diferit de isset() care returnează false pentru null.
        if (array_key_exists($col, $data)) {
            $fields[] = "$col = ?";
            $params[] = $col === 'name' ? trim($data[$col]) : $data[$col];
        }
    }

    // Executăm UPDATE doar dacă există cel puțin un câmp de actualizat.
    if ($fields) {
        $params[] = $id; // ID-ul merge la finalul params (pentru WHERE id = ?)
        $pdo->prepare('UPDATE categories SET ' . implode(', ', $fields) . ' WHERE id = ?')
            ->execute($params);
    }

    json_response(['message' => 'Categoria actualizata cu succes.']);
}

// ============================================================
// HANDLER: DELETE /api/categories.php?id=X
// Șterge o categorie existentă. Doar adminul poate face asta.
// Băuturile din acea categorie vor avea category_id = NULL (ON DELETE SET NULL din schema SQL).
// ============================================================

function handle_delete(int $id): void {
    require_admin();

    $pdo = get_db();

    // Verificăm că categoria există.
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_error('Categoria nu a fost gasita.', 404);
    }

    // Ștergem categoria.
    // Băuturile asociate nu sunt șterse — category_id lor devine NULL
    // datorită ON DELETE SET NULL definit în data/init.sql.
    $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);

    json_response(['message' => 'Categoria stearsa cu succes.']);
}
