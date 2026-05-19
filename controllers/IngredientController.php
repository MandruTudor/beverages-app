<?php

class IngredientController {
    public function __construct(private IngredientService $service) {}

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
        json_response(['ingredients' => $this->service->list()]);
    }

    private function get(int $id): void {
        json_response(['ingredient' => $this->service->get($id)]);
    }

    private function create(): void {
        $data    = json_body();
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.");
        }
        $id = $this->service->create($data);
        json_response(['message' => 'Ingredientul adaugat cu succes.', 'id' => $id], 201);
    }

    private function update(int $id): void {
        $data    = json_body();
        $missing = missing_field($data, ['name']);
        if ($missing) {
            json_error("Campul '$missing' este obligatoriu.");
        }
        $this->service->update($id, $data);
        json_response(['message' => 'Ingredientul actualizat cu succes.']);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Ingredientul sters cu succes.']);
    }
}
