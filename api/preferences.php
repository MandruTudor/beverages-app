<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/PreferenceRepository.php';
require_once __DIR__ . '/../services/PreferenceService.php';
require_once __DIR__ . '/../controllers/PreferenceController.php';

header('Content-Type: application/json; charset=utf-8');

(new PreferenceController(
    new PreferenceService(
        new PreferenceRepository(get_db())
    )
))->handle();
