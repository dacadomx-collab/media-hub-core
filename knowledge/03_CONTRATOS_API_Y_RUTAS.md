# 03_CONTRATOS_API_Y_RUTAS — {{PROJECT_NAME}}
## Contratos JSON Inmutables, Payloads y Reglas de Auth por Endpoint
**Versión:** 1.0 | **Fecha de consolidación:** 2026-06-11 | **Clasificación:** Ley Suprema — Pilar Canónico
**Fuentes consolidadas:** 03_CONTRATOS_API_Y_LOGICA.md, 03_API_CONTRACTS_AND_ROUTING.md

> Estos contratos son inmutables (Mandamiento 5). Ningún endpoint altera sus propiedades JSON sin actualizar este documento. Claude DEBE rechazar cargas inválidas con HTTP 422 antes de tocar
> la DB. No reinventar respuestas — si no está aquí documentado, no se implementa sin aprobación.

---

## 📡 PROTOCOLO BASE DE INTEGRACIÓN

- **Intercambio:** JSON UTF-8 exclusivamente. Prohibido form-data en endpoints de IA.
- **Content-Type obligatorio (POST):** `application/json` — el servidor rechaza con `415` si difiere.
- **CORS Security:** Estricto (mismo origen) en `/SECURITY`. Por token en `/CORE` y `/PARTNERS`.
- **Estructura Standard de Respuesta:**
  ```json
  { "status": "success|error", "message": "string", "data": { ... } }
  ```
- **Enforcement:** Claude crea validadores PHP estrictos por endpoint. Sin validación = sin merge.
- **Librería de Snippets:** Antes de crear un componente, consultar `/knowledge/` por uno existente.
- **Regla de Oro — Arquitectura de Endpoints:** Todo endpoint PHP consumible por el frontend **vive en `/api/`**. Las clases internas (PDO, Auth, motores de IA) viven en `/CORE/src/` y se consumen con `require_once`. Ningún archivo de `/CORE/src/` ni `CORE/.env` es accesible por HTTP (bloqueado por `.htaccess`). Sin excepción.

---

## 🛠️ CONTRATO 1 — The Scanner

**Ruta:** `SECURITY/GEM/api_scan.php`
**Método:** `POST`
**CORS:** Estricto — solo mismo origen o `localhost/127.0.0.1`
**Auth:** Sin token (el CORS es la primera línea de defensa)

### Payload (Frontend → Backend):
```json
{
  "dominio": "string — REQUERIDO. Ej: ejemplo.com (sin protocolo)",
  "phase":   "string — OPCIONAL. Default: all",
  "check":   "string — SOLO si phase=check"
}
```

**Valores válidos para `phase`:**
| Valor | Módulo que activa |
| :--- | :--- |
| `all` | Ejecuta todos los módulos en secuencia (default) |
| `recon` | OSINT: WHOIS + DNS + Blacklist |
| `military` | Auditoría de headers HTTP completa |
| `military_headers` | Solo cabeceras de seguridad |
| `military_ssl` | Solo certificado SSL/TLS |
| `wp_audit` | Detección y auditoría WordPress |
| `port_scan` | Radar de puertos críticos |
| `check` | Checklist Oro (requiere campo `check`) |

**Valores válidos para `check`** (solo cuando `phase=check`):
| Valor | Verificación |
| :--- | :--- |
| `env` | Exposición de archivo `.env` público |
| `htaccess` | Blindaje `.htaccess` activo |
| `indexes` | Directory listing desactivado |
| `ipintel` | Inteligencia de IP (geolocalización, VPN, reputación) |

### Response (Backend → Frontend):
```json
{
  "status": "success",
  "domain": "string — dominio normalizado y validado",
  "data": {
    "phase": "string",
    "recon":            { "whois": {}, "dns": {}, "blacklist": {}, "lines": [] },
    "military":         { "lines": [] },
    "military_headers": { "lines": [] },
    "military_ssl":     { "lines": [] },
    "wp_audit":         { "lines": [] },
    "port_scan":        { "lines": [] },
    "checklist":        { "lines": [] }
  }
}
```

