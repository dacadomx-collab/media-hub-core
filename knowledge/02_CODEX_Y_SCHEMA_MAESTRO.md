# 02 · CODEX Y SCHEMA MAESTRO — MEDIA HUB

> **Version:** 1.1 (Fase 5 — RBAC Escalado + Shows Nativos + Guest Onboarding)
> **Clasificacion:** Documento Critico — Fuente de Verdad de Datos
> **Fuente:** `database/schema.sql` + `database/migration_fase5_rbac_and_native_shows.sql` · Motor: **InnoDB** · Charset: **utf8mb4** · Collation: **utf8mb4_general_ci**
> Complementa a `DB_STRUCTURE.md` (amendment Fase 1).

Este documento es el **diccionario de datos** de las 12 tablas activas de Media HUB (9 de Fase 1 + `guest_invite_links`/`guest_submissions` de Fase 5, mas `checkinout_log` ya contada como decima tabla operativa de Fase 1). Ninguna tabla, columna o ENUM nuevo debe crearse sin registrarse aqui primero.

---

## 1. MAPA GENERAL DEL ESQUEMA

```
users ──┬──< user_legal_signatures >── legal_documents
        │
        ├──< calls >── programs ──< clients
        │      │           │
        │      │           └── conductor_user_id (nullable, Fase 5)
        │      └──< call_assignments >── users
        │
        ├──< checkinout_log >── (inventory_items | fleet_vehicles)
        │          └── calls
        │
        └── programs ──< guest_invite_links >── guest_submissions
```

| # | Tabla | Proposito |
|---|---|---|
| 1 | `users` | Organigrama digital y autenticacion (RBAC escalado, Fase 5) |
| 2 | `legal_documents` | Catalogo de los 4 reglamentos inmutables |
| 3 | `user_legal_signatures` | Bitacora de firma digital por usuario/documento |
| 4 | `clients` | Clientes Jornal |
| 5 | `programs` | Programas recurrentes por cliente **o** Shows Nativos de Media HUB (Fase 5) |
| 6 | `calls` | Agenda / llamados con control de colisiones |
| 7 | `call_assignments` | Tareas asignadas al staff por llamado |
| 8 | `inventory_items` | Inventario dinamico de equipo |
| 9 | `fleet_vehicles` | Flota movil (Van / Embarcacion) |
| — | `checkinout_log` | Bitacora Check-In/Check-Out (10ma tabla operativa) |
| 11 | `guest_invite_links` | Enlaces temporales de invitado con TTL de 3 clics (Fase 5) |
| 12 | `guest_submissions` | Datos capturados del invitado por enlace (Fase 5) |

> Nota: el bloque de seed data agrega una decima tabla operativa (`checkinout_log`) que documenta el movimiento de `inventory_items` y `fleet_vehicles`. La Fase 5 (`migration_fase5_rbac_and_native_shows.sql`) agrega las tablas 11 y 12 de forma aditiva, sin alterar las 10 anteriores salvo las columnas nuevas documentadas en `users` y `programs` abajo.

---

## 2. DICCIONARIO DE DATOS POR TABLA

### 2.1 `users` — Organigrama Digital

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `user_id` | VARCHAR(50) UNIQUE | Identificador legible (`admin.root`, `lider.glage`, `staff.gmorales`, `staff.amurillo`, `chofer.logistica1`) |
| `full_name` | VARCHAR(100) | Nombre completo — usado para validar firma digital |
| `email` | VARCHAR(150) UNIQUE | Correo corporativo, usado para login |
| `password_hash` | VARCHAR(255) | Hash Bcrypt (`PASSWORD_BCRYPT`, cost 12) |
| `role` | ENUM | `Super_admin`, `Admin`, `Lider_Proyecto`, `Staff_Tecnico`, `Lider_Logistica`, `Cliente`, `Team`, `Conductor` (default `Staff_Tecnico`) — **actualizado en Fase 5**, ver §2.1.1 |
| `status` | ENUM | `Activo`, `Suspendido`, `Troll_Mode`, `Pendiente` (default `Activo`) — **`Pendiente` agregado en Fase 5.1**: cuenta invitada por correo, sin contrasena real todavia (ver `03_CONTRATOS_API_Y_RUTAS.md` Contrato 6) |
| `failed_attempts` | INT(1) | Contador de intentos fallidos; >= `MH_MAX_FAILED_ATTEMPTS` (5) → `status = 'Troll_Mode'` |
| `last_login` | DATETIME | Ultimo login exitoso |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Seed data (Fase 1, password temporal `MediaHub2026!` para todos):**

