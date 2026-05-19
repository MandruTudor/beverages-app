<?php

class PreferenceController {
    public function __construct(private PreferenceService $service) {}

    public function handle(): void {
        $method     = http_method();
        $beverageId = get_param('beverage_id') ? (int) get_param('beverage_id') : null;

        match (true) {
            // Lista tuturor preferințelor userului curent
            $method === 'GET'                                    => $this->list(),
            // Creare sau actualizare preferință (upsert)
            $method === 'POST'                                   => $this->upsert(),
            // Ștergere preferință pentru o băutură: DELETE ?beverage_id=X
            $method === 'DELETE' && $beverageId !== null         => $this->delete($beverageId),
            default                                              => json_error('Metoda sau ruta invalida.', 405),
        };
    }

    private function list(): void {
        json_response(['preferences' => $this->service->list()]);
    }

    private function upsert(): void {
        $data    = json_body();
        $missing = missing_field($data, ['beverage_id', 'rating']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.");
        }
        $this->service->upsert($data);
        json_response(['message' => 'Preferinta salvata cu succes.']);
    }

    private function delete(int $beverageId): void {
        $this->service->delete($beverageId);
        json_response(['message' => 'Preferinta stearsa cu succes.']);
    }
}
