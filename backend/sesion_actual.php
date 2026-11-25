<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['correcto' => false, 'mensaje' => 'Method not allowed.']);
    exit;
}

if (!empty($_SESSION['autenticado']) && !empty($_SESSION['usuario_id'])) {
    require_once __DIR__ . '/config/conexion.php';
    $userId = (int) $_SESSION['usuario_id'];
    $statement = $conexion->prepare('SELECT phone FROM users WHERE id = ? LIMIT 1');
    if (!$statement || !$statement->bind_param('i', $userId) || !$statement->execute()) {
        http_response_code(500);
        echo json_encode(['correcto' => false, 'mensaje' => 'No se pudo leer el perfil.']);
        exit;
    }
    $profile = $statement->get_result()->fetch_assoc();
    $statement->close();
    http_response_code(200);
    echo json_encode([
        'correcto' => true,
        'autenticado' => true,
        'rol' => $_SESSION['rol'] ?? 'user',
        'phone' => $profile['phone'] ?? null,
    ]);
    exit;
}

http_response_code(401);
echo json_encode([
    'correcto' => false,
    'autenticado' => false,
    'mensaje' => 'Authentication required.',
]);
