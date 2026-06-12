<?php

define('DB_PATH',      __DIR__ . '/../data/beverages.sqlite');
define('INIT_SQL_PATH', __DIR__ . '/../data/init.sql');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $db_exists = file_exists(DB_PATH);

    try {
        $conn = new PDO('sqlite:' . DB_PATH);
        $conn->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $conn->exec('PRAGMA busy_timeout = 10000;');
        
        $conn->exec('PRAGMA journal_mode = WAL;');
        $conn->exec('PRAGMA foreign_keys = ON;');

        if (!$db_exists && file_exists(INIT_SQL_PATH)) {
            $conn->exec(file_get_contents(INIT_SQL_PATH));
        }

        $migrated = (bool)$conn->query(
            "SELECT 1 FROM sqlite_master WHERE type='table' AND name='dietary_regimes'"
        )->fetchColumn();

        if (!$migrated) {
            $conn->beginTransaction();

            $has_valid_until = (bool)$conn->query(
                "SELECT 1 FROM pragma_table_info('beverages') WHERE name='valid_until'"
            )->fetchColumn();
            if (!$has_valid_until) {
                $conn->exec('ALTER TABLE beverages ADD COLUMN valid_until DATE');
            }

            $conn->exec('CREATE TABLE IF NOT EXISTS dietary_regimes (
                id   INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE
            )');
            $conn->exec('CREATE TABLE IF NOT EXISTS beverage_dietary_regimes (
                beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
                regime_id   INTEGER NOT NULL REFERENCES dietary_regimes(id) ON DELETE CASCADE,
                PRIMARY KEY (beverage_id, regime_id)
            )');

            $stmt = $conn->prepare('INSERT OR IGNORE INTO dietary_regimes (name) VALUES (?)');
            foreach (['Vegan','Vegetarian','Fara Gluten','Fara Zahar','Fara Lactoza','Keto','Bio','Fara Cofeina'] as $r) {
                $stmt->execute([$r]);
            }

            $stmt = $conn->prepare('INSERT OR IGNORE INTO allergens (name) VALUES (?)');
            foreach (['Gluten','Lactoze','Nuci','Soia','Sulfiti','Arahide','Oua'] as $name) {
                $stmt->execute([$name]);
            }

            $stmt = $conn->prepare('
                INSERT OR IGNORE INTO beverage_allergens (beverage_id, allergen_id)
                SELECT b.id, a.id FROM beverages b, allergens a WHERE b.name = ? AND a.name = ?
            ');
            foreach ([
                ['Lapte de vanilie','Lactoze'], ['Kefir natural','Lactoze'],
                ['Ceai de musetel','Sulfiti'],  ['Sirop de zmeura','Sulfiti'],
                ['Ceai de ghimbir','Sulfiti'],
            ] as [$b, $a]) {
                $stmt->execute([$b, $a]);
            }

            $conn->commit();
        }

        $pdo = $conn;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }

    return $pdo;
}
