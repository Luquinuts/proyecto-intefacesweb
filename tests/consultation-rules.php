<?php

require_once __DIR__ . '/../backend/lib/ConsultationRules.php';

$zone = new DateTimeZone('America/Argentina/Buenos_Aires');
$now = new DateTimeImmutable('2026-12-01T09:00:00', $zone);
assert(consultation_phone('+54 (9) 342-1234567') === '+5493421234567');
foreach ([null, [], 123, '', '5493421234567', '+12', '+012345678', '+1234567890123456', '+54abc12345678'] as $bad) {
    try { consultation_phone($bad); assert(false, 'Invalid phone accepted'); } catch (InvalidArgumentException $e) {}
}
assert(consultation_saved_phone('+5493421234567', '', 'phone') === '+5493421234567');
assert(consultation_saved_phone('+5493421234567', '+12345678901', 'phone') === '+5493421234567');
assert(consultation_saved_phone(null, null, 'video') === null);
foreach (['2026-02-30T09:00', '2026-12-01 09:00', 'tomorrow', '2026-12-01T25:00', [], '2026-12-01T09:00:00', "2026-12-01T09:00\0"] as $bad) {
    try { consultation_start($bad); assert(false, 'Invalid date accepted'); } catch (InvalidArgumentException $e) {}
}
assert(consultation_start('2026-12-01T09:00')->format('c') === '2026-12-01T09:00:00-03:00');
$row = ['id' => 7, 'user_id' => 1, 'professional_id' => 3, 'status' => 'scheduled', 'scheduled_at' => '2026-12-01 09:00:00', 'contact_preference' => 'video', 'meeting_link' => 'https://meet.google.com/abc-defg-hij', 'phone' => '+5493421234567'];
assert(consultation_can_read($row, 1, 'user'));
assert(consultation_can_read($row, 3, 'professional'));
assert(consultation_can_read($row, 2, 'administrator'));
assert(!consultation_can_read($row, 4, 'professional'));
assert(consultation_meeting($row, $now)['can_join']);
foreach (['08:59:59', '09:30:00', '10:00:00'] as $time) {
    $fields = consultation_meeting($row, new DateTimeImmutable('2026-12-01T' . $time, $zone));
    assert(!$fields['can_join'] && $fields['meeting_link'] === null);
}
assert(!consultation_meeting(array_replace($row, ['status' => 'closed']), $now)['can_join']);
foreach (['http://meet.google.com/abc', 'https://meet.google.com.evil.test/abc', 'https://evil.test/', 'https://user@meet.google.com/abc', "https://meet.google.com/abc\r\nX: y"] as $bad) {
    assert(!consultation_meeting(array_replace($row, ['meeting_link' => $bad]), $now)['can_join']);
}

// SQL-shaped fake executes the shared transaction flow; it is not a MySQL lock proof.
class ConsultationFakeStatement {
    public $db; public $sql; public $values = []; public $rows = []; public $insert_id = 8;
    function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    function bind_param($types, &...$values) { $this->values = &$values; return true; }
    function execute() {
        $this->db->log[] = $this->sql;
        $s = $this->sql; $v = $this->values;
        if (strpos($s, 'SELECT phone') === 0) $this->rows = [['phone' => $this->db->phone]];
        elseif (strpos($s, 'SELECT professional_id') === 0) $this->rows = [['professional_id' => 3]];
        elseif (strpos($s, 'SELECT id FROM users') === 0) $this->rows = [['id' => 3]];
        elseif (strpos($s, 'SELECT c.*') === 0 || strpos($s, 'SELECT id, user_id') === 0 || strpos($s, 'SELECT id, scheduled_at') === 0) $this->rows = [$this->db->row];
        elseif (strpos($s, 'SELECT id FROM consultations') === 0) {
            $this->rows = [];
            foreach ($this->db->busy as $busy) {
                if ($busy['id'] != $v[1] && $busy['professional_id'] == $v[0] && $busy['status'] === 'scheduled'
                    && $busy['scheduled_at'] < $v[2] && date('Y-m-d H:i:s', strtotime($busy['scheduled_at']) + 1800) > $v[3]) $this->rows[] = ['id' => $busy['id']];
            }
        } elseif (strpos($s, 'UPDATE users SET phone') === 0) $this->db->phone = $v[0];
        elseif (strpos($s, 'INSERT INTO consultations') === 0 && $this->db->failInsert) throw new RuntimeException('Insert failed');
        elseif (strpos($s, 'INSERT INTO consultations') === 0) $this->db->created = $v;
        elseif (strpos($s, 'UPDATE consultations') === 0) $this->db->saved = $v;
        return true;
    }
    function get_result() { return $this; }
    function fetch_assoc() { return array_shift($this->rows); }
    function close() {}
}
class ConsultationFakeDb {
    public $log = []; public $phone = null; public $failInsert = false; public $snapshot; public $row; public $busy = []; public $saved; public $created;
    function prepare($sql) { return new ConsultationFakeStatement($this, $sql); }
    function begin_transaction() { $this->log[] = 'BEGIN'; $this->snapshot = $this->phone; return true; }
    function commit() { $this->log[] = 'COMMIT'; return true; }
    function rollback() { $this->log[] = 'ROLLBACK'; $this->phone = $this->snapshot; return true; }
}

