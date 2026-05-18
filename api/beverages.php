<?php
/**
 * api/beverages.php
 *
 * Endpoint RESTful pentru resursa "beverages" (băuturi).
 * Rutarea se face pe baza metodei HTTP și prezenței parametrului ?id=
 *
 * GET    /api/beverages.php          -> lista tuturor băuturilor (cu filtrare opțională)
 * GET    /api/beverages.php?id=X     -> o singură băutură după ID
 * POST   /api/beverages.php          -> adaugă băutură nouă (doar admin)
 * PATCH  /api/beverages.php?id=X     -> modifică parțial o băutură (doar admin)
 * DELETE /api/beverages.php?id=X     -> șterge o băutură (doar admin)
 */

// Încărcăm utilitarele partajate folosite în toată aplicația.
// require_once garantează că fiecare fișier e inclus o singură dată,
// chiar dacă acest fișier ar fi inclus de mai multe ori.
require_once __DIR__ . '/../includes/db.php';      // get_db() — conexiunea PDO la SQLite
require_once __DIR__ . '/../includes/auth.php';    // require_admin(), current_user() etc.
require_once __DIR__ . '/../includes/helpers.php'; // json_response(), json_error(), json_body() etc.

// Spunem browserului/clientului că răspunsul nostru va fi JSON, nu HTML.
// charset=utf-8 e necesar pentru caracterele românești (ă, î, â etc.)
header('Content-Type: application/json; charset=utf-8');

// Citim metoda HTTP a cererii curente: GET, POST, PATCH sau DELETE.
// Aceasta determină ce operație vrem să facem (citire, creare, modificare, ștergere).
$method = http_method();

// Citim parametrul ?id= din URL dacă există și îl convertim la int.
// Dacă ?id= lipsește, $id rămâne null.
// Exemplu: /api/beverages.php?id=5 → $id = 5
//          /api/beverages.php      → $id = null
$id = get_param('id') ? (int) get_param('id') : null;

// Routerul principal: decide ce funcție să apeleze în funcție de
// metoda HTTP și prezența ID-ului în URL.
// match(true) evaluează fiecare condiție de sus în jos și o execută
// pe prima care e adevărată — echivalent cu un if/elseif mai curat.
match (true) {
    // GET fără ID → vrem lista tuturor băuturilor
    $method === 'GET'    && $id === null => handle_list(),

    // GET cu ID → vrem o singură băutură specifică
    $method === 'GET'    && $id !== null => handle_get($id),

    // POST → vrem să creăm o băutură nouă (ID-ul nu există încă)
    $method === 'POST'                   => handle_create(),

    // PATCH cu ID → vrem să modificăm parțial o băutură existentă
    $method === 'PATCH'  && $id !== null => handle_update($id),

    // DELETE cu ID → vrem să ștergem o băutură existentă
    $method === 'DELETE' && $id !== null => handle_delete($id),

    // Orice altă combinație (ex: DELETE fără ID) → eroare 405 Method Not Allowed
    default => json_error('Metoda sau ruta invalida.', 405)
};

// ============================================================
// HANDLER: GET /api/beverages.php
// Returnează lista tuturor băuturilor, cu filtrare opțională.
// ============================================================

