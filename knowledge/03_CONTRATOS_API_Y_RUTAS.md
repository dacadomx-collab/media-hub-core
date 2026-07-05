# 03 · CONTRATOS API Y RUTAS — MEDIA HUB

> **Version:** 2.0 (Reescritura total — reemplaza contenido generico no relacionado con el proyecto)
> **Clasificacion:** Ley Suprema — Pilar Canonico — Contratos JSON Inmutables
> **Fuente de verdad:** codigo real en `api/*.php` (auditado linea por linea al redactar este documento)

> Estos contratos son inmutables (Mandamiento 5, ver `01_LEY_Y_PROTOCOLOS_DE_VUELO.md`). Ningun endpoint altera sus propiedades JSON sin actualizar este documento. Todo endpoint de mutacion rechaza cargas invalidas con `422` antes de tocar la base de datos.

---

## 0. NOTA DE CONSOLIDACION

La version anterior de este archivo describia contratos de un producto SaaS ajeno a Media HUB (Scanner de dominios, Synaptic Core, DCD Engine/Extractor/Analyzer, tokens de partners). Ese contenido no correspondia a ningun archivo real de este repositorio y fue **descartado por completo**. Este documento reemplaza esa version con los contratos reales de `api/*.php`, verificados contra el codigo fuente.

---

## 📡 PROTOCOLO BASE DE INTEGRACION

- **Intercambio:** JSON UTF-8 en el cuerpo de la peticion (`php://input`, decodificado con `mh_read_json_body()`). Los filtros de listados (`GET`) via query string.
- **Estructura estandar de respuesta** (`api/response.php` → `mh_json_response()`):
  ```json
  { "status": "success|error", "message": "string", "data": { ... } }
  ```
- **Sesion:** `session_start()` en cada endpoint. `mh_require_session()` responde `401` JSON (sin redirect) si `$_SESSION['user_id']` no existe — estos endpoints se consumen via `fetch()` desde `dashboard/index.php`, nunca por navegacion directa.
- **CSRF:** `mh_require_csrf($payload)` valida `payload.csrf_token` o el header `X-CSRF-Token` contra `$_SESSION['csrf_token']` con `hash_equals()`. Obligatorio en todo `POST`/`PUT`.
- **Troll Mode:** `mh_guard_request($payload, 'contexto')` se ejecuta antes de cualquier `INSERT`/`UPDATE`, escaneando el payload contra `MH_ATTACK_PATTERNS` (ver `04_ARQUITECTURA_Y_BLINDAJE.md` §4).
- **Rutas:** todo endpoint vive en `api/` (un nivel bajo la raiz) y se consume como `api/<archivo>.php?action=<accion>`.

---

## 🛠️ CONTRATO 1 — Agenda y Control de Colisiones (`api/agenda.php`)

**Auth:** Sesion requerida. `create_call`, `assign_staff`, `update_status` → roles `Super_admin`/`Admin`/`Lider_Proyecto`. `verify_advance` → solo `Super_admin`/`Admin`. `list` → cualquier rol autenticado, **con alcance restringido para `Conductor`** (ver abajo).

### GET `?action=list`
Filtros opcionales en query string: `from`, `to` (fecha `YYYY-MM-DD`), `location`, `program_id`.

**Alcance por rol (Fase 5.5):** si `currentUser.role === 'Conductor'`, `program_id` es **obligatorio** y debe ser un show nativo cuyo `conductor_user_id` sea el usuario en sesión (`403` si no) — la respuesta **omite** `advance_required_pct`, `advance_paid`, `total_amount` y `notes` para ese rol. El `JOIN` con `clients` es `LEFT` (no `INNER`): los llamados de shows nativos (`programs.client_id NULL`) ya no se ocultan — antes de Fase 5.5 eran invisibles para cualquier consulta.

