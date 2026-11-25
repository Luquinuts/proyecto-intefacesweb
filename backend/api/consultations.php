<?php

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

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
$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '', '/');
$matches = [];
$isCollection = preg_match('#/consultations(?:\.php)?$#', $path) === 1;
$isItem = preg_match('#/consultations(?:\.php)?/([0-9]+)$#', $path, $matches) === 1;
$id = $isItem ? (int) $matches[1] : 0;
if (!$isCollection && !$isItem) respond(['correcto' => false, 'mensaje' => 'Resource not found.'], 404);
$allowed = $isCollection ? ['GET', 'POST'] : ['GET', 'PUT'];
if (!in_array($method, $allowed, true)) {
    header('Allow: ' . implode(', ', $allowed));
    respond(['correcto' => false, 'mensaje' => 'Method not allowed.'], 405);
}
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../lib/GoogleCalendarMeet.php';
require_once __DIR__ . '/../lib/ConsultationRules.php';
$userId = (int) $_SESSION['usuario_id'];
$role = $_SESSION['rol'] ?? 'user';

try {
    if ($method === 'GET') {
        $sql = 'SELECT c.*, u.email AS user_email, u.phone FROM consultations c INNER JOIN users u ON u.id = c.user_id';
        $types = ''; $values = [];
        if ($isItem) {
            $sql .= ' WHERE c.id = ?';
            $types = 'i'; $values = [$id];
        } elseif ($role !== 'administrator') {
            $sql .= $role === 'professional' ? ' WHERE c.professional_id = ?' : ' WHERE c.user_id = ?';
            $types = 'i'; $values = [$userId];
        }
        $stmt = consultation_query($conexion, $sql . ' ORDER BY c.requested_at DESC', $types, $values);
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            if (!consultation_can_read($row, $userId, $role)) throw new DomainException('Not authorized.');
            $rows[] = array_merge([
                'id' => (int) $row['id'], 'description' => $row['description'],
                'contact_preference' => $row['contact_preference'], 'status' => $row['status'],
                'professional_note' => $row['professional_note'], 'requested_at' => $row['requested_at'],
                'user_email' => $row['user_email'], 'phone' => $row['phone'], 'scheduled_at' => $row['scheduled_at'],
            ], consultation_meeting($row));
        }
        $stmt->close();
        if ($isItem && !$rows) throw new OutOfBoundsException('Consultation not found.');
        respond(['correcto' => true, 'consultations' => $isItem ? $rows[0] : $rows], 200);
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) throw new InvalidArgumentException('JSON body required.');
    if ($method === 'POST') {
        $newId = consultation_create($conexion, $userId, $role, $data);
        respond(['correcto' => true, 'id' => $newId], 201);
    }
    respond(consultation_update($conexion, $id, $userId, $role, $data), 200);
} catch (InvalidArgumentException $e) {
    respond(['correcto' => false, 'mensaje' => $e->getMessage()], 400);
} catch (DomainException $e) {
    respond(['correcto' => false, 'mensaje' => 'Not authorized.'], 403);
} catch (OutOfBoundsException $e) {
    respond(['correcto' => false, 'mensaje' => 'Consultation not found.'], 404);
} catch (Throwable $e) {
    respond(['correcto' => false, 'mensaje' => 'No se pudo guardar/leer la consulta. Si estabas agendando, reintentá con la misma fecha para comprobar el evento.'], 500);
}
