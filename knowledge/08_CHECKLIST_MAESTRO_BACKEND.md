# 08. CHECKLIST MAESTRO BACKEND — Media HUB V2
### Backend Actionable Blueprint / Fuente de Verdad de Tareas Backend

> **Alcance:** este documento es el plano de construcción modular del backend de Media HUB V2. No describe necesariamente el estado actual del repositorio (Fase 1/Fase 2), sino el blueprint objetivo hacia el que deben migrar progresivamente los modulos `api/`, `config/` y `legal/`. Cualquier divergencia con el codigo vigente debe resolverse mediante migraciones aditivas, nunca con sobreescrituras destructivas.
>
> **Conexion de datos:** todo acceso a MySQL pasa exclusivamente por el patron PDO Singleton `config/Database.php`, el cual lee las credenciales desde `.env` contra la base de datos activa `tecnidepot_mediahub_db`. Queda prohibido hardcodear credenciales en cualquier archivo de `api/`.
>
> **Higiene de redirecciones:** todo `header('Location: ...')` debe usar rutas locales relativas sin barra diagonal inicial (ej. `Location: ../index.php`, `Location: ../legal/signatures.php`) para evitar 404 bajo subcarpetas de XAMPP.
>
> **Privacidad del desarrollador:** `knowledge/info.txt` es de uso exclusivo del desarrollador humano. Prohibido leerlo, alterarlo o borrarlo desde cualquier tarea de este checklist.

---

## 1. ARQUITECTURA DE DIRECTORIOS Y CAPA MIDDLEWARE

### 1.1 Estructura modular de `api/`

- [ ] `api/auth/` — login, logout, recuperacion de contrasena, refresco de sesion.
- [ ] `api/users/` — CRUD de usuarios/staff, organigrama, perfil propio (`action=me`).
- [ ] `api/clients/` — CRUD de Clientes Jornal (`is_active`, baja logica).
- [ ] `api/programs/` — CRUD de Programas recurrentes vinculados a Clientes Jornal.
- [ ] `api/projects/` — CRUD de Proyectos/Producciones (agrupador superior a `programs`, si aplica V2).
- [ ] `api/calls/` — Agenda de llamados, maquina de estados, control de colisiones, verificacion de anticipo.
- [ ] `api/inventory/` — Inventario de equipo + flota (Van Terrestre / Embarcacion Maritima), bitacora `equipment_movements`.
- [ ] `api/legal/` — Endpoints de firmas legales, consulta/registro de `user_legal_signatures`.
- [ ] `api/finance/` — Calculo de anticipos, IVA, utilidades proyectadas, matriz de costos.
- [ ] `api/notifications/` — Disparadores y plantillas de correo transaccional (mailer engine).
- [ ] `api/security/` — Endpoints de soporte para Troll Mode, bloqueo de IP, blacklist.
- [ ] `api/logs/` — Lectura/consulta de `security_logs`, `equipment_movements`, bitacoras de auditoria.

### 1.2 Capa `middleware/` — validacion perimetral secuencial

> Orden de ejecucion obligatorio en cada endpoint sensible: `auth.php` → `csrf.php` → `permissions.php` → `troll_mode.php` (deteccion activa sobre el payload ya autenticado).

- [ ] `middleware/auth.php`
  - [ ] Verifica `session_status()` y existencia de `$_SESSION['user_id']`.
  - [ ] Responde `401` JSON (`{status:"error", message:"No autenticado..."}`) en endpoints AJAX.
  - [ ] Redirige a `../index.php` (ruta relativa) en vistas server-rendered sin sesion.
  - [ ] Expone `mh_require_session(): array` reutilizable para toda la capa `api/`.
- [ ] `middleware/csrf.php`
  - [ ] Genera token criptografico de un solo uso por sesion (`bin2hex(random_bytes(32))`).
  - [ ] Valida `csrf_token` recibido en body JSON o header `X-CSRF-Token` con `hash_equals()`.
  - [ ] Token vinculado a sesion, **no** reusable entre formularios distintos tras envio exitoso (rotacion post-mutacion en operaciones criticas: cambio de password, alta de usuarios).
- [ ] `middleware/permissions.php`
  - [ ] Matriz de roles → acciones permitidas (ver Seccion 4).
  - [ ] Funcion `mh_require_role(array $user, array $allowedRoles): void` con respuesta `403` estandar.
  - [ ] Soporta reglas compuestas (ej. "Administrador siempre, o Lider_Proyecto si `program_id` le pertenece").
- [ ] `middleware/troll_mode.php`
  - [ ] Escaneo regex de patrones SQLi/XSS sobre `$_POST`/`$_GET`/body JSON (`MH_ATTACK_PATTERNS`).
  - [ ] Registro de evento en `seguridad.log` / `security_logs` con IP, URI, contexto y payload serializado.
  - [ ] Redireccion inmediata a `../troll.php` (ruta relativa) al detectar patron malicioso.
  - [ ] Integracion con escalamiento de intentos (Seccion 5.2).

