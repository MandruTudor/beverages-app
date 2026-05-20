<?php

class PreferenceRepository {
    public function __construct(private PDO $pdo) {}

    // Returnează toate preferințele unui user cu detalii despre băutură
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

    // Caută preferința unui user pentru o anumită băutură
    public function findByUserAndBeverage(int $userId, int $beverageId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM user_preferences WHERE user_id = ? AND beverage_id = ?');
        $stmt->execute([$userId, $beverageId]);
        return $stmt->fetch() ?: null;
    }

    // INSERT OR REPLACE: dacă există deja o preferință pentru (user, beverage), o suprascrie
    // Aceasta e o operație "upsert" — creare sau actualizare în același pas
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
}
