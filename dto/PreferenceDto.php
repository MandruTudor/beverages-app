<?php
// ============================================================
// dto/PreferenceDto.php
//
// DTO pentru salvarea (upsert) unei preferinÈ›e.
// O preferinÈ›Äƒ = rating + note ale unui user pentru o bÄƒuturÄƒ.
// OperaÈ›ia e Ã®ntotdeauna upsert (INSERT OR REPLACE) â€” acelaÈ™i DTO
// acoperÄƒ atÃ¢t crearea cÃ¢t È™i actualizarea.
// ============================================================

class PreferenceDto {

    public int     $beverageId; // Obligatoriu: ID-ul bÄƒuturii evaluate
    public int     $rating;     // Obligatoriu: nota 1â€“5 stele
    public ?string $notes;      // OpÈ›ional: notiÈ›Äƒ personalÄƒ liberÄƒ

    public function __construct(array $data) {
        // ValidÄƒm cÃ¢mpurile obligatorii.
        $missing = missing_field($data, ['beverage_id', 'rating']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->beverageId = (int) $data['beverage_id'];
        $rating           = (int) $data['rating'];

        // ValidÄƒm cÄƒ rating-ul e Ã®n intervalul 1â€“5, conform constrÃ¢ngerii din SQL:
        // CHECK(rating BETWEEN 1 AND 5) definit Ã®n init.sql.
        // ValidÄƒm la nivel de DTO (nu doar DB) pentru mesaj de eroare mai clar.
        if ($rating < 1 || $rating > 5) {
            json_error('Rating-ul trebuie sa fie intre 1 si 5.', 400);
        }

        $this->rating = $rating;
        $this->notes  = $data['notes'] ?? null;
    }
}


