<?php
/**
 * api/shopping_lists.php
 * Endpoint pentru resursa: shopping_lists
 *
 * TODO: de implementat
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

$action = get_param('action') ?? '';

json_error('Endpoint nesimplemented inca.', 501);
