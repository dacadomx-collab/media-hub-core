# 04 · ARQUITECTURA Y BLINDAJE — MEDIA HUB

> **Version:** 1.1 (Fase 1 — Estandar Oro / Reestructuracion `api/`)
> **Clasificacion:** Memoria Inmutable del Sistema — Politicas de Inmunidad Tecnica
> **Stack:** PHP 8.x nativo + PDO, MySQL/MariaDB InnoDB (utf8mb4), Tailwind CSS (CDN) + CSS nativo, XAMPP local

---

## 1. ESTRUCTURA DE CARPETAS (RAIZ DEL PROYECTO)

```
MediaHUB/
├── .env                      # Credenciales y secretos (NUNCA versionar)
├── .gitignore                # Excluye .env, seguridad.log y scripts de diagnostico
├── index.php                 # Landing publica + Modal "Portal Staff" (Login)
├── troll.php                 # Pagina 403 para atacantes detectados
├── seguridad.log             # Bitacora de eventos de Troll Mode (se autogenera)
├── config/
│   └── Database.php           # PDO Singleton
├── api/                       # Capa API: unico punto de entrada para mutaciones
│   ├── csrf.php                # Generacion/validacion de tokens CSRF
│   ├── security.php            # Troll Mode: deteccion + logging + redireccion
│   ├── auth_guard.php          # mh_require_auth() para rutas protegidas
│   ├── login.php               # Procesador de autenticacion (antes process_login.php)
│   ├── logout.php               # Cierre de sesion (antes dashboard/logout.php)
│   └── signature.php            # Procesador de firma digital de reglamentos
├── legal/
│   └── firma.php               # Render del flujo de firma digital obligatoria
├── dashboard/
│   └── index.php               # Dashboard por rol
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
└── knowledge/                   # Esta base de conocimiento (no se despliega a produccion)
```

> **Archivos de solo-desarrollo** (`genesis.php`, `setup_superadmin.php`, `db_test.php`, `test_hub_connection.php`): viven en la raiz durante el desarrollo local pero **nunca** deben llegar a produccion. Ver seccion 10.

### 1.1 La Capa `api/`

Toda mutacion de estado (login, logout, firma de reglamentos, y cualquier endpoint futuro que escriba en la base de datos) se centraliza en `api/`. Esto separa claramente:

- **Vistas** (`index.php`, `legal/firma.php`, `dashboard/index.php`) → renderizan HTML, hacen `GET` y leen datos de sesion.
- **Acciones** (`api/*.php`) → procesan `POST`, validan CSRF + Troll Mode, escriben en la base de datos y **siempre terminan en un `header('Location: ...')`**.

Cada archivo de `api/` esta a **un nivel de profundidad** respecto a la raiz, igual que `legal/` y `dashboard/`. Esto simplifica las rutas relativas: desde cualquier archivo en `api/`, `legal/` o `dashboard/`, la raiz del proyecto es siempre `../`.

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

## 3. PROTECCION CSRF (`api/csrf.php`)

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
2. `api/login.php` y `api/signature.php` llaman `csrf_validate($_POST['csrf_token'] ?? null)` como **primera linea de defensa**, antes de tocar la base de datos.
3. Si la validacion falla, la peticion se rechaza (`Location: ../index.php?error=csrf`, relativo desde `api/`) sin revelar mas informacion.

`hash_equals()` se usa deliberadamente (en lugar de `===`) para evitar ataques de temporizacion (timing attacks).

---

## 4. "TROLL MODE" — DETECCION ACTIVA DE INTRUSOS (`api/security.php`)

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
    // Ruta relativa: valida desde api/ y legal/ (un nivel bajo la raiz).
    header('Location: ../troll.php');
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

`mh_guard_request($_POST, 'login')` se ejecuta en `api/login.php` **inmediatamente despues** de la validacion CSRF y **antes** de cualquier consulta a la base de datos. El mismo guard protege `api/signature.php` con el contexto `'legal_firma'`.

### 4.5 `seguridad.log`

Archivo plano en la raiz del proyecto (`__DIR__ . '/../seguridad.log'` desde `api/`), con escritura atomica (`FILE_APPEND | LOCK_EX`). Formato de linea:

```
[2026-06-12 10:32:01] IP=203.0.113.55 URI=/api/login.php REASON=Patron de inyeccion detectado PAYLOAD={"context":"login"}
```

Este archivo debe excluirse de control de versiones y revisarse periodicamente por el rol `Administrador`.

### 4.6 Doble capa de bloqueo: Troll Mode por intentos fallidos

