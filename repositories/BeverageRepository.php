<?php

class BeverageRepository {
    public function __construct(private PDO $pdo) {}

    public function findAll(array $filters): array {
        $where  = [];
        $params = [];

        if (isset($filters['category_id'])) {
            $where[]  = 'b.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (isset($filters['season'])) {
            $where[]  = 'b.season = ?';
            $params[] = $filters['season'];
        }
        if (isset($filters['region'])) {
            $where[]  = 'b.region = ?';
            $params[] = $filters['region'];
        }
        if (isset($filters['exclude_allergen_id'])) {
            // Excludem băuturile care conțin alergenul selectat
            $where[]  = 'b.id NOT IN (SELECT beverage_id FROM beverage_allergens WHERE allergen_id = ?)';
            $params[] = (int) $filters['exclude_allergen_id'];
        }
        if (isset($filters['dietary_regime_id'])) {
            // Includem DOAR băuturile compatibile cu regimul alimentar selectat
            $where[]  = 'b.id IN (SELECT beverage_id FROM beverage_dietary_regimes WHERE regime_id = ?)';
            $params[] = (int) $filters['dietary_regime_id'];
        }

        // allergen_count și regime_count permit cardurilor să afișeze badge-uri fără request suplimentar
        $sql = 'SELECT b.*, c.name AS category_name,
                (SELECT COUNT(*) FROM beverage_allergens ba WHERE ba.beverage_id = b.id) AS allergen_count,
                (SELECT COUNT(*) FROM beverage_dietary_regimes bdr WHERE bdr.beverage_id = b.id) AS regime_count
                FROM beverages b LEFT JOIN categories c ON c.id = b.category_id';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY b.created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('
            SELECT b.*, c.name AS category_name
            FROM beverages b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE b.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare('
            INSERT INTO beverages (name, category_id, price, description, image_path, perishability_days, valid_until, season, region, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            trim($data['name']),
            $data['category_id']        ?? null,
            $data['price']              ?? null,
            $data['description']        ?? null,
            $data['image_path']         ?? null,
            $data['perishability_days'] ?? null,
            $data['valid_until']        ?? null,
            $data['season']             ?? null,
            $data['region']             ?? null,
            $data['created_by']         ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $fields = [];
        $params = [];

        foreach (['name', 'category_id', 'price', 'description', 'image_path', 'perishability_days', 'valid_until', 'season', 'region'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $col === 'name' ? trim($data[$col]) : $data[$col];
            }
        }

        if ($fields) {
            $params[] = $id;
            $this->pdo->prepare('UPDATE beverages SET ' . implode(', ', $fields) . ' WHERE id = ?')
                      ->execute($params);
        }
    }

    public function delete(int $id): void {
        $this->pdo->prepare('DELETE FROM beverages WHERE id = ?')->execute([$id]);
    }

    public function getIngredients(int $beverage_id): array {
        $stmt = $this->pdo->prepare('
            SELECT i.id, i.name, bi.quantity
            FROM beverage_ingredients bi
            JOIN ingredients i ON i.id = bi.ingredient_id
            WHERE bi.beverage_id = ?
        ');
        $stmt->execute([$beverage_id]);
        return $stmt->fetchAll();
    }

    public function getAllergens(int $beverage_id): array {
        $stmt = $this->pdo->prepare('
            SELECT a.id, a.name
            FROM beverage_allergens ba
            JOIN allergens a ON a.id = ba.allergen_id
            WHERE ba.beverage_id = ?
        ');
        $stmt->execute([$beverage_id]);
        return $stmt->fetchAll();
    }

    public function getVenues(int $beverage_id): array {
        $stmt = $this->pdo->prepare('
            SELECT v.id, v.name, v.location, v.contact
            FROM beverage_venues bv
            JOIN venues v ON v.id = bv.venue_id
            WHERE bv.beverage_id = ?
        ');
        $stmt->execute([$beverage_id]);
        return $stmt->fetchAll();
    }

    public function syncIngredients(int $beverage_id, array $items): void {
        $this->pdo->prepare('DELETE FROM beverage_ingredients WHERE beverage_id = ?')->execute([$beverage_id]);
        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO beverage_ingredients (beverage_id, ingredient_id, quantity) VALUES (?, ?, ?)');
        foreach ($items as $item) {
            $stmt->execute([$beverage_id, (int) $item['id'], $item['quantity'] ?? null]);
        }
    }

    public function syncAllergens(int $beverage_id, array $items): void {
        $this->pdo->prepare('DELETE FROM beverage_allergens WHERE beverage_id = ?')->execute([$beverage_id]);
        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO beverage_allergens (beverage_id, allergen_id) VALUES (?, ?)');
        foreach ($items as $item) {
            $stmt->execute([$beverage_id, (int) $item['id']]);
        }
    }

    public function syncVenues(int $beverage_id, array $items): void {
        $this->pdo->prepare('DELETE FROM beverage_venues WHERE beverage_id = ?')->execute([$beverage_id]);
        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO beverage_venues (beverage_id, venue_id) VALUES (?, ?)');
        foreach ($items as $item) {
            $stmt->execute([$beverage_id, (int) $item['id']]);
        }
    }

    public function getDietaryRegimes(int $beverage_id): array {
        $stmt = $this->pdo->prepare('
            SELECT dr.id, dr.name
            FROM beverage_dietary_regimes bdr
            JOIN dietary_regimes dr ON dr.id = bdr.regime_id
            WHERE bdr.beverage_id = ?
            ORDER BY dr.name
        ');
        $stmt->execute([$beverage_id]);
        return $stmt->fetchAll();
    }

    public function syncDietaryRegimes(int $beverage_id, array $items): void {
        $this->pdo->prepare('DELETE FROM beverage_dietary_regimes WHERE beverage_id = ?')->execute([$beverage_id]);
        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO beverage_dietary_regimes (beverage_id, regime_id) VALUES (?, ?)');
        foreach ($items as $item) {
            $stmt->execute([$beverage_id, (int) $item['id']]);
        }
    }
}