| `user_id` | `full_name` | `role` |
|---|---|---|
| `admin.root` | Administrador Media HUB | `Administrador` |
| `lider.glage` | German Lage | `Lider_Proyecto` |
| `staff.gmorales` | Gibran Morales | `Staff_Tecnico` |
| `staff.amurillo` | Antonio Murillo | `Staff_Tecnico` |
| `chofer.logistica1` | Chofer Logistica 1 | `Chofer_Logistica` (legacy, renombrado a `Lider_Logistica` en Fase 4) |

**Altas oficiales (Fase 4, `migration_fase4_roles_seed.sql`, password temporal `MediaHub2026!`):**

| `user_id` | `full_name` | `email` | `role` | `status` | Firmas legales |
|---|---|---|---|---|---|
| `admin.glage` | German Lage | `leolageacadep@gmail.com` | `Administrador` | `Activo` | Precargadas `signed = 1` (acceso directo, sin bloqueo) |
| `logistica.gmorales` | Gibran Morales | `gibranmorales700@gmail.com` | `Lider_Logistica` | `Activo` | Pendientes `signed = 0` (debe firmar en su primer login) |

**Alta oficial (Fase 5, `migration_fase5_rbac_and_native_shows.sql`, password temporal `MediaHub2026!`):**

| `user_id` | `full_name` | `email` | `role` | `status` | Firmas legales |
|---|---|---|---|---|---|
| `super.dcabrera` | David Cabrera | `davidcabrera@mediahubbcs.com` | `Super_admin` | `Activo` | Precargadas `signed = 1` (mismo criterio que `admin.glage` en Fase 4 — acceso directo, sin bloqueo) |

#### 2.1.1 RBAC Escalado (Fase 5) — Jerarquia de Roles

El ENUM `users.role` se amplio en Fase 5 mediante migracion segura de 3 pasos (ampliar → migrar filas → cerrar, ver `database/migration_fase5_rbac_and_native_shows.sql`). El valor legado `Administrador` fue **renombrado** a `Admin` (la fila de `admin.glage` migro automaticamente); los roles operativos existentes (`Lider_Proyecto`, `Staff_Tecnico`, `Lider_Logistica`, `Cliente`) se preservaron intactos.

| Rol | Nivel | Alcance |
|---|---|---|
| `Super_admin` | 1 (tope) | Control absoluto. Unico rol facultado para crear cuentas `Admin`. Cuenta exclusiva de David Cabrera. |
| `Admin` | 2 | Puede crear usuarios hacia abajo de la jerarquia (`Team`, `Conductor`, y los roles operativos). Bloqueado para crear `Super_admin`. |
| `Lider_Proyecto`, `Staff_Tecnico`, `Lider_Logistica` | Operativo (heredado de Fase 1/4) | Roles de produccion en campo — sin cambios de alcance. |
| `Cliente` | Externo | Reservado para el futuro Portal de Cliente Jornal (Fase 3). |
| `Team` | 3 | Personal operativo interno (Soporte, Administracion general, Ventas). |
| `Conductor` | 4 | Talento/host vinculado a un show nativo (`programs.conductor_user_id`). Ve unicamente los datos de su show asignado y genera enlaces de invitado. |

> **Pendiente de implementacion en PHP (no en este documento):** la regla "`Super_admin` es el unico que crea `Admin`; `Admin` no puede crear `Super_admin`" es logica de aplicacion — debe codificarse en `api/users.php?action=create` (matriz de permisos), no en el schema.

---

### 2.2 `legal_documents` — Catalogo de Reglamentos

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `code` | VARCHAR(40) UNIQUE | `CONTRATO_STAFF`, `REGLAS_ESTUDIO`, `REGLAS_GRABACION`, `REGLAS_GENERALES` |
| `title` | VARCHAR(150) | Titulo visible en `legal/firma.php` |
| `content` | TEXT | Texto integro del reglamento (ver `01_LEY_Y_PROTOCOLOS_DE_VUELO.md`) |
| `version` | VARCHAR(10) | Default `'1.0'` |
| `sort_order` | INT(2) | Orden de presentacion (1-4) |
| `updated_at` | TIMESTAMP | `ON UPDATE CURRENT_TIMESTAMP` |

---

