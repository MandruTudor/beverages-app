<?php
// ============================================================
// dto/ShoppingListItemCreateDto.php
//
// DTO pentru ADÄ‚UGAREA unui item Ã®ntr-o listÄƒ de cumpÄƒrÄƒturi.
// Un item = o bÄƒuturÄƒ + cantitatea doritÄƒ.
// ============================================================

class ShoppingListItemCreateDto {

    public int $beverageId; // Obligatoriu: ID-ul bÄƒuturii de adÄƒugat
    public int $quantity;   // OpÈ›ional: cantitatea, default 1

    public function __construct(array $data) {
        $missing = missing_field($data, ['beverage_id']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->beverageId = (int) $data['beverage_id'];
        // Cantitatea e minimum 1 â€” folosim max() pentru siguranÈ›Äƒ.
        // DacÄƒ nu e trimisÄƒ, default 1 (o unitate).
        $this->quantity   = isset($data['quantity']) ? max(1, (int) $data['quantity']) : 1;
    }
}


