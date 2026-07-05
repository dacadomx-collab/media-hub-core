<?php
/**
 * MH-CORE: Endpoint de Checklists Operativos de Set (Antes / Durante / Despues).
 * Ubicacion: api/checklist.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?action=get&call_id=ID -> Checklist completo (3 fases) + progreso del llamado.
 *   POST action=toggle           -> Marca/desmarca un item; deja firma digital
 *                                    (checked_by + checked_at) del usuario autenticado.
 *
 * Acceso: Administrador / Lider_Proyecto siempre; el resto de roles solo si
 * estan asignados al llamado (call_assignments).
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';

const MH_CHECKLIST_PHASES = ['Antes', 'Durante', 'Despues'];

$currentUser = mh_require_session();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

$pdo = Database::getInstance()->getConnection();

/**
 * Verifica que el usuario actual pueda operar el checklist del llamado:
 * Administrador/Lider_Proyecto siempre, o staff asignado a ese llamado.
 */
function mh_checklist_assert_access(PDO $pdo, array $currentUser, int $callId): void
{
    if (in_array($currentUser['role'], ['Super_admin', 'Admin', 'Lider_Proyecto'], true)) {
        return;
    }

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS total FROM call_assignments WHERE call_id = :call_id AND user_id = :user_id'
    );
    $stmt->execute(['call_id' => $callId, 'user_id' => $currentUser['user_id']]);

    if ((int) $stmt->fetch()['total'] === 0) {
        mh_json_response('error', 'No estas asignado a este llamado.', [], 403);
    }
}

try {
    // -----------------------------------------------------------------
    // GET ?action=get&call_id=ID — Checklist completo (3 fases) + progreso
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'get') {
        $callId = (int) ($_GET['call_id'] ?? 0);
        if ($callId <= 0) {
            mh_json_response('error', 'Falta el parametro call_id.', [], 422);
        }

        $callStmt = $pdo->prepare('SELECT id, title, location, call_date, status FROM calls WHERE id = :id');
        $callStmt->execute(['id' => $callId]);
        $call = $callStmt->fetch();

        if (!$call) {
            mh_json_response('error', 'El llamado no existe.', [], 404);
        }

        mh_checklist_assert_access($pdo, $currentUser, $callId);

        $stmt = $pdo->prepare(
            'SELECT t.id AS template_id, t.phase, t.item_key, t.item_label, t.sort_order,
                    COALESCE(p.is_checked, 0) AS is_checked, p.checked_at, p.notes,
                    u.full_name AS checked_by_name
             FROM checklist_templates t
             LEFT JOIN call_checklist_progress p ON p.template_id = t.id AND p.call_id = :call_id
             LEFT JOIN users u ON u.id = p.checked_by
             ORDER BY t.phase, t.sort_order'
        );
        $stmt->execute(['call_id' => $callId]);
        $rows = $stmt->fetchAll();

        $checklist = ['Antes' => [], 'Durante' => [], 'Despues' => []];
        foreach ($rows as $row) {
            $checklist[$row['phase']][] = [
                'template_id'     => (int) $row['template_id'],
                'item_key'        => $row['item_key'],
                'item_label'      => $row['item_label'],
                'is_checked'      => (bool) $row['is_checked'],
                'checked_at'      => $row['checked_at'],
                'checked_by_name' => $row['checked_by_name'],
                'notes'           => $row['notes'],
            ];
        }

        mh_json_response('success', 'Checklist cargado.', ['call' => $call, 'checklist' => $checklist]);
    }

    // -----------------------------------------------------------------
    // POST action=toggle — Marca/desmarca un item del checklist
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'toggle') {
        $payload = mh_read_json_body();
        mh_guard_request($payload, 'checklist_toggle');
        mh_require_csrf($payload);

        $callId     = (int) ($payload['call_id'] ?? 0);
        $templateId = (int) ($payload['template_id'] ?? 0);
        $isChecked  = (bool) ($payload['is_checked'] ?? false);
        $notes      = trim((string) ($payload['notes'] ?? ''));

        if ($callId <= 0 || $templateId <= 0) {
            mh_json_response('error', 'Faltan campos obligatorios (call_id, template_id).', [], 422);
        }

        $callStmt = $pdo->prepare('SELECT id FROM calls WHERE id = :id');
        $callStmt->execute(['id' => $callId]);
        if (!$callStmt->fetch()) {
            mh_json_response('error', 'El llamado no existe.', [], 404);
        }

        $templateStmt = $pdo->prepare('SELECT id, phase, item_label FROM checklist_templates WHERE id = :id');
        $templateStmt->execute(['id' => $templateId]);
        $template = $templateStmt->fetch();
        if (!$template) {
            mh_json_response('error', 'El item de checklist no existe.', [], 404);
        }

        mh_checklist_assert_access($pdo, $currentUser, $callId);

        $stmt = $pdo->prepare(
            'INSERT INTO call_checklist_progress (call_id, template_id, is_checked, checked_by, checked_at, notes)
             VALUES (:call_id, :template_id, :is_checked, :checked_by, :checked_at, :notes)
             ON DUPLICATE KEY UPDATE
                is_checked = :is_checked2, checked_by = :checked_by2, checked_at = :checked_at2, notes = :notes2'
        );
        $checkedBy = $isChecked ? $currentUser['user_id'] : null;
        $checkedAt = $isChecked ? date('Y-m-d H:i:s') : null;
        $notesValue = $notes !== '' ? $notes : null;

        $stmt->execute([
            'call_id'     => $callId,
            'template_id' => $templateId,
            'is_checked'  => $isChecked ? 1 : 0,
            'checked_by'  => $checkedBy,
            'checked_at'  => $checkedAt,
            'notes'       => $notesValue,
            'is_checked2' => $isChecked ? 1 : 0,
            'checked_by2' => $checkedBy,
            'checked_at2' => $checkedAt,
            'notes2'      => $notesValue,
        ]);

        mh_json_response('success', "Checklist actualizado: '{$template['item_label']}'.", [
            'call_id'     => $callId,
            'template_id' => $templateId,
            'is_checked'  => $isChecked,
        ]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/checklist.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
