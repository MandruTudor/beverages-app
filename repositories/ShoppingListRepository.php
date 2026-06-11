<?php

class ShoppingListRepository {
    public function __construct(private PDO $pdo) {}

    public function findByUser(int $userId): array {
        $stmt = $this->pdo->prepare('
            SELECT sl.*,
                   (SELECT COUNT(*) FROM shopping_list_items WHERE list_id = sl.id) AS item_count,
                   g.name AS group_name
            FROM shopping_lists sl
            LEFT JOIN groups g ON g.id = sl.group_id
            WHERE sl.user_id = ?
               OR sl.group_id IN (
                   SELECT group_id FROM group_members WHERE user_id = ?
               )
            ORDER BY sl.created_at DESC
        ');
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM shopping_lists WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getItems(int $listId): array {
        $stmt = $this->pdo->prepare('
            SELECT sli.id, sli.quantity, sli.checked,
                   b.id AS beverage_id, b.name AS beverage_name, b.price
            FROM shopping_list_items sli
            JOIN beverages b ON b.id = sli.beverage_id
            WHERE sli.list_id = ?
            ORDER BY sli.id
        ');
        $stmt->execute([$listId]);
        return $stmt->fetchAll();
    }

    public function create(int $userId, string $name, ?int $groupId): int {
        $stmt = $this->pdo->prepare('INSERT INTO shopping_lists (user_id, name, group_id) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $name, $groupId]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $id): void {
        $this->pdo->prepare('DELETE FROM shopping_lists WHERE id = ?')->execute([$id]);
    }

    public function addItem(int $listId, int $beverageId, int $quantity): int {
        $stmt = $this->pdo->prepare('INSERT INTO shopping_list_items (list_id, beverage_id, quantity) VALUES (?, ?, ?)');
        $stmt->execute([$listId, $beverageId, $quantity]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findItemById(int $itemId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM shopping_list_items WHERE id = ?');
        $stmt->execute([$itemId]);
        return $stmt->fetch() ?: null;
    }

    public function updateItem(int $itemId, array $data): void {
        $fields = [];
        $params = [];
        if (array_key_exists('quantity', $data)) {
            $fields[] = 'quantity = ?';
            $params[] = (int) $data['quantity'];
        }
        if (array_key_exists('checked', $data)) {
            $fields[] = 'checked = ?';
            // checked is stored as 0/1 — SQLite has no native boolean type
            $params[] = $data['checked'] ? 1 : 0;
        }
        if ($fields) {
            $params[] = $itemId;
            $this->pdo->prepare('UPDATE shopping_list_items SET ' . implode(', ', $fields) . ' WHERE id = ?')
                      ->execute($params);
        }
    }

    public function removeItem(int $itemId): void {
        $this->pdo->prepare('DELETE FROM shopping_list_items WHERE id = ?')->execute([$itemId]);
    }
}
