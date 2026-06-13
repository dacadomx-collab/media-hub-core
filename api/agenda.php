<?php
/**
 * MH-CORE: Endpoint de Agenda y Control de Colisiones (Estudio 5 de Mayo,
 * Van Terrestre, Embarcacion Maritima).
 * Ubicacion: api/agenda.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?action=list          -> Listado de llamados (filtros: from, to, location, program_id).
 *   POST action=create_call    -> Alta de llamado con ALGORITMO DE INMUNIDAD ANTE SOBRE-RESERVAS.
 *   POST action=assign_staff   -> Asigna staff a un llamado (exige anticipo 50% verificado).
 *   PUT  action=update_status  -> Cambia el estatus del llamado (Confirmado/Cancelado/Completado).
 *   PUT  action=verify_advance -> Verifica/desverifica el anticipo del 50% (solo Administrador).
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/mailer.php';

const MH_VALID_LOCATIONS = ['Estudio 5 de Mayo', 'Locacion Externa', 'Van Terrestre', 'Embarcacion Maritima'];
const MH_VALID_CALL_STATUS = ['Pendiente', 'Confirmado', 'Cancelado', 'Completado'];

$currentUser = mh_require_session();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

$pdo = Database::getInstance()->getConnection();

try {
    // -----------------------------------------------------------------
    // GET ?action=list — Listado de llamados con filtros opcionales
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list') {
        $where  = [];
        $params = [];

        if (!empty($_GET['from'])) {
            $where[]          = 'c.call_date >= :from';
            $params['from']   = (string) $_GET['from'];
        }
        if (!empty($_GET['to'])) {
            $where[]          = 'c.call_date <= :to';
            $params['to']     = (string) $_GET['to'];
        }
        if (!empty($_GET['location']) && in_array($_GET['location'], MH_VALID_LOCATIONS, true)) {
            $where[]            = 'c.location = :location';
            $params['location'] = (string) $_GET['location'];
        }
        if (!empty($_GET['program_id'])) {
            $where[]              = 'c.program_id = :program_id';
            $params['program_id'] = (int) $_GET['program_id'];
        }

        $sql = 'SELECT c.id, c.title, c.location, c.call_date, c.start_time, c.end_time,
                       c.status, c.advance_required_pct, c.advance_paid, c.total_amount,
                       c.notes, p.id AS program_id, p.name AS program_name,
                       cl.full_name AS client_name, cl.company AS client_company
                FROM calls c
                INNER JOIN programs p ON p.id = c.program_id
                INNER JOIN clients cl ON cl.id = p.client_id';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.call_date ASC, c.start_time ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $calls = $stmt->fetchAll();

        if ($calls !== []) {
            $callIds  = array_column($calls, 'id');
            $inClause = implode(',', array_fill(0, count($callIds), '?'));

            $assignStmt = $pdo->prepare(
                "SELECT ca.call_id, ca.user_id, u.full_name, u.role, ca.task_description, ca.status
                 FROM call_assignments ca
                 INNER JOIN users u ON u.id = ca.user_id
                 WHERE ca.call_id IN ($inClause)"
            );
            $assignStmt->execute($callIds);
            $assignments = $assignStmt->fetchAll();

            $byCall = [];
            foreach ($assignments as $row) {
                $byCall[$row['call_id']][] = $row;
            }
            foreach ($calls as &$call) {
                $call['assignments'] = $byCall[$call['id']] ?? [];
            }
            unset($call);
        }

        mh_json_response('success', 'Agenda cargada.', ['calls' => $calls]);
    }

    // -----------------------------------------------------------------
    // POST action=create_call — Alta de llamado con control de colisiones
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create_call') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'agenda_create_call');
        mh_require_csrf($payload);

        $programId  = (int) ($payload['program_id'] ?? 0);
        $title      = trim((string) ($payload['title'] ?? ''));
        $location   = (string) ($payload['location'] ?? '');
        $callDate   = (string) ($payload['call_date'] ?? '');
        $startTime  = (string) ($payload['start_time'] ?? '');
        $endTime    = (string) ($payload['end_time'] ?? '');
        $totalAmount = $payload['total_amount'] ?? null;
        $notes       = trim((string) ($payload['notes'] ?? ''));

        if ($programId <= 0 || $title === '' || $callDate === '' || $startTime === '' || $endTime === '') {
            mh_json_response('error', 'Faltan campos obligatorios (program_id, title, call_date, start_time, end_time).', [], 422);
        }
        if (!in_array($location, MH_VALID_LOCATIONS, true)) {
            mh_json_response('error', 'Locacion invalida.', [], 422);
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $callDate)) {
            mh_json_response('error', 'Formato de call_date invalido (YYYY-MM-DD).', [], 422);
        }
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $endTime)) {
            mh_json_response('error', 'Formato de hora invalido (HH:MM).', [], 422);
        }
        if ($startTime >= $endTime) {
            mh_json_response('error', 'La hora de inicio debe ser anterior a la hora de fin.', [], 422);
        }

        // -------------------------------------------------------------
        // ALGORITMO DE INMUNIDAD ANTE SOBRE-RESERVAS:
        // Bloquea cualquier solapamiento de horario en la misma locacion
        // y fecha, comparando contra llamados no cancelados.
        // (start_time < existing_end AND end_time > existing_start)
        // -------------------------------------------------------------
        $collisionStmt = $pdo->prepare(
            "SELECT COUNT(*) AS conflicts FROM calls
             WHERE location = :location AND call_date = :call_date AND status != 'Cancelado'
               AND start_time < :end_time AND end_time > :start_time"
        );
        $collisionStmt->execute([
            'location'  => $location,
            'call_date' => $callDate,
            'start_time' => $startTime,
            'end_time'   => $endTime,
        ]);

        if ((int) $collisionStmt->fetch()['conflicts'] > 0) {
            mh_json_response('error', 'Colision de horario detectada: ya existe un llamado en esa locacion, fecha y horario.', [], 409);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO calls (program_id, title, location, call_date, start_time, end_time, total_amount, notes, created_by)
             VALUES (:program_id, :title, :location, :call_date, :start_time, :end_time, :total_amount, :notes, :created_by)'
        );
        $stmt->execute([
            'program_id'   => $programId,
            'title'        => $title,
            'location'     => $location,
            'call_date'    => $callDate,
            'start_time'   => $startTime,
            'end_time'     => $endTime,
            'total_amount' => $totalAmount !== null ? (float) $totalAmount : null,
            'notes'        => $notes !== '' ? $notes : null,
            'created_by'   => $currentUser['user_id'],
        ]);

        mh_json_response('success', 'Llamado creado correctamente.', ['id' => (int) $pdo->lastInsertId()], 201);
    }

    // -----------------------------------------------------------------
    // POST action=assign_staff — Asigna staff a un llamado
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'assign_staff') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'agenda_assign_staff');
        mh_require_csrf($payload);

        $callId = (int) ($payload['call_id'] ?? 0);
        $userId = (int) ($payload['user_id'] ?? 0);
        $task   = trim((string) ($payload['task_description'] ?? ''));

        if ($callId <= 0 || $userId <= 0) {
            mh_json_response('error', 'Faltan campos obligatorios (call_id, user_id).', [], 422);
        }

        $callStmt = $pdo->prepare('SELECT status, advance_paid, advance_required_pct, total_amount FROM calls WHERE id = :id');
        $callStmt->execute(['id' => $callId]);
        $call = $callStmt->fetch();

        if (!$call) {
            mh_json_response('error', 'El llamado no existe.', [], 404);
        }
        if ($call['status'] === 'Cancelado') {
            mh_json_response('error', 'No se puede asignar personal a un llamado cancelado.', [], 422);
        }

        // -------------------------------------------------------------
        // COMPUERTA FINANCIERA OBLIGATORIA: el anticipo del 50% debe
        // figurar como verificado (advance_paid = 1) antes de escribir
        // cualquier asignacion de staff para este llamado.
        // -------------------------------------------------------------
        if ((int) $call['advance_paid'] !== 1) {
            $pct          = (float) $call['advance_required_pct'];
            $advanceAmount = $call['total_amount'] !== null ? ((float) $call['total_amount'] * $pct / 100) : 0.0;
            $formattedAmount = number_format($advanceAmount, 2, '.', ',');

            mh_json_response(
                'error',
                "No se puede asignar personal: el anticipo del {$pct}% (\${$formattedAmount} MXN) aun no ha sido verificado para este llamado.",
                [],
                422
            );
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO call_assignments (call_id, user_id, task_description)
                 VALUES (:call_id, :user_id, :task_description)'
            );
            $stmt->execute([
                'call_id'          => $callId,
                'user_id'          => $userId,
                'task_description' => $task !== '' ? $task : null,
            ]);
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                mh_json_response('error', 'Este usuario ya esta asignado a este llamado.', [], 409);
            }
            throw $e;
        }

        mh_json_response('success', 'Staff asignado al llamado correctamente.', ['id' => (int) $pdo->lastInsertId()], 201);
    }

    // -----------------------------------------------------------------
    // PUT action=update_status — Cambia el estatus de un llamado
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'update_status') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'agenda_update_status');
        mh_require_csrf($payload);

        $callId = (int) ($payload['call_id'] ?? 0);
        $status = (string) ($payload['status'] ?? '');

        if ($callId <= 0 || !in_array($status, MH_VALID_CALL_STATUS, true)) {
            mh_json_response('error', 'Faltan campos obligatorios o el estatus es invalido.', [], 422);
        }

        // -------------------------------------------------------------
        // VALIDACION COMERCIAL: no se puede confirmar el llamado en la
        // agenda mientras el anticipo del 50% no este verificado.
        // -------------------------------------------------------------
        if ($status === 'Confirmado') {
            $advanceStmt = $pdo->prepare('SELECT advance_paid FROM calls WHERE id = :id');
            $advanceStmt->execute(['id' => $callId]);
            $advanceRow = $advanceStmt->fetch();

            if (!$advanceRow) {
                mh_json_response('error', 'El llamado no existe.', [], 404);
            }
            if ((int) $advanceRow['advance_paid'] !== 1) {
                mh_json_response('error', 'No se puede confirmar el llamado: el anticipo del 50% aun no esta verificado.', [], 422);
            }
        }

        $stmt = $pdo->prepare('UPDATE calls SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $callId]);

        if ($stmt->rowCount() === 0) {
            mh_json_response('error', 'El llamado no existe.', [], 404);
        }

        mh_json_response('success', 'Estatus del llamado actualizado.', ['id' => $callId, 'status' => $status]);
    }

    // -----------------------------------------------------------------
    // PUT action=verify_advance — Verifica el anticipo del 50% (Administrador)
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'verify_advance') {
        mh_require_role($currentUser, ['Administrador']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'agenda_verify_advance');
        mh_require_csrf($payload);

        $callId      = (int) ($payload['call_id'] ?? 0);
        $advancePaid = (bool) ($payload['advance_paid'] ?? false);

        if ($callId <= 0) {
            mh_json_response('error', 'Falta el campo call_id.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE calls SET advance_paid = :advance_paid WHERE id = :id');
        $stmt->execute(['advance_paid' => $advancePaid ? 1 : 0, 'id' => $callId]);

        if ($stmt->rowCount() === 0) {
            mh_json_response('error', 'El llamado no existe.', [], 404);
        }

        // -------------------------------------------------------------
        // NOTIFICACION AUTOMATICA: al verificarse el anticipo del 50%,
        // se envia "Fecha Confirmada + Personal Reservado" al cliente.
        // -------------------------------------------------------------
        if ($advancePaid) {
            $detailsStmt = $pdo->prepare(
                'SELECT c.title, c.location, c.call_date, c.start_time, c.end_time,
                        p.name AS program_name, cl.full_name AS client_name, cl.email AS client_email
                 FROM calls c
                 INNER JOIN programs p ON p.id = c.program_id
                 INNER JOIN clients cl ON cl.id = p.client_id
                 WHERE c.id = :id'
            );
            $detailsStmt->execute(['id' => $callId]);
            $details = $detailsStmt->fetch();

            if ($details && !empty($details['client_email'])) {
                $staffStmt = $pdo->prepare(
                    'SELECT u.full_name FROM call_assignments ca
                     INNER JOIN users u ON u.id = ca.user_id
                     WHERE ca.call_id = :call_id'
                );
                $staffStmt->execute(['call_id' => $callId]);
                $staffNames = array_column($staffStmt->fetchAll(), 'full_name');

                $mail = mh_mail_call_confirmed(
                    $details['client_name'],
                    $details['program_name'],
                    $details['title'],
                    $details['location'],
                    $details['call_date'],
                    $details['start_time'],
                    $details['end_time'],
                    $staffNames
                );
                mh_send_mail($details['client_email'], $mail['subject'], $mail['html']);
            }
        }

        mh_json_response('success', 'Estatus de anticipo actualizado.', ['id' => $callId, 'advance_paid' => $advancePaid]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/agenda.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
