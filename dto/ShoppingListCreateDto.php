<?php

class ShoppingListCreateDto {
    public string $name;
    public ?int   $groupId;

    public function __construct(array $data) {
        $missing = missing_field($data, ['name']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);
        $this->name    = trim($data['name']);
        $this->groupId = isset($data['group_id']) ? (int) $data['group_id'] : null;
    }
}
