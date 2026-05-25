<?php
// ============================================================
// dto/CategoryUpdateDto.php
//
// DTO pentru ACTUALIZAREA parțială (PATCH) a unei categorii.
//
// Diferența față de CategoryCreateDto:
//   - Niciun câmp nu este obligatoriu (PATCH = modifici doar ce trimiți)
//   - Trebuie să știm ce câmpuri AU FOST trimise vs. care lipsesc,
//     ca să nu suprascriem date cu null accidental
//
// Exemplu:
//   PATCH cu { "description": "nou" } → actualizăm DOAR descrierea, name rămâne neschimbat
//   PATCH cu { "name": "X", "description": null } → actualizăm ambele (description devine NULL)
// ============================================================

class CategoryUpdateDto {

    // Câmpurile posibile ale categoriei — toate opționale (nullable).
    public readonly ?string $name;
    public readonly ?string $description;

    // Array-ul cu cheile prezente în cererea originală.
    // Necesar pentru semantica PATCH: dacă 'name' nu e în cerere,
    // NU actualizăm coloana name în baza de date.
    // Dacă 'name' E în cerere (chiar și cu valoarea null), O actualizăm.
    private readonly array $provided;

    public function __construct(array $data) {
        // Salvăm lista cheilor prezente ÎNAINTE de orice transformare.
        // array_keys() returnează array-ul cu cheile array-ului asociativ.
        // Ex: ['name', 'description'] sau ['description'] sau []
        $this->provided = array_keys($data);

        // Dacă 'name' e prezent: îl trimăm. Dacă lipsește: null.
        // isset() vs array_key_exists(): isset() returnează false pentru valori null.
        // Folosim isset() pentru name deoarece nu are sens un name null (e ignorat).
        $this->name = isset($data['name']) ? trim($data['name']) : null;

        // Pentru description, valoarea null e validă (înseamnă "șterge descrierea"),
        // deci folosim array_key_exists pentru a distinge "absent" de "setat la null".
        // Totuși, în practică PHP json_decode() nu diferențiază absent vs. null pentru noi,
        // dar ?? null acoperă ambele cazuri corect.
        $this->description = $data['description'] ?? null;
    }

    // Verifică dacă un câmp a fost explicit inclus în cererea PATCH.
    // Returnează true chiar dacă valoarea câmpului e null.
    //
    // Ex: $dto->has('name') → true dacă { "name": "X" } sau { "name": null }
    //                       → false dacă câmpul 'name' lipsea complet din JSON
    public function has(string $field): bool {
        // in_array() verifică dacă $field există în array-ul $this->provided.
        // Al treilea argument true = comparare strictă (tip + valoare).
        return in_array($field, $this->provided, true);
    }

    // Construiește array-ul de câmpuri pentru UPDATE SQL.
    // Returnează DOAR câmpurile care au fost trimise în cerere.
    // Repository-ul primește acest array și actualizează dinamic doar aceste coloane.
    public function toArray(): array {
        $result = [];

        // Adăugăm fiecare câmp în rezultat NUMAI dacă a fost prezent în cerere.
        if ($this->has('name'))        $result['name']        = $this->name;
        if ($this->has('description')) $result['description'] = $this->description;

        return $result;
    }
}
