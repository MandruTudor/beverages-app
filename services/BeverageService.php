<?php

class BeverageService {
    public function __construct(private BeverageRepository $repo) {}

    public function list(array $filters): array {
        // Filtrele rămân ca array simplu — vin din query params, nu din body JSON.
        return $this->repo->findAll($filters);
    }

    public function get(int $id): array {
        $beverage = $this->repo->findById($id);
        if (!$beverage) {
            json_error('Bautura nu a fost gasita.', 404);
        }

        // Adăugăm relațiile many-to-many ca sub-array-uri în răspuns.
        $beverage['ingredients'] = $this->repo->getIngredients($id);
        $beverage['allergens']   = $this->repo->getAllergens($id);
        $beverage['venues']      = $this->repo->getVenues($id);

        return $beverage;
    }

    // Acceptă BeverageCreateDto — toate câmpurile sunt tipizate și validate.
    public function create(BeverageCreateDto $dto): int {
        require_admin();

        // Construim array-ul pentru repository din proprietățile tipizate ale DTO-ului.
        // Repository-ul se așteaptă la array cu chei specifice (coloana SQL → valoare).
        $data = [
            'name'               => $dto->name,
            'category_id'        => $dto->categoryId,
            'price'              => $dto->price,
            'description'        => $dto->description,
            'image_path'         => $dto->imagePath,
            'perishability_days' => $dto->perishabilityDays,
            'season'             => $dto->season,
            'region'             => $dto->region,
            // ID-ul adminului care creează băutura — din sesiunea curentă, nu din body.
            'created_by'         => current_user()['id'] ?? null,
        ];

        $id = $this->repo->create($data);

        // Sincronizăm relațiile many-to-many (DELETE + INSERT pentru fiecare).
        // $dto->ingredients este deja array ([] dacă nu au fost trimise).
        $this->repo->syncIngredients($id, $dto->ingredients);
        $this->repo->syncAllergens($id, $dto->allergens);
        $this->repo->syncVenues($id, $dto->venues);

        return $id;
    }

    // Acceptă BeverageUpdateDto — suportă semantica PATCH (câmpuri selective).
    public function update(int $id, BeverageUpdateDto $dto): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Bautura nu a fost gasita.', 404);
        }

        // toScalarArray() returnează doar câmpurile SCALARE trimise în cerere.
        // Repository-ul construiește dinamic SET clause doar din aceste câmpuri.
        $this->repo->update($id, $dto->toScalarArray());

        // Sincronizăm relațiile NUMAI dacă au fost trimise explicit în cerere.
        // $dto->has('ingredients') = true chiar dacă valoarea e [] (șterge toate relațiile).
        // $dto->ingredients este null dacă NU au fost trimise = nu atingem relațiile.
        if ($dto->has('ingredients')) $this->repo->syncIngredients($id, $dto->ingredients ?? []);
        if ($dto->has('allergens'))   $this->repo->syncAllergens($id, $dto->allergens   ?? []);
        if ($dto->has('venues'))      $this->repo->syncVenues($id, $dto->venues         ?? []);
    }

    public function delete(int $id): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Bautura nu a fost gasita.', 404);
        }

        // Relațiile se șterg automat prin ON DELETE CASCADE definit în init.sql.
        $this->repo->delete($id);
    }
}
