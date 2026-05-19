<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/ShoppingListRepository.php';
require_once __DIR__ . '/../services/ShoppingListService.php';
require_once __DIR__ . '/../controllers/ShoppingListController.php';

header('Content-Type: application/json; charset=utf-8');

(new ShoppingListController(
    new ShoppingListService(
        new ShoppingListRepository(get_db())
    )
))->handle();
