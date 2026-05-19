<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../controllers/AuthController.php';

header('Content-Type: application/json; charset=utf-8');

(new AuthController(
    new AuthService(
        new UserRepository(get_db())
    )
))->handle(get_param('action') ?? '');
