<?php

class IngredientService {
    public function __construct(private IngredientRepository $repo) {}

    public function list(): array {
        return $this->repo->findAll();
    }

    public function get(int $id): array {
        $ingredient = $this->repo->findById($id);
        if (!$ingredient) {
            json_error('Ingredientul nu a fost gasit.', 404);
        }
        return $ingredient;
    }

    public function create(array $data): int {
        require_admin();

        $name = trim($data['name']);
        if ($this->repo->existsByName($name)) {
            json_error('Un ingredient cu acest nume exista deja.', 409);
        }

        return $this->repo->create($name);
    }

    public function update(int $id, array $data): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Ingredientul nu a fost gasit.', 404);
        }
        if (isset($data['name']) && $this->repo->existsByName(trim($data['name']), $id)) {
            json_error('Un ingredient cu acest nume exista deja.', 409);
        }

        $this->repo->update($id, trim($data['name']));
    }

    public function delete(int $id): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Ingredientul nu a fost gasit.', 404);
        }

        // ON DELETE CASCADE: legăturile din beverage_ingredients se șterg automat
        $this->repo->delete($id);
    }
}
