<?php
// NameDto e partajat — regimurile au doar câmpul 'name', ca alergenii și ingredientele.
class DietaryRegimeController {
    public function __construct(private DietaryRegimeService $service) {}

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
        json_response(['dietary_regimes' => $this->service->list()]);
    }

    private function get(int $id): void {
        json_response(['dietary_regime' => $this->service->get($id)]);
    }

    private function create(): void {
        $dto = new NameDto(json_body());
        $id  = $this->service->create($dto);
        json_response(['message' => 'Regimul alimentar adaugat cu succes.', 'id' => $id], 201);
    }

    private function update(int $id): void {
        $dto = new NameDto(json_body());
        $this->service->update($id, $dto);
        json_response(['message' => 'Regimul alimentar actualizat cu succes.']);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Regimul alimentar sters cu succes.']);
    }
}