if (($argv[1] ?? '') === 'endpoint') {
    $case = $argv[2];
    $conexion = new ConsultationFakeDb();
    $conexion->row = $row + ['user_email' => 'owner@example.test', 'description' => 'Private', 'professional_note' => null, 'requested_at' => '2026-11-30 09:00:00'];
    $current = new DateTimeImmutable('now', $zone);
    $conexion->row['scheduled_at'] = $current->modify('-5 minutes')->format('Y-m-d H:i:s');
    $_SESSION = ['autenticado' => true, 'usuario_id' => 1, 'rol' => 'user'];
    $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/consultations.php/7'];
    $_GET = ['id' => '7'];
    if (strpos($case, 'unauthorized') !== false) $_SESSION = ['autenticado' => true, 'usuario_id' => 4, 'rol' => 'professional'];
    if (strpos($case, 'admin') !== false) $_SESSION['rol'] = 'administrator';
    if (strpos($case, 'closed') !== false) $conexion->row['status'] = 'closed';
    if (strpos($case, 'expired') !== false) $conexion->row['scheduled_at'] = $current->modify('-31 minutes')->format('Y-m-d H:i:s');
    if (strpos($case, 'early') !== false) $conexion->row['scheduled_at'] = $current->modify('+5 minutes')->format('Y-m-d H:i:s');
    if (strpos($case, 'unsafe') !== false) $conexion->row['meeting_link'] = 'https://evil.test/';
    $file = strpos($case, 'join') === 0 ? 'join_meeting.php' : (strpos($case, 'video') === 0 ? 'videocall.php' : 'consultations.php');
    $source = file_get_contents(__DIR__ . '/../backend/api/' . $file);
    $source = preg_replace('/^<\?php\s*|session_start\(\);|^require_once .*;$/m', '', $source);
    // The real endpoint executes unchanged except its external boundaries and redirect capture.
    $source = str_replace("header('Location: ' . \$meeting['meeting_link'], true, 302);", "http_response_code(302); echo json_encode(['redirect' => \$meeting['meeting_link']]);", $source);
    register_shutdown_function(function () { echo "\nSTATUS:" . http_response_code(); });
    eval($source);
    exit;
}

foreach ([
    'item-unauthorized' => 403, 'item-admin-expired' => 200, 'video-admin-expired' => 200,
    'join-owner' => 302, 'join-closed' => 403, 'join-expired' => 403, 'join-early' => 403,
    'join-unauthorized' => 403, 'join-unsafe' => 403,
] as $case => $status) {
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-d', 'zend.assertions=1', '-d', 'assert.exception=1', __FILE__, 'endpoint', $case], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    assert(proc_close($process) === 0, $errors);
    assert(substr($output, -strlen('STATUS:' . $status)) === 'STATUS:' . $status, $case . ': ' . $output . $errors);
    if ($status !== 302) assert(strpos($output, 'https://meet.google.com') === false, 'Closed/expired/unauthorized serialization leaked link');
    if ($case === 'item-unauthorized') assert(strpos($output, '+549') === false, 'Unrelated professional leaked phone');
}