**Response 200:**
```json
{
  "status": "success",
  "message": "Agenda cargada.",
  "data": {
    "calls": [
      {
        "id": 1, "title": "string", "location": "Estudio 5 de Mayo",
        "call_date": "YYYY-MM-DD", "start_time": "HH:MM:SS", "end_time": "HH:MM:SS",
        "status": "Pendiente|Confirmado|Cancelado|Completado",
        "advance_required_pct": 50.00, "advance_paid": 0,
        "total_amount": 0.00, "notes": "string|null",
        "program_id": 1, "program_name": "string",
        "client_name": "string", "client_company": "string",
        "assignments": [{ "call_id": 1, "user_id": 1, "full_name": "string", "role": "string", "task_description": "string|null", "status": "string" }]
      }
    ]
  }
}
```

### POST `?action=create_call`
**Payload:** `{ "program_id": int, "title": "string", "location": "enum MH_VALID_LOCATIONS", "call_date": "YYYY-MM-DD", "start_time": "HH:MM", "end_time": "HH:MM", "total_amount": number|null, "notes": "string|null" }`

**Reglas de Piedra:**
1. Rechaza si faltan `program_id`/`title`/`call_date`/`start_time`/`end_time` (`422`).
2. `location` debe estar en `MH_VALID_LOCATIONS` (`Estudio 5 de Mayo`, `Locacion Externa`, `Van Terrestre`, `Embarcacion Maritima`).
3. **Algoritmo de Inmunidad ante Sobre-Reservas:** antes del `INSERT`, ejecuta la consulta de colision (`location` + `call_date` + solape de `start_time`/`end_time` contra llamados no cancelados). Si hay conflicto → `409`.
4. Éxito → `201` con `{ "id": int }`.

### POST `?action=assign_staff`
**Payload:** `{ "call_id": int, "user_id": int, "task_description": "string|null" }`

**Reglas de Piedra:**
1. `404` si el llamado no existe. `422` si `status = 'Cancelado'`.
2. **Compuerta financiera obligatoria:** si `calls.advance_paid != 1`, responde `422` con el monto exacto calculado (`total_amount * advance_required_pct / 100`): *"No se puede asignar personal: el anticipo del 50% ($X MXN) aun no ha sido verificado para este llamado."*
3. `409` si el usuario ya está asignado a ese llamado (constraint unico `call_id, user_id`).

### PUT `?action=update_status`
**Payload:** `{ "call_id": int, "status": "enum MH_VALID_CALL_STATUS" }`

**Reglas de Piedra:** pasar a `status = 'Confirmado'` exige `advance_paid = 1` (`422` si no). `404` si el llamado no existe.

### PUT `?action=verify_advance`
**Payload:** `{ "call_id": int, "advance_paid": bool }`

**Reglas de Piedra:** al pasar `advance_paid` a `true`, dispara automáticamente `mh_mail_call_confirmed()` (Contrato 5) al `email` del cliente del programa, best-effort (no bloquea la respuesta si el correo falla).

---

## 🛠️ CONTRATO 2 — Clientes Jornal (`api/clients.php`)

**Auth:** `create`/`update` → `Super_admin`/`Admin`/`Lider_Proyecto`. `deactivate`/`activate` → solo `Super_admin`/`Admin`.

| Accion | Metodo | Descripcion |
| :--- | :--- | :--- |
| `list` | GET | Listado de clientes con conteo de programas asociados |
| `create` | POST | Alta de cliente (`full_name`, `email`, `phone`, `company`) |
| `update` | PUT | Edicion de datos de contacto |
| `deactivate` | POST | Baja lógica → `clients.is_active = 0` |
| `activate` | POST | Reactivación → `clients.is_active = 1` |

**Contrato base de respuesta:** `{ status, message, data }` en todas las acciones, códigos `200`/`201`/`404`/`422` según corresponda. Ver `02_CODEX_Y_SCHEMA_MAESTRO.md` §2.4 para el esquema completo de `clients`.

---

## 🛠️ CONTRATO 3 — Programas Recurrentes y Shows Nativos (`api/programs.php`)

**Auth:** `list`/`create`/`update`/`deactivate`/`activate` → sesión de staff (`Super_admin`/`Admin`/`Lider_Proyecto`). `public_catalog` → **público, sin sesión** (ver nota abajo).

