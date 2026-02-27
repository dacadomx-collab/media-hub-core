<?php
// MH-CORE: Validador de Conexión Silencioso
$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    die("Error Crítico: Archivo .env no encontrado en la raíz.");
}

$env = parse_ini_file($envFile);

if (!$env) {
    die("Error Crítico: El archivo .env tiene un error de sintaxis (revisa comillas o paréntesis).");
}

try {
    $host = $env['DB_HOST'];
    $db   = $env['DB_NAME'];
    $user = $env['DB_USER'];
    $pass = $env['DB_PASS'];
    $charset = $env['DB_CHARSET'] ?? 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Éxito: Estética Media HUB (Pacific Turquoise)
    echo "
    <div style='background:#022D53; color:#00BFB2; padding:30px; font-family:sans-serif; border-left:5px solid #00BFB2; border-radius:8px;'>
        <h2 style='margin:0;'>⚓ MEDIA HUB: Sincronización Exitosa</h2>
        <p style='color:white;'>El motor de base de datos responde correctamente en el entorno: <b>" . htmlspecialchars($env['APP_ENV']) . "</b></p>
    </div>";

} catch (PDOException $e) {
    // Registro silencioso en seguridad.log
    error_log("[" . date('Y-m-d H:i:s') . "] FALLO DB: " . $e->getMessage());
    
    // Error visual para el cliente (Sunset Orange)
    echo "
    <div style='background:#022D53; color:#FF5733; padding:30px; font-family:sans-serif; border-left:5px solid #FF5733; border-radius:8px;'>
        <h2 style='margin:0;'>⚠️ Error de Sincronización</h2>
        <p style='color:white;'>El incidente ha sido registrado en el log de seguridad para auditoría.</p>
    </div>";
}