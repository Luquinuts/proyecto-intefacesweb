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

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '';
$path = rtrim($path, '/');
$matches = [];
$isCollection = preg_match('#/consultations(?:\.php)?$#', $path) === 1;
$isItem = preg_match('#/consultations(?:\.php)?/([0-9]+)$#', $path, $matches) === 1;
$id = $isItem ? (int) $matches[1] : 0;

if (!$isCollection && !$isItem) {
    respond(['correcto' => false, 'mensaje' => 'Resource not found.'], 404);
}

if ($isCollection && !in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    respond(['correcto' => false, 'mensaje' => 'Method not allowed.'], 405);
}

if ($isItem && !in_array($method, ['GET', 'PUT'], true)) {
    header('Allow: GET, PUT');
    respond(['correcto' => false, 'mensaje' => 'Method not allowed.'], 405);
}

require_once __DIR__ . '/../config/conexion.php';
$userId = (int) $_SESSION['usuario_id'];
$role = $_SESSION['rol'] ?? 'user';
$canReview = in_array($role, ['administrator', 'professional'], true);

function consultationFromRow(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'description' => $row['description'],
        'contact_preference' => $row['contact_preference'],
        'status' => $row['status'],
        'professional_note' => $row['professional_note'],
        'requested_at' => $row['requested_at'],
        'user_email' => $row['user_email'] ?? null,
    ];
}

if ($method === 'GET') {
    if ($isCollection) {
        if ($canReview && $role === 'administrator') {
            $statement = $conexion->prepare(
                'SELECT c.id, c.description, c.contact_preference, c.status,
                        c.professional_note, c.requested_at, u.email AS user_email
                 FROM consultations c INNER JOIN users u ON u.id = c.user_id
                 ORDER BY c.requested_at DESC'
            );
        } elseif ($role === 'professional') {
            $statement = $conexion->prepare(
                'SELECT c.id, c.description, c.contact_preference, c.status,
                        c.professional_note, c.requested_at, u.email AS user_email
                 FROM consultations c INNER JOIN users u ON u.id = c.user_id
                 WHERE c.professional_id = ? ORDER BY c.requested_at DESC'
            );
            $statement->bind_param('i', $userId);
        } else {
            $statement = $conexion->prepare(
                'SELECT c.id, c.description, c.contact_preference, c.status,
                        c.professional_note, c.requested_at, u.email AS user_email
                 FROM consultations c INNER JOIN users u ON u.id = c.user_id
                 WHERE c.user_id = ? ORDER BY c.requested_at DESC'
            );
            $statement->bind_param('i', $userId);
        }
    } else {
        $statement = $conexion->prepare(
            'SELECT c.id, c.description, c.contact_preference, c.status,
                    c.professional_note, c.requested_at, u.email AS user_email
             FROM consultations c INNER JOIN users u ON u.id = c.user_id
             WHERE c.id = ? AND (? = 1 OR c.user_id = ?) LIMIT 1'
        );
        $reviewFlag = $canReview ? 1 : 0;
        $statement->bind_param('iii', $id, $reviewFlag, $userId);
    }

    if (!$statement || !$statement->execute()) {
        respond(['correcto' => false, 'mensaje' => 'Could not read consultations.'], 500);
    }
    $result = $statement->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = consultationFromRow($row);
    }
    $statement->close();
    if ($isItem && count($rows) === 0) {
        respond(['correcto' => false, 'mensaje' => 'Consultation not found.'], 404);
    }
    respond(['correcto' => true, 'consultations' => $isItem ? $rows[0] : $rows], 200);
}

$datos = json_decode(file_get_contents('php://input'), true);
if ($method === 'POST') {
    if (!is_array($datos)) {
        respond(['correcto' => false, 'mensaje' => 'JSON body required.'], 400);
    }
    $description = isset($datos['description']) ? trim((string) $datos['description']) : '';
    $contactPreference = isset($datos['contact_preference']) ? trim((string) $datos['contact_preference']) : '';
    if ($description === '' || strlen($description) > 3000 || !in_array($contactPreference, ['email', 'phone', 'video'], true)) {
        respond(['correcto' => false, 'mensaje' => 'Invalid consultation data.'], 400);
    }
    $profId = null;
    if ($role === 'user') {
        $profStmt = $conexion->prepare("SELECT id FROM users WHERE role='professional' ORDER BY id ASC LIMIT 1");
        if ($profStmt && $profStmt->execute()) {
            $profRes = $profStmt->get_result();
            if ($profRow = $profRes->fetch_assoc()) {
                $profId = (int) $profRow['id'];
            }
            $profStmt->close();
        }
    }
    $statement = $conexion->prepare(
        'INSERT INTO consultations (user_id, professional_id, description, contact_preference) VALUES (?, ?, ?, ?)'
    );
    $statement->bind_param('iiss', $userId, $profId, $description, $contactPreference);
    if (!$statement->execute()) {
        respond(['correcto' => false, 'mensaje' => 'Could not create consultation.'], 500);
    }
    $newId = $statement->insert_id;
    $statement->close();
    respond(['correcto' => true, 'id' => $newId], 201);
}

if (!$canReview) {
    respond(['correcto' => false, 'mensaje' => 'Only administrators or professionals can update status.'], 401);
}

if (!is_array($datos) || !isset($datos['status']) || !in_array($datos['status'], ['received', 'under_review', 'scheduled', 'closed'], true)) {
    respond(['correcto' => false, 'mensaje' => 'Invalid status.'], 400);
}

$status = $datos['status'];
$note = isset($datos['professional_note']) ? trim((string) $datos['professional_note']) : null;
$statement = $conexion->prepare(
    'UPDATE consultations SET status = ?, professional_note = ? WHERE id = ?'
);
$statement->bind_param('ssi', $status, $note, $id);
if (!$statement->execute()) {
    respond(['correcto' => false, 'mensaje' => 'Could not update consultation.'], 500);
}
if ($statement->affected_rows === 0) {
    $statement->close();
    respond(['correcto' => false, 'mensaje' => 'Consultation not found or unchanged.'], 404);
}
$statement->close();
respond(['correcto' => true, 'id' => $id, 'status' => $status], 200);
