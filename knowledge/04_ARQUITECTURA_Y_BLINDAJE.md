# 04 · ARQUITECTURA Y BLINDAJE — MEDIA HUB

> **Version:** 1.0 (Fase 1 — Estandar Oro)
> **Clasificacion:** Memoria Inmutable del Sistema — Politicas de Inmunidad Tecnica
> **Stack:** PHP 8.x nativo + PDO, MySQL/MariaDB InnoDB (utf8mb4), Tailwind CSS (CDN) + CSS nativo, XAMPP local

---

## 1. ESTRUCTURA DE CARPETAS (RAIZ DEL PROYECTO)

```
MediaHUB/
├── .env                      # Credenciales y secretos (NUNCA versionar)
├── index.php                 # Landing publica + Modal "Portal Staff" (Login)
├── process_login.php         # Handler de autenticacion
├── troll.php                 # Pagina 403 para atacantes detectados
├── seguridad.log             # Bitacora de eventos de Troll Mode (se autogenera)
├── setup_superadmin.php       # Script de alta inicial (autodestructivo)
├── config/
│   └── Database.php           # PDO Singleton
├── includes/
│   ├── csrf.php               # Generacion/validacion de tokens CSRF
│   ├── security.php            # Troll Mode: deteccion + logging + redireccion
│   └── auth_guard.php          # mh_require_auth() para rutas protegidas
├── legal/
│   └── firma.php               # Flujo de firma digital obligatoria
├── dashboard/
│   ├── index.php               # Dashboard por rol
│   └── logout.php              # Cierre de sesion
├── assets/
│   ├── css/
│   │   ├── main.css            # Estilos base (viewport, login-shell, etc.)
│   │   ├── login-widget.css     # Extracto de main.css para el modal de index.php
│   │   ├── legal.css            # Estilos del modulo legal
│   │   └── dashboard.css        # Estilos del dashboard
│   ├── js/
│   │   └── login.js             # Validacion de formulario en cliente
│   └── img/
│       └── logo.png             # Isotipo Media HUB
├── database/
│   └── schema.sql               # Script de inicializacion (9 tablas + seed)
└── knowledge/                   # Esta base de conocimiento
```

---

## 2. CAPA DE DATOS: PDO SINGLETON + `.env`

### 2.1 Aislamiento de credenciales

Todas las credenciales de base de datos viven **exclusivamente** en `.env` (raiz del proyecto, fuera de control de versiones). `config/Database.php` las carga vía `parse_ini_file()`:

```php
$env = parse_ini_file($envFile);
$host    = $env['DB_HOST'];
$name    = $env['DB_NAME'];
$user    = $env['DB_USER'];
$pass    = $env['DB_PASS'];
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';
```

**Regla de Inmunidad #1:** ningun archivo PHP fuera de `config/Database.php` debe contener un usuario, contraseña o nombre de base de datos en texto plano. Si se necesita un nuevo parametro de conexion, se agrega a `.env` y se lee desde `Database.php`.

### 2.2 Patron Singleton

```php
class Database
{
    private static ?Database $instance = null;
    private PDO $connection;

    private function __construct() { /* ... carga .env y crea PDO ... */ }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO { return $this->connection; }

    private function __clone() {}
    public function __wakeup() { throw new RuntimeException('No se permite deserializar el Singleton de Database.'); }
}
```

Uso estandar en cualquier script:
```php
$db = Database::getInstance()->getConnection();
```

### 2.3 Sentencias Preparadas Obligatorias (PDO)

`PDO::ATTR_EMULATE_PREPARES => false` esta activo globalmente — esto fuerza a MySQL a preparar las consultas de forma nativa, eliminando por completo la superficie de **inyeccion SQL** vía concatenacion de strings.

**Regla de Inmunidad #2:** todo acceso a datos usa `prepare()` + `execute([...])` con parametros nombrados (`:param`) o posicionales. Esta **prohibido** construir SQL concatenando variables de `$_POST`/`$_GET`/`$_SESSION`.

```php
$stmt = $db->prepare("SELECT id, full_name, password_hash, role, status, failed_attempts
                       FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();
```

Opciones globales aplicadas en `Database.php`:
- `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` — cualquier error de SQL lanza excepcion (no falla silenciosamente).
- `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` — resultados como arreglos asociativos consistentes.

---

## 3. PROTECCION CSRF (`includes/csrf.php`)

Cada formulario sensible (`login`, `firma.php`) incluye un token CSRF generado por sesion:

```php
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate(?string $token): bool
{
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
```

**Flujo:**
1. `index.php` llama `csrf_token()` y lo imprime en un `<input type="hidden" name="csrf_token">` dentro del Modal "Portal Staff".
2. `process_login.php` y `legal/firma.php` llaman `csrf_validate($_POST['csrf_token'] ?? null)` como **primera linea de defensa**, antes de tocar la base de datos.
3. Si la validacion falla, la peticion se rechaza (`Location: index.php?error=csrf`) sin revelar mas informacion.

