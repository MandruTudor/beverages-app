<?php
// ============================================================
// dto/BeverageCreateDto.php
//
// DTO pentru CREAREA unei băuturi noi.
// Băutura e cel mai complex resource — are câmpuri scalare și relații many-to-many
// (ingrediente, alergeni, localuri) care se trimit tot prin același POST.
// ============================================================

class BeverageCreateDto {

    // --- Câmpuri scalare ale băuturii ---
    public readonly string  $name;              // Obligatoriu: numele băuturii
    public readonly ?int    $categoryId;        // Opțional: ID-ul categoriei
    public readonly ?float  $price;             // Opțional: prețul în lei
    public readonly ?string $description;       // Opțional: descriere liberă
    public readonly ?string $imagePath;         // Opțional: calea relativă a imaginii
    public readonly ?int    $perishabilityDays; // Opțional: zile de perisabilitate
    public readonly ?string $season;            // Opțional: 'spring'|'summer'|'autumn'|'winter'|'all'
    public readonly ?string $region;            // Opțional: regiune geografică

    // --- Relații many-to-many (opționale, array de obiecte) ---
    // Exemplu ingredients: [{"id": 1, "quantity": "200ml"}, {"id": 3}]
    // Exemplu allergens:   [{"id": 2}, {"id": 5}]
    // Exemplu venues:      [{"id": 1}, {"id": 3}]
    public readonly array $ingredients; // default []
    public readonly array $allergens;   // default []
    public readonly array $venues;      // default []

    public function __construct(array $data) {
        // Validăm câmpul obligatoriu 'name'.
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        // Câmpuri scalare — conversie la tipul corect sau null dacă lipsesc.
        $this->name              = trim($data['name']);

        // (int) convertește la întreg. category_id trebuie să fie un număr valid de ID.
        $this->categoryId        = isset($data['category_id'])        ? (int)   $data['category_id']        : null;

        // (float) pentru preț — suportă valori cu zecimale (ex: 12.50).
        $this->price             = isset($data['price'])              ? (float) $data['price']              : null;
        $this->description       = isset($data['description'])        ? trim($data['description'])          : null;
        $this->imagePath         = isset($data['image_path'])         ? trim($data['image_path'])           : null;

        // Perisabilitatea e număr întreg de zile (ex: 7, 30, 365).
        $this->perishabilityDays = isset($data['perishability_days']) ? (int)   $data['perishability_days'] : null;

        // Sezonul e un enum string — nu trimăm, valoarea vine deja curată din dropdown.
        $this->season            = $data['season']  ?? null;
        $this->region            = isset($data['region'])             ? trim($data['region'])               : null;

        // Relațiile many-to-many — array de obiecte sau array gol dacă lipsesc.
        // ?? [] = dacă cheia lipsește, folosim array gol (nu se inserează nicio relație).
        $this->ingredients = $data['ingredients'] ?? [];
        $this->allergens   = $data['allergens']   ?? [];
        $this->venues      = $data['venues']      ?? [];
    }
}
