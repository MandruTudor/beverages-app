<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/AllergenRepository.php';
require_once __DIR__ . '/../services/AllergenService.php';
require_once __DIR__ . '/../controllers/AllergenController.php';

header('Content-Type: application/json; charset=utf-8');

(new AllergenController(
    new AllergenService(
        new AllergenRepository(get_db())
    )
))->handle();
