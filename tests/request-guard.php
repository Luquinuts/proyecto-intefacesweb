<?php


$endpoints = ['crear_sesion.php', 'api/consultations.php', 'validar_captcha.php', 'cerrar_sesion.php'];
$cases = [
    ['https://evil.test', 'application/json', 403],
    ['null', 'application/json', 403],
    ['http://localhost.evil.test', 'application/json', 403],
    ['http://localhost:5175', 'application/json', 403],
    ['http://127.0.0.1:8080', 'application/json', 403],
    ['http://localhost:5174@evil.test', 'application/json', 403],
    ['http://localhost/path', 'application/json', 403],
    ['https://class.example.test:444', 'application/json', 403],
    ['http://localhost', 'text/plain', 400],
    ['http://localhost:5174', 'application/x-www-form-urlencoded', 400],
    [null, '', 400],
    ['http://localhost', 'application/json', 0],
    ['http://127.0.0.1', 'application/json; charset=utf-8', 0],
    ['http://localhost:5174', 'application/json', 0],
    ['http://127.0.0.1:5174', 'application/json', 0],
    ['https://class.example.test', 'application/json', 0],
    [null, 'application/json', 0],
];
foreach ($endpoints as $endpoint) {
    foreach ($cases as [$origin, $type, $expected]) {
        $json = in_array($endpoint, ['crear_sesion.php', 'api/consultations.php'], true);
        if (!$json && $expected === 400) $expected = 0;
        foreach ($endpoint === 'api/consultations.php' ? ['POST', 'PUT'] : ['POST'] as $method) {
            $path = __DIR__ . '/../backend/' . $endpoint;
            $source = file_get_contents($path);
            $source = str_replace('__DIR__', var_export(dirname(realpath($path)), true), $source);
            $source = str_replace('session_start();', '', $source);
            $source = preg_replace('/require_once .*config\/conexion.php.*;/', 'throw new Exception("DB reached");', $source);
            $setup = 'putenv("PASOS_FRONTEND_URL=https://CLASS.example.test:443/app/#consultations");'
                . '$_SERVER = ' . var_export(['REQUEST_METHOD' => $method, 'REQUEST_URI' => '/api/consultations.php' . ($method === 'PUT' ? '/7' : ''), 'CONTENT_TYPE' => $type], true) . ';'
                . ($origin === null ? '' : '$_SERVER["HTTP_ORIGIN"] = ' . var_export($origin, true) . ';')
                . '$_SESSION = ["sentinel" => 1];'
                . 'register_shutdown_function(function () { assert($_SESSION === ["sentinel" => 1]); echo "\nSTATUS:" . http_response_code(); });';

            if ($expected === 0) {
                $source = preg_replace('/(pasos_require_write\([^;]*;)/', '$1 echo "ACCEPTED"; exit;', $source);
            }
            $script = preg_replace('/^<\?php/', '<?php ' . $setup, $source, 1);
            $process = proc_open([PHP_BINARY, '-d', 'zend.assertions=1', '-d', 'assert.exception=1'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes);
            fwrite($pipes[0], $script); fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
            $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
            assert(proc_close($process) === 0 && $errors === '', "$endpoint $method: $errors");
            if ($expected) {
                assert(str_ends_with($output, 'STATUS:' . $expected), "$endpoint $method expected $expected: $output");
                assert(json_decode(explode("\nSTATUS:", $output)[0], true)['correcto'] === false);
            } else assert(str_starts_with($output, 'ACCEPTED'), "$endpoint $method did not pass the guard: $output");
        }
    }
}
echo "PASS: exact local/configured origins, JSON content types, endpoint guards before side effects\n";
