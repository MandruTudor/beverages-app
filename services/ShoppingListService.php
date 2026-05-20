<?php

class ShoppingListService {
    public function __construct(private ShoppingListRepository $repo) {}

    public function list(): array {
        require_auth();
        return $this->repo->findByUser(current_user()['id']);
    }

    public function get(int $id): array {
        require_auth();

        $list = $this->repo->findById($id);
        if (!$list) {
            json_error('Lista nu a fost gasita.', 404);
        }

        $this->assertAccess($list);

        $list['items'] = $this->repo->getItems($id);
        return $list;
    }

    public function create(array $data): int {
        require_auth();
        return $this->repo->create(
            current_user()['id'],
            trim($data['name']),
            isset($data['group_id']) ? (int) $data['group_id'] : null
        );
    }

    public function delete(int $id): void {
        require_auth();

        $list = $this->repo->findById($id);
        if (!$list) {
            json_error('Lista nu a fost gasita.', 404);
        }

        $this->assertAccess($list);
        $this->repo->delete($id);
    }

    public function addItem(int $listId, array $data): int {
        require_auth();

        $list = $this->repo->findById($listId);
        if (!$list) {
            json_error('Lista nu a fost gasita.', 404);
        }

        $this->assertAccess($list);

        return $this->repo->addItem(
            $listId,
            (int) $data['beverage_id'],
            (int) ($data['quantity'] ?? 1)
        );
    }

    public function updateItem(int $itemId, array $data): void {
        require_auth();

        $item = $this->repo->findItemById($itemId);
        if (!$item) {
            json_error('Itemul nu a fost gasit.', 404);
        }

        // Verificăm că userul are acces la lista din care face parte itemul
        $list = $this->repo->findById($item['list_id']);
        $this->assertAccess($list);

        $this->repo->updateItem($itemId, $data);
    }

    public function removeItem(int $itemId): void {
        require_auth();

        $item = $this->repo->findItemById($itemId);
        if (!$item) {
            json_error('Itemul nu a fost gasit.', 404);
        }

        $list = $this->repo->findById($item['list_id']);
        $this->assertAccess($list);

        $this->repo->removeItem($itemId);
    }

    // Verifică că userul curent are dreptul să acceseze lista:
    // fie este creatorul listei, fie este membru al grupului listei
    private function assertAccess(array $list): void {
        $userId = current_user()['id'];

        if ($list['user_id'] === $userId) {
            return; // Lista personală a userului
        }

        if ($list['group_id'] !== null) {
            // Verificăm apartenența la grup prin query direct
            // (nu avem GroupRepository injectat, dar putem folosi get_db())
            $stmt = get_db()->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmt->execute([$list['group_id'], $userId]);
            if ($stmt->fetch()) {
                return; // Userul e membru al grupului listei
            }
        }

        json_error('Nu ai acces la aceasta lista.', 403);
    }
}
