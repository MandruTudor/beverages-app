-- ============================================================
-- seed_demo.sql
-- Date demo suplimentare: categorii, bauturi, ingrediente,
-- localuri, utilizatori, preferinte, liste de cumparaturi.
-- Toate INSERT OR IGNORE => safe la rulare repetata.
-- Parola tuturor utilizatorilor demo: password
-- ============================================================

-- Categorii noi
INSERT OR IGNORE INTO categories (name, description) VALUES
    ('Smoothie-uri',        'Bauturi mixate din fructe si legume proaspete'),
    ('Cafea si Cacao',      'Cafea, cacao si bauturi calde aromatice'),
    ('Kombucha',            'Bauturi fermentate din ceai cu probiotice'),
    ('Limonade artizanale', 'Limonade preparate artizanal cu ingrediente naturale'),
    ('Infuzii speciale',    'Infuzii de plante si flori exotice');

-- Ingrediente noi
INSERT OR IGNORE INTO ingredients (name) VALUES
    ('Mango'),('Ananas'),('Capsuni'),('Afine'),('Rodie'),
    ('Cocos'),('Scortisoara'),('Cardamon'),('Turmeric'),('Lavanda'),
    ('Banane'),('Spanac'),('Sfecla'),('Morcov'),('Grapefruit'),
    ('Hibiscus'),('Matcha'),('Cafea'),('Cacao'),('Tei');

-- Localuri
INSERT OR IGNORE INTO venues (name, location, contact) VALUES
    ('Cafe Central',  'Calea Victoriei 45, Bucuresti',       'office@cafecentral.ro'),
    ('Tea House',     'Str. Memorandumului 12, Cluj-Napoca',  '0264-123456'),
    ('Fresh Bar',     'Str. Lapusneanu 8, Iasi',             'contact@freshbar.ro'),
    ('Juice Corner',  'Bulevardul Revolutiei 20, Timisoara',  '0256-789012'),
    ('Green Garden',  'Str. Muresenilor 5, Brasov',          'info@greengarden.ro');

-- ============================================================
-- Bauturi noi
-- ============================================================

-- Ceaiuri
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Ceai verde Matcha',id,12.00,'Ceai verde japonez cu aroma intensa de matcha',2,'all','Japonia',1 FROM categories WHERE name='Ceaiuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Ceai de lavanda',id,9.00,'Ceai relaxant cu flori de lavanda',2,'spring','Franta',1 FROM categories WHERE name='Ceaiuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Ceai de hibiscus',id,8.50,'Ceai racoritor bogat in vitamina C',2,'summer','Romania',1 FROM categories WHERE name='Ceaiuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Ceai Earl Grey',id,8.00,'Ceai negru aromatizat cu bergamota',2,'all','Anglia',1 FROM categories WHERE name='Ceaiuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Ceai de tei',id,7.00,'Ceai traditional romanesc din flori de tei',2,'summer','Romania',1 FROM categories WHERE name='Ceaiuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Ceai de rodie',id,10.00,'Ceai cu extract de rodie si antioxidanti',2,'winter','Romania',1 FROM categories WHERE name='Ceaiuri';

-- Lactate
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Lapte de cocos',id,11.00,'Lapte vegetal din cocos - alternativa fara lactoza',3,'all','Thailanda',1 FROM categories WHERE name='Lactate';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Iaurt de baut cu capsuni',id,8.50,'Iaurt lichid cu piure de capsuni proaspete',5,'spring','Romania',1 FROM categories WHERE name='Lactate';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Chefir cu fructe',id,9.00,'Chefir probiotic cu mix de fructe de padure',7,'all','Romania',1 FROM categories WHERE name='Lactate';

