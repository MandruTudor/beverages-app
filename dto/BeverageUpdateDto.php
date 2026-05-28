<?php
// ============================================================
// dto/BeverageUpdateDto.php
//
// DTO pentru ACTUALIZAREA parÈ›ialÄƒ (PATCH) a unei bÄƒuturi.
// Toate cÃ¢mpurile sunt opÈ›ionale â€” PATCH Ã®nseamnÄƒ cÄƒ actualizezi
// doar ce trimiÈ›i, restul rÄƒmÃ¢ne neschimbat Ã®n baza de date.
//
// RelaÈ›iile (ingredients, allergens, venues) funcÈ›ioneazÄƒ diferit:
// dacÄƒ sunt trimise, se RESCRIU complet (sync: DELETE + INSERT).
// DacÄƒ nu sunt trimise, rÄƒmÃ¢n neatinse.
// ============================================================

class BeverageUpdateDto {

    // Toate cÃ¢mpurile sunt nullable â€” pot lipsi din cerere.
    public ?string $name;
    public ?int    $categoryId;
    public ?float  $price;
    public ?string $description;
    public ?string $imagePath;
    public ?int    $perishabilityDays;
    public ?string $season;
    public ?string $region;

    // RelaÈ›iile many-to-many â€” null Ã®nseamnÄƒ "nu au fost trimise, nu le atinge".
    // [] (array gol) Ã®nseamnÄƒ "au fost trimise dar goale â†’ È™terge toate relaÈ›iile".
    public ?array $ingredients;
    public ?array $allergens;
    public ?array $venues;

    // Cheile prezente Ã®n cererea HTTP originalÄƒ â€” fundamentale pentru semantica PATCH.
    private array $provided;

    public function __construct(array $data) {
        $this->provided = array_keys($data);

        // CÃ¢mpuri scalare: null dacÄƒ lipsesc din cerere.
        $this->name              = isset($data['name'])              ? trim($data['name'])              : null;
        $this->categoryId        = isset($data['category_id'])       ? (int)   $data['category_id']       : null;
        $this->price             = isset($data['price'])             ? (float) $data['price']             : null;
        $this->description       = isset($data['description'])       ? trim($data['description'])         : null;
        $this->imagePath         = isset($data['image_path'])        ? trim($data['image_path'])          : null;
        $this->perishabilityDays = isset($data['perishability_days'])? (int)   $data['perishability_days']: null;
        $this->season            = $data['season']  ?? null;
        $this->region            = isset($data['region'])            ? trim($data['region'])              : null;

        // RelaÈ›ii: null dacÄƒ LIPSESC din cerere; array (chiar gol) dacÄƒ SUNT Ã®n cerere.
        // Folosim array_key_exists (nu isset) ca sÄƒ detectÄƒm È™i valoarea [] (array gol explicit).
        $this->ingredients = array_key_exists('ingredients', $data) ? $data['ingredients'] : null;
        $this->allergens   = array_key_exists('allergens',   $data) ? $data['allergens']   : null;
        $this->venues      = array_key_exists('venues',      $data) ? $data['venues']      : null;
    }

    // VerificÄƒ dacÄƒ un cÃ¢mp a fost trimis Ã®n cerere (chiar dacÄƒ valoarea e null).
    // Folositoare Ã®n Service pentru a decide ce cÃ¢mpuri sÄƒ actualizeze.
    public function has(string $field): bool {
        return in_array($field, $this->provided, true);
    }

    // ConstruieÈ™te array-ul de cÃ¢mpuri scalare pentru UPDATE SQL.
    // ReturneazÄƒ DOAR cÃ¢mpurile scalar care au fost prezente Ã®n cerere.
    // Repository-ul construieÈ™te dinamic clauza SET doar din aceste cÃ¢mpuri.
    public function toScalarArray(): array {
        // MapÄƒm: proprietate DTO â†’ coloana SQL corespunzÄƒtoare.
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

        // FiltrÄƒm: returnÄƒm doar intrÄƒrile ale cÄƒror chei au fost prezente Ã®n cerere.
        // array_flip() inverteazÄƒ array-ul provided: ['a','b'] â†’ ['a'=>0,'b'=>1]
        // array_intersect_key() pÄƒstreazÄƒ doar cheile din $map care existÄƒ È™i Ã®n provided.
        return array_intersect_key($map, array_flip(
            array_intersect($this->provided, array_keys($map))
        ));
    }
}


