<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/GroupRepository.php';
require_once __DIR__ . '/../services/GroupService.php';
require_once __DIR__ . '/../controllers/GroupController.php';

header('Content-Type: application/json; charset=utf-8');

(new GroupController(
    new GroupService(
        new GroupRepository(get_db())
    )
))->handle();