-- Sucuri
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Suc de ananas',id,11.00,'Suc natural din ananas proaspat',1,'summer','Romania',1 FROM categories WHERE name='Sucuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Suc de mango',id,13.00,'Suc exotic din mango copt',1,'summer','Romania',1 FROM categories WHERE name='Sucuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Suc de afine',id,12.00,'Suc bogat in antioxidanti din afine romanesti',1,'autumn','Romania',1 FROM categories WHERE name='Sucuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Suc de rodie',id,15.00,'Suc de rodie presat la rece',1,'winter','Romania',1 FROM categories WHERE name='Sucuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Suc de sfecla cu morcov',id,10.00,'Mix detox din sfecla rosie si morcov',1,'all','Romania',1 FROM categories WHERE name='Sucuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Suc de grapefruit',id,11.50,'Suc proaspat stors din grapefruit',1,'winter','Romania',1 FROM categories WHERE name='Sucuri';

-- Siropuri
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Sirop de lavanda',id,18.00,'Sirop floral din lavanda proaspata',180,'spring','Franta',1 FROM categories WHERE name='Siropuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Sirop de ghimbir',id,16.00,'Sirop picant din ghimbir proaspat',180,'winter','Romania',1 FROM categories WHERE name='Siropuri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Sirop de mentol',id,14.00,'Sirop racoritor de menta concentrat',180,'summer','Romania',1 FROM categories WHERE name='Siropuri';

-- Ape
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Apa cu lamaie si menta',id,6.00,'Apa infuzata cu lamaie si menta proaspata',1,'summer','Romania',1 FROM categories WHERE name='Ape';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Apa cu castravete',id,5.50,'Apa infuzata cu castravete si ierburi aromatice',1,'summer','Romania',1 FROM categories WHERE name='Ape';

-- Smoothie-uri
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Smoothie Mango Ananas',id,16.00,'Smoothie tropical cu mango si ananas',1,'summer','Romania',1 FROM categories WHERE name='Smoothie-uri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Smoothie Afine Banane',id,14.00,'Smoothie cremos cu afine si banane',1,'all','Romania',1 FROM categories WHERE name='Smoothie-uri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Smoothie Capsuni Lapte',id,13.00,'Smoothie dulce cu capsuni si lapte',1,'spring','Romania',1 FROM categories WHERE name='Smoothie-uri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Smoothie Verde Detox',id,15.00,'Smoothie verde cu spanac, mar si ghimbir',1,'all','Romania',1 FROM categories WHERE name='Smoothie-uri';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Smoothie Cocos Ananas',id,17.00,'Smoothie tropical cremos cu cocos si ananas',1,'summer','Romania',1 FROM categories WHERE name='Smoothie-uri';

-- Cafea si Cacao
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Cafea cu lapte rece',id,14.00,'Cold brew racoritor cu lapte de vaca',1,'summer','Romania',1 FROM categories WHERE name='Cafea si Cacao';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Cacao belgiana calda',id,13.00,'Cacao calda preparata din pudra premium belgiana',1,'winter','Belgia',1 FROM categories WHERE name='Cafea si Cacao';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Cafea cu cardamon',id,12.00,'Cafea araba aromatizata cu cardamon si condimente',1,'all','Arabia',1 FROM categories WHERE name='Cafea si Cacao';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Cafea cu scortisoara',id,11.00,'Cafea aromata cu scortisoara si nucsoara',1,'winter','Romania',1 FROM categories WHERE name='Cafea si Cacao';

-- Kombucha
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Kombucha Ghimbir Lamaie',id,18.00,'Kombucha fermentat cu ghimbir si lamaie',30,'all','Romania',1 FROM categories WHERE name='Kombucha';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Kombucha cu Afine',id,17.00,'Kombucha probiotic cu afine de padure',30,'autumn','Romania',1 FROM categories WHERE name='Kombucha';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Kombucha Rodie Hibiscus',id,19.00,'Kombucha premium cu rodie si hibiscus',30,'winter','Romania',1 FROM categories WHERE name='Kombucha';

