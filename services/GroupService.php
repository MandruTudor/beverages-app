<?php

class GroupService {
    public function __construct(private GroupRepository $repo) {}

    public function list(): array {
        require_auth();
        return $this->repo->findByUser(current_user()['id']);
    }

    public function get(int $id): array {
        require_auth();
        $group = $this->repo->findById($id);
        if (!$group) json_error('Grupul nu a fost gasit.', 404);
        if (!$this->repo->isMember($id, current_user()['id'])) json_error('Nu ai acces la acest grup.', 403);
        $group['members'] = $this->repo->getMembers($id);
        return $group;
    }

    public function create(GroupCreateDto $dto): int {
        require_auth();
        return $this->repo->create($dto->name, current_user()['id']);
    }

    public function delete(int $id): void {
        require_auth();
        $group = $this->repo->findById($id);
        if (!$group) json_error('Grupul nu a fost gasit.', 404);
        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate sterge grupul.', 403);
        }
        $this->repo->delete($id);
    }

    public function addMember(int $groupId, GroupMemberDto $dto): void {
        require_auth();
        $group = $this->repo->findById($groupId);
        if (!$group) json_error('Grupul nu a fost gasit.', 404);
        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate adauga membri.', 403);
        }
        if ($this->repo->isMember($groupId, $dto->userId)) {
            json_error('Utilizatorul este deja membru al grupului.', 409);
        }
        $this->repo->addMember($groupId, $dto->userId);
    }

    public function searchUsers(string $q): array {
        require_auth();
        $q = trim($q);
        if (strlen($q) < 2) return [];
        return $this->repo->searchUsers($q, current_user()['id']);
    }

    public function removeMember(int $groupId, GroupMemberDto $dto): void {
        require_auth();
        $group = $this->repo->findById($groupId);
        if (!$group) json_error('Grupul nu a fost gasit.', 404);
        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate elimina membri.', 403);
        }
        // Creator cannot remove themselves — group would have no owner
        if ($dto->userId === $group['created_by']) {
            json_error('Creatorul nu se poate elimina din propriul grup.', 400);
        }
        $this->repo->removeMember($groupId, $dto->userId);
    }
}