$db = new ConsultationFakeDb();
$db->failInsert = true;
try { consultation_create($db, 1, 'user', ['description' => 'Private', 'contact_preference' => 'phone', 'phone' => '+54 9 3421234567', 'user_id' => 99]); assert(false); } catch (RuntimeException $e) {}
assert($db->phone === null && end($db->log) === 'ROLLBACK', 'Failed POST must not save phone');
$db->failInsert = false;
consultation_create($db, 1, 'user', ['description' => 'Private', 'contact_preference' => 'phone', 'phone' => '+54 9 3421234567', 'user_id' => 99]);
assert($db->phone === '+5493421234567' && end($db->log) === 'COMMIT');
assert($db->created[0] === 1, 'POST user_id must come from the authenticated session');
consultation_create($db, 1, 'user', ['description' => 'Private', 'contact_preference' => 'phone', 'phone' => []]);
assert($db->phone === '+5493421234567');
$insert = array_values(array_filter($db->log, fn($sql) => strpos($sql, 'INSERT INTO consultations') === 0));
assert(count($insert) === 3);
$db->row = $row + ['google_event_id' => 'stable', 'user_email' => 'owner@example.test', 'professional_note' => null];
$remoteCalls = 0;
$remote = function ($db, $professionalId, $data, $withMeet) use (&$remoteCalls) {
    $remoteCalls++;
    assert($professionalId === 3 && $data['owner_email'] === 'owner@example.test');
    assert(!isset($data['description']) && !isset($data['phone']));
    return ['ok' => true, 'event_id' => 'stable', 'meet_link' => 'https://meet.google.com/abc-defg-hij', 'conference_status' => 'success'];
};
$db->busy = [array_replace($row, ['id' => 9])];
try { consultation_update($db, 7, 3, 'professional', ['scheduled_at' => '2026-12-01T09:15'], $remote, $now->modify('-1 day')); assert(false); } catch (InvalidArgumentException $e) {}
assert($remoteCalls === 0 && end($db->log) === 'ROLLBACK', 'Conflicts precede Google');
consultation_update($db, 7, 3, 'professional', ['scheduled_at' => '2026-12-01T09:30'], $remote, $now->modify('-1 day'));
assert($remoteCalls === 1);
$db->busy = [$row];
consultation_update($db, 7, 3, 'professional', ['scheduled_at' => '2026-12-01T09:00'], $remote, $now->modify('+1 hour'));
assert($remoteCalls === 2, 'Scheduled same-slot check remains possible after expiry');
foreach ([['professional', 4, ['status' => 'closed']], ['administrator', 2, ['scheduled_at' => '2026-11-30T09:00']]] as [$role, $user, $data]) {
    try { consultation_update($db, 7, $user, $role, $data, $remote, $now); assert(false); } catch (InvalidArgumentException | DomainException $e) {}
}
$db->row['status'] = 'closed';
try { consultation_update($db, 7, 3, 'professional', ['scheduled_at' => '2026-12-01T09:00'], $remote, $now->modify('+1 hour')); assert(false); } catch (InvalidArgumentException $e) {}
$db->row['status'] = 'scheduled';
consultation_update($db, 7, 2, 'administrator', ['status' => 'closed'], $remote, $now);
assert($db->saved[1] === 'closed');
$lastBegin = array_search('BEGIN', array_reverse($db->log, true));
$flow = array_slice($db->log, $lastBegin);
assert(strpos($flow[2], 'users') !== false && strpos($flow[2], 'FOR UPDATE') !== false);
assert(strpos($flow[3], 'c.*') !== false && strpos($flow[3], 'FOR UPDATE') !== false, 'Professional mutex precedes fresh consultation lock');
// Administrator reopening also uses the overlap check before any remote mutation.
$db->row['status'] = 'under_review';
$db->busy = [array_replace($row, ['id' => 9])];
try { consultation_update($db, 7, 2, 'administrator', ['status' => 'scheduled'], $remote, $now->modify('-1 day')); assert(false); } catch (InvalidArgumentException $e) {}
assert(end($db->log) === 'ROLLBACK');
$db->row = array_replace($db->row, ['contact_preference' => 'email', 'status' => 'received', 'scheduled_at' => null]);
$callsBeforeEmail = $remoteCalls;
consultation_update($db, 7, 3, 'professional', ['status' => 'scheduled'], $remote, $now);
assert($db->saved[1] === 'scheduled' && $db->saved[2] === null, 'Legacy email status does not require an appointment date');
$db->row['status'] = 'scheduled';
consultation_update($db, 7, 3, 'professional', ['professional_note' => 'Email review note'], $remote, $now);
assert($db->saved[0] === 'Email review note' && $db->saved[2] === null);
try { consultation_update($db, 7, 3, 'professional', ['scheduled_at' => '2026-12-02T09:00'], $remote, $now); assert(false); } catch (InvalidArgumentException $e) {}
assert($remoteCalls === $callsBeforeEmail, 'Email review must never create a Google booking');
foreach (['phone', 'video'] as $preference) {
    $db->row['contact_preference'] = $preference;
    $db->row['status'] = 'received';
    try { consultation_update($db, 7, 3, 'professional', ['status' => 'scheduled'], $remote, $now); assert(false); } catch (InvalidArgumentException $e) {}
}
echo "PASS: phone validation/reuse/rollback, privacy, strict Buenos Aires dates, join boundaries, transaction flow, overlap/adjacent/self/retry/closed/admin\n";
