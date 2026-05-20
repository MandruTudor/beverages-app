<?php

class PreferenceService {
    public function __construct(private PreferenceRepository $repo) {}

    // Listează toate preferințele (rating + note) ale userului curent
    public function list(): array {
        require_auth();
        return $this->repo->findByUser(current_user()['id']);
    }

    // Creează sau actualizează preferința pentru o băutură (upsert)
    public function upsert(array $data): void {
        require_auth();

        $rating = (int) $data['rating'];

        // Rating-ul trebuie să fie între 1 și 5 conform schemei bazei de date
        if ($rating < 1 || $rating > 5) {
            json_error('Rating-ul trebuie sa fie intre 1 si 5.', 400);
        }

        $this->repo->upsert(
            current_user()['id'],
            (int) $data['beverage_id'],
            $rating,
            $data['notes'] ?? null
        );
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
