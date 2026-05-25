<?php

class GroupService {
    public function __construct(private GroupRepository $repo) {}

    // Listează grupurile din care face parte userul curent.
    public function list(): array {
        require_auth();
        return $this->repo->findByUser(current_user()['id']);
    }

    // Returnează grupul cu lista membrilor (doar dacă userul e membru).
    public function get(int $id): array {
        require_auth();

        $group = $this->repo->findById($id);
        if (!$group) {
            json_error('Grupul nu a fost gasit.', 404);
        }

        // Verificăm că userul curent este membru al grupului.
        if (!$this->repo->isMember($id, current_user()['id'])) {
            json_error('Nu ai acces la acest grup.', 403);
        }

        $group['members'] = $this->repo->getMembers($id);
        return $group;
    }

    // Acceptă GroupCreateDto — name tipizat și validat.
    public function create(GroupCreateDto $dto): int {
        require_auth();
        // trim() deja aplicat în DTO constructor — Repository primește string curat.
        return $this->repo->create($dto->name, current_user()['id']);
    }

    public function delete(int $id): void {
        require_auth();

        $group = $this->repo->findById($id);
        if (!$group) {
            json_error('Grupul nu a fost gasit.', 404);
        }

        // Numai creatorul grupului sau un admin îl poate șterge.
        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate sterge grupul.', 403);
        }

        $this->repo->delete($id);
    }

    // Acceptă GroupMemberDto — userId tipizat ca int și validat.
    public function addMember(int $groupId, GroupMemberDto $dto): void {
        require_auth();

        $group = $this->repo->findById($groupId);
        if (!$group) {
            json_error('Grupul nu a fost gasit.', 404);
        }

        // Numai creatorul sau adminul poate adăuga membri.
        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate adauga membri.', 403);
        }

        // Verificăm că userul nu e deja în grup (ar viola cheia primară compusă).
        if ($this->repo->isMember($groupId, $dto->userId)) {
            json_error('Utilizatorul este deja membru al grupului.', 409);
        }

        $this->repo->addMember($groupId, $dto->userId);
    }

    // Acceptă GroupMemberDto — același DTO pentru eliminare.
    public function removeMember(int $groupId, GroupMemberDto $dto): void {
        require_auth();

        $group = $this->repo->findById($groupId);
        if (!$group) {
            json_error('Grupul nu a fost gasit.', 404);
        }

        // Numai creatorul sau adminul poate elimina membri.
        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate elimina membri.', 403);
        }

        // Creatorul nu se poate elimina pe el însuși — grupul ar rămâne fără owner.
        if ($dto->userId === $group['created_by']) {
            json_error('Creatorul nu se poate elimina din propriul grup.', 400);
        }

        $this->repo->removeMember($groupId, $dto->userId);
    }
}
