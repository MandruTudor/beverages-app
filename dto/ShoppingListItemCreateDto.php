<?php
// ============================================================
// dto/ShoppingListItemCreateDto.php
//
// DTO pentru ADĂUGAREA unui item într-o listă de cumpărături.
// Un item = o băutură + cantitatea dorită.
// ============================================================

class ShoppingListItemCreateDto {

    public readonly int $beverageId; // Obligatoriu: ID-ul băuturii de adăugat
    public readonly int $quantity;   // Opțional: cantitatea, default 1

    public function __construct(array $data) {
        $missing = missing_field($data, ['beverage_id']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->beverageId = (int) $data['beverage_id'];
        // Cantitatea e minimum 1 — folosim max() pentru siguranță.
        // Dacă nu e trimisă, default 1 (o unitate).
        $this->quantity   = isset($data['quantity']) ? max(1, (int) $data['quantity']) : 1;
    }
}
