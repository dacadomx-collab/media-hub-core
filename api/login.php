<?php
/**
 * MH-CORE: Procesador de Login.
 * - Valida CSRF.
 * - Activa "Troll Mode" ante patrones de inyeccion.
 * - Verifica credenciales (Bcrypt) y estado de cuenta.
 * - Aplica bloqueo progresivo por intentos fallidos.
 * - Si el usuario tiene reglamentos sin firmar, fuerza redireccion a
 *   legal/firma.php antes de permitir el acceso al Dashboard.
 *
 * Ubicacion: api/login.php (un nivel bajo la raiz). Todas las
 * redirecciones son relativas a esta carpeta.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';

const MH_MAX_FAILED_ATTEMPTS = 5;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

mh_guard_request($_POST, 'login');

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header('Location: ../index.php?error=csrf');
    exit;
}

$email    = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../index.php?error=invalid');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    $stmt = $pdo->prepare(
        'SELECT id, user_id, full_name, email, password_hash, role, status, failed_attempts
         FROM users WHERE email = :email LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        header('Location: ../index.php?error=credentials');
        exit;
    }

    if ($user['status'] === 'Troll_Mode') {
        mh_log_security_event('Intento de login en cuenta bajo Troll Mode', ['user_id' => $user['user_id']]);
        mh_troll_redirect();
    }

    if ($user['status'] === 'Suspendido') {
        header('Location: ../index.php?error=suspended');
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = (int) $user['failed_attempts'] + 1;

        mh_log_security_event('Credenciales invalidas', ['user_id' => $user['user_id']]);
        mh_troll_escalate();

        if ($attempts >= MH_MAX_FAILED_ATTEMPTS) {
            $update = $pdo->prepare(
                "UPDATE users SET failed_attempts = :attempts, status = 'Troll_Mode' WHERE id = :id"
            );
            $update->execute(['attempts' => $attempts, 'id' => $user['id']]);

            mh_log_security_event('Limite de intentos fallidos alcanzado - cuenta movida a Troll_Mode', [
                'user_id' => $user['user_id'],
            ]);
            mh_troll_redirect();
        }

        $update = $pdo->prepare('UPDATE users SET failed_attempts = :attempts WHERE id = :id');
        $update->execute(['attempts' => $attempts, 'id' => $user['id']]);

        header('Location: ../index.php?error=credentials');
        exit;
    }

    // Login exitoso: reinicia contador, actualiza telemetria de acceso.
    $update = $pdo->prepare(
        "UPDATE users SET failed_attempts = 0, last_login = NOW() WHERE id = :id"
    );
    $update->execute(['id' => $user['id']]);

    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['user_code']  = $user['user_id'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['email']      = $user['email'];

    // Verifica banderas de firma legal (Estandar Oro - Modulo Legal Integrado).
    $pending = $pdo->prepare(
        'SELECT COUNT(*) AS pending
         FROM user_legal_signatures
         WHERE user_id = :user_id AND signed = 0'
    );
    $pending->execute(['user_id' => $user['id']]);
    $pendingCount = (int) $pending->fetch()['pending'];

    if ($pendingCount > 0) {
        header('Location: ../legal/firma.php');
        exit;
    }

    header('Location: ../dashboard/index.php');
    exit;
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/login.php: ' . $e->getMessage());
    header('Location: ../index.php?error=server');
    exit;
} catch (RuntimeException $e) {
    error_log('MH-CORE config error en api/login.php: ' . $e->getMessage());
    header('Location: ../index.php?error=server');
    exit;
}
