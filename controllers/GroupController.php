<?php
class GroupController {
    public function __construct(private GroupService $service) {}

    public function handle(): void {
        $method = http_method();
        $id     = get_param('id')     ? (int) get_param('id') : null;
        $action = get_param('action') ?? null;

        match (true) {
            // Căutare utilizatori după username (pentru add member)
            $method === 'GET'    && $action === 'search_users'                   => $this->searchUsers(),
            // Lista grupurilor userului curent
            $method === 'GET'    && $id === null                                 => $this->list(),
            // Detalii grup cu membri
            $method === 'GET'    && $id !== null                                 => $this->get($id),
            // Creare grup nou
            $method === 'POST'   && $id === null                                 => $this->create(),
            // Ștergere grup
            $method === 'DELETE' && $id !== null && $action === null             => $this->delete($id),
            // Adaugă un membru în grup: POST /groups.php?id=X&action=add_member
            $method === 'POST'   && $id !== null && $action === 'add_member'     => $this->addMember($id),
            // Elimină un membru din grup: DELETE /groups.php?id=X&action=remove_member
            $method === 'DELETE' && $id !== null && $action === 'remove_member'  => $this->removeMember($id),
            default                                                              => json_error('Metoda sau ruta invalida.', 405),
        };
    }

    private function searchUsers(): void {
        json_response(['users' => $this->service->searchUsers(get_param('q') ?? '')]);
    }

    private function list(): void {
        json_response(['groups' => $this->service->list()]);
    }

    private function get(int $id): void {
        json_response(['group' => $this->service->get($id)]);
    }

    private function create(): void {
        // GroupCreateDto validează că 'name' există.
        $dto = new GroupCreateDto(json_body());
        $id  = $this->service->create($dto);
        json_response(['message' => 'Grupul a fost creat cu succes.', 'id' => $id], 201);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Grupul a fost sters cu succes.']);
    }

    private function addMember(int $groupId): void {
        // GroupMemberDto validează că 'user_id' există și e întreg.
        $dto = new GroupMemberDto(json_body());
        $this->service->addMember($groupId, $dto);
        json_response(['message' => 'Membrul a fost adaugat in grup.']);
    }

    private function removeMember(int $groupId): void {
        $dto = new GroupMemberDto(json_body());
        $this->service->removeMember($groupId, $dto);
        json_response(['message' => 'Membrul a fost eliminat din grup.']);
    }
}
