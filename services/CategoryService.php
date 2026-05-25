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

    // Acceptă CategoryCreateDto în loc de array — tipuri clare, validare deja făcută în DTO.
    public function create(CategoryCreateDto $dto): int {
        require_admin();

        // Verificăm unicitatea: nu pot exista două categorii cu același nume.
        // Accesăm proprietatea tipizată $dto->name (string garantat, nu array key).
        if ($this->repo->existsByName($dto->name)) {
            json_error('O categorie cu acest nume exista deja.', 409);
        }

        // Repository-ul primește valorile individuale tipizate, nu un array anonim.
        return $this->repo->create($dto->name, $dto->description);
    }

    // Acceptă CategoryUpdateDto — conține logica de PATCH (care câmpuri au fost trimise).
    public function update(int $id, CategoryUpdateDto $dto): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Categoria nu a fost gasita.', 404);
        }

        // Verificăm unicitatea doar dacă 'name' a fost trimis în cerere.
        // $dto->has('name') returnează true chiar dacă valoarea e null.
        if ($dto->has('name') && $dto->name !== null && $this->repo->existsByName($dto->name, $id)) {
            json_error('O categorie cu acest nume exista deja.', 409);
        }

        // $dto->toArray() construiește array-ul cu DOAR câmpurile trimise în cerere.
        // Repository-ul actualizează dinamic doar aceste coloane (semantica PATCH).
        $this->repo->update($id, $dto->toArray());
    }

    public function delete(int $id): void {
        require_admin();

        if (!$this->repo->findById($id)) {
            json_error('Categoria nu a fost gasita.', 404);
        }

        // ON DELETE SET NULL în schema SQL: băuturile din această categorie
        // vor avea category_id = NULL după ștergere, nu vor fi și ele șterse.
        $this->repo->delete($id);
    }
}
