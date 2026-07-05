<?php
/**
 * MH-CORE: Helpers comunes para endpoints JSON de la Capa API.
 * Estandariza el contrato de respuesta { status, message, data } y los
 * controles de sesion/rol compartidos por users.php, agenda.php e
 * inventory.php.
 */

/**
 * Cuenta las firmas legales PENDIENTES de un usuario, filtrando SOLO los
 * documentos que `legal_document_roles` exige para su rol (Fase 5.8 —
 * Handshake Legal Condicional). Antes de esto, el gate exigia los 4
 * reglamentos a TODOS los roles por igual -- un Conductor (talento
 * externo) no debe firmar contratos laborales internos de staff.
 *
 * Usado por api/login.php y api/auth_guard.php (la unica fuente de
 * verdad del gate — ambos deben usar exactamente esta funcion, nunca un
 * COUNT(*) plano sobre user_legal_signatures, para no divergir).
 */
function mh_count_pending_signatures(PDO $pdo, int $userId, string $role): int
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS pending
         FROM user_legal_signatures uls
         INNER JOIN legal_document_roles ldr ON ldr.document_id = uls.document_id AND ldr.role = :role
         WHERE uls.user_id = :user_id AND uls.signed = 0'
    );
    $stmt->execute(['user_id' => $userId, 'role' => $role]);

    return (int) $stmt->fetch()['pending'];
}

/**
 * Lee una clave puntual del .env raiz (sin exponer el archivo completo).
 * Usada por flujos que necesitan un secreto/config especifico
 * (ej. CSRF_SECRET para tokens HMAC de recuperacion de contrasena).
 */
function mh_app_env(string $key, string $default = ''): string
{
    $env = Database::loadEnv();

    $value = $env[$key] ?? '';

    return $value !== '' ? (string) $value : $default;
}

/**
 * Detecta la URL base real de la aplicacion (Fase 5.5) a partir de la
 * peticion HTTP actual, en vez de depender de un `APP_URL` estatico en
 * `.env` que puede quedar desactualizado tras un cambio de dominio/entorno
 * (causa confirmada de 404 en los enlaces de las plantillas de correo).
 *
 * Soporta tanto un despliegue en la raiz del dominio (produccion,
 * `https://mediahub.tecnidepot.com`) como en subcarpeta (desarrollo local
 * XAMPP, `http://localhost/MediaHUB`), derivando la carpeta base desde el
 * script que esta ejecutandose (todos los llamadores viven en `api/`).
 *
 * Fuera de un contexto HTTP (CLI, cron) no hay `$_SERVER['HTTP_HOST']` --
 * degrada al `APP_URL` de `.env` como ultimo recurso.
 */
function mh_detect_base_url(): string
{
    if (empty($_SERVER['HTTP_HOST'])) {
        return rtrim(mh_app_env('APP_URL', ''), '/');
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? '') === '443'
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    $scheme    = $isHttps ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'];
    $scriptDir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
    $basePath  = rtrim(preg_replace('#/api$#', '', $scriptDir), '/');

    return $scheme . '://' . $host . $basePath;
}

/**
 * Responde en JSON con el contrato estandar y termina la ejecucion.
 */
function mh_json_response(string $status, string $message, array $data = [], int $httpCode = 200): never
{
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Exige sesion activa. Responde 401 JSON (sin redirect) si no hay sesion,
 * ya que estos endpoints son consumidos vía fetch/AJAX por el Dashboard.
 */
function mh_require_session(): array
{
    if (empty($_SESSION['user_id'])) {
        mh_json_response('error', 'No autenticado. Inicia sesion nuevamente.', [], 401);
    }

    return [
        'user_id'   => (int) $_SESSION['user_id'],
        'user_code' => $_SESSION['user_code'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
    ];
}

/**
 * Exige que el rol de sesion pertenezca a la lista de roles permitidos.
 */
function mh_require_role(array $user, array $allowedRoles): void
{
    if (!in_array($user['role'], $allowedRoles, true)) {
        mh_json_response('error', 'No tienes permisos suficientes para esta operacion.', [], 403);
    }
}

/**
 * Lee y decodifica el cuerpo JSON de la peticion. Responde 400 si el
 * cuerpo no es un JSON valido (objeto/array).
 */
function mh_read_json_body(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === '' || $raw === false) {
        return [];
    }

    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        mh_json_response('error', 'Cuerpo JSON invalido.', [], 400);
    }

    return $decoded;
}

/**
 * Valida el token CSRF enviado en el payload JSON o en el header
 * X-CSRF-Token para peticiones que mutan estado (POST/PUT/DELETE).
 */
function mh_require_csrf(array $payload): void
{
    $token = $payload['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

    if (!csrf_validate($token)) {
        mh_json_response('error', 'Token CSRF invalido o sesion expirada.', [], 403);
    }
}
