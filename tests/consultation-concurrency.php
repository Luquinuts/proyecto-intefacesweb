<?php


$database = getenv('PASOS_CONCURRENCY_DB') ?: '';
if (getenv('PASOS_CONCURRENCY_ALLOW') !== '1' || !preg_match('/^pasos_test_[a-zA-Z0-9_]+$/D', $database)) {
    fwrite(STDERR, "UNAVAILABLE: set PASOS_CONCURRENCY_ALLOW=1 and PASOS_CONCURRENCY_DB=pasos_test_<disposable>, with the migrated schema already installed. No connection made.\n");
    exit(2);
}
require_once __DIR__ . '/../backend/lib/ConsultationRules.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function testConnection(): mysqli
{
    return new mysqli(getenv('PASOS_CONCURRENCY_HOST') ?: '127.0.0.1', getenv('PASOS_CONCURRENCY_USER') ?: 'root', getenv('PASOS_CONCURRENCY_PASSWORD') ?: '', getenv('PASOS_CONCURRENCY_DB'), (int) (getenv('PASOS_CONCURRENCY_PORT') ?: 3306));
}
try { $db = testConnection(); } catch (Throwable $e) {
    fwrite(STDERR, "UNAVAILABLE: disposable MySQL connection failed; no concurrency proof.\n");
    exit(2);
}

if (($argv[1] ?? '') === 'worker') {
    $sync = function ($db, $professionalId, $data, $meet) use ($argv) {
        if ($argv[5] !== 'go') {
            echo "LOCKED\n"; fflush(STDOUT);
            stream_set_timeout(STDIN, 5);
            if (trim((string) fgets(STDIN)) !== 'release') throw new RuntimeException('Worker barrier timed out.');
        }
        if ($argv[5] === 'rollback') throw new RuntimeException('Deliberate rollback');
        return ['ok' => true, 'event_id' => 'offline-concurrency', 'meet_link' => null, 'conference_status' => 'not_requested'];
    };
    try {
        consultation_update($db, (int) $argv[2], (int) $argv[3], 'professional', ['scheduled_at' => $argv[4]], $sync);
        echo "ACCEPT\n";
    } catch (InvalidArgumentException $e) { echo "REJECT\n";
    } catch (Throwable $e) { echo $argv[5] === 'rollback' ? "ROLLBACK\n" : "ERROR\n"; }
    exit;
}

function worker(int $id, int $professional, string $start, string $mode): array
{
    $process = proc_open([PHP_BINARY, __FILE__, 'worker', (string) $id, (string) $professional, $start, $mode], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start worker.');
    stream_set_timeout($pipes[1], 3);
    return [$process, $pipes];
}
function line(array $worker): string
{
    $line = fgets($worker[1][1]);
    if ($line === false) throw new RuntimeException('Worker output timed out.');
    return trim($line);
}
function expect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
function finish(array $worker): void
{
    foreach ($worker[1] as $pipe) fclose($pipe);
    proc_close($worker[0]);
}

$users = []; $consultations = []; $workers = [];
try {
    $suffix = bin2hex(random_bytes(8));
    foreach (['user', 'professional', 'professional'] as $index => $role) {
        $uid = 'concurrency-' . $suffix . '-' . $index;
        $stmt = consultation_query($db, 'INSERT INTO users (firebase_uid, name, email, role) VALUES (?, ?, ?, ?)', 'ssss', [$uid, 'Disposable test', $uid . '@example.test', $role]);
        $users[] = (int) $stmt->insert_id; $stmt->close();
    }
    foreach ([$users[1], $users[1], $users[2]] as $professional) {
        $stmt = consultation_query($db, "INSERT INTO consultations (user_id, professional_id, description, contact_preference) VALUES (?, ?, 'Disposable concurrency test', 'phone')", 'ii', [$users[0], $professional]);
        $consultations[] = (int) $stmt->insert_id; $stmt->close();
    }
    $date = (new DateTimeImmutable('tomorrow', new DateTimeZone('America/Argentina/Buenos_Aires')))->format('Y-m-d');
    foreach (['same', 'adjacent', 'rollback', 'distinct'] as $scenario) {
        foreach ($consultations as $id) {
            $stmt = consultation_query($db, "UPDATE consultations SET status = 'received', scheduled_at = NULL, google_event_id = NULL WHERE id = ?", 'i', [$id]); $stmt->close();
        }
        $first = worker($consultations[0], $users[1], $date . 'T09:00', $scenario === 'rollback' ? 'rollback' : 'pause');
        $workers[] = $first;
        expect(line($first) === 'LOCKED', 'First worker did not obtain its mutex.');
        $distinct = $scenario === 'distinct';
        $second = worker($consultations[$distinct ? 2 : 1], $users[$distinct ? 2 : 1], $date . ($scenario === 'adjacent' ? 'T09:30' : 'T09:00'), 'go');
        $workers[] = $second;
        if ($distinct) {
            expect(line($second) === 'ACCEPT', 'Different professionals must complete independently while the first mutex is held.');
        } else {
            $read = [$second[1][1]]; $write = null; $except = null;
            expect(stream_select($read, $write, $except, 0, 200000) === 0, 'Second worker must wait for the same professional mutex.');
        }
        fwrite($first[1][0], "release\n"); fflush($first[1][0]);
        expect(line($first) === ($scenario === 'rollback' ? 'ROLLBACK' : 'ACCEPT'), 'Unexpected first result.');
        if (!$distinct) expect(line($second) === ($scenario === 'same' ? 'REJECT' : 'ACCEPT'), 'Unexpected second result.');
        finish($first); finish($second); $workers = [];
        echo 'PASS REAL MYSQL: ' . $scenario . "\n";
    }
} finally {
    foreach ($workers as $worker) { proc_terminate($worker[0]); finish($worker); }
    foreach ($consultations as $id) { $stmt = consultation_query($db, 'DELETE FROM consultations WHERE id = ?', 'i', [$id]); $stmt->close(); }
    foreach (array_reverse($users) as $id) { $stmt = consultation_query($db, 'DELETE FROM users WHERE id = ?', 'i', [$id]); $stmt->close(); }
    $db->close();
}
