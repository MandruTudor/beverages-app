<?php
// ============================================================
// dto/NameDto.php
//
// DTO partajat pentru resursele care au un singur câmp editabil: 'name'.
// Folosit de: AllergenController, IngredientController
// (atât la creare cât și la actualizare, deoarece ambele operații
//  cer câmpul 'name' obligatoriu pentru aceste resurse simple).
//
// Reutilizarea unui singur DTO evită duplicarea codului identic
// în AllergenCreateDto + AllergenUpdateDto + IngredientCreateDto + IngredientUpdateDto.
// ============================================================

class NameDto {

    // Singurul câmp al acestui DTO: numele resursei (allergen sau ingredient).
    // Obligatoriu — constructorul aruncă eroare dacă lipsește.
    public readonly string $name;

    public function __construct(array $data) {
        // Validăm că 'name' există și nu e gol.
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        // trim() elimină spațiile de la capete înainte de stocare.
        $this->name = trim($data['name']);
    }
}
