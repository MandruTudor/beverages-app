<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../repositories/VenueRepository.php';
require_once __DIR__ . '/../services/VenueService.php';
require_once __DIR__ . '/../controllers/VenueController.php';

header('Content-Type: application/json; charset=utf-8');

(new VenueController(
    new VenueService(
        new VenueRepository(get_db())
    )
))->handle();