---

## 2. MAQUINA DE ESTADOS RECURRENTES Y COMPUERTAS DE CONTROL (LOGISTICA DE LLAMADOS)

### 2.1 Transiciones de `calls.status` (maquina de estados V2)

- [ ] Definir ENUM/lookup ampliado en `calls.status`:
  - [ ] `DRAFT` — llamado creado sin validar disponibilidad financiera.
  - [ ] `PENDING_ADVANCE` — llamado validado por colisiones, en espera del anticipo del 50%.
  - [ ] `ADVANCE_PAID` — `calls.advance_paid = 1` confirmado por Administrador.
  - [ ] `STAFF_ASSIGNED` — staff asignado en `call_assignments` (solo permitido si `ADVANCE_PAID`).
  - [ ] `READY_FOR_SETUP` — checklist "Antes" completo al 100% (ver Seccion 3, Etapa 1).
  - [ ] `LIVE` — transmision en curso (checklist "Durante" activo).
  - [ ] `WRAP_UP` — desmontaje en curso (checklist "Despues" activo).
  - [ ] `ARCHIVED` — llamado cerrado, respaldo confirmado, equipo en check-in completo.
- [ ] Codificar tabla/funcion de transicion `mh_call_transition(int $callId, string $nextStatus)`:
  - [ ] Valida que la transicion solicitada sea adyacente en la cadena (no permite saltos arbitrarios, salvo `*` → `ARCHIVED`/`Cancelado` por Administrador).
  - [ ] Registra cada cambio de estado con timestamp + `user_id` responsable (bitacora de auditoria).
  - [ ] Expone el estado anterior y nuevo en la respuesta JSON estandar `{status, message, data}`.
- [ ] Mapear estados legados Fase 1/2 (`Pendiente`, `Confirmado`, `Cancelado`, `Completado`) hacia la nueva cadena mediante migracion aditiva (columna `status_v2` o vista de compatibilidad) sin romper `api/agenda.php` existente.

### 2.2 Compuerta mandatoria de comprobacion financiera (50% de anticipo)

- [X] En `api/agenda.php` (accion `assign_staff` y cualquier transicion hacia `STAFF_ASSIGNED`):
  - [X] Verificar `SELECT advance_paid FROM calls WHERE id = :id` antes de cualquier `INSERT` en `call_assignments`.
  - [X] Si `calls.advance_paid = 0`, **bloquear** la escritura (no ejecutar el `INSERT`/`UPDATE`).
  - [X] Responder codigo `422` con el mensaje exacto de alerta para despliegue movil (monto calculado dinamicamente desde `total_amount * advance_required_pct / 100`, ej.):
    > "No se puede asignar personal: el anticipo del 50% ($9,137.03 MXN) aun no ha sido verificado para este llamado."
  - [X] Replicar la misma compuerta en `update_status` cuando el destino sea `Confirmado` (pendiente: maquina de estados V2 completa `PENDING_ADVANCE → ADVANCE_PAID → STAFF_ASSIGNED`).
- [X] Frontend (dashboard): renderizar badge ambar/rojo "Bloqueo por Falta de Anticipo" + el mensaje de alerta exacto (monto dinamico) cuando `advance_paid = 0`, deshabilitando los controles de asignacion de staff.
- [ ] `PUT action=verify_advance` (solo Administrador): al pasar `advance_paid` de `0` a `1`, disparar:
  - [ ] Transicion automatica `PENDING_ADVANCE → ADVANCE_PAID`.
  - [X] Plantilla de correo "Confirmacion de Fecha Bloqueada" (Seccion 6.3).

---

## 3. CONSOLA DE CHECKLISTS OPERATIVOS Y MOVIMIENTOS DE INVENTARIO (ESTUDIO 5 DE MAYO)

### 3.1 Etapa 1 — ANTES (Montaje)

- [X] **Iluminacion LED**
  - [X] Checklist: calibracion de temperatura de color (Kelvin) y exposicion de cada fixture LED frio.
  - [X] Registro de `item_key = calibracion_led` en `call_checklist_progress` con firma digital (`checked_by`, `checked_at`) — implementado via `database/migration_fase2.sql` (seed `checklist_templates`) y `api/checklist.php?action=toggle`.
- [ ] **Camaras**
  - [ ] Checklist: formateo de tarjetas de memoria previo a grabacion. (Sin `item_key` dedicado en el catalogo actual).
  - [X] Checklist: verificacion de foco/encuadre (`item_key = encuadres`) por cada camara activa.