Independientemente de la deteccion de patrones, `api/login.php` implementa un segundo mecanismo:

- Constante `MH_MAX_FAILED_ATTEMPTS = 5`.
- Cada intento de login con password incorrecto incrementa `users.failed_attempts`.
- Al alcanzar 5 intentos, `users.status` cambia a `'Troll_Mode'` y se invoca `mh_troll_redirect()`.
- Un usuario en `status = 'Troll_Mode'` o `'Suspendido'` es rechazado en el login **aunque la contrasena sea correcta**, con el mensaje correspondiente (`error=suspended`).

---

## 5. AUTENTICACION Y SESIONES (`api/login.php`)

Secuencia completa de `api/login.php`. **Importante:** todos los `Location:` de esta seccion son rutas relativas calculadas desde `api/` (un nivel bajo la raiz), por lo que apuntan con `../` al archivo objetivo en la raiz o en `legal/`/`dashboard/`.

1. `session_start()` (heredado de `index.php`).
2. `csrf_validate($_POST['csrf_token'] ?? null)` → si falla, `Location: ../index.php?error=csrf`.
3. `mh_guard_request($_POST, 'login')` → Troll Mode por patrones (redirige a `../troll.php` si detecta ataque).
4. Validacion de formato de `email` (`FILTER_VALIDATE_EMAIL`) y longitud minima de `password` → si falla, `Location: ../index.php?error=invalid`.
5. `SELECT ... FROM users WHERE email = :email LIMIT 1` (prepared statement).
6. Si `status IN ('Troll_Mode', 'Suspendido')` → `Location: ../index.php?error=suspended`.
7. `password_verify($password, $user['password_hash'])`:
   - **Falla:** incrementa `failed_attempts`; si llega a 5, `status = 'Troll_Mode'` + `mh_troll_redirect()` (→ `../troll.php`); si no, `Location: ../index.php?error=credentials`.
   - **Exito:** `session_regenerate_id(true)` (mitiga session fixation), reset de `failed_attempts`, `last_login = NOW()`.
8. Se cargan en sesion: `$_SESSION['user_id']`, `user_code`, `full_name`, `role`, `email`.
9. Verificacion legal: `SELECT COUNT(*) FROM user_legal_signatures WHERE user_id = :id AND signed = 0`.
   - `> 0` → `Location: ../legal/firma.php`.
   - `= 0` → `Location: ../dashboard/index.php`.

### 5.1 Hashing de contraseñas

`password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12])` en `setup_superadmin.php`; verificacion siempre via `password_verify()`. **Nunca** se compara hash contra hash con `==`/`===`, ni se almacena password en texto plano.

---

## 6. PERMISOS DE ARCHIVOS (DESPLIEGUE EN PRODUCCION)

| Tipo de recurso | Permiso recomendado | Ejemplos |
|---|---|---|
| Directorios | `755` (`drwxr-xr-x`) | `config/`, `api/`, `legal/`, `dashboard/`, `assets/`, `database/` |
| Archivos PHP / CSS / JS / SQL | `644` (`-rw-r--r--`) | `index.php`, `api/login.php`, `assets/css/*.css`, `database/schema.sql` |
| `.env` | `600` (`-rw-------`), propietario = usuario del proceso PHP | Solo lectura para el proceso de PHP, sin acceso de grupo/otros |
| `seguridad.log` | `644`, fuera del `document root` si es posible | Escribible por PHP, idealmente no servible publicamente |

**Regla de Inmunidad #3:** ningun archivo debe quedar con permisos `777`. El `.env` jamas debe ser accesible vía HTTP — debe bloquearse explicitamente a nivel de servidor web (ej. regla en `.htaccess` o configuracion de Apache que niegue acceso a archivos `.env`).

---

## 7. AUTOGUARDA DE RUTAS PROTEGIDAS (`api/auth_guard.php`)

`mh_require_auth(): array` se invoca al inicio de `dashboard/index.php` vía `require_once __DIR__ . '/../api/auth_guard.php';` (y debe invocarse en cualquier ruta futura del Portal Staff). Al igual que en `api/login.php`, las redirecciones son relativas (`../`), correctas desde `dashboard/`:

1. Si `$_SESSION['user_id']` no existe → `Location: ../index.php` (el login vive ahora en el Modal "Portal Staff").
2. Verifica firmas legales pendientes (igual que el paso 9 de `api/login.php`) → si hay pendientes, `Location: ../legal/firma.php`.
3. Si todo es valido, devuelve un arreglo con los datos de sesion del usuario (`user_id`, `user_code`, `full_name`, `role`, `email`).
4. El cierre de sesion (`dashboard/index.php` → enlace "Salir") apunta a `../api/logout.php`, que destruye la sesion y redirige a `../index.php`.