### 2.1.2 `password_resets` — Tokens de Recuperacion/Activacion (Fase 2 + Fase 5.1)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `user_id` | INT(11) | FK → `users.id` (`ON DELETE CASCADE`), indexado por `idx_reset_user` |
| `purpose` | ENUM | `reset`, `activation` (default `reset`) — **agregado en Fase 5.1** para distinguir recuperacion de contrasena (`api/forgot_password.php`/`api/reset_password.php`) de activacion de cuenta invitada (`api/users.php?action=create`/`api/set_password.php`) |
| `token_hash` | VARCHAR(255) | `hash_hmac('sha256', $rawToken, CSRF_SECRET)` — el token crudo solo vive en la URL del correo, nunca en BD |
| `expires_at` | DATETIME | 1 hora para `reset`, 7 dias para `activation` |
| `used` | TINYINT(1) | Default `0` |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

### 2.1.3 `user_remember_tokens` — Sesion Prolongada "Recuerdame" (Fase 5.1)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `user_id` | INT(11) | FK → `users.id` (`ON DELETE CASCADE`), indexado por `idx_remember_user` |
| `token_hash` | VARCHAR(255) UNIQUE | `hash_hmac('sha256', $rawToken, CSRF_SECRET)` — el crudo vive solo en la cookie `mh_remember` (`HttpOnly`, `Secure`, `SameSite=Strict`) |
| `user_agent` | VARCHAR(255) NULL | Auditoria del dispositivo |
| `ip_address` | VARCHAR(45) NULL | Auditoria de origen |
| `expires_at` | DATETIME | 60 dias desde la emision |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Rotacion obligatoria:** cada restauracion exitosa (`api/auth_guard.php::mh_try_restore_session_from_cookie()`) borra el token consumido y emite uno nuevo. `api/logout.php` borra todos los tokens del usuario y expira la cookie.

---

### 2.3 `user_legal_signatures` — Firma Digital Obligatoria

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `user_id` | INT(11) | FK → `users.id` (`ON DELETE CASCADE`) |
| `document_id` | INT(11) | FK → `legal_documents.id` (`ON DELETE CASCADE`) |
| `signed` | TINYINT(1) | `0` = pendiente, `1` = firmado. Default `0` |
| `signed_at` | DATETIME | Timestamp de firma |
| `ip_address` | VARCHAR(45) | IP del firmante |

**Clave unica:** `(user_id, document_id)` — garantiza una sola fila de estado por combinacion usuario/documento.

**Seed:** producto cartesiano `users x legal_documents` con `signed = 0` para todos los usuarios iniciales.

---

### 2.4 `clients` — Clientes Jornal

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `full_name` | VARCHAR(120) | Nombre del contacto principal |
| `email` | VARCHAR(150) | Opcional |
| `phone` | VARCHAR(30) | Opcional |
| `company` | VARCHAR(120) | Nombre comercial |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Seed (Fase 1, ya sembrado):**

| `full_name` | `company` |
|---|---|
| Dr. Efrain Torres | Medicina del Siglo XXI |
| Efrain Torres | CCBCS |

> **Reconciliacion Fase 5.11:** el roster objetivo de Fase 5.6 ("Consejo Coordinador / Producciones Asociadas" como Cliente Jornal con fila propia en `clients`) queda **descartado y reemplazado**. *Enfoque 360*, *El Ring* y *En Privado* NO son Cliente Jornal — son Shows Nativos (`programs.is_native_show = 1`, ver §2.5) afiliados al canal **"El Jornal BCS"** (David de la Paz), un valor de texto libre en `programs.affiliated_channel` (Fase 5.8), no una fila de `clients`. No se agrega ninguna fila nueva a esta tabla para ese roster.

---

### 2.5 `programs` — Programas Recurrentes **y** Shows Nativos (Fase 5)

