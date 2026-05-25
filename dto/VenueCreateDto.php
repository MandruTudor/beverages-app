<?php
// ============================================================
// dto/VenueCreateDto.php
//
// DTO pentru CREAREA unui local (venue).
// Un local are: name (obligatoriu), location și contact (opționale).
// ============================================================

class VenueCreateDto {

    public readonly string  $name;     // Obligatoriu: ex. "Café Central"
    public readonly ?string $location; // Opțional: adresă sau descriere locație
    public readonly ?string $contact;  // Opțional: telefon, email, website

    public function __construct(array $data) {
        // Verificăm câmpul obligatoriu.
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->name     = trim($data['name']);
        $this->location = isset($data['location']) ? trim($data['location']) : null;
        $this->contact  = isset($data['contact'])  ? trim($data['contact'])  : null;
    }
}
