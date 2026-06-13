<?php
/**
 * MH-CORE: "Troll Mode".
 * Deteccion activa de patrones de inyeccion (SQLi/XSS) en cualquier input
 * de formulario. Si se detecta un patron malicioso, la peticion se
 * suspende, se registra la IP en seguridad.log y el atacante es
 * redirigido instantaneamente a troll.php.
 */

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
 * Escribe un evento de seguridad en seguridad.log (raiz del proyecto).
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
}

/**
 * Suspende la peticion y redirige al atacante a troll.php.
 */
function mh_troll_redirect(): never
{
    header('Location: /troll.php');
    exit;
}

/**
 * Punto unico de verificacion para formularios POST: si detecta un patron
 * malicioso, registra el evento y redirige a troll.php sin continuar.
 */
function mh_guard_request(array $inputs, string $context = 'form'): void
{
    if (mh_detect_attack($inputs)) {
        mh_log_security_event('Patron de inyeccion detectado', ['context' => $context]);
        mh_troll_redirect();
    }
}
