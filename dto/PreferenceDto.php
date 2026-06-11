<?php

class PreferenceDto {
    public int     $beverageId;
    public int     $rating;
    public ?string $notes;

    public function __construct(array $data) {
        $missing = missing_field($data, ['beverage_id', 'rating']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);

        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) json_error('Rating-ul trebuie sa fie intre 1 si 5.', 400);

        $this->beverageId = (int) $data['beverage_id'];
        $this->rating     = $rating;
        $this->notes      = $data['notes'] ?? null;
    }
}
