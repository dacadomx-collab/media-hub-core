<?php
/**
 * MH-CORE: Centro de Comando Ejecutivo (KPIs financieros y operativos).
 * Ubicacion: api/finance.php (un nivel bajo la raiz).
 *
 * Acciones soportadas (contrato { status, message, data }):
 *   GET ?action=kpis -> Ingresos mensuales, utilidad neta proyectada,
 *                        IVA acumulado (16%), anticipos pendientes por
 *                        cobrar, horas de estudio consumidas y alertas
 *                        de flota en mantenimiento. Solo Administrador.
 */

session_start();

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';

// Utilidad Neta Proyectada = 40% de los ingresos del mes (matriz de costos).
const MH_PROJECTED_PROFIT_MARGIN = 0.40;
// IVA de ley sobre ingresos facturables.
const MH_IVA_RATE = 0.16;

$currentUser = mh_require_session();
$method      = $_SERVER['REQUEST_METHOD'];
$action      = $_GET['action'] ?? '';

$pdo = Database::getInstance()->getConnection();

try {
    // -----------------------------------------------------------------
    // GET ?action=kpis — Tarjetas del Centro de Comando Ejecutivo
    // -----------------------------------------------------------------
    if ($method === 'GET' && $action === 'kpis') {
        mh_require_role($currentUser, ['Administrador']);

        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        // Ingresos mensuales totales (llamados confirmados/completados del mes).
        $revenueStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(total_amount), 0) AS total
             FROM calls
             WHERE call_date BETWEEN :start AND :end
               AND status IN ('Confirmado', 'Completado')"
        );
        $revenueStmt->execute(['start' => $monthStart, 'end' => $monthEnd]);
        $monthlyRevenue = (float) $revenueStmt->fetch()['total'];

        $projectedProfit = $monthlyRevenue * MH_PROJECTED_PROFIT_MARGIN;
        $ivaAccrued      = $monthlyRevenue * MH_IVA_RATE;

        // Anticipos pendientes por cobrar (llamados activos sin anticipo verificado).
        $pendingStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(total_amount * advance_required_pct / 100), 0) AS total
             FROM calls
             WHERE advance_paid = 0 AND status != 'Cancelado' AND total_amount IS NOT NULL"
        );
        $pendingStmt->execute();
        $pendingAdvances = (float) $pendingStmt->fetch()['total'];

        // Horas de estudio consumidas en el mes (llamados confirmados/completados).
        $hoursStmt = $pdo->prepare(
            "SELECT COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0) AS seconds
             FROM calls
             WHERE call_date BETWEEN :start AND :end
               AND status IN ('Confirmado', 'Completado')"
        );
        $hoursStmt->execute(['start' => $monthStart, 'end' => $monthEnd]);
        $studioHours = round(((float) $hoursStmt->fetch()['seconds']) / 3600, 1);

        // Alertas de flota: Van Terrestre / Embarcacion Maritima en Mantenimiento.
        $fleetStmt = $pdo->prepare(
            "SELECT id, name, type FROM fleet_vehicles WHERE status = 'Mantenimiento'"
        );
        $fleetStmt->execute();
        $fleetAlerts = $fleetStmt->fetchAll();

        mh_json_response('success', 'KPIs ejecutivos cargados.', [
            'monthly_revenue'   => round($monthlyRevenue, 2),
            'projected_profit'  => round($projectedProfit, 2),
            'iva_accrued'       => round($ivaAccrued, 2),
            'pending_advances'  => round($pendingAdvances, 2),
            'studio_hours'      => $studioHours,
            'fleet_maintenance' => $fleetAlerts,
            'period'            => ['start' => $monthStart, 'end' => $monthEnd],
        ]);
    }

    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en api/finance.php: ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
