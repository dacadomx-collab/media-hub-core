<?php
/**
 * Media HUB - Diagnostic Sensor for GreenGeeks Production
 * Ubicación: public_html/api/debug_db.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<body style='background:#022D53;color:#fff;font-family:monospace;padding:20px;'>";
echo "<h2>🔬 MEDIA HUB - SENSOR DE CONTINUIDAD EN PRODUCCIÓN</h2>";
echo "<hr style='border-color:#00BFB2;'>";

$env_path = __DIR__ . '/../.env';
if (!file_exists($env_path)) {
    die("<p style='color:#f87171;font-weight:bold;'>❌ ERROR CRÍTICO: El archivo .env NO existe en la raíz de tu servidor GreenGeeks o está mal nombrado.</p></body>");
}

echo "<p style='color:#34d399;'>✔ Archivo de entorno .env detectado correctamente en la raíz.</p>";

// Intentar cargar la clase Database
$db_class = __DIR__ . '/../config/Database.php';
if (!file_exists($db_class)) {
    die("<p style='color:#f87171;'>❌ ERROR: No se encontró config/Database.php</p></body>");
}

require_once $db_class;

// Pre-vuelo: usa el lector de .env tolerante a fallos (Database::loadEnv)
// para confirmar que las 4 llaves obligatorias se reconocieron, sin
// imprimir jamas sus valores reales (host/usuario/clave son sensibles).
$env             = Database::loadEnv();
$requiredKeys    = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
$detectedKeys    = [];
$missingKeys     = [];

foreach ($requiredKeys as $key) {
    if (array_key_exists($key, $env) && $env[$key] !== '') {
        $detectedKeys[] = $key;
    } else {
        $missingKeys[] = $key;
    }
}

if (empty($missingKeys)) {
    echo "<p style='color:#34d399;'>✔ Variables detectadas en .env: " . htmlspecialchars(implode(', ', $detectedKeys)) . ".</p>";
} else {
    echo "<p style='color:#f87171;font-weight:bold;'>❌ Variables faltantes o vacias en .env: " . htmlspecialchars(implode(', ', $missingKeys)) . ".</p>";
}

try {
    echo "<p style='color:#cbd5e1;'>Intentando enlace por medio del Singleton con las credenciales del .env...</p>";
    $pdo = Database::getInstance()->getConnection();
    echo "<p style='color:#34d399;font-weight:bold;'>✔ ¡CONEXIÓN EXITOSA! PHP se ha conectado fluidamente a MySQL en GreenGeeks.</p>";

    // Validar si las tablas reales existen en producción
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->fetch()) {
        echo "<p style='color:#34d399;'>✔ La tabla 'users' existe y está disponible en la base de datos remota.</p>";
    } else {
        echo "<p style='color:#f87171;font-weight:bold;'>❌ ALERTA: La tabla 'users' NO EXISTE. ¿Olvidaste importar tu archivo .sql en el phpMyAdmin del cPanel?</p>";
    }
} catch (RuntimeException $e) {
    echo "<p style='color:#f87171;font-weight:bold;'>❌ CONFIGURACION DE ENTORNO INVALIDA (config/Database.php):</p>";
    echo "<pre style='background:#000;color:#f87171;padding:15px;border:1px solid #f87171;border-radius:4px;overflow-x:auto;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    echo "<p style='color:#cbd5e1;'>💡 HINT: El .env no se encontro, o falta/esta vacia alguna de DB_HOST, DB_NAME, DB_USER, DB_PASS. Revisa el listado de variables detectadas arriba.</p>";
} catch (PDOException $e) {
    echo "<p style='color:#f87171;font-weight:bold;'>❌ CORTOCIRCUITO DETECTADO - ERROR REAL DE MYSQL:</p>";
    echo "<pre style='background:#000;color:#f87171;padding:15px;border:1px solid #f87171;border-radius:4px;overflow-x:auto;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
    echo "<p style='color:#cbd5e1;'>💡 HINT DE ARQUITECTURA: Verifica si tu base de datos y usuario de cPanel llevan un prefijo (ej: tecnidep_tecnidepot_mediahub_db) o si olvidaste asignarle 'Todos los privilegios' al usuario en el asistente de MySQL.</p>";
}

echo "</body>";