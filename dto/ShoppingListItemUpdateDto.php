<?php

class ShoppingListItemUpdateDto {
    public ?int $quantity;
    public ?int $checked;
    private array $provided;

    public function __construct(array $data) {
        $this->provided = array_keys($data);

        // Validăm cantitatea la update: 0 și negative sunt interzise
        if (isset($data['quantity'])) {
            $qty = (int) $data['quantity'];
            if ($qty <= 0) json_error('Cantitatea trebuie sa fie mai mare decat 0.', 400);
            $this->quantity = $qty;
        } else {
            $this->quantity = null;
        }

        $this->checked = isset($data['checked']) ? ($data['checked'] ? 1 : 0) : null;
    }

    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    public function toArray(): array {
        $result = [];
        if ($this->has('quantity')) $result['quantity'] = $this->quantity;
        if ($this->has('checked'))  $result['checked']  = $this->checked;
        return $result;
    }
}
