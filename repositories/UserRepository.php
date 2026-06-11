<?php

class UserRepository {
    public function __construct(private PDO $pdo) {}

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([trim($email)]);
        return $stmt->fetch() ?: null;
    }

    public function findByUsernameOrEmail(string $username, string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $email]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $username, string $email, string $hash): int {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $hash]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findAll(): array {
        return $this->pdo->query(
            'SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC'
        )->fetchAll();
    }

    public function setRole(int $id, string $role): void {
        $stmt = $this->pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $id]);
    }

    public function delete(int $id): void {
        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
