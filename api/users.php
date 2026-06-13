<?php
/**
 * MH-CORE: Endpoint de Gestion de Usuarios y Organigrama Digital.
 * Ubicacion: api/users.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?action=me     -> Perfil propio + checklist de obligaciones por llamado.
 *   GET  ?action=list   -> Listado del organigrama (Administrador, Lider_Proyecto).
 *   POST action=create  -> Alta de staff (Administrador).
 *   PUT  action=update  -> Edicion de perfil/rol/estatus (Administrador).
 *   PUT  action=update_self -> Edicion de datos propios (cualquier usuario autenticado).
 *   POST action=deactivate  -> Baja logica -> status = 'Suspendido' (Administrador).
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/mailer.php';

const MH_VALID_ROLES   = ['Administrador', 'Lider_Proyecto', 'Staff_Tecnico', 'Lider_Logistica', 'Cliente'];
const MH_VALID_STATUS  = ['Activo', 'Suspendido', 'Troll_Mode'];

$currentUser = mh_require_session();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

$pdo = Database::getInstance()->getConnection();

try {
    // -----------------------------------------------------------------
    // GET ?action=me — Perfil propio + checklist de obligaciones por llamado
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'me') {
        $stmt = $pdo->prepare(
            'SELECT ca.id AS assignment_id, ca.task_description, ca.status AS assignment_status,
                    c.id AS call_id, c.title, c.location, c.call_date, c.start_time, c.end_time
             FROM call_assignments ca
             INNER JOIN calls c ON c.id = ca.call_id
             WHERE ca.user_id = :user_id AND ca.status != "Completado"
             ORDER BY c.call_date ASC, c.start_time ASC'
        );
        $stmt->execute(['user_id' => $currentUser['user_id']]);
        $checklist = $stmt->fetchAll();

        // Asociar el checklist obligatorio del llamado a la sesion activa.
        $_SESSION['checklist'] = $checklist;

        mh_json_response('success', 'Perfil y checklist cargados.', [
            'profile'   => $currentUser,
            'checklist' => $checklist,
        ]);
    }

    // -----------------------------------------------------------------
    // GET ?action=list — Organigrama completo (solo Administrador / Lider_Proyecto)
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list') {
        mh_require_role($currentUser, ['Administrador', 'Lider_Proyecto']);

        $stmt = $pdo->query(
            'SELECT id, user_id, full_name, email, role, status, last_login, created_at
             FROM users ORDER BY role, full_name'
        );

        mh_json_response('success', 'Organigrama cargado.', ['users' => $stmt->fetchAll()]);
    }

    // -----------------------------------------------------------------
    // POST action=create — Alta de staff (solo Administrador)
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create') {
        mh_require_role($currentUser, ['Administrador']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'users_create');
        mh_require_csrf($payload);

        $userCode = trim((string) ($payload['user_id'] ?? ''));
        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $email    = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $role     = (string) ($payload['role'] ?? '');

        if ($userCode === '' || $fullName === '' || $email === '' || $password === '') {
            mh_json_response('error', 'Faltan campos obligatorios (user_id, full_name, email, password).', [], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            mh_json_response('error', 'El correo electronico no tiene un formato valido.', [], 422);
        }
        if (strlen($password) < 8) {
            mh_json_response('error', 'La contrasena debe tener al menos 8 caracteres.', [], 422);
        }
        if (!in_array($role, MH_VALID_ROLES, true)) {
            mh_json_response('error', 'Rol invalido.', [], 422);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (user_id, full_name, email, password_hash, role)
                 VALUES (:user_id, :full_name, :email, :password_hash, :role)'
            );
            $stmt->execute([
                'user_id'       => $userCode,
                'full_name'     => $fullName,
                'email'         => $email,
                'password_hash' => $passwordHash,
                'role'          => $role,
            ]);

            $newId = (int) $pdo->lastInsertId();

            // Inicializa la matriz de firmas legales pendientes (Estandar Oro).
            $pdo->prepare(
                'INSERT INTO user_legal_signatures (user_id, document_id, signed)
                 SELECT :new_user_id, id, 0 FROM legal_documents'
            )->execute(['new_user_id' => $newId]);

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();

            if ((int) $e->errorInfo[1] === 1062) {
                mh_json_response('error', 'El user_id o el correo ya estan registrados.', [], 409);
            }

            throw $e;
        }

        // Notificacion de bienvenida con credenciales + ID unico (best-effort).
        $mail = mh_mail_welcome($fullName, $userCode, $email, $password);
        mh_send_mail($email, $mail['subject'], $mail['html']);

        mh_json_response('success', 'Staff registrado correctamente.', ['id' => $newId], 201);
    }

    // -----------------------------------------------------------------
    // PUT action=update — Edicion de perfil/rol/estatus (solo Administrador)
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'update') {
        mh_require_role($currentUser, ['Administrador']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'users_update');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del usuario a editar.', [], 422);
        }

        $fields = [];
        $params = ['id' => $targetId];

        if (isset($payload['full_name']) && trim((string) $payload['full_name']) !== '') {
            $fields[]            = 'full_name = :full_name';
            $params['full_name'] = trim((string) $payload['full_name']);
        }
        if (isset($payload['email'])) {
            $email = trim((string) $payload['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                mh_json_response('error', 'El correo electronico no tiene un formato valido.', [], 422);
            }
            $fields[]        = 'email = :email';
            $params['email'] = $email;
        }
        if (isset($payload['role'])) {
            if (!in_array($payload['role'], MH_VALID_ROLES, true)) {
                mh_json_response('error', 'Rol invalido.', [], 422);
            }
            $fields[]       = 'role = :role';
            $params['role'] = $payload['role'];
        }
        if (isset($payload['status'])) {
            if (!in_array($payload['status'], MH_VALID_STATUS, true)) {
                mh_json_response('error', 'Estatus invalido.', [], 422);
            }
            $fields[]         = 'status = :status';
            $params['status'] = $payload['status'];

            if ($payload['status'] === 'Activo') {
                $fields[] = 'failed_attempts = 0';
            }
        }
        if (isset($payload['password']) && $payload['password'] !== '') {
            if (strlen((string) $payload['password']) < 8) {
                mh_json_response('error', 'La contrasena debe tener al menos 8 caracteres.', [], 422);
            }
            $fields[]            = 'password_hash = :password_hash';
            $params['password_hash'] = password_hash((string) $payload['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if ($fields === []) {
            mh_json_response('error', 'No se proporcionaron campos para actualizar.', [], 422);
        }

        try {
            $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
            $stmt->execute($params);
        } catch (PDOException $e) {
            if ((int) $e->errorInfo[1] === 1062) {
                mh_json_response('error', 'El correo ya esta en uso por otro usuario.', [], 409);
            }
            throw $e;
        }

        mh_json_response('success', 'Usuario actualizado correctamente.', ['id' => $targetId]);
    }

    // -----------------------------------------------------------------
    // PUT action=update_self — Edicion de datos propios (cualquier rol)
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'update_self') {
        $payload = mh_read_json_body();
        mh_guard_request($payload, 'users_update_self');
        mh_require_csrf($payload);

        $fields = [];
        $params = ['id' => $currentUser['user_id']];

        if (isset($payload['full_name']) && trim((string) $payload['full_name']) !== '') {
            $fields[]            = 'full_name = :full_name';
            $params['full_name'] = trim((string) $payload['full_name']);
        }
        if (isset($payload['password']) && $payload['password'] !== '') {
            if (strlen((string) $payload['password']) < 8) {
                mh_json_response('error', 'La contrasena debe tener al menos 8 caracteres.', [], 422);
            }
            $fields[]                 = 'password_hash = :password_hash';
            $params['password_hash']  = password_hash((string) $payload['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if ($fields === []) {
            mh_json_response('error', 'No se proporcionaron campos para actualizar.', [], 422);
        }

        $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);

        if (isset($params['full_name'])) {
            $_SESSION['full_name'] = $params['full_name'];
        }

        mh_json_response('success', 'Perfil actualizado correctamente.');
    }

    // -----------------------------------------------------------------
    // POST action=deactivate — Baja logica (solo Administrador)
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'deactivate') {
        mh_require_role($currentUser, ['Administrador']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'users_deactivate');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del usuario a dar de baja.', [], 422);
        }
        if ($targetId === $currentUser['user_id']) {
            mh_json_response('error', 'No puedes dar de baja tu propia cuenta.', [], 422);
        }

        $stmt = $pdo->prepare("UPDATE users SET status = 'Suspendido' WHERE id = :id");
        $stmt->execute(['id' => $targetId]);

        mh_json_response('success', 'Usuario dado de baja (status = Suspendido).', ['id' => $targetId]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/users.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