Desde Fase 5, `programs` representa dos casos de uso mutuamente excluyentes segun `is_native_show`:

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `client_id` | INT(11) **NULL** (Fase 5: antes NOT NULL) | FK → `clients.id` (`ON DELETE CASCADE`), indexado por `idx_program_client`. **`NULL` si `is_native_show = 1`** |
| `name` | VARCHAR(150) | Nombre del programa |
| `description` | TEXT | Descripcion editorial interna |
| `catalog_description` | TEXT NULL | **Fase 5.** Descripcion publica para el catalogo web (solo shows nativos) |
| `logo_url` | VARCHAR(255) NULL | **Fase 5.** Logo del show nativo para el catalogo publico |
| `public_social_links` | JSON NULL | **Fase 5.** Redes sociales publicas del show nativo. Columna JSON real -- todo valor debe pasar por `json_encode()` antes del INSERT/UPDATE (un string plano provoca `Invalid JSON text` en MySQL) y por `JSON.parse()`/`json_decode()` al leerlo |
| `production_schedule` | JSON NULL | **Fase 5.2.** Calendario de produccion: `{ "days": ["Lunes","Miercoles"], "start_time": "18:00", "end_time": "20:00" }`. Generado por `mh_build_schedule_json()` en `api/programs.php` |
| `is_native_show` | TINYINT(1) | **Fase 5.** Default `0`. `1` = show propio de Media HUB (catalogo publico, sin `client_id`) |
| `conductor_user_id` | INT(11) NULL | **Fase 5.** FK → `users.id` (`ON DELETE SET NULL`, `fk_program_conductor`). Obligatorio si `is_native_show = 1` (regla de aplicacion, no CHECK de BD) |
| `is_active` | TINYINT(1) | Default `1` |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Regla de integridad de negocio (validar en PHP, no hay CHECK portable entre columnas en MySQL):**
- `is_native_show = 1` ⟹ `conductor_user_id IS NOT NULL` y `client_id IS NULL`.
- `is_native_show = 0` ⟹ `client_id IS NOT NULL` (comportamiento legado de Fase 1 intacto).

**Seed (Fase 1 — programas de Clientes Jornal, `is_native_show = 0`):**

| `name` | Cliente | Descripcion |
|---|---|---|
| Medicina del Siglo XXI | Dr. Efrain Torres | Entrevistas a especialistas de la salud, producido en Estudio 5 de Mayo |
| CCBCS | Efrain Torres (CCBCS) | Programa institucional del Consejo Coordinador, produccion y transmision Simulcast |

**Roster real de Shows Nativos (Fase 5.11 — canal afiliado "El Jornal BCS", David de la Paz):**

| `name` | `is_native_show` | `affiliated_channel` | Notas |
|---|---|---|---|
| Enfoque 360 | 1 | El Jornal BCS | Construido vía `migration_fase5_7_enfoque360_native_show.sql` (conductor asignado: Pepe Pecas) |
| El Ring | 1 | El Jornal BCS | Idem |
| En Privado | 1 | El Jornal BCS | Idem |

> Los shows nativos se dan de alta vía `api/programs.php?action=create_native` con `conductor_user_id` asignado (ver Contrato 3), o vía el script `migration_fase5_7_enfoque360_native_show.sql` ya generado para este roster especifico. **Estos 3 shows NO son Cliente Jornal tradicional** (`is_native_show = 0`) — no tienen `client_id`, no facturan bajo el esquema de anticipo del 50%, y "El Jornal BCS" vive únicamente como texto libre en `affiliated_channel`, nunca como fila de `clients` (Mandamiento 9 — reconciliacion de la Fase 5.6, ver §2.4).
>
> ⚠️ **Pendiente de verificacion con el Arquitecto:** la relacion entre "David de la Paz" (dueño/responsable del canal "El Jornal BCS") y "Pepe Pecas" (usuario `Conductor` ya asignado a estos 3 shows via `migration_fase5_7`) no esta confirmada — pueden ser la misma persona (nombre real vs. nombre artistico) o dos roles distintos (dueño de canal vs. conductor en pantalla). No se asume ninguna de las dos hasta confirmacion explicita.

---

### 2.11 `guest_invite_links` — Enlaces de Invitado con TTL de 3 Clics (Fase 5)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `program_id` | INT(11) | FK → `programs.id` (`ON DELETE CASCADE`), indexado por `idx_guest_link_program`. Debe referenciar un `programs.is_native_show = 1` (regla de aplicacion) |
| `call_id` | INT(11) NULL | **Fase 5.5.** FK → `calls.id` (`ON DELETE SET NULL`, `fk_guest_link_call`). Si se proporciona, debe pertenecer al mismo `program_id` (regla de aplicacion) — permite que `guest_form.php` muestre fecha/hora/locacion reales del llamado en vez del texto generico de marcador |
| `token` | VARCHAR(64) UNIQUE | Token aleatorio (`bin2hex(random_bytes(32))` truncado o equivalente), clave `uq_guest_link_token` |
| `created_by` | INT(11) NULL | FK → `users.id` (`ON DELETE SET NULL`) — el `Conductor` que genero el enlace |
| `click_count` | TINYINT(1) UNSIGNED | Default `0`. Incrementa en cada acceso valido del invitado (0→1 alta, 1→2 edicion, 2→3 expira) |
| `max_clicks` | TINYINT(1) UNSIGNED | Default `3` — inmutable por diseño, no editable vía API |
| `status` | ENUM | `Activo`, `Expirado` (default `Activo`) |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |
| `expired_at` | DATETIME NULL | Se establece al llegar `click_count = max_clicks` |

