<?php
// ============================================================
// dto/BeverageUpdateDto.php
//
// DTO pentru ACTUALIZAREA parțială (PATCH) a unei băuturi.
// Toate câmpurile sunt opționale — PATCH înseamnă că actualizezi
// doar ce trimiți, restul rămâne neschimbat în baza de date.
//
// Relațiile (ingredients, allergens, venues) funcționează diferit:
// dacă sunt trimise, se RESCRIU complet (sync: DELETE + INSERT).
// Dacă nu sunt trimise, rămân neatinse.
// ============================================================

class BeverageUpdateDto {

    // Toate câmpurile sunt nullable — pot lipsi din cerere.
    public readonly ?string $name;
    public readonly ?int    $categoryId;
    public readonly ?float  $price;
    public readonly ?string $description;
    public readonly ?string $imagePath;
    public readonly ?int    $perishabilityDays;
    public readonly ?string $season;
    public readonly ?string $region;

    // Relațiile many-to-many — null înseamnă "nu au fost trimise, nu le atinge".
    // [] (array gol) înseamnă "au fost trimise dar goale → șterge toate relațiile".
    public readonly ?array $ingredients;
    public readonly ?array $allergens;
    public readonly ?array $venues;

    // Cheile prezente în cererea HTTP originală — fundamentale pentru semantica PATCH.
    private readonly array $provided;

    public function __construct(array $data) {
        $this->provided = array_keys($data);

        // Câmpuri scalare: null dacă lipsesc din cerere.
        $this->name              = isset($data['name'])              ? trim($data['name'])              : null;
        $this->categoryId        = isset($data['category_id'])       ? (int)   $data['category_id']       : null;
        $this->price             = isset($data['price'])             ? (float) $data['price']             : null;
        $this->description       = isset($data['description'])       ? trim($data['description'])         : null;
        $this->imagePath         = isset($data['image_path'])        ? trim($data['image_path'])          : null;
        $this->perishabilityDays = isset($data['perishability_days'])? (int)   $data['perishability_days']: null;
        $this->season            = $data['season']  ?? null;
        $this->region            = isset($data['region'])            ? trim($data['region'])              : null;

        // Relații: null dacă LIPSESC din cerere; array (chiar gol) dacă SUNT în cerere.
        // Folosim array_key_exists (nu isset) ca să detectăm și valoarea [] (array gol explicit).
        $this->ingredients = array_key_exists('ingredients', $data) ? $data['ingredients'] : null;
        $this->allergens   = array_key_exists('allergens',   $data) ? $data['allergens']   : null;
        $this->venues      = array_key_exists('venues',      $data) ? $data['venues']      : null;
    }

    // Verifică dacă un câmp a fost trimis în cerere (chiar dacă valoarea e null).
    // Folositoare în Service pentru a decide ce câmpuri să actualizeze.
    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    // Construiește array-ul de câmpuri scalare pentru UPDATE SQL.
    // Returnează DOAR câmpurile scalar care au fost prezente în cerere.
    // Repository-ul construiește dinamic clauza SET doar din aceste câmpuri.
    public function toScalarArray(): array {
        // Mapăm: proprietate DTO → coloana SQL corespunzătoare.
        $map = [
            'name'               => $this->name,
            'category_id'        => $this->categoryId,
            'price'              => $this->price,
            'description'        => $this->description,
            'image_path'         => $this->imagePath,
            'perishability_days' => $this->perishabilityDays,
            'season'             => $this->season,
            'region'             => $this->region,
        ];

        // Filtrăm: returnăm doar intrările ale căror chei au fost prezente în cerere.
        // array_flip() invertează array-ul provided: ['a','b'] → ['a'=>0,'b'=>1]
        // array_intersect_key() păstrează doar cheile din $map care există și în provided.
        return array_intersect_key($map, array_flip(
            array_intersect($this->provided, array_keys($map))
        ));
    }
}
