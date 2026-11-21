<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/google.php';

function google_http_json(string $method, string $url, ?array $body = null, array $headers = [], array $query = []): array
{
    if (!empty($query)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($query);
    }

    $ch = curl_init($url);
    $requestHeaders = array_merge(['Accept: application/json'], $headers);
    $payload = null;
    if ($body !== null) {
        $payload = json_encode($body);
        $requestHeaders[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['ok' => false, 'status' => 0, 'error' => $error ?: 'Error de conexión con Google.', 'data' => null];
    }

    $data = json_decode($response, true);
    if ($data === null && trim($response) !== '') {
        $data = ['raw' => $response];
    }

    return ['ok' => $status >= 200 && $status < 300, 'status' => $status, 'error' => null, 'data' => $data];
}

function google_get_token_row(mysqli $conexion, int $userId): ?array
{
    $stmt = $conexion->prepare('SELECT user_id, google_email, access_token, refresh_token, token_type, expires_at, scope, calendar_id FROM google_oauth_tokens WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function google_save_tokens(mysqli $conexion, int $userId, array $tokenData, ?string $googleEmail = null): bool
{
    $accessToken = $tokenData['access_token'] ?? null;
    $refreshToken = $tokenData['refresh_token'] ?? null;
    $tokenType = $tokenData['token_type'] ?? null;
    $scope = $tokenData['scope'] ?? null;
    $expiresIn = isset($tokenData['expires_in']) ? (int) $tokenData['expires_in'] : null;
    $expiresAt = $expiresIn ? date('Y-m-d H:i:s', time() + $expiresIn - 60) : null;

    $existing = google_get_token_row($conexion, $userId);
    if ($existing) {
        if ($refreshToken === null || $refreshToken === '') {
            $refreshToken = $existing['refresh_token'];
        }
        if ($googleEmail === null || $googleEmail === '') {
            $googleEmail = $existing['google_email'];
        }
        $stmt = $conexion->prepare('UPDATE google_oauth_tokens SET google_email = ?, access_token = ?, refresh_token = ?, token_type = ?, expires_at = ?, scope = ? WHERE user_id = ?');
        $stmt->bind_param('ssssssi', $googleEmail, $accessToken, $refreshToken, $tokenType, $expiresAt, $scope, $userId);
    } else {
        $calendarId = 'primary';
        $stmt = $conexion->prepare('INSERT INTO google_oauth_tokens (user_id, google_email, access_token, refresh_token, token_type, expires_at, scope, calendar_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssssss', $userId, $googleEmail, $accessToken, $refreshToken, $tokenType, $expiresAt, $scope, $calendarId);
    }

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function google_refresh_access_token(mysqli $conexion, int $userId): array
{
    $config = pasos_google_config();
    $row = google_get_token_row($conexion, $userId);
    if (!$row || empty($row['refresh_token'])) {
        return ['ok' => false, 'error' => 'No hay refresh token de Google. El abogado debe volver a conectar su cuenta.'];
    }

    $response = google_http_json('POST', $config['token_uri'], null, [], []);
    // Token endpoint expects form-encoded; handle separately below.
    $ch = curl_init($config['token_uri']);
    $payload = http_build_query([
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'refresh_token' => $row['refresh_token'],
        'grant_type' => 'refresh_token',
    ]);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => $error ?: 'No se pudo refrescar el token de Google.'];
    }
    $data = json_decode($raw, true);
    if ($status < 200 || $status >= 300 || empty($data['access_token'])) {
        $msg = $data['error_description'] ?? $data['error'] ?? 'No se pudo refrescar el token de Google.';
        return ['ok' => false, 'error' => $msg, 'data' => $data];
    }

    google_save_tokens($conexion, $userId, $data, $row['google_email']);
    return ['ok' => true, 'access_token' => $data['access_token']];
}

function google_get_valid_access_token(mysqli $conexion, int $userId): array
{
    $row = google_get_token_row($conexion, $userId);
    if (!$row) {
        return ['ok' => false, 'error' => 'La cuenta de Google no está conectada.'];
    }

    $expiresAt = $row['expires_at'] ? strtotime($row['expires_at']) : 0;
    if (!empty($row['access_token']) && $expiresAt > time() + 30) {
        return ['ok' => true, 'access_token' => $row['access_token'], 'row' => $row];
    }

    $refresh = google_refresh_access_token($conexion, $userId);
    if (!$refresh['ok']) {
        return $refresh;
    }
    $row = google_get_token_row($conexion, $userId);
    return ['ok' => true, 'access_token' => $refresh['access_token'], 'row' => $row];
}

function google_build_auth_url(string $state): string
{
    $config = pasos_google_config();
    $params = [
        'client_id' => $config['client_id'],
        'redirect_uri' => $config['redirect_uri'],
        'response_type' => 'code',
        'scope' => implode(' ', $config['scopes']),
        'access_type' => 'offline',
        'prompt' => 'consent',
        'include_granted_scopes' => 'true',
        'state' => $state,
    ];
    return $config['auth_uri'] . '?' . http_build_query($params);
}

function google_exchange_code(string $code): array
{
    $config = pasos_google_config();
    $ch = curl_init($config['token_uri']);
    $payload = http_build_query([
        'code' => $code,
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri' => $config['redirect_uri'],
        'grant_type' => 'authorization_code',
    ]);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return ['ok' => false, 'error' => $error ?: 'No se pudo conectar con Google.'];
    }
    $data = json_decode($raw, true);
    if ($status < 200 || $status >= 300) {
        return ['ok' => false, 'error' => $data['error_description'] ?? $data['error'] ?? 'Error al intercambiar código de Google.', 'data' => $data];
    }
    return ['ok' => true, 'data' => $data];
}

function google_fetch_user_email(string $accessToken): ?string
{
    $response = google_http_json('GET', 'https://openidconnect.googleapis.com/v1/userinfo', null, [
        'Authorization: Bearer ' . $accessToken,
    ]);
    if ($response['ok'] && !empty($response['data']['email'])) {
        return $response['data']['email'];
    }
    return null;
}

function google_create_calendar_event(mysqli $conexion, int $userId, array $eventData, bool $withMeet): array
{
    $token = google_get_valid_access_token($conexion, $userId);
    if (!$token['ok']) {
        return $token;
    }

    $config = pasos_google_config();
    $row = $token['row'] ?? google_get_token_row($conexion, $userId);
    $calendarId = $row['calendar_id'] ?? 'primary';

    $body = [
        'summary' => $eventData['summary'] ?? 'Consulta PASOS',
        'description' => $eventData['description'] ?? '',
        'start' => [
            'dateTime' => $eventData['start'],
            'timeZone' => $eventData['timeZone'] ?? 'America/Argentina/Buenos_Aires',
        ],
        'end' => [
            'dateTime' => $eventData['end'],
            'timeZone' => $eventData['timeZone'] ?? 'America/Argentina/Buenos_Aires',
        ],
    ];

    $query = [];
    if ($withMeet) {
        $body['conferenceData'] = [
            'createRequest' => [
                'requestId' => 'pasos-' . uniqid('', true),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
            ],
        ];
        $query['conferenceDataVersion'] = 1;
    }

    $url = $config['calendar_api_base'] . '/calendars/' . rawurlencode($calendarId) . '/events';
    $response = google_http_json('POST', $url, $body, [
        'Authorization: Bearer ' . $token['access_token'],
    ], $query);

    if (!$response['ok']) {
        $msg = $response['data']['error']['message'] ?? $response['error'] ?? 'No se pudo crear el evento en Google Calendar.';
        return ['ok' => false, 'error' => $msg, 'data' => $response['data']];
    }

    $meetLink = null;
    $data = $response['data'];
    if (!empty($data['conferenceData']['entryPoints'])) {
        foreach ($data['conferenceData']['entryPoints'] as $ep) {
            if (($ep['entryPointType'] ?? '') === 'video' && !empty($ep['uri'])) {
                $meetLink = $ep['uri'];
                break;
            }
        }
    }
    if ($meetLink === null && !empty($data['hangoutLink'])) {
        $meetLink = $data['hangoutLink'];
    }

    return [
        'ok' => true,
        'event_id' => $data['id'] ?? null,
        'html_link' => $data['htmlLink'] ?? null,
        'meet_link' => $meetLink,
        'data' => $data,
    ];
}

function google_create_meet_space(mysqli $conexion, int $userId): array
{
    $token = google_get_valid_access_token($conexion, $userId);
    if (!$token['ok']) {
        return $token;
    }
    $config = pasos_google_config();
    $response = google_http_json('POST', $config['meet_api_base'] . '/spaces', [
        'config' => ['accessType' => 'OPEN', 'entryPointAccess' => 'ALL'],
    ], [
        'Authorization: Bearer ' . $token['access_token'],
    ]);

    if (!$response['ok']) {
        $msg = $response['data']['error']['message'] ?? $response['error'] ?? 'No se pudo crear el espacio de Google Meet.';
        return ['ok' => false, 'error' => $msg, 'data' => $response['data']];
    }

    return ['ok' => true, 'meet_link' => $response['data']['meetingUri'] ?? null, 'space' => $response['data']];
}
