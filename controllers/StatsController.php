<?php

class StatsController {
    public function __construct(private StatsService $service) {}

    public function handle(): void {
        // Stats e read-only — acceptăm doar GET
        if (http_method() !== 'GET') {
            json_error('Metoda invalida. Foloseste GET.', 405);
        }

        $action = get_param('action') ?? 'summary';

        match ($action) {
            // GET /stats.php          → totals + by_category + by_season
            'summary'    => $this->summary(),
            // GET /stats.php?action=top_rated&limit=5
            'top_rated'  => $this->topRated(),
            default      => json_error('Actiune invalida.', 404),
        };
    }

    private function summary(): void {
        json_response($this->service->summary());
    }

    private function topRated(): void {
        // Limita e opțională, default 5, maxim 20
        $limit = min((int) (get_param('limit') ?? 5), 20);
        json_response(['top_rated' => $this->service->topRated($limit)]);
    }
}
