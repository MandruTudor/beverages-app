<?php

class GroupPreferenceController {
    public function __construct(private GroupPreferenceService $service) {}

    public function handle(): void {
        $method   = http_method();
        $groupId  = get_param('group_id') ? (int) get_param('group_id') : null;

        match (true) {
            // GET /api/group_preferences.php?group_id=X → lista preferintelor grupului
            $method === 'GET'    && $groupId !== null => $this->list($groupId),
            // POST /api/group_preferences.php → upsert preferinta
            $method === 'POST'                        => $this->upsert(),
            // DELETE /api/group_preferences.php?group_id=X&beverage_id=Y
            $method === 'DELETE' && $groupId !== null => $this->delete($groupId),
            default => json_error('Metoda sau ruta invalida.', 405),
        };
    }

    private function list(int $groupId): void {
        json_response(['preferences' => $this->service->list($groupId)]);
    }

    private function upsert(): void {
        $dto = new GroupPreferenceDto(json_body());
        $this->service->upsert($dto);
        json_response(['message' => 'Preferinta de grup salvata cu succes.']);
    }

    private function delete(int $groupId): void {
        $beverageId = get_param('beverage_id') ? (int) get_param('beverage_id') : null;
        if (!$beverageId) {
            json_error('Campul beverage_id este obligatoriu.', 400);
        }
        $this->service->delete($groupId, $beverageId);
        json_response(['message' => 'Preferinta de grup stearsa.']);
    }
}
