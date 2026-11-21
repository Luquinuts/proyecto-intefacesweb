<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../lib/GoogleCalendarMeet.php';

if (empty($_SESSION['autenticado']) || empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['correcto' => false, 'mensaje' => 'Authentication required.']);
    exit;
}

$userId = (int) $_SESSION['usuario_id'];
$role = $_SESSION['rol'] ?? 'user';

if ($role !== 'professional') {
    echo json_encode(['correcto' => true, 'connected' => false, 'google_email' => null]);
    exit;
}

$row = google_get_token_row($conexion, $userId);
$config = pasos_google_config();
$configured = !empty($config['client_id']) && !empty($config['client_secret']);

echo json_encode([
    'correcto' => true,
    'connected' => $row !== null && !empty($row['refresh_token']),
    'google_email' => $row['google_email'] ?? null,
    'configured' => $configured,
]);
