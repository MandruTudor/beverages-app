<?php

define('DB_PATH',      __DIR__ . '/../data/beverages.sqlite');
define('INIT_SQL_PATH', __DIR__ . '/../data/init.sql');

function get_db(): PDO {
    // $pdo e variabilă statică: persistă între requesturi pe același fir Apache.
    // O setăm DOAR după ce toată inițializarea reușește — dacă eșuează rămâne null
    // și requestul următor reîncearcă de la zero (nu returnează o conexiune parțial inițializată).
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $db_exists = file_exists(DB_PATH);

    try {
        // Folosim o variabilă locală $conn; $pdo primește valoarea abia la final
        $conn = new PDO('sqlite:' . DB_PATH);
        $conn->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // busy_timeout PRIMUL — protejează toate operațiunile ulterioare, inclusiv WAL.
        // Dacă DB-ul e blocat, PDO așteaptă până la 10s înainte de a arunca excepție.
        // Fără asta, PRAGMA journal_mode = WAL de mai jos poate eșua imediat cu SQLITE_BUSY
        // dacă un alt thread Apache încearcă simultan să deschidă conexiunea.
        $conn->exec('PRAGMA busy_timeout = 10000;');
        // WAL: permite citiri și scrieri simultane pe thread-uri diferite (esențial pe XAMPP).
        // Persistă pe fișierul .sqlite — se setează o dată, rămâne permanent.
        $conn->exec('PRAGMA journal_mode = WAL;');
        $conn->exec('PRAGMA foreign_keys = ON;');

        if (!$db_exists && file_exists(INIT_SQL_PATH)) {
            $conn->exec(file_get_contents(INIT_SQL_PATH));
        }

        // Grupăm toate migrările într-o singură tranzacție:
        // - Un singur lock de scriere în loc de N lock-uri separate
        // - Dacă ceva eșuează, rollback atomic (DB rămâne consistent)
        $conn->beginTransaction();

        // ALTER TABLE eșuează dacă coloana există deja — prins separat, ignorat intenționat
        try { $conn->exec('ALTER TABLE beverages ADD COLUMN valid_until DATE'); } catch (PDOException) {}

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

        // Totul a reușit — acum setăm $pdo (static) pentru requesturile ulterioare
        $pdo = $conn;

    } catch (PDOException $e) {
        // Nu setăm $pdo → requestul următor reîncearcă inițializarea de la zero
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }

    return $pdo;
}
