<?php
/**
 * MH-CORE: Solicitud de Recuperacion de Contrasena.
 * - Valida CSRF y "Troll Mode".
 * - Genera un token aleatorio de un solo uso, lo firma con HMAC
 *   (CSRF_SECRET) y guarda solo el hash en `password_resets` con
 *   expiracion de 1 hora.
 * - Envia el enlace de recuperacion por correo (best-effort, no revela
 *   si el correo existe o no en el sistema).
 *
 * Ubicacion: api/forgot_password.php (un nivel bajo la raiz). Todas las
 * redirecciones son relativas a esta carpeta.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/mailer.php';

const MH_RESET_TOKEN_TTL_SECONDS = 3600; // 1 hora

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

mh_guard_request($_POST, 'forgot_password');

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header('Location: ../index.php?error=csrf');
    exit;
}

$email = trim((string) ($_POST['email'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../index.php?error=invalid');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    $stmt = $pdo->prepare('SELECT id, full_name, email, status FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    // Por seguridad, no revelamos si el correo existe: siempre se redirige
    // al mismo mensaje de exito, pero solo se genera/envia el token si el
    // usuario existe y su cuenta esta Activa.
    if ($user && $user['status'] === 'Activo') {
        $tokenRaw  = bin2hex(random_bytes(32));
        $tokenHash = hash_hmac('sha256', $tokenRaw, mh_app_env('CSRF_SECRET', 'mediahub_fallback_secret'));
        $expiresAt = date('Y-m-d H:i:s', time() + MH_RESET_TOKEN_TTL_SECONDS);

        $pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
        )->execute([
            'user_id'    => $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);

        $appUrl   = rtrim(mh_app_env('APP_URL', ''), '/');
        $query    = 'uid=' . $user['id'] . '&token=' . $tokenRaw;
        $resetUrl = $appUrl !== '' ? $appUrl . '/reset_password.php?' . $query : 'reset_password.php?' . $query;

        $mail = mh_mail_password_reset($user['full_name'], $resetUrl);
        mh_send_mail($user['email'], $mail['subject'], $mail['html']);
    }

    header('Location: ../index.php?info=reset_sent');
    exit;
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/forgot_password.php: ' . $e->getMessage());
    header('Location: ../index.php?error=server');
    exit;
}
