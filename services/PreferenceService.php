<?php

class PreferenceService {
    public function __construct(private PreferenceRepository $repo) {}

    public function list(): array {
        require_auth();
        return $this->repo->findByUser(current_user()['id']);
    }

    public function upsert(PreferenceDto $dto): void {
        require_auth();
        $this->repo->upsert(current_user()['id'], $dto->beverageId, $dto->rating, $dto->notes);
    }

    public function recommendations(int $limit = 6): array {
        require_auth();
        return $this->repo->getRecommendations(current_user()['id'], $limit);
    }

    public function delete(int $beverageId): void {
        require_auth();
        $userId = current_user()['id'];
        if (!$this->repo->findByUserAndBeverage($userId, $beverageId)) {
            json_error('Nu ai o preferinta salvata pentru aceasta bautura.', 404);
        }
        $this->repo->delete($userId, $beverageId);
    }
}
