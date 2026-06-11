<?php

class BeverageUpdateDto {
    public ?string $name;
    public ?int    $categoryId;
    public ?float  $price;
    public ?string $description;
    public ?string $imagePath;
    public ?int    $perishabilityDays;
    public ?string $validUntil;
    public ?string $season;
    public ?string $region;
    // null = not sent (leave untouched); [] = sent empty (delete all relations)
    public ?array  $ingredients;
    public ?array  $allergens;
    public ?array  $venues;
    public ?array  $dietaryRegimes;
    private array  $provided;

    public function __construct(array $data) {
        $this->provided          = array_keys($data);
        $this->name              = isset($data['name'])              ? trim($data['name'])              : null;
        $this->categoryId        = isset($data['category_id'])       ? (int)   $data['category_id']       : null;
        $this->price             = isset($data['price'])             ? (float) $data['price']             : null;
        $this->description       = isset($data['description'])       ? trim($data['description'])         : null;
        $this->imagePath         = isset($data['image_path'])        ? trim($data['image_path'])          : null;
        $this->perishabilityDays = isset($data['perishability_days']) ? (int) $data['perishability_days'] : null;
        $this->validUntil        = isset($data['valid_until'])        ? trim($data['valid_until'])         : null;
        $this->season            = $data['season'] ?? null;
        $this->region            = isset($data['region'])            ? trim($data['region'])              : null;
        // array_key_exists (not isset) so an explicit [] is detected as "provided but empty"
        $this->ingredients    = array_key_exists('ingredients',     $data) ? $data['ingredients']     : null;
        $this->allergens      = array_key_exists('allergens',       $data) ? $data['allergens']       : null;
        $this->venues         = array_key_exists('venues',          $data) ? $data['venues']          : null;
        $this->dietaryRegimes = array_key_exists('dietary_regimes', $data) ? $data['dietary_regimes'] : null;
    }

    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    public function toScalarArray(): array {
        $map = [
            'name'               => $this->name,
            'category_id'        => $this->categoryId,
            'price'              => $this->price,
            'description'        => $this->description,
            'image_path'         => $this->imagePath,
            'perishability_days' => $this->perishabilityDays,
            'valid_until'        => $this->validUntil,
            'season'             => $this->season,
            'region'             => $this->region,
        ];
        $result = [];
        foreach ($map as $col => $val) {
            if ($this->has($col)) $result[$col] = $val;
        }
        return $result;
    }
}
