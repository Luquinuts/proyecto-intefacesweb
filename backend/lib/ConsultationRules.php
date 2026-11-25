<?php

function consultation_phone($value): string
{
    if (!is_string($value) || !preg_match('/^\+[0-9 ()-]+$/D', trim($value))) {
        throw new InvalidArgumentException('Ingresá un teléfono internacional con + y código de país.');
    }
    $phone = str_replace([' ', '(', ')', '-'], '', trim($value));
    if (!preg_match('/^\+[1-9][0-9]{7,14}$/D', $phone)) {
        throw new InvalidArgumentException('El teléfono debe tener entre 8 y 15 dígitos.');
    }
    return $phone;
}

function consultation_saved_phone(?string $saved, $submitted, string $preference): ?string
{
    if ($saved !== null && $saved !== '') return $saved;
    return $preference === 'phone' ? consultation_phone($submitted) : null;
}

function consultation_start($value): DateTimeImmutable
{
    if (!is_string($value) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}$/D', $value)) {
        throw new InvalidArgumentException('Fecha/hora inválida. Usá la hora de Buenos Aires.');
    }
    $zone = new DateTimeZone('America/Argentina/Buenos_Aires');
    $start = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $zone);
    if (!$start || $start->format('Y-m-d\TH:i') !== $value || (int) $start->format('Y') < 1000) {
        throw new InvalidArgumentException('Fecha/hora inválida. Usá la hora de Buenos Aires.');
    }
    return $start;
}

function consultation_can_read(array $row, int $userId, string $role): bool
{
    return $role === 'administrator' || (int) $row['user_id'] === $userId
        || ($role === 'professional' && (int) $row['professional_id'] === $userId);
}

function consultation_meeting(array $row, ?DateTimeImmutable $now = null): array
{
    $zone = new DateTimeZone('America/Argentina/Buenos_Aires');
    $now = $now ?? new DateTimeImmutable('now', $zone);
    $start = !empty($row['scheduled_at']) ? DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $row['scheduled_at'], $zone) : false;
    $end = $start ? $start->modify('+30 minutes') : null;
    $link = $row['meeting_link'] ?? '';
    $parts = is_string($link) ? parse_url($link) : false;
    $safeLink = $parts && ($parts['scheme'] ?? '') === 'https' && ($parts['host'] ?? '') === 'meet.google.com'
        && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['port'])
        && !preg_match('/[\x00-\x20\x7f]/', $link) && !empty($parts['path']);
    $canJoin = $safeLink && ($row['contact_preference'] ?? '') === 'video' && $row['status'] === 'scheduled'
        && $start && $start <= $now && $now < $end;
    return [
        'scheduled_at_iso' => $start ? $start->format('c') : null,
        'ends_at' => $end ? $end->format('c') : null,
        'can_join' => (bool) $canJoin,
        'meeting_state' => !$start ? 'unscheduled' : ($row['status'] === 'closed' || $now >= $end ? 'finished' : ($now < $start ? 'before' : 'current')),
        'meeting_link' => $canJoin ? $link : null,
    ];
}

function consultation_query($db, string $sql, string $types = '', array $values = [])
{
    $stmt = $db->prepare($sql);
    if (!$stmt || ($types !== '' && !$stmt->bind_param($types, ...$values)) || !$stmt->execute()) {
        throw new RuntimeException('Could not save/read consultation.');
    }
    return $stmt;
}

