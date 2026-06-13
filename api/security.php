<?php
/**
 * MH-CORE: "Troll Mode".
 * Deteccion activa de patrones de inyeccion (SQLi/XSS) en cualquier input
 * de formulario. Si se detecta un patron malicioso, la peticion se
 * suspende, se registra la IP en seguridad.log/security_logs y el
 * atacante es redirigido instantaneamente a troll.php.
 *
 * ESCALAMIENTO PERIMETRAL (security_logs / ip_blocks / ip_blacklist):
 *   - 3 eventos de la misma IP en 15 minutos -> bloqueo temporal de 30 min.
 *   - 10 eventos de la misma IP en 15 minutos -> alta inmutable en ip_blacklist.
 */

require_once __DIR__ . '/../config/Database.php';

const MH_ATTACK_PATTERNS = [
    /* SQL Injection */
    '/(\bunion\b\s+\bselect\b)/i',
    '/(\bselect\b.+\bfrom\b)/i',
    '/(\binsert\b\s+\binto\b)/i',
    '/(\bdrop\b\s+\btable\b)/i',
    '/(\bupdate\b.+\bset\b)/i',
    '/(\bor\b\s+\d+\s*=\s*\d+)/i',
    '/(\band\b\s+\d+\s*=\s*\d+)/i',
    '/(--|#|\/\*)/',
    '/(;\s*--)/',
    '/(\bsleep\s*\()/i',
    '/(\bbenchmark\s*\()/i',
    /* XSS */
    '/<\s*script/i',
    '/<\s*iframe/i',
    '/on\w+\s*=\s*["\']?/i',
    '/javascript\s*:/i',
];

/**
 * Revisa un arreglo de valores (tipicamente $_POST o $_GET) contra los
 * patrones de ataque conocidos.
 */
function mh_detect_attack(array $inputs): bool
{
    foreach ($inputs as $value) {
        if (is_array($value)) {
            if (mh_detect_attack($value)) {
                return true;
            }
            continue;
        }

        $value = (string) $value;

        foreach (MH_ATTACK_PATTERNS as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Escribe un evento de seguridad en seguridad.log (raiz del proyecto) y,
 * de forma best-effort, en la tabla `security_logs` para alimentar el
 * escalamiento perimetral por IP.
 */
function mh_log_security_event(string $reason, array $context = []): void
{
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $uri     = $_SERVER['REQUEST_URI'] ?? 'unknown';
    $time    = date('Y-m-d H:i:s');
    $payload = json_encode($context, JSON_UNESCAPED_UNICODE);

    $line = "[{$time}] IP={$ip} URI={$uri} REASON={$reason} PAYLOAD={$payload}" . PHP_EOL;

    $logFile = __DIR__ . '/../seguridad.log';
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

    try {
        $pdo  = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            'INSERT INTO security_logs (ip_address, event_type, context, payload)
             VALUES (:ip, :event_type, :context, :payload)'
        );
        $stmt->execute([
            'ip'         => $ip,
            'event_type' => $reason,
            'context'    => $context['context'] ?? null,
            'payload'    => $payload,
        ]);
    } catch (Throwable $e) {
        // Si la tabla de escalamiento aun no existe o la BD no responde,
        // el registro en seguridad.log ya quedo a salvo.
    }
}

/**
 * Suspende la peticion y redirige al atacante a troll.php.
 * Ruta relativa: valida desde api/ y legal/ (un nivel bajo la raiz).
 */
function mh_troll_redirect(): never
{
    header('Location: ../troll.php');
    exit;
}

/**
 * Verifica la reputacion de la IP actual contra `ip_blacklist` (alta
 * inmutable) y `ip_blocks` (bloqueo temporal vigente). Si la IP esta
 * bloqueada, redirige de inmediato a troll.php.
 */
function mh_enforce_ip_reputation(): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    try {
        $pdo = Database::getInstance()->getConnection();

        $blacklisted = $pdo->prepare('SELECT COUNT(*) AS hits FROM ip_blacklist WHERE ip_address = :ip');
        $blacklisted->execute(['ip' => $ip]);
        if ((int) $blacklisted->fetch()['hits'] > 0) {
            mh_troll_redirect();
        }

        $blocked = $pdo->prepare('SELECT COUNT(*) AS hits FROM ip_blocks WHERE ip_address = :ip AND blocked_until > NOW()');
        $blocked->execute(['ip' => $ip]);
        if ((int) $blocked->fetch()['hits'] > 0) {
            mh_troll_redirect();
        }
    } catch (Throwable $e) {
        // Si las tablas de reputacion aun no existen, no se bloquea el flujo.
    }
}

/**
 * Escalamiento perimetral: cuenta los eventos de seguridad de la IP
 * actual en los ultimos 15 minutos y aplica:
 *   - >= 10 eventos -> alta inmutable en `ip_blacklist`.
 *   - >= 3 eventos  -> bloqueo temporal de 30 minutos en `ip_blocks`.
 */
function mh_troll_escalate(): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    try {
        $pdo = Database::getInstance()->getConnection();

        $count = $pdo->prepare(
            'SELECT COUNT(*) AS hits FROM security_logs
             WHERE ip_address = :ip AND created_at >= (NOW() - INTERVAL 15 MINUTE)'
        );
        $count->execute(['ip' => $ip]);
        $hits = (int) $count->fetch()['hits'];

        if ($hits >= 10) {
            $exists = $pdo->prepare('SELECT COUNT(*) AS hits FROM ip_blacklist WHERE ip_address = :ip');
            $exists->execute(['ip' => $ip]);

            if ((int) $exists->fetch()['hits'] === 0) {
                $insert = $pdo->prepare(
                    'INSERT INTO ip_blacklist (ip_address, reason) VALUES (:ip, :reason)'
                );
                $insert->execute(['ip' => $ip, 'reason' => 'Limite de 10 intentos maliciosos en 15 minutos']);
            }
        } elseif ($hits >= 3) {
            $reason = 'Limite de 3 intentos en 15 minutos';
            $upsert = $pdo->prepare(
                'INSERT INTO ip_blocks (ip_address, blocked_until, reason)
                 VALUES (:ip, NOW() + INTERVAL 30 MINUTE, :reason1)
                 ON DUPLICATE KEY UPDATE blocked_until = NOW() + INTERVAL 30 MINUTE, reason = :reason2'
            );
            $upsert->execute(['ip' => $ip, 'reason1' => $reason, 'reason2' => $reason]);
        }
    } catch (Throwable $e) {
        // Si las tablas de reputacion aun no existen, no se bloquea el flujo.
    }
}

/**
 * Punto unico de verificacion para formularios POST: aplica primero la
 * reputacion de IP (bloqueo/blacklist) y, si detecta un patron malicioso,
 * registra el evento, escala el contador perimetral y redirige a troll.php.
 */
function mh_guard_request(array $inputs, string $context = 'form'): void
{
    mh_enforce_ip_reputation();

    if (mh_detect_attack($inputs)) {
        mh_log_security_event('Patron de inyeccion detectado', ['context' => $context]);
        mh_troll_escalate();
        mh_troll_redirect();
    }
}
