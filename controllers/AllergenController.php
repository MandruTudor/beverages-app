<?php
// NameDto e partajat cu IngredientController — ambele resurse au doar câmpul 'name'.
require_once __DIR__ . '/../dto/NameDto.php';

class AllergenController {
    public function __construct(private AllergenService $service) {}

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
        json_response(['allergens' => $this->service->list()]);
    }

    private function get(int $id): void {
        json_response(['allergen' => $this->service->get($id)]);
    }

    private function create(): void {
        // NameDto validează că 'name' există și nu e gol.
        $dto = new NameDto(json_body());
        $id  = $this->service->create($dto);
        json_response(['message' => 'Alergenul adaugat cu succes.', 'id' => $id], 201);
    }

    private function update(int $id): void {
        // La actualizare, 'name' e și el obligatoriu (allergen-ul are doar acest câmp).
        $dto = new NameDto(json_body());
        $this->service->update($id, $dto);
        json_response(['message' => 'Alergenul actualizat cu succes.']);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Alergenul sters cu succes.']);
    }
}