- [X] **Audio**
  - [X] Checklist: pruebas de aislamiento acustico del set (`item_key = aislamiento_acustico`) — puertas cerradas, ruido externo controlado.
- [ ] **Streaming / Simulcast**
  - [ ] Alerta automatica programada a **T-15 minutos** antes de salir al aire. (No implementada como temporizador automatico; el item de checklist existe y debe marcarse manualmente).
  - [X] Checklist obligatorio (`item_key = bitrate_simulcast`): verificacion de bitrate de subida para Simulcast Facebook + YouTube.
  - [ ] Bloquear transicion `READY_FOR_SETUP → LIVE` si este item no esta marcado. (Requiere la maquina de estados V2 de la Seccion 2.1, no implementada).
- [X] **Registro obligatorio de Check-Out de hardware (`checkinout_log`, equivalente a `equipment_movements`)**
  - [X] Campo `user_id` — staff tecnico que realiza el retiro (firma digital), tomado de la sesion autenticada.
  - [X] Campo `equipment_id` — referencia a `inventory_items`/`fleet_vehicles` (`asset_type` + `asset_id`).
  - [X] Campo `checkout_time` — timestamp del retiro (`logged_at`, `DEFAULT CURRENT_TIMESTAMP`).
  - [X] Campo `condition_before` — estado/observaciones del equipo al salir del almacen (consolidado en `condition_notes`).
  - [ ] Campo `photo_before` — referencia/ruta de evidencia fotografica previa al uso. (No implementado).
  - [X] Endpoint `api/inventory.php?action=checkout` inserta el registro de forma append-only (sin UPDATE/DELETE posterior) y transiciona el activo a `'En Uso'`.

### 3.2 Etapa 2 — DURANTE (Live)

- [X] **Monitoreo de audio**
  - [X] Checklist obligatorio (`item_key = monitoreo_gain`) con firma digital. (Vista en vivo del nivel de ganancia por hardware/streaming aun no implementada — el item cubre el control de proceso, no la telemetria en tiempo real).
- [ ] **Control termico**
  - [X] Checklist obligatorio (`item_key = clima_set`) con firma digital de control de clima del set.
  - [ ] Alerta push/automatica si la temperatura **supera los 32°C** (`MH_TEMP_THRESHOLD_C = 32`) — no implementada (requiere sensor/integracion IoT).
- [X] **Reglas de set (control continuo)**
  - [X] Recordatorio persistente: celulares del personal en **modo avion** (`item_key = modo_avion`).
  - [X] Recordatorio persistente: **prohibicion estricta de liquidos y alimentos** cerca de consolas/equipo de transmision (`item_key = prohibicion_liquidos`).
  - [X] Ambos recordatorios se muestran como tarjetas de alerta fijas en la pestana "Durante" del checklist (`dashboard/index.php`, seccion `#checklist`), independientes del checklist marcable.

### 3.3 Etapa 3 — DESPUES (Desmontaje y Respaldo)

- [X] **Apagado seguro**
  - [X] Checklist: apagado seguro del master y equipos de transmision (`item_key = apagado_master`). (Alcance especifico a UPS no diferenciado).
- [X] **Resguardo de hardware**
  - [X] Checklist: limpieza general del set tras la grabacion (`item_key = limpieza_set`).
  - [ ] Checklist: resguardo fisico de hardware en almacen/racks asignados. (Sin `item_key` dedicado).
- [X] **Respaldo de medios**
  - [X] Checklist: subida de respaldos RAW/editables a los discos del HUB (`item_key = respaldo_raw`).
- [X] **Registro obligatorio de Check-In de hardware (`checkinout_log`, equivalente a `equipment_movements`) — `item_key = checkin_hardware`**
  - [X] Campo `checkin_time` — timestamp del reingreso del equipo (`logged_at`).
  - [X] Campo `condition_after` — estado/observaciones del equipo al reingresar (consolidado en `condition_notes`).
  - [X] Campo `damage_report` — texto obligatorio si se marca `damaged = true` (validado en `api/inventory.php?action=checkin`: rechaza con `422` si `damaged=true` y `condition_notes` esta vacio).
  - [ ] Campo `photo_after` — referencia/ruta de evidencia fotografica posterior al uso. (No implementado).
  - [X] Si `damaged = true`, transiciona automaticamente el activo a estatus `Mantenimiento` (`api/inventory.php?action=checkin`).
  - [ ] Bloquear transicion `WRAP_UP → ARCHIVED` mientras existan activos sin Check-In registrado. (Requiere la maquina de estados V2 de la Seccion 2.1, no implementada).

---

## 4. MATRIZ DE SEGURIDAD ROLES Y HANDSHAKE LEGAL

### 4.1 Matriz de permisos por rol

