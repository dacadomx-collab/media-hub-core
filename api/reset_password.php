<?php
/**
 * MH-CORE: Procesador de Recuperacion de Contrasena.
 * - Valida CSRF y "Troll Mode".
 * - Verifica el token HMAC contra `password_resets` (no usado, no expirado).
 * - Actualiza `users.password_hash` y marca el token (y cualquier otro
 *   token pendiente del usuario) como usado.
 *
 * Ubicacion: api/reset_password.php (un nivel bajo la raiz). Todas las
 * redirecciones son relativas a esta carpeta -> ../reset_password.php.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../reset_password.php');
    exit;
}

mh_guard_request($_POST, 'reset_password');

$uid            = (int) ($_POST['uid'] ?? 0);
$token          = (string) ($_POST['token'] ?? '');
$password       = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

$redirectBase = '../reset_password.php?uid=' . $uid . '&token=' . urlencode($token);

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header('Location: ' . $redirectBase . '&error=csrf');
    exit;
}

if ($uid <= 0 || $token === '') {
    header('Location: ' . $redirectBase . '&error=invalid');
    exit;
}

if (strlen($password) < 8 || $password !== $passwordConfirm) {
    header('Location: ' . $redirectBase . '&error=weak');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    $tokenHash = hash_hmac('sha256', $token, mh_app_env('CSRF_SECRET', 'mediahub_fallback_secret'));

    $stmt = $pdo->prepare(
        'SELECT id, expires_at FROM password_resets
         WHERE user_id = :user_id AND token_hash = :token_hash AND used = 0
         ORDER BY id DESC LIMIT 1'
    );
    $stmt->execute(['user_id' => $uid, 'token_hash' => $tokenHash]);
    $resetRow = $stmt->fetch();

    if (!$resetRow) {
        header('Location: ' . $redirectBase . '&error=invalid');
        exit;
    }

    if (strtotime($resetRow['expires_at']) < time()) {
        header('Location: ' . $redirectBase . '&error=expired');
        exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id')
            ->execute([
                'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'id'             => $uid,
            ]);

        // Invalida este token y cualquier otro token pendiente del usuario.
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE user_id = :user_id AND used = 0')
            ->execute(['user_id' => $uid]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    header('Location: ../reset_password.php?success=1');
    exit;
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/reset_password.php: ' . $e->getMessage());
    header('Location: ' . $redirectBase . '&error=server');
    exit;
}
