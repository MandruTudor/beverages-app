<?php
class PreferenceController {
    public function __construct(private PreferenceService $service) {}

    public function handle(): void {
        $method     = http_method();
        $beverageId = get_param('beverage_id') ? (int) get_param('beverage_id') : null;
        $action     = get_param('action');

        match (true) {
            $method === 'GET' && $action === 'recommendations' => $this->recommendations(),
            $method === 'GET'                                  => $this->list(),
            $method === 'POST'                                 => $this->upsert(),
            $method === 'DELETE' && $beverageId !== null       => $this->delete($beverageId),
            default                                            => json_error('Metoda sau ruta invalida.', 405),
        };
    }

    private function recommendations(): void {
        $limit = min((int) (get_param('limit') ?? 6), 12);
        json_response(['recommendations' => $this->service->recommendations($limit)]);
    }

    private function list(): void {
        json_response(['preferences' => $this->service->list()]);
    }

    private function upsert(): void {
        $dto = new PreferenceDto(json_body());
        $this->service->upsert($dto);
        json_response(['message' => 'Preferinta salvata cu succes.']);
    }

    private function delete(int $beverageId): void {
        $this->service->delete($beverageId);
        json_response(['message' => 'Preferinta stearsa cu succes.']);
    }
}