function consultation_create($db, int $userId, string $role, array $data): int
{
    $description = $data['description'] ?? null;
    $preference = $data['contact_preference'] ?? null;
    if (!is_string($description) || trim($description) === '' || mb_strlen($description) > 3000
        || !in_array($preference, ['phone', 'video'], true)) {
        throw new InvalidArgumentException('Datos de consulta inválidos.');
    }
    $description = trim($description);
    if (!$db->begin_transaction()) throw new RuntimeException('Transaction failed.');
    try {
        $stmt = consultation_query($db, 'SELECT phone FROM users WHERE id = ? FOR UPDATE', 'i', [$userId]);
        $owner = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$owner) throw new DomainException('Not authorized.');
        $phone = consultation_saved_phone($owner['phone'], $data['phone'] ?? null, $preference);
        if (empty($owner['phone']) && $phone !== null) {
            $stmt = consultation_query($db, 'UPDATE users SET phone = ? WHERE id = ?', 'si', [$phone, $userId]);
            $stmt->close();
        }
        $professionalId = null;
        if ($role === 'user') {
            $stmt = consultation_query($db, "SELECT id FROM users WHERE role='professional' ORDER BY id ASC LIMIT 1");
            $professional = $stmt->get_result()->fetch_assoc();
            $professionalId = $professional ? (int) $professional['id'] : null;
            $stmt->close();
        }
        $stmt = consultation_query($db, 'INSERT INTO consultations (user_id, professional_id, description, contact_preference) VALUES (?, ?, ?, ?)', 'iiss', [$userId, $professionalId, $description, $preference]);
        $id = (int) $stmt->insert_id;
        $stmt->close();
        if (!$db->commit()) throw new RuntimeException('Commit failed.');
        return $id;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function consultation_update($db, int $id, int $userId, string $role, array $data, ?callable $sync = null, ?DateTimeImmutable $now = null): array
{
    if (!in_array($role, ['professional', 'administrator'], true)) throw new DomainException('Not authorized.');
    // The mutex protects overlap checks; avoid range gap locks across other professionals.
    $isolation = consultation_query($db, 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
    $isolation->close();
    if (!$db->begin_transaction()) throw new RuntimeException('Transaction failed.');
    try {
        // Discover the mutex ID only; read mutable consultation data after the mutex.
        $stmt = consultation_query($db, 'SELECT professional_id FROM consultations WHERE id = ?', 'i', [$id]);
        $initial = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$initial) throw new OutOfBoundsException('Consultation not found.');
        $professionalId = (int) $initial['professional_id'];
        $stmt = consultation_query($db, 'SELECT id FROM users WHERE id = ? FOR UPDATE', 'i', [$professionalId]);
        $professional = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $stmt = consultation_query($db, 'SELECT c.*, (SELECT email FROM users WHERE id = c.user_id) AS user_email FROM consultations c WHERE c.id = ? FOR UPDATE', 'i', [$id]);
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) throw new OutOfBoundsException('Consultation not found.');
        if ((int) $row['professional_id'] !== $professionalId || ($role === 'professional' && $professionalId !== $userId)) throw new DomainException('Not authorized.');
        $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('America/Argentina/Buenos_Aires'));
        $status = $row['status'];
        if (array_key_exists('status', $data)) {
            if (!in_array($data['status'], ['received', 'under_review', 'scheduled', 'closed'], true)) throw new InvalidArgumentException('Estado inválido.');
            $status = $data['status'];
        }
        $note = $row['professional_note'];
        if (array_key_exists('professional_note', $data)) {
            if (!is_string($data['professional_note']) || mb_strlen($data['professional_note']) > 3000) throw new InvalidArgumentException('Nota inválida.');
            $note = trim($data['professional_note']);
        }
        $scheduledAt = $row['scheduled_at'];
        $eventId = $row['google_event_id'];
        $link = $row['meeting_link'];
        $conferenceStatus = null;
        $booking = array_key_exists('scheduled_at', $data);
        $isAppointment = in_array($row['contact_preference'], ['phone', 'video'], true);
        if ($booking) {
            if (!$professional || !$isAppointment) throw new InvalidArgumentException('No se puede agendar esta consulta.');
            $start = consultation_start($data['scheduled_at']);
            $scheduledAt = $start->format('Y-m-d H:i:s');
            $sameSlot = $scheduledAt === $row['scheduled_at'] && $row['status'] === 'scheduled' && !empty($eventId);
            if ($start <= $now && !$sameSlot) throw new InvalidArgumentException('Seleccioná una fecha futura.');
            if ($row['status'] === 'closed' && $scheduledAt === $row['scheduled_at']) throw new InvalidArgumentException('La consulta está cerrada; elegí una nueva fecha futura.');
            if (isset($data['status']) && $data['status'] !== 'scheduled') throw new InvalidArgumentException('No combines agendar con otro estado.');
            $status = 'scheduled';
        } elseif ($isAppointment && $status === 'scheduled' && $row['status'] !== 'scheduled') {
            if ($row['status'] === 'closed') throw new InvalidArgumentException('La consulta está cerrada; elegí una nueva fecha futura.');
            if (!$scheduledAt || consultation_start(substr(str_replace(' ', 'T', $scheduledAt), 0, 16)) <= $now) throw new InvalidArgumentException('Seleccioná una nueva fecha futura.');
        }
        if ($isAppointment && $status === 'scheduled') {
            if (!$professional || !$scheduledAt) throw new InvalidArgumentException('La consulta necesita profesional y fecha.');
            $start = consultation_start(substr(str_replace(' ', 'T', $scheduledAt), 0, 16));
            $end = $start->modify('+30 minutes')->format('Y-m-d H:i:s');
            // The professional mutex serializes writers; READ COMMITTED avoids cross-professional range locks.
            $stmt = consultation_query($db, "SELECT id FROM consultations WHERE professional_id = ? AND id != ? AND status = 'scheduled' AND contact_preference IN ('phone', 'video') AND scheduled_at < ? AND DATE_ADD(scheduled_at, INTERVAL 30 MINUTE) > ? LIMIT 1", 'iiss', [$professionalId, $id, $end, $scheduledAt]);
            $conflict = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($conflict) throw new InvalidArgumentException('Ese horario ya está ocupado. Elegí otro.');
        }
        if ($booking) {
            $sync = $sync ?? 'google_create_calendar_event';
            $result = $sync($db, $professionalId, [
                'consultation_id' => $id, 'event_id' => $eventId ?: null,
                'summary' => 'Consulta PASOS #' . $id, 'owner_email' => $row['user_email'],
                'start' => $start->format('c'), 'end' => $start->modify('+30 minutes')->format('c'),
                'timeZone' => 'America/Argentina/Buenos_Aires',
            ], $row['contact_preference'] === 'video');
            if (!$result['ok']) throw new RuntimeException('No se pudo sincronizar Google Calendar. Reintentá con la misma fecha.');
            $eventId = $result['event_id'];
            $link = $row['contact_preference'] === 'video' ? ($result['meet_link'] ?? $link) : null;
            $conferenceStatus = $result['conference_status'];
        }
        $stmt = consultation_query($db, 'UPDATE consultations SET professional_note = ?, status = ?, scheduled_at = ?, google_event_id = ?, meeting_link = ? WHERE id = ?', 'sssssi', [$note, $status, $scheduledAt, $eventId, $link, $id]);
        $stmt->close();
        if (!$db->commit()) throw new RuntimeException('Commit failed.');
        return ['correcto' => true, 'id' => $id, 'conference_status' => $conferenceStatus];
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