function handle_list(): void {
    // Obținem conexiunea PDO la baza de date SQLite.
    $pdo = get_db();

    // Vom construi dinamic clauza WHERE și parametrii SQL
    // în funcție de filtrele primite în URL.
    $where  = []; // array cu condițiile SQL (ex: "b.season = ?")
    $params = []; // array cu valorile corespunzătoare (ex: "summer")

    // Citim filtrele opționale din URL.
    // get_param() returnează null dacă parametrul lipsește din URL.
    $category_id = get_param('category_id'); // ex: ?category_id=3
    $season      = get_param('season');      // ex: ?season=summer
    $region      = get_param('region');      // ex: ?region=Romania

    // Dacă filtrul există, adăugăm condiția SQL și valoarea corespunzătoare.
    // Folosim "?" în SQL (placeholder) în loc de valoarea directă pentru
    // a preveni SQL Injection — valorile se trimit separat prin execute().
    if ($category_id !== null) {
        $where[]  = 'b.category_id = ?';
        $params[] = (int) $category_id; // convertim la int pentru siguranță
    }
    if ($season !== null) {
        $where[]  = 'b.season = ?';
        $params[] = $season;
    }
    if ($region !== null) {
        $where[]  = 'b.region = ?';
        $params[] = $region;
    }

    // Construim query-ul SQL de bază.
    // "b.*" = toate coloanele din tabelul beverages
    // LEFT JOIN categories = adăugăm numele categoriei lângă fiecare băutură.
    // LEFT JOIN înseamnă că băuturile fără categorie sunt incluse tot (cu category_name = null).
    $sql = '
        SELECT b.*, c.name AS category_name
        FROM beverages b
        LEFT JOIN categories c ON c.id = b.category_id
    ';

    // Dacă există cel puțin un filtru, adăugăm clauza WHERE.
    // implode(' AND ', $where) unește condițiile cu AND:
    // ex: "b.season = ? AND b.region = ?"
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    // Sortăm rezultatele: cele mai noi băuturi apar primele.
    $sql .= ' ORDER BY b.created_at DESC';

    // Pregătim și executăm query-ul cu parametrii corespunzători.
    // prepare() + execute() separat = protecție SQL Injection.
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // fetchAll() returnează toate rândurile ca array de array-uri asociative.
    $beverages = $stmt->fetchAll();

    // Trimitem răspunsul JSON cu status 200 (implicit).
    json_response(['beverages' => $beverages]);
}

// ============================================================
// HANDLER: GET /api/beverages.php?id=X
// Returnează o singură băutură după ID, cu toate detaliile.
// ============================================================

