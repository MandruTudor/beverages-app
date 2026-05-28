<?php
// ============================================================
// dto/ShoppingListCreateDto.php
//
// DTO pentru CREAREA unei liste de cumpÄƒrÄƒturi.
// O listÄƒ poate fi personalÄƒ (group_id = null) sau de grup (group_id setat).
// ============================================================

class ShoppingListCreateDto {

    public string $name;     // Obligatoriu: ex. "CumpÄƒrÄƒturi weekend"
    public ?int   $groupId;  // OpÈ›ional: dacÄƒ e setat, lista aparÈ›ine grupului

    public function __construct(array $data) {
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->name    = trim($data['name']);
        // DacÄƒ group_id nu e trimis sau e null, lista e personalÄƒ.
        $this->groupId = isset($data['group_id']) ? (int) $data['group_id'] : null;
    }
}