### Reglas de Piedra — The Scanner:
1. **Sanitización obligatoria:** El dominio se normaliza eliminando protocolo, path, query y puerto. Regex estricta FQDN.
2. **Solo hostnames válidos:** IPs directas rechazadas. Longitud máxima 253 caracteres.
3. **Método POST exclusivo:** GET = `405 Method Not Allowed`.
4. **Content-Type estricto:** Diferente a `application/json` = `415 Unsupported Media Type`.
5. **Sin datos internos expuestos:** El scanner audita dominios externos. Nunca apunta a la DB interna.

---

## 🛠️ CONTRATO 2 — DCD Engine (Motor IA SaaS)

**Ruta:** `CORE/ChatBot AI-generated/api/v1/dcd_engine.php`
**Método:** `POST`
**Auth:** Token de socio validado contra `clientes_saas.token` + `estatus = 'activo'`
**Modelo IA:** `gpt-4o-mini`

### Payload (Frontend del Socio → Engine):
```json
{
  "message": "string — REQUERIDO. Mensaje del usuario final",
  "token":   "string — REQUERIDO. Token único del socio DCD",
  "history": "array  — OPCIONAL. Historial previo [{role, content}]"
}
```

### Response (Engine → Frontend del Socio):
```json
{
  "success":  true,
  "response": "string — respuesta generada por OpenAI"
}
```

### Response de Error:
```json
{ "success": false, "response": "DCD_LABS: Mensaje de error genérico." }
```

### Reglas de Piedra — DCD Engine:
1. **Sin token válido = `403`.** El engine no procesa ninguna petición sin token activo en DB.
2. **La API Key de OpenAI NUNCA se expone al socio.** Vive exclusivamente en `CORE/ChatBot AI-generated/api/v1/.env` → `DCD_OPENAI_KEY`.
3. **El Source of Truth del prompt** (instrucciones del chatbot del socio) vive en el servidor DCD. El cliente nunca lo ve.
4. **Carga de `.env`:** Se usa `getenv()` con fallback de lectura manual del archivo `CORE/ChatBot AI-generated/api/v1/.env`. Sin hardcode.
5. **Error de método:** `GET` = `403 Metodo no permitido`.

---

## 🛠️ CONTRATO 3 — DCD Extractor (Extractor NLP de Entidades)

**Ruta:** `CORE/ChatBot AI-generated/api/v1/dcd_extractor.php`
**Método:** `POST`
**Auth:** Campo `token` en body igual a `API_TOKEN` (definido en `.env`)
**Modelo IA:** `gpt-4o-mini` con `response_format: json_object`

### Payload:
```json
{
  "token":   "string — REQUERIDO. Token de seguridad del puente",
  "history": "array  — REQUERIDO. Historial del chat a analizar [{role, content}]"
}
```

### Response (JSON limpio de OpenAI):
```json
{
  "nombre":   "string — nombre real extraído del chat",
  "telefono": "string — teléfono extraído o 'No proporcionado'",
  "resumen":  "string — resumen ejecutivo de máx 40 palabras del prospecto"
}
```

### Reglas de Piedra — DCD Extractor:
1. **Sanitización de strings:** Todo input pasa por `strip_tags()` y `mb_substr($str, 0, 200)`.
2. **Retries controlados:** Máximo `MAX_RETRIES = 2` ante fallo de la API de OpenAI.
3. **Respuesta siempre JSON:** `Content-Type: application/json` sin excepción.
4. **Si falta la API Key:** Responde `500` con `{"error": "Internal Server Error: AI Key missing"}`. Sin revelar detalles.
5. **CORS abierto** (`Access-Control-Allow-Origin: *`) — La seguridad real es el token en el body.

---

## 🛠️ CONTRATO 4 — DCD Analyzer (Analizador Comercial de Leads)

**Ruta:** `CORE/ChatBot AI-generated/api/v1/dcd_analyzer.php`
**Método:** `POST`
**Auth:** Token validado contra tabla `clientes_saas` en BD [PROVEEDOR_HOSTING]
**Modelo IA:** `gpt-4o-mini` con `response_format: json_object`