- [ ] **Administrador**
  - [ ] Acceso total a todos los modulos de `api/` sin restriccion.
  - [ ] Unico rol autorizado para `verify_advance`, gestion financiera (`api/finance/`) y KPIs ejecutivos (Seccion 7).
  - [ ] Control de bitacoras de unidades moviles (Van Terrestre / Embarcacion Maritima) en `equipment_movements`/`fleet_vehicles`.
  - [ ] Unico rol que puede cambiar `role`/`status` de otros usuarios.
- [ ] **Lider de Proyecto** (Germán Lage)
  - [ ] Coordinacion y calidad en set: alta de llamados, asignacion de staff (sujeta a compuerta de anticipo), gestion de checklists.
  - [ ] **Sin acceso a datos financieros**: ocultar/bloquear endpoints de `api/finance/` y campos `total_amount`/`advance_paid` en vistas que no sean estrictamente de agenda operativa.
  - [ ] Acceso de lectura a Clientes/Programas asociados a sus proyectos.
- [ ] **Staff Tecnico** (Gibrán Morales, Antonio Murillo)
  - [ ] Operacion de checklists (Etapas 1-3) limitada a llamados donde figuren en `call_assignments`.
  - [ ] Bitacoras de hardware: Check-Out/Check-In en `equipment_movements` solo para activos vinculados a su llamado asignado.
  - [ ] Sin acceso a organigrama de staff ni a modulos administrativos.
- [X] **Lider_Logistica** (renombrado desde `Chofer_Logistica` en Fase 4 — ver Apendice Fase 4)
  - [ ] Acceso de lectura/escritura a rutas y consumo de combustible de la Van Terrestre y la Embarcacion Maritima.
  - [ ] Check-In/Check-Out restringido a `asset_type = 'Vehiculo'`.
  - [ ] Sin acceso a checklists de set (Estudio 5 de Mayo) salvo asignacion explicita.
- [ ] Codificar `middleware/permissions.php` con tabla declarativa `ROLE_CAPABILITIES` consumida por todos los endpoints (evitar reglas dispersas/duplicadas por archivo).

### 4.2 Flujo de Handshake Legal restrictivo

- [ ] En `api/login.php` (post-autenticacion exitosa):
  - [ ] Consultar `user_legal_signatures` del usuario, validando columnas `signature_hash` y `device_fingerprint`.
  - [ ] Si existe al menos un registro con valor `0`/`FALSE` (firma pendiente o fingerprint no validado):
    - [ ] Ejecutar desvio obligatorio: `header('Location: ../legal/signatures.php')` (ruta relativa).
    - [ ] Bloquear el acceso a **todos** los demas modulos del panel hasta completar el handshake (replicar verificacion en `auth_guard.php`/`middleware/auth.php`).
- [ ] `legal/signatures.php`:
  - [ ] Renderiza documentos legales pendientes y captura `signature_hash` (hash del documento + datos del firmante) y `device_fingerprint` (huella del dispositivo/navegador).
  - [ ] Al completar firma: `UPDATE user_legal_signatures SET signed = 1, signature_hash = :hash, device_fingerprint = :fp, signed_at = NOW()`.
- [ ] **Archivo final de evidencia (PDF)**
  - [ ] Generar PDF de evidencia de firma (documento + hash + fingerprint + timestamp).
  - [ ] Resguardar de forma **aislada** en `/legal_archive/` (fuera de `api/` y de rutas servibles publicamente; acceso solo via endpoint autenticado de Administrador).
  - [ ] Nomenclatura sugerida: `/legal_archive/{user_id}/{document_id}_{timestamp}.pdf`.
  - [ ] Verificar que `/legal_archive/` este excluido de git (`.gitignore`) y protegido por `.htaccess`/reglas de Apache (denegar listado y acceso directo).

---

## 5. ESPECIFICACIONES DE ENDPOINTS CRUD SEGUROS (CAPA API)

### 5.1 CRUD con borrado logico (`deleted_at`, `deleted_by`)

- [ ] **Usuarios** (`api/users/`)
  - [ ] Migracion aditiva: agregar columnas `deleted_at` (DATETIME NULL) y `deleted_by` (FK → `users.id`, NULL).
  - [ ] Toda consulta `SELECT` de listado filtra `WHERE deleted_at IS NULL` por defecto.
  - [ ] Accion `delete` (soft-delete): `UPDATE users SET deleted_at = NOW(), deleted_by = :admin_id WHERE id = :id` — reemplaza/complementa el `status = 'Suspendido'` actual sin perder el historial.
  - [ ] Todas las consultas usan **PDO con declaraciones preparadas** (`prepare()` + `execute([...])`), `PDO::ATTR_EMULATE_PREPARES => false`.
