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
        if (!$list) json_error('Lista nu a fost gasita.', 404);
        $this->assertAccess($list);
        $list['items'] = $this->repo->getItems($id);
        return $list;
    }

    public function create(ShoppingListCreateDto $dto): int {
        require_auth();
        return $this->repo->create(current_user()['id'], $dto->name, $dto->groupId);
    }

    public function delete(int $id): void {
        require_auth();
        $list = $this->repo->findById($id);
        if (!$list) json_error('Lista nu a fost gasita.', 404);
        $this->assertAccess($list);
        $this->repo->delete($id);
    }

    public function addItem(int $listId, ShoppingListItemCreateDto $dto): int {
        require_auth();
        $list = $this->repo->findById($listId);
        if (!$list) json_error('Lista nu a fost gasita.', 404);
        $this->assertAccess($list);
        return $this->repo->addItem($listId, $dto->beverageId, $dto->quantity);
    }

    public function updateItem(int $itemId, ShoppingListItemUpdateDto $dto): void {
        require_auth();
        $item = $this->repo->findItemById($itemId);
        if (!$item) json_error('Itemul nu a fost gasit.', 404);
        $this->assertAccess($this->repo->findById($item['list_id']));
        $this->repo->updateItem($itemId, $dto->toArray());
    }

    public function removeItem(int $itemId): void {
        require_auth();
        $item = $this->repo->findItemById($itemId);
        if (!$item) json_error('Itemul nu a fost gasit.', 404);
        $this->assertAccess($this->repo->findById($item['list_id']));
        $this->repo->removeItem($itemId);
    }

    // Access granted if user owns the list, or is a member of the list's group
    private function assertAccess(array $list): void {
        $userId = current_user()['id'];
        if ($list['user_id'] === $userId) return;
        if ($list['group_id'] !== null) {
            $stmt = get_db()->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmt->execute([$list['group_id'], $userId]);
            if ($stmt->fetch()) return;
        }
        json_error('Nu ai acces la aceasta lista.', 403);
    }
}
