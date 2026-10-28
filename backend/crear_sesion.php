<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['correcto' => false, 'mensaje' => 'Method not allowed.']);
    exit;
}

if (empty($_SESSION['captcha_validado'])) {
    http_response_code(401);
    echo json_encode(['correcto' => false, 'mensaje' => 'Captcha must be validated first.']);
    exit;
}

$datos = json_decode(file_get_contents('php://input'), true);
$email = is_array($datos) && isset($datos['email']) ? filter_var($datos['email'], FILTER_VALIDATE_EMAIL) : false;
$uid = is_array($datos) && isset($datos['uid']) ? trim((string) $datos['uid']) : '';
$token = is_array($datos) && isset($datos['token']) ? trim((string) $datos['token']) : '';

if (!$email || $uid === '' || $token === '' || strlen($uid) > 128) {
    http_response_code(400);
    echo json_encode(['correcto' => false, 'mensaje' => 'Invalid session data.']);
    exit;
}

// Academic limitation preserved from the first partial: token verification
// with Firebase Admin SDK remains a pending decision and is not added here.
require_once __DIR__ . '/config/conexion.php';

$name = strstr($email, '@', true) ?: 'PASOS user';

$statement = $conexion->prepare('SELECT firebase_uid, role FROM users WHERE email = ? LIMIT 1');
if (!$statement) {
    http_response_code(500);
    echo json_encode(['correcto' => false, 'mensaje' => 'Could not prepare user lookup.']);
    exit;
}
$statement->bind_param('s', $email);
$statement->execute();
$statement->bind_result($storedUid, $storedRole);
$emailFound = $statement->fetch();
$statement->close();

if ($emailFound && $storedUid !== $uid) {
    if ($storedUid === 'replace-with-firebase-uid' && $storedRole === 'administrator') {
        $statement = $conexion->prepare('UPDATE users SET firebase_uid = ?, name = ? WHERE email = ?');
        if (!$statement) {
            http_response_code(500);
            echo json_encode(['correcto' => false, 'mensaje' => 'Could not prepare administrator update.']);
            exit;
        }
        $statement->bind_param('sss', $uid, $name, $email);
    } else {
        http_response_code(409);
        echo json_encode(['correcto' => false, 'mensaje' => 'Email is already linked to another Firebase user.']);
        exit;
    }
} else {
    $statement = $conexion->prepare(
        'INSERT INTO users (firebase_uid, name, email) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE email = VALUES(email), name = VALUES(name)'
    );
    if (!$statement) {
        http_response_code(500);
        echo json_encode(['correcto' => false, 'mensaje' => 'Could not prepare user statement.']);
        exit;
    }
    $statement->bind_param('sss', $uid, $name, $email);
}

if (!$statement->execute()) {
    $statement->close();
    http_response_code(500);
    echo json_encode(['correcto' => false, 'mensaje' => 'Could not save user.']);
    exit;
}
$statement->close();

$statement = $conexion->prepare('SELECT id, role, name, email FROM users WHERE firebase_uid = ? LIMIT 1');
$statement->bind_param('s', $uid);
$statement->execute();
$statement->bind_result($userId, $role, $storedName, $storedEmail);
$found = $statement->fetch();
$statement->close();

if (!$found) {
    http_response_code(500);
    echo json_encode(['correcto' => false, 'mensaje' => 'Could not load user.']);
    exit;
}

$_SESSION['autenticado'] = true;
$_SESSION['usuario_id'] = $userId;
$_SESSION['usuario'] = htmlspecialchars($storedEmail, ENT_QUOTES, 'UTF-8');
$_SESSION['nombre'] = htmlspecialchars($storedName, ENT_QUOTES, 'UTF-8');
$_SESSION['rol'] = $role;
unset($_SESSION['captcha_validado']);

echo json_encode(['correcto' => true, 'rol' => $role]);