### Payload:
```json
{
  "token":   "string — REQUERIDO. Token único del socio",
  "history": "array  — REQUERIDO. Array de mensajes [{role: string, content: string}]"
}
```

### Response (el JSON de OpenAI pasado directamente al cliente):
```json
{
  "nombre":   "string",
  "telefono": "string",
  "resumen":  "string — máx 40 palabras sobre lo que necesita el prospecto"
}
```

### Reglas de Piedra — DCD Analyzer:
1. **Sin token o sin history = `400 Bad Request`.**
2. **Token inválido o socio inactivo = `403 Forbidden`.**
3. **Contadores obligatorios por petición exitosa:**
   - `total_peticiones = total_peticiones + 1`
   - `total_tokens_ia = total_tokens_ia + tokens_used`
   - `fecha_ultima_peticion = NOW()`
4. **Fallo silencioso en UPDATE de contadores:** Si el UPDATE falla, no se detiene la respuesta. El error se registra en `error_log`. El cliente nunca sabe de fallos internos de DB.
5. **Validación de JSON de OpenAI:** Antes de responder al cliente, verificar `json_last_error() === JSON_ERROR_NONE`. Si falla, responder `500` con mensaje genérico.

---

## 🛠️ CONTRATO 5 — Synaptic Core (Prompts Maestros)

**Ruta:** `api/admin/synaptic_core.php` v2.1
**Auth:** Cookie `axon_token` → `usuarios.token_acceso`, `estatus='activo'` y `rol = super_admin`. Sin token = 401.
**Formato:** JSON UTF-8. Para `POST`, `PUT` y `POST ?action=test`, `Content-Type: application/json`.
**Tabla BD:** `synaptic_prompts` (columnas canónicas: `prompt_sistema`, `prompt_usuario_tpl`)
**Documentación completa:** `knowledge/06_NUCLEO_COGNITIVO_Y_PROMPTS.md`

### GET — lista activa

`GET /api/admin/synaptic_core.php`

**200:**
```json
{ "status": "success", "data": [{ "id": 1, "context_key": "axon_genesis", "version": 1 }] }
```

### GET — historial

`GET /api/admin/synaptic_core.php?context_key=axon_genesis`

**200:** misma estructura que lista activa, ordenada por `version DESC`.

### Payload POST / PUT (Frontend → Backend):
```json
{
  "context_key":          "string — REQUERIDO. Solo [a-z0-9_]. Ej: axon_genesis",
  "nombre":               "string — REQUERIDO",
  "descripcion":          "string — OPCIONAL",
  "prompt_sistema":       "string — Al menos uno de los dos prompts es REQUERIDO",
  "prompt_usuario_tpl":   "string — OPCIONAL. Vacío = caller provee el mensaje",
  "id_proveedor_default": "integer | null — FK → ai_proveedores.id",
  "temperatura_override": "float | null — Rango 0.0–2.0",
  "max_tokens_override":  "integer | null — Rango 1–65535",
  "variables_requeridas": "array | null — Ej: [\"nombre\",\"geolocalizacion\"]",
  "temporada_codigo":     "string | null — Ej: VERANO_2026",
  "etiquetas":            "array | null — Ej: [\"onboarding\",\"genesis\"]"
}
```

**Ejemplo de payload POST — crear v1:**
```json
{
  "context_key": "axon_genesis",
  "nombre": "Onboarding Genesis",
  "descripcion": "string",
  "prompt_sistema": "string",
  "prompt_usuario_tpl": "string",
  "variables_requeridas": ["nombre", "geolocalizacion"],
  "temporada_codigo": "VERANO_2026",
  "etiquetas": ["onboarding", "genesis"],
  "id_proveedor_default": 1,
  "temperatura_override": 0.85,
  "max_tokens_override": 2000
}
```

### Respuesta exitosa POST (201):
```json
{ "status": "success", "message": "Prompt creado como v1.", "id": 42, "version": 1, "data": { "id": 42, "version": 1 } }
```

### PUT — publicar nueva versión

`PUT /api/admin/synaptic_core.php`

