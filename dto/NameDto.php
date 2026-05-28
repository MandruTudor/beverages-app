<?php
// ============================================================
// dto/NameDto.php
//
// DTO partajat pentru resursele care au un singur cÃ¢mp editabil: 'name'.
// Folosit de: AllergenController, IngredientController
// (atÃ¢t la creare cÃ¢t È™i la actualizare, deoarece ambele operaÈ›ii
//  cer cÃ¢mpul 'name' obligatoriu pentru aceste resurse simple).
//
// Reutilizarea unui singur DTO evitÄƒ duplicarea codului identic
// Ã®n AllergenCreateDto + AllergenUpdateDto + IngredientCreateDto + IngredientUpdateDto.
// ============================================================

class NameDto {

    // Singurul cÃ¢mp al acestui DTO: numele resursei (allergen sau ingredient).
    // Obligatoriu â€” constructorul aruncÄƒ eroare dacÄƒ lipseÈ™te.
    public string $name;

    public function __construct(array $data) {
        // ValidÄƒm cÄƒ 'name' existÄƒ È™i nu e gol.
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        // trim() eliminÄƒ spaÈ›iile de la capete Ã®nainte de stocare.
        $this->name = trim($data['name']);
    }
}


