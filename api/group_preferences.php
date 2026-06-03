<?php

require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

(new GroupPreferenceController(
    new GroupPreferenceService(
        new GroupPreferenceRepository(get_db())
    )
))->handle();
