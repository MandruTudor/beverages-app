<?php
// ============================================================
// api/openfoodfacts.php — Proxy spre Open Food Facts API.
//
// Motivul pentru un proxy PHP (în loc de fetch direct din JS):
//   - Evităm problemele CORS — OFF nu permite cereri XHR din browsere arbitrare.
//   - Ascundem URL-ul exact al OFF față de client.
//   - Facem mapping-ul la schema noastră pe server, unde avem acces la DB.
//
// Acțiuni disponibile:
//   GET ?action=search&q=TERMEN  — caută produse după cuvânt cheie
//   GET ?action=get&barcode=COD  — obține un produs după codul de bare
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

session_init();
// Doar utilizatorii autentificați pot folosi integrarea OFF
require_auth();

header('Content-Type: application/json; charset=utf-8');

$action = get_param('action');

// Dispecerizăm acțiunea
match ($action) {
    'search' => handle_off_search(),
    'get'    => handle_off_get(),
    default  => json_error('Acțiune necunoscută. Folosește: search sau get.', 400),
};

// ============================================================
// HANDLER: Căutare produse după cuvânt cheie
// ============================================================

function handle_off_search(): void {
    $q = trim(get_param('q') ?? '');
    if (strlen($q) < 2) {
        json_error('Termenul de căutare trebuie să aibă cel puțin 2 caractere.', 400);
    }

    // Construim URL-ul de căutare OFF cu câmpurile de care avem nevoie
    // page_size=12 — suficient pentru un grid de rezultate
    $url = 'https://world.openfoodfacts.org/cgi/search.pl?' . http_build_query([
        'search_terms'    => $q,
        'json'            => 1,
        'page_size'       => 12,
        'fields'          => 'code,product_name,categories_tags,ingredients_text,allergens_tags,image_front_url,countries_tags',
    ]);

    $raw = off_fetch($url);
    $data = json_decode($raw, true);

    if (!$data || !isset($data['products'])) {
        json_error('Răspuns invalid de la Open Food Facts.', 502);
    }

    $pdo      = get_db();
    // Filtrăm produsele fără nume și facem mapping
    $products = array_values(array_filter(
        array_map(fn($p) => map_off_product($p, $pdo), $data['products'])
    ));

    json_response([
        'products' => $products,
        'count'    => count($products),
    ]);
}

// ============================================================
// HANDLER: Obținere produs după cod de bare
// ============================================================

function handle_off_get(): void {
    $barcode = trim(get_param('barcode') ?? '');
    // Validăm că e un cod numeric de 8-14 cifre (EAN-8, EAN-13, UPC etc.)
    if (!preg_match('/^\d{8,14}$/', $barcode)) {
        json_error('Cod de bare invalid. Folosește un cod numeric de 8-14 cifre.', 400);
    }

    $url = "https://world.openfoodfacts.org/api/v2/product/{$barcode}.json?"
         . http_build_query([
               'fields' => 'code,product_name,categories_tags,ingredients_text,allergens_tags,image_front_url,countries_tags',
           ]);

    $raw  = off_fetch($url);
    $data = json_decode($raw, true);

    // OFF returnează status=0 dacă produsul nu există
    if (!$data || (int)($data['status'] ?? 0) !== 1) {
        json_error('Produsul nu a fost găsit în Open Food Facts.', 404);
    }

    $pdo     = get_db();
    $product = map_off_product($data['product'], $pdo);
    if (!$product) {
        json_error('Produsul nu are un nume valid în Open Food Facts.', 422);
    }

    json_response(['product' => $product]);
}

// ============================================================
// UTILITAR: Execută un request HTTP spre OFF
// ============================================================

function off_fetch(string $url): string {
    // Setăm un User-Agent corect — OFF cere un User-Agent identificabil
    $context = stream_context_create(['http' => [
        'timeout' => 10,
        'header'  => "User-Agent: BeveragesApp/1.0 (proiect universitar; contact: admin@beverages.local)\r\n",
    ]]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        json_error('Nu s-a putut conecta la Open Food Facts. Verifică conexiunea la internet.', 503);
    }
    return $raw;
}

// ============================================================
// MAPPING: Produs OFF → formatul intern al aplicației
// ============================================================

/**
 * map_off_product(array $p, PDO $pdo): ?array
 *
 * Transformă un produs din formatul OFF în structura așteptată de
 * POST /api/beverages.php (câmpuri scalare + array allergens).
 * Returnează null dacă produsul nu are un nume utilizabil.
 */
