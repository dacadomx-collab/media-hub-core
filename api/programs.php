<?php
/**
 * MH-CORE: Endpoint de Programas Recurrentes (CRUD + gestion de estados).
 * Ubicacion: api/programs.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?action=list       -> Listado de programas (filtro opcional client_id).
 *   POST action=create      -> Alta de programa (Administrador, Lider_Proyecto).
 *                               Dispara correo "Nuevo Programa" al cliente si tiene email.
 *   PUT  action=update      -> Edicion de nombre/descripcion/cliente (Administrador, Lider_Proyecto).
 *   POST action=deactivate  -> Baja logica -> is_active = 0 (Administrador, Lider_Proyecto).
 *   POST action=activate    -> Reactivacion -> is_active = 1 (Administrador, Lider_Proyecto).
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/mailer.php';

$currentUser = mh_require_session();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

$pdo = Database::getInstance()->getConnection();

try {
    // -----------------------------------------------------------------
    // GET ?action=list — Listado de programas
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $sql    = 'SELECT pr.id, pr.client_id, pr.name, pr.description, pr.is_active, pr.created_at,
                          cl.full_name AS client_name, cl.company AS client_company
                   FROM programs pr
                   INNER JOIN clients cl ON cl.id = pr.client_id';
        $params = [];

        if (!empty($_GET['client_id'])) {
            $sql               .= ' WHERE pr.client_id = :client_id';
            $params['client_id'] = (int) $_GET['client_id'];
        }
        $sql .= ' ORDER BY pr.is_active DESC, pr.name';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        mh_json_response('success', 'Programas cargados.', ['programs' => $stmt->fetchAll()]);
    }

    // -----------------------------------------------------------------
    // POST action=create — Alta de programa
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_create');
        mh_require_csrf($payload);

        $clientId    = (int) ($payload['client_id'] ?? 0);
        $name        = trim((string) ($payload['name'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        if ($clientId <= 0 || $name === '') {
            mh_json_response('error', 'Faltan campos obligatorios (client_id, name).', [], 422);
        }

        $clientStmt = $pdo->prepare('SELECT full_name, email FROM clients WHERE id = :id');
        $clientStmt->execute(['id' => $clientId]);
        $client = $clientStmt->fetch();

        if (!$client) {
            mh_json_response('error', 'El cliente indicado no existe.', [], 404);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO programs (client_id, name, description)
             VALUES (:client_id, :name, :description)'
        );
        $stmt->execute([
            'client_id'   => $clientId,
            'name'        => $name,
            'description' => $description !== '' ? $description : null,
        ]);

        $newId = (int) $pdo->lastInsertId();

        // Notificacion "Nuevo Programa Creado" al Cliente Jornal (best-effort).
        if (!empty($client['email'])) {
            $mail = mh_mail_new_program($client['full_name'], $name, $description);
            mh_send_mail($client['email'], $mail['subject'], $mail['html']);
        }

        mh_json_response('success', 'Programa registrado correctamente.', ['id' => $newId], 201);
    }

    // -----------------------------------------------------------------
    // PUT action=update — Edicion de programa
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'update') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_update');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del programa a editar.', [], 422);
        }

        $fields = [];
        $params = ['id' => $targetId];

        if (isset($payload['name']) && trim((string) $payload['name']) !== '') {
            $fields[]        = 'name = :name';
            $params['name']  = trim((string) $payload['name']);
        }
        if (isset($payload['description'])) {
            $description           = trim((string) $payload['description']);
            $fields[]               = 'description = :description';
            $params['description']  = $description !== '' ? $description : null;
        }
        if (isset($payload['client_id'])) {
            $clientId = (int) $payload['client_id'];
            if ($clientId <= 0) {
                mh_json_response('error', 'client_id invalido.', [], 422);
            }
            $fields[]            = 'client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        if ($fields === []) {
            mh_json_response('error', 'No se proporcionaron campos para actualizar.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE programs SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            mh_json_response('error', 'El programa no existe.', [], 404);
        }

        mh_json_response('success', 'Programa actualizado correctamente.', ['id' => $targetId]);
    }

    // -----------------------------------------------------------------
    // POST action=deactivate — Baja logica
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'deactivate') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_deactivate');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del programa a dar de baja.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE programs SET is_active = 0 WHERE id = :id');
        $stmt->execute(['id' => $targetId]);

        mh_json_response('success', 'Programa dado de baja (is_active = 0).', ['id' => $targetId]);
    }

    // -----------------------------------------------------------------
    // POST action=activate — Reactivacion
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'activate') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'programs_activate');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del programa a reactivar.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE programs SET is_active = 1 WHERE id = :id');
        $stmt->execute(['id' => $targetId]);

        mh_json_response('success', 'Programa reactivado (is_active = 1).', ['id' => $targetId]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/programs.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
