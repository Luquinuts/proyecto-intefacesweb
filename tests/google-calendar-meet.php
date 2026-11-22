<?php

// Fail before loading the old helper, which eagerly connects to MySQL.
$source = file_get_contents(__DIR__ . '/../backend/lib/GoogleCalendarMeet.php');
assert(strpos($source, 'function google_sync_calendar_event(') !== false, 'Offline Calendar transport seam is missing.');
require_once __DIR__ . '/../backend/lib/GoogleCalendarMeet.php';

$events = [];
$calls = [];
$mode = 'pending';
$transport = function ($method, $url, $body, $headers, $query) use (&$events, &$calls, &$mode) {
    $calls[] = $method;
    $id = $body['id'] ?? basename($url);
    if ($mode === 'network') return ['ok' => false, 'status' => 0, 'error' => 'Offline transport failure', 'data' => null];
    if ($method === 'POST') {
        $events[$id] = $body;
        if ($mode === 'uncertain') return ['ok' => false, 'status' => 0, 'error' => 'Response lost after insert', 'data' => null];
        if ($mode === 'conflict') return ['ok' => false, 'status' => 409, 'data' => null];
    } elseif (!isset($events[$id])) {
        return ['ok' => false, 'status' => 404, 'data' => null];
    } elseif ($method === 'PATCH') {
        assert(!isset($body['conferenceData']), 'Rescheduling must preserve the conference.');
        $events[$id] = array_replace($events[$id], $body);
    }
    $event = $events[$id];
    $event['id'] = $id;
    if (isset($event['conferenceData'])) {
        $event['conferenceData']['createRequest']['status']['statusCode'] = $mode === 'failed' ? 'failure' : 'pending';
        if ($mode === 'success') $event['hangoutLink'] = 'https://meet.google.com/offline-test';
        if ($mode === 'missing_link') $event['conferenceData']['createRequest']['status']['statusCode'] = 'success';
    }
    return ['ok' => true, 'status' => 200, 'data' => $event];
};
$data = ['consultation_id' => 17, 'start' => '2026-12-01T09:00:00', 'end' => '2026-12-01T09:30:00'];
$sync = function ($data, $meet = true) use ($transport) {
    return google_sync_calendar_event('offline-token', 'primary', 3, $data, $meet, $transport);
};
$pending = $sync($data);
assert($pending['ok'] && $pending['conference_status'] === 'pending' && $pending['event_id']);
assert(preg_match('/^[0-9a-v]{5,1024}$/', $pending['event_id']) === 1);
$mode = 'success';
$ready = $sync($data);
assert($ready['event_id'] === $pending['event_id'] && $ready['conference_status'] === 'success' && $ready['meet_link']);
assert(count(array_filter($calls, fn($method) => $method === 'POST')) === 1);
$data['event_id'] = $pending['event_id'];
$data['start'] = '2026-12-01T10:00:00';
$data['end'] = '2026-12-01T10:30:00';
$moved = $sync($data);
assert($moved['event_id'] === $pending['event_id'] && end($calls) === 'PATCH');
$sync($data);
assert(end($calls) === 'GET');
$zoned = $data;
$zoned['start'] = '2026-12-01T09:00:00';
$zoned['end'] = '2026-12-01T09:30:00';
$zoned['timeZone'] = 'America/Argentina/Buenos_Aires';
$events[$zoned['event_id']]['start']['dateTime'] = '2026-12-01T09:00:00+00:00';
$events[$zoned['event_id']]['end']['dateTime'] = '2026-12-01T09:30:00+00:00';
$sync($zoned);
assert(end($calls) === 'PATCH', 'Identical clock times with different offsets must reschedule.');
$events[$zoned['event_id']]['start']['dateTime'] = '2026-12-01T12:00:00+00:00';
$events[$zoned['event_id']]['end']['dateTime'] = '2026-12-01T12:30:00+00:00';
$sync($zoned);
assert(end($calls) === 'GET', 'Equivalent instants with different offsets must not PATCH.');
$events[$zoned['event_id']]['end']['dateTime'] = '2026-12-01T09:30:00+00:00';
$sync($zoned);
assert(end($calls) === 'PATCH', 'End time offsets must also be compared as instants.');
$earlier = $data;
$earlier['start'] = '2026-12-01T09:00:00';
$sync($earlier);
assert(count($events) === 1, 'Reordered retries cannot duplicate events.');
$mode = 'failed';
assert($sync($data)['conference_status'] === 'failure');
$mode = 'network';
assert(!$sync($data)['ok']);
$mode = 'conflict';
$other = $data;
unset($other['event_id']);
$other['consultation_id'] = 18;
assert($sync($other)['ok'] && end($calls) === 'GET');
$mode = 'success';
$other['consultation_id'] = 19;
$phone = $sync($other, false);
assert($phone['ok'] && $phone['conference_status'] === 'not_requested' && !$phone['meet_link']);
assert(!isset($events[$phone['event_id']]['conferenceData']));
$mode = 'uncertain';
$other['consultation_id'] = 20;
assert(!$sync($other)['ok']);
$insertCount = count(array_filter($calls, fn($method) => $method === 'POST'));
$mode = 'success';
assert($sync($other)['conference_status'] === 'success');
assert(count(array_filter($calls, fn($method) => $method === 'POST')) === $insertCount);
$mode = 'missing_link';
assert($sync($other)['conference_status'] === 'pending');
$legacy = $data;
$legacy['event_id'] = 'legacy-stored-id';
$events[$legacy['event_id']] = $events[$pending['event_id']];
assert($sync($legacy)['event_id'] === 'legacy-stored-id', 'Stored identity overrides generated identity.');
unset($events[$legacy['event_id']]);
assert(!$sync($legacy)['ok'] && end($calls) === 'GET', 'Missing stored events must not be recreated.');
$api = file_get_contents(__DIR__ . '/../backend/api/consultations.php');
assert(strpos($api, 'google_event_id = ?') !== false && strpos($api, '$currentEventId') !== false);
assert(strpos($api, "'conference_status' => \$conferenceStatus") !== false);
assert(strpos($api, "if (!\$generatedMeetLink)") === false, 'Pending events must reach the database update.');
assert(strpos($api, "\$vals[] = \$eventResult['event_id'];") !== false);
echo "PASS: pending/success, retry/reorder, reschedule, timezone instants, conflict, conference failure, network failure, phone, persistence structure\n";
