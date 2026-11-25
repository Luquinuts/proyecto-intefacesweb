<?php

require_once __DIR__ . '/../backend/lib/FirebaseAuth.php';

$config = ['api_key' => 'offline-key', 'project_id' => 'offline-project'];
$now = time();
$claims = ['aud' => $config['project_id'], 'iss' => 'https://securetoken.google.com/offline-project',
    'sub' => 'offline-user', 'exp' => $now + 3600, 'iat' => $now - 10, 'auth_time' => $now - 20];
function token(array $claims): string {
    return 'eyJhbGciOiJSUzI1NiJ9.' . rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=') . '.offline-signature';
}
$input = ['token' => token($claims), 'password' => ' offline password ', 'email' => 'user@example.test', 'uid' => 'offline-user'];
$user = ['localId' => 'offline-user', 'email' => 'user@example.test', 'disabled' => false, 'validSince' => (string) ($now - 30)];
$lookup = ['users' => [$user]];
$passwordResult = ['localId' => $user['localId'], 'email' => $user['email']];
$calls = [];
$transport = function ($url, $body) use (&$calls, &$lookup, &$passwordResult, $input) {
    $calls[] = $url;
    assert(strpos($url, 'https://identitytoolkit.googleapis.com/v1/accounts:') === 0);
    if (strpos($url, ':lookup?') !== false) {
        assert(array_keys($body) === ['idToken']);
        return ['status' => 200, 'body' => json_encode($lookup)];
    }
    assert($body['email'] === 'user@example.test');
    assert($body['password'] === $input['password'], 'Passwords must never be trimmed');
    return ['status' => 200, 'body' => json_encode($passwordResult)];
};
$identity = firebase_verify_login($input, $config, $transport);
assert($identity === ['email' => $user['email'], 'uid' => $user['localId']]);
assert(count($calls) === 2);
assert(firebase_verify_login(['token' => $input['token'], 'password' => $input['password']], $config, $transport) === $identity);
function rejected(array $input, array $config, callable $transport): void {
    try {
        firebase_verify_login($input, $config, $transport);
        assert(false, 'Untrusted login accepted');
    } catch (RuntimeException $error) {
        assert($error->getMessage() === 'Could not verify Firebase credentials.');
    }
}
foreach ([['aud' => 'other-project'], ['iss' => 'https://attacker.test'], ['sub' => 'other-user'],
    ['exp' => $now - 1], ['iat' => $now + 60], ['auth_time' => $now + 60], ['iat' => '123'],
    ['auth_time' => $now - 100], ['sub' => []], ['exp' => null]] as $change) {
    rejected(array_replace($input, ['token' => token(array_replace($claims, $change))]), $config, $transport);
}
foreach (['token' => ['bad'], 'password' => 123, 'email' => 'spoof@example.test', 'uid' => 'spoof'] as $field => $value) {
    rejected(array_replace($input, [$field => $value]), $config, $transport);
}
rejected(array_replace($input, ['token' => 'malformed']), $config, $transport);
rejected(array_replace($input, ['password' => '']), $config, $transport);
rejected(array_replace($input, ['password' => str_repeat('a', 4097)]), $config, $transport);
rejected(array_diff_key($input, ['password' => true]), $config, $transport);
foreach ([[], ['users' => []], ['users' => [$user, $user]], ['users' => [array_replace($user, ['disabled' => true])]],
    ['users' => [array_replace($user, ['disabled' => 'false'])]], ['users' => [array_replace($user, ['email' => []])]],
    ['users' => [array_replace($user, ['email' => 'not an email'])]],
    ['users' => [array_replace($user, ['validSince' => 'invalid'])]], ['users' => [array_replace($user, ['localId' => ''])]]] as $bad) {
    $lookup = $bad;
    rejected($input, $config, $transport);
}
$lookup = ['users' => [$user]];
foreach ([['localId' => 'other-user', 'email' => $user['email']], ['localId' => $user['localId'], 'email' => 'other@example.test'], []] as $bad) {
    $passwordResult = $bad;
    rejected($input, $config, $transport);
}
$passwordResult = ['localId' => $user['localId'], 'email' => $user['email']];
foreach ([['status' => 400, 'body' => '{"error":{"message":"INVALID_PASSWORD"}}'], ['status' => 200, 'body' => 'not-json']] as $bad) {
    rejected($input, $config, fn() => $bad);
}
rejected($input, $config, function () { throw new Exception('Network failure containing sensitive data'); });
rejected($input, $config, function ($url) use ($lookup) {
    return strpos($url, ':lookup?') !== false ? ['status' => 200, 'body' => json_encode($lookup)] : ['status' => 400, 'body' => '{}'];
});

