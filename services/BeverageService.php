<?php

class BeverageService {
    public function __construct(private BeverageRepository $repo) {}

    public function list(array $filters): array {
        return $this->repo->findAll($filters);
    }

    public function get(int $id): array {
        $beverage = $this->repo->findById($id);
        if (!$beverage) json_error('Bautura nu a fost gasita.', 404);

        $beverage['ingredients']     = $this->repo->getIngredients($id);
        $beverage['allergens']       = $this->repo->getAllergens($id);
        $beverage['venues']          = $this->repo->getVenues($id);
        $beverage['dietary_regimes'] = $this->repo->getDietaryRegimes($id);

        return $beverage;
    }

    public function create(BeverageCreateDto $dto): int {
        require_admin();

        $data = [
            'name'               => $dto->name,
            'category_id'        => $dto->categoryId,
            'price'              => $dto->price,
            'description'        => $dto->description,
            'image_path'         => $dto->imagePath,
            'perishability_days' => $dto->perishabilityDays,
            'valid_until'        => $dto->validUntil,
            'season'             => $dto->season,
            'region'             => $dto->region,
            'created_by'         => current_user()['id'] ?? null,
        ];

        $id = $this->repo->create($data);
        $this->repo->syncIngredients($id, $dto->ingredients);
        $this->repo->syncAllergens($id, $dto->allergens);
        $this->repo->syncVenues($id, $dto->venues);
        $this->repo->syncDietaryRegimes($id, $dto->dietaryRegimes);

        return $id;
    }

    public function update(int $id, BeverageUpdateDto $dto): void {
        require_admin();
        if (!$this->repo->findById($id)) json_error('Bautura nu a fost gasita.', 404);

        $this->repo->update($id, $dto->toScalarArray());

        // Sync only if the key was present in the request; null = absent = leave untouched
        if ($dto->has('ingredients'))     $this->repo->syncIngredients($id, $dto->ingredients     ?? []);
        if ($dto->has('allergens'))       $this->repo->syncAllergens($id, $dto->allergens         ?? []);
        if ($dto->has('venues'))          $this->repo->syncVenues($id, $dto->venues               ?? []);
        if ($dto->has('dietary_regimes')) $this->repo->syncDietaryRegimes($id, $dto->dietaryRegimes ?? []);
    }

    public function delete(int $id): void {
        require_admin();
        if (!$this->repo->findById($id)) json_error('Bautura nu a fost gasita.', 404);
        $this->repo->delete($id);
    }
}