| Accion | Metodo | Auth | Descripcion |
| :--- | :--- | :--- | :--- |
| `public_catalog` | GET | **Público** | Catálogo de shows nativos activos (`is_native_show=1 AND is_active=1`) para `index.php` §`#programas`. Solo columnas públicas: `id`, `name`, `catalog_description`, `logo_url`, `public_social_links` |
| `list` | GET | Staff | Listado interno de programas, filtro opcional `client_id` (no incluye shows nativos por el `INNER JOIN clients` — ver nota) |
| `create` | POST | Staff | Alta de programa (`client_id`, `name`, `description`) — dispara `mh_mail_new_program()` (Contrato 5) si el cliente tiene `email` |
| `update` | PUT | Staff | Edición de nombre/descripción/cliente asociado |
| `deactivate` / `activate` | POST | Staff | Baja/reactivación lógica vía `programs.is_active` |

> **Nota Fase 5:** `public_catalog` es la **única** vía pública para leer `programs`. Se agregó porque `list` requiere sesión y hace `INNER JOIN clients` (excluye filas con `client_id = NULL`, es decir, excluye shows nativos) — `programs.php` gestiona ambos tipos de fila (Contrato 2/§2.5 del Codex) pero cada acción sirve a una audiencia distinta.

### GET `?action=public_catalog` — Response 200
```json
{ "status": "success", "data": { "programs": [
  { "id": 1, "name": "string", "catalog_description": "string|null", "logo_url": "string|null", "public_social_links": "string|null (JSON-encoded)" }
] } }
```

### GET `?action=list_native` — Fase 5.2 (Staff: `Super_admin`/`Admin`)
Listado completo de shows nativos con datos del Conductor (`conductor_name`, `conductor_email`, `conductor_status`) vía `LEFT JOIN users`.

### POST `?action=create_native` — Fase 5.2/5.3 (Staff: `Super_admin`/`Admin`)
**⚠️ Único endpoint de este pilar que NO usa JSON** — `multipart/form-data` (requiere subir archivo). Payload de formulario:

```
name (requerido), catalog_description,
public_social_links_json (JSON string de [{platform,url}, ...] — Hito 3 Fase 5.3, arreglo dinamico, 0..N filas),
schedule_days[] (subset de Lunes..Domingo — OJO: el checkbox HTML debe llamarse "schedule_days[]", no "schedule_days"),
schedule_start_time, schedule_end_time,
logo (archivo, opcional), csrf_token,
conductor_full_name / conductor_email (ambos o ninguno — Conductor Inline, Fase 5.3: SIN campo de ID manual, el email es el identificador)
```

**Reglas de Piedra:**
1. Logo: validado por **MIME real** (`finfo` con fallback a `mime_content_type()`, no la extensión declarada), máx. 5 MB, whitelist PNG/JPEG/WEBP, renombrado por hash SHA-256 antes de persistir en `/uploads/` (nunca el nombre original del cliente).
2. `production_schedule` se arma como JSON (`{days, start_time, end_time}`) — `null` si no se envía nada.
3. `public_social_links` (columna JSON real) recibe `json_encode()` del arreglo `[{platform,url}]` ya parseado y validado (`FILTER_VALIDATE_URL` por cada `url` no vacía) — nunca el string crudo del formulario.
4. Conductor Inline (Fase 5.3 — Identidad Email-Only): si se llena, reutiliza `mh_provision_pending_user()` pasando el **email como `user_code`** (mismo patrón que `api/users.php?action=create`, ver `api/user_provisioning.php`) **dentro de la misma transacción** que el `INSERT` del show — si falla cualquiera de los dos, ambos se revierten y el logo ya subido se borra (`unlink`).
5. La Plantilla 1 al Conductor se despacha **después** del commit (best-effort, nunca dentro de la transacción).
6. **Puente forense (Fase 5.3):** el catch externo es `Throwable` (no solo `PDOException`) — con `?debug_token=` válido revela `get_class($e)` + mensaje + archivo:línea; sin token, degrada a 500 genérico. Mismo patrón que `api/login.php`.

### GET `?action=my_native_show` — Fase 5.2 (rol `Conductor`)
Devuelve únicamente los shows nativos donde `conductor_user_id = currentUser.user_id`. Ningún otro rol puede leer esta acción.