---

### 2.12 `guest_submissions` — Datos Capturados del Invitado (Fase 5)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `link_id` | INT(11) UNIQUE | FK → `guest_invite_links.id` (`ON DELETE CASCADE`), clave `uq_guest_submission_link` — **una sola submission por enlace** (click 1 crea, click 2 actualiza la misma fila) |
| `full_name` | VARCHAR(120) | **Obligatorio** |
| `title_position` | VARCHAR(150) | **Obligatorio** — titulo o puesto corporativo |
| `social_links` | JSON NULL | Opcional, publico |
| `whatsapp` | VARCHAR(30) NULL | Opcional, publico |
| `website` | VARCHAR(255) NULL | Opcional, publico |
| `email` | VARCHAR(150) NULL | Opcional, publico |
| `invite_message` | TEXT NULL | Mensaje corto de invitacion, opcional |
| `qa_notes` | TEXT NULL | Comentarios/sugerencias de auditoria de calidad (uso interno, no publico) |
| `created_at` / `updated_at` | TIMESTAMP | `updated_at` con `ON UPDATE CURRENT_TIMESTAMP` (se actualiza en el click 2) |

---

### 2.6 `calls` — Agenda / Llamados (Control de Colisiones)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `program_id` | INT(11) | FK → `programs.id` (`ON DELETE CASCADE`), indexado por `idx_call_program` |
| `title` | VARCHAR(150) | Titulo del llamado |
| `location` | ENUM | `Estudio 5 de Mayo`, `Locacion Externa`, `Van Terrestre`, `Embarcacion Maritima` (default `Estudio 5 de Mayo`) |
| `call_date` | DATE | Fecha del llamado |
| `start_time` / `end_time` | TIME | Ventana horaria. **Fase 5.11:** default institucional `17:00:00` / `17:30:00` (horario estandar del planner "Siguiente Programa" del Conductor) — ver nota de verificacion abajo |
| `status` | ENUM | `Pendiente`, `Confirmado`, `Cancelado`, `Completado` (default `Pendiente`) |
| `advance_required_pct` | DECIMAL(5,2) | Default `50.00` |
| `advance_paid` | TINYINT(1) | Default `0` |
| `total_amount` | DECIMAL(10,2) | Monto total del llamado (opcional) |
| `notes` | TEXT | Notas libres |
| `episode_theme` | TEXT NULL | **Fase 5.8.** Tema/eje del episodio, varia por llamado (no por show) |
| `created_by` | INT(11) | FK → `users.id` (`ON DELETE SET NULL`) |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Indice critico:** `idx_call_collision (location, call_date, start_time, end_time)` — soporta la consulta de colision descrita en `01_LEY_Y_PROTOCOLOS_DE_VUELO.md` seccion 3.

> ⚠️ **Pendiente de verificacion (Fase 5.11):** el Arquitecto confirma que `start_time`/`end_time` ya corren en vivo en GreenGeeks con `DEFAULT 17:00:00` / `DEFAULT 17:30:00`, pero no existe en este repositorio ningún script `database/migration_fase5_11_*.sql` que documente ese `ALTER TABLE`. La logica de negocio (Fase 5.10, `api/agenda.php?action=save_conductor_call`) ya replica ese mismo valor en el lado de la aplicacion, así que el comportamiento observable es correcto de cualquier forma — pero el cambio de esquema en si queda sin script de respaldo local. Si el Arquitecto confirma el `ALTER TABLE` exacto, debe documentarse aqui con su propio archivo de migracion para cerrar Mandamiento 15 (Agente Residente).

---

### 2.7 `call_assignments` — Tareas del Staff por Llamado

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `call_id` | INT(11) | FK → `calls.id` (`ON DELETE CASCADE`) |
| `user_id` | INT(11) | FK → `users.id` (`ON DELETE CASCADE`) |
| `task_description` | TEXT | Descripcion de la tarea asignada |
| `status` | ENUM | `Pendiente`, `En Progreso`, `Completado` (default `Pendiente`) |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Clave unica:** `(call_id, user_id)` — un usuario solo puede tener una fila de asignacion por llamado.

