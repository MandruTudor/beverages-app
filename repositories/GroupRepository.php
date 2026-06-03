<?php

class GroupRepository {
    public function __construct(private PDO $pdo) {}

    // Returnează toate grupurile din care face parte un user (ca membru)
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

    // Returnează lista membrilor unui grup cu username-urile lor
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

    // Creează grup și adaugă creatorul ca prim membru, returnează ID-ul grupului
    public function create(string $name, int $createdBy): int {
        $stmt = $this->pdo->prepare('INSERT INTO groups (name, created_by) VALUES (?, ?)');
        $stmt->execute([$name, $createdBy]);
        $groupId = (int) $this->pdo->lastInsertId();

        // Creatorul devine automat membru al grupului
        $this->addMember($groupId, $createdBy);

        return $groupId;
    }

    public function delete(int $id): void {
        // ON DELETE CASCADE în schema SQL: group_members se șterg automat
        $this->pdo->prepare('DELETE FROM groups WHERE id = ?')->execute([$id]);
    }

    public function addMember(int $groupId, int $userId): void {
        // INSERT OR IGNORE: dacă userul e deja membru, nu aruncăm eroare
        $stmt = $this->pdo->prepare('INSERT OR IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)');
        $stmt->execute([$groupId, $userId]);
    }

    public function removeMember(int $groupId, int $userId): void {
        $stmt = $this->pdo->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$groupId, $userId]);
    }

    // Verifică dacă un user este deja în grup
    public function isMember(int $groupId, int $userId): bool {
        $stmt = $this->pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$groupId, $userId]);
        return (bool) $stmt->fetch();
    }

    // Caută utilizatori după username (parțial), excludând userul curent
    public function searchUsers(string $q, int $excludeId): array {
        $stmt = $this->pdo->prepare(
            'SELECT id, username FROM users WHERE username LIKE ? AND id != ? LIMIT 10'
        );
        $stmt->execute(['%' . $q . '%', $excludeId]);
        return $stmt->fetchAll();
    }
}
