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
require_once __DIR__ . '/response.php';

const MH_MAX_FAILED_ATTEMPTS = 5;

/**
 * Puente de diagnostico forense seguro.
 * Si MH_DEBUG_TOKEN esta configurado en .env y coincide con ?debug_token=
 * de la URL, imprime el mensaje crudo de la excepcion y su origen en lugar
 * de la redireccion ciega a ?error=server. Permite confirmar en caliente en
 * GreenGeeks si el fallo es .env ausente/invalido, credenciales de MySQL
 * remoto rechazadas, etc. Sin token o sin coincidencia, degrada siempre a
 * la redireccion segura (no revela nada a usuarios publicos).
 */
function mh_forensic_or_redirect(Throwable $e, string $context): never
{
    error_log("MH-CORE {$context} en api/login.php: " . $e->getMessage());

    $expectedToken = mh_app_env('MH_DEBUG_TOKEN', '');
    $providedToken = (string) ($_GET['debug_token'] ?? '');

    if ($expectedToken !== '' && hash_equals($expectedToken, $providedToken)) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "MH-CORE FORENSIC BRIDGE - {$context}\n";
        echo str_repeat('=', 60) . "\n";
        echo 'Exception: ' . get_class($e) . "\n";
        echo 'Message  : ' . $e->getMessage() . "\n";
        echo 'File     : ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
        exit;
    }

    header('Location: ../index.php?error=server');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

mh_guard_request($_POST, 'login');

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    header('Location: ../index.php?error=csrf');
    exit;
}

$loginInput = trim((string) ($_POST['email'] ?? ''));
$password   = (string) ($_POST['password'] ?? '');

if ($loginInput === '' || $password === '') {
    header('Location: ../index.php?error=invalid');
    exit;
}

try {
    $pdo = Database::getInstance()->getConnection();

    // Login hibrido: el campo acepta tanto el correo corporativo como el
    // user_id (identificador unico de organigrama). PDO::ATTR_EMULATE_PREPARES
    // esta deshabilitado (config/Database.php), por lo que se usan dos
    // placeholders distintos con el mismo valor (no se permiten nombres repetidos).
    $stmt = $pdo->prepare(
        'SELECT id, user_id, full_name, email, password_hash, role, status, failed_attempts
         FROM users WHERE email = :login_email OR user_id = :login_user_id LIMIT 1'
    );
    $stmt->execute(['login_email' => $loginInput, 'login_user_id' => $loginInput]);
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
    mh_forensic_or_redirect($e, 'DB error');
} catch (RuntimeException $e) {
    mh_forensic_or_redirect($e, 'config error');
}
