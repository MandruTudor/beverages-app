<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/CategoryRepository.php';
require_once __DIR__ . '/../services/CategoryService.php';
require_once __DIR__ . '/../controllers/CategoryController.php';

header('Content-Type: application/json; charset=utf-8');

(new CategoryController(
    new CategoryService(
        new CategoryRepository(get_db())
    )
))->handle();
