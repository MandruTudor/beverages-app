-- ============================================================
-- init.sql — Schema inițială a bazei de date SQLite.
-- Rulat O SINGURĂ DATĂ de db.php când fișierul .sqlite nu există.
-- ============================================================

-- Activăm suportul pentru chei externe (dezactivat by default în SQLite).
-- Fără această linie, ON DELETE CASCADE / SET NULL nu ar funcționa.
PRAGMA foreign_keys = ON;

-- ============================================================
-- Tabelul USERS — utilizatorii aplicației
-- ============================================================
-- IF NOT EXISTS previne eroarea dacă schema e rulată de două ori accidental
CREATE TABLE IF NOT EXISTS users (
    id               INTEGER PRIMARY KEY AUTOINCREMENT, -- ID unic auto-incrementat
    username         TEXT    NOT NULL UNIQUE,           -- Numele de utilizator, unic în sistem
    email            TEXT    NOT NULL UNIQUE,           -- Email unic, folosit la login
    password_hash    TEXT    NOT NULL,                  -- Hash bcrypt al parolei (NICIODATĂ parola clară)
    role             TEXT    NOT NULL DEFAULT 'user',   -- Rolul: 'user' (implicit) sau 'admin'
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP -- Data și ora creării contului
);

-- ============================================================
-- Tabelul GROUPS — grupuri de utilizatori
-- Un grup poate fi creat de orice utilizator autentificat.
-- ============================================================
CREATE TABLE IF NOT EXISTS groups (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,                                      -- Numele grupului
    created_by  INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- Creatorul grupului
    -- ON DELETE CASCADE: dacă userul creator e șters, grupul e șters și el
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabelul de legătură MANY-TO-MANY: un user poate fi în mai multe grupuri,
-- un grup poate avea mai mulți utilizatori.
CREATE TABLE IF NOT EXISTS group_members (
    group_id    INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE, -- Grupul
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,  -- Membrul
    -- Cheia primară compusă: combinația group_id + user_id e unică
    -- (un user nu poate fi adăugat de două ori în același grup)
    PRIMARY KEY (group_id, user_id)
);

-- ============================================================
-- Tabelul CATEGORIES — categorii de băuturi (Ceaiuri, Sucuri etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL UNIQUE, -- Numele categoriei, unic (nu pot fi două "Ceaiuri")
    description TEXT                    -- Descriere opțională (NULL permis)
);

-- ============================================================
-- Tabelul BEVERAGES — produsele principale ale aplicației
-- ============================================================
CREATE TABLE IF NOT EXISTS beverages (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    name                TEXT    NOT NULL,                              -- Numele băuturii
    category_id         INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    -- ON DELETE SET NULL: dacă categoria e ștearsă, category_id devine NULL
    -- (băutura rămâne în sistem, fără categorie)
    price               REAL,    -- Prețul (REAL = virgulă mobilă în SQLite; NULL permis)
    description         TEXT,    -- Descriere liberă
    image_path          TEXT,    -- Calea relativă spre imaginea băuturii (ex: assets/images/ceai.jpg)
    perishability_days  INTEGER, -- Câte zile se poate păstra după deschidere
    season              TEXT,    -- Sezonul disponibil: 'spring'|'summer'|'autumn'|'winter'|'all'
    region              TEXT,    -- Regiunea geografică unde e disponibilă (ex: 'Moldova')
    created_by          INTEGER REFERENCES users(id) ON DELETE SET NULL, -- Cine a adăugat băutura
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Tabelul INGREDIENTS — ingrediente individuale reutilizabile
-- ============================================================
CREATE TABLE IF NOT EXISTS ingredients (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE -- Ex: 'Apă', 'Zahăr', 'Mentă' — unice în sistem
);

-- Tabelul de legătură MANY-TO-MANY: o băutură are mai multe ingrediente,
-- un ingredient poate apărea în mai multe băuturi.
CREATE TABLE IF NOT EXISTS beverage_ingredients (
    beverage_id     INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    -- ON DELETE CASCADE: dacă băutura e ștearsă, și ingredientele asociate ei dispar
    ingredient_id   INTEGER NOT NULL REFERENCES ingredients(id) ON DELETE CASCADE,
    quantity        TEXT,    -- Ex: '200ml', '2 linguri' — opțional, format liber
    PRIMARY KEY (beverage_id, ingredient_id) -- Un ingredient apare o singură dată per băutură
);

-- ============================================================
-- Tabelul ALLERGENS — alergeni (Gluten, Lactoză etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS allergens (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE -- Ex: 'Gluten', 'Lactoză', 'Nuci'
);

-- Legătura MANY-TO-MANY băuturi ↔ alergeni
CREATE TABLE IF NOT EXISTS beverage_allergens (
    beverage_id  INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    allergen_id  INTEGER NOT NULL REFERENCES allergens(id) ON DELETE CASCADE,
    PRIMARY KEY (beverage_id, allergen_id)
);

-- ============================================================
-- Tabelul VENUES — localuri fizice unde se găsesc băuturile
-- ============================================================
CREATE TABLE IF NOT EXISTS venues (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    name     TEXT NOT NULL, -- Numele localului (ex: 'Café Central')
    location TEXT,          -- Adresa sau descrierea locației
    contact  TEXT           -- Telefon, email, website etc.
);

-- Legătura MANY-TO-MANY băuturi ↔ localuri
-- O băutură poate fi la mai multe localuri; un local poate servi mai multe băuturi
CREATE TABLE IF NOT EXISTS beverage_venues (
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    venue_id    INTEGER NOT NULL REFERENCES venues(id) ON DELETE CASCADE,
    PRIMARY KEY (beverage_id, venue_id)
);

-- ============================================================
-- Tabelul SHOPPING_LISTS — liste de cumpărături
-- Poate fi personală (group_id = NULL) sau de grup (group_id setat)
-- ============================================================
CREATE TABLE IF NOT EXISTS shopping_lists (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER REFERENCES users(id) ON DELETE CASCADE,  -- Proprietarul listei
    group_id   INTEGER REFERENCES groups(id) ON DELETE CASCADE, -- NULL = listă personală
    name       TEXT NOT NULL,                                   -- Ex: 'Cumpărături weekend'
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Elementele individuale ale unei liste
CREATE TABLE IF NOT EXISTS shopping_list_items (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    list_id     INTEGER NOT NULL REFERENCES shopping_lists(id) ON DELETE CASCADE,
    -- ON DELETE CASCADE: dacă lista e ștearsă, elementele ei dispar
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    quantity    INTEGER NOT NULL DEFAULT 1,  -- Câte unități din acea băutură
    checked     INTEGER NOT NULL DEFAULT 0   -- 0 = necumpărat, 1 = bifat/cumpărat
    -- SQLite nu are tip BOOLEAN; folosim INTEGER 0/1 (convenție standard)
);

-- ============================================================
-- Tabelul USER_PREFERENCES — preferințele individuale ale utilizatorilor
-- Fiecare user poate acorda un rating și notițe pentru orice băutură.
-- ============================================================
CREATE TABLE IF NOT EXISTS user_preferences (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    rating      INTEGER CHECK(rating BETWEEN 1 AND 5), -- Rating 1-5 stele; CHECK e o constrângere
    notes       TEXT,                                   -- Notiță personală liberă
    UNIQUE(user_id, beverage_id) -- Un user poate evalua o băutură o singură dată
);

-- ============================================================
-- Tabelul GROUP_PREFERENCES — preferințele la nivel de grup
-- Similar cu user_preferences, dar pentru un grup întreg.
-- ============================================================
CREATE TABLE IF NOT EXISTS group_preferences (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id    INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    rating      INTEGER CHECK(rating BETWEEN 1 AND 5),
    notes       TEXT,
    UNIQUE(group_id, beverage_id) -- Un grup evaluează o băutură o singură dată
);

-- ============================================================
-- Date inițiale (seed data)
-- INSERT OR IGNORE: dacă înregistrarea există deja (UNIQUE collision),
-- o ignorăm silențios (nu aruncăm eroare).
-- ============================================================

-- Contul de admin implicit.
-- ATENȚIE: Parola hash-uită de mai jos corespunde șirului 'password'.
-- Schimbă-o imediat după primul login în producție!
INSERT OR IGNORE INTO users (username, email, password_hash, role)
VALUES (
    'admin',
    'admin@beverages.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- hash pentru 'password'
    'admin'
);

-- Categoriile inițiale de băuturi
-- Acestea apar în dropdown-ul de la adăugarea/editarea băuturilor
INSERT OR IGNORE INTO categories (name, description) VALUES
    ('Ceaiuri',  'Ceaiuri din plante, fructe sau alte ingrediente naturale'),
    ('Lactate',  'Lapte, iaurt de baut, kefir si alte produse lactate'),
    ('Sucuri',   'Sucuri naturale sau din concentrate, fresh-uri'),
    ('Siropuri', 'Siropuri pentru diluat sau pentru aroma'),
    ('Ape',      'Ape plate, minerale, carbogazoase');

-- Ingrediente de baza
INSERT OR IGNORE INTO ingredients (name) VALUES
    ('Lamaie'), ('Menta'), ('Ghimbir'), ('Miere'), ('Musetel'),
    ('Lapte'), ('Vanilie'), ('Portocale'), ('Mere'), ('Zmeura');

-- Bauturi de test
INSERT OR IGNORE INTO beverages (name, category_id, price, description, perishability_days, season, region, created_by) VALUES
    ('Ceai de musetel',     1, 8.00,  'Ceai relaxant din flori de musetel',         2,   'all',    'Romania', 1),
    ('Ceai de ghimbir',     1, 9.50,  'Ceai energizant cu ghimbir si lamaie',       2,   'winter', 'Romania', 1),
    ('Lapte de vanilie',    2, 7.00,  'Lapte cald cu aroma de vanilie',             3,   'all',    'Romania', 1),
    ('Kefir natural',       2, 6.50,  'Kefir din lapte integral',                   5,   'all',    'Romania', 1),
    ('Fresh de portocale',  3, 12.00, 'Suc proaspat stors din portocale',           1,   'winter', 'Romania', 1),
    ('Suc de mere',         3, 10.00, 'Suc natural din mere romanesti',             1,   'autumn', 'Romania', 1),
    ('Limonada cu menta',   3, 11.00, 'Limonada racoritoare cu menta proaspata',    1,   'summer', 'Romania', 1),
    ('Sirop de zmeura',     4, 15.00, 'Sirop concentrat din zmeura de padure',      180, 'summer', 'Romania', 1),
    ('Apa minerala plata',  5, 4.00,  'Apa minerala naturala plata',                365, 'all',    'Romania', 1),
    ('Apa carbogazoasa',    5, 4.50,  'Apa minerala carbogazoasa',                  365, 'all',    'Romania', 1);

-- Asocieri ingrediente - bauturi (exemple)
INSERT OR IGNORE INTO beverage_ingredients (beverage_id, ingredient_id, quantity) VALUES
    (1, 5, '2g'),      -- Ceai musetel: musetel
    (2, 4, '100ml'),   -- Ceai ghimbir: ghimbir
    (2, 1, '1 felie'), -- Ceai ghimbir: lamaie
    (3, 6, '200ml'),   -- Lapte vanilie: lapte
    (3, 7, '1 lingurita'), -- Lapte vanilie: vanilie
    (5, 8, '3 buc'),   -- Fresh portocale: portocale
    (6, 9, '4 buc'),   -- Suc mere: mere
    (7, 1, '1 buc'),   -- Limonada: lamaie
    (7, 2, '5 frunze'),-- Limonada: menta
    (8, 10, '500g');   -- Sirop zmeura: zmeura
