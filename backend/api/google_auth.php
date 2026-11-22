<?php

session_start();

require_once __DIR__ . '/../config/google.php';
require_once __DIR__ . '/../lib/GoogleCalendarMeet.php';

if (empty($_SESSION['autenticado']) || empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['correcto' => false, 'mensaje' => 'Authentication required.']);
    exit;
}

$role = $_SESSION['rol'] ?? 'user';
if ($role !== 'professional') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['correcto' => false, 'mensaje' => 'Solo el abogado puede conectar Google Calendar.']);
    exit;
}

$config = pasos_google_config();
if (empty($config['client_id']) || empty($config['client_secret'])) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['correcto' => false, 'mensaje' => 'Falta configurar GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en backend/config/google.local.php.']);
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_user_id'] = (int) $_SESSION['usuario_id'];

$loginHint = isset($_SESSION['usuario']) ? html_entity_decode((string) $_SESSION['usuario'], ENT_QUOTES, 'UTF-8') : null;
header('Location: ' . google_build_auth_url($state, $loginHint));
exit;
