<?php
// ============================================================
// dto/GroupMemberDto.php
//
// DTO pentru operaÈ›iunile pe membrii unui grup:
//   - adÄƒugare membru (POST ?action=add_member)
//   - eliminare membru (DELETE ?action=remove_member)
// Ambele operaÈ›ii necesitÄƒ ID-ul utilizatorului È›intÄƒ.
// ============================================================

class GroupMemberDto {

    public int $userId; // Obligatoriu: ID-ul userului de adÄƒugat/eliminat

    public function __construct(array $data) {
        $missing = missing_field($data, ['user_id']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.", 400);
        }

        // (int) converteÈ™te string-ul din JSON la integer PHP.
        // ID-urile sunt Ã®ntotdeauna numere Ã®ntregi pozitive.
        $this->userId = (int) $data['user_id'];
    }
}


