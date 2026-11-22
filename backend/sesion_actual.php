<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['correcto' => false, 'mensaje' => 'Method not allowed.']);
    exit;
}

if (!empty($_SESSION['autenticado'])) {
    http_response_code(200);
    echo json_encode([
        'correcto' => true,
        'autenticado' => true,
        'rol' => $_SESSION['rol'] ?? 'user',
    ]);
    exit;
}

http_response_code(401);
echo json_encode([
    'correcto' => false,
    'autenticado' => false,
    'mensaje' => 'Authentication required.',
]);
