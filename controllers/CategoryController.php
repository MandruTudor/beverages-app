<?php
// DTOs necesare pentru acest controller — incluse înainte de declararea clasei.
class CategoryController {
    // Constructor cu injecție de dependință: Service-ul e pasat din afară (din api/categories.php).
    public function __construct(private CategoryService $service) {}

    // Rutează cererea HTTP la metoda corectă în funcție de metodă și prezența ?id=
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
        json_response(['categories' => $this->service->list()]);
    }

    private function get(int $id): void {
        json_response(['category' => $this->service->get($id)]);
    }

    private function create(): void {
        // Construim DTO-ul din body-ul cererii JSON.
        // CategoryCreateDto validează intern că 'name' există și nu e gol.
        // Dacă validarea eșuează, constructorul apelează json_error() și oprește execuția.
        $dto = new CategoryCreateDto(json_body());
        $id  = $this->service->create($dto);
        json_response(['message' => 'Categoria adaugata cu succes.', 'id' => $id], 201);
    }

    private function update(int $id): void {
        // CategoryUpdateDto acceptă orice câmpuri — niciun câmp nu e obligatoriu la PATCH.
        $dto = new CategoryUpdateDto(json_body());
        $this->service->update($id, $dto);
        json_response(['message' => 'Categoria actualizata cu succes.']);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Categoria stearsa cu succes.']);
    }
}
