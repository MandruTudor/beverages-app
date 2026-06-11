<?php

class DietaryRegimeRepository {
    public function __construct(private PDO $pdo) {}

    // Returnează toate regimurile, sortate alfabetic
    public function findAll(): array {
        return $this->pdo->query('SELECT * FROM dietary_regimes ORDER BY name')
                         ->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM dietary_regimes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name): int {
        $stmt = $this->pdo->prepare('INSERT INTO dietary_regimes (name) VALUES (?)');
        $stmt->execute([trim($name)]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name): void {
        $this->pdo->prepare('UPDATE dietary_regimes SET name = ? WHERE id = ?')
                  ->execute([trim($name), $id]);
    }

    public function delete(int $id): void {
        // beverage_dietary_regimes se șterge prin ON DELETE CASCADE
        $this->pdo->prepare('DELETE FROM dietary_regimes WHERE id = ?')
                  ->execute([$id]);
    }
}