---

## 🛠️ CONTRATO 4 — Checklist Operativo (`api/checklist.php`)

**Auth:** Sesión requerida. Acceso: `Super_admin`/`Admin`/`Lider_Proyecto` o staff asignado al llamado (`call_assignments`).

### GET `?action=get&call_id=ID`
Devuelve el checklist completo de 3 fases (Antes/Durante/Después) con el progreso registrado en `call_checklist_progress` para ese llamado.

### POST `?action=toggle`
**Payload:** `{ "call_id": int, "template_id": int }` (o `item_key` equivalente, ver `08_CHECKLIST_MAESTRO_BACKEND.md` §3 para el catálogo de `item_key` por fase).

**Reglas de Piedra:** cada marcado registra `checked_by` (usuario de sesión) y `checked_at` (`NOW()`) — es la firma digital del progreso operativo. Clave única `(call_id, template_id)` evita duplicados.

---

## 🛠️ CONTRATO 5 — Inventario y Flota (`api/inventory.php`)

**Auth:** `list`/`log`/`checkout`/`checkin` → sesión requerida (sin rol específico, salvo la regla de asignación abajo). `set_maintenance` → `Super_admin`/`Admin`/`Lider_Proyecto`.

### GET `?action=list`
```json
{ "status": "success", "data": { "inventory_items": [...], "fleet_vehicles": [...] } }
```

### GET `?action=log`
Filtros opcionales: `asset_type` (`Inventario`|`Vehiculo`), `asset_id`. Devuelve hasta 200 filas de `checkinout_log` con `staff_name`/`staff_role` unidos desde `users`.

### POST `?action=checkout`
**Payload:** `{ "asset_type": "Inventario|Vehiculo", "asset_id": int, "call_id": int|null, "condition_notes": "string|null" }`

**Reglas de Piedra:**
1. `404` si el activo no existe. `409` si `status != 'Disponible'`.
2. Si `call_id` viene informado y el rol de sesión no es `Super_admin`/`Admin`/`Lider_Proyecto`, exige que el usuario esté en `call_assignments` de ese llamado (`403` si no).
3. Transacción atómica: `UPDATE` de estatus a `'En Uso'` + `INSERT` append-only en `checkinout_log` (`action = 'Check-Out'`).

### POST `?action=checkin`
**Payload:** `{ "asset_type": "Inventario|Vehiculo", "asset_id": int, "call_id": int|null, "condition_notes": "string|null", "damaged": bool }`

**Reglas de Piedra:**
1. `409` si `status != 'En Uso'`. `422` si `damaged = true` y `condition_notes` viene vacío.
2. Si `damaged = true` → nuevo estatus `'Mantenimiento'`; si no → `'Disponible'`. Transacción atómica igual que `checkout`.

### POST `?action=set_maintenance`
**Payload:** `{ "asset_type": "Inventario|Vehiculo", "asset_id": int, "notes": "string|null" }` → fuerza `status = 'Mantenimiento'`.

---

## 🛠️ CONTRATO 6 — Organigrama de Usuarios (`api/users.php`)

| Accion | Metodo | Auth | Descripcion |
| :--- | :--- | :--- | :--- |
| `me` | GET | Sesión | Perfil propio + checklist de obligaciones por llamado |
| `list` | GET | `Super_admin`/`Admin`/`Lider_Proyecto` | Organigrama completo |
| `create` | POST | `Super_admin`/`Admin` | Alta de staff — **payload: `full_name`, `email`, `role` únicamente (Fase 5.3, Identidad Email-Only: SIN `user_id` manual — internamente `users.user_id = email`)**. Matriz Fase 5: solo `Super_admin` crea `Admin`; `Admin` crea hacia abajo (`Team`, `Conductor`, roles operativos), nunca `Super_admin` (`403` si lo intenta) |
| `update` | PUT | `Super_admin`/`Admin` | Edición de perfil/rol/estatus |
| `update_self` | PUT | Sesión (cualquier rol) | Edición de datos propios |
| `deactivate` | POST | `Super_admin`/`Admin` | Baja lógica → `users.status = 'Suspendido'` |

