<?php
class VenueController {
    public function __construct(private VenueService $service) {}

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
        json_response(['venues' => $this->service->list()]);
    }

    private function get(int $id): void {
        json_response(['venue' => $this->service->get($id)]);
    }

    private function create(): void {
        // VenueCreateDto validează 'name' ca obligatoriu; location și contact sunt opționale.
        $dto = new VenueCreateDto(json_body());
        $id  = $this->service->create($dto);
        json_response(['message' => 'Localul adaugat cu succes.', 'id' => $id], 201);
    }

    private function update(int $id): void {
        // VenueUpdateDto acceptă toate câmpurile ca opționale.
        $dto = new VenueUpdateDto(json_body());
        $this->service->update($id, $dto);
        json_response(['message' => 'Localul actualizat cu succes.']);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Localul sters cu succes.']);
    }
}