function handle_get(int $id): void {
    $pdo = get_db();

    // Căutăm băutura după ID, alăturând și numele categoriei.
    $stmt = $pdo->prepare('
        SELECT b.*, c.name AS category_name
        FROM beverages b
        LEFT JOIN categories c ON c.id = b.category_id
        WHERE b.id = ?
    ');
    $stmt->execute([$id]);

    // fetch() returnează un singur rând sau false dacă nu există.
    $beverage = $stmt->fetch();

    // Dacă băutura nu există, oprim execuția cu eroare 404 Not Found.
    if (!$beverage) {
        json_error('Bautura nu a fost gasita.', 404);
    }

    // Adăugăm relațiile many-to-many ca sub-array-uri în răspuns.
    // Astfel clientul primește toate informațiile într-un singur apel API.
    $beverage['ingredients'] = get_ingredients($pdo, $id); // ingredientele băuturii
    $beverage['allergens']   = get_allergens($pdo, $id);   // alergenii băuturii
    $beverage['venues']      = get_venues($pdo, $id);      // localurile unde se găsește

    json_response(['beverage' => $beverage]);
}

// ============================================================
// HANDLER: POST /api/beverages.php
// Creează o băutură nouă. Doar adminul poate face asta.
// ============================================================

function handle_create(): void {
    // Verificăm că userul este logat și are rolul 'admin'.
    // Dacă nu, require_admin() oprește execuția cu 401 sau 403.
    require_admin();

    // Citim body-ul cererii HTTP interpretat ca JSON.
    // Clientul trebuie să trimită: Content-Type: application/json
    $data = json_body();

    // Verificăm că câmpul obligatoriu 'name' există în body.
    // missing_field() returnează numele primului câmp lipsă sau null dacă totul e ok.
    $missing = missing_field($data, ['name']);
    if ($missing) {
        json_error("Campul '$missing' este obligatoriu.");
    }

    $pdo = get_db();

    // Inserăm băutura nouă în baza de date.
    // "?" pentru fiecare valoare = prepared statement (protecție SQL Injection).
    // Valorile opționale folosesc "?? null" — dacă cheia nu există în $data,
    // valoarea inserată va fi NULL în baza de date.
    $stmt = $pdo->prepare('
        INSERT INTO beverages (name, category_id, price, description, image_path, perishability_days, season, region, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        trim($data['name']),              // trim() elimină spațiile de la capete
        $data['category_id']        ?? null,
        $data['price']              ?? null,
        $data['description']        ?? null,
        $data['image_path']         ?? null,
        $data['perishability_days'] ?? null,
        $data['season']             ?? null,
        $data['region']             ?? null,
        current_user()['id']        ?? null, // ID-ul adminului care a creat băutura
    ]);

    // lastInsertId() returnează ID-ul auto-generat al rândului tocmai inserat.
    $id = (int) $pdo->lastInsertId();

    // Salvăm relațiile many-to-many dacă au fost trimise în body.
    // "?? []" înseamnă: dacă cheia lipsește din $data, folosim array gol (nu se inserează nimic).
    sync_ingredients($pdo, $id, $data['ingredients'] ?? []);
    sync_allergens($pdo, $id, $data['allergens']     ?? []);
    sync_venues($pdo, $id, $data['venues']           ?? []);

    // Răspundem cu 201 Created și ID-ul noii băuturi.
    json_response(['message' => 'Bautura adaugata cu succes.', 'id' => $id], 201);
}

// ============================================================
// HANDLER: PATCH /api/beverages.php?id=X
// Modifică parțial o băutură existentă. Doar adminul poate face asta.
// PATCH = modificare parțială (doar câmpurile trimise se actualizează).
// ============================================================

function handle_update(int $id): void {
    require_admin();

    $pdo = get_db();

    // Verificăm că băutura cu ID-ul dat există înainte să încercăm s-o modificăm.
    $stmt = $pdo->prepare('SELECT id FROM beverages WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_error('Bautura nu a fost gasita.', 404);
    }

    // Citim câmpurile trimise de client în body-ul cererii.
    $data = json_body();

    // Construim dinamic query-ul UPDATE cu doar câmpurile trimise.
    // Asta implementează comportamentul PATCH: câmpurile netrimise rămân neschimbate.
    $fields = []; // ex: ["name = ?", "price = ?"]
    $params = []; // valorile corespunzătoare

    // Iterăm prin toate câmpurile posibile ale băuturii.
    foreach (['name', 'category_id', 'price', 'description', 'image_path', 'perishability_days', 'season', 'region'] as $col) {
        // array_key_exists verifică dacă cheia există în $data,
        // chiar dacă valoarea e null (diferit de isset() care ignoră null-urile).
        if (array_key_exists($col, $data)) {
            $fields[] = "$col = ?";
            // Pentru 'name' aplicăm trim(), pentru celelalte luăm valoarea ca atare.
            $params[] = $col === 'name' ? trim($data[$col]) : $data[$col];
        }
    }

    // Dacă există cel puțin un câmp de actualizat, executăm UPDATE-ul.
    if ($fields) {
        // Adăugăm ID-ul la sfârșitul parametrilor (pentru clauza WHERE id = ?).
        $params[] = $id;
        // implode(', ', $fields) generează: "name = ?, price = ?" etc.
        $pdo->prepare('UPDATE beverages SET ' . implode(', ', $fields) . ' WHERE id = ?')
            ->execute($params);
    }

    // Actualizăm relațiile many-to-many doar dacă au fost trimise în body.
    // Dacă 'ingredients' nu e în body, ingredientele existente rămân neatinse.
    if (array_key_exists('ingredients', $data)) {
        sync_ingredients($pdo, $id, $data['ingredients']);
    }
    if (array_key_exists('allergens', $data)) {
        sync_allergens($pdo, $id, $data['allergens']);
    }
    if (array_key_exists('venues', $data)) {
        sync_venues($pdo, $id, $data['venues']);
    }

    json_response(['message' => 'Bautura actualizata cu succes.']);
}

// ============================================================
// HANDLER: DELETE /api/beverages.php?id=X
// Șterge o băutură existentă. Doar adminul poate face asta.
// ============================================================

function handle_delete(int $id): void {
    require_admin();

    $pdo = get_db();

    // Verificăm că băutura există înainte să încercăm s-o ștergem.
    $stmt = $pdo->prepare('SELECT id FROM beverages WHERE id = ?');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        json_error('Bautura nu a fost gasita.', 404);
    }

    // Ștergem băutura. Relațiile din tabelele beverage_ingredients,
    // beverage_allergens și beverage_venues se șterg automat prin
    // ON DELETE CASCADE definit în schema SQL (data/init.sql).
    $pdo->prepare('DELETE FROM beverages WHERE id = ?')->execute([$id]);

    json_response(['message' => 'Bautura stearsa cu succes.']);
}

