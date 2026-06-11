<?php

class PreferenceRepository {
    public function __construct(private PDO $pdo) {}

    public function findByUser(int $userId): array {
        $stmt = $this->pdo->prepare('
            SELECT up.*, b.name AS beverage_name, b.price
            FROM user_preferences up
            JOIN beverages b ON b.id = up.beverage_id
            WHERE up.user_id = ?
            ORDER BY up.rating DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findByUserAndBeverage(int $userId, int $beverageId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM user_preferences WHERE user_id = ? AND beverage_id = ?');
        $stmt->execute([$userId, $beverageId]);
        return $stmt->fetch() ?: null;
    }

    public function upsert(int $userId, int $beverageId, int $rating, ?string $notes): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO user_preferences (user_id, beverage_id, rating, notes)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(user_id, beverage_id) DO UPDATE SET rating = excluded.rating, notes = excluded.notes
        ');
        $stmt->execute([$userId, $beverageId, $rating, $notes]);
    }

    public function delete(int $userId, int $beverageId): void {
        $stmt = $this->pdo->prepare('DELETE FROM user_preferences WHERE user_id = ? AND beverage_id = ?');
        $stmt->execute([$userId, $beverageId]);
    }

    // Scoring: +3 for matching a liked category, +1 per shared ingredient with liked beverages (rating >= 4)
    public function getRecommendations(int $userId, int $limit = 6): array {
        $stmt = $this->pdo->prepare('
            SELECT b.id, b.name, b.price, b.description, b.season, b.region,
                   c.name AS category_name,
                   (
                       CASE WHEN b.category_id IN (
                           SELECT DISTINCT bv.category_id
                           FROM beverages bv
                           JOIN user_preferences up ON up.beverage_id = bv.id
                           WHERE up.user_id = :uid1 AND up.rating >= 4
                             AND bv.category_id IS NOT NULL
                       ) THEN 3 ELSE 0 END
                       +
                       (
                           SELECT COUNT(*)
                           FROM beverage_ingredients bi
                           WHERE bi.beverage_id = b.id
                             AND bi.ingredient_id IN (
                                 SELECT DISTINCT bi2.ingredient_id
                                 FROM beverage_ingredients bi2
                                 JOIN user_preferences up2 ON up2.beverage_id = bi2.beverage_id
                                 WHERE up2.user_id = :uid2 AND up2.rating >= 4
                             )
                       )
                   ) AS score
            FROM beverages b
            LEFT JOIN categories c ON c.id = b.category_id
            WHERE b.id NOT IN (
                SELECT beverage_id FROM user_preferences WHERE user_id = :uid3
            )
            ORDER BY score DESC, b.name
            LIMIT :lim
        ');
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid3', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim',  $limit,  PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