Mismo payload que POST. Opera en transacción: archiva la versión activa y crea `version+1`.

### Respuesta exitosa PUT (200):
```json
{ "status": "success", "message": "Versión anterior archivada. Nueva versión v3 activa.", "id": 99, "new_version": 3 }
```

**Ejemplo equivalente (v2):**
```json
{ "status": "success", "message": "Versión anterior archivada. Nueva versión v2 activa.", "id": 43, "new_version": 2, "data": { "id": 43, "new_version": 2 } }
```

### Payload POST ?action=test — Playground:

`POST /api/admin/synaptic_core.php?action=test`

```json
{
  "prompt_sistema":     "string — puede contener {{variables}}",
  "prompt_usuario_tpl": "string — puede contener {{variables}} y {{test_message}}",
  "variables":          "object — { nombre: string, geolocalizacion: string, ... }",
  "test_message":       "string — REQUERIDO. Mensaje de prueba del usuario"
}
```

**Ejemplo de payload Playground:**
```json
{
  "prompt_sistema": "Eres AXON_GENESIS...",
  "prompt_usuario_tpl": "Partner: {{nombre}}. Mensaje: {{test_message}}",
  "variables": { "nombre": "Carlos Reyes" },
  "test_message": "Hola, acabo de ser aprobado."
}
```

### Respuesta exitosa Playground (200):
```json
{
  "status": "success",
  "content": "string — respuesta del LLM",
  "proveedor_usado": "string",
  "modelo_usado": "string",
  "tokens_total": 520,
  "duracion_ms": 1240,
  "compiled_system": "string — prompt sistema compilado con variables sustituidas"
}
```

**Ejemplo equivalente:**
```json
{
  "status": "success",
  "content": "Respuesta del modelo",
  "proveedor_usado": "OpenAI",
  "modelo_usado": "gpt-4o-mini",
  "tokens_total": 520,
  "duracion_ms": 1240,
  "compiled_system": "Eres AXON_GENESIS..."
}
```

### Tabla de errores — Synaptic Core:

| HTTP | Uso |
| :--- | :--- |
| 401 | Sin sesión o token inválido |
| 403 | Rol distinto de `super_admin` |
| 409 | `context_key` duplicado en POST |
| 415 | `Content-Type` distinto de `application/json` |
| 422 | Payload incompleto o inválido |
| 500 | Error interno de consulta o persistencia |
| 502 | Fallo controlado del orquestador IA en Playground |

### Reglas de Piedra — Synaptic Core:
1. **`context_key` inmutable:** POST crea v1. PUT archiva la versión activa e inserta v+1. El context_key no cambia nunca.
2. **Binding PDO explícito obligatorio:** Todos los campos nullable usan `PDO::PARAM_NULL` explícito. El driver MySQL nativo rechaza NULL implícito en FK INT con `ATTR_EMULATE_PREPARES=false`.
3. **Campos JSON en BD:** `variables_requeridas` y `etiquetas` se almacenan como JSON string. Se decodifican a array en la respuesta GET.
4. **Playground no persiste:** `trackUsage=false` en `AiOrchestrator::dispatch()`. No inserta en `consumo_tokens`.
5. **DELETE bloqueado por trigger:** `trg_synaptic_prompts_block_delete` — toda versión es registro histórico de auditoría.
6. **Error logging:** Todo `Throwable` en catch escribe `SQLSTATE + Message + context_key` en `error_log`. El cliente solo recibe el mensaje genérico.

---

## 📋 CONTRATO 6 — Logger (Bitácora Segmentada)

**Clase:** `CORE/src/Logger.php`
**Invocación:** Estática — `Logger::partner()`, `Logger::cliente()`, `Logger::sistema()`
**Tablas destino:** `log_actividad_partners`, `log_actividad_clientes`, `log_actividad_sistema`
**Script SQL:** `backups_y_pruebas/schema_patch_v6_log_segmentation.sql`

### Routing por actor (obligatorio desde v6+):