function map_off_product(array $p, PDO $pdo): ?array {
    // Câmpul product_name e obligatoriu
    $name = trim($p['product_name'] ?? '');
    if ($name === '') return null;

    return [
        'name'        => $name,
        'category_id' => guess_off_category($p['categories_tags'] ?? [], $pdo),
        'description' => isset($p['ingredients_text'])
                            ? trim($p['ingredients_text'])
                            : null,
        'image_path'  => $p['image_front_url'] ?? null,
        'region'      => extract_off_region($p['countries_tags'] ?? []),
        'allergens'   => map_off_allergens($p['allergens_tags'] ?? [], $pdo),
        // Codul de bare — îl trimitem ca informație ajutătoare în UI,
        // dar nu există un câmp pentru el în schema DB
        '_barcode'    => $p['code'] ?? null,
    ];
}

// ============================================================
// UTILITAR: Potrivire categorie
// ============================================================

/**
 * guess_off_category(array $tags, PDO $pdo): ?int
 *
 * Încearcă să potrivească tag-urile de categorie OFF cu categoriile din DB.
 * OFF trimite tag-uri de forma "en:teas", "en:herbal-teas" etc.
 * Facem o potrivire fuzzy bazată pe cuvinte cheie.
 */
function guess_off_category(array $tags, PDO $pdo): ?int {
    if (!$tags) return null;

    // Cuvinte cheie (în tag-ul OFF) → numele categoriei în DB
    $keywordMap = [
        'tea'      => 'Ceaiuri',
        'herbal'   => 'Ceaiuri',
        'infusion' => 'Ceaiuri',
        'tisane'   => 'Ceaiuri',
        'milk'     => 'Lactate',
        'dairy'    => 'Lactate',
        'kefir'    => 'Lactate',
        'yogurt'   => 'Lactate',
        'juice'    => 'Sucuri',
        'nectar'   => 'Sucuri',
        'soda'     => 'Sucuri',
        'lemonade' => 'Sucuri',
        'drink'    => 'Sucuri',
        'syrup'    => 'Siropuri',
        'cordial'  => 'Siropuri',
        'water'    => 'Ape',
    ];

    foreach ($tags as $tag) {
        // Extragem partea după ":" (ex: "en:teas" → "teas")
        $val = strtolower(explode(':', $tag)[1] ?? $tag);

        foreach ($keywordMap as $keyword => $catName) {
            if (str_contains($val, $keyword)) {
                // Căutăm categoria în DB după nume
                $stmt = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
                $stmt->execute([$catName]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) return (int) $row['id'];
            }
        }
    }

    return null;
}

// ============================================================
// UTILITAR: Extragere regiune
// ============================================================

/**
 * extract_off_region(array $tags): ?string
 *
 * OFF trimite țările ca "en:romania", "en:france" etc.
 * Transformăm primul tag într-un string lizibil.
 */
function extract_off_region(array $tags): ?string {
    if (!$tags) return null;

    // Luăm prima țară din listă
    $parts = explode(':', $tags[0]);
    $raw   = end($parts); // Ex: "romania"

    // Traducem câteva țări comune în română — restul rămân în engleză
    $translate = [
        'romania'        => 'România',
        'france'         => 'Franța',
        'germany'        => 'Germania',
        'italy'          => 'Italia',
        'spain'          => 'Spania',
        'united-kingdom' => 'Regatul Unit',
        'united-states'  => 'SUA',
    ];

    return $translate[$raw] ?? ucfirst(str_replace('-', ' ', $raw));
}

// ============================================================
// UTILITAR: Potrivire alergeni
// ============================================================

/**
 * map_off_allergens(array $tags, PDO $pdo): array
 *
 * Transformă tag-urile de alergeni OFF în array-ul de IDs din DB.
 * Formatul returnat: [{"id": 1}, {"id": 3}] — exact ce așteaptă BeverageCreateDto.
 *
 * Tag-uri OFF arată ca: ["en:gluten", "en:milk", "en:nuts"]
 */
function map_off_allergens(array $tags, PDO $pdo): array {
    if (!$tags) return [];

    // Mapare tag OFF → substring de potrivit în coloana `name` din tabela allergens
    $offMap = [
        'gluten'    => 'Gluten',
        'milk'      => 'Lactoze',
        'lactose'   => 'Lactoze',
        'nuts'      => 'Nuci',
        'soybeans'  => 'Soia',
        'soy'       => 'Soia',
        'sulphites' => 'Sulfiti',
        'sulfites'  => 'Sulfiti',
        'peanuts'   => 'Arahide',
        'eggs'      => 'Oua',
    ];

    $result = [];
    $seen   = []; // Evităm duplicatele (ex: "milk" și "lactose" → același alergen)

    foreach ($tags as $tag) {
        $val = strtolower(explode(':', $tag)[1] ?? $tag);

        foreach ($offMap as $keyword => $dbName) {
            if (str_contains($val, $keyword) && !in_array($dbName, $seen)) {
                $stmt = $pdo->prepare('SELECT id FROM allergens WHERE name = ?');
                $stmt->execute([$dbName]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $result[] = ['id' => (int) $row['id']];
                    $seen[]   = $dbName;
                }
            }
        }
    }

    return $result;
}
