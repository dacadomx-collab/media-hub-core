<?php
/**
 * MH-CORE: Generacion y auditoria de Enlaces de Invitado (Guest Onboarding).
 * Ubicacion: api/guest_links.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   POST action=create -> Genera un enlace temporal con TTL de 3 clics para
 *                          un show nativo (programs.is_native_show = 1).
 *                          call_id (Fase 5.5, opcional): ata el enlace a un
 *                          llamado especifico de la agenda del show, para
 *                          que guest_form.php pueda mostrar fecha/hora reales.
 *   GET  ?action=list&program_id=ID -> Lista los enlaces generados para un show,
 *                          con datos del llamado atado y un indicador de
 *                          completitud del formulario del invitado (Fase 5.5).
 *
 * Ver knowledge/03_CONTRATOS_API_Y_RUTAS.md Contrato 10.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';

$currentUser = mh_require_session();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

$pdo = Database::getInstance()->getConnection();

try {
    // -----------------------------------------------------------------
    // POST action=create — Genera un enlace de invitado para un show nativo
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Conductor']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'guest_links_create');
        mh_require_csrf($payload);

        $programId = (int) ($payload['program_id'] ?? 0);
        if ($programId <= 0) {
            mh_json_response('error', 'Falta el campo program_id.', [], 422);
        }

        $programStmt = $pdo->prepare(
            'SELECT id, name, is_native_show, conductor_user_id
             FROM programs WHERE id = :id'
        );
        $programStmt->execute(['id' => $programId]);
        $program = $programStmt->fetch();

        if (!$program) {
            mh_json_response('error', 'El programa no existe.', [], 422);
        }
        if ((int) $program['is_native_show'] !== 1) {
            mh_json_response('error', 'Solo los shows nativos pueden generar enlaces de invitado.', [], 422);
        }

        // Un Conductor solo genera enlaces para su propio show asignado.
        if ($currentUser['role'] === 'Conductor'
            && (int) ($program['conductor_user_id'] ?? 0) !== (int) $currentUser['user_id']
        ) {
            mh_json_response('error', 'No eres el conductor asignado a este show.', [], 403);
        }

        // call_id (Fase 5.5, opcional): debe pertenecer al MISMO show.
        $callId = isset($payload['call_id']) && $payload['call_id'] !== '' ? (int) $payload['call_id'] : null;

        if ($callId !== null) {
            $callStmt = $pdo->prepare('SELECT id FROM calls WHERE id = :id AND program_id = :program_id');
            $callStmt->execute(['id' => $callId, 'program_id' => $programId]);

            if (!$callStmt->fetch()) {
                mh_json_response('error', 'El llamado indicado no pertenece a este show.', [], 422);
            }
        }

        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare(
            'INSERT INTO guest_invite_links (program_id, call_id, token, created_by)
             VALUES (:program_id, :call_id, :token, :created_by)'
        );
        $stmt->execute([
            'program_id' => $programId,
            'call_id'    => $callId,
            'token'      => $token,
            'created_by' => $currentUser['user_id'],
        ]);

        $appUrl    = mh_detect_base_url();
        $publicUrl = $appUrl !== '' ? "{$appUrl}/guest_form.php?token={$token}" : "guest_form.php?token={$token}";

        mh_json_response('success', 'Enlace de invitado generado.', [
            'id'         => (int) $pdo->lastInsertId(),
            'token'      => $token,
            'public_url' => $publicUrl,
        ], 201);
    }

    // -----------------------------------------------------------------
    // POST action=create_batch — Genera de 1 a 6 enlaces de invitado de
    // una sola vez, todos atados al MISMO call_id (Fase 5.8 — planner
    // "Siguiente Programa" del Conductor).
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create_batch') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Conductor']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'guest_links_create_batch');
        mh_require_csrf($payload);

        $programId = (int) ($payload['program_id'] ?? 0);
        $callId    = (int) ($payload['call_id'] ?? 0);
        $quantity  = (int) ($payload['quantity'] ?? 0);

        if ($programId <= 0 || $callId <= 0) {
            mh_json_response('error', 'Faltan campos obligatorios (program_id, call_id).', [], 422);
        }
        if ($quantity < 1 || $quantity > 6) {
            mh_json_response('error', 'La cantidad de invitados debe ser entre 1 y 6.', [], 422);
        }

        $programStmt = $pdo->prepare(
            'SELECT id, is_native_show, conductor_user_id FROM programs WHERE id = :id'
        );
        $programStmt->execute(['id' => $programId]);
        $program = $programStmt->fetch();

        if (!$program) {
            mh_json_response('error', 'El programa no existe.', [], 422);
        }
        if ((int) $program['is_native_show'] !== 1) {
            mh_json_response('error', 'Solo los shows nativos pueden generar enlaces de invitado.', [], 422);
        }
        if ($currentUser['role'] === 'Conductor'
            && (int) ($program['conductor_user_id'] ?? 0) !== (int) $currentUser['user_id']
        ) {
            mh_json_response('error', 'No eres el conductor asignado a este show.', [], 403);
        }

        $callStmt = $pdo->prepare('SELECT id FROM calls WHERE id = :id AND program_id = :program_id');
        $callStmt->execute(['id' => $callId, 'program_id' => $programId]);

        if (!$callStmt->fetch()) {
            mh_json_response('error', 'El llamado indicado no pertenece a este show.', [], 422);
        }

        $appUrl = mh_detect_base_url();
        $links  = [];

        $insertStmt = $pdo->prepare(
            'INSERT INTO guest_invite_links (program_id, call_id, token, created_by)
             VALUES (:program_id, :call_id, :token, :created_by)'
        );

        for ($i = 0; $i < $quantity; $i++) {
            $token = bin2hex(random_bytes(32));
            $insertStmt->execute([
                'program_id' => $programId,
                'call_id'    => $callId,
                'token'      => $token,
                'created_by' => $currentUser['user_id'],
            ]);

            $links[] = [
                'id'         => (int) $pdo->lastInsertId(),
                'token'      => $token,
                'public_url' => $appUrl !== '' ? "{$appUrl}/guest_form.php?token={$token}" : "guest_form.php?token={$token}",
            ];
        }

        mh_json_response('success', count($links) . ' enlace(s) de invitado generado(s).', ['links' => $links], 201);
    }

    // -----------------------------------------------------------------
    // GET ?action=list&program_id=ID — Auditoria de enlaces por show
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Conductor']);

        $programId = (int) ($_GET['program_id'] ?? 0);
        if ($programId <= 0) {
            mh_json_response('error', 'Falta el parametro program_id.', [], 422);
        }

        if ($currentUser['role'] === 'Conductor') {
            $ownStmt = $pdo->prepare('SELECT conductor_user_id FROM programs WHERE id = :id');
            $ownStmt->execute(['id' => $programId]);
            $owner = $ownStmt->fetch();

            if (!$owner || (int) $owner['conductor_user_id'] !== (int) $currentUser['user_id']) {
                mh_json_response('error', 'No eres el conductor asignado a este show.', [], 403);
            }
        }

        $stmt = $pdo->prepare(
            'SELECT gl.id, gl.token, gl.click_count, gl.max_clicks, gl.status,
                    gl.created_at, gl.expired_at, gl.call_id,
                    c.title AS call_title, c.call_date, c.start_time, c.end_time,
                    gs.full_name AS guest_full_name, gs.title_position AS guest_title_position,
                    gs.social_links, gs.whatsapp, gs.website, gs.email, gs.invite_message
             FROM guest_invite_links gl
             LEFT JOIN calls c ON c.id = gl.call_id
             LEFT JOIN guest_submissions gs ON gs.link_id = gl.id
             WHERE gl.program_id = :program_id
             ORDER BY gl.created_at DESC'
        );
        $stmt->execute(['program_id' => $programId]);
        $links = $stmt->fetchAll();

        // Indicador de completitud (Fase 5.4/5.5 — Monitor OBS): banderas
        // POR CAMPO (no solo un agregado) para que el frontend pinte un
        // badge individual por cada dato opcional (redes, WhatsApp, mensaje)
        // ademas del progreso de los 2 obligatorios. Un campo vacio se
        // normaliza a "" / [] antes de exponerse -- nunca null -- para que
        // el frontend no tenga que adivinar el tipo al renderizar.
        foreach ($links as &$link) {
            $requiredFlags = [
                'full_name'      => $link['guest_full_name'] !== null,
                'title_position' => $link['guest_title_position'] !== null,
            ];

            $optionalFieldsMap = [
                'social_links'   => $link['social_links'],
                'whatsapp'       => $link['whatsapp'],
                'website'        => $link['website'],
                'invite_message' => $link['invite_message'],
            ];
            $optionalFlags = [];
            foreach ($optionalFieldsMap as $field => $value) {
                $optionalFlags[$field] = !empty($value);
                unset($link[$field]);
            }
            unset($link['email']);

            $requiredDone = count(array_filter($requiredFlags));
            $optionalDone = count(array_filter($optionalFlags));

            $link['completion'] = [
                'required_done'   => $requiredDone,
                'required_total'  => count($requiredFlags),
                'required_fields' => $requiredFlags,
                'optional_done'   => $optionalDone,
                'optional_total'  => count($optionalFlags),
                'optional_fields' => $optionalFlags,
                'has_submission'  => $link['guest_full_name'] !== null,
            ];
        }
        unset($link);

        mh_json_response('success', 'Enlaces cargados.', ['links' => $links]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/guest_links.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