```php
// Partner autenticado
Logger::partner(idPartner: $id, idUsuario: $uid, accion: 'PROSPECTO_CREADO',
    entidad: 'prospectos', entidadId: $newId, detalle: [...], ip: $ip);

// Cliente final del bot
Logger::cliente(idPartner: $partnerId, sessionId: $hash, accion: 'LEAD_CAPTURADO',
    canal: 'whatsapp', tokensUsados: 120, detalle: [...]);

// Sistema / IA / infraestructura
Logger::sistema(origen: 'AiOrchestrator', accion: 'AI_DISPATCH', nivel: 'info',
    idUsuario: $adminId, contextKey: 'axon_genesis', idProveedor: 1,
    detalle: ['tokens' => 800, 'ms' => 1240], duracionMs: 1240);
```

### Niveles de severidad para `log_actividad_sistema`:

| Nivel | Uso |
| :--- | :--- |
| `info` | Operación normal (AI_DISPATCH, SYNAPTIC_CREATE) |
| `warning` | Degradación parcial (FAILOVER_ACTIVADO) |
| `error` | Fallo sin impacto visible al usuario |
| `critical` | Fallo con impacto confirmado (TODOS_PROVEEDORES_FALLARON) |

### Reglas de Piedra — Logger:
1. **Fallos de log son silenciosos:** El catch de Logger nunca interrumpe la operación principal. Solo loguea a `error_log`.
2. **Append-only:** Las 3 tablas tienen triggers `BEFORE UPDATE` y `BEFORE DELETE` con `SIGNAL SQLSTATE '45000'`.
3. **`$trackUsage = false` en Playground:** No inserta en `consumo_tokens` ni en `log_actividad_sistema`.
4. **Contexto JSON libre:** El campo `detalle` acepta cualquier array PHP que se serializa como JSON. No requiere esquema fijo.

---

## 🛠️ CONTRATO 7 — Verificador de Primer Cliente Cerrado

**Ruta:** `api/partner/verificar_primer_cliente.php`
**Método:** `GET`
**Auth:** Cookie `axon_token` → `usuarios.token_acceso` + `rol IN ('partner', 'super_admin')`. Sin token = 401.
**Mandamiento:** Mandamiento 19 — Blindaje Fiscal por Cartera

### Parámetros opcionales (query string):
| Parámetro | Tipo | Descripción |
| :--- | :--- | :--- |
| `id_usuario` | Integer (opcional) | Solo `super_admin`: verificar otro partner. Omitido = propio perfil. |

### Respuesta exitosa (200):
```json
{
  "status": "success",
  "tiene_cliente_cerrado": true
}
```

```json
{
  "status": "success",
  "tiene_cliente_cerrado": false
}
```

### Lógica interna:
1. Autenticar `axon_token` → obtener `usuarios.id` y `rol`
2. Resolver `partners.id` via `id_usuario`
3. `SELECT COUNT(*) FROM prospectos WHERE id_partner = :id AND etapa_kanban = 'cerrado'`
4. Si COUNT > 0 → `true`. Si COUNT = 0 o no existe partner → `false` (fail-safe)

### Comportamiento del frontend (`wallet/page.tsx`):
- Fetch en `useEffect` al montar el componente
- Si `tiene_cliente_cerrado === false` → overlay con glassmorphism bloquea sección "Datos de Retiro"
- Si endpoint falla (catch) → bloquea por seguridad (fail-safe)
- Si `true` → sección CLABE/CSF/Actualizar disponible

### Reglas de Piedra:
1. **Fail-safe:** Si el endpoint no responde o devuelve error, el frontend bloquea los datos fiscales. Nunca falla abierto.
2. **Sin exposición de datos:** El endpoint solo devuelve un booleano. Nunca expone id_partner ni datos del prospecto.
3. **PDO ATTR_EMULATE_PREPARES = false:** Anti-SQLi nativo por configuración de `conexion.php`.
4. **Cache-Control: no-store:** El resultado no se cachea en el navegador para garantizar verificación en tiempo real.

---

## 🛠️ CONTRATO 8 — Flujo Completo de consumo_tokens (Orquestador IA)

