<?php

class VenueRepository {
    public function __construct(private PDO $pdo) {}

    public function findAll(): array {
        return $this->pdo->query('SELECT * FROM venues ORDER BY name')->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM venues WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, ?string $location, ?string $contact): int {
        $stmt = $this->pdo->prepare('INSERT INTO venues (name, location, contact) VALUES (?, ?, ?)');
        $stmt->execute([$name, $location, $contact]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $fields = [];
        $params = [];
        foreach (['name', 'location', 'contact'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $col === 'name' ? trim($data[$col]) : $data[$col];
            }
        }
        if ($fields) {
            $params[] = $id;
            $this->pdo->prepare('UPDATE venues SET ' . implode(', ', $fields) . ' WHERE id = ?')
                      ->execute($params);
        }
    }

    public function delete(int $id): void {
        $this->pdo->prepare('DELETE FROM venues WHERE id = ?')->execute([$id]);
    }
}