-- Limonade artizanale
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Limonada cu Afine',id,12.00,'Limonada artizanala cu afine proaspete',1,'summer','Romania',1 FROM categories WHERE name='Limonade artizanale';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Limonada cu Capsuni',id,12.00,'Limonada dulce-acrisoara cu capsuni de sezon',1,'spring','Romania',1 FROM categories WHERE name='Limonade artizanale';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Limonada cu Lavanda',id,13.00,'Limonada florala cu sirop de lavanda',1,'summer','Franta',1 FROM categories WHERE name='Limonade artizanale';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Limonada cu Rodie',id,14.00,'Limonada premium cu suc de rodie',1,'autumn','Romania',1 FROM categories WHERE name='Limonade artizanale';

-- Infuzii speciale
INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Infuzie Turmeric Ghimbir',id,11.00,'Infuzie antiinflamatoare cu turmeric si ghimbir',2,'all','India',1 FROM categories WHERE name='Infuzii speciale';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Infuzie Musetel Lavanda',id,9.50,'Infuzie calmanta din musetel si lavanda',2,'all','Romania',1 FROM categories WHERE name='Infuzii speciale';

INSERT OR IGNORE INTO beverages (name,category_id,price,description,perishability_days,season,region,created_by)
SELECT 'Infuzie de Hibiscus',id,10.00,'Infuzie bogata in vitamina C din hibiscus uscat',2,'summer','Romania',1 FROM categories WHERE name='Infuzii speciale';

-- ============================================================
-- Asocieri bautura-ingredient
-- ============================================================

INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Ceai verde Matcha' AND i.name='Matcha';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Ceai de lavanda' AND i.name='Lavanda';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Ceai de hibiscus' AND i.name='Hibiscus';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Ceai de tei' AND i.name='Tei';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Ceai de rodie' AND i.name='Rodie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Lapte de cocos' AND i.name='Cocos';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Iaurt de baut cu capsuni' AND i.name='Capsuni';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Iaurt de baut cu capsuni' AND i.name='Lapte';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Chefir cu fructe' AND i.name='Afine';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Suc de ananas' AND i.name='Ananas';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Suc de mango' AND i.name='Mango';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Suc de afine' AND i.name='Afine';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Suc de rodie' AND i.name='Rodie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Suc de sfecla cu morcov' AND i.name='Sfecla';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Suc de sfecla cu morcov' AND i.name='Morcov';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Suc de grapefruit' AND i.name='Grapefruit';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Sirop de lavanda' AND i.name='Lavanda';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Sirop de ghimbir' AND i.name='Ghimbir';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Sirop de mentol' AND i.name='Menta';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Apa cu lamaie si menta' AND i.name='Lamaie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Apa cu lamaie si menta' AND i.name='Menta';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Mango Ananas' AND i.name='Mango';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Mango Ananas' AND i.name='Ananas';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Afine Banane' AND i.name='Afine';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Afine Banane' AND i.name='Banane';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Capsuni Lapte' AND i.name='Capsuni';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Capsuni Lapte' AND i.name='Lapte';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Verde Detox' AND i.name='Spanac';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Verde Detox' AND i.name='Mere';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Verde Detox' AND i.name='Ghimbir';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Cocos Ananas' AND i.name='Cocos';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Smoothie Cocos Ananas' AND i.name='Ananas';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Cafea cu lapte rece' AND i.name='Cafea';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Cafea cu lapte rece' AND i.name='Lapte';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Cacao belgiana calda' AND i.name='Cacao';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Cacao belgiana calda' AND i.name='Lapte';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Cafea cu cardamon' AND i.name='Cafea';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Cafea cu cardamon' AND i.name='Cardamon';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Cafea cu scortisoara' AND i.name='Cafea';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Cafea cu scortisoara' AND i.name='Scortisoara';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Kombucha Ghimbir Lamaie' AND i.name='Ghimbir';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Kombucha Ghimbir Lamaie' AND i.name='Lamaie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Kombucha cu Afine' AND i.name='Afine';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Kombucha Rodie Hibiscus' AND i.name='Rodie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Kombucha Rodie Hibiscus' AND i.name='Hibiscus';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Limonada cu Afine' AND i.name='Lamaie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Limonada cu Afine' AND i.name='Afine';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Limonada cu Capsuni' AND i.name='Lamaie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Limonada cu Capsuni' AND i.name='Capsuni';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Limonada cu Lavanda' AND i.name='Lamaie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Limonada cu Lavanda' AND i.name='Lavanda';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Limonada cu Rodie' AND i.name='Lamaie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Limonada cu Rodie' AND i.name='Rodie';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Infuzie Turmeric Ghimbir' AND i.name='Turmeric';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Infuzie Turmeric Ghimbir' AND i.name='Ghimbir';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Infuzie Musetel Lavanda' AND i.name='Musetel';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Infuzie Musetel Lavanda' AND i.name='Lavanda';
INSERT OR IGNORE INTO beverage_ingredients(beverage_id,ingredient_id) SELECT b.id,i.id FROM beverages b,ingredients i WHERE b.name='Infuzie de Hibiscus' AND i.name='Hibiscus';