Roles válidos (`MH_VALID_ROLES`, Fase 5): `Super_admin`, `Admin`, `Lider_Proyecto`, `Staff_Tecnico`, `Lider_Logistica`, `Cliente`, `Team`, `Conductor` (ver `02_CODEX_Y_SCHEMA_MAESTRO.md` §2.1/§2.1.1 — renombre `Administrador → Admin` y jerarquía completa).

**Botón "Resetear Contraseña" (Hito 2, Fase 5.3):** el panel admin dispara `POST api/forgot_password.php` (Contrato 8) a nombre del Administrador, reutilizando la misma sesión PHP (mismo `csrf_token`) — no es una acción nueva de `users.php`.

---

## 🛠️ CONTRATO 7 — Motor de Correos (`api/mailer.php`, `api/smtp_mailer.php`)

No es un endpoint HTTP — es una librería de helpers consumida internamente por `agenda.php`, `programs.php`, `user_provisioning.php` y los flujos de password reset.

**Transporte (Fase 5.3):** `mh_send_mail()` intenta primero SMTP autenticado real (`api/smtp_mailer.php::mh_smtp_send()`, cliente SMTP nativo sin Composer/PHPMailer, `AUTH LOGIN` sobre `ssl://` puerto 465) si `.env` define `MAIL_HOST` + `MAIL_USER` + `MAIL_PASS` completos. Si falta cualquiera, degrada automáticamente a `mail()` nativo de PHP (comportamiento previo). Envío **best-effort** en ambos casos: los fallos se registran en `error_log`/`mail.log` sin interrumpir el flujo de negocio que lo invocó.

> **⚠️ Pendiente de autorización — `MAIL_PASS` vacío:** `.env` tiene `MAIL_HOST`/`MAIL_USER`/`MAIL_PORT`/`MAIL_PROTOCOL` pero **no** la contraseña SMTP real. Hasta que se complete, todo envío sigue cayendo en el `mail()` nativo (sin cambio de comportamiento). El botón "Simular Flujo Onboarding completo" del panel admin ya ejerce las Plantillas 1 y 2 reales de punta a punta en cuanto el SMTP quede completo.

| Función | Disparada por | Contenido |
| :--- | :--- | :--- |
| `mh_mail_welcome(fullName, userCode, email, tempPassword)` | Alta de staff (`users.php?action=create`) | Bienvenida + credenciales temporales |
| `mh_mail_new_program(clientName, programName, programDescription)` | `programs.php?action=create` | Notificación de alta de programa al cliente |
| `mh_mail_call_confirmed(...)` | `agenda.php?action=verify_advance` (al pasar a `true`) | Fecha confirmada + personal reservado |
| `mh_mail_password_reset(fullName, resetUrl)` | `forgot_password.php` | Enlace seguro de recuperación |

Paleta institucional de las plantillas HTML: `#022D53` (layout) / `#00BFB2` (acentos), consistente con `00_ADN_Y_FILOSOFIA.md` §5.

---

## 🛠️ CONTRATO 8 — Autenticación y Sesión (`api/login.php`, `logout.php`, `signature.php`, `forgot_password.php`, `reset_password.php`)

Estos endpoints **no** siguen el contrato JSON `{status, message, data}` — son procesadores de formulario (vistas server-rendered) que responden con `header('Location: ...')` relativo. Documentados en detalle en `04_ARQUITECTURA_Y_BLINDAJE.md` §5 (login) y §10.3 (firma digital). Resumen de responsabilidad:

| Archivo | Método | Función |
| :--- | :--- | :--- |
| `api/login.php` | POST | CSRF + Troll Mode + `password_verify()` + gate de firmas legales pendientes |
| `api/logout.php` | GET/POST | Destruye la sesión, redirige a `../index.php` |
| `api/signature.php` | POST | Procesa la firma de los 4 reglamentos (`user_legal_signatures`) |
| `api/forgot_password.php` | POST | Genera token HMAC de recuperación, sin revelar si el correo existe |
| `api/reset_password.php` | POST | Valida token (no usado, no expirado), actualiza `password_hash`, invalida tokens pendientes |

