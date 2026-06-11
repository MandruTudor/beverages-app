<?php

class GroupRepository {
    public function __construct(private PDO $pdo) {}

    public function findByUser(int $userId): array {
        $stmt = $this->pdo->prepare('
            SELECT g.*, u.username AS owner_name
            FROM groups g
            JOIN group_members gm ON gm.group_id = g.id
            JOIN users u ON u.id = g.created_by
            WHERE gm.user_id = ?
            ORDER BY g.created_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('
            SELECT g.*, u.username AS owner_name
            FROM groups g
            JOIN users u ON u.id = g.created_by
            WHERE g.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getMembers(int $groupId): array {
        $stmt = $this->pdo->prepare('
            SELECT u.id, u.username, u.email
            FROM group_members gm
            JOIN users u ON u.id = gm.user_id
            WHERE gm.group_id = ?
        ');
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public function create(string $name, int $createdBy): int {
        $stmt = $this->pdo->prepare('INSERT INTO groups (name, created_by) VALUES (?, ?)');
        $stmt->execute([$name, $createdBy]);
        $groupId = (int) $this->pdo->lastInsertId();
        $this->addMember($groupId, $createdBy); // creator auto-joins the group
        return $groupId;
    }

    public function delete(int $id): void {
        $this->pdo->prepare('DELETE FROM groups WHERE id = ?')->execute([$id]);
    }

    public function addMember(int $groupId, int $userId): void {
        // INSERT OR IGNORE silently skips if the user is already a member
        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)');
        $stmt->execute([$groupId, $userId]);
    }

    public function removeMember(int $groupId, int $userId): void {
        $stmt = $this->pdo->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$groupId, $userId]);
    }

    public function isMember(int $groupId, int $userId): bool {
        $stmt = $this->pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$groupId, $userId]);
        return (bool) $stmt->fetch();
    }

    public function searchUsers(string $q, int $excludeId): array {
        $stmt = $this->pdo->prepare(
            'SELECT id, username FROM users WHERE username LIKE ? AND id != ? LIMIT 10'
        );
        $stmt->execute(['%' . $q . '%', $excludeId]);
        return $stmt->fetchAll();
    }
}
