<?php

function pasos_write_origin(string $url, bool $configured = false): ?string
{
    if ($url === '' || preg_match('/[\s\x00-\x1f\x7f]/', $url)) return null;
    $parts = parse_url($url);
    if (!$parts || !isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) return null;
    if (!$configured && (isset($parts['path']) || isset($parts['query']) || isset($parts['fragment']))) return null;
    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) return null;
    $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
    return $scheme . '://' . strtolower($parts['host']) . (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80) ? '' : ':' . $port);
}

// One procedural boundary for session and consultation writes; no private OAuth config is loaded.
function pasos_require_write(bool $json = false): void
{
    $allowed = ['http://localhost', 'http://127.0.0.1', 'http://localhost:5174', 'http://127.0.0.1:5174'];
    $configured = pasos_write_origin(getenv('PASOS_FRONTEND_URL') ?: '', true);
    if ($configured !== null) $allowed[] = $configured;
    if (isset($_SERVER['HTTP_ORIGIN']) && !in_array(pasos_write_origin($_SERVER['HTTP_ORIGIN']), $allowed, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['correcto' => false, 'mensaje' => 'Request not allowed.']);
        exit;
    }
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($json && $type !== 'application/json') {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['correcto' => false, 'mensaje' => 'JSON body required.']);
        exit;
    }
}
