<?php

class GroupPreferenceRepository {
    public function __construct(private PDO $pdo) {}

    public function findByGroup(int $groupId): array {
        $stmt = $this->pdo->prepare('
            SELECT gp.group_id, gp.beverage_id, gp.rating, gp.notes,
                   b.name AS beverage_name, b.price
            FROM group_preferences gp
            JOIN beverages b ON b.id = gp.beverage_id
            WHERE gp.group_id = ?
            ORDER BY gp.rating DESC, b.name
        ');
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public function upsert(int $groupId, int $beverageId, int $rating, ?string $notes): void {
        $stmt = $this->pdo->prepare('
            INSERT INTO group_preferences (group_id, beverage_id, rating, notes)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(group_id, beverage_id) DO UPDATE SET rating = excluded.rating, notes = excluded.notes
        ');
        $stmt->execute([$groupId, $beverageId, $rating, $notes]);
    }

    public function delete(int $groupId, int $beverageId): void {
        $stmt = $this->pdo->prepare(
            'DELETE FROM group_preferences WHERE group_id = ? AND beverage_id = ?'
        );
        $stmt->execute([$groupId, $beverageId]);
    }
}
