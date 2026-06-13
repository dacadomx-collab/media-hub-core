<?php
/**
 * MH-CORE: Guardia de acceso al Dashboard.
 * - Exige sesion activa (login).
 * - Bloquea el acceso si quedan reglamentos sin firmar, redirigiendo
 *   a legal/firma.php (Estandar Oro - Modulo Legal Integrado).
 *
 * Uso esperado: incluido desde dashboard/index.php (un nivel bajo la raiz),
 * por lo que las redirecciones son relativas a esa ubicacion.
 */

require_once __DIR__ . '/../config/Database.php';

function mh_require_auth(): array
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }

    $pdo = Database::getInstance()->getConnection();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*) AS pending FROM user_legal_signatures WHERE user_id = :user_id AND signed = 0'
    );
    $stmt->execute(['user_id' => $_SESSION['user_id']]);

    if ((int) $stmt->fetch()['pending'] > 0) {
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
