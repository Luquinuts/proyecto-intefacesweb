<?php

session_start();
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

function denyMeeting(int $status): void
{
    http_response_code($status);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'La videollamada no está disponible.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    denyMeeting(405);
}
if (empty($_SESSION['autenticado']) || empty($_SESSION['usuario_id'])) denyMeeting(401);
if (!isset($_GET['id']) || !is_string($_GET['id']) || !ctype_digit($_GET['id'])) denyMeeting(400);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../lib/ConsultationRules.php';
try {
    $stmt = consultation_query($conexion, 'SELECT id, user_id, professional_id, status, contact_preference, scheduled_at, meeting_link FROM consultations WHERE id = ?', 'i', [(int) $_GET['id']]);
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || !consultation_can_read($row, (int) $_SESSION['usuario_id'], $_SESSION['rol'] ?? 'user')) denyMeeting(403);
    $meeting = consultation_meeting($row);
    if (!$meeting['can_join']) denyMeeting(403);
    $link = $meeting['meeting_link'];
    $email = isset($_SESSION['usuario']) ? html_entity_decode((string) $_SESSION['usuario'], ENT_QUOTES, 'UTF-8') : '';
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $separator = str_contains($link, '?') ? '&' : '?';
        $link .= $separator . 'authuser=' . rawurlencode($email);
    }
    header('Location: ' . $link, true, 302);
    exit;
} catch (Throwable $e) {
    denyMeeting(500);
}
