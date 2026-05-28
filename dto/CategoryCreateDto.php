<?php
// ============================================================
// dto/CategoryCreateDto.php
//
// DTO = Data Transfer Object â€” un obiect simplu care transportÄƒ
// date de la un strat al aplicaÈ›iei la altul, cu validare È™i tipuri clare.
//
// Acest DTO reprezintÄƒ datele necesare pentru CREAREA unei categorii.
// Este construit de Controller din json_body() È™i pasat la Service.
//
// Avantaje faÈ›Äƒ de a pasa un array simplu:
//   - CÃ¢mpurile obligatorii sunt validate Ã®n constructor (nu Ã®n Controller sau Service)
//   - ProprietÄƒÈ›ile sunt tipizate (string, nu "orice")
//   - Eroarea apare imediat la limita de intrare (Controller), nu mai adÃ¢nc Ã®n cod
// ============================================================

class CategoryCreateDto {

    // public = proprietate publicÄƒ (accesibilÄƒ din exterior) dar imutabilÄƒ
    // (nu poate fi modificatÄƒ dupÄƒ ce a fost setatÄƒ Ã®n constructor).
    // AceastÄƒ imutabilitate garanteazÄƒ cÄƒ datele nu se corup pe parcurs.
    public string  $name;        // Obligatoriu: numele categoriei
    public ?string $description; // OpÈ›ional: descrierea categoriei (? = nullable)

    // Constructorul primeÈ™te array-ul brut de date din json_body()
    // È™i Ã®l valideazÄƒ + converteÈ™te la tipuri clare.
    public function __construct(array $data) {
        // VerificÄƒm cÄƒ 'name' existÄƒ È™i nu e gol.
        // missing_field() returneazÄƒ null dacÄƒ totul e ok, sau numele cÃ¢mpului lipsÄƒ.
        $missing = missing_field($data, ['name']);
        if ($missing) {
            // json_error() trimite rÄƒspunsul HTTP È™i opreÈ™te execuÈ›ia PHP (exit).
            // 400 Bad Request = datele trimise de client sunt invalide.
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        // trim() eliminÄƒ spaÈ›iile de la capete: "  Ceaiuri  " â†’ "Ceaiuri"
        $this->name = trim($data['name']);

        // ?? null = operatorul null-coalescing: dacÄƒ cheia 'description' nu existÄƒ Ã®n array
        // (sau e null), stocÄƒm null. Nu aruncÄƒm eroare â€” cÃ¢mpul e opÈ›ional.
        // trim() aplicat doar dacÄƒ valoarea existÄƒ (nu pe null).
        $this->description = isset($data['description'])
            ? trim($data['description'])
            : null;
    }
}


