<?php
// ============================================================
// dto/VenueCreateDto.php
//
// DTO pentru CREAREA unui local (venue).
// Un local are: name (obligatoriu), location È™i contact (opÈ›ionale).
// ============================================================

class VenueCreateDto {

    public string  $name;     // Obligatoriu: ex. "CafÃ© Central"
    public ?string $location; // OpÈ›ional: adresÄƒ sau descriere locaÈ›ie
    public ?string $contact;  // OpÈ›ional: telefon, email, website

    public function __construct(array $data) {
        // VerificÄƒm cÃ¢mpul obligatoriu.
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->name     = trim($data['name']);
        $this->location = isset($data['location']) ? trim($data['location']) : null;
        $this->contact  = isset($data['contact'])  ? trim($data['contact'])  : null;
    }
}


