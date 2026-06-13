<?php
/**
 * MH-CORE: Cierre de sesion.
 * Ubicacion: api/logout.php (un nivel bajo la raiz).
 */
session_start();
$_SESSION = [];
session_destroy();
header('Location: ../index.php');
exit;
