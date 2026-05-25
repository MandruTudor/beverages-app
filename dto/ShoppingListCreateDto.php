<?php
// ============================================================
// dto/ShoppingListCreateDto.php
//
// DTO pentru CREAREA unei liste de cumpărături.
// O listă poate fi personală (group_id = null) sau de grup (group_id setat).
// ============================================================

class ShoppingListCreateDto {

    public readonly string $name;     // Obligatoriu: ex. "Cumpărături weekend"
    public readonly ?int   $groupId;  // Opțional: dacă e setat, lista aparține grupului

    public function __construct(array $data) {
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->name    = trim($data['name']);
        // Dacă group_id nu e trimis sau e null, lista e personală.
        $this->groupId = isset($data['group_id']) ? (int) $data['group_id'] : null;
    }
}