- [ ] **Clientes** (`api/clients/`)
  - [ ] Migracion aditiva: agregar `deleted_at`, `deleted_by` (complementa `is_active` ya existente para distinguir "pausado" de "eliminado").
  - [ ] CRUD completo (`list`, `create`, `update`, `delete`) con PDO parametrizado.
- [ ] **Programas** (`api/programs/`)
  - [ ] Migracion aditiva: agregar `deleted_at`, `deleted_by`.
  - [ ] Validar integridad referencial con `clients` antes de soft-delete (advertencia si tiene `calls` futuros).
- [ ] **Proyectos** (`api/projects/`)
  - [ ] Definir esquema base `projects` (id, nombre, cliente/programa asociado, fechas, `deleted_at`, `deleted_by`).
  - [ ] CRUD completo con PDO parametrizado y soft-delete consistente con los modulos anteriores.
- [ ] Estandarizar en los 4 modulos:
  - [ ] Sanitizacion/validacion de entrada (`mh_guard_request()` + Troll Mode antes de tocar la capa de datos).
  - [ ] Contrato de respuesta uniforme `{status, message, data}` con codigos HTTP explicitos (`200/201/4xx/5xx`).
  - [ ] Token CSRF obligatorio en toda operacion `POST`/`PUT`/`DELETE`.

### 5.2 Escalamiento Troll Mode (contadores en `security_logs`)

- [ ] Esquema `security_logs`: `id`, `ip_address`, `user_id` (nullable), `event_type`, `context`, `payload`, `created_at`. (Implementado en `database/migration_fase3_security.sql` sin columna `user_id` — pendiente agregarla en una migracion aditiva posterior).
- [X] Contador de intentos por IP/usuario en ventana deslizante (ej. 15 minutos):
  - [X] **3 intentos** de patron malicioso/credenciales invalidas → **bloqueo temporal de IP** (ej. 30 minutos, registrado en tabla `ip_blocks` o cache).
  - [X] **10 intentos** → **registro en blacklist inmutable del servidor** (tabla `ip_blacklist`, append-only, sin `UPDATE`/`DELETE` permitido a nivel aplicacion).
- [ ] `middleware/troll_mode.php` consulta `ip_blocks`/`ip_blacklist` al inicio de cada request **antes** de procesar middleware de autenticacion (rechazo temprano `403`). (Implementado parcialmente: `mh_enforce_ip_reputation()` en `api/security.php` se invoca dentro de `mh_guard_request()` para formularios POST, no como middleware global de entrada).
- [ ] Job/endpoint administrativo de solo lectura para auditar `security_logs` (Administrador), sin capacidad de editar/borrar entradas (integridad de bitacora).

---

## 6. MOTOR DE NOTIFICACIONES Y CORREOS CORPORATIVOS HTML

### 6.1 Estandar visual y tecnico del motor de correo

- [ ] Paleta institucional V2: **Deep Sea Blue `#0B2D48`** (fondos/encabezados) + acentos **Turquoise `#1CC7C1`** (CTAs/links).
- [ ] Tipografias cargadas **localmente** (no via CDN externo) para garantizar renderizado consistente en clientes de correo: empaquetar `@font-face` con archivos `.woff2` servidos desde `assets/fonts/`.
- [ ] Layout responsivo base de 600px (tabla HTML compatible con clientes legacy), reutilizable via `mh_email_layout(string $title, string $bodyHtml, string $footerNote = '')`.
- [ ] Envio best-effort: `mh_send_mail()` no lanza excepciones; registra fallos en `mail.log` sin interrumpir flujos de negocio.

### 6.2 Plantilla 1 — Bienvenida + contrasena temporal

- [ ] Generacion de contrasena temporal segura.
- [ ] Hash con **Bcrypt cost 12** (`password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])`) antes de persistir.
- [ ] Contenido del correo: nombre completo, ID de usuario unico, contrasena temporal (texto plano solo en el correo, nunca en BD ni logs), CTA hacia el portal de acceso.

### 6.3 Plantilla 2 — Registro de Nuevo Programa de Clientes Jornal

- [ ] Disparo automatico al ejecutar `POST api/programs/create` cuando el cliente asociado tiene `email` registrado.
- [ ] Contenido: nombre del cliente, nombre del programa, descripcion, fecha de alta.

### 6.4 Plantilla 3 — Confirmacion de Fecha Bloqueada

- [ ] **Activacion automatica** al pasar `calls.advance_paid` de `0` a `1` (transicion `PENDING_ADVANCE → ADVANCE_PAID`, Seccion 2.2).
- [ ] Contenido: cliente, programa, titulo del llamado, locacion, fecha, horario, staff asignado (si ya existe).
- [ ] Envio al `email` del cliente asociado al programa (best-effort, sin bloquear la transaccion principal si falla).

### 6.5 Plantilla 4 — Enlace seguro de recuperacion de contrasena (Clientes/Staff)

