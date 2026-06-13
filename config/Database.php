<?php
/**
 * MH-CORE: Conexion PDO Singleton.
 * Aisla credenciales en .env (raiz del proyecto) y expone una unica
 * instancia PDO reutilizable en toda la aplicacion.
 */
class Database
{
    private static ?Database $instance = null;
    private static ?array $env = null;
    private PDO $connection;

    private function __construct()
    {
        $envFile = __DIR__ . '/../.env';

        if (!file_exists($envFile)) {
            throw new RuntimeException('Archivo .env no encontrado en la raiz del proyecto.');
        }

        $env = self::loadEnv();

        $required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
        $missing  = [];

        foreach ($required as $key) {
            if (!array_key_exists($key, $env) || $env[$key] === '') {
                $missing[] = $key;
            }
        }

        if (!empty($missing)) {
            $debugToken    = $env['MH_DEBUG_TOKEN'] ?? '';
            $providedToken = (string) ($_GET['debug_token'] ?? '');

            if ($debugToken !== '' && hash_equals($debugToken, $providedToken)) {
                throw new RuntimeException(
                    'Configuracion de entorno incompleta. Variables faltantes o vacias: ' . implode(', ', $missing)
                );
            }

            throw new RuntimeException('Configuracion de entorno incompleta.');
        }

        $host    = $env['DB_HOST'];
        $name    = $env['DB_NAME'];
        $user    = $env['DB_USER'];
        $pass    = $env['DB_PASS'];
        $charset = $env['DB_CHARSET'] ?? 'utf8mb4';

        $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->connection = new PDO($dsn, $user, $pass, $options);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Carga y cachea el .env de la raiz del proyecto usando un lector de
     * lineas propio (ver parseEnvFile). Devuelve [] si el archivo no existe,
     * para que llamadas auxiliares (ej. mh_app_env) degraden con seguridad.
     */
    public static function loadEnv(): array
    {
        if (self::$env === null) {
            $envFile  = __DIR__ . '/../.env';
            self::$env = file_exists($envFile) ? self::parseEnvFile($envFile) : [];
        }

        return self::$env;
    }

    /**
     * Lector de archivos .env tolerante a fallos, linea por linea.
     *
     * Reemplaza a parse_ini_file(), que en GreenGeeks rompe (devuelve
     * false para todo el archivo) cuando una contrasena real contiene
     * signos de pesos ($), punto y coma (;) u otros caracteres especiales
     * de sintaxis INI. Reglas:
     *  - Ignora lineas vacias y comentarios que comienzan con # o ;.
     *  - Separa clave/valor por el PRIMER signo de igual (=) de la linea,
     *    de modo que el valor puede contener = adicionales.
     *  - trim() en clave y valor.
     *  - Si el valor viene envuelto en comillas simples o dobles, remueve
     *    unicamente esas comillas de los extremos, preservando intacto
     *    cualquier $, ; u otro simbolo especial en el interior.
     */
    private static function parseEnvFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return [];
        }

        $env = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }

            $separator = strpos($line, '=');

            if ($separator === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $separator));
            $value = trim(substr($line, $separator + 1));

            $length = strlen($value);

            if ($length >= 2) {
                $first = $value[0];
                $last  = $value[$length - 1];

                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, $length - 2);
                }
            }

            if ($key !== '') {
                $env[$key] = $value;
            }
        }

        return $env;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new RuntimeException('No se permite deserializar el Singleton de Database.');
    }
}
