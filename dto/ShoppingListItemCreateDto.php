<?php

class ShoppingListItemCreateDto {
    public int $beverageId;
    public int $quantity;

    public function __construct(array $data) {
        $missing = missing_field($data, ['beverage_id']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);
        $this->beverageId = (int) $data['beverage_id'];
        $this->quantity   = isset($data['quantity']) ? max(1, (int) $data['quantity']) : 1;
    }
}
