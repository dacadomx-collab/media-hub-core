<?php
/**
 * MH-CORE: Endpoint de Inventarios Dinamicos y Flota (Check-In/Check-Out).
 * Ubicacion: api/inventory.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?action=list         -> Listado de inventory_items + fleet_vehicles.
 *   GET  ?action=log          -> Bitacora de Check-In/Check-Out (filtros: asset_type, asset_id).
 *   POST action=checkout      -> 'Disponible' -> 'En Uso' + firma digital en bitacora.
 *   POST action=checkin       -> 'En Uso' -> 'Disponible' (o 'Mantenimiento' si damaged=true).
 *   POST action=set_maintenance -> Marca el activo como 'Mantenimiento' (Administrador / Lider_Proyecto).
 *
 * Nota sobre "firma digital": cada movimiento queda registrado de forma
 * append-only en checkinout_log con el user_id de la sesion autenticada,
 * IP y timestamp -- esto constituye la firma/bitacora que deslinda
 * responsabilidades por desgaste o dano del equipo.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';

const MH_VALID_ASSET_TYPES = ['Inventario', 'Vehiculo'];
const MH_VALID_ASSET_STATUS = ['Disponible', 'En Uso', 'Mantenimiento'];

$currentUser = mh_require_session();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

$pdo = Database::getInstance()->getConnection();

/**
 * Resuelve la tabla fisica y el estatus actual de un activo.
 * Devuelve null si el activo no existe.
 */
function mh_inventory_fetch_asset(PDO $pdo, string $assetType, int $assetId): ?array
{
    $table = $assetType === 'Inventario' ? 'inventory_items' : 'fleet_vehicles';

    $stmt = $pdo->prepare("SELECT id, name, status FROM {$table} WHERE id = :id");
    $stmt->execute(['id' => $assetId]);
    $asset = $stmt->fetch();

    return $asset ?: null;
}