- [ ] Esquema `password_reset_tokens`: `id`, `user_id` (FK), `token_hash`, `expires_at`, `used`, `created_at`.
- [ ] Generar token aleatorio (`random_bytes(32)`), almacenar **solo** `hash('sha256', $tokenRaw)` en `token_hash`.
- [ ] **Expiracion rigida de 1 hora** (`expires_at = now + 3600s`); validar `used = 0` y `expires_at > NOW()` antes de aceptar el reset.
- [ ] Invalidar (`used = 1`) todos los tokens previos del usuario al completar un reset exitoso.
- [ ] No revelar si el correo existe en el sistema (mismo mensaje de respuesta en ambos casos).
- [ ] Tras reset exitoso, re-hash de la nueva contrasena con **Bcrypt cost 12**.

---

## 7. CENTRO DE COMANDO EJECUTIVO (MODULOS DE KPIS RECOMENDADOS)

> Todos los KPIs se exponen via `api/finance/` (acceso exclusivo Administrador, ver Seccion 4.1) mediante pipelines agregados de SQL sobre PDO, optimizados con indices en `calls.call_date`, `calls.status` y `calls.advance_paid`.

- [X] **Ingresos mensuales**
  - [X] Pipeline: `SUM(total_amount)` de `calls` agrupado por mes/anio, filtrando `status IN ('Confirmado','Completado')` (implementado en `api/finance.php?action=kpis` → `monthly_revenue`; pendiente incorporar el estado `ARCHIVED` de la maquina de estados V2).
- [ ] **Utilidades proyectadas (matriz de costos)**
  - [ ] Definir/persistir matriz de costos fijos + variables por tipo de llamado/locacion.
  - [ ] Pipeline: `ingresos_mensuales - costos_proyectados` por periodo, con desglose por programa/cliente. (Implementado como simplificacion provisional: `monthly_revenue * MH_PROJECTED_PROFIT_MARGIN (40%)` en `api/finance.php` → `projected_profit`, sin matriz de costos persistida).
- [X] **IVA acumulado de ley (16%)**
  - [X] Pipeline: `SUM(total_amount) * 0.16` sobre llamados facturables del periodo, con corte mensual para declaracion (implementado en `api/finance.php?action=kpis` → `iva_accrued`).
- [X] **Horas de transmision en vivo consumidas**
  - [X] Pipeline equivalente: `SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))) / 3600` para llamados `Confirmado`/`Completado` del mes (implementado en `api/finance.php?action=kpis` → `studio_hours`; pendiente migrar a estados `LIVE`/`ARCHIVED` de la maquina V2).
- [X] **Equipos en mantenimiento**
  - [ ] Pipeline: `COUNT(*)` de `inventory_items` + `fleet_vehicles` con `status = 'Mantenimiento'`, con detalle de `damage_report` desde `equipment_movements`. (Implementado parcialmente: `api/finance.php?action=kpis` → `fleet_maintenance` solo cubre `fleet_vehicles` (Van Terrestre/Embarcacion Maritima); falta `inventory_items` y detalle de `damage_report`).
- [ ] **Alertas pendientes**
  - [ ] Pipeline agregado que consolida: llamados con `advance_paid = 0` proximos (≤72h), checklists "Antes" incompletos a T-15min de Simulcast, temperaturas de set reportadas >32°C sin resolver, e intentos de seguridad escalados (Seccion 5.2) sin revisar.
- [ ] Endpoint unico `GET api/finance/dashboard.php?action=kpis` que devuelve los 6 bloques anteriores en una sola respuesta JSON `{status, message, data: {ingresos, utilidades, iva, horas_transmision, equipos_mantenimiento, alertas}}` para consumo directo del Dashboard Ejecutivo. (Implementado como `GET api/finance.php?action=kpis`, devolviendo `monthly_revenue`, `projected_profit`, `iva_accrued`, `pending_advances`, `studio_hours`, `fleet_maintenance` y `period`; faltan el bloque "alertas" consolidado y la ruta exacta `api/finance/dashboard.php`).

---

## APENDICE — VEREDICTO DE CONTINUIDAD DE CABLES (CIERRE FASE 2/3)

> Generado tras la ejecucion de las migraciones fisicas (`database/migration_fase2.sql` y `database/migration_fase3_security.sql`) sobre `tecnidepot_mediahub_db` por el Super Administrador.

