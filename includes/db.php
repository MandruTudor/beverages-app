<?php
// ============================================================
// includes/db.php
// Gestionează conexiunea la baza de date SQLite prin PDO.
// Orice fișier care are nevoie de baza de date face:
//   require_once __DIR__ . '/../includes/db.php';
// apoi apelează get_db() pentru a obține conexiunea.
// ============================================================

// Definim o constantă cu calea absolută spre fișierul SQLite.
// __DIR__ este directorul curent al acestui fișier (includes/).
// '/../data/beverages.sqlite' urcă un nivel și intră în data/.
define('DB_PATH', __DIR__ . '/../data/beverages.sqlite');

// Calea spre fișierul SQL cu schema inițială (tabele, date seed).
// Acesta este rulat o singură dată, la prima pornire, când baza
// de date nu există încă pe disc.
define('INIT_SQL_PATH', __DIR__ . '/../data/init.sql');

// ------------------------------------------------------------
// get_db() — returnează instanța PDO (singleton).
// "Singleton" înseamnă că, indiferent de câte ori apelezi
// această funcție în aceeași cerere HTTP, vei primi mereu
// același obiect PDO, nu unul nou de fiecare dată.
// Tipul de retur ': PDO' este declarat explicit (PHP 7+).
// ------------------------------------------------------------
function get_db(): PDO {

    // $pdo este o variabilă statică: supraviețuiește între
    // apeluri succesive ale aceleiași funcții în același request.
    // La primul apel are valoarea null; ulterior reține conexiunea.
    static $pdo = null;

    // Dacă conexiunea există deja, o returnăm imediat.
    // Evităm astfel să deschidem de N ori același fișier SQLite.
    if ($pdo !== null) {
        return $pdo;
    }

    // Verificăm dacă fișierul bazei de date există deja pe disc.
    // Stocăm rezultatul ÎNAINTE să creăm conexiunea, deoarece
    // new PDO('sqlite:...') creează automat fișierul dacă lipsește.
    $db_exists = file_exists(DB_PATH);

    // Blocul try/catch prinde orice eroare PDO la conectare.
    try {

        // Deschidem (sau creăm) fișierul SQLite.
        // DSN-ul (Data Source Name) are formatul 'sqlite:/cale'.
        $pdo = new PDO('sqlite:' . DB_PATH);

        // ERRMODE_EXCEPTION: orice eroare SQL aruncă o excepție
        // în loc să returneze false silențios. Mult mai ușor de depanat.
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // FETCH_ASSOC: rezultatele SELECT vor fi array-uri asociative
        // (coloane după nume), nu indexate numeric — cod mai lizibil.
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // SQLite dezactivează implicit cheile externe (FOREIGN KEYS).
        // PRAGMA le activează: acum ON DELETE CASCADE/SET NULL etc. funcționează.
        // exec() rulează SQL care nu returnează rezultate.
        $pdo->exec('PRAGMA foreign_keys = ON;');

        // Dacă baza de date nu exista înainte (tocmai a fost creată),
        // rulăm schema inițială din fișierul init.sql.
        // file_exists(INIT_SQL_PATH) previne eroarea dacă fișierul SQL lipsește.
        if (!$db_exists && file_exists(INIT_SQL_PATH)) {

            // Citim tot conținutul fișierului SQL ca string.
            $sql = file_get_contents(INIT_SQL_PATH);

            // Executăm toate instrucțiunile SQL din fișier dintr-o singură dată.
            // init.sql conține CREATE TABLE și INSERT-uri seed.
            $pdo->exec($sql);
        }

    } catch (PDOException $e) {
        // Dacă conectarea eșuează, trimitem HTTP 500 (Internal Server Error).
        http_response_code(500);

        // Răspundem cu JSON de eroare generic — nu expunem mesajul real
        // al excepției ($e->getMessage()) în producție, deoarece poate
        // conține informații sensibile despre server.
        echo json_encode(['error' => 'Database connection failed.']);

        // Oprim execuția PHP imediat — nu mai continuăm cu $pdo = null.
        exit;
    }

    // Returnăm conexiunea PDO creată și stocată în variabila statică.
    return $pdo;
}