try {
    // -----------------------------------------------------------------
    // GET ?action=list — Listado de inventario y flota
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list') {
        $items   = $pdo->query(
            'SELECT id, name, category, serial_number, status, notes, created_at
             FROM inventory_items ORDER BY category, name'
        )->fetchAll();

        $vehicles = $pdo->query(
            'SELECT id, name, type, registration, status, notes, created_at
             FROM fleet_vehicles ORDER BY type, name'
        )->fetchAll();

        mh_json_response('success', 'Inventario y flota cargados.', [
            'inventory_items' => $items,
            'fleet_vehicles'  => $vehicles,
        ]);
    }

    // -----------------------------------------------------------------
    // GET ?action=log — Bitacora de Check-In/Check-Out
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'log') {
        $where  = [];
        $params = [];

        if (!empty($_GET['asset_type']) && in_array($_GET['asset_type'], MH_VALID_ASSET_TYPES, true)) {
            $where[]              = 'l.asset_type = :asset_type';
            $params['asset_type'] = (string) $_GET['asset_type'];
        }
        if (!empty($_GET['asset_id'])) {
            $where[]            = 'l.asset_id = :asset_id';
            $params['asset_id'] = (int) $_GET['asset_id'];
        }

        $sql = 'SELECT l.id, l.asset_type, l.asset_id, l.call_id, l.action, l.condition_notes, l.logged_at,
                       u.full_name AS staff_name, u.role AS staff_role
                FROM checkinout_log l
                INNER JOIN users u ON u.id = l.user_id';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY l.logged_at DESC LIMIT 200';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        mh_json_response('success', 'Bitacora cargada.', ['log' => $stmt->fetchAll()]);
    }

    // -----------------------------------------------------------------
    // POST action=checkout — 'Disponible' -> 'En Uso'
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'checkout') {
        $payload = mh_read_json_body();
        mh_guard_request($payload, 'inventory_checkout');
        mh_require_csrf($payload);

        $assetType = (string) ($payload['asset_type'] ?? '');
        $assetId   = (int) ($payload['asset_id'] ?? 0);
        $callId    = isset($payload['call_id']) && $payload['call_id'] !== '' ? (int) $payload['call_id'] : null;
        $notes     = trim((string) ($payload['condition_notes'] ?? ''));

        if (!in_array($assetType, MH_VALID_ASSET_TYPES, true) || $assetId <= 0) {
            mh_json_response('error', 'Faltan campos obligatorios (asset_type, asset_id).', [], 422);
        }

        $asset = mh_inventory_fetch_asset($pdo, $assetType, $assetId);
        if (!$asset) {
            mh_json_response('error', 'El activo no existe.', [], 404);
        }
        if ($asset['status'] !== 'Disponible') {
            mh_json_response('error', "El activo '{$asset['name']}' no esta Disponible (estatus actual: {$asset['status']}).", [], 409);
        }

        // Si se asocia a un llamado, solo el staff asignado o un
        // Lider_Proyecto/Administrador pueden realizar el check-out.
        if ($callId !== null && !in_array($currentUser['role'], ['Super_admin', 'Admin', 'Lider_Proyecto'], true)) {
            $assignStmt = $pdo->prepare(
                'SELECT COUNT(*) AS total FROM call_assignments WHERE call_id = :call_id AND user_id = :user_id'
            );
            $assignStmt->execute(['call_id' => $callId, 'user_id' => $currentUser['user_id']]);

            if ((int) $assignStmt->fetch()['total'] === 0) {
                mh_json_response('error', 'No estas asignado a este llamado, no puedes registrar el check-out.', [], 403);
            }
        }

        $table = $assetType === 'Inventario' ? 'inventory_items' : 'fleet_vehicles';

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE {$table} SET status = 'En Uso' WHERE id = :id")
                ->execute(['id' => $assetId]);

            $pdo->prepare(
                "INSERT INTO checkinout_log (asset_type, asset_id, call_id, user_id, action, condition_notes)
                 VALUES (:asset_type, :asset_id, :call_id, :user_id, 'Check-Out', :condition_notes)"
            )->execute([
                'asset_type'      => $assetType,
                'asset_id'        => $assetId,
                'call_id'         => $callId,
                'user_id'         => $currentUser['user_id'],
                'condition_notes' => $notes !== '' ? $notes : null,
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

        mh_json_response('success', "Check-Out registrado. '{$asset['name']}' ahora esta En Uso.", [
            'asset_id' => $assetId,
            'status'   => 'En Uso',
        ]);
    }

    // -----------------------------------------------------------------
    // POST action=checkin — 'En Uso' -> 'Disponible' (o 'Mantenimiento')
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'checkin') {
        $payload = mh_read_json_body();
        mh_guard_request($payload, 'inventory_checkin');
        mh_require_csrf($payload);

        $assetType = (string) ($payload['asset_type'] ?? '');
        $assetId   = (int) ($payload['asset_id'] ?? 0);
        $callId    = isset($payload['call_id']) && $payload['call_id'] !== '' ? (int) $payload['call_id'] : null;
        $notes     = trim((string) ($payload['condition_notes'] ?? ''));
        $damaged   = (bool) ($payload['damaged'] ?? false);

        if (!in_array($assetType, MH_VALID_ASSET_TYPES, true) || $assetId <= 0) {
            mh_json_response('error', 'Faltan campos obligatorios (asset_type, asset_id).', [], 422);
        }

        $asset = mh_inventory_fetch_asset($pdo, $assetType, $assetId);
        if (!$asset) {
            mh_json_response('error', 'El activo no existe.', [], 404);
        }
        if ($asset['status'] !== 'En Uso') {
            mh_json_response('error', "El activo '{$asset['name']}' no esta En Uso (estatus actual: {$asset['status']}).", [], 409);
        }

        if ($damaged && $notes === '') {
            mh_json_response('error', 'Si reportas dano, debes incluir condition_notes con el detalle.', [], 422);
        }

        $newStatus = $damaged ? 'Mantenimiento' : 'Disponible';
        $table     = $assetType === 'Inventario' ? 'inventory_items' : 'fleet_vehicles';

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE {$table} SET status = :status WHERE id = :id")
                ->execute(['status' => $newStatus, 'id' => $assetId]);

            $pdo->prepare(
                "INSERT INTO checkinout_log (asset_type, asset_id, call_id, user_id, action, condition_notes)
                 VALUES (:asset_type, :asset_id, :call_id, :user_id, 'Check-In', :condition_notes)"
            )->execute([
                'asset_type'      => $assetType,
                'asset_id'        => $assetId,
                'call_id'         => $callId,
                'user_id'         => $currentUser['user_id'],
                'condition_notes' => $notes !== '' ? $notes : null,
            ]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

        mh_json_response('success', "Check-In registrado. '{$asset['name']}' ahora esta {$newStatus}.", [
            'asset_id' => $assetId,
            'status'   => $newStatus,
        ]);
    }

    // -----------------------------------------------------------------
    // POST action=set_maintenance — Marca el activo como 'Mantenimiento'
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'set_maintenance') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'inventory_set_maintenance');
        mh_require_csrf($payload);

        $assetType = (string) ($payload['asset_type'] ?? '');
        $assetId   = (int) ($payload['asset_id'] ?? 0);
        $notes     = trim((string) ($payload['notes'] ?? ''));

        if (!in_array($assetType, MH_VALID_ASSET_TYPES, true) || $assetId <= 0) {
            mh_json_response('error', 'Faltan campos obligatorios (asset_type, asset_id).', [], 422);
        }

        $asset = mh_inventory_fetch_asset($pdo, $assetType, $assetId);
        if (!$asset) {
            mh_json_response('error', 'El activo no existe.', [], 404);
        }

        $table = $assetType === 'Inventario' ? 'inventory_items' : 'fleet_vehicles';

        $stmt = $pdo->prepare("UPDATE {$table} SET status = 'Mantenimiento', notes = :notes WHERE id = :id");
        $stmt->execute([
            'notes' => $notes !== '' ? $notes : null,
            'id'    => $assetId,
        ]);

        mh_json_response('success', "'{$asset['name']}' marcado como Mantenimiento.", [
            'asset_id' => $assetId,
            'status'   => 'Mantenimiento',
        ]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/inventory.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
