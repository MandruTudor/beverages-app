<?php

class ShoppingListItemCreateDto {
    public int $beverageId;
    public int $quantity;

    public function __construct(array $data) {
        $missing = missing_field($data, ['beverage_id']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);
        $this->beverageId = (int) $data['beverage_id'];

        // Validăm cantitatea: 0 și valori negative sunt interzise explicit
        if (isset($data['quantity'])) {
            $qty = (int) $data['quantity'];
            if ($qty <= 0) json_error('Cantitatea trebuie sa fie mai mare decat 0.', 400);
            $this->quantity = $qty;
        } else {
            $this->quantity = 1; // valoare implicită dacă nu e trimisă
        }
    }
}
