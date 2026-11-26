<?php

require_once __DIR__ . '/lib/RequestGuard.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') pasos_require_write();
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['correcto' => false]);
    exit;
}

$captcha = $_POST['captcha'] ?? null;
$correcto = count($_POST) === 1 && is_string($captcha) && preg_match('/^[0-9]{1,3}$/D', $captcha) &&
    isset($_SESSION['captcha']) && (int) $captcha === $_SESSION['captcha'];
unset($_SESSION['captcha'], $_SESSION['captcha_validado']);

if ($correcto) {
    $_SESSION['captcha_validado'] = true;
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode(['correcto' => $correcto]);