`hash_equals()` se usa deliberadamente (en lugar de `===`) para evitar ataques de temporizacion (timing attacks).

---

## 4. "TROLL MODE" — DETECCION ACTIVA DE INTRUSOS (`includes/security.php`)

### 4.1 Filosofia

Cualquier input de un formulario (`$_POST`, `$_GET`) que coincida con un patron conocido de SQL Injection o XSS **no recibe un mensaje de error util** — el atacante es redirigido de inmediato a `troll.php`, una pagina 403 generica, mientras el sistema registra el incidente.

### 4.2 Patrones vigilados (`MH_ATTACK_PATTERNS`)

| Categoria | Patrones (regex, case-insensitive) |
|---|---|
| SQL Injection | `UNION SELECT`, `SELECT ... FROM`, `INSERT INTO`, `DROP TABLE`, `UPDATE ... SET`, `OR 1=1` / `AND 1=1`, comentarios SQL (`--`, `#`, `/* */`), `; --`, `SLEEP()`, `BENCHMARK()` |
| XSS | `<script`, `<iframe`, atributos `on\w+=` (ej. `onerror=`), `javascript:` |

### 4.3 Flujo de deteccion

```php
function mh_detect_attack(array $inputs): bool {
    // recorre $inputs recursivamente (incluye arrays anidados como ack[])
    // devuelve true si CUALQUIER valor coincide con MH_ATTACK_PATTERNS
}

function mh_log_security_event(string $reason, array $context = []): void {
    // Escribe en seguridad.log: [timestamp] IP=... URI=... REASON=... PAYLOAD={...}
}

function mh_troll_redirect(): never {
    header('Location: /troll.php');
    exit;
}

function mh_guard_request(array $inputs, string $context = 'form'): void {
    if (mh_detect_attack($inputs)) {
        mh_log_security_event('Patron de inyeccion detectado', ['context' => $context]);
        mh_troll_redirect();
    }
}
```

### 4.4 Punto de aplicacion

`mh_guard_request($_POST, 'login')` se ejecuta en `process_login.php` **inmediatamente despues** de la validacion CSRF y **antes** de cualquier consulta a la base de datos. El mismo guard protege `legal/firma.php` con el contexto `'firma'`.

### 4.5 `seguridad.log`

Archivo plano en la raiz del proyecto (`__DIR__ . '/../seguridad.log'` desde `includes/`), con escritura atomica (`FILE_APPEND | LOCK_EX`). Formato de linea:

```
[2026-06-12 10:32:01] IP=203.0.113.55 URI=/process_login.php REASON=Patron de inyeccion detectado PAYLOAD={"context":"login"}
```

Este archivo debe excluirse de control de versiones y revisarse periodicamente por el rol `Administrador`.

### 4.6 Doble capa de bloqueo: Troll Mode por intentos fallidos

Independientemente de la deteccion de patrones, `process_login.php` implementa un segundo mecanismo:

- Constante `MH_MAX_FAILED_ATTEMPTS = 5`.
- Cada intento de login con password incorrecto incrementa `users.failed_attempts`.
- Al alcanzar 5 intentos, `users.status` cambia a `'Troll_Mode'` y se invoca `mh_troll_redirect()`.
- Un usuario en `status = 'Troll_Mode'` o `'Suspendido'` es rechazado en el login **aunque la contrasena sea correcta**, con el mensaje correspondiente (`error=suspended`).

---

## 5. AUTENTICACION Y SESIONES (`process_login.php`)

Secuencia completa de `process_login.php`:

1. `session_start()` (heredado de `index.php`).
2. `csrf_validate($_POST['csrf_token'] ?? null)` → si falla, `Location: index.php?error=csrf`.
3. `mh_guard_request($_POST, 'login')` → Troll Mode por patrones.
4. Validacion de formato de `email` (`FILTER_VALIDATE_EMAIL`) y longitud minima de `password` → si falla, `error=invalid`.
5. `SELECT ... FROM users WHERE email = :email LIMIT 1` (prepared statement).
6. Si `status IN ('Troll_Mode', 'Suspendido')` → `error=suspended`.
7. `password_verify($password, $user['password_hash'])`:
   - **Falla:** incrementa `failed_attempts`; si llega a 5, `status = 'Troll_Mode'` + `mh_troll_redirect()`; si no, `error=credentials`.
   - **Exito:** `session_regenerate_id(true)` (mitiga session fixation), reset de `failed_attempts`, `last_login = NOW()`.
8. Se cargan en sesion: `$_SESSION['user_id']`, `user_code`, `full_name`, `role`, `email`.
9. Verificacion legal: `SELECT COUNT(*) FROM user_legal_signatures WHERE user_id = :id AND signed = 0`.
   - `> 0` → `Location: legal/firma.php`.
   - `= 0` → `Location: dashboard/index.php`.

### 5.1 Hashing de contraseñas

`password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])` en `setup_superadmin.php`; verificacion siempre via `password_verify()`. **Nunca** se compara hash contra hash con `==`/`===`, ni se almacena password en texto plano.

