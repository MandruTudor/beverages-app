<?php

class CategoryService {
    public function __construct(private CategoryRepository $repo) {}

    public function list(): array {
        return $this->repo->findAll();
    }

    public function get(int $id): array {
        $category = $this->repo->findById($id);
        if (!$category) {
            json_error('Categoria nu a fost gasita.', 404);
        }
        return $category;
    }

    public function create(array $data): int {
        // Doar adminul poate adăuga categorii noi
        require_admin();

        $name = trim($data['name']);

        // Verificăm unicitatea numelui înainte de inserare
        if ($this->repo->existsByName($name)) {
            json_error('O categorie cu acest nume exista deja.', 409);
        }

        return $this->repo->create($name, $data['description'] ?? null);
    }

    public function update(int $id, array $data): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Categoria nu a fost gasita.', 404);
        }

        // Verificăm unicitatea numelui nou față de celelalte categorii (excludem pe cea curentă)
        if (isset($data['name']) && $this->repo->existsByName(trim($data['name']), $id)) {
            json_error('O categorie cu acest nume exista deja.', 409);
        }

        $this->repo->update($id, $data);
    }

    public function delete(int $id): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Categoria nu a fost gasita.', 404);
        }

        // ON DELETE SET NULL în schema SQL: băuturile din această categorie
        // vor avea category_id = NULL după ștergere, nu vor fi și ele șterse
        $this->repo->delete($id);
    }
}
