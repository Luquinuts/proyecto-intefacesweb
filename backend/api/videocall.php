<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, int $status): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (empty($_SESSION['autenticado']) || empty($_SESSION['usuario_id'])) {
    respond(['correcto' => false, 'mensaje' => 'Authentication required.'], 401);
}

require_once __DIR__ . '/../config/conexion.php';
$userId = (int) $_SESSION['usuario_id'];
$role = $_SESSION['rol'] ?? 'user';

if ($role === 'professional') {
    $statement = $conexion->prepare(
        'SELECT id, scheduled_at, meeting_link, status FROM consultations 
         WHERE professional_id = ? AND contact_preference = "video" AND scheduled_at IS NOT NULL AND meeting_link IS NOT NULL AND meeting_link != "" 
         ORDER BY scheduled_at ASC'
    );
    $statement->bind_param('i', $userId);
} elseif ($role === 'user') {
    $statement = $conexion->prepare(
        'SELECT id, scheduled_at, meeting_link, status FROM consultations 
         WHERE user_id = ? AND contact_preference = "video" AND scheduled_at IS NOT NULL AND meeting_link IS NOT NULL AND meeting_link != "" 
         ORDER BY scheduled_at ASC'
    );
    $statement->bind_param('i', $userId);
} else {
    $statement = $conexion->prepare(
        'SELECT id, scheduled_at, meeting_link, status FROM consultations 
         WHERE contact_preference = "video" AND scheduled_at IS NOT NULL AND meeting_link IS NOT NULL AND meeting_link != "" 
         ORDER BY scheduled_at ASC'
    );
}

if (!$statement || !$statement->execute()) {
    respond(['correcto' => false, 'mensaje' => 'No se pudieron obtener las videollamadas.'], 500);
}

$result = $statement->get_result();
$meetings = [];
while ($row = $result->fetch_assoc()) {
    $meetings[] = $row;
}
$statement->close();

respond(['correcto' => true, 'meetings' => $meetings], 200);
