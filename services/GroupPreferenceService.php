<?php

class GroupPreferenceService {
    public function __construct(private GroupPreferenceRepository $repo) {}

    public function list(int $groupId): array {
        require_auth();
        $this->assertMember($groupId);
        return $this->repo->findByGroup($groupId);
    }

    public function upsert(GroupPreferenceDto $dto): void {
        require_auth();
        $this->assertMember($dto->groupId);
        $this->repo->upsert($dto->groupId, $dto->beverageId, $dto->rating, $dto->notes);
    }

    public function delete(int $groupId, int $beverageId): void {
        require_auth();
        $this->assertMember($groupId);
        $this->repo->delete($groupId, $beverageId);
    }

    private function assertMember(int $groupId): void {
        $stmt = get_db()->prepare(
            'SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?'
        );
        $stmt->execute([$groupId, current_user()['id']]);
        if (!$stmt->fetch()) {
            json_error('Nu esti membru al acestui grup.', 403);
        }
    }
}