**Módulo central:** `CORE/src/AiOrchestrator.php` + `CORE/src/UsageTracker.php`
**Tabla destino:** `consumo_tokens` (append-only)
**Monitor frontend:** `z/app/mtx1/admin/consumption-monitor/page.tsx` ← `api/admin/consumption_monitor.php`

### Cadena de escritura (llamada de producción, `trackUsage = true`):

```
AiOrchestrator::dispatch(messages, idUsuario, contextKey, trackUsage=true)
  │
  ├─ callProvider() → respuesta del LLM
  │     └─ result: {content, modelo_usado, tokens_entrada, tokens_salida, duracion_ms}
  │
  ├─ UsageTracker::record()
  │     └─ INSERT INTO consumo_tokens
  │            estatus = 'exitoso'    ← valor canónico (ver ENUM abajo)
  │
  └─ Logger::sistema(accion='AI_DISPATCH', nivel='info')
        └─ INSERT INTO log_actividad_sistema
```

### ENUM canónico de `consumo_tokens.estatus`:

| Valor | Cuándo se usa |
| :--- | :--- |
| `exitoso` | Llamada directa exitosa al primer proveedor |
| `error_proveedor` | El proveedor devolvió HTTP != 200 |
| `timeout` | cURL timeout agotado |
| `fallback_activado` | Éxito tras failover a proveedor secundario |
| `completado` | Alias legacy del patch v5 — equivale a `exitoso` |

**⚠️ Fix registrado:** El patch v5 creó el ENUM con valores incorrectos (`'pendiente','completado','error'`). El patch v7 (`schema_patch_v7_fix_consumo_tokens_enum.sql`) los amplía para incluir los valores canónicos del PHP. Sin el patch v7, `UsageTracker::record()` falla silenciosamente y el monitor siempre muestra ceros.

### Contrato JSON de `consumption_monitor.php`:

**GET `?period=7d|30d|90d|all`** → respuesta canónica:
```json
{
  "status":  "success",
  "period":  "30d",
  "kpis": {
    "total_tokens":     12500,
    "total_llamadas":   45,
    "avg_tokens":       277,
    "costo_total_usd":  0.003750,
    "usuarios_activos": 3,
    "avg_latencia_ms":  1240
  },
  "porcentaje_proveedores": [
    {
      "proveedor_slug":   "openai",
      "proveedor_nombre": "OpenAI",
      "prioridad":        10,
      "total_tokens":     10000,
      "total_llamadas":   36,
      "pct_of_total":     80.0
    }
  ],
  "top_usuarios": [
    {
      "rank":             1,
      "id_usuario":       1,
      "nombre_usuario":   "Admin DCD",
      "email":            "admin@dcd.com",
      "id_proveedor":     1,
      "proveedor":        "OpenAI",
      "proveedor_slug":   "openai",
      "total_tokens":     10000,
      "total_llamadas":   36,
      "avg_tokens":       277,
      "costo_usd":        0.003000,
      "fallback_events":  0,
      "pct_of_total":     80.0,
      "ultima_actividad": "2026-05-28T17:00:00"
    }
  ]
}
```

### Script de diagnóstico (temporal):
**Ruta:** `api/admin/test_real_dispatch.php`
**Propósito:** Verificar end-to-end que `AiOrchestrator → UsageTracker → consumo_tokens` funciona.
**Auth:** Cookie `axon_token` + `rol = super_admin`.
**⚠️ ELIMINAR antes del deploy a producción (Mandamiento 18).**

### Reglas de Piedra — consumo_tokens:
1. **`trackUsage = false` en Playground:** El Playground nunca registra consumo real.
2. **ENUM crítico:** El valor `'exitoso'` debe estar en el ENUM de `consumo_tokens.estatus`.
3. **Silent fail = zero telemetría:** Un INSERT que falla silenciosamente vacía el monitor — el error_log es la única señal.
4. **Monitor backward-compatible:** Las queries del monitor usan `IN ('exitoso','completado')` para capturar ambas generaciones del ENUM.

---

## 🧠 LÓGICA DE NEGOCIO — REGLAS GLOBALES DE PIEDRA

