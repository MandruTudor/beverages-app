<?php

class VenueService {
    public function __construct(private VenueRepository $repo) {}

    public function list(): array {
        return $this->repo->findAll();
    }

    public function get(int $id): array {
        $venue = $this->repo->findById($id);
        if (!$venue) {
            json_error('Localul nu a fost gasit.', 404);
        }
        return $venue;
    }

    public function create(array $data): int {
        require_admin();
        return $this->repo->create(
            trim($data['name']),
            $data['location'] ?? null,
            $data['contact']  ?? null
        );
    }

    public function update(int $id, array $data): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Localul nu a fost gasit.', 404);
        }

        $this->repo->update($id, $data);
    }

    public function delete(int $id): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Localul nu a fost gasit.', 404);
        }

        $this->repo->delete($id);
    }
}