// ============================================================
// HELPERS — funcții pentru relații many-to-many
// Fiecare funcție get_* citește relațiile unei băuturi.
// Fiecare funcție sync_* rescrie complet relațiile unei băuturi.
// ============================================================

// Returnează ingredientele asociate unei băuturi ca array.
// JOIN cu tabelul ingredients pentru a obține și numele, nu doar ID-ul.
function get_ingredients(PDO $pdo, int $beverage_id): array {
    $stmt = $pdo->prepare('
        SELECT i.id, i.name, bi.quantity
        FROM beverage_ingredients bi
        JOIN ingredients i ON i.id = bi.ingredient_id
        WHERE bi.beverage_id = ?
    ');
    $stmt->execute([$beverage_id]);
    return $stmt->fetchAll();
}

// Returnează alergenii asociați unei băuturi ca array.
function get_allergens(PDO $pdo, int $beverage_id): array {
    $stmt = $pdo->prepare('
        SELECT a.id, a.name
        FROM beverage_allergens ba
        JOIN allergens a ON a.id = ba.allergen_id
        WHERE ba.beverage_id = ?
    ');
    $stmt->execute([$beverage_id]);
    return $stmt->fetchAll();
}

// Returnează localurile asociate unei băuturi ca array.
function get_venues(PDO $pdo, int $beverage_id): array {
    $stmt = $pdo->prepare('
        SELECT v.id, v.name, v.location, v.contact
        FROM beverage_venues bv
        JOIN venues v ON v.id = bv.venue_id
        WHERE bv.beverage_id = ?
    ');
    $stmt->execute([$beverage_id]);
    return $stmt->fetchAll();
}

// Sincronizează ingredientele unei băuturi: șterge tot ce există și reinseră
// lista nouă trimisă de client. Mai simplu decât a calcula diferența.
// $items = array de obiecte cu 'id' și opțional 'quantity'.
// Exemplu: [{"id": 1, "quantity": "200ml"}, {"id": 3, "quantity": "1 lingurita"}]
function sync_ingredients(PDO $pdo, int $beverage_id, array $items): void {
    // Ștergem toate ingredientele existente ale băuturii.
    $pdo->prepare('DELETE FROM beverage_ingredients WHERE beverage_id = ?')->execute([$beverage_id]);

    // Reinserăm ingredientele noi unul câte unul.
    // INSERT OR IGNORE: dacă un ingredient e duplicat (din cauza unui bug în client),
    // îl ignorăm în loc să aruncăm eroare.
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO beverage_ingredients (beverage_id, ingredient_id, quantity) VALUES (?, ?, ?)');
    foreach ($items as $item) {
        $stmt->execute([$beverage_id, (int) $item['id'], $item['quantity'] ?? null]);
    }
}

// Sincronizează alergenii unei băuturi. Același pattern ca sync_ingredients.
// $items = array de obiecte cu 'id'. Exemplu: [{"id": 2}, {"id": 5}]
function sync_allergens(PDO $pdo, int $beverage_id, array $items): void {
    $pdo->prepare('DELETE FROM beverage_allergens WHERE beverage_id = ?')->execute([$beverage_id]);
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO beverage_allergens (beverage_id, allergen_id) VALUES (?, ?)');
    foreach ($items as $item) {
        $stmt->execute([$beverage_id, (int) $item['id']]);
    }
}

// Sincronizează localurile unei băuturi. Același pattern ca sync_ingredients.
// $items = array de obiecte cu 'id'. Exemplu: [{"id": 1}, {"id": 3}]
function sync_venues(PDO $pdo, int $beverage_id, array $items): void {
    $pdo->prepare('DELETE FROM beverage_venues WHERE beverage_id = ?')->execute([$beverage_id]);
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO beverage_venues (beverage_id, venue_id) VALUES (?, ?)');
    foreach ($items as $item) {
        $stmt->execute([$beverage_id, (int) $item['id']]);
    }
}
