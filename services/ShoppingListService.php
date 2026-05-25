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

    // Acceptă ShoppingListCreateDto — name tipizat, groupId ca ?int.
    public function create(ShoppingListCreateDto $dto): int {
        require_auth();
        return $this->repo->create(
            current_user()['id'], // ID-ul proprietarului listei — din sesiune
            $dto->name,           // string tipizat și trimmed în DTO
            $dto->groupId         // ?int — null dacă lista e personală
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

    // Acceptă ShoppingListItemCreateDto — beverageId și quantity tipizate.
    public function addItem(int $listId, ShoppingListItemCreateDto $dto): int {
        require_auth();

        $list = $this->repo->findById($listId);
        if (!$list) {
            json_error('Lista nu a fost gasita.', 404);
        }

        $this->assertAccess($list);

        // Repository-ul primește valorile individuale tipizate.
        return $this->repo->addItem($listId, $dto->beverageId, $dto->quantity);
    }

    // Acceptă ShoppingListItemUpdateDto — $dto->toArray() returnează câmpurile trimise.
    public function updateItem(int $itemId, ShoppingListItemUpdateDto $dto): void {
        require_auth();

        $item = $this->repo->findItemById($itemId);
        if (!$item) {
            json_error('Itemul nu a fost gasit.', 404);
        }

        // Verificăm că userul are acces la lista din care face parte itemul.
        $list = $this->repo->findById($item['list_id']);
        $this->assertAccess($list);

        // Repository-ul primește array-ul dinamic cu câmpurile de actualizat.
        $this->repo->updateItem($itemId, $dto->toArray());
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
    // fie este creatorul listei, fie este membru al grupului listei.
    private function assertAccess(array $list): void {
        $userId = current_user()['id'];

        // Dacă lista e a userului curent, accesul e permis imediat.
        if ($list['user_id'] === $userId) {
            return;
        }

        // Dacă lista aparține unui grup, verificăm apartenența userului la acel grup.
        if ($list['group_id'] !== null) {
            $stmt = get_db()->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmt->execute([$list['group_id'], $userId]);
            if ($stmt->fetch()) {
                return; // Userul e membru al grupului — acces permis.
            }
        }

        json_error('Nu ai acces la aceasta lista.', 403);
    }
}
