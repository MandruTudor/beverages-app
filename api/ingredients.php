<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/IngredientRepository.php';
require_once __DIR__ . '/../services/IngredientService.php';
require_once __DIR__ . '/../controllers/IngredientController.php';

header('Content-Type: application/json; charset=utf-8');

(new IngredientController(
    new IngredientService(
        new IngredientRepository(get_db())
    )
))->handle();
