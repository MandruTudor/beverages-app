<?php

class StatsService {
    public function __construct(private StatsRepository $repo) {}

    // Statistici generale — accesibile oricărui user logat
    public function summary(): array {
        require_auth();
        return [
            'totals'      => $this->repo->getTotals(),
            'by_category' => $this->repo->getByCategory(),
            'by_season'   => $this->repo->getBySeason(),
        ];
    }

    // Top băuturi evaluate — accesibile oricărui user logat
    public function topRated(int $limit = 5): array {
        require_auth();
        return $this->repo->getTopRated($limit);
    }
}
