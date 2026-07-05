<?php
/**
 * MH-CORE: Cierre de sesion.
 * Ubicacion: api/logout.php (un nivel bajo la raiz).
 *
 * Fase 5.1: ademas de destruir la sesion, revoca cualquier token de
 * "Recuerdame" del usuario y borra la cookie -- de lo contrario el
 * cierre de sesion seria cosmetico (la cookie restauraria la sesion
 * automaticamente en la siguiente visita, ver api/auth_guard.php).
 */
session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/auth_guard.php';

if (!empty($_SESSION['user_id'])) {
    try {
        Database::getInstance()->getConnection()
            ->prepare('DELETE FROM user_remember_tokens WHERE user_id = :user_id')
            ->execute(['user_id' => (int) $_SESSION['user_id']]);
    } catch (PDOException $e) {
        error_log('MH-CORE DB error en api/logout.php: ' . $e->getMessage());
    }
}

mh_clear_remember_cookie();

$_SESSION = [];
session_destroy();
header('Location: ../index.php');
exit;
