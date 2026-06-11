<?php

class CategoryCreateDto {
    public string  $name;
    public ?string $description;

    public function __construct(array $data) {
        $missing = missing_field($data, ['name']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);
        $this->name        = trim($data['name']);
        $this->description = isset($data['description']) ? trim($data['description']) : null;
    }
}