1. **Ruta PHP siempre con `__DIR__`:** Prohibidas las rutas relativas simples (`require_once 'archivo.php'`). Siempre `require_once __DIR__ . '/archivo.php'`.
2. **Módulo de error nunca al frontend:** `PDOException`, errores de sintaxis SQL, o fallos de API externa van a `error_log()` internamente. El frontend solo recibe mensajes genéricos amigables.
3. **Blindaje Técnico Universal:** Uso obligatorio de `TRIM`, validación de tipos, `htmlspecialchars()` en salidas HTML, Prepared Statements en todas las queries.
4. **`cyber_score` de {{PROJECT_NAME}}:** El score se calcula dinámicamente. Los rangos para cotización son: `90-100` = Póliza Sentinel (`[PRECIO_MRR_POLIZA_SENTINEL]` `{{CURRENCY}}`/mes); `70-89` = Plan Básico (~`[PRECIO_SETUP_PLAN_BASICO]` `{{CURRENCY}}`); `0-69` = Plan Seguridad ORO (`[PRECIO_SETUP_PLAN_ORO_MIN]`–`[PRECIO_SETUP_PLAN_ORO_MAX]` `{{CURRENCY}}`). Siempre incluir opción Póliza Sentinel en propuesta.

---

## 🌐 ESTÁNDAR GLOBAL — CÓDIGOS HTTP DEL SISTEMA

| Código | Uso |
| :--- | :--- |
| `200` | OK — operación exitosa con datos |
| `201` | Created — recurso creado exitosamente |
| `204` | No Content — preflight OPTIONS |
| `400` | Bad Request — payload JSON malformado |
| `401` | Unauthorized — sin token o token inválido/expirado |
| `403` | Forbidden — token válido pero sin permisos (rol insuficiente / CORS) |
| `404` | Not Found — recurso no encontrado |
| `405` | Method Not Allowed — método HTTP incorrecto |
| `409` | Conflict — conflicto de estado (ej: cancelar algo ya cancelado) |
| `415` | Unsupported Media Type — `Content-Type` distinto de `application/json` |
| `422` | Unprocessable Entity — payload válido pero datos inválidos (validación) |
| `500` | Internal Server Error — error de servidor (nunca exponer detalles al frontend) |

---

## 🛡️ PATRÓN CANÓNICO DE ENDPOINT BLINDADO (6 CAPAS)

Todo endpoint nuevo en `/api/` sigue este patrón de 6 capas, sin excepción:

```php
<?php
declare(strict_types=1);

// 1. CORS (siempre primero)
require_once __DIR__ . '/cors.php';

// 2. AUTH (en endpoints protegidos)
require_once __DIR__ . '/../CORE/src/jwt.php';
require_once __DIR__ . '/../CORE/src/auth_middleware.php';
requireRole(['admin'], $authPayload);

// 3. MÉTODO HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse('error', 'Método no permitido.', [], 405);
}

// 4. LEER Y VALIDAR PAYLOAD
$raw = file_get_contents('php://input');
$payload = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
// ... validaciones estrictas (422 si fallan) ...

// 5. CONEXIÓN A DB
require_once __DIR__ . '/../CORE/src/conexion.php';
$pdo = (new Database())->getConnection();

// 6. LÓGICA DE NEGOCIO (con try/catch + log)
try {
    $stmt = $pdo->prepare("SELECT * FROM tabla WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetchAll();
    jsonResponse('success', 'Operación exitosa.', $result);
} catch (PDOException $e) {
    error_log('[' . date('Y-m-d H:i:s') . '] [endpoint] ' . $e->getMessage());
    jsonResponse('error', 'Error interno. Intente más tarde.', [], 500);
}
```

**Enforcement — Cero Deriva (JSON Schema):** Por cada endpoint documentado en este pilar, Claude DEBE crear validaciones estrictas en PHP para que la API rechace cargas inválidas con `422` antes de tocar la base de datos. Antes de crear un endpoint nuevo, consultar primero `/knowledge/` y `/CORE/src/` por una clase o helper ya blindado — no reinventar si ya existe.
