<?php

header('Content-Type: application/json; charset=utf-8');

try {
    if (!@session_start()) {
        throw new RuntimeException('Session initialization failed');
    }

    $numberOne = random_int(1, 10);
    $numberTwo = random_int(1, 10);
    $_SESSION['captcha'] = $numberOne + $numberTwo;

    echo json_encode([
        'pregunta' => $numberOne . ' + ' . $numberTwo,
    ]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'error' => 'No se pudo generar el captcha.',
    ]);
}
