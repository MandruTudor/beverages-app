<?php
/**
 * db.php
 * Conexiunea PDO la baza de date SQLite.
 * Se include o singura data (require_once) in orice fisier care are nevoie de DB.
 */

define('DB_PATH', __DIR__ . '/../data/beverages.sqlite');
define('INIT_SQL_PATH', __DIR__ . '/../data/init.sql');

/**
 * Returneaza instanta PDO (singleton).
 * Creeaza si initializeaza baza de date daca nu exista.
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $db_exists = file_exists(DB_PATH);

    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Activeaza foreign keys (SQLite le are dezactivate by default)
        $pdo->exec('PRAGMA foreign_keys = ON;');

        // Daca baza de date tocmai a fost creata, ruleaza schema initiala
        if (!$db_exists && file_exists(INIT_SQL_PATH)) {
            $sql = file_get_contents(INIT_SQL_PATH);
            $pdo->exec($sql);
        }

    } catch (PDOException $e) {
        // In productie, nu expune detaliile erorii
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed.']);
        exit;
    }

    return $pdo;
}
