<?php
// ============================================================
// dto/PreferenceDto.php
//
// DTO pentru salvarea (upsert) unei preferințe.
// O preferință = rating + note ale unui user pentru o băutură.
// Operația e întotdeauna upsert (INSERT OR REPLACE) — același DTO
// acoperă atât crearea cât și actualizarea.
// ============================================================

class PreferenceDto {

    public readonly int     $beverageId; // Obligatoriu: ID-ul băuturii evaluate
    public readonly int     $rating;     // Obligatoriu: nota 1–5 stele
    public readonly ?string $notes;      // Opțional: notiță personală liberă

    public function __construct(array $data) {
        // Validăm câmpurile obligatorii.
        $missing = missing_field($data, ['beverage_id', 'rating']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->beverageId = (int) $data['beverage_id'];
        $rating           = (int) $data['rating'];

        // Validăm că rating-ul e în intervalul 1–5, conform constrângerii din SQL:
        // CHECK(rating BETWEEN 1 AND 5) definit în init.sql.
        // Validăm la nivel de DTO (nu doar DB) pentru mesaj de eroare mai clar.
        if ($rating < 1 || $rating > 5) {
            json_error('Rating-ul trebuie sa fie intre 1 si 5.', 400);
        }

        $this->rating = $rating;
        $this->notes  = $data['notes'] ?? null;
    }
}
