BEGIN;

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER  PRIMARY KEY AUTOINCREMENT,
    username      TEXT     NOT NULL UNIQUE,
    email         TEXT     NOT NULL UNIQUE,
    password_hash TEXT     NOT NULL,
    role          TEXT     NOT NULL DEFAULT 'user',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS groups (
    id         INTEGER  PRIMARY KEY AUTOINCREMENT,
    name       TEXT     NOT NULL,
    created_by INTEGER  NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS group_members (
    group_id INTEGER NOT NULL REFERENCES groups(id) ON DELETE CASCADE,
    user_id  INTEGER NOT NULL REFERENCES users(id)  ON DELETE CASCADE,
    PRIMARY KEY (group_id, user_id)
);

CREATE TABLE IF NOT EXISTS categories (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL UNIQUE,
    description TEXT
);


CREATE TABLE IF NOT EXISTS beverages (
    id                 INTEGER  PRIMARY KEY AUTOINCREMENT,
    name               TEXT     NOT NULL,
    category_id        INTEGER  REFERENCES categories(id) ON DELETE SET NULL,
    price              REAL,
    description        TEXT,
    image_path         TEXT,
    perishability_days INTEGER,
    valid_until        DATE,
    season             TEXT,
    region             TEXT,
    created_by         INTEGER  REFERENCES users(id) ON DELETE SET NULL,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS ingredients (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT    NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS beverage_ingredients (
    beverage_id   INTEGER NOT NULL REFERENCES beverages(id)   ON DELETE CASCADE,
    ingredient_id INTEGER NOT NULL REFERENCES ingredients(id) ON DELETE CASCADE,
    quantity      TEXT,
    PRIMARY KEY (beverage_id, ingredient_id)
);


CREATE TABLE IF NOT EXISTS allergens (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT    NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS beverage_allergens (
    beverage_id INTEGER NOT NULL REFERENCES beverages(id)  ON DELETE CASCADE,
    allergen_id INTEGER NOT NULL REFERENCES allergens(id)  ON DELETE CASCADE,
    PRIMARY KEY (beverage_id, allergen_id)
);


CREATE TABLE IF NOT EXISTS venues (
    id       INTEGER PRIMARY KEY AUTOINCREMENT,
    name     TEXT    NOT NULL,
    location TEXT,
    contact  TEXT
);

CREATE TABLE IF NOT EXISTS beverage_venues (
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    venue_id    INTEGER NOT NULL REFERENCES venues(id)    ON DELETE CASCADE,
    PRIMARY KEY (beverage_id, venue_id)
);


CREATE TABLE IF NOT EXISTS dietary_regimes (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT    NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS beverage_dietary_regimes (
    beverage_id INTEGER NOT NULL REFERENCES beverages(id)       ON DELETE CASCADE,
    regime_id   INTEGER NOT NULL REFERENCES dietary_regimes(id) ON DELETE CASCADE,
    PRIMARY KEY (beverage_id, regime_id)
);


CREATE TABLE IF NOT EXISTS shopping_lists (
    id         INTEGER  PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER  REFERENCES users(id)  ON DELETE CASCADE,
    group_id   INTEGER  REFERENCES groups(id) ON DELETE CASCADE,
    name       TEXT     NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shopping_list_items (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    list_id     INTEGER NOT NULL REFERENCES shopping_lists(id) ON DELETE CASCADE,
    beverage_id INTEGER NOT NULL REFERENCES beverages(id)      ON DELETE CASCADE,
    quantity    INTEGER NOT NULL DEFAULT 1,
    checked     INTEGER NOT NULL DEFAULT 0
);


CREATE TABLE IF NOT EXISTS user_preferences (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id     INTEGER NOT NULL REFERENCES users(id)     ON DELETE CASCADE,
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    rating      INTEGER CHECK(rating BETWEEN 1 AND 5),
    notes       TEXT,
    UNIQUE(user_id, beverage_id)
);

CREATE TABLE IF NOT EXISTS group_preferences (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    group_id    INTEGER NOT NULL REFERENCES groups(id)    ON DELETE CASCADE,
    beverage_id INTEGER NOT NULL REFERENCES beverages(id) ON DELETE CASCADE,
    rating      INTEGER CHECK(rating BETWEEN 1 AND 5),
    notes       TEXT,
    UNIQUE(group_id, beverage_id)
);



-- ---- Utilizatori (parola pentru toți: 'password') ----
INSERT OR IGNORE INTO users (username, email, password_hash, role) VALUES
    ('admin',   'admin@beverages.local',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
    ('maria_p', 'maria.pop@example.ro',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('ion_c',   'ion.constantin@example.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('elena_m', 'elena.marin@example.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('tudor_b', 'tudor.barbu@example.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('ana_d',   'ana.dumitrescu@example.ro', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- ---- Categorii ----
INSERT OR IGNORE INTO categories (name, description) VALUES
    ('Ceaiuri',       'Ceaiuri din plante, fructe sau alte ingrediente naturale'),
    ('Lactate',       'Lapte, iaurt de baut, kefir si alte produse lactate'),
    ('Sucuri',        'Sucuri naturale sau din concentrate, fresh-uri si nectare'),
    ('Siropuri',      'Siropuri concentrate pentru diluat sau aromatizat'),
    ('Ape',           'Ape plate, minerale, carbogazoase si infuzate'),
    ('Smoothie-uri',  'Bauturi blended din fructe si legume proaspete'),
    ('Infuzii',       'Infuzii din flori, plante si fructe uscate'),
    ('Bauturi Calde', 'Bauturi calde pe baza de lapte, cacao sau condimente');

-- ---- Regimuri alimentare ----
INSERT OR IGNORE INTO dietary_regimes (name) VALUES
    ('Vegan'),
    ('Vegetarian'),
    ('Fara Gluten'),
    ('Fara Zahar'),
    ('Fara Lactoza'),
    ('Keto'),
    ('Bio'),
    ('Fara Cofeina');

-- ---- Alergeni ----
INSERT OR IGNORE INTO allergens (name) VALUES
    ('Gluten'),
    ('Lactoze'),
    ('Nuci'),
    ('Soia'),
    ('Sulfiti'),
    ('Arahide'),
    ('Oua');

-- ---- Ingrediente ----
INSERT OR IGNORE INTO ingredients (name) VALUES
    ('Lamaie'),         -- 1
    ('Menta'),          -- 2
    ('Ghimbir'),        -- 3
    ('Miere'),          -- 4
    ('Musetel'),        -- 5
    ('Lapte'),          -- 6
    ('Vanilie'),        -- 7
    ('Portocale'),      -- 8
    ('Mere'),           -- 9
    ('Zmeura'),         -- 10
    ('Capsuni'),        -- 11
    ('Banana'),         -- 12
    ('Mango'),          -- 13
    ('Morcovi'),        -- 14
    ('Grapefruit'),     -- 15
    ('Piersici'),       -- 16
    ('Rodie'),          -- 17
    ('Fulgi de ovaz'),  -- 18
    ('Migdale'),        -- 19
    ('Lavanda'),        -- 20
    ('Trandafir'),      -- 21
    ('Flori de soc'),   -- 22
    ('Tei'),            -- 23
    ('Cacao pudra'),    -- 24
    ('Scortisoara'),    -- 25
    ('Hibiscus'),       -- 26
    ('Frunze rooibos'), -- 27
    ('Frunze sencha'),  -- 28
    ('Nuca de cocos'),  -- 29
    ('Afine');          -- 30

-- ---- Localuri ----
INSERT OR IGNORE INTO venues (name, location, contact) VALUES
    ('Cafe Central',    'Str. Victoriei 10, Cluj-Napoca',        '0264-123456'),
    ('Tea House',       'Calea Dorobantilor 42, Bucuresti',      '021-345678'),
    ('Green Garden',    'Str. Florilor 5, Brasov',               '0268-234567'),
    ('Bio & More',      'Piata Unirii 3, Iasi',                  '0232-456789'),
    ('Natura Cafe',     'Bulevardul Eroilor 18, Cluj-Napoca',    '0264-654321'),
    ('The Coffee Lab',  'Calea Mosilor 87, Bucuresti',           '021-876543'),
    ('Herbal Corner',   'Str. Eminescu 22, Timisoara',           '0256-789012'),
    ('Fresh Corner',    'Str. Republicii 14, Constanta',         '0241-321654'),
    ('Zen Garden',      'Str. Kogalniceanu 8, Brasov',           '0268-987654'),
    ('Aqua Bar',        'Bulevardul Unirii 55, Bucuresti',       '021-234567');

-- ============================================================
-- BĂUTURI (36 produse)
-- Categorii: 1=Ceaiuri 2=Lactate 3=Sucuri 4=Siropuri
--            5=Ape 6=Smoothie-uri 7=Infuzii 8=Bauturi Calde
-- Sezoane: all / winter / spring / summer / autumn
-- ============================================================
INSERT OR IGNORE INTO beverages (name, category_id, price, description, perishability_days, valid_until, season, region, created_by) VALUES
    -- Ceaiuri (cat 1)
    ('Ceai de musetel',            1,  8.00, 'Ceai relaxant din flori de musetel cules manual. Calmeaza si ajuta somnul.',       2,  '2026-12-31', 'all',    'Romania',        1),
    ('Ceai de ghimbir si lamaie',  1,  9.50, 'Ceai energizant cu ghimbir proaspat si lamaie. Ideal pe timp de iarna.',           2,  '2026-12-31', 'winter', 'Romania',        1),
    ('Ceai verde Sencha',          1, 12.00, 'Ceai verde japonez Sencha de prima recolta, bogat in antioxidanti.',               2,  '2027-06-30', 'spring', 'Japonia',         1),
    ('Ceai negru English Breakfast',1,10.00, 'Amestec clasic de ceaiuri negre pentru o dimineata plina de energie.',             2,  '2027-03-31', 'all',    'Anglia',          1),
    ('Ceai de menta marocan',      1,  8.50, 'Ceai de menta proaspata in stil marocan, parfumat si racoritor.',                  2,  '2026-12-31', 'all',    'Maroc',           1),
    ('Ceai Rooibos African',       1, 11.00, 'Ceai rooibos natural fara cofeina, bogat in minerale si antioxidanti.',           2,  '2027-01-31', 'all',    'Africa de Sud',   2),
    ('Ceai de hibiscus',           1,  9.00, 'Ceai din flori de hibiscus, cu gust acrișor si culoare rosie vibranta.',          2,  '2026-09-30', 'summer', 'Egipt',           2),
    ('Ceai alb Pai Mu Tan',        1, 18.00, 'Ceai alb chinezesc de lux, delicat si floral, cules primavara.',                  2,  '2027-06-30', 'spring', 'China',           2),
    -- Lactate (cat 2)
    ('Lapte de vanilie',           2,  7.00, 'Lapte cald cu aroma naturala de vanilie, catifelat si usor dulce.',               3,  '2026-07-31', 'all',    'Romania',         2),
    ('Kefir natural',              2,  6.50, 'Kefir traditional din lapte integral, probiotice naturale.',                      5,  '2026-07-15', 'all',    'Romania',         2),
    ('Lapte de ovaz',              2,  9.00, 'Bautura vegetala din ovaz, cremoasa si usor dulce, alternativa la lapte.',        7,  '2026-08-31', 'all',    'Germania',        3),
    ('Iaurt de baut cu capsuni',   2,  8.50, 'Iaurt lichid cu capsuni romanesti, racoritor si plin de vitamine.',               5,  '2026-08-31', 'summer', 'Romania',         3),
    ('Lapte de migdale',           2, 10.00, 'Bautura din migdale italiene, usor amaruie si delicata, fara lactoza.',           7,  '2026-09-30', 'all',    'Italia',          3),
    -- Sucuri (cat 3)
    ('Fresh de portocale',         3, 12.00, 'Suc proaspat stors din portocale romanesti, 100% natural, fara conservanti.',    1,   NULL,         'winter', 'Romania',         3),
    ('Suc de mere Transilvan',     3, 10.00, 'Suc natural presat la rece din mere de Transilvania, usor acidulat.',            1,  '2026-11-30', 'autumn', 'Transilvania',    4),
    ('Limonada cu menta',          3, 11.00, 'Limonada racoritoare facuta la comanda, cu menta proaspata si miere.',           1,   NULL,         'summer', 'Romania',         4),
    ('Suc de morcovi si ghimbir',  3,  9.50, 'Suc presat la rece din morcovi si ghimbir, plin de betacaroten.',               1,   NULL,         'all',    'Romania',         4),
    ('Fresh de grapefruit',        3, 11.50, 'Fresh din grapefruit roz, usor amar si foarte racoritor.',                       1,   NULL,         'winter', 'Romania',         4),
    ('Nectar de piersici',         3,  7.00, 'Nectar dens din piersici de Muntenia, dulce si parfumat.',                      1,  '2026-09-30', 'summer', 'Muntenia',        1),
    ('Suc de rodie',               3, 15.00, 'Suc de rodie indian, bogat in antioxidanti si cu gust intens.',                 1,  '2026-11-30', 'autumn', 'India',           1),
    ('Suc de afine si acai',       3, 14.00, 'Amestec de afine romanesti si acai, superfood lichid.',                         1,  '2026-08-31', 'summer', 'Romania',         1),
    -- Siropuri (cat 4)
    ('Sirop de zmeura',            4, 15.00, 'Sirop concentrat din zmeura de padure, cules manual in sezon.',                180,  '2027-01-31', 'summer', 'Romania',         2),
    ('Sirop de lamaie si ghimbir', 4, 14.00, 'Sirop artizanal de lamaie si ghimbir, excelent pentru ceaiuri si limonade.',   180,  '2027-06-30', 'all',    'Romania',         2),
    ('Sirop de lavanda',           4, 19.00, 'Sirop de lavanda din Provence, floral si sofisticat.',                         365,  '2028-01-31', 'spring', 'Franta',          2),
    ('Sirop de trandafir',         4, 17.00, 'Sirop traditional din Valea Trandafirilor, parfumat intens.',                  365,  '2027-12-31', 'spring', 'Bulgaria',        3),
    -- Ape (cat 5)
    ('Apa minerala plata Borsec',  5,  4.00, 'Apa minerala naturala plata din izvoarele Borsec, bogata in minerale.',        365,  '2028-06-30', 'all',    'Romania',         3),
    ('Apa carbogazoasa Harghita',  5,  4.50, 'Apa minerala carbogazoasa din Harghita, racoritoare si digestiva.',            365,  '2028-06-30', 'all',    'Romania',         3),
    ('Apa cu lamaie si ghimbir',   5,  6.00, 'Apa infuzata cu lamaie si ghimbir proaspat, detoxifianta.',                    1,   NULL,         'all',    'Romania',         4),
    ('Apa de cocos',               5,  8.50, 'Apa naturala de cocos tanara din Indonezia, hidratare electrolitilor.',         2,  '2026-12-31', 'summer', 'Indonezia',       4),
    -- Smoothie-uri (cat 6)
    ('Smoothie verde cu spanac',   6, 14.00, 'Smoothie detox cu mere, spanac, lamaie si menta, energizant matinal.',          1,   NULL,         'summer', 'Romania',         4),
    ('Smoothie de fructe rosii',   6, 13.00, 'Amestec de capsuni, zmeura si afine, bogat in antioxidanti.',                  1,   NULL,         'summer', 'Romania',         4),
    ('Smoothie banana mango',      6, 12.00, 'Smoothie tropical cremos din banana si mango coapte, fara adaos de zahar.',    1,   NULL,         'summer', 'Romania',         1),
    -- Infuzii (cat 7)
    ('Infuzie de flori de soc',    7,  9.00, 'Infuzie din flori de soc de Bucovina, parfumata si racoritoare.',              2,  '2026-06-30', 'spring', 'Bucovina',        1),
    ('Infuzie de tei cu miere',    7,  8.50, 'Infuzie calmanta din flori de tei romanesc, indulcita cu miere de salcam.',   2,  '2026-08-31', 'summer', 'Romania',         1),
    -- Bauturi Calde (cat 8)
    ('Cacao calda mexicana',       8, 11.00, 'Cacao calda preparata dupa reteta mexicana, cu scortisoara si ardei iute.',    1,   NULL,         'winter', 'Mexic',           2),
    ('Lapte cald cu scortisoara',  8,  7.50, 'Lapte cald romanesc cu scortisoara si un strop de miere, reconfortant.',       1,   NULL,         'winter', 'Romania',         2);

-- ============================================================
-- BEVERAGE — ALERGENI
-- ============================================================
INSERT OR IGNORE INTO beverage_allergens (beverage_id, allergen_id)
SELECT b.id, a.id FROM beverages b, allergens a WHERE
    (b.name = 'Ceai de musetel'           AND a.name = 'Sulfiti') OR
    (b.name = 'Ceai de ghimbir si lamaie' AND a.name = 'Sulfiti') OR
    (b.name = 'Lapte de vanilie'          AND a.name = 'Lactoze') OR
    (b.name = 'Kefir natural'             AND a.name = 'Lactoze') OR
    (b.name = 'Lapte de ovaz'             AND a.name = 'Gluten')  OR
    (b.name = 'Iaurt de baut cu capsuni'  AND a.name = 'Lactoze') OR
    (b.name = 'Lapte de migdale'          AND a.name = 'Nuci')    OR
    (b.name = 'Sirop de zmeura'           AND a.name = 'Sulfiti') OR
    (b.name = 'Sirop de lamaie si ghimbir'AND a.name = 'Sulfiti') OR
    (b.name = 'Sirop de lavanda'          AND a.name = 'Sulfiti') OR
    (b.name = 'Sirop de trandafir'        AND a.name = 'Sulfiti') OR
    (b.name = 'Apa de cocos'              AND a.name = 'Nuci')    OR
    (b.name = 'Cacao calda mexicana'      AND a.name = 'Lactoze') OR
    (b.name = 'Lapte cald cu scortisoara' AND a.name = 'Lactoze');

-- ============================================================
-- BEVERAGE — INGREDIENTE
-- ID-uri ingrediente: 1=Lamaie 2=Menta 3=Ghimbir 4=Miere 5=Musetel
--   6=Lapte 7=Vanilie 8=Portocale 9=Mere 10=Zmeura 11=Capsuni
--   12=Banana 13=Mango 14=Morcovi 15=Grapefruit 16=Piersici
--   17=Rodie 18=Fulgi ovaz 19=Migdale 20=Lavanda 21=Trandafir
--   22=Flori soc 23=Tei 24=Cacao pudra 25=Scortisoara 26=Hibiscus
--   27=Frunze rooibos 28=Frunze sencha 29=Nuca cocos 30=Afine
-- ============================================================
INSERT OR IGNORE INTO beverage_ingredients (beverage_id, ingredient_id, quantity) VALUES
    (1,  5, '2g'),          (1,  4, '1 lingurita'),
    (2,  3, '20g'),         (2,  1, '1 felie'),     (2,  4, '1 lingurita'),
    (3,  28,'3g'),
    (5,  2, '5 frunze'),
    (6,  27,'3g'),          (6,  4, '1 lingurita'),
    (7,  26,'5g'),          (7,  4, '1 lingurita'),
    (9,  6, '200ml'),       (9,  7, '1 lingurita'),
    (10, 6, '250ml'),
    (11, 18,'100g'),
    (12, 11,'50g'),
    (13, 19,'100g'),
    (14, 8, '3 buc'),
    (15, 9, '4 buc'),
    (16, 1, '2 buc'),       (16, 2, '5 frunze'),    (16, 4, '2 linguri'),
    (17, 14,'3 buc'),       (17, 3, '10g'),
    (18, 15,'2 buc'),
    (19, 16,'4 buc'),
    (20, 17,'2 buc'),
    (21, 30,'200g'),
    (22, 10,'500g'),        (22, 4, '200g'),
    (23, 1, '5 buc'),       (23, 3, '50g'),
    (24, 20,'30g'),
    (25, 21,'50g'),
    (28, 1, '1 felie'),     (28, 3, '5g'),
    (29, 29,'1 buc'),
    (30, 9, '1 buc'),       (30, 2, '3 frunze'),    (30, 1, '0.5 buc'),
    (31, 11,'100g'),        (31, 10,'100g'),         (31, 30,'50g'),
    (32, 12,'2 buc'),       (32, 13,'1 buc'),
    (33, 22,'10g'),         (33, 4, '1 lingurita'),
    (34, 23,'5g'),          (34, 4, '1 lingurita'),
    (35, 24,'20g'),         (35, 25,'0.5 lingurita'),
    (36, 6, '200ml'),       (36, 25,'1 lingurita'),  (36, 4, '1 lingurita');

-- ============================================================
-- BEVERAGE — REGIMURI ALIMENTARE
-- Regimuri: 1=Vegan 2=Vegetarian 3=Fara Gluten 4=Fara Zahar
--           5=Fara Lactoza 6=Keto 7=Bio 8=Fara Cofeina
-- ============================================================
INSERT OR IGNORE INTO beverage_dietary_regimes (beverage_id, regime_id) VALUES
    -- Ceai musetel: tot exceptand Keto (are miere)
    (1,1),(1,2),(1,3),(1,4),(1,5),(1,7),(1,8),
    -- Ceai ghimbir: vegan, fara gluten, fara zahar, fara lactoza, fara cofeina
    (2,1),(2,2),(2,3),(2,4),(2,5),(2,8),
    -- Ceai verde: vegan, fara gluten, fara zahar, fara lactoza, bio (are cofeina)
    (3,1),(3,2),(3,3),(3,4),(3,5),(3,7),
    -- Ceai negru: vegan, fara gluten, fara zahar, fara lactoza (are cofeina)
    (4,1),(4,2),(4,3),(4,4),(4,5),
    -- Ceai menta: vegan, fara gluten, fara zahar, fara lactoza, fara cofeina
    (5,1),(5,2),(5,3),(5,4),(5,5),(5,8),
    -- Ceai rooibos: vegan, fara gluten, fara zahar, fara lactoza, fara cofeina
    (6,1),(6,2),(6,3),(6,4),(6,5),(6,8),
    -- Ceai hibiscus
    (7,1),(7,2),(7,3),(7,4),(7,5),(7,8),
    -- Ceai alb: vegan, fara gluten, fara zahar, fara lactoza, bio
    (8,1),(8,2),(8,3),(8,4),(8,5),(8,7),
    -- Lapte vanilie: vegetarian
    (9,2),
    -- Kefir: vegetarian
    (10,2),
    -- Lapte ovaz: vegan, vegetarian, fara lactoza
    (11,1),(11,2),(11,5),
    -- Iaurt capsuni: vegetarian
    (12,2),
    -- Lapte migdale: vegan, vegetarian, fara gluten, fara lactoza
    (13,1),(13,2),(13,3),(13,5),
    -- Fresh portocale: vegan, fara gluten, fara zahar, fara lactoza
    (14,1),(14,2),(14,3),(14,4),(14,5),
    -- Suc mere: vegan, fara gluten, fara lactoza, bio
    (15,1),(15,2),(15,3),(15,5),(15,7),
    -- Limonada: vegan, fara gluten, fara lactoza
    (16,1),(16,2),(16,3),(16,5),
    -- Suc morcovi: vegan, fara gluten, fara zahar, fara lactoza
    (17,1),(17,2),(17,3),(17,4),(17,5),
    -- Fresh grapefruit
    (18,1),(18,2),(18,3),(18,4),(18,5),
    -- Nectar piersici
    (19,1),(19,2),(19,3),(19,5),
    -- Suc rodie
    (20,1),(20,2),(20,3),(20,4),(20,5),
    -- Suc afine
    (21,1),(21,2),(21,3),(21,5),
    -- Siropuri: vegan, vegetarian
    (22,1),(22,2),
    (23,1),(23,2),
    -- Sirop lavanda: vegan, vegetarian, bio
    (24,1),(24,2),(24,7),
    -- Sirop trandafir: vegan, vegetarian, bio
    (25,1),(25,2),(25,7),
    -- Ape: toate regimurile inclusiv keto
    (26,1),(26,2),(26,3),(26,4),(26,5),(26,6),
    (27,1),(27,2),(27,3),(27,4),(27,5),(27,6),
    (28,1),(28,2),(28,3),(28,4),(28,5),
    (29,1),(29,2),(29,3),(29,5),
    -- Smoothie-uri: vegan, fara gluten, fara lactoza
    (30,1),(30,2),(30,3),(30,5),
    (31,1),(31,2),(31,3),(31,5),
    (32,1),(32,2),(32,3),(32,5),
    -- Infuzii: vegan, fara gluten, fara zahar, fara lactoza, bio, fara cofeina
    (33,1),(33,2),(33,3),(33,4),(33,5),(33,7),(33,8),
    (34,1),(34,2),(34,3),(34,4),(34,5),(34,7),(34,8),
    -- Bauturi calde cu lapte: vegetarian, fara gluten
    (35,2),(35,3),
    (36,2);

-- ============================================================
-- BEVERAGE — LOCALURI
-- ============================================================
INSERT OR IGNORE INTO beverage_venues (beverage_id, venue_id) VALUES
    (1, 2),(1, 3),(1, 7),
    (2, 2),(2, 7),
    (3, 2),(3, 9),
    (4, 1),(4, 2),(4, 6),
    (5, 2),(5, 4),
    (6, 2),(6, 9),
    (7, 2),(7, 4),(7, 9),
    (8, 2),(8, 9),
    (9, 1),(9, 5),
    (10,4),(10,5),
    (11,4),(11,6),
    (12,4),(12,8),
    (13,4),(13,5),(13,6),
    (14,3),(14,8),
    (15,3),(15,4),(15,8),
    (16,3),(16,8),(16,10),
    (17,4),(17,8),
    (18,3),(18,8),
    (19,8),
    (20,4),(20,6),
    (21,4),(21,5),(21,8),
    (22,3),(22,9),
    (24,1),(24,6),
    (25,1),(25,5),
    (26,1),(26,2),(26,4),(26,5),(26,8),(26,10),
    (27,1),(27,6),(27,10),
    (30,3),(30,5),
    (31,5),(31,8),
    (32,3),(32,5),(32,8),
    (33,3),(33,7),
    (34,7),(34,9),
    (35,1),(35,6),
    (36,1),(36,5);

-- ============================================================
-- GRUPURI
-- ============================================================
INSERT OR IGNORE INTO groups (name, created_by) VALUES
    ('Familia Popescu',  2),
    ('Colegii de birou', 3),
    ('Clubul de ceai',   1);

INSERT OR IGNORE INTO group_members (group_id, user_id) VALUES
    (1, 2),(1, 3),(1, 4),
    (2, 1),(2, 3),(2, 5),(2, 6),
    (3, 1),(3, 2),(3, 5);

-- ============================================================
-- LISTE DE CUMPĂRĂTURI
-- ============================================================
INSERT OR IGNORE INTO shopping_lists (user_id, group_id, name) VALUES
    (2, NULL, 'Cumparaturi saptamanale'),
    (3,    2, 'Bauturi pentru birou'),
    (5, NULL, 'Weekend relaxat'),
    (4, NULL, 'Ceaiuri de iarna'),
    (2,    1, 'Lista familiei');

INSERT OR IGNORE INTO shopping_list_items (list_id, beverage_id, quantity, checked) VALUES
    (1,  1, 2, 0),(1, 13, 1, 1),(1, 15, 3, 0),
    (2, 26, 6, 1),(2,  4, 2, 0),(2, 16, 4, 0),(2, 27, 4, 1),
    (3, 30, 2, 0),(3,  7, 1, 0),(3, 28, 3, 0),
    (4,  2, 3, 0),(4,  6, 2, 0),(4,  5, 2, 1),(4, 34, 2, 0),
    (5, 14, 4, 0),(5,  9, 2, 1),(5, 12, 3, 0),(5, 31, 2, 0);

-- ============================================================
-- PREFERINȚE UTILIZATORI
-- ============================================================
INSERT OR IGNORE INTO user_preferences (user_id, beverage_id, rating, notes) VALUES
    (2,  1, 5, 'Favoritul meu de seara, ma ajuta sa dorm.'),
    (2,  3, 4, 'Ceai verde de calitate, aroma delicata.'),
    (2, 13, 5, 'Excelent pentru regim vegan, textura cremoasa.'),
    (2, 30, 4, 'Bun dimineata inainte de sport.'),
    (2, 34, 5, 'Perfect pentru somn, mai bun decat orice somnifer.'),
    (2,  7, 4, 'Culoarea e minunata, gustul usor acrisor.'),
    (3,  2, 5, 'Cel mai bun ceai pentru iarna, ma incalzeste instant.'),
    (3, 14, 4, 'Fresh proaspat, bun dimineata.'),
    (3, 16, 5, 'Racoritoare! Perfect vara la terasa.'),
    (3, 26, 3, 'Apa buna dar prefer una cu bule.'),
    (3, 27, 4, 'Clasicul de la birou, nu lipseste niciodata.'),
    (3, 17, 4, 'Surprinzator de gustos, ghimbirul face diferenta.'),
    (4,  9, 4, 'Placut si dulce, bun seara.'),
    (4, 10, 3, 'Kefir ok, dar prefer varianta cu fructe.'),
    (4, 12, 5, 'Gustos si racoros, capsunile sunt naturale.'),
    (4, 35, 5, 'Perfecta pentru serile de iarna, scortisoara e secretul.'),
    (4, 33, 4, 'Parfumat si delicat, aduce aminte de vara.'),
    (4, 36, 4, 'Simplu si reconfortant.'),
    (5,  4, 5, 'Dimineata fara ceai negru nu exista. Punct.'),
    (5,  6, 4, 'Rooibos de calitate, gustul e unic.'),
    (5, 24, 3, 'Interesant dar prea parfumat pentru mine.'),
    (5, 32, 4, 'Tropical si satisfacator, bun post-antrenament.'),
    (5,  8, 5, 'Ceai alb de lux, merita fiecare ban.'),
    (5, 20, 4, 'Suc de rodie autentic, intens si bogat.'),
    (6,  7, 5, 'Culoarea si gustul sunt absolut minunate!'),
    (6, 20, 4, 'Exotic si sanatos, il cumpar mereu din deplasari.'),
    (6, 29, 5, 'Revigorant si natural, cel mai bun dupa plaja.'),
    (6, 31, 4, 'Mix perfect de fructe, culoarea e apetisanta.'),
    (6, 21, 5, 'Superaliment lichid, il recomand tuturor.'),
    (6,  3, 4, 'Ceai verde fin, se simte calitatea japoneza.');

-- ============================================================
-- PREFERINȚE GRUPURI
-- ============================================================
INSERT OR IGNORE INTO group_preferences (group_id, beverage_id, rating, notes) VALUES
    (1,  1, 5, 'Toata familia iubeste ceaiul de musetel seara.'),
    (1,  9, 4, 'Copiii adora laptele de vanilie.'),
    (1, 15, 4, 'Suc de mere local, sustinam producatorii romani.'),
    (2,  4, 4, 'Standardul de la birou, consumam zilnic.'),
    (2, 27, 3, 'Apa carbogazoasa pentru intalniri.'),
    (2, 26, 5, 'Toti bem apa plata Borsec la birou.'),
    (3,  3, 5, 'Standardul gold al ceaiurilor verzi.'),
    (3,  6, 4, 'Rooibos pentru serile de discutii.'),
    (3,  7, 5, 'Preferat de toti membrii clubului nostru.'),
    (3,  8, 5, 'Ceaiul alb e o experienta, nu doar o bautura.');

COMMIT;
