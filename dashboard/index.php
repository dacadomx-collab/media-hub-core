<?php
/**
 * MH-CORE: Dashboard - Punto de entrada post-login.
 * Modulos por modulo (agenda, inventario, etc.) se construiran sobre
 * esta base en fases siguientes. Aqui se valida sesion + firmas legales
 * y se presenta el organigrama digital segun el rol del usuario.
 */

session_start();
require_once __DIR__ . '/../includes/auth_guard.php';

$user = mh_require_auth();

$roleLabels = [
    'Administrador'    => 'Administrador',
    'Lider_Proyecto'    => 'Lider de Proyecto',
    'Staff_Tecnico'     => 'Staff Tecnico',
    'Chofer_Logistica'  => 'Chofer de Logistica',
    'Cliente'           => 'Cliente',
];

$roleDescriptions = [
    'Administrador'    => 'Control total del sistema: usuarios, agenda, inventario y modulo legal.',
    'Lider_Proyecto'    => 'Supervision de programas, agenda del Estudio 5 de Mayo y asignacion de staff por llamado.',
    'Staff_Tecnico'     => 'Tareas tecnicas asignadas por llamado y check-in/check-out de equipo.',
    'Chofer_Logistica'  => 'Operacion y check-in/check-out de unidades moviles (Van Terrestre / Embarcacion Maritima).',
    'Cliente'           => 'Seguimiento de tu programa y agenda de llamados en Media HUB.',
];

$roleLabel = $roleLabels[$user['role']] ?? $user['role'];
$roleDescription = $roleDescriptions[$user['role']] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media HUB | Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-shell">
        <header class="dashboard-topbar">
            <div class="brand-block" style="justify-items: start; gap: 0.15rem;">
                <p class="brand-label">MEDIA HUB</p>
                <strong><?php echo htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
            <div style="display:flex; align-items:center; gap:0.6rem;">
                <span class="dashboard-role-badge"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                <a class="dashboard-logout" href="logout.php">Salir</a>
            </div>
        </header>

        <section class="dashboard-card">
            <h2>Bienvenido al nucleo digital</h2>
            <p><?php echo htmlspecialchars($roleDescription, ENT_QUOTES, 'UTF-8'); ?></p>
        </section>

        <section class="dashboard-grid">
            <article class="dashboard-card">
                <h2>Agenda Estudio 5 de Mayo</h2>
                <p>Calendario inteligente con control de colisiones y anticipo del 50% por llamado. (Modulo en construccion)</p>
            </article>
            <article class="dashboard-card">
                <h2>Programas y Clientes Jornal</h2>
                <p>Medicina del Siglo XXI y CCBCS activos en el catalogo de programas recurrentes.</p>
            </article>
            <article class="dashboard-card">
                <h2>Inventario y Flota</h2>
                <p>Camaras, opticas, luces LED, Van Terrestre y Embarcacion Maritima con check-in/check-out digital.</p>
            </article>
            <article class="dashboard-card">
                <h2>Tareas por Llamado</h2>
                <p>Lista de responsabilidades asignadas a tu perfil para los proximos llamados.</p>
            </article>
        </section>
    </div>
</body>
</html>