-- ============================================================
-- Asocieri bautura-alergen
-- ============================================================

INSERT OR IGNORE INTO beverage_allergens(beverage_id,allergen_id) SELECT b.id,a.id FROM beverages b,allergens a WHERE b.name='Iaurt de baut cu capsuni' AND a.name='Lactoze';
INSERT OR IGNORE INTO beverage_allergens(beverage_id,allergen_id) SELECT b.id,a.id FROM beverages b,allergens a WHERE b.name='Chefir cu fructe' AND a.name='Lactoze';
INSERT OR IGNORE INTO beverage_allergens(beverage_id,allergen_id) SELECT b.id,a.id FROM beverages b,allergens a WHERE b.name='Smoothie Capsuni Lapte' AND a.name='Lactoze';
INSERT OR IGNORE INTO beverage_allergens(beverage_id,allergen_id) SELECT b.id,a.id FROM beverages b,allergens a WHERE b.name='Cafea cu lapte rece' AND a.name='Lactoze';
INSERT OR IGNORE INTO beverage_allergens(beverage_id,allergen_id) SELECT b.id,a.id FROM beverages b,allergens a WHERE b.name='Cacao belgiana calda' AND a.name='Lactoze';

-- ============================================================
-- Asocieri bautura-locatie
-- ============================================================

INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Ceai verde Matcha' AND v.name='Tea House';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Ceai de lavanda' AND v.name='Tea House';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Ceai de lavanda' AND v.name='Green Garden';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Ceai Earl Grey' AND v.name='Cafe Central';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Ceai Earl Grey' AND v.name='Tea House';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Iaurt de baut cu capsuni' AND v.name='Fresh Bar';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Suc de mango' AND v.name='Juice Corner';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Suc de mango' AND v.name='Fresh Bar';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Suc de rodie' AND v.name='Juice Corner';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Suc de sfecla cu morcov' AND v.name='Fresh Bar';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Smoothie Mango Ananas' AND v.name='Fresh Bar';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Smoothie Mango Ananas' AND v.name='Juice Corner';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Smoothie Afine Banane' AND v.name='Fresh Bar';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Smoothie Capsuni Lapte' AND v.name='Green Garden';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Smoothie Verde Detox' AND v.name='Juice Corner';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Cafea cu lapte rece' AND v.name='Cafe Central';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Cacao belgiana calda' AND v.name='Cafe Central';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Cacao belgiana calda' AND v.name='Green Garden';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Cafea cu cardamon' AND v.name='Cafe Central';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Cafea cu scortisoara' AND v.name='Cafe Central';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Kombucha Ghimbir Lamaie' AND v.name='Green Garden';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Kombucha cu Afine' AND v.name='Green Garden';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Limonada cu Afine' AND v.name='Juice Corner';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Limonada cu Afine' AND v.name='Green Garden';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Limonada cu Capsuni' AND v.name='Green Garden';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Limonada cu Lavanda' AND v.name='Cafe Central';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Apa cu lamaie si menta' AND v.name='Green Garden';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Infuzie Musetel Lavanda' AND v.name='Tea House';
INSERT OR IGNORE INTO beverage_venues(beverage_id,venue_id) SELECT b.id,v.id FROM beverages b,venues v WHERE b.name='Infuzie Musetel Lavanda' AND v.name='Green Garden';

