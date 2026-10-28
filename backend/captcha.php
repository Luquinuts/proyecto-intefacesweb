<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

$numberOne = random_int(1, 10);
$numberTwo = random_int(1, 10);
$_SESSION['captcha'] = $numberOne + $numberTwo;

echo json_encode([
    'pregunta' => $numberOne . ' + ' . $numberTwo,
]);
