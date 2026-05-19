<?php

class BeverageService {
    public function __construct(private BeverageRepository $repo) {}

    public function list(array $filters): array {
        return $this->repo->findAll($filters);
    }

    public function get(int $id): array {
        $beverage = $this->repo->findById($id);
        if (!$beverage) {
            json_error('Bautura nu a fost gasita.', 404);
        }

        $beverage['ingredients'] = $this->repo->getIngredients($id);
        $beverage['allergens']   = $this->repo->getAllergens($id);
        $beverage['venues']      = $this->repo->getVenues($id);

        return $beverage;
    }

    public function create(array $data): int {
        require_admin();

        $data['created_by'] = current_user()['id'] ?? null;
        $id = $this->repo->create($data);

        $this->repo->syncIngredients($id, $data['ingredients'] ?? []);
        $this->repo->syncAllergens($id, $data['allergens']     ?? []);
        $this->repo->syncVenues($id, $data['venues']           ?? []);

        return $id;
    }

    public function update(int $id, array $data): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Bautura nu a fost gasita.', 404);
        }

        $this->repo->update($id, $data);

        if (array_key_exists('ingredients', $data)) {
            $this->repo->syncIngredients($id, $data['ingredients']);
        }
        if (array_key_exists('allergens', $data)) {
            $this->repo->syncAllergens($id, $data['allergens']);
        }
        if (array_key_exists('venues', $data)) {
            $this->repo->syncVenues($id, $data['venues']);
        }
    }

    public function delete(int $id): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Bautura nu a fost gasita.', 404);
        }

        $this->repo->delete($id);
    }
}
