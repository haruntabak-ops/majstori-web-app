<?php
/*
 * Entry point of the REST API.
 * Every request to /backend/... is sent here (see .htaccess).
 * For now it only proves that PHP runs – FlightPHP and the real routes come in week 05.
 */
header('Content-Type: application/json');
echo json_encode([
    'app' => 'Majstori Web App API',
    'message' => 'The backend is running.'
]);