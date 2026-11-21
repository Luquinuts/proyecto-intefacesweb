<?php

session_start();

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../lib/GoogleCalendarMeet.php';

function redirect_frontend(string $params): void
{
    $frontendUrl = getenv('PASOS_FRONTEND_URL') ?: 'http://localhost/proyecto-intefacesweb/frontend/index.html';
    $location = rtrim($frontendUrl, '/') . '#consultations' . $params;
    header('Location: ' . $location);
    exit;
}

if (isset($_GET['error'])) {
    redirect_frontend('?google=error');
}

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';
$expectedState = $_SESSION['google_oauth_state'] ?? '';
$userId = (int) ($_SESSION['google_oauth_user_id'] ?? ($_SESSION['usuario_id'] ?? 0));

if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state) || $userId <= 0) {
    redirect_frontend('?google=state_error');
}

unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_user_id']);

$tokenResult = google_exchange_code($code);
if (!$tokenResult['ok']) {
    redirect_frontend('?google=token_error');
}

$accessToken = $tokenResult['data']['access_token'] ?? null;
$googleEmail = $accessToken ? google_fetch_user_email($accessToken) : null;
$saved = google_save_tokens($conexion, $userId, $tokenResult['data'], $googleEmail);
if (!$saved) {
    redirect_frontend('?google=save_error');
}

redirect_frontend('?google=connected');
