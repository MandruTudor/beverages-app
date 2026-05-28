<?php
// ============================================================
// dto/GroupCreateDto.php
//
// DTO pentru CREAREA unui grup de utilizatori.
// Un grup are un singur cÃ¢mp obligatoriu: name.
// Creatorul e determinat din sesiunea curentÄƒ (nu din body-ul cererii).
// ============================================================

class GroupCreateDto {

    public string $name; // Obligatoriu: ex. "Familia Popescu"

    public function __construct(array $data) {
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        $this->name = trim($data['name']);
    }
}


