# 02 · CODEX Y SCHEMA MAESTRO — MEDIA HUB

> **Version:** 1.0 (Fase 1 — Estandar Oro)
> **Clasificacion:** Documento Critico — Fuente de Verdad de Datos
> **Fuente:** `database/schema.sql` · Motor: **InnoDB** · Charset: **utf8mb4** · Collation: **utf8mb4_general_ci**
> Complementa a `DB_STRUCTURE.md` (amendment Fase 1).

Este documento es el **diccionario de datos** de las 9 tablas inicializadas en Fase 1. Ninguna tabla, columna o ENUM nuevo debe crearse sin registrarse aqui primero.

---

## 1. MAPA GENERAL DEL ESQUEMA

```
users ──┬──< user_legal_signatures >── legal_documents
        │
        ├──< calls >── programs ──< clients
        │      │
        │      └──< call_assignments >── users
        │
        └──< checkinout_log >── (inventory_items | fleet_vehicles)
                   └── calls
```

| # | Tabla | Proposito |
|---|---|---|
| 1 | `users` | Organigrama digital y autenticacion |
| 2 | `legal_documents` | Catalogo de los 4 reglamentos inmutables |
| 3 | `user_legal_signatures` | Bitacora de firma digital por usuario/documento |
| 4 | `clients` | Clientes Jornal |
| 5 | `programs` | Programas recurrentes por cliente |
| 6 | `calls` | Agenda / llamados con control de colisiones |
| 7 | `call_assignments` | Tareas asignadas al staff por llamado |
| 8 | `inventory_items` | Inventario dinamico de equipo |
| 9 | `fleet_vehicles` | Flota movil (Van / Embarcacion) |
| — | `checkinout_log` | Bitacora Check-In/Check-Out (10ma tabla operativa) |

> Nota: el bloque de seed data agrega una decima tabla operativa (`checkinout_log`) que documenta el movimiento de `inventory_items` y `fleet_vehicles`. En total el script crea **9 tablas con CREATE TABLE** explicito.

---

## 2. DICCIONARIO DE DATOS POR TABLA

### 2.1 `users` — Organigrama Digital

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `user_id` | VARCHAR(50) UNIQUE | Identificador legible (`admin.root`, `lider.glage`, `staff.gmorales`, `staff.amurillo`, `chofer.logistica1`) |
| `full_name` | VARCHAR(100) | Nombre completo — usado para validar firma digital |
| `email` | VARCHAR(150) UNIQUE | Correo corporativo, usado para login |
| `password_hash` | VARCHAR(255) | Hash Bcrypt (`PASSWORD_BCRYPT`) |
| `role` | ENUM | `Administrador`, `Lider_Proyecto`, `Staff_Tecnico`, `Lider_Logistica`, `Cliente` (default `Staff_Tecnico`) |
| `status` | ENUM | `Activo`, `Suspendido`, `Troll_Mode` (default `Activo`) |
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

**Seed:**

| `full_name` | `company` |
|---|---|
| Dr. Efrain Torres | Medicina del Siglo XXI |
| Efrain Torres | CCBCS |

---

### 2.5 `programs` — Programas Recurrentes

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `client_id` | INT(11) | FK → `clients.id` (`ON DELETE CASCADE`), indexado por `idx_program_client` |
| `name` | VARCHAR(150) | Nombre del programa |
| `description` | TEXT | Descripcion editorial |
| `is_active` | TINYINT(1) | Default `1` |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Seed:**

| `name` | Cliente | Descripcion |
|---|---|---|
| Medicina del Siglo XXI | Dr. Efrain Torres | Entrevistas a especialistas de la salud, producido en Estudio 5 de Mayo |
| CCBCS | Efrain Torres (CCBCS) | Programa institucional del Consejo Coordinador, produccion y transmision Simulcast |

---

### 2.6 `calls` — Agenda / Llamados (Control de Colisiones)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | INT(11) AUTO_INCREMENT | PK |
| `program_id` | INT(11) | FK → `programs.id` (`ON DELETE CASCADE`), indexado por `idx_call_program` |
| `title` | VARCHAR(150) | Titulo del llamado |
| `location` | ENUM | `Estudio 5 de Mayo`, `Locacion Externa`, `Van Terrestre`, `Embarcacion Maritima` (default `Estudio 5 de Mayo`) |
| `call_date` | DATE | Fecha del llamado |
| `start_time` / `end_time` | TIME | Ventana horaria |
| `status` | ENUM | `Pendiente`, `Confirmado`, `Cancelado`, `Completado` (default `Pendiente`) |
| `advance_required_pct` | DECIMAL(5,2) | Default `50.00` |
| `advance_paid` | TINYINT(1) | Default `0` |
| `total_amount` | DECIMAL(10,2) | Monto total del llamado (opcional) |
| `notes` | TEXT | Notas libres |
| `created_by` | INT(11) | FK → `users.id` (`ON DELETE SET NULL`) |
| `created_at` | TIMESTAMP | Default `CURRENT_TIMESTAMP` |

**Indice critico:** `idx_call_collision (location, call_date, start_time, end_time)` — soporta la consulta de colision descrita en `01_LEY_Y_PROTOCOLOS_DE_VUELO.md` seccion 3.

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

El script completo y ejecutable vive en [`database/schema.sql`](../database/schema.sql). Estructura:

1. `SET NAMES utf8mb4;` / `SET FOREIGN_KEY_CHECKS = 0;`
2. 9 bloques `CREATE TABLE IF NOT EXISTS` (uno por tabla de la seccion 2, excepto el bloque combinado de `legal_documents` + `user_legal_signatures` que forman el Modulo Legal).
3. `SET FOREIGN_KEY_CHECKS = 1;`
4. Bloque de **datos semilla** (`INSERT INTO`) para `users`, `legal_documents`, `user_legal_signatures`, `clients`, `programs`, `inventory_items`, `fleet_vehicles`.

Ejecutar este script es el primer paso de despliegue de cualquier ambiente nuevo (local XAMPP o produccion).
