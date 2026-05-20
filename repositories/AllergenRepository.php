<?php

class AllergenRepository {
    public function __construct(private PDO $pdo) {}

    public function findAll(): array {
        return $this->pdo->query('SELECT * FROM allergens ORDER BY name')->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM allergens WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM allergens WHERE name = ? AND id != ?');
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id FROM allergens WHERE name = ?');
            $stmt->execute([$name]);
        }
        return (bool) $stmt->fetch();
    }

    public function create(string $name): int {
        $stmt = $this->pdo->prepare('INSERT INTO allergens (name) VALUES (?)');
        $stmt->execute([$name]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name): void {
        $this->pdo->prepare('UPDATE allergens SET name = ? WHERE id = ?')->execute([$name, $id]);
    }

    public function delete(int $id): void {
        $this->pdo->prepare('DELETE FROM allergens WHERE id = ?')->execute([$id]);
    }
}
