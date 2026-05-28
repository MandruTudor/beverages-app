<?php
// ============================================================
// dto/BeverageCreateDto.php
//
// DTO pentru CREAREA unei bÄƒuturi noi.
// BÄƒutura e cel mai complex resource â€” are cÃ¢mpuri scalare È™i relaÈ›ii many-to-many
// (ingrediente, alergeni, localuri) care se trimit tot prin acelaÈ™i POST.
// ============================================================

class BeverageCreateDto {

    // --- CÃ¢mpuri scalare ale bÄƒuturii ---
    public string  $name;              // Obligatoriu: numele bÄƒuturii
    public ?int    $categoryId;        // OpÈ›ional: ID-ul categoriei
    public ?float  $price;             // OpÈ›ional: preÈ›ul Ã®n lei
    public ?string $description;       // OpÈ›ional: descriere liberÄƒ
    public ?string $imagePath;         // OpÈ›ional: calea relativÄƒ a imaginii
    public ?int    $perishabilityDays; // OpÈ›ional: zile de perisabilitate
    public ?string $season;            // OpÈ›ional: 'spring'|'summer'|'autumn'|'winter'|'all'
    public ?string $region;            // OpÈ›ional: regiune geograficÄƒ

    // --- RelaÈ›ii many-to-many (opÈ›ionale, array de obiecte) ---
    // Exemplu ingredients: [{"id": 1, "quantity": "200ml"}, {"id": 3}]
    // Exemplu allergens:   [{"id": 2}, {"id": 5}]
    // Exemplu venues:      [{"id": 1}, {"id": 3}]
    public array $ingredients; // default []
    public array $allergens;   // default []
    public array $venues;      // default []

    public function __construct(array $data) {
        // ValidÄƒm cÃ¢mpul obligatoriu 'name'.
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        // CÃ¢mpuri scalare â€” conversie la tipul corect sau null dacÄƒ lipsesc.
        $this->name              = trim($data['name']);

        // (int) converteÈ™te la Ã®ntreg. category_id trebuie sÄƒ fie un numÄƒr valid de ID.
        $this->categoryId        = isset($data['category_id'])        ? (int)   $data['category_id']        : null;

        // (float) pentru preÈ› â€” suportÄƒ valori cu zecimale (ex: 12.50).
        $this->price             = isset($data['price'])              ? (float) $data['price']              : null;
        $this->description       = isset($data['description'])        ? trim($data['description'])          : null;
        $this->imagePath         = isset($data['image_path'])         ? trim($data['image_path'])           : null;

        // Perisabilitatea e numÄƒr Ã®ntreg de zile (ex: 7, 30, 365).
        $this->perishabilityDays = isset($data['perishability_days']) ? (int)   $data['perishability_days'] : null;

        // Sezonul e un enum string â€” nu trimÄƒm, valoarea vine deja curatÄƒ din dropdown.
        $this->season            = $data['season']  ?? null;
        $this->region            = isset($data['region'])             ? trim($data['region'])               : null;

        // RelaÈ›iile many-to-many â€” array de obiecte sau array gol dacÄƒ lipsesc.
        // ?? [] = dacÄƒ cheia lipseÈ™te, folosim array gol (nu se insereazÄƒ nicio relaÈ›ie).
        $this->ingredients = $data['ingredients'] ?? [];
        $this->allergens   = $data['allergens']   ?? [];
        $this->venues      = $data['venues']      ?? [];
    }
}


