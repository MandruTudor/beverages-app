<?php

class ExportController {
    public function __construct(private ExportService $service) {}

    public function handle(): void {
        if (http_method() !== 'GET') {
            json_error('Metoda invalida. Foloseste GET.', 405);
        }

        // GET /export.php → JSON complet cu toate băuturile și relațiile lor
        $beverages = $this->service->export();
        json_response([
            'exported_at' => date('Y-m-d H:i:s'),
            'count'       => count($beverages),
            'beverages'   => $beverages,
        ]);
    }
}
