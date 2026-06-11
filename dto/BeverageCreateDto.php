<?php

class BeverageCreateDto {
    public string  $name;
    public ?int    $categoryId;
    public ?float  $price;
    public ?string $description;
    public ?string $imagePath;
    public ?int    $perishabilityDays;
    public ?string $validUntil;
    public ?string $season;
    public ?string $region;
    public array   $ingredients;
    public array   $allergens;
    public array   $venues;
    public array   $dietaryRegimes;

    public function __construct(array $data) {
        $missing = missing_field($data, ['name']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);

        $this->name              = trim($data['name']);
        $this->categoryId        = isset($data['category_id'])        ? (int)   $data['category_id']        : null;
        $this->price             = isset($data['price'])              ? (float) $data['price']              : null;
        $this->description       = isset($data['description'])        ? trim($data['description'])          : null;
        $this->imagePath         = isset($data['image_path'])         ? trim($data['image_path'])           : null;
        $this->perishabilityDays = isset($data['perishability_days']) ? (int)   $data['perishability_days'] : null;
        $this->validUntil        = isset($data['valid_until'])        ? trim($data['valid_until'])          : null;
        $this->season            = $data['season']  ?? null;
        $this->region            = isset($data['region'])             ? trim($data['region'])               : null;
        $this->ingredients       = $data['ingredients']     ?? [];
        $this->allergens         = $data['allergens']       ?? [];
        $this->venues            = $data['venues']          ?? [];
        $this->dietaryRegimes    = $data['dietary_regimes'] ?? [];
    }
}
