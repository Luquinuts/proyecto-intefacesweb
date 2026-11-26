<?php

function firebase_http_request(string $url, array $body): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    return ['status' => $status, 'body' => $response];
}

function firebase_verify_login(array $input, array $config, ?callable $transport = null): array
{
    try {
        foreach (['token', 'password'] as $field) {
            if (!isset($input[$field]) || !is_string($input[$field]) || $input[$field] === '') {
                throw new RuntimeException();
            }
        }
        if (strlen($input['token']) > 16384 || strlen($input['password']) > 4096 ||
            empty($config['api_key']) || !is_string($config['api_key']) ||
            empty($config['project_id']) || !is_string($config['project_id'])) {
            throw new RuntimeException();
        }
        $transport = $transport ?? 'firebase_http_request';
        $base = 'https://identitytoolkit.googleapis.com/v1/accounts:';
        $key = '?key=' . rawurlencode($config['api_key']);
        $response = $transport($base . 'lookup' . $key, ['idToken' => $input['token']]);
        if (($response['status'] ?? null) !== 200 || !is_string($response['body'] ?? null)) {
            throw new RuntimeException();
        }
        $lookup = json_decode($response['body'], true, 32, JSON_THROW_ON_ERROR);
        if (!isset($lookup['users']) || !is_array($lookup['users']) || array_keys($lookup['users']) !== [0] || !is_array($lookup['users'][0])) {
            throw new RuntimeException();
        }
        $user = $lookup['users'][0];
        if (!isset($user['localId'], $user['email']) || !is_string($user['localId']) || $user['localId'] === '' ||
            strlen($user['localId']) > 128 || !is_string($user['email']) || strlen($user['email']) > 190 ||
            !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/u', $user['email']) ||
            (array_key_exists('disabled', $user) && $user['disabled'] !== false)) {
            throw new RuntimeException();
        }

        $parts = explode('.', $input['token']);
        if (count($parts) !== 3 || !preg_match('/^[A-Za-z0-9_-]+$/', $parts[1])) {
            throw new RuntimeException();
        }
        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);
        $claims = json_decode($payload === false ? '' : $payload, true, 32, JSON_THROW_ON_ERROR);
        $now = time();
        foreach (['exp', 'iat', 'auth_time'] as $field) {
            if (!isset($claims[$field]) || !is_int($claims[$field]) || $claims[$field] <= 0) {
                throw new RuntimeException();
            }
        }
        if (($claims['aud'] ?? null) !== $config['project_id'] ||
            ($claims['iss'] ?? null) !== 'https://securetoken.google.com/' . $config['project_id'] ||
            ($claims['sub'] ?? null) !== $user['localId'] || $claims['exp'] <= $now ||
            $claims['iat'] > $now || $claims['auth_time'] > $now ||
            $claims['auth_time'] > $claims['iat'] || $claims['iat'] >= $claims['exp']) {
            throw new RuntimeException();
        }
        if (array_key_exists('validSince', $user) &&
            ((!is_string($user['validSince']) && !is_int($user['validSince'])) ||
             !preg_match('/^[0-9]{1,12}$/', (string) $user['validSince']) ||
             $claims['auth_time'] < (int) $user['validSince'])) {
            throw new RuntimeException();
        }
        $identity = ['email' => $user['email'], 'uid' => $user['localId']];
        foreach ($identity as $field => $value) {
            if (array_key_exists($field, $input) && $input[$field] !== $value) {
                throw new RuntimeException();
            }
        }
        $response = $transport($base . 'signInWithPassword' . $key, [
            'email' => $identity['email'], 'password' => $input['password'], 'returnSecureToken' => true,
        ]);
        if (($response['status'] ?? null) !== 200 || !is_string($response['body'] ?? null)) {
            throw new RuntimeException();
        }
        $verified = json_decode($response['body'], true, 32, JSON_THROW_ON_ERROR);
        if (($verified['localId'] ?? null) !== $identity['uid'] || ($verified['email'] ?? null) !== $identity['email']) {
            throw new RuntimeException();
        }
        return $identity;
    } catch (Throwable $error) {

        throw new RuntimeException('Could not verify Firebase credentials.');
    }
}

function firebase_password_hash(string $password, ?string $stored): string
{
    $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

    $bcryptInput = base64_encode(hash('sha384', $password, true));
    $material = $algorithm === PASSWORD_BCRYPT ? $bcryptInput : $password;
    $storedMaterial = $stored && password_get_info($stored)['algoName'] === 'bcrypt' ? $bcryptInput : $password;
    if ($stored && password_verify($storedMaterial, $stored) && !password_needs_rehash($stored, $algorithm)) {
        return $stored;
    }
    return password_hash($material, $algorithm);
}
