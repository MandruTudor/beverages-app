<?php

class VenueCreateDto {
    public string  $name;
    public ?string $location;
    public ?string $contact;

    public function __construct(array $data) {
        $missing = missing_field($data, ['name']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);
        $this->name     = trim($data['name']);
        $this->location = isset($data['location']) ? trim($data['location']) : null;
        $this->contact  = isset($data['contact'])  ? trim($data['contact'])  : null;
    }
}
