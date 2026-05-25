<?php

class AllergenService {
    public function __construct(private AllergenRepository $repo) {}

    public function list(): array {
        return $this->repo->findAll();
    }

    public function get(int $id): array {
        $allergen = $this->repo->findById($id);
        if (!$allergen) {
            json_error('Alergenul nu a fost gasit.', 404);
        }
        return $allergen;
    }

    // Acceptă NameDto — validarea numelui e deja făcută în constructor DTO.
    public function create(NameDto $dto): int {
        require_admin();

        // Verificăm unicitatea: nu pot exista doi alergeni cu același nume.
        if ($this->repo->existsByName($dto->name)) {
            json_error('Un alergen cu acest nume exista deja.', 409);
        }

        // Repository-ul primește direct string-ul tipizat, nu array-ul brut.
        return $this->repo->create($dto->name);
    }

    // Acceptă NameDto și la update — allergen-ul are NUMAI câmpul 'name'.
    public function update(int $id, NameDto $dto): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Alergenul nu a fost gasit.', 404);
        }

        // Verificăm unicitatea excluzând ID-ul curent (nu e conflict cu sine însuși).
        if ($this->repo->existsByName($dto->name, $id)) {
            json_error('Un alergen cu acest nume exista deja.', 409);
        }

        $this->repo->update($id, $dto->name);
    }

    public function delete(int $id): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Alergenul nu a fost gasit.', 404);
        }

        $this->repo->delete($id);
    }
}