$password = str_repeat('a', 90) . ' final suffix ';
$hash = firebase_password_hash($password, null);
assert($hash !== $password && strlen($hash) <= 255);
assert(firebase_password_hash($password, $hash) === $hash, 'Reuse current verified hash');
assert(firebase_password_hash($password . 'changed', $hash) !== $hash, 'Long suffix must not be truncated');
assert(firebase_password_hash(trim($password), $hash) !== $hash, 'Whitespace remains significant');
$oldHash = password_hash(base64_encode(hash('sha384', $password, true)), PASSWORD_BCRYPT, ['cost' => 4]);
assert(firebase_password_hash($password, $oldHash) !== $oldHash, 'Upgrade obsolete cost');

// Execute the actual endpoint with only I/O boundaries replaced; rejected proof must never reach SQL.
$source = file_get_contents(__DIR__ . '/../backend/crear_sesion.php');
foreach (['token', 'password', 'spoof', 'types'] as $failure) {
    $badInput = $input;
    if ($failure === 'spoof') $badInput['uid'] = 'spoof';
    if ($failure === 'types') $badInput['password'] = [];
    $script = str_replace("file_get_contents('php://input')", var_export(json_encode($badInput), true), $source);
    $script = str_replace("__DIR__ . '/lib/FirebaseAuth.php'", var_export(realpath(__DIR__ . '/../backend/lib/FirebaseAuth.php'), true), $script);
    $script = str_replace("require_once __DIR__ . '/config/firebase.php'", '$firebaseConfig = ' . var_export($config, true), $script);
    $script = str_replace("require_once __DIR__ . '/config/conexion.php';", 'throw new Exception("SQL reached on rejected login");', $script);
    $response = $failure === 'token' ? ['status' => 400, 'body' => '{}'] : ['status' => 200, 'body' => json_encode($lookup)];
    $mock = 'function ($url, $body) { return strpos($url, ":lookup?") !== false ? ' . var_export($response, true) . ' : ["status" => 400, "body" => "{}"]; }';
    $script = str_replace('firebase_verify_login($datos, $firebaseConfig)', 'firebase_verify_login($datos, $firebaseConfig, ' . $mock . ')', $script);
    $script = str_replace('session_start();', 'session_start(); $_SESSION = ["captcha_validado" => true, "sentinel" => 1]; $_SERVER["REQUEST_METHOD"] = "POST"; register_shutdown_function(function () { assert($_SESSION === ["captcha_validado" => true, "sentinel" => 1]); });', $script);
    $process = proc_open([PHP_BINARY, '-d', 'zend.assertions=1', '-d', 'assert.exception=1'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fwrite($pipes[0], $script); fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    assert(proc_close($process) === 0 && $errors === '', 'Endpoint rejection must not reach SQL or mutate session');
    assert(json_decode($output, true)['correcto'] === false);
    assert(strpos($output, $input['token']) === false && strpos($output, $input['password']) === false);
}

$captchaSource = file_get_contents(__DIR__ . '/../backend/validar_captcha.php');
foreach ([['POST', ['captcha' => '4'], true], ['POST', ['captcha' => ['4']], false],
    ['POST', ['captcha' => '4', 'extra' => 'ignored'], false], ['POST', ['captcha' => '4x'], false],
    ['POST', [], false], ['GET', ['captcha' => '4'], false]] as [$method, $post, $expected]) {
    $setup = 'session_start(); $_SESSION = ["captcha" => 4]; $_SERVER["REQUEST_METHOD"] = ' . var_export($method, true) . '; $_POST = ' . var_export($post, true) . ';';
    $script = str_replace('session_start();', $setup, $captchaSource);
    $process = proc_open([PHP_BINARY, '-d', 'zend.assertions=1', '-d', 'assert.exception=1'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
    fwrite($pipes[0], $script); fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    assert(proc_close($process) === 0 && $errors === '');
    assert(json_decode($output, true)['correcto'] === $expected, 'CAPTCHA accepts only a POST scalar answer');
}
echo "PASS: Firebase identity/password proof, rejection before SQL/session changes, safe hash lifecycle (offline)\n";
