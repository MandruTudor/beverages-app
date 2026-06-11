<?php

class CategoryUpdateDto {
    public ?string $name;
    public ?string $description;
    private array  $provided;

    public function __construct(array $data) {
        $this->provided    = array_keys($data);
        $this->name        = isset($data['name'])        ? trim($data['name'])        : null;
        $this->description = isset($data['description']) ? trim($data['description']) : null;
    }

    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    public function toArray(): array {
        $result = [];
        if ($this->has('name'))        $result['name']        = $this->name;
        if ($this->has('description')) $result['description'] = $this->description;
        return $result;
    }
}
