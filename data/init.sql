-- ============================================================
-- Beverages App - Schema initiala SQLite
-- ============================================================

PRAGMA foreign_keys = ON;

-- ------------------------------------------------------------
-- Utilizatori
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    username         TEXT    NOT NULL UNIQUE,
    email            TEXT    NOT NULL UNIQUE,
    password_hash    TEXT    NOT NULL,
    role             TEXT    NOT NULL DEFAULT 'user', -- 'user' | 'admin'
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Grupuri
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS groups (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    created_by  INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS group_members (
    group_id    INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    PRIMARY KEY (group_id, user_id)
);

-- ------------------------------------------------------------
-- Categorii
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL UNIQUE,
    description TEXT
);

-- ------------------------------------------------------------
-- Bauturi
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS beverages (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    name                TEXT    NOT NULL,
    category_id         INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    price               REAL,
    description         TEXT,
    image_path          TEXT,
    perishability_days  INTEGER,
    season              TEXT,   -- 'spring' | 'summer' | 'autumn' | 'winter' | 'all'
    region              TEXT,
    created_by          INTEGER REFERENCES users(id) ON DELETE SET NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Ingrediente
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ingredients (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS beverage_ingredients (
    beverage_id     INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    ingredient_id   INTEGER NOT NULL REFERENCES ingredients(id) ON DELETE CASCADE,
    quantity        TEXT,
    PRIMARY KEY (beverage_id, ingredient_id)
);

-- ------------------------------------------------------------
-- Alergeni
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS allergens (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS beverage_allergens (
    beverage_id  INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    allergen_id  INTEGER NOT NULL REFERENCES allergens(id) ON DELETE CASCADE,
    PRIMARY KEY (beverage_id, allergen_id)
);

-- ------------------------------------------------------------
-- Localuri (venues)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS venues (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    name     TEXT NOT NULL,
    location TEXT,
    contact  TEXT
);

CREATE TABLE IF NOT EXISTS beverage_venues (
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    venue_id    INTEGER NOT NULL REFERENCES venues(id) ON DELETE CASCADE,
    PRIMARY KEY (beverage_id, venue_id)
);

-- ------------------------------------------------------------
-- Liste de cumparaturi
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shopping_lists (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER REFERENCES users(id) ON DELETE CASCADE,
    group_id   INTEGER REFERENCES groups(id) ON DELETE CASCADE,  -- NULL = lista personala
    name       TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shopping_list_items (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    list_id     INTEGER NOT NULL REFERENCES shopping_lists(id) ON DELETE CASCADE,
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    quantity    INTEGER NOT NULL DEFAULT 1,
    checked     INTEGER NOT NULL DEFAULT 0  -- 0 = necumparat, 1 = cumparat
);

-- ------------------------------------------------------------
-- Preferinte individuale
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_preferences (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    rating      INTEGER CHECK(rating BETWEEN 1 AND 5),
    notes       TEXT,
    UNIQUE(user_id, beverage_id)
);

-- ------------------------------------------------------------
-- Preferinte de grup
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS group_preferences (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id    INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    rating      INTEGER CHECK(rating BETWEEN 1 AND 5),
    notes       TEXT,
    UNIQUE(group_id, beverage_id)
);

-- ------------------------------------------------------------
-- Date initiale: admin default
-- ------------------------------------------------------------
-- Parola default: admin123 (schimba imediat dupa primul login)
INSERT OR IGNORE INTO users (username, email, password_hash, role)
VALUES (
    'admin',
    'admin@beverages.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- parola: password
    'admin'
);

-- Cateva categorii initiale
INSERT OR IGNORE INTO categories (name, description) VALUES
    ('Ceaiuri',  'Ceaiuri din plante, fructe sau alte ingrediente naturale'),
    ('Lactate',  'Lapte, iaurt de baut, kefir si alte produse lactate'),
    ('Sucuri',   'Sucuri naturale sau din concentrate, fresh-uri'),
    ('Siropuri', 'Siropuri pentru diluat sau pentru aroma'),
    ('Ape',      'Ape plate, minerale, carbogazoase');
