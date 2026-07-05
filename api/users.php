<?php
/**
 * MH-CORE: Endpoint de Gestion de Usuarios y Organigrama Digital.
 * Ubicacion: api/users.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET  ?action=me     -> Perfil propio + checklist de obligaciones por llamado.
 *   GET  ?action=list   -> Listado del organigrama (Super_admin, Admin, Lider_Proyecto).
 *   POST action=create  -> Alta de staff via ONBOARDING POR CORREO (Fase 5.1/5.3).
 *                          Payload: full_name, email, role. Identidad Email-Only
 *                          (Fase 5.3): ya NO recibe user_id -- internamente
 *                          users.user_id = email. Tampoco recibe password -- el
 *                          usuario nace status='Pendiente' y crea su propia
 *                          contrasena en set_password.php (token de activacion
 *                          en password_resets, Plantilla 1).
 *   PUT  action=update  -> Edicion de perfil/rol/estatus (Super_admin, Admin).
 *   PUT  action=update_self -> Edicion de datos propios (cualquier usuario autenticado).
 *   POST action=deactivate  -> Baja logica -> status = 'Suspendido' (Super_admin, Admin).
 *   POST action=resend_invite -> Reenvia Plantilla 1 a cuenta 'Pendiente'/'Suspendido'/
 *                          'Troll_Mode' existente (Fase 5.7): invalida el token de
 *                          activacion previo, emite uno nuevo, status -> 'Pendiente'.
 *
 * RBAC Fase 5: Super_admin es el unico rol facultado para asignar role='Admin'
 * o 'Super_admin' a otro usuario (create y update). Admin puede asignar
 * cualquier otro rol, pero recibe 403 si intenta asignar Super_admin/Admin.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/user_provisioning.php';

const MH_VALID_ROLES   = ['Super_admin', 'Admin', 'Lider_Proyecto', 'Staff_Tecnico', 'Lider_Logistica', 'Cliente', 'Team', 'Conductor'];
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

        // Contacto opt-in (Fase 5.8): solo relevante para el rol Conductor,
        // pero se incluye siempre en el perfil propio por simplicidad.
        $contactStmt = $pdo->prepare(
            'SELECT whatsapp, show_whatsapp_publicly, show_email_publicly FROM users WHERE id = :id'
        );
        $contactStmt->execute(['id' => $currentUser['user_id']]);
        $contact = $contactStmt->fetch() ?: [];

        mh_json_response('success', 'Perfil y checklist cargados.', [
            'profile'   => array_merge($currentUser, $contact),
            'checklist' => $checklist,
        ]);
    }

    // -----------------------------------------------------------------
    // GET ?action=list — Organigrama completo (solo Administrador / Lider_Proyecto)
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'list') {
        mh_require_role($currentUser, ['Super_admin', 'Admin', 'Lider_Proyecto']);

        $stmt = $pdo->query(
            'SELECT id, user_id, full_name, email, role, status, last_login, created_at
             FROM users ORDER BY role, full_name'
        );

        mh_json_response('success', 'Organigrama cargado.', ['users' => $stmt->fetchAll()]);
    }

    // -----------------------------------------------------------------
    // POST action=create — Alta de staff via ONBOARDING POR CORREO (Fase 5.1)
    // Ya NO recibe password del payload: el usuario nace 'Pendiente' y
    // crea su propia contrasena via set_password.php (Plantilla 1 + 2).
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'create') {
        mh_require_role($currentUser, ['Super_admin', 'Admin']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'users_create');
        mh_require_csrf($payload);

        $fullName = trim((string) ($payload['full_name'] ?? ''));
        $email    = trim((string) ($payload['email'] ?? ''));
        $role     = (string) ($payload['role'] ?? '');

        if ($fullName === '' || $email === '') {
            mh_json_response('error', 'Faltan campos obligatorios (full_name, email).', [], 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            mh_json_response('error', 'El correo electronico no tiene un formato valido.', [], 422);
        }
        if (!in_array($role, MH_VALID_ROLES, true)) {
            mh_json_response('error', 'Rol invalido.', [], 422);
        }

        // Identidad Email-Only (Fase 5.3): el correo ES el identificador
        // unico del organigrama -- ya no se pide un "ID de usuario" manual.
        $userCode = $email;

        // ---------------------------------------------------------------
        // RBAC ESCALADO (Fase 5): solo Super_admin puede crear cuentas
        // Super_admin o Admin. Admin puede crear cualquier otro rol
        // (Team, Conductor, Staff_Tecnico, Lider_Logistica, Lider_Proyecto,
        // Cliente) pero NUNCA Super_admin/Admin — 403 si lo intenta.
        // ---------------------------------------------------------------
        if (in_array($role, ['Super_admin', 'Admin'], true) && $currentUser['role'] !== 'Super_admin') {
            mh_json_response('error', 'Solo Super_admin puede crear cuentas Super_admin o Admin.', [], 403);
        }

        $pdo->beginTransaction();

        try {
            $provision = mh_provision_pending_user($pdo, $userCode, $fullName, $email, $role);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();

            if ((int) $e->errorInfo[1] === 1062) {
                mh_json_response('error', 'El user_id o el correo ya estan registrados.', [], 409);
            }

            throw $e;
        }

        // Plantilla 1 — Invitacion a crear contrasena (best-effort, post-commit).
        mh_dispatch_account_invite($fullName, $email, $role, $provision['activation_url']);

        mh_json_response('success', 'Invitacion enviada. El usuario debe activar su cuenta desde su correo.', ['id' => $provision['id']], 201);
    }

    // -----------------------------------------------------------------
    // PUT action=update — Edicion de perfil/rol/estatus (solo Administrador)
    // -----------------------------------------------------------------
    if ($method === 'PUT' && $action === 'update') {
        mh_require_role($currentUser, ['Super_admin', 'Admin']);

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
            // RBAC ESCALADO (Fase 5): solo Super_admin puede promover a
            // Super_admin o Admin.
            if (in_array($payload['role'], ['Super_admin', 'Admin'], true) && $currentUser['role'] !== 'Super_admin') {
                mh_json_response('error', 'Solo Super_admin puede asignar el rol Super_admin o Admin.', [], 403);
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

        // Contacto opt-in del Conductor (Fase 5.8): el numero de WhatsApp y
        // los toggles de visibilidad publica son parte del perfil propio --
        // cualquier rol puede en teoria enviarlos, pero solo el Conductor
        // los usa desde el Dashboard.
        if (isset($payload['whatsapp'])) {
            $whatsapp = trim((string) $payload['whatsapp']);
            $fields[]            = 'whatsapp = :whatsapp';
            $params['whatsapp']  = $whatsapp !== '' ? $whatsapp : null;
        }
        if (isset($payload['show_whatsapp_publicly'])) {
            $fields[]                        = 'show_whatsapp_publicly = :show_whatsapp_publicly';
            $params['show_whatsapp_publicly'] = !empty($payload['show_whatsapp_publicly']) ? 1 : 0;
        }
        if (isset($payload['show_email_publicly'])) {
            $fields[]                     = 'show_email_publicly = :show_email_publicly';
            $params['show_email_publicly'] = !empty($payload['show_email_publicly']) ? 1 : 0;
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
        mh_require_role($currentUser, ['Super_admin', 'Admin']);

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

    // -----------------------------------------------------------------
    // POST action=resend_invite — Reenvia Plantilla 1 a una cuenta
    // 'Pendiente'/'Suspendido' existente (Fase 5.7). NO aplica a 'Activo'
    // (para esos, usar action=deactivate + "Resetear Contrasena" en su lugar,
    // ver Contrato 8 api/forgot_password.php).
    // -----------------------------------------------------------------
    if ($method === 'POST' && $action === 'resend_invite') {
        mh_require_role($currentUser, ['Super_admin', 'Admin']);

        $payload = mh_read_json_body();
        mh_guard_request($payload, 'users_resend_invite');
        mh_require_csrf($payload);

        $targetId = (int) ($payload['id'] ?? 0);
        if ($targetId <= 0) {
            mh_json_response('error', 'Falta el id del usuario.', [], 422);
        }

        $userStmt = $pdo->prepare('SELECT full_name, email, role, status FROM users WHERE id = :id');
        $userStmt->execute(['id' => $targetId]);
        $targetUser = $userStmt->fetch();

        if (!$targetUser) {
            mh_json_response('error', 'El usuario no existe.', [], 404);
        }
        if (!in_array($targetUser['status'], ['Pendiente', 'Suspendido', 'Troll_Mode'], true)) {
            mh_json_response('error', 'Solo se puede reenviar invitacion a cuentas Pendiente, Suspendido o Troll_Mode.', [], 422);
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE users SET status = 'Pendiente' WHERE id = :id")->execute(['id' => $targetId]);
            $activationUrl = mh_issue_activation_token($pdo, $targetId);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }

        mh_dispatch_account_invite($targetUser['full_name'], $targetUser['email'], $targetUser['role'], $activationUrl);

        mh_json_response('success', 'Invitacion reenviada. El usuario debe revisar su correo.', ['id' => $targetId]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/users.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
