<?php

class CategoryRepository {
    public function __construct(private PDO $pdo) {}

    // Returnează toate categoriile ordonate alfabetic
    public function findAll(): array {
        return $this->pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }

    // Caută o categorie după ID; returnează null dacă nu există
    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // Verifică dacă există deja o categorie cu același nume
    // excludeId e folosit la update: ignorăm categoria care se modifică
    public function existsByName(string $name, ?int $excludeId = null): bool {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare('SELECT id FROM categories WHERE name = ? AND id != ?');
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare('SELECT id FROM categories WHERE name = ?');
            $stmt->execute([$name]);
        }
        return (bool) $stmt->fetch();
    }

    // Inserează o categorie nouă și returnează ID-ul generat
    public function create(string $name, ?string $description): int {
        $stmt = $this->pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)');
        $stmt->execute([$name, $description]);
        return (int) $this->pdo->lastInsertId();
    }

    // Actualizează dinamic doar câmpurile trimise (PATCH behavior)
    public function update(int $id, array $data): void {
        $fields = [];
        $params = [];
        foreach (['name', 'description'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = ?";
                $params[] = $col === 'name' ? trim($data[$col]) : $data[$col];
            }
        }
        if ($fields) {
            $params[] = $id;
            $this->pdo->prepare('UPDATE categories SET ' . implode(', ', $fields) . ' WHERE id = ?')
                      ->execute($params);
        }
    }

    public function delete(int $id): void {
        $this->pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
    }
}
