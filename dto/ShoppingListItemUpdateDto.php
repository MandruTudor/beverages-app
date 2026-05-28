<?php
// ============================================================
// dto/ShoppingListItemUpdateDto.php
//
// DTO pentru ACTUALIZAREA unui item dintr-o listÄƒ de cumpÄƒrÄƒturi.
// Un item poate fi actualizat Ã®n douÄƒ moduri (sau ambele):
//   - quantity: schimbÄƒm cantitatea (ex: 1 â†’ 3)
//   - checked:  bifÄƒm/debifÄƒm itemul (0 = necumpÄƒrat, 1 = cumpÄƒrat)
// ============================================================

class ShoppingListItemUpdateDto {

    public ?int $quantity; // OpÈ›ional: noua cantitate
    public ?int $checked;  // OpÈ›ional: 0 sau 1 (SQLite nu are BOOLEAN)

    private array $provided; // Cheile prezente â€” pentru semantica PATCH

    public function __construct(array $data) {
        $this->provided = array_keys($data);

        $this->quantity = isset($data['quantity']) ? max(1, (int) $data['quantity']) : null;

        // Convertim la 0 sau 1 â€” orice valoare truthy devine 1, falsy devine 0.
        // Asta permite clientul sÄƒ trimitÄƒ: true/false (JSON) sau 1/0 (int).
        $this->checked = isset($data['checked']) ? ($data['checked'] ? 1 : 0) : null;
    }

    // VerificÄƒ dacÄƒ cÃ¢mpul a fost trimis Ã®n cerere.
    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    // ConstruieÈ™te array-ul pentru UPDATE SQL â€” doar cÃ¢mpurile trimise.
    public function toArray(): array {
        $result = [];
        if ($this->has('quantity')) $result['quantity'] = $this->quantity;
        if ($this->has('checked'))  $result['checked']  = $this->checked;
        return $result;
    }
}


