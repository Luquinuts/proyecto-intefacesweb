<?php

mysqli_report(MYSQLI_REPORT_OFF);

$host = getenv('PASOS_DB_HOST') ?: '127.0.0.1';
$user = getenv('PASOS_DB_USER') ?: 'root';
$password = getenv('PASOS_DB_PASSWORD') ?: '';
$database = getenv('PASOS_DB_NAME') ?: 'pasos';
$port = (int) (getenv('PASOS_DB_PORT') ?: 3306);

$conexion = new mysqli($host, $user, $password, $database, $port);

if ($conexion->connect_errno) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['correcto' => false, 'mensaje' => 'Database connection failed.']);
    exit;
}

$conexion->set_charset('utf8mb4');

// The course still requires a password_hash() decision. Firebase remains the
// only credential store in this MVP; no duplicate local password is added.
