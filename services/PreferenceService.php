<?php

class PreferenceService {
    public function __construct(private PreferenceRepository $repo) {}

    // Listează toate preferințele (rating + note) ale userului curent.
    public function list(): array {
        require_auth();
        return $this->repo->findByUser(current_user()['id']);
    }

    // Acceptă PreferenceDto — beverage_id, rating (validat 1-5 în DTO), notes.
    // Validarea rating-ului se face acum în DTO, nu în Service — responsabilitate clară.
    public function upsert(PreferenceDto $dto): void {
        require_auth();

        // Repository-ul primește valorile individuale tipizate, nu un array anonim.
        $this->repo->upsert(
            current_user()['id'], // ID-ul userului curent din sesiune
            $dto->beverageId,     // int — tipizat în DTO
            $dto->rating,         // int 1-5 — validat în DTO
            $dto->notes           // ?string — null dacă nu a fost trimis
        );
    }

    public function recommendations(int $limit = 6): array {
        require_auth();
        return $this->repo->getRecommendations(current_user()['id'], $limit);
    }

    public function delete(int $beverageId): void {
        require_auth();

        $userId = current_user()['id'];

        // Verificăm că preferința există înainte să o ștergem.
        if (!$this->repo->findByUserAndBeverage($userId, $beverageId)) {
            json_error('Nu ai o preferinta salvata pentru aceasta bautura.', 404);
        }

        $this->repo->delete($userId, $beverageId);
    }
}
