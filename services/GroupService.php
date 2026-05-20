<?php

class GroupService {
    public function __construct(private GroupRepository $repo) {}

    // Listează grupurile din care face parte userul curent
    public function list(): array {
        require_auth();
        return $this->repo->findByUser(current_user()['id']);
    }

    // Returnează grupul cu lista membrilor
    public function get(int $id): array {
        require_auth();

        $group = $this->repo->findById($id);
        if (!$group) {
            json_error('Grupul nu a fost gasit.', 404);
        }

        // Verificăm că userul curent este membru al grupului
        if (!$this->repo->isMember($id, current_user()['id'])) {
            json_error('Nu ai acces la acest grup.', 403);
        }

        $group['members'] = $this->repo->getMembers($id);
        return $group;
    }

    public function create(string $name): int {
        require_auth();
        return $this->repo->create(trim($name), current_user()['id']);
    }

    public function delete(int $id): void {
        require_auth();

        $group = $this->repo->findById($id);
        if (!$group) {
            json_error('Grupul nu a fost gasit.', 404);
        }

        // Numai creatorul grupului sau un admin îl poate șterge
        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate sterge grupul.', 403);
        }

        $this->repo->delete($id);
    }

    // Adaugă un user în grup — doar creatorul grupului sau adminul poate face asta
    public function addMember(int $groupId, int $userId): void {
        require_auth();

        $group = $this->repo->findById($groupId);
        if (!$group) {
            json_error('Grupul nu a fost gasit.', 404);
        }

        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate adauga membri.', 403);
        }

        if ($this->repo->isMember($groupId, $userId)) {
            json_error('Utilizatorul este deja membru al grupului.', 409);
        }

        $this->repo->addMember($groupId, $userId);
    }

    // Elimină un user din grup — creatorul sau adminul, dar nu te poți elimina pe tine însuți dacă ești creator
    public function removeMember(int $groupId, int $userId): void {
        require_auth();

        $group = $this->repo->findById($groupId);
        if (!$group) {
            json_error('Grupul nu a fost gasit.', 404);
        }

        $user = current_user();
        if ($group['created_by'] !== $user['id'] && $user['role'] !== 'admin') {
            json_error('Doar creatorul grupului poate elimina membri.', 403);
        }

        // Creatorul nu se poate elimina pe el însuși (ar rămâne grup fără owner)
        if ($userId === $group['created_by']) {
            json_error('Creatorul nu se poate elimina din propriul grup.', 400);
        }

        $this->repo->removeMember($groupId, $userId);
    }
}