---

## 🛠️ CONTRATO 10 — Guest Onboarding (`api/guest_links.php`, `api/guest_submissions.php`)

**Tablas:** `guest_invite_links`, `guest_submissions` (Fase 5, ver `02_CODEX_Y_SCHEMA_MAESTRO.md` §2.11/§2.12).

### `api/guest_links.php`

**Auth:** Sesión requerida. `create` → roles `Super_admin`, `Admin`, `Conductor` (un `Conductor` solo puede generar enlaces para `programs` donde `conductor_user_id = currentUser.id`).

#### POST `?action=create`
**Payload:** `{ "program_id": int, "call_id": int|null }` — `call_id` es **opcional** (Fase 5.5): ata el enlace a un llamado específico de la agenda del show, para que `guest_form.php` muestre fecha/hora/locación reales en vez del texto genérico.

**Reglas de Piedra:**
1. `422` si `program_id` no existe o no es `is_native_show = 1`.
2. `403` si el rol es `Conductor` y `programs.conductor_user_id != currentUser.id`.
3. `422` si `call_id` se proporciona pero `calls.program_id != program_id` (el llamado no pertenece a ese show).
4. Genera `token` con `bin2hex(random_bytes(32))`, `click_count = 0`, `status = 'Activo'`.
5. Respuesta `201`: `{ "id": int, "token": "string", "public_url": "string" }`.

#### GET `?action=list&program_id=ID`
Lista los enlaces generados para un show nativo (auditoría del `Conductor`/`Admin`). Incluye `status`, `click_count`, `created_at`, datos del llamado atado (`call_title`, `call_date`, `start_time`, `end_time` vía `LEFT JOIN calls`) y un objeto `completion` calculado (Fase 5.5 — Panel de Auditoría OBS):

```json
{ "completion": { "required_done": 2, "required_total": 2, "optional_done": 3, "optional_total": 5, "has_submission": true } }
```

### `api/guest_submissions.php` — Consumo público (sin sesión — el invitado no tiene cuenta)

**Auth:** Ninguna — validación exclusivamente por posesión del `token` en la URL. Sujeto igualmente a `mh_guard_request()` (Troll Mode) y CSRF de formulario público (token de un solo uso por sesión anónima).

#### GET `?token=...` — Consulta del estado del enlace + datos públicos del show

**Reglas de Piedra:**
1. `404` si el token no existe. `410 Gone` si `status = 'Expirado'` (o `click_count >= max_clicks`) → el frontend redirige a la Landing pública de Media HUB.
2. Si `click_count = 0`: incrementa a `1`, devuelve el formulario **vacío** + datos públicos del programa (`name`, `catalog_description`, `logo_url`).
3. Si `click_count = 1`: incrementa a `2`, devuelve el formulario **precargado** desde `guest_submissions` (si existe) para edición.
4. Si `click_count` alcanza `2` en este acceso (o ya era `2`): tras servir la respuesta, marca `status = 'Expirado'`, `expired_at = NOW()` — el **siguiente** acceso ya cae en la regla 1.

#### POST `?token=...` — Guardado parcial (crea o actualiza)

**Payload:** `{ "full_name": "string", "title_position": "string", "social_links": object|null, "whatsapp": "string|null", "website": "string|null", "email": "string|null", "invite_message": "string|null" }`

**Reglas de Piedra:**
1. `410 Gone` si el enlace ya está `Expirado`. `422` si faltan `full_name`/`title_position`.
2. `INSERT ... ON DUPLICATE KEY UPDATE` sobre `guest_submissions` (clave única `link_id`) — click 1 crea la fila, click 2 la actualiza. Nunca hay más de una fila por enlace.
3. Respuesta: `{ "status": "success", "message": "Datos guardados.", "data": { "click_count": int, "clicks_restantes": int } }`.
4. **Nunca** expone `qa_notes` (uso interno) en la respuesta pública.

### Estándar de errores — Guest Onboarding

| HTTP | Uso |
| :--- | :--- |
| `404` | Token inexistente |
| `410` | Enlace expirado (3er clic consumido) — el único código de este contrato que no es del estándar global de la Sección "Estándar Global" abajo |
| `422` | Payload inválido o `program_id` no es show nativo |

