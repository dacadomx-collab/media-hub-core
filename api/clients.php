<?php
/**
 * MH-CORE: Endpoint de Clientes Jornal (CRUD + gestion de estados).
 * Ubicacion: api/clients.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?action=list       -> Listado de clientes (con conteo de programas).
 *   POST action=create      -> Alta de cliente (Administrador, Lider_Proyecto).
 *   PUT  action=update      -> Edicion de datos de contacto (Administrador, Lider_Proyecto).
 *   POST action=deactivate  -> Baja logica -> is_active = 0 (Administrador).
 *   POST action=activate    -> Reactivacion -> is_active = 1 (Administrador).
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
    // GET ?action=list — Listado de clientes Jornal
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $stmt = $pdo->query(
            'SELECT c.id, c.full_name, c.email, c.phone, c.company, c.is_active, c.created_at,
                    (SELECT COUNT(*) FROM programs p WHERE p.client_id = c.id) AS programs_count
             FROM clients c
             ORDER BY c.is_active DESC, c.full_name'
        );

        mh_json_response('success', 'Clientes cargados.', ['clients' => $stmt->fetchAll()]);
    }

    // -----------------------------------------------------------------
    // POST action=create — Alta de cliente
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'clients_create');
        mh_require_csrf($payload);

        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $email    = trim((string) ($payload['email'] ?? ''));
        $phone    = trim((string) ($payload['phone'] ?? ''));
        $company  = trim((string) ($payload['company'] ?? ''));

        if ($fullName === '') {
            mh_json_response('error', 'Falta el campo obligatorio full_name.', [], 422);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            mh_json_response('error', 'El correo electronico no tiene un formato valido.', [], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO clients (full_name, email, phone, company)
             VALUES (:full_name, :email, :phone, :company)'
        );
        $stmt->execute([
            'full_name' => $fullName,
            'email'     => $email !== '' ? $email : null,
            'phone'     => $phone !== '' ? $phone : null,
            'company'   => $company !== '' ? $company : null,
        ]);

        mh_json_response('success', 'Cliente registrado correctamente.', ['id' => (int) $pdo->lastInsertId()], 201);
    }

    // -----------------------------------------------------------------
    // PUT action=update — Edicion de datos de contacto
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'update') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'clients_update');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del cliente a editar.', [], 422);
        }

        $fields = [];
        $params = ['id' => $targetId];

        if (isset($payload['full_name']) && trim((string) $payload['full_name']) !== '') {
            $fields[]            = 'full_name = :full_name';
            $params['full_name'] = trim((string) $payload['full_name']);
        }
        if (isset($payload['email'])) {
            $email = trim((string) $payload['email']);
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                mh_json_response('error', 'El correo electronico no tiene un formato valido.', [], 422);
            }
            $fields[]        = 'email = :email';
            $params['email'] = $email !== '' ? $email : null;
        }
        if (isset($payload['phone'])) {
            $phone           = trim((string) $payload['phone']);
            $fields[]        = 'phone = :phone';
            $params['phone'] = $phone !== '' ? $phone : null;
        }
        if (isset($payload['company'])) {
            $company           = trim((string) $payload['company']);
            $fields[]          = 'company = :company';
            $params['company'] = $company !== '' ? $company : null;
        }

        if ($fields === []) {
            mh_json_response('error', 'No se proporcionaron campos para actualizar.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE clients SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            mh_json_response('error', 'El cliente no existe.', [], 404);
        }

        mh_json_response('success', 'Cliente actualizado correctamente.', ['id' => $targetId]);
    }

    // -----------------------------------------------------------------
    // POST action=deactivate — Baja logica (solo Administrador)
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'deactivate') {
        mh_require_role($currentUser, ['Super_admin', 'Admin']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'clients_deactivate');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del cliente a dar de baja.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE clients SET is_active = 0 WHERE id = :id');
        $stmt->execute(['id' => $targetId]);

        mh_json_response('success', 'Cliente dado de baja (is_active = 0).', ['id' => $targetId]);
    }

    // -----------------------------------------------------------------
    // POST action=activate — Reactivacion (solo Administrador)
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'activate') {
        mh_require_role($currentUser, ['Super_admin', 'Admin']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'clients_activate');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del cliente a reactivar.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE clients SET is_active = 1 WHERE id = :id');
        $stmt->execute(['id' => $targetId]);

        mh_json_response('success', 'Cliente reactivado (is_active = 1).', ['id' => $targetId]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/clients.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
