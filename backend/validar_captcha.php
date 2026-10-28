<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

$captcha = isset($_POST['captcha']) ? filter_var($_POST['captcha'], FILTER_VALIDATE_INT) : false;
$correcto = $captcha !== false && isset($_SESSION['captcha']) && $captcha === $_SESSION['captcha'];

if ($correcto) {
    unset($_SESSION['captcha']);
    $_SESSION['captcha_validado'] = true;
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode(['correcto' => $correcto]);
