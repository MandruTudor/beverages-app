<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/StatsRepository.php';
require_once __DIR__ . '/../services/StatsService.php';
require_once __DIR__ . '/../controllers/StatsController.php';

header('Content-Type: application/json; charset=utf-8');

(new StatsController(
    new StatsService(
        new StatsRepository(get_db())
    )
))->handle();
