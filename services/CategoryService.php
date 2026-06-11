<?php

class CategoryService {
    public function __construct(private CategoryRepository $repo) {}

    public function list(): array {
        return $this->repo->findAll();
    }

    public function get(int $id): array {
        $category = $this->repo->findById($id);
        if (!$category) json_error('Categoria nu a fost gasita.', 404);
        return $category;
    }

    public function create(CategoryCreateDto $dto): int {
        require_admin();
        if ($this->repo->existsByName($dto->name)) {
            json_error('O categorie cu acest nume exista deja.', 409);
        }
        return $this->repo->create($dto->name, $dto->description);
    }

    public function update(int $id, CategoryUpdateDto $dto): void {
        require_admin();
        if (!$this->repo->findById($id)) json_error('Categoria nu a fost gasita.', 404);
        if ($dto->has('name') && $dto->name !== null && $this->repo->existsByName($dto->name, $id)) {
            json_error('O categorie cu acest nume exista deja.', 409);
        }
        $this->repo->update($id, $dto->toArray());
    }

    public function delete(int $id): void {
        require_admin();
        if (!$this->repo->findById($id)) json_error('Categoria nu a fost gasita.', 404);
        // ON DELETE SET NULL: beverages keep their row but get category_id = NULL
        $this->repo->delete($id);
    }
}
