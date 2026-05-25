<?php
// ============================================================
// dto/GroupMemberDto.php
//
// DTO pentru operațiunile pe membrii unui grup:
//   - adăugare membru (POST ?action=add_member)
//   - eliminare membru (DELETE ?action=remove_member)
// Ambele operații necesită ID-ul utilizatorului țintă.
// ============================================================

class GroupMemberDto {

    public readonly int $userId; // Obligatoriu: ID-ul userului de adăugat/eliminat

    public function __construct(array $data) {
        $missing = missing_field($data, ['user_id']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        // (int) convertește string-ul din JSON la integer PHP.
        // ID-urile sunt întotdeauna numere întregi pozitive.
        $this->userId = (int) $data['user_id'];
    }
}
