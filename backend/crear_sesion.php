<?php

require_once __DIR__ . '/lib/RequestGuard.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') pasos_require_write(true);
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
unset($_SESSION['captcha_validado']);

$datos = json_decode(file_get_contents('php://input'), true);
if (!is_array($datos)) {
    http_response_code(400);
    echo json_encode(['correcto' => false, 'mensaje' => 'Invalid session data.']);
    exit;
}

require_once __DIR__ . '/lib/FirebaseAuth.php';
require_once __DIR__ . '/config/firebase.php';
try {
    $identity = firebase_verify_login($datos, $firebaseConfig);
} catch (RuntimeException $error) {
    http_response_code(401);
    echo json_encode(['correcto' => false, 'mensaje' => 'Could not verify Firebase credentials.']);
    exit;
}
$email = $identity['email'];
$uid = $identity['uid'];
require_once __DIR__ . '/config/conexion.php';

$name = strstr($email, '@', true) ?: 'PASOS user';

$statement = $conexion->prepare('SELECT firebase_uid, role, password_hash FROM users WHERE email = ? LIMIT 1');
if (!$statement) {
    http_response_code(500);
    echo json_encode(['correcto' => false, 'mensaje' => 'Could not prepare user lookup.']);
    exit;
}
$statement->bind_param('s', $email);
$statement->execute();
$statement->bind_result($storedUid, $storedRole, $storedHash);
$emailFound = $statement->fetch();
$statement->close();
$pendingUid = $emailFound && ($storedUid === 'replace-with-firebase-uid' || strpos($storedUid, 'pending-') === 0);
if ($pendingUid && $storedRole !== 'user') {
    http_response_code(403);
    echo json_encode(['correcto' => false, 'mensaje' => 'Account setup required. Contact the administrator.']);
    exit;
}
if ($emailFound && $storedUid !== $uid && !$pendingUid) {
    http_response_code(409);
    echo json_encode(['correcto' => false, 'mensaje' => 'Email is already linked to another Firebase user.']);
    exit;
}
$passwordHash = firebase_password_hash($datos['password'], $emailFound ? $storedHash : null);
unset($datos['password']);

if ($emailFound && $storedUid !== $uid) {
    $statement = $conexion->prepare('UPDATE users SET firebase_uid = ?, name = ?, password_hash = ? WHERE email = ?');
    if (!$statement) {
        http_response_code(500);
        echo json_encode(['correcto' => false, 'mensaje' => 'Could not prepare user update.']);
        exit;
    }
    $statement->bind_param('ssss', $uid, $name, $passwordHash, $email);
} else {
    $statement = $conexion->prepare(
        'INSERT INTO users (firebase_uid, name, email, password_hash) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE email = VALUES(email), name = VALUES(name), password_hash = VALUES(password_hash)'
    );
    if (!$statement) {
        http_response_code(500);
        echo json_encode(['correcto' => false, 'mensaje' => 'Could not prepare user statement.']);
        exit;
    }
    $statement->bind_param('ssss', $uid, $name, $email, $passwordHash);
}

if (!$statement->execute()) {
    $statement->close();
    http_response_code(500);
    echo json_encode(['correcto' => false, 'mensaje' => 'Could not save user.']);
    exit;
}
$statement->close();

$statement = $conexion->prepare('SELECT id, role, name, email FROM users WHERE firebase_uid = ? LIMIT 1');
if (!$statement) {
    http_response_code(500);
    echo json_encode(['correcto' => false, 'mensaje' => 'Could not prepare user lookup.']);
    exit;
}
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

if (!session_regenerate_id(true)) {
    http_response_code(500);
    echo json_encode(['correcto' => false, 'mensaje' => 'Could not initialize session.']);
    exit;
}
$_SESSION['autenticado'] = true;
$_SESSION['usuario_id'] = $userId;
$_SESSION['usuario'] = htmlspecialchars($storedEmail, ENT_QUOTES, 'UTF-8');
$_SESSION['nombre'] = htmlspecialchars($storedName, ENT_QUOTES, 'UTF-8');
$_SESSION['rol'] = $role;
echo json_encode(['correcto' => true, 'rol' => $role]);
