<?php

class StatsRepository {
    public function __construct(private PDO $pdo) {}

    // Totaluri generale: număr băuturi, useri, categorii, preferințe
    public function getTotals(): array {
        return [
            'beverages'   => (int) $this->pdo->query('SELECT COUNT(*) FROM beverages')->fetchColumn(),
            'users'       => (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'categories'  => (int) $this->pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
            'ingredients' => (int) $this->pdo->query('SELECT COUNT(*) FROM ingredients')->fetchColumn(),
            'preferences' => (int) $this->pdo->query('SELECT COUNT(*) FROM user_preferences')->fetchColumn(),
        ];
    }

    // Băuturile cele mai bine evaluate (media rating-urilor)
    // HAVING COUNT(*) >= 1 asigură că apar doar băuturile cu cel puțin un rating
    public function getTopRated(int $limit = 5): array {
        $stmt = $this->pdo->prepare('
            SELECT b.id, b.name, b.price,
                   ROUND(AVG(up.rating), 2) AS avg_rating,
                   COUNT(up.id) AS rating_count
            FROM beverages b
            JOIN user_preferences up ON up.beverage_id = b.id
            GROUP BY b.id
            HAVING COUNT(up.id) >= 1
            ORDER BY avg_rating DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // Numărul de băuturi per categorie
    public function getByCategory(): array {
        return $this->pdo->query('
            SELECT c.name AS category, COUNT(b.id) AS count
            FROM categories c
            LEFT JOIN beverages b ON b.category_id = c.id
            GROUP BY c.id
            ORDER BY count DESC
        ')->fetchAll();
    }

    // Distribuția băuturilor pe sezoane
    public function getBySeason(): array {
        return $this->pdo->query('
            SELECT COALESCE(season, \'necunoscut\') AS season, COUNT(*) AS count
            FROM beverages
            GROUP BY season
            ORDER BY count DESC
        ')->fetchAll();
    }
}