---

## 8. FRONTEND: LANDING + MODAL "PORTAL STAFF" (`index.php`)

A partir de esta version, `index.php` cumple **doble funcion**:

1. **Landing publica** (Tailwind CSS via CDN, `darkMode: 'class'`, colores `deep-sea` / `turquoise` / `digital-white`): hero, infraestructura del Estudio 5 de Mayo, Simulcast, testimoniales de Clientes Jornal, contacto y footer. Es lo primero que ve cualquier visitante.
2. **Portal Staff (Login)**: un modal (`#loginModal`, oculto por defecto con la clase `.hidden` de `assets/css/login-widget.css`) que se activa al pulsar cualquier elemento `[data-open-login]` (boton "Portal Staff" en el nav de escritorio, "Ingresar" en el nav movil, o el enlace del footer).

El formulario interno del modal **no cambia su contrato tecnico**, salvo el endpoint de procesamiento que ahora vive en la capa `api/`:
- `action="api/login.php"`, `method="post"` (ruta relativa desde la raiz, donde vive `index.php`).
- Campo oculto `csrf_token` generado por `csrf_token()` (incluido vía `require_once __DIR__ . '/api/csrf.php';`).
- IDs preservados para `assets/js/login.js`: `#loginForm`, `#email`, `#password`, `#emailMessage`, `#passwordMessage`, `#systemMessage`, `#submitBtn`, `#csrf_token`.
- Si `$_GET['error']` esta presente, el modal se abre automaticamente al cargar la pagina y `#systemMessage` muestra el mensaje correspondiente con clase `.error`.

De forma analoga, `legal/firma.php` renderiza el formulario de reglamentos con `action="../api/signature.php"` (ruta relativa desde `legal/`), y el procesador (`api/signature.php`) redirige de vuelta a `../legal/firma.php?error=mismatch`, `../index.php?error=csrf` o `../dashboard/index.php` segun el resultado.

`assets/css/login-widget.css` aisla los estilos del panel de acceso (variables `--deep-sea-blue`, `--pacific-turquoise`, `.login-shell`, `.field-wrap`, etc.) sin interferir con las clases Tailwind de la Landing, evitando que `main.css` (usado por `troll.php`, `legal/firma.php` y `dashboard/`) sobreescriba el `body` de la pagina publica.

---

## 9. CHECKLIST DE BLINDAJE ("ESTANDAR ORO") — RESUMEN EJECUTIVO

- [x] Conexion a base de datos vía PDO Singleton (`config/Database.php`).
- [x] `PDO::ATTR_EMULATE_PREPARES => false` + sentencias preparadas en **todos** los accesos a datos.
- [x] Tokens CSRF generados por sesion y validados con `hash_equals()` en cada formulario POST.
- [x] Passwords con `PASSWORD_BCRYPT` (`cost => 12`), verificadas con `password_verify()`.
- [x] Troll Mode por patrones (`mh_guard_request`) en `api/login.php` y `api/signature.php`.
- [x] Troll Mode por intentos fallidos (`failed_attempts >= 5` → `status = 'Troll_Mode'`).
- [x] `seguridad.log` con registro de IP, URI, razon y payload.
- [x] `.env` fuera de codigo fuente versionado, leido solo por `config/Database.php`.
- [x] `session_regenerate_id(true)` tras login exitoso.
- [x] Gate de firma legal obligatoria antes de acceso al Dashboard.
- [x] Capa `api/` centralizada para toda mutacion de estado, con rutas relativas (`../`) en todos los `Location:`.
- [ ] Politica de permisos `755`/`644`/`600` aplicada en el servidor de produccion (pendiente de verificacion en despliegue real).

---

## 10. FIX DEL ERROR 404 DE REDIRECCION (RUTAS RELATIVAS EN SUBCARPETA)

### 10.1 Causa raiz

Media HUB se ejecuta en XAMPP bajo una subcarpeta (`http://localhost/MediaHUB/`), **no** en la raiz del servidor web (`htdocs/`). El codigo original usaba headers de redireccion **absolutos** (`Location: /index.php`, `Location: /dashboard/index.php`, `Location: /legal/firma.php`, `Location: /troll.php`). Un path que empieza con `/` se resuelve contra la raiz del **dominio**, no contra la raiz del proyecto — por lo tanto `/dashboard/index.php` apuntaba a `htdocs/dashboard/index.php` (fuera de `MediaHUB/`), produciendo un **404**.

