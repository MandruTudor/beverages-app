<?php

class GroupMemberDto {
    public int $userId;

    public function __construct(array $data) {
        $missing = missing_field($data, ['user_id']);
        if ($missing) json_error("Campul '$missing' este obligatoriu.", 400);
        $this->userId = (int) $data['user_id'];
    }
}
