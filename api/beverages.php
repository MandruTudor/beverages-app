<?php

require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

(new BeverageController(
    new BeverageService(
        new BeverageRepository(get_db())
    )
))->handle();
