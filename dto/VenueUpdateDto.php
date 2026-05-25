<?php
// ============================================================
// dto/VenueUpdateDto.php
//
// DTO pentru ACTUALIZAREA parțială (PATCH) a unui local.
// Toate câmpurile sunt opționale.
// ============================================================

class VenueUpdateDto {

    public readonly ?string $name;
    public readonly ?string $location;
    public readonly ?string $contact;

    // Cheile prezente în cererea originală — pentru semantica PATCH.
    private readonly array $provided;

    public function __construct(array $data) {
        $this->provided = array_keys($data);

        $this->name     = isset($data['name'])     ? trim($data['name'])     : null;
        $this->location = isset($data['location']) ? trim($data['location']) : null;
        $this->contact  = isset($data['contact'])  ? trim($data['contact'])  : null;
    }

    // Verifică dacă câmpul a fost trimis în cerere.
    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    // Construiește array-ul de câmpuri pentru UPDATE SQL.
    public function toArray(): array {
        $result = [];
        if ($this->has('name'))     $result['name']     = $this->name;
        if ($this->has('location')) $result['location'] = $this->location;
        if ($this->has('contact'))  $result['contact']  = $this->contact;
        return $result;
    }
}
