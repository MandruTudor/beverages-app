<?php
// ============================================================
// dto/CategoryCreateDto.php
//
// DTO = Data Transfer Object — un obiect simplu care transportă
// date de la un strat al aplicației la altul, cu validare și tipuri clare.
//
// Acest DTO reprezintă datele necesare pentru CREAREA unei categorii.
// Este construit de Controller din json_body() și pasat la Service.
//
// Avantaje față de a pasa un array simplu:
//   - Câmpurile obligatorii sunt validate în constructor (nu în Controller sau Service)
//   - Proprietățile sunt tipizate (string, nu "orice")
//   - Eroarea apare imediat la limita de intrare (Controller), nu mai adânc în cod
// ============================================================

class CategoryCreateDto {

    // public readonly = proprietate publică (accesibilă din exterior) dar imutabilă
    // (nu poate fi modificată după ce a fost setată în constructor).
    // Această imutabilitate garantează că datele nu se corup pe parcurs.
    public readonly string  $name;        // Obligatoriu: numele categoriei
    public readonly ?string $description; // Opțional: descrierea categoriei (? = nullable)

    // Constructorul primește array-ul brut de date din json_body()
    // și îl validează + convertește la tipuri clare.
    public function __construct(array $data) {
        // Verificăm că 'name' există și nu e gol.
        // missing_field() returnează null dacă totul e ok, sau numele câmpului lipsă.
        $missing = missing_field($data, ['name']);
        if ($missing) {
            // json_error() trimite răspunsul HTTP și oprește execuția PHP (exit).
            // 400 Bad Request = datele trimise de client sunt invalide.
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        // trim() elimină spațiile de la capete: "  Ceaiuri  " → "Ceaiuri"
        $this->name = trim($data['name']);

        // ?? null = operatorul null-coalescing: dacă cheia 'description' nu există în array
        // (sau e null), stocăm null. Nu aruncăm eroare — câmpul e opțional.
        // trim() aplicat doar dacă valoarea există (nu pe null).
        $this->description = isset($data['description'])
            ? trim($data['description'])
            : null;
    }
}
