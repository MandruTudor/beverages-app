<?php

class ShoppingListItemUpdateDto {
    public ?int $quantity;
    public ?int $checked;
    private array $provided;

    public function __construct(array $data) {
        $this->provided = array_keys($data);
        $this->quantity = isset($data['quantity']) ? max(1, (int) $data['quantity']) : null;
        $this->checked  = isset($data['checked'])  ? ($data['checked'] ? 1 : 0)     : null;
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
