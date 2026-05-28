<?php
// ============================================================
// dto/VenueUpdateDto.php
//
// DTO pentru ACTUALIZAREA parÈ›ialÄƒ (PATCH) a unui local.
// Toate cÃ¢mpurile sunt opÈ›ionale.
// ============================================================

class VenueUpdateDto {

    public ?string $name;
    public ?string $location;
    public ?string $contact;

    // Cheile prezente Ã®n cererea originalÄƒ â€” pentru semantica PATCH.
    private array $provided;

    public function __construct(array $data) {
        $this->provided = array_keys($data);

        $this->name     = isset($data['name'])     ? trim($data['name'])     : null;
        $this->location = isset($data['location']) ? trim($data['location']) : null;
        $this->contact  = isset($data['contact'])  ? trim($data['contact'])  : null;
    }

    // VerificÄƒ dacÄƒ cÃ¢mpul a fost trimis Ã®n cerere.
    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    // ConstruieÈ™te array-ul de cÃ¢mpuri pentru UPDATE SQL.
    public function toArray(): array {
        $result = [];
        if ($this->has('name'))     $result['name']     = $this->name;
        if ($this->has('location')) $result['location'] = $this->location;
        if ($this->has('contact'))  $result['contact']  = $this->contact;
        return $result;
    }
}


