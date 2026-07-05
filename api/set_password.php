<?php
/**
 * MH-CORE: Procesador de Creacion de Contrasena (Onboarding por correo, Paso 2).
 * - Valida CSRF y "Troll Mode".
 * - Verifica el token HMAC contra `password_resets` (purpose='activation',
 *   no usado, no expirado) -- mismo mecanismo que api/reset_password.php.
 * - Actualiza `users.password_hash` + `users.status = 'Activo'`.
 * - Dispara la Plantilla 2 (Bienvenida de Acceso) best-effort.
 *
 * Ubicacion: api/set_password.php (un nivel bajo la raiz). Todas las
 * redirecciones son relativas a esta carpeta -> ../set_password.php.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../set_password.php');
    exit;
}

mh_guard_request($_POST, 'set_password');

$uid             = (int) ($_POST['uid'] ?? 0);
$token           = (string) ($_POST['token'] ?? '');
$password        = (string) ($_POST['password'] ?? '');
$passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

$redirectBase = '../set_password.php?uid=' . $uid . '&token=' . urlencode($token);

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header('Location: ' . $redirectBase . '&error=csrf');
    exit;
}

if ($uid <= 0 || $token === '') {
    header('Location: ' . $redirectBase . '&error=invalid');
    exit;
}

// Misma politica de fortaleza validada en cliente (assets/js/set-password.js):
// minimo 8 caracteres, mayuscula, minuscula, numero y caracter especial.
$isStrong = strlen($password) >= 8
    && preg_match('/[A-Z]/', $password)
    && preg_match('/[a-z]/', $password)
    && preg_match('/[0-9]/', $password)
    && preg_match('/[^A-Za-z0-9]/', $password);

if (!$isStrong || $password !== $passwordConfirm) {
    header('Location: ' . $redirectBase . '&error=weak');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    $tokenHash = hash_hmac('sha256', $token, mh_app_env('CSRF_SECRET', 'mediahub_fallback_secret'));

    $stmt = $pdo->prepare(
        "SELECT id, expires_at FROM password_resets
         WHERE user_id = :user_id AND token_hash = :token_hash
           AND purpose = 'activation' AND used = 0
         ORDER BY id DESC LIMIT 1"
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

    // Fix de seguridad (Fase 5.6): un token de activacion valido NO debe
    // poder reactivar una cuenta que un Administrador suspendio o que
    // Troll Mode bloqueo despues de emitido el token. Sin esto, activar
    // la cuenta pisaba silenciosamente la suspension.
    $statusStmt = $pdo->prepare('SELECT status FROM users WHERE id = :id');
    $statusStmt->execute(['id' => $uid]);
    $currentStatus = $statusStmt->fetch()['status'] ?? null;

    if (in_array($currentStatus, ['Suspendido', 'Troll_Mode'], true)) {
        header('Location: ' . $redirectBase . '&error=suspended');
        exit;
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare(
            "UPDATE users SET password_hash = :password_hash, status = 'Activo' WHERE id = :id"
        )->execute([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'id'            => $uid,
        ]);

        // Invalida este token y cualquier otro token de activacion pendiente.
        $pdo->prepare(
            "UPDATE password_resets SET used = 1
             WHERE user_id = :user_id AND purpose = 'activation' AND used = 0"
        )->execute(['user_id' => $uid]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Plantilla 2 — Bienvenida de Acceso (best-effort, no bloquea la respuesta).
    $userStmt = $pdo->prepare('SELECT full_name, email FROM users WHERE id = :id');
    $userStmt->execute(['id' => $uid]);
    $user = $userStmt->fetch();

    if ($user) {
        $mail = mh_mail_account_activated($user['full_name'], $user['email']);
        mh_send_mail($user['email'], $mail['subject'], $mail['html']);
    }

    header('Location: ../set_password.php?success=1');
    exit;
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/set_password.php: ' . $e->getMessage());
    header('Location: ' . $redirectBase . '&error=server');
    exit;
}