---

## 6. PERMISOS DE ARCHIVOS (DESPLIEGUE EN PRODUCCION)

| Tipo de recurso | Permiso recomendado | Ejemplos |
|---|---|---|
| Directorios | `755` (`drwxr-xr-x`) | `config/`, `includes/`, `legal/`, `dashboard/`, `assets/`, `database/`, `knowledge/` |
| Archivos PHP / CSS / JS / SQL | `644` (`-rw-r--r--`) | `index.php`, `process_login.php`, `assets/css/*.css`, `database/schema.sql` |
| `.env` | `600` (`-rw-------`), propietario = usuario del proceso PHP | Solo lectura para el proceso de PHP, sin acceso de grupo/otros |
| `seguridad.log` | `644`, fuera del `document root` si es posible | Escribible por PHP, idealmente no servible publicamente |

**Regla de Inmunidad #3:** ningun archivo debe quedar con permisos `777`. El `.env` jamas debe ser accesible vía HTTP — debe bloquearse explicitamente a nivel de servidor web (ej. regla en `.htaccess` o configuracion de Apache que niegue acceso a archivos `.env`).

---

## 7. AUTOGUARDA DE RUTAS PROTEGIDAS (`includes/auth_guard.php`)

`mh_require_auth(): array` se invoca al inicio de `dashboard/index.php` (y debe invocarse en cualquier ruta futura del Portal Staff):

1. Si `$_SESSION['user_id']` no existe → redirige a `index.php` (el login vive ahora en el Modal "Portal Staff").
2. Verifica firmas legales pendientes (igual que el paso 9 de `process_login.php`) → si hay pendientes, redirige a `legal/firma.php`.
3. Si todo es valido, devuelve un arreglo con los datos de sesion del usuario (`user_id`, `full_name`, `role`, `email`).

---

## 8. FRONTEND: LANDING + MODAL "PORTAL STAFF" (`index.php`)

A partir de esta version, `index.php` cumple **doble funcion**:

1. **Landing publica** (Tailwind CSS via CDN, `darkMode: 'class'`, colores `deep-sea` / `turquoise` / `digital-white`): hero, infraestructura del Estudio 5 de Mayo, Simulcast, testimoniales de Clientes Jornal, contacto y footer. Es lo primero que ve cualquier visitante.
2. **Portal Staff (Login)**: un modal (`#loginModal`, oculto por defecto con la clase `.hidden` de `assets/css/login-widget.css`) que se activa al pulsar cualquier elemento `[data-open-login]` (boton "Portal Staff" en el nav de escritorio, "Ingresar" en el nav movil, o el enlace del footer).

El formulario interno del modal **no cambia su contrato tecnico**:
- `action="process_login.php"`, `method="post"`.
- Campo oculto `csrf_token` generado por `csrf_token()`.
- IDs preservados para `assets/js/login.js`: `#loginForm`, `#email`, `#password`, `#emailMessage`, `#passwordMessage`, `#systemMessage`, `#submitBtn`, `#csrf_token`.
- Si `$_GET['error']` esta presente, el modal se abre automaticamente al cargar la pagina y `#systemMessage` muestra el mensaje correspondiente con clase `.error`.

`assets/css/login-widget.css` aisla los estilos del panel de acceso (variables `--deep-sea-blue`, `--pacific-turquoise`, `.login-shell`, `.field-wrap`, etc.) sin interferir con las clases Tailwind de la Landing, evitando que `main.css` (usado por `troll.php`, `legal/firma.php` y `dashboard/`) sobreescriba el `body` de la pagina publica.

---

## 9. CHECKLIST DE BLINDAJE ("ESTANDAR ORO") — RESUMEN EJECUTIVO

- [x] Conexion a base de datos vía PDO Singleton (`config/Database.php`).
- [x] `PDO::ATTR_EMULATE_PREPARES => false` + sentencias preparadas en **todos** los accesos a datos.
- [x] Tokens CSRF generados por sesion y validados con `hash_equals()` en cada formulario POST.
- [x] Passwords con `PASSWORD_BCRYPT` (`cost => 12`), verificadas con `password_verify()`.
- [x] Troll Mode por patrones (`mh_guard_request`) en `process_login.php` y `legal/firma.php`.
- [x] Troll Mode por intentos fallidos (`failed_attempts >= 5` → `status = 'Troll_Mode'`).
- [x] `seguridad.log` con registro de IP, URI, razon y payload.
- [x] `.env` fuera de codigo fuente versionado, leido solo por `config/Database.php`.
- [x] `session_regenerate_id(true)` tras login exitoso.
- [x] Gate de firma legal obligatoria antes de acceso al Dashboard.
- [ ] Politica de permisos `755`/`644`/`600` aplicada en el servidor de produccion (pendiente de verificacion en despliegue real).

---

*Este documento describe el estado de blindaje vigente a Fase 1. Cualquier nuevo endpoint que reciba input de usuario debe pasar por `csrf_validate()` + `mh_guard_request()` antes de tocar la capa de datos.*
