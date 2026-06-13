<?php
/**
 * MH-CORE: Helpers comunes para endpoints JSON de la Capa API.
 * Estandariza el contrato de respuesta { status, message, data } y los
 * controles de sesion/rol compartidos por users.php, agenda.php e
 * inventory.php.
 */

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