### 10.2 Solucion adoptada

Todos los `header('Location: ...')` se reescribieron como **rutas relativas** (`../`), calculadas segun la profundidad real del archivo que emite la redireccion:

| Archivo (profundidad) | Raiz del proyecto | Ejemplos de `Location:` |
|---|---|---|
| `index.php`, `troll.php` (raiz) | `.` | `dashboard/index.php`, `legal/firma.php` |
| `api/*.php` (nivel 1) | `..` | `../index.php`, `../dashboard/index.php`, `../legal/firma.php`, `../troll.php` |
| `legal/firma.php` (nivel 1) | `..` | `../index.php`, `../dashboard/index.php` |
| `dashboard/index.php` (nivel 1) | `..` | `../index.php`, `../legal/firma.php`, `../api/logout.php` |

**Regla de Inmunidad #4:** ningun `header('Location: ...')` debe comenzar con `/`. Todo redireccionamiento interno usa rutas relativas (`../` segun la profundidad del archivo) o rutas relativas simples sin prefijo (`dashboard/index.php` desde la raiz). Esto garantiza portabilidad entre entornos (subcarpeta local vs. dominio raiz en produccion), siempre que la estructura de carpetas relativa entre `api/`, `legal/`, `dashboard/` e `index.php` se mantenga intacta.

### 10.3 `api/signature.php`: procesador de firma digital

El antiguo `legal/firma.php` mezclaba renderizado (GET) y procesamiento (POST) en un solo archivo. Se separo el procesamiento a `api/signature.php`, siguiendo el mismo patron que `api/login.php`:

1. `session_start()` → si no hay `$_SESSION['user_id']` → `Location: ../index.php`.
2. Si la peticion no es POST → `Location: ../legal/firma.php`.
3. `mh_guard_request($_POST, 'legal_firma')` → Troll Mode por patrones.
4. `csrf_validate(...)` falla → `Location: ../index.php?error=csrf`.
5. Por cada documento legal pendiente: verifica `ack[doc_id]` marcado **y** que `signature_name` (case-insensitive) coincida con `$_SESSION['full_name']`.
6. Si algun documento no cumple → `Location: ../legal/firma.php?error=mismatch`.
7. Si todo es correcto → `UPDATE user_legal_signatures SET signed = 1, signed_at = NOW(), ip_address = :ip WHERE user_id = :user_id AND signed = 0` → `Location: ../dashboard/index.php`.

`legal/firma.php` queda como **vista pura**: renderiza el formulario con `action="../api/signature.php"` y traduce `$_GET['error']` (`mismatch`, `csrf`) a mensajes legibles para el usuario.

---

## 11. DEPURACION PRE-PUSH: ARCHIVOS QUE NUNCA DEBEN LLEGAR A PRODUCCION

Los siguientes scripts existen **solo para desarrollo/diagnostico local** en XAMPP y exponen operaciones peligrosas (reconstruccion de base de datos, alta de superadmin, diagnostico de conexion). **Nunca deben subirse al repositorio remoto ni desplegarse a produccion**:

| Archivo | Riesgo si llega a produccion |
|---|---|
| `genesis.php` | Script de reconstruccion masiva de la base de datos — permitiria a un atacante recrear/vaciar tablas. |
| `setup_superadmin.php` | Script de alta inicial de superadministrador — permitiria crear cuentas con privilegios totales. |
| `db_test.php` | Panel de diagnostico de ingeniería — expone metadatos de conexion y estructura de la base de datos. |
| `test_hub_connection.php` | Script de prueba de conexion — puede revelar configuracion de `.env` en mensajes de error. |

### 11.1 Doble blindaje aplicado

1. **`.gitignore`** (raiz del proyecto) excluye estos archivos de control de versiones, ademas de `.env` y `seguridad.log`.
2. **`.github/workflows/deploy.yml`** excluye explicitamente estos mismos archivos (mas `knowledge/**`, que contiene documentacion interna y propuestas de diseño que no deben viajar al servidor de produccion) de la sincronizacion FTP, como salvaguarda adicional aunque alguno de ellos llegara a versionarse por error.

### 11.2 Checklist antes de `git push` a `main`

