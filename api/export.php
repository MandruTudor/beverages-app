<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/ExportRepository.php';
require_once __DIR__ . '/../services/ExportService.php';
require_once __DIR__ . '/../controllers/ExportController.php';

header('Content-Type: application/json; charset=utf-8');

(new ExportController(
    new ExportService(
        new ExportRepository(get_db())
    )
))->handle();
