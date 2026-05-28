<?php
// ============================================================
// dto/CategoryUpdateDto.php
//
// DTO pentru ACTUALIZAREA parÈ›ialÄƒ (PATCH) a unei categorii.
//
// DiferenÈ›a faÈ›Äƒ de CategoryCreateDto:
//   - Niciun cÃ¢mp nu este obligatoriu (PATCH = modifici doar ce trimiÈ›i)
//   - Trebuie sÄƒ È™tim ce cÃ¢mpuri AU FOST trimise vs. care lipsesc,
//     ca sÄƒ nu suprascriem date cu null accidental
//
// Exemplu:
//   PATCH cu { "description": "nou" } â†’ actualizÄƒm DOAR descrierea, name rÄƒmÃ¢ne neschimbat
//   PATCH cu { "name": "X", "description": null } â†’ actualizÄƒm ambele (description devine NULL)
// ============================================================

class CategoryUpdateDto {

    // CÃ¢mpurile posibile ale categoriei â€” toate opÈ›ionale (nullable).
    public ?string $name;
    public ?string $description;

    // Array-ul cu cheile prezente Ã®n cererea originalÄƒ.
    // Necesar pentru semantica PATCH: dacÄƒ 'name' nu e Ã®n cerere,
    // NU actualizÄƒm coloana name Ã®n baza de date.
    // DacÄƒ 'name' E Ã®n cerere (chiar È™i cu valoarea null), O actualizÄƒm.
    private array $provided;

    public function __construct(array $data) {
        // SalvÄƒm lista cheilor prezente ÃŽNAINTE de orice transformare.
        // array_keys() returneazÄƒ array-ul cu cheile array-ului asociativ.
        // Ex: ['name', 'description'] sau ['description'] sau []
        $this->provided = array_keys($data);

        // DacÄƒ 'name' e prezent: Ã®l trimÄƒm. DacÄƒ lipseÈ™te: null.
        // isset() vs array_key_exists(): isset() returneazÄƒ false pentru valori null.
        // Folosim isset() pentru name deoarece nu are sens un name null (e ignorat).
        $this->name = isset($data['name']) ? trim($data['name']) : null;

        // Pentru description, valoarea null e validÄƒ (Ã®nseamnÄƒ "È™terge descrierea"),
        // deci folosim array_key_exists pentru a distinge "absent" de "setat la null".
        // TotuÈ™i, Ã®n practicÄƒ PHP json_decode() nu diferenÈ›iazÄƒ absent vs. null pentru noi,
        // dar ?? null acoperÄƒ ambele cazuri corect.
        $this->description = $data['description'] ?? null;
    }

    // VerificÄƒ dacÄƒ un cÃ¢mp a fost explicit inclus Ã®n cererea PATCH.
    // ReturneazÄƒ true chiar dacÄƒ valoarea cÃ¢mpului e null.
    //
    // Ex: $dto->has('name') â†’ true dacÄƒ { "name": "X" } sau { "name": null }
    //                       â†’ false dacÄƒ cÃ¢mpul 'name' lipsea complet din JSON
    public function has(string $field): bool {
        // in_array() verificÄƒ dacÄƒ $field existÄƒ Ã®n array-ul $this->provided.
        // Al treilea argument true = comparare strictÄƒ (tip + valoare).
        return in_array($field, $this->provided, true);
    }

    // ConstruieÈ™te array-ul de cÃ¢mpuri pentru UPDATE SQL.
    // ReturneazÄƒ DOAR cÃ¢mpurile care au fost trimise Ã®n cerere.
    // Repository-ul primeÈ™te acest array È™i actualizeazÄƒ dinamic doar aceste coloane.
    public function toArray(): array {
        $result = [];

        // AdÄƒugÄƒm fiecare cÃ¢mp Ã®n rezultat NUMAI dacÄƒ a fost prezent Ã®n cerere.
        if ($this->has('name'))        $result['name']        = $this->name;
        if ($this->has('description')) $result['description'] = $this->description;

        return $result;
    }
}


