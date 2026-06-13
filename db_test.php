<?php
/**
 * Media HUB - Script de Diagnóstico de Base de Datos
 * Propósito: Revelar a qué base de datos exacta se está conectando PHP y ver la estructura real de 'users'.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db_path = __DIR__ . '/config/Database.php';
if (!file_exists($db_path)) {
    die("<h3 style='color:red;'>❌ Error: No se encontró config/Database.php</h3>");
}

require_once $db_path;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Media HUB - Diagnóstico del Ecosistema</title>
    <style>
        body { font-family: monospace; background-color: #022D53; color: #FFFFFF; padding: 20px; line-height: 1.5; }
        .card { background-color: #0c3866; border: 1px solid #00BFB2; padding: 20px; border-radius: 6px; margin-bottom: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        h2 { color: #00BFB2; border-bottom: 1px solid #00BFB2; padding-bottom: 5px; margin-top: 0; }
        .success { color: #34d399; font-weight: bold; }
        .danger { color: #f87171; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; background-color: #022D53; }
        th, td { border: 1px solid #00BFB2; padding: 8px; text-align: left; }
        th { background-color: #062341; color: #00BFB2; }
    </style>
</head>
<body>

    <h1>🔍 PANEL DE DIAGNÓSTICO DE INGENIERÍA — MEDIA HUB</h1>

    <div class="card">
        <h2>1. Estado de la Conexión PDO Singleton</h2>
        <?php
        try {
            $db = Database::getInstance()->getConnection();
            echo "<p class='success'>✔ Conexión establecida exitosamente a través del Singleton Core.</p>";
            
            // 2. Revelar nombre real de la base de datos activa
            $stmt = $db->query("SELECT DATABASE()");
            $current_db = $stmt->fetchColumn();
            echo "<p><strong>Base de Datos que está leyendo PHP actualmente:</strong> <span class='success'>'" . htmlspecialchars($current_db) . "'</span></p>";
            
        } catch (Exception $e) {
            echo "<p class='danger'>❌ Fallo de Conexión: " . htmlspecialchars($e->getMessage()) . "</p>";
            die();
        }
        ?>
    </div>

    <div class="card">
        <h2>2. Tablas Detectadas en este Nodo</h2>
        <ul>
        <?php
        $tables = $db->query("SHOW TABLES");
        while ($row = $tables->fetch(PDO::FETCH_NUM)) {
            echo "<li>📦 " . htmlspecialchars($row[0]) . "</li>";
        }
        ?>
        </ul>
    </div>

    <div class="card">
        <h2>3. Estructura Real de la Tabla `users`</h2>
        <p>A continuación se muestran las columnas que PHP encuentra en caliente dentro de la tabla `users`:</p>
        <table>
            <thead>
                <tr>
                    <th>Campo (Column)</th>
                    <th>Tipo de Dato</th>
                    <th>¿Permite Nulos?</th>
                    <th>Llave</th>
                    <th>Por Defecto</th>
                </tr>
            </thead>
            <tbody>
            <?php
            try {
                $columns = $db->query("SHOW COLUMNS FROM `users`");
                $has_email = false;
                while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
                    if ($col['Field'] === 'email') {
                        $has_email = true;
                    }
                    echo "<tr>
                            <td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>
                            <td>" . htmlspecialchars($col['Type']) . "</td>
                            <td>" . htmlspecialchars($col['Null']) . "</td>
                            <td>" . htmlspecialchars($col['Key']) . "</td>
                            <td>" . htmlspecialchars($col['Default'] ?? 'Ninguno') . "</td>
                          </tr>";
                }
                
                echo "</tbody></table>";
                
                echo "<br>";
                if ($has_email) {
                    echo "<p class='success'>✔ ANALISIS: La columna 'email' SÍ existe aquí. El error podría ser de caché de sesión o estructura de consulta.</p>";
                } else {
                    echo "<p class='danger'>⚠ ALERTA CRÍTICA: La columna 'email' NO EXISTE en esta tabla. Estás conectado a una base de datos desactualizada.</p>";
                }
                
            } catch (Exception $e) {
                echo "</tbody></table>";
                echo "<p class='danger'>❌ Error al inspeccionar la tabla: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
    </div>

</body>
</html>