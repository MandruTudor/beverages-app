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

    // Acceptă VenueCreateDto — name tipizat ca string, location/contact ca ?string.
    public function create(VenueCreateDto $dto): int {
        require_admin();
        // Repository-ul primește valorile individuale tipizate.
        return $this->repo->create($dto->name, $dto->location, $dto->contact);
    }

    // Acceptă VenueUpdateDto — $dto->toArray() returnează doar câmpurile trimise.
    public function update(int $id, VenueUpdateDto $dto): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Localul nu a fost gasit.', 404);
        }

        // Repository-ul actualizează dinamic doar câmpurile prezente în array.
        $this->repo->update($id, $dto->toArray());
    }

    public function delete(int $id): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Localul nu a fost gasit.', 404);
        }

        $this->repo->delete($id);
    }
}
