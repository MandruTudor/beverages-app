<?php
class GroupController {
    public function __construct(private GroupService $service) {}

    public function handle(): void {
        $method = http_method();
        $id     = get_param('id')     ? (int) get_param('id') : null;
        $action = get_param('action') ?? null;

        match (true) {
            $method === 'GET'    && $action === 'search_users'                  => $this->searchUsers(),
            $method === 'GET'    && $id === null                                => $this->list(),
            $method === 'GET'    && $id !== null                                => $this->get($id),
            $method === 'POST'   && $id === null                                => $this->create(),
            $method === 'DELETE' && $id !== null && $action === null            => $this->delete($id),
            $method === 'POST'   && $id !== null && $action === 'add_member'    => $this->addMember($id),
            $method === 'DELETE' && $id !== null && $action === 'remove_member' => $this->removeMember($id),
            default                                                             => json_error('Metoda sau ruta invalida.', 405),
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
        $dto = new GroupCreateDto(json_body());
        $id  = $this->service->create($dto);
        json_response(['message' => 'Grupul a fost creat cu succes.', 'id' => $id], 201);
    }

    private function delete(int $id): void {
        $this->service->delete($id);
        json_response(['message' => 'Grupul a fost sters cu succes.']);
    }

    private function addMember(int $groupId): void {
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
