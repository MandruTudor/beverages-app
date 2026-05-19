<?php

class ExportService {
    public function __construct(private ExportRepository $repo) {}

    // Exportul e disponibil doar adminilor — conține toate datele
    public function export(): array {
        require_admin();
        return $this->repo->getAllBeverages();
    }
}
