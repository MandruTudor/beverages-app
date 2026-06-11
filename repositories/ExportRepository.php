<?php

class ExportRepository {
    public function __construct(private PDO $pdo) {}

    public function getAllBeverages(): array {
        $beverages = $this->pdo->query('
            SELECT b.*, c.name AS category_name
            FROM beverages b
            LEFT JOIN categories c ON c.id = b.category_id
            ORDER BY b.name
        ')->fetchAll();

        foreach ($beverages as &$bev) {
            $stmt = $this->pdo->prepare('
                SELECT i.name, bi.quantity
                FROM beverage_ingredients bi
                JOIN ingredients i ON i.id = bi.ingredient_id
                WHERE bi.beverage_id = ?
            ');
            $stmt->execute([$bev['id']]);
            $bev['ingredients'] = $stmt->fetchAll();

            $stmt = $this->pdo->prepare('
                SELECT a.name FROM beverage_allergens ba
                JOIN allergens a ON a.id = ba.allergen_id
                WHERE ba.beverage_id = ?
            ');
            $stmt->execute([$bev['id']]);
            $bev['allergens'] = $stmt->fetchAll();
        }

        return $beverages;
    }
}