---

### 2.8 `inventory_items` — Inventario Dinamico

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `name` | VARCHAR(150) | Nombre del activo |
| `category` | ENUM | `Camara`, `Optica`, `Luz LED`, `Audio`, `Otro` (default `Otro`) |
| `serial_number` | VARCHAR(80) UNIQUE | Numero de serie |
| `status` | ENUM | `Disponible`, `En Uso`, `Mantenimiento` (default `Disponible`) |
| `notes` | TEXT | Notas de mantenimiento |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Seed:**

| `name` | `category` | `serial_number` |
|---|---|---|
| Camara Principal Set A | Camara | CAM-A-001 |
| Camara Secundaria Set B | Camara | CAM-B-002 |
| Optica 24-70mm f2.8 | Optica | LENS-2470-001 |
| Panel LED Frio 1 | Luz LED | LED-001 |
| Panel LED Frio 2 | Luz LED | LED-002 |
| Kit Microfonia Lavalier | Audio | AUD-LAV-001 |

---

### 2.9 `fleet_vehicles` — Flota Movil

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `name` | VARCHAR(120) | Nombre de la unidad |
| `type` | ENUM | `Van Terrestre`, `Embarcacion Maritima` |
| `registration` | VARCHAR(60) | Matricula / registro |
| `status` | ENUM | `Disponible`, `En Uso`, `Mantenimiento` (default `Disponible`) |
| `notes` | TEXT | Notas de mantenimiento |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Seed:**

| `name` | `type` | `registration` |
|---|---|---|
| Van de Produccion BCS-01 | Van Terrestre | BCS-0145-A |
| Embarcacion Mar de Cortes | Embarcacion Maritima | MX-MC-002 |

---

### 2.10 `checkinout_log` — Bitacora Check-In / Check-Out

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `asset_type` | ENUM | `Inventario`, `Vehiculo` |
| `asset_id` | INT(11) | Apunta a `inventory_items.id` o `fleet_vehicles.id` segun `asset_type`. Indexado via `idx_log_asset (asset_type, asset_id)` |
| `call_id` | INT(11) | FK → `calls.id` (`ON DELETE SET NULL`), opcional |
| `user_id` | INT(11) | FK → `users.id` (`ON DELETE CASCADE`) |
| `action` | ENUM | `Check-In`, `Check-Out` |
| `condition_notes` | TEXT | Estado del activo al momento del movimiento |
| `logged_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

> `asset_id` **no** tiene FK fisica porque referencia dos tablas distintas segun `asset_type` (patron polimorfico). La integridad debe validarse a nivel de aplicacion.

---

## 3. CONVENCIONES DE NOMBRES

- Tablas y columnas en `snake_case`, en ingles.
- Valores de ENUM y contenido visible al usuario en **español** (ej. `'Disponible'`, `'Estudio 5 de Mayo'`, `'Confirmado'`).
- Toda tabla con datos mutables por el usuario incluye `created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP`.
- Claves foraneas con prefijo `fk_` + tabla_origen + referencia (ej. `fk_call_program`, `fk_signature_user`).
- Indices de consulta frecuente con prefijo `idx_` (ej. `idx_call_collision`, `idx_program_client`, `idx_log_asset`).

---

## 4. SCRIPT DE INICIALIZACION

El script base vive en [`database/schema.sql`](../database/schema.sql). Migraciones aditivas aplicadas en orden sobre `tecnidepot_mediahub_db`: `migration_fase2.sql`, `migration_fase3_security.sql`, `migration_fase4_roles_seed.sql`, `migration_fase5_rbac_and_native_shows.sql` (RBAC escalado + shows nativos + `guest_invite_links`/`guest_submissions`, ejecutada en el servidor remoto de producción). Estructura del script base:

1. `SET NAMES utf8mb4;` / `SET FOREIGN_KEY_CHECKS = 0;`
2. 9 bloques `CREATE TABLE IF NOT EXISTS` (uno por tabla de la seccion 2, excepto el bloque combinado de `legal_documents` + `user_legal_signatures` que forman el Modulo Legal).
3. `SET FOREIGN_KEY_CHECKS = 1;`
4. Bloque de **datos semilla** (`INSERT INTO`) para `users`, `legal_documents`, `user_legal_signatures`, `clients`, `programs`, `inventory_items`, `fleet_vehicles`.

Ejecutar este script es el primer paso de despliegue de cualquier ambiente nuevo (local XAMPP o produccion).
