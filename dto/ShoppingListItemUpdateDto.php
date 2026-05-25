<?php
// ============================================================
// dto/ShoppingListItemUpdateDto.php
//
// DTO pentru ACTUALIZAREA unui item dintr-o listă de cumpărături.
// Un item poate fi actualizat în două moduri (sau ambele):
//   - quantity: schimbăm cantitatea (ex: 1 → 3)
//   - checked:  bifăm/debifăm itemul (0 = necumpărat, 1 = cumpărat)
// ============================================================

class ShoppingListItemUpdateDto {

    public readonly ?int $quantity; // Opțional: noua cantitate
    public readonly ?int $checked;  // Opțional: 0 sau 1 (SQLite nu are BOOLEAN)

    private readonly array $provided; // Cheile prezente — pentru semantica PATCH

    public function __construct(array $data) {
        $this->provided = array_keys($data);

        $this->quantity = isset($data['quantity']) ? max(1, (int) $data['quantity']) : null;

        // Convertim la 0 sau 1 — orice valoare truthy devine 1, falsy devine 0.
        // Asta permite clientul să trimită: true/false (JSON) sau 1/0 (int).
        $this->checked = isset($data['checked']) ? ($data['checked'] ? 1 : 0) : null;
    }

    // Verifică dacă câmpul a fost trimis în cerere.
    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    // Construiește array-ul pentru UPDATE SQL — doar câmpurile trimise.
    public function toArray(): array {
        $result = [];
        if ($this->has('quantity')) $result['quantity'] = $this->quantity;
        if ($this->has('checked'))  $result['checked']  = $this->checked;
        return $result;
    }
}
