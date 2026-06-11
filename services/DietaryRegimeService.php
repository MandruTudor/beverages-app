<?php

class DietaryRegimeService {
    public function __construct(private DietaryRegimeRepository $repo) {}

    public function list(): array {
        return $this->repo->findAll();
    }

    public function get(int $id): array {
        $regime = $this->repo->findById($id);
        if (!$regime) {
            json_error('Regimul alimentar nu a fost gasit.', 404);
        }
        return $regime;
    }

    public function create(NameDto $dto): int {
        require_admin();
        return $this->repo->create($dto->name);
    }

    public function update(int $id, NameDto $dto): void {
        require_admin();
        if (!$this->repo->findById($id)) {
            json_error('Regimul alimentar nu a fost gasit.', 404);
        }
        $this->repo->update($id, $dto->name);
    }

    public function delete(int $id): void {
        require_admin();
        if (!$this->repo->findById($id)) {
            json_error('Regimul alimentar nu a fost gasit.', 404);
        }
        $this->repo->delete($id);
    }
}
