<?php
// ============================================================
// api/users.php — Administrarea utilizatorilor.
// Accesibil EXCLUSIV administratorilor.
// ============================================================

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

header('Content-Type: application/json; charset=utf-8');

// Verificăm că utilizatorul curent este admin (aruncă 403 dacă nu)
require_admin();

$repo   = new UserRepository(get_db());
$method = http_method();
$id     = get_param('id') ? (int) get_param('id') : null;
$action = get_param('action');

match (true) {
    // GET /api/users.php → lista tuturor utilizatorilor
    $method === 'GET'  && $id === null                       => listUsers($repo),
    // PATCH /api/users.php?id=X&action=set_role → schimbă rolul
    $method === 'PATCH' && $id !== null && $action === 'set_role' => setRole($repo, $id),
    // DELETE /api/users.php?id=X → șterge utilizatorul
    $method === 'DELETE' && $id !== null                     => deleteUser($repo, $id),
    default                                                  => json_error('Ruta invalida.', 405),
};

function listUsers(UserRepository $repo): void {
    json_response(['users' => $repo->findAll()]);
}

function setRole(UserRepository $repo, int $id): void {
    $data = json_body();
    $role = $data['role'] ?? '';
    // Validăm că rolul e unul dintre cele permise
    if (!in_array($role, ['user', 'admin'], true)) {
        json_error('Rol invalid. Valorile acceptate: user, admin.');
    }
    // Prevenim demotarea propriului cont de admin
    if ($id === (int) current_user()['id']) {
        json_error('Nu îți poți schimba propriul rol.', 403);
    }
    $repo->setRole($id, $role);
    json_response(['message' => 'Rolul a fost actualizat.']);
}

function deleteUser(UserRepository $repo, int $id): void {
    // Prevenim ștergerea propriului cont
    if ($id === (int) current_user()['id']) {
        json_error('Nu îți poți șterge propriul cont.', 403);
    }
    $repo->delete($id);
    json_response(['message' => 'Utilizatorul a fost șters.']);
}