- [ ] Verificar que `genesis.php`, `setup_superadmin.php`, `db_test.php` y `test_hub_connection.php` **no esten staged** (`git status`).
- [ ] Confirmar que `.env` no aparece en `git status` (debe estar ignorado).
- [ ] Confirmar que `seguridad.log` no aparece en `git status` (debe estar ignorado).
- [ ] Revisar que ningun archivo nuevo bajo `api/` contenga credenciales hardcodeadas — toda conexion debe pasar por `Database::getInstance()`.
- [ ] Revisar que ningun `header('Location: ...')` nuevo comience con `/` (ver Regla de Inmunidad #4).
- [ ] Ejecutar `php -l` sobre cualquier archivo PHP modificado antes de commitear.

---

*Este documento describe el estado de blindaje vigente a Fase 1 (incluyendo la reestructuracion `api/`). Cualquier nuevo endpoint que reciba input de usuario debe pasar por `csrf_validate()` + `mh_guard_request()` antes de tocar la capa de datos, vivir dentro de `api/`, y usar exclusivamente rutas de redireccion relativas.*

---

## 12. FASE 2: MODULOS OPERATIVOS (CRUD, CHECKLISTS Y CORREO TRANSACCIONAL)

### 12.1 Nuevos endpoints JSON (`api/`)

| Endpoint | Acciones | Notas |
|---|---|---|
| `api/clients.php` | `list`, `create`, `update`, `deactivate`, `activate` | Baja logica via `clients.is_active` (columna aditiva, ver `database/migration_fase2.sql`). |
| `api/programs.php` | `list`, `create`, `update`, `deactivate`, `activate` | Al crear un programa, dispara `mh_mail_new_program()` al correo del cliente (best-effort). |
| `api/checklist.php` | `get` (GET), `toggle` (POST) | Checklist de 3 fases (Antes/Durante/Despues) por llamado. Acceso: Administrador/Lider_Proyecto o staff asignado al llamado. La firma digital del check es `checked_by` + `checked_at` en `call_checklist_progress`. |
| `api/mailer.php` | n/a (helpers) | `mh_send_mail()`, `mh_email_layout()` y 4 plantillas: `mh_mail_welcome()`, `mh_mail_new_program()`, `mh_mail_call_confirmed()`, `mh_mail_password_reset()`. Paleta: `#022D53` (layout) / `#00BFB2` (acentos). Envio best-effort: si `mail()` falla, se registra en `mail.log` sin interrumpir el flujo. |
| `api/forgot_password.php` | POST (form, no JSON) | Genera token aleatorio, guarda `hash_hmac('sha256', token, CSRF_SECRET)` en `password_resets` con expiracion de 1 hora. No revela si el correo existe. |
| `api/reset_password.php` | POST (form, no JSON) | Valida token contra `password_resets` (no usado, no expirado), actualiza `users.password_hash`, invalida todos los tokens pendientes del usuario. |

### 12.2 Migracion aditiva: `database/migration_fase2.sql`

Ejecutar una sola vez sobre `tecnidepot_mediahub_db`. Agrega:
- `clients.is_active` (TINYINT, default 1) — gestion de estados de Clientes Jornal.
- `checklist_templates` — catalogo maestro de items por fase (Antes/Durante/Despues), con seed data.
- `call_checklist_progress` — progreso por llamado (`call_id` + `template_id` unicos), con firma digital `checked_by`/`checked_at`.
- `password_resets` — tokens de recuperacion de contrasena (hash HMAC, `expires_at`, `used`).

### 12.3 Reglas de negocio nuevas en `api/agenda.php`

- `PUT action=update_status` con `status = 'Confirmado'` exige `calls.advance_paid = 1` (422 si no), ademas de la regla previa para `assign_staff`.
- `PUT action=verify_advance` con `advance_paid = true` dispara automaticamente el correo "Fecha Confirmada + Personal Reservado" (`mh_mail_call_confirmed()`) al cliente del programa, incluyendo el staff ya asignado al llamado.

### 12.4 `.env`: claves de correo (aditivas, sin credenciales SMTP)

```
MAIL_FROM_NAME="Media HUB Audiovisual Studio"
MAIL_FROM_ADDRESS=no-reply@mediahubbcs.com
```

### 12.5 Dashboard (`dashboard/index.php` + `assets/js/dashboard.js`)

- Incluye `csrf_token()` y lo expone como `window.MH_CSRF` para las peticiones `fetch` (header implicito vía `csrf_token` en el body JSON).
- Modulos: Agenda (con control de colisiones y badges de anticipo), Clientes/Programas (solo Administrador/Lider_Proyecto), Checklist operativo, Inventario/Flota (check-in/check-out) y Mis Tareas.
- Toggle de tema claro/oscuro persistido en `localStorage` (`mh-theme`), vía clase `.light-mode` en `<html>` (ver `assets/css/dashboard.css` seccion Fase 2).