---

## 🛠️ CONTRATO 9 — Centro de Comando Ejecutivo (`api/finance.php`)

**Auth:** solo `Super_admin`, `Admin` (rol renombrado en Fase 5 — ver nota de reconciliación abajo).

### GET `?action=kpis`
```json
{
  "status": "success",
  "data": {
    "monthly_revenue": 0.00,
    "projected_profit": 0.00,
    "iva_accrued": 0.00,
    "pending_advances": 0.00,
    "studio_hours": 0.0,
    "fleet_maintenance": [{ "id": 1, "name": "string", "type": "string" }],
    "period": { "start": "YYYY-MM-01", "end": "YYYY-MM-DD" }
  }
}
```

Constantes de negocio: `MH_PROJECTED_PROFIT_MARGIN = 0.40` (utilidad neta proyectada = 40% de ingresos del mes), `MH_IVA_RATE = 0.16` (IVA de ley). Ver `05_MATRIZ_FINANCIERA_Y_VENTAS.md` para el detalle comercial completo.

---

## ⚠️ NOTA DE RECONCILIACION — MIGRACION DE ROLES (FASE 5)

La migración `database/migration_fase5_rbac_and_native_shows.sql` renombró `'Administrador' → 'Admin'` en `users.role`. Todo el código de `api/*.php` que comparaba contra el string literal `'Administrador'` fue actualizado en esta misma sesión a `['Super_admin', 'Admin']` (`Super_admin` hereda todos los permisos de `Admin`). Archivos corregidos: `agenda.php`, `clients.php`, `programs.php`, `checklist.php`, `inventory.php`, `users.php`, `finance.php`. Antes de esta corrección, ningún usuario podía pasar las verificaciones de rol administrativo tras la migración — quedó documentado como incidente resuelto, no como diseño original.

---

## 🌐 ESTANDAR GLOBAL — CODIGOS HTTP DEL SISTEMA

| Codigo | Uso |
| :--- | :--- |
| `200` | OK — operación exitosa con datos |
| `201` | Created — recurso creado (llamado, asignación, cliente, programa) |
| `401` | Unauthorized — sin sesión activa (`mh_require_session()`) |
| `403` | Forbidden — rol insuficiente, o staff sin asignación al llamado, o CSRF inválido |
| `404` | Not Found — recurso no encontrado (llamado, activo, usuario) |
| `405` | Method Not Allowed — acción/método no soportado por el endpoint |
| `409` | Conflict — colisión de agenda, activo ya asignado, estatus incompatible |
| `422` | Unprocessable Entity — payload incompleto o regla de negocio no satisfecha (ej. anticipo no verificado) |
| `500` | Internal Server Error — excepción `PDOException` atrapada, registrada en `error_log`, nunca expuesta al frontend |

---

## 🛡️ PATRON CANONICO DE ENDPOINT (Capas obligatorias en `api/*.php`)

```php
<?php
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/response.php';

$currentUser = mh_require_session();          // 401 si no hay sesión
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$pdo = Database::getInstance()->getConnection();

try {
    if ($method === 'POST' && $action === '...') {
        mh_require_role($currentUser, ['Super_admin', 'Admin']);   // 403 si el rol no aplica
        $payload = mh_read_json_body();
        mh_guard_request($payload, 'contexto');             // Troll Mode
        mh_require_csrf($payload);                           // CSRF

        // ... validación 422, lógica de negocio, PDO preparado ...

        mh_json_response('success', 'Mensaje.', [...], 200);
    }
    mh_json_response('error', 'Accion o metodo no soportado.', [], 405);
} catch (PDOException $e) {
    error_log('MH-CORE DB error en ' . basename(__FILE__) . ': ' . $e->getMessage());
    mh_json_response('error', 'Error interno del servidor. Intenta mas tarde.', [], 500);
}
```

Todo endpoint nuevo debe seguir este patrón sin excepción. Antes de crear un endpoint, consultar primero si `api/response.php` ya expone el helper necesario — no reinventar.
