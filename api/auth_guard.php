<?php
/**
 * MH-CORE: Guardia de acceso al Dashboard + Persistencia "Recuerdame" (Fase 5.1).
 * - Exige sesion activa (login), restaurandola de forma transparente desde
 *   la cookie de "Recuerdame por 60 dias" si no hay sesion viva.
 * - Bloquea el acceso si quedan reglamentos sin firmar, redirigiendo
 *   a legal/firma.php (Estandar Oro - Modulo Legal Integrado).
 *
 * Uso esperado: incluido desde dashboard/index.php (un nivel bajo la raiz)
 * y desde api/login.php (mismo nivel) -- las redirecciones internas de
 * mh_require_auth() son relativas a dashboard/, pero mh_issue_remember_token()
 * y mh_try_restore_session_from_cookie() no redirigen, son seguras desde
 * cualquier profundidad.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/response.php';

const MH_REMEMBER_COOKIE   = 'mh_remember';
const MH_REMEMBER_TTL_DAYS = 60;

/**
 * Opciones de cookie compartidas (emision y borrado). Secure se activa si
 * la peticion ya es HTTPS o si APP_ENV=production (mediahub.tecnidepot.com
 * sirve siempre bajo HTTPS) -- nunca se envia la cookie "Recuerdame" en
 * claro sobre HTTP.
 */
function mh_remember_cookie_options(): array
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || mh_app_env('APP_ENV', '') === 'production';

    return [
        'expires'  => time() + (MH_REMEMBER_TTL_DAYS * 86400),
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ];
}

/**
 * Emite un nuevo token de "Recuerdame" para el usuario: genera 256 bits
 * aleatorios, persiste solo el hash HMAC (mismo esquema que password_resets)
 * y escribe la cookie en el navegador. Se usa tanto en login exitoso como
 * en cada rotacion de restauracion automatica.
 */
function mh_issue_remember_token(PDO $pdo, int $userId): void
{
    $rawToken  = bin2hex(random_bytes(32));
    $tokenHash = hash_hmac('sha256', $rawToken, mh_app_env('CSRF_SECRET', 'mediahub_fallback_secret'));
    $expiresAt = date('Y-m-d H:i:s', time() + (MH_REMEMBER_TTL_DAYS * 86400));

    $pdo->prepare(
        'INSERT INTO user_remember_tokens (user_id, token_hash, user_agent, ip_address, expires_at)
         VALUES (:user_id, :token_hash, :user_agent, :ip_address, :expires_at)'
    )->execute([
        'user_id'    => $userId,
        'token_hash' => $tokenHash,
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'expires_at' => $expiresAt,
    ]);

    setcookie(MH_REMEMBER_COOKIE, $userId . ':' . $rawToken, mh_remember_cookie_options());
}

/**
 * Borra la cookie "Recuerdame" del navegador (no toca la BD -- el llamador
 * es responsable de eliminar el/los registro(s) de user_remember_tokens
 * cuando corresponda, ver logout.php).
 */
function mh_clear_remember_cookie(): void
{
    $options            = mh_remember_cookie_options();
    $options['expires'] = time() - 3600;
    setcookie(MH_REMEMBER_COOKIE, '', $options);
    unset($_COOKIE[MH_REMEMBER_COOKIE]);
}

/**
 * Intenta restaurar la sesion desde la cookie "Recuerdame". Rota el token
 * en cada uso exitoso (borra el consumido, emite uno nuevo) para reducir
 * la ventana de robo de cookie. Devuelve false y limpia rastros invalidos
 * ante cualquier anomalia (token no encontrado, expirado, o cuenta ya no
 * habilitada para acceso automatico).
 */
function mh_try_restore_session_from_cookie(PDO $pdo): bool
{
    if (empty($_COOKIE[MH_REMEMBER_COOKIE])) {
        return false;
    }

    $parts = explode(':', (string) $_COOKIE[MH_REMEMBER_COOKIE], 2);

    if (count($parts) !== 2) {
        mh_clear_remember_cookie();
        return false;
    }

    [$userIdPart, $rawToken] = $parts;
    $userId = (int) $userIdPart;

    if ($userId <= 0 || $rawToken === '') {
        mh_clear_remember_cookie();
        return false;
    }

    $tokenHash = hash_hmac('sha256', $rawToken, mh_app_env('CSRF_SECRET', 'mediahub_fallback_secret'));

    $stmt = $pdo->prepare(
        'SELECT rt.id, rt.expires_at, u.id AS user_id, u.user_id AS user_code,
                u.full_name, u.role, u.email, u.status
         FROM user_remember_tokens rt
         INNER JOIN users u ON u.id = rt.user_id
         WHERE rt.user_id = :user_id AND rt.token_hash = :token_hash
         LIMIT 1'
    );
    $stmt->execute(['user_id' => $userId, 'token_hash' => $tokenHash]);
    $row = $stmt->fetch();

    if (!$row || strtotime($row['expires_at']) < time()) {
        // Token invalido o expirado: limpia cualquier residuo de este usuario.
        $pdo->prepare('DELETE FROM user_remember_tokens WHERE user_id = :user_id')
            ->execute(['user_id' => $userId]);
        mh_clear_remember_cookie();
        return false;
    }

    if (in_array($row['status'], ['Suspendido', 'Troll_Mode', 'Pendiente'], true)) {
        // Cuenta ya no habilitada para acceso automatico -- invalida el token.
        $pdo->prepare('DELETE FROM user_remember_tokens WHERE id = :id')->execute(['id' => $row['id']]);
        mh_clear_remember_cookie();
        return false;
    }

    // Rotacion: borra el token consumido antes de emitir el siguiente.
    $pdo->prepare('DELETE FROM user_remember_tokens WHERE id = :id')->execute(['id' => $row['id']]);

    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $row['user_id'];
    $_SESSION['user_code'] = $row['user_code'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['role']      = $row['role'];
    $_SESSION['email']     = $row['email'];

    mh_issue_remember_token($pdo, (int) $row['user_id']);

    return true;
}

function mh_require_auth(): array
{
    if (empty($_SESSION['user_id'])) {
        $pdo = Database::getInstance()->getConnection();

        if (!mh_try_restore_session_from_cookie($pdo)) {
            header('Location: ../index.php');
            exit;
        }
    } else {
        $pdo = Database::getInstance()->getConnection();
    }

    // Fase 5.8 — Handshake Legal Condicional: filtra por rol via
    // legal_document_roles (ver api/response.php::mh_count_pending_signatures()).
    if (mh_count_pending_signatures($pdo, (int) $_SESSION['user_id'], (string) $_SESSION['role']) > 0) {
        header('Location: ../legal/firma.php');
        exit;
    }

    return [
        'user_id'   => (int) $_SESSION['user_id'],
        'user_code' => $_SESSION['user_code'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'email'     => $_SESSION['email'],
    ];
}
