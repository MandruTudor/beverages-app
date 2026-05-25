<?php
require_once __DIR__ . '/../dto/ShoppingListCreateDto.php';
require_once __DIR__ . '/../dto/ShoppingListItemCreateDto.php';
require_once __DIR__ . '/../dto/ShoppingListItemUpdateDto.php';

class ShoppingListController {
    public function __construct(private ShoppingListService $service) {}

    public function handle(): void {
        $method = http_method();
        $id     = get_param('id')      ? (int) get_param('id')      : null;
        $itemId = get_param('item_id') ? (int) get_param('item_id') : null;
        $action = get_param('action') ?? null;

        match (true) {
            // Lista tuturor listelor userului curent
            $method === 'GET'    && $id === null && $action === null              => $this->list(),
            // Detalii lista cu items
            $method === 'GET'    && $id !== null                                  => $this->get($id),
            // Creare lista nouă
            $method === 'POST'   && $id === null                                  => $this->create(),
            // Ștergere lista
            $method === 'DELETE' && $id !== null && $action === null              => $this->delete($id),
            // Adaugă item în lista: POST /shopping_lists.php?id=X&action=add_item
            $method === 'POST'   && $id !== null && $action === 'add_item'        => $this->addItem($id),
            // Actualizează item: PATCH /shopping_lists.php?action=update_item&item_id=X
            $method === 'PATCH'  && $action === 'update_item' && $itemId !== null => $this->updateItem($itemId),
            // Șterge item: DELETE /shopping_lists.php?action=remove_item&item_id=X
            $method === 'DELETE' && $action === 'remove_item' && $itemId !== null => $this->removeItem($itemId),
            default                                                               => json_error('Metoda sau ruta invalida.', 405),
        };
    }

    private function list(): void {
        json_response(['lists' => $this->service->list()]);
    }

    private function get(int $id): void {
        json_response(['list' => $this->service->get($id)]);
    }

    private function create(): void {
        // ShoppingListCreateDto validează 'name' ca obligatoriu; group_id e opțional.
        $dto = new ShoppingListCreateDto(json_body());
        $id  = $this->service->create($dto);
        json_response(['message' => 'Lista creata cu succes.', 'id' => $id], 201);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Lista stearsa cu succes.']);
    }

    private function addItem(int $listId): void {
        // ShoppingListItemCreateDto validează 'beverage_id' ca obligatoriu.
        $dto = new ShoppingListItemCreateDto(json_body());
        $id  = $this->service->addItem($listId, $dto);
        json_response(['message' => 'Itemul a fost adaugat in lista.', 'id' => $id], 201);
    }

    private function updateItem(int $itemId): void {
        // ShoppingListItemUpdateDto acceptă quantity și/sau checked — ambele opționale.
        $dto = new ShoppingListItemUpdateDto(json_body());
        $this->service->updateItem($itemId, $dto);
        json_response(['message' => 'Itemul a fost actualizat.']);
    }

    private function removeItem(int $itemId): void {
        $this->service->removeItem($itemId);
        json_response(['message' => 'Itemul a fost eliminat din lista.']);
    }
}
