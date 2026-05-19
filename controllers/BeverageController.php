<?php

class BeverageController {
    public function __construct(private BeverageService $service) {}

    public function handle(): void {
        $method = http_method();
        $id     = get_param('id') ? (int) get_param('id') : null;

        match (true) {
            $method === 'GET'    && $id === null => $this->list(),
            $method === 'GET'    && $id !== null => $this->get($id),
            $method === 'POST'                   => $this->create(),
            $method === 'PATCH'  && $id !== null => $this->update($id),
            $method === 'DELETE' && $id !== null => $this->delete($id),
            default                              => json_error('Metoda sau ruta invalida.', 405),
        };
    }

    private function list(): void {
        $filters = array_filter([
            'category_id' => get_param('category_id'),
            'season'      => get_param('season'),
            'region'      => get_param('region'),
        ], fn($v) => $v !== null);

        json_response(['beverages' => $this->service->list($filters)]);
    }

    private function get(int $id): void {
        json_response(['beverage' => $this->service->get($id)]);
    }

    private function create(): void {
        $data    = json_body();
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.");
        }
        $id = $this->service->create($data);
        json_response(['message' => 'Bautura adaugata cu succes.', 'id' => $id], 201);
    }

    private function update(int $id): void {
        $this->service->update($id, json_body());
        json_response(['message' => 'Bautura actualizata cu succes.']);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Bautura stearsa cu succes.']);
    }
}