- [X] **Suite de diagnostico de lazo cerrado** (`api/test_integration.php`):
  - [X] Abre `beginTransaction()`, ejecuta CRUD completo sobre `users`, `clients`, `programs`, `calls`, `inventory_items` y `checkinout_log` (alta de staff, alta de cliente/programa, llamado de prueba, item de inventario, bitacora de check-out) y fuerza `rollBack()` al finalizar — la base de datos queda intacta.
  - [X] Replica el query espejo del algoritmo anticolisiones del Estudio 5 de Mayo (Seccion 2.1/2.2) y confirma que detecta el solapamiento simulado.
  - [X] Ejecutado contra `tecnidepot_mediahub_db` en el nodo local (XAMPP): **6/6 pruebas con continuidad verificada** — "TODOS LOS CABLES ESTAN PERFECTAMENTE CONECTADOS Y SOLDADOS CON LA BASE DE DATOS MASTER".
- [X] **Verificacion de tablas fisicas de escalamiento perimetral (Seccion 5.2)**:
  - [X] `security_logs` — creada y operativa.
  - [X] `ip_blocks` — creada y operativa (bloqueo temporal de 30 min a partir de 3 intentos).
  - [ ] `ip_blacklist` — **pendiente**: no se encontro en el esquema fisico tras la migracion; `mh_troll_escalate()` ya contempla esta tabla (umbral de 10 intentos) y degrada con gracia (try/catch) hasta que se cree. Requiere re-ejecutar el bloque `CREATE TABLE IF NOT EXISTS ip_blacklist` de `database/migration_fase3_security.sql` (aditivo, no destructivo).
- [X] **Verificacion visual/funcional del Dashboard** (`dashboard/index.php` + `assets/js/dashboard.js`):
  - [X] Favicon institucional (`../assets/img/logo.png`) presente en `<head>` (`link rel="icon"`).
  - [X] Logotipo corporativo en navbar/sidebar con escalado mobile-first (`h-16 md:h-22 object-contain`).
  - [X] Menu hamburguesa (`#sidebarToggle`/`#sidebarClose`/`#sidebarOverlay`) con `openSidebar()`/`closeSidebar()` y cierre automatico al navegar (`bindNavHighlight`).
  - [X] Alerta dinamica de bloqueo de staff por anticipo (`advance_paid = 0`) con el porcentaje y monto MXN calculados en tiempo real (`advancePct`/`advanceAmount`, `assets/js/dashboard.js`).
- [X] **Lint final**: `php -l` sin errores en todos los `.php` modificados/creados (`api/`, `config/`, `dashboard/`) y `node --check` sin errores en `assets/js/dashboard.js`.

---

## APENDICE — REFACTOR DE ROLES Y ALTA DE CUENTAS OFICIALES (FASE 4)

> Generado tras la ejecucion de `database/migration_fase4_roles_seed.sql` sobre `tecnidepot_mediahub_db` por el Super Administrador, en preparacion del despliegue a GreenGeeks.

- [X] **Refactor semantico `Chofer_Logistica` -> `Lider_Logistica`**:
  - [X] `api/users.php` (`MH_VALID_ROLES`).
  - [X] `assets/js/dashboard.js` (`ROLE_OPTIONS`).
  - [X] `dashboard/index.php` (`$roleLabels`, `$roleDescriptions` y `<option>` del formulario `#userForm`).
  - [X] `api/response.php` y `api/auth_guard.php` revisados: no contienen strings de rol especificos, sin cambios necesarios.
  - [X] ENUM de `users.role` migrado de forma segura (3 pasos: ampliar -> migrar filas -> cerrar) al set canonico final: `('Administrador','Lider_Proyecto','Staff_Tecnico','Lider_Logistica','Cliente')`.
- [X] **Alta de cuentas oficiales del organigrama** (password temporal `MediaHub2026!`, Bcrypt cost=12):
  - [X] `admin.glage` — German Lage — `leolageacadep@gmail.com` — rol `Administrador` — status `Activo` — 4/4 firmas legales precargadas (`signed = 1`, `ip_address = '127.0.0.1'`) -> acceso directo al Dashboard sin bloqueo de firmas.
  - [X] `logistica.gmorales` — Gibran Morales — `gibranmorales700@gmail.com` — rol `Lider_Logistica` — status `Activo` — 4/4 firmas legales pendientes (`signed = 0`) -> forzara firma de reglamentos en su primer login (`legal/firma.php`).
- [X] Migracion ejecutada en vivo via `config/Database.php` (PDO Singleton): 8 sentencias OK, usuarios y firmas verificados post-insercion, ENUM final confirmado con `SHOW COLUMNS`.

---

## APENDICE — AUDITORIA DE DESPLIEGUE GREENGEEKS (FASE 4.1)

