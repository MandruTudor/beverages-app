<?php

class GroupCreateDto {
    public string $name;

    public function __construct(array $data) {
        $missing = missing_field($data, ['name']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);
        $this->name = trim($data['name']);
    }
}