-- ============================================================
-- Utilizatori demo (parola: password)
-- ============================================================

INSERT OR IGNORE INTO users (username, email, password_hash, role) VALUES
    ('maria',  'maria@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('ion',    'ion@test.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('elena',  'elena@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('andrei', 'andrei@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
    ('ana',    'ana@test.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- ============================================================
-- Preferinte utilizatori
-- ============================================================

INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Absolut delicios, il comand mereu!' FROM users u,beverages b WHERE u.username='maria' AND b.name='Ceai verde Matcha';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Tropical si racoritor' FROM users u,beverages b WHERE u.username='maria' AND b.name='Smoothie Mango Ananas';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Cea mai buna limonada!' FROM users u,beverages b WHERE u.username='maria' AND b.name='Limonada cu Capsuni';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,3,'Buna dar prea dulce' FROM users u,beverages b WHERE u.username='maria' AND b.name='Cafea cu lapte rece';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Bun pentru relaxare' FROM users u,beverages b WHERE u.username='maria' AND b.name='Ceai de lavanda';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Excelent pentru digestie' FROM users u,beverages b WHERE u.username='ion' AND b.name='Kombucha Ghimbir Lamaie';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Culoare minunata, gust acrisor' FROM users u,beverages b WHERE u.username='ion' AND b.name='Ceai de hibiscus';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Bogat in antioxidanti' FROM users u,beverages b WHERE u.username='ion' AND b.name='Suc de rodie';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,3,'Sanatos dar gustul e puternic' FROM users u,beverages b WHERE u.username='ion' AND b.name='Smoothie Verde Detox';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Racoritor si usor' FROM users u,beverages b WHERE u.username='ion' AND b.name='Suc de afine';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Perfecta pentru iarna!' FROM users u,beverages b WHERE u.username='elena' AND b.name='Cacao belgiana calda';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Relaxant si floral' FROM users u,beverages b WHERE u.username='elena' AND b.name='Ceai de lavanda';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Super cremos!' FROM users u,beverages b WHERE u.username='elena' AND b.name='Iaurt de baut cu capsuni';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Combinatie inedita' FROM users u,beverages b WHERE u.username='elena' AND b.name='Limonada cu Lavanda';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,3,'Bun dar asteptam mai mult' FROM users u,beverages b WHERE u.username='elena' AND b.name='Kombucha cu Afine';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Ma duce cu gandul la calatorii' FROM users u,beverages b WHERE u.username='andrei' AND b.name='Cafea cu cardamon';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Interesant, probiotic' FROM users u,beverages b WHERE u.username='andrei' AND b.name='Kombucha cu Afine';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Tropical si delicios' FROM users u,beverages b WHERE u.username='andrei' AND b.name='Suc de mango';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Clasic si bun' FROM users u,beverages b WHERE u.username='andrei' AND b.name='Ceai Earl Grey';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Imi aduce aminte de vacanta!' FROM users u,beverages b WHERE u.username='ana' AND b.name='Smoothie Cocos Ananas';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Excelent pentru sanatate' FROM users u,beverages b WHERE u.username='ana' AND b.name='Infuzie Turmeric Ghimbir';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Culoare superba, gust perfect' FROM users u,beverages b WHERE u.username='ana' AND b.name='Limonada cu Afine';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,3,'Simplu dar racoritor' FROM users u,beverages b WHERE u.username='ana' AND b.name='Apa cu lamaie si menta';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Relaxant dupa o zi lunga' FROM users u,beverages b WHERE u.username='admin' AND b.name='Ceai de musetel';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,5,'Cel mai bun sirop!' FROM users u,beverages b WHERE u.username='admin' AND b.name='Sirop de zmeura';
INSERT OR IGNORE INTO user_preferences(user_id,beverage_id,rating,notes) SELECT u.id,b.id,4,'Il recomand tuturor' FROM users u,beverages b WHERE u.username='admin' AND b.name='Kombucha Ghimbir Lamaie';

-- ============================================================
-- Liste de cumparaturi demo
-- ============================================================

INSERT OR IGNORE INTO shopping_lists(user_id,name) SELECT id,'Cumparaturi weekend' FROM users WHERE username='maria';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,2 FROM shopping_lists l,beverages b,users u WHERE l.name='Cumparaturi weekend' AND l.user_id=u.id AND u.username='maria' AND b.name='Ceai verde Matcha';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,1 FROM shopping_lists l,beverages b,users u WHERE l.name='Cumparaturi weekend' AND l.user_id=u.id AND u.username='maria' AND b.name='Smoothie Mango Ananas';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,3 FROM shopping_lists l,beverages b,users u WHERE l.name='Cumparaturi weekend' AND l.user_id=u.id AND u.username='maria' AND b.name='Limonada cu Capsuni';

INSERT OR IGNORE INTO shopping_lists(user_id,name) SELECT id,'Lista detox' FROM users WHERE username='ion';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,2 FROM shopping_lists l,beverages b,users u WHERE l.name='Lista detox' AND l.user_id=u.id AND u.username='ion' AND b.name='Kombucha Ghimbir Lamaie';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,1 FROM shopping_lists l,beverages b,users u WHERE l.name='Lista detox' AND l.user_id=u.id AND u.username='ion' AND b.name='Smoothie Verde Detox';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,2 FROM shopping_lists l,beverages b,users u WHERE l.name='Lista detox' AND l.user_id=u.id AND u.username='ion' AND b.name='Suc de sfecla cu morcov';

INSERT OR IGNORE INTO shopping_lists(user_id,name) SELECT id,'Bauturi de iarna' FROM users WHERE username='elena';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,1 FROM shopping_lists l,beverages b,users u WHERE l.name='Bauturi de iarna' AND l.user_id=u.id AND u.username='elena' AND b.name='Cacao belgiana calda';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,2 FROM shopping_lists l,beverages b,users u WHERE l.name='Bauturi de iarna' AND l.user_id=u.id AND u.username='elena' AND b.name='Ceai de tei';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,1 FROM shopping_lists l,beverages b,users u WHERE l.name='Bauturi de iarna' AND l.user_id=u.id AND u.username='elena' AND b.name='Kombucha Rodie Hibiscus';

INSERT OR IGNORE INTO shopping_lists(user_id,name) SELECT id,'Comenzi saptamanale' FROM users WHERE username='andrei';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,5 FROM shopping_lists l,beverages b,users u WHERE l.name='Comenzi saptamanale' AND l.user_id=u.id AND u.username='andrei' AND b.name='Cafea cu cardamon';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,3 FROM shopping_lists l,beverages b,users u WHERE l.name='Comenzi saptamanale' AND l.user_id=u.id AND u.username='andrei' AND b.name='Cafea cu lapte rece';

INSERT OR IGNORE INTO shopping_lists(user_id,name) SELECT id,'Vacation vibes' FROM users WHERE username='ana';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,2 FROM shopping_lists l,beverages b,users u WHERE l.name='Vacation vibes' AND l.user_id=u.id AND u.username='ana' AND b.name='Smoothie Cocos Ananas';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,2 FROM shopping_lists l,beverages b,users u WHERE l.name='Vacation vibes' AND l.user_id=u.id AND u.username='ana' AND b.name='Limonada cu Afine';
INSERT OR IGNORE INTO shopping_list_items(list_id,beverage_id,quantity) SELECT l.id,b.id,3 FROM shopping_lists l,beverages b,users u WHERE l.name='Vacation vibes' AND l.user_id=u.id AND u.username='ana' AND b.name='Suc de ananas';