- [X] **Login hibrido (`api/login.php`)**: el campo del formulario acepta correo electronico (`email`) o identificador unico (`user_id`). Query: `WHERE email = :login_email OR user_id = :login_user_id` (placeholders distintos por `PDO::ATTR_EMULATE_PREPARES = false`). Bcrypt cost=12, CSRF y redirecciones relativas (`../index.php`, `../legal/firma.php`, `../dashboard/index.php`) intactas.
  - [X] `index.php`: input `#email` cambiado de `type="email"` a `type="text"` (label "Correo corporativo o ID de usuario") para no bloquear via validacion HTML5 el ingreso de `user_id`.
  - [X] `assets/js/login.js`: `validateEmail()` acepta formato de correo O patron de `user_id` (`/^[a-zA-Z0-9_.]{3,50}$/`).
- [X] **Auditoria de rutas del Dashboard (`dashboard/index.php` + `assets/js/dashboard.js`)**: sin dependencias de Bootstrap/jQuery (verificado via grep), 100% Tailwind CSS via CDN. Confirmados: favicon institucional (`../assets/img/logo.png`), menu hamburguesa (`#sidebarToggle`/`#sidebarClose`/`#sidebarOverlay`), switch de tema claro/oscuro (`#themeToggle` + `localStorage['mh-theme']`) y las 6 tarjetas de KPIs ejecutivos (`#kpiGrid` <- `api/finance.php?action=kpis`) para el rol `Administrador`.

---

## APENDICE — PUENTE FORENSE DE PRODUCCION Y AUDITORIA TAILWIND (FASE 4.2)

- [X] **Puente de diagnostico forense seguro (`api/login.php`)**: en los bloques `catch (PDOException)` / `catch (RuntimeException)`, si `MH_DEBUG_TOKEN` (definido en `.env`, fuera del repo) coincide via `hash_equals()` con `?debug_token=` de la URL, se imprime `get_class($e)`, `$e->getMessage()`, `$e->getFile()`/`getLine()` y `getTraceAsString()` en texto plano. Sin token o sin coincidencia, degrada siempre a `header('Location: ../index.php?error=server')` (ruta relativa). Permite diagnosticar en caliente en GreenGeeks `.env` ausente/invalido o credenciales de MySQL remoto rechazadas, sin exponer nada a usuarios publicos.
  - [X] `.env` local actualizado con `MH_DEBUG_TOKEN` (gitignored, no se sube al repo — debe configurarse manualmente en el `.env` de GreenGeeks para activar el puente en produccion).
- [X] **Advertencia de consola Tailwind CDN (`index.php` y `dashboard/index.php`)**: se agrego un comentario estructurado documentando la migracion futura a Tailwind CLI (build step en `deploy.yml`) y un filtro minimo de `console.warn` que oculta unicamente el mensaje "cdn.tailwindcss.com should not be used in production", sin alterar clases utilitarias ni el renderizado mobile-first (Deep Sea Blue / Turquesa).

---

## APENDICE — PARSER .ENV TOLERANTE A FALLOS (FASE 4.3)

- [X] **Refactor del cargador de entorno (`config/Database.php`)**: se reemplazo `parse_ini_file()` (rompia por completo al encontrar `$`, `;` o `=` adicionales dentro de `DB_PASS` reales de cPanel GreenGeeks, devolviendo `false` para todo el archivo) por `Database::parseEnvFile()`, un lector linea por linea con `file()`. Ignora comentarios (`#`/`;` al inicio de linea), separa clave/valor por el PRIMER `=`, aplica `trim()` y remueve unicamente comillas envolventes (simples o dobles) sin tocar `$`/`;` internos. Si falta `DB_HOST`, `DB_NAME`, `DB_USER` o `DB_PASS`, el `RuntimeException` detalla las llaves faltantes solo si `MH_DEBUG_TOKEN` coincide via `hash_equals()` con `?debug_token=`; en caso contrario el mensaje permanece generico ("Configuracion de entorno incompleta.").
  - [X] `Database::loadEnv()` (estatico, cacheado) expone el `.env` parseado para consumidores internos; `api/response.php::mh_app_env()` ahora delega en `Database::loadEnv()` en lugar de su propio `parse_ini_file()`, eliminando el mismo punto de falla en el puente forense de `api/login.php`.
- [X] **Sensor de continuidad (`api/debug_db.php`)**: invoca `Database::loadEnv()` para listar (sin exponer valores) que llaves de `DB_HOST`/`DB_NAME`/`DB_USER`/`DB_PASS` se detectaron antes de intentar la conexion, y separa el `catch` en `RuntimeException` (entorno invalido) vs `PDOException` (rechazo real de MySQL: usuario/prefijo/privilegios de cPanel), imprimiendo el mensaje exacto de cada caso.

---

*Este checklist es la fuente de verdad de tareas backend para Media HUB V2. Cada casilla marcada debe corresponder a codigo verificado con `php -l`, pruebas funcionales del endpoint y, cuando aplique, una migracion aditiva documentada en `database/`. No se debe marcar una tarea como completada si introduce una regresion en los modulos vigentes de Fase 1/Fase 2.*
