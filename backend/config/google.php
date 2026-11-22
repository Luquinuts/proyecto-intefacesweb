<?php

function pasos_google_config(): array
{
    $localConfig = __DIR__ . '/google.local.php';
    $local = [];
    if (file_exists($localConfig)) {
        $loaded = require $localConfig;
        if (is_array($loaded)) {
            $local = $loaded;
        }
    }

    $clientId = getenv('GOOGLE_CLIENT_ID') ?: ($local['client_id'] ?? '');
    $clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: ($local['client_secret'] ?? '');
    $redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: ($local['redirect_uri'] ?? 'http://localhost/proyecto-intefacesweb-develop/backend/api/google_callback.php');

    return [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'frontend_url' => getenv('PASOS_FRONTEND_URL') ?: ($local['frontend_url'] ?? 'http://localhost:5174/'),
        'auth_uri' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'calendar_api_base' => 'https://www.googleapis.com/calendar/v3',
        'meet_api_base' => 'https://meet.googleapis.com/v2',
        'scopes' => [
            'openid',
            'email',
            'https://www.googleapis.com/auth/calendar.events',
            'https://www.googleapis.com/auth/meetings.space.created',
        ],
    ];
}
