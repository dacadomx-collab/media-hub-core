# 06_NUCLEO_COGNITIVO_Y_PROMPTS — {{PROJECT_NAME}}
## AURA AiOrchestrator, Synaptic Core, Telemetría ASFL, AXON_NEXUS y Prompts Maestros
**Versión:** 1.0 (Consolidado) | **Fecha de consolidación:** 2026-06-11 | **Clasificación:** ESTRATÉGICO — Núcleo Cognitivo AURA, Documentación Viva [INMUTABLE]
**Fuentes consolidadas:** 09_SYNAPTIC_CORE_MODULE.md, 10_AI_COPILOT_STRATEGY.md, 11_AURA_ENGINE_MANDATE_AND_COGNITIVE_TELEMETRY.md, 12_AXON_NEXUS_HUMAN_RELATIONAL_OPERATING_SYSTEM.md, 13_MATRIZ_DE_RESOLUCION_COGNITIVA.md, GEM.txt, Creador_de_Prompts.txt

> **Propósito del Pilar:** Este documento es el cerebro IA del ecosistema {{PROJECT_NAME}}. Reúne el enrutamiento multinivel del orquestador AURA (AiOrchestrator), la telemetría cognitiva oculta (ASFL), el modelo de prompts versionados (Synaptic Core), el sistema de memoria relacional AXON_NEXUS, la estrategia de copiloto IA, la Matriz de Resolución Cognitiva (marco operativo, perfiles de cliente, motor financiero y discrepancias activas) y los prompts maestros canónicos (GEM y Creador de Prompts). Para datos financieros canónicos (comisiones, tabuladores, retenciones modulares), la única fuente de verdad sigue siendo `05_MATRIZ_FINANCIERA_Y_VENTAS.md`.

---

## SECCIÓN 1 — AURA AiOrchestrator: ENRUTAMIENTO MULTINIVEL (NIVEL 1/2/3)

### 1.1 Declaración de Misión y Propósito Central

La máquina **AURA** (`AiOrchestrator` / `AudioOrchestrator`) constituye el **Núcleo Único Dictador de Inteligencia Artificial** dentro de {{PROJECT_NAME}}. Su diseño matemático y arquitectónico obedece al principio de **Fricción Cero Operativa**.

Queda estrictamente **PROHIBIDO** que cualquier vista, componente de Next.js o controlador secundario de PHP realice conexiones directas mediante HTTP o inicialice llaves de API externas (`OPENAI_API_KEY`, `GROQ_API_KEY`) de manera local o hardcodeada en archivos `.env` periféricos. Toda petición de procesamiento de lenguaje natural (NLP), Texto a Voz (TTS), o Transcripción de Voz (Whisper) debe ser canalizada, auditada y descifrada exclusivamente por los túneles centrales de AURA.

### 1.2 Topología de Alimentación y Seguridad Criptográfica

AURA no consume credenciales en texto plano expuestas en el entorno. Su flujo de alimentación de datos opera así:

1. **Bóveda Central Relacional:** El Super Admin gestiona e inyecta los proveedores en la tabla `ai_proveedores`. Las llaves privadas se almacenan en formato BLOB cifradas mediante algoritmos simétricos avanzados.
2. **Desencriptación al Vuelo (On-the-fly):** Al instanciarse `AudioOrchestrator` o `AiOrchestrator`, el motor extrae de forma segura la variable global `AI_ENCRYPTION_KEY` desde el entorno protegido de `CORE/.env`.
3. **Aislamiento AES-256-CBC:** El software descifra la API Key del proveedor requerido (`groq`, `openai`, `anthropic`, etc.) únicamente en la memoria volátil del servidor para procesar la petición HTTP vía cURL. La llave se evapora del ecosistema en cuanto se cierra la transacción, impidiendo fugas por inyección de código o inspección forense.

Este aislamiento estricto de credenciales bajo el estándar **AES-256-CBC** garantiza que las API Keys corporativas jamás tocan el frontend ni los repositorios públicos de control de versiones. Todo secreto se resguarda en la bóveda cifrada del backend de producción (`CORE/.env`), descifrándose *on-the-fly* en la memoria volátil del servidor utilizando la llave maestra `AI_ENCRYPTION_KEY`, y destruyéndose de inmediato al cerrar la transacción cURL con el proveedor externo.

**Contrato Base de Comunicación (JSON UTF-8)** — todo flujo entre el frontend TDTM y el backend de inferencia debe cumplirlo rigurosamente:

```json
{
  "status": "success|error",
  "message": "string",
  "data": {}
}
```

**Sanitización FQDN Estricta:** Antes de interactuar con el escáner o cualquier capa de persistencia, todo dominio proveído por el Partner se normaliza de forma obligatoria eliminando protocolos (`http://`, `https://`), paths, puertos y queries mediante expresiones regulares estructuradas. (Ver §6.1 más abajo para la tarea de extracción del helper compartido).

### 1.3 Enrutamiento Cognitivo Multinivel — Tabla Canónica de Niveles

Para erradicar la dependencia financiera absoluta de APIs de cobro por token y maximizar el margen operativo del ecosistema, AURA actúa como un **orquestador inteligente de enrutamiento cognitivo multinivel**. El sistema evalúa la complejidad conceptual de la tarea y decide dinámicamente el motor óptimo:

| Nivel de Enrutamiento | Infraestructura Asociada | Costo Operativo | Tipo de Tarea Asignada |
| :--- | :--- | :--- | :--- |
| **NIVEL 1** | Ollama Local (Servidor Linux ACADEP) | **$0.00 MXN (Costo Cero)** | Procesamiento de texto pesado, redacción filosófica, análisis FODA base, resúmenes estructurales. |
| **NIVEL 2** | Gemini CLI / Scripts Python Free Tier | **$0.00 MXN (Costo Cero Externo)** | Investigación de mercado en vivo, Google Search Grounding, auditorías de competencia regionalizada, ráfagas OSINT. |
| **NIVEL 3** | OpenAI (`gpt-4o-mini`) / Claude API | Transaccional (Fallback de Emergencia) | Fallas de entorno local, desbordamiento de contexto (*overflow*), JSONs malformados en cascada, validación crítica. |

### 1.4 Flujo de Orquestación Interna

```
[Petición Frontend]
   ➔ [Análisis de Complejidad AURA]
   ➔ [Decisión de Motor: NIVEL 1 / 2 / 3]
   ➔ [Ejecución de Inferencia]
   ➔ [Normalización JSON]
   ➔ [Persistencia DB]
   ➔ [Telemetría ASFL]
```

> **Nota de implementación:** El failover entre proveedores (NIVEL 3) ya está modelado en la tabla `ai_proveedores` (Codex Tabla 15: `prioridad`, `es_activo`, `modelo_fallback`). Los NIVELES 1 y 2 (Ollama / Gemini CLI) **no tienen aún representación en `ai_proveedores`** — deben evaluarse como `slug`s adicionales de failover o como una capa de decisión previa al `AiOrchestrator.php` actual. Esta evaluación es uno de los hitos pendientes en la hoja de ruta activa (`CLAUDE.md` §8.1).

### 1.5 Selección de Proveedor — Menor Costo Disponible (NIVEL 3)

`AiOrchestrator::dispatch()` itera `ai_proveedores` ordenados por `prioridad ASC`. El proveedor con menor costo operativo debe tener la prioridad numérica más baja en la tabla.

Para clasificación de geo-contexto y memoria (AXON_NEXUS), el `context_key='axon_nexus'` puede tener un `id_proveedor_default` que apunte al modelo más económico activo (e.g., Groq `llama-3.1-8b-instant` en lugar de `gpt-4o`).

### 1.6 Control de Accesos y Auth Gates Integrados

El motor de inferencia valida la identidad del operador cruzando la petición con las cookies `HttpOnly` de Next.js (`axon_token` y `axon_user`). Si el `estatus` del usuario en la tabla `usuarios` está marcado como `pendiente`, `inactivo` o `suspendido`, AURA aborta el hilo de ejecución arrojando un error `401 Unauthorized` de manera inmediata (Mandamiento 14: "CORS ≠ Auth").

### 1.7 Regla de Inmutabilidad Financiera (Contrato DB Append-Only)

El motor de IA estructura y ejecuta sus cálculos asumiendo que las tablas transaccionales y de control de MariaDB (`cotizaciones`, `transacciones`, `log_auditoria_financiera`) son **estrictamente append-only**. Los triggers nativos del sistema (`trg_cotizaciones_block_update`, `trg_cotizaciones_block_delete`, `trg_cotizaciones_price_floor_check`) bloquean con una excepción `SIGNAL SQLSTATE '45000'` cualquier intento de modificación física o eliminación de registros.

Cuando se requiere ajustar una propuesta o corregir un trato comercial, la IA tiene **prohibido** emitir consultas de `UPDATE`; en su lugar debe generar un nuevo `INSERT` utilizando el mismo UUID maestro (`quote_id`), incrementando secuencialmente el campo `version` (v1 → v2 → v3) y recalculando el `financial_hash` (SHA-256) para garantizar la integridad matemática de la auditoría. Una vez confirmado el pago, el sistema congela de forma permanente e irreversible la edición de precios, descuentos y comisiones.

---

## SECCIÓN 2 — TELEMETRÍA COGNITIVA ASFL (AXON SYNAPTIC FLOW LEDGER)

### 2.1 Telemetría en Caliente (ASFL) y Memoria Episódica (AXON_NEXUS)

Cada inferencia o proceso cognitivo ejecutado por la IA debe canalizar de forma obligatoria su telemetría oculta al registro `log_actividad_sistema` mediante el componente **AXON Synaptic Flow Ledger (ASFL)**, capturando métricas de control perimetral:

- `network_latency_ms`
- `db_query_status`
- `synaptic_input_payload`
- `tokens_in_flight`

Estos datos de diagnóstico técnico permanecen completamente invisibles para el cliente final (Mandamiento "ASFL — PROHIBIDO en respuestas al cliente", ver Codex §178–181).

Simultáneamente, el motor activa la **Atomización de Memoria (NEXUS)** para extraer de forma estructurada "Átomos de Memoria" de cada interacción del chat, persistiéndolos en la tabla `partners_memory` bajo tres niveles de retención estricta:

| Nivel de Retención | Significado |
| :--- | :--- |
| `PERMANENT` | Persiste indefinidamente |
| `SEMIPERMANENT` | Persiste con expiración programada |
| `EPISODIC` | Persiste solo durante la sesión/episodio activo |

Segmentados sistemáticamente en canales de:
- **`TACTICAL_CONTEXT`** — datos operativos del negocio y sus prospectos.
- **`Personal Context`** — preferencias y estilo de gestión del Partner.

> **⚠️ DEUDA TÉCNICA HEREDADA (Codex §253):** `NexusMemoryAgent.php` y `genesis_saludo.php` referencian columnas (`id_partner`, `content`, `retention`, `fase`, `geolocalizacion`, `temperatura_cached`) que **NO EXISTEN** en las tablas reales `partners_memory` (que usa `partner_id` y `memory_value`, ver Codex §175–176) y `axon_genesis_sesiones`. Esta refactorización es **prerrequisito** antes de expandir la Atomización de Memoria descrita arriba. (Ver también §4.6 — Reglas de Hierro AXON_NEXUS, y la hoja de ruta `CLAUDE.md` §8.1).

### 2.2 Fórmulas de Costeo Contable — Telemetría de Tokens

Toda interacción de AURA que consuma créditos de IA registra un row en `consumo_tokens` vía `UsageTracker::record()`. Este registro es **visible en tiempo real** en la pantalla de "Consumo de Tokens IA" del Super Admin. Para dar cumplimiento estricto al **Mandamiento 3B**, cada interacción de voz o texto es procesada de forma mandatoria por `UsageTracker::record()`.

Debido a que las APIs de audio de OpenAI y Groq tarifican por caracteres o tamaño de archivo y no por tokens de texto puros, AURA aplica la siguiente normalización contable:

#### 2.2.1 Endpoints con `trackUsage=true`

| Endpoint | Motor | Método | Tracking |
|---|---|---|---|
| `api/partner/genesis_saludo.php` | `AiOrchestrator::dispatch()` | LLM (texto) | tokens_entrada + tokens_salida del API response |
| `api/audio/tts.php` | `AudioOrchestrator::synthesize()` | TTS (audio) | Estimado por caracteres del texto |
| `api/audio/transcribe.php` | `AudioOrchestrator::transcribe()` | Whisper (STT) | Estimado por bytes del archivo de audio |
| `CORE/src/NexusMemoryAgent.php` → `atomize()` | `AiOrchestrator::dispatch()` | LLM (JSON) | tokens_entrada + tokens_salida del API response |

#### 2.2.2 Fórmulas de Estimación de Tokens

**LLM (texto → texto):**
```
tokens_entrada = API response: usage.prompt_tokens
tokens_salida  = API response: usage.completion_tokens
tokens_total   = tokens_entrada + tokens_salida
```

**TTS — Métrica de Voz Saliente (texto → audio):**
```
tokens_entrada = ceil(strlen(texto) / 4)   // ~4 chars por token
tokens_salida  = 0                          // TTS no devuelve tokens de salida
```
*Registrado bajo el Context Key:* `audio_tts` · *Estatus en BD:* `exitoso` | `completado`

**Whisper STT — Métrica de Voz Entrante (audio → texto):**
```
tokens_entrada = ceil(fileSizeBytes / 1000)   // Estimación por bytes (OpenAI no devuelve tokens exactos)
tokens_salida  = ceil(strlen(transcript) / 4)
```
*Registrado bajo el Context Key:* `audio_whisper`

> **Nota de consolidación:** la fórmula de tokens_entrada de Whisper usa `fileSizeBytes / 1000` según la especificación del Algoritmo de Estimación Contable de Audio (Bloque 11_AURA_ENGINE §5.1). La variante de Telemetría de Tokens (Bloque §11.2 del mismo origen) describe `fileSizeBytes / 4`; se conserva `/1000` como la fórmula canónica del Algoritmo de Estimación Contable de Audio por ser la más específica para el caso Whisper, dejando esta nota para auditoría futura del Arquitecto.

#### 2.2.3 Contrato de `consumo_tokens` (tabla de auditoría)

```sql
-- Columnas visibles en el Monitor del Super Admin:
id_usuario     → quién generó el consumo
context_key    → 'axon_genesis' | 'axon_nexus' | 'tts' | 'whisper'
modelo_usado   → 'gpt-4o-mini' | 'llama-3.1-8b' | 'whisper-1' | 'tts-1'
tokens_entrada → input tokens (o estimado)
tokens_salida  → output tokens (o 0 para TTS)
costo_usd      → costo calculado según tarifa del proveedor
duracion_ms    → latencia HTTP total
estatus        → exitoso | error_proveedor | timeout | fallback_activado
created_at     → timestamp para gráficas en tiempo real
```

---

## SECCIÓN 3 — SYNAPTIC CORE: MODELO DE PROMPTS VERSIONADOS

### 3.1 Descripción y Propósito

El **Synaptic Core** es el módulo de gestión de instrucciones de IA del ecosistema TDTM. Provee:

- **Biblioteca centralizada** de prompts del sistema con versionado liviano (append-on-update).
- **Playground integrado** para probar prompts en tiempo real sin persistir resultados.
- **Motor de onboarding AXON_GENESIS** con calibración emocional y enrutamiento de vectores tácticos.
- **Historial de versiones** inmutable para auditoría y rollback cognitivo.

> **Regla de Oro:** Los prompts maestros son el equivalente a código de producción. Cada cambio crea una versión nueva (PUT = archivo viejo + INSERT nuevo). El DELETE está bloqueado por trigger de BD.

### 3.2 Arquitectura de Rutas

| Capa | Ruta | Acceso HTTP |
| :--- | :--- | :--- |
| Frontend (Next.js) | `z/app/mtx1/admin/synaptic-core/page.tsx` | `GET /mtx1/admin/synaptic-core/` |
| Backend PHP | `api/admin/synaptic_core.php` | `GET/POST/PUT /api/admin/synaptic_core.php` |
| Dependencia: Orquestador | `CORE/src/AiOrchestrator.php` | No accesible HTTP (bloqueado en .htaccess) |
| Dependencia: Conexión PDO | `CORE/src/conexion.php` | No accesible HTTP |
| Proveedores | `api/admin/ai_proveedores.php` | `GET /api/admin/ai_proveedores.php` |

### 3.3 Contrato de API — synaptic_core.php

**Auth:** Cookie `axon_token` → `usuarios.token_acceso` + `rol = super_admin`. Sin token válido = `401`.

#### 3.3.1 GET — Listar prompts activos

```
GET /api/admin/synaptic_core.php
```

Devuelve el prompt activo más reciente (`es_activo=1`) de cada `context_key`, ordenados A→Z.

**Response exitosa:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "context_key": "axon_genesis",
      "version": 3,
      "nombre": "Onboarding Genesis v3",
      "descripcion": "Saludo hiper-personalizado post-aprobación del partner",
      "prompt_sistema": "Eres AXON_GENESIS...",
      "prompt_usuario_tpl": "El partner {{nombre}} acaba de...",
      "id_proveedor_default": 1,
      "proveedor_nombre": "OpenAI",
      "temperatura_override": 0.85,
      "max_tokens_override": null,
      "es_activo": true,
      "char_count": 4820,
      "preview": "Eres AXON_GENESIS, el agente de bienvenida...",
      "created_at": "2026-05-28T14:00:00",
      "updated_at": "2026-05-28T14:00:00"
    }
  ]
}
```

#### 3.3.2 GET ?context_key=xxx — Historial de versiones

```
GET /api/admin/synaptic_core.php?context_key=axon_genesis
```

Devuelve **todas las versiones** del `context_key` dado, ordenadas de más reciente a más antigua.
La versión activa (`es_activo=1`) aparece primera.

#### 3.3.3 POST — Crear primer prompt (v1)

```
POST /api/admin/synaptic_core.php
Content-Type: application/json
```

**Payload:**
```json
{
  "context_key":          "axon_genesis",
  "nombre":               "string — REQUERIDO",
  "descripcion":          "string — OPCIONAL",
  "prompt_sistema":       "string — al menos uno de los dos prompts es REQUERIDO",
  "prompt_usuario_tpl":   "string — OPCIONAL",
  "id_proveedor_default": 1,
  "temperatura_override": 0.85,
  "max_tokens_override":  2000
}
```

**Reglas de validación (PHP, antes de tocar la BD):**
- `context_key`: REQUERIDO. Solo `/^[a-z0-9_]+$/`. Max 100 chars.
- `nombre`: REQUERIDO. No vacío.
- Al menos `prompt_sistema` o `prompt_usuario_tpl` debe tener contenido.
- `temperatura_override`: Float 0.0–2.0 si se provee.
- `max_tokens_override`: Integer 1–65535 si se provee.
- Si el `context_key` ya existe → `409 Conflict` (usar PUT para actualizar).

**Response exitosa:**
```json
{ "status": "success", "message": "Prompt creado como v1.", "id": 42, "version": 1 }
```

#### 3.3.4 PUT — Versionar prompt (archive + create v+1)

```
PUT /api/admin/synaptic_core.php
Content-Type: application/json
```

**Payload:** Mismo esquema que POST.

**Comportamiento interno (transacción atómica):**
1. `UPDATE synaptic_prompts SET es_activo=0 WHERE context_key=:ck AND es_activo=1`
2. `INSERT INTO synaptic_prompts (...) VALUES (:ck, maxVersion+1, ..., 1)`
3. Si el INSERT falla → `ROLLBACK` (la versión anterior permanece activa).

**Response exitosa:**
```json
{ "status": "success", "message": "Versión anterior archivada. Nueva versión v4 activa.", "id": 99, "new_version": 4 }
```

#### 3.3.5 POST ?action=test — Playground (sin persistir)

```
POST /api/admin/synaptic_core.php?action=test
Content-Type: application/json
```

**Payload:**
```json
{
  "prompt_sistema":     "string — puede contener {{variables}}",
  "prompt_usuario_tpl": "string — puede contener {{variables}} y {{test_message}}",
  "variables": {
    "nombre": "Carlos Reyes",
    "geolocalizacion": "Los Cabos, BCS"
  },
  "test_message": "Hola, acabo de ser aprobado como partner."
}
```

**Flujo de compilación:**
1. Reemplaza `{{key}}` → `htmlspecialchars(value)` en ambos prompts.
2. Reemplaza `{{test_message}}` → el mensaje de prueba.
3. Construye array `messages` con `role=system` + `role=user`.
4. Llama `AiOrchestrator::dispatch($messages, $adminId, 'playground_test', false)`.
   - `trackUsage=false` → **no** inserta en `consumo_tokens`.
5. Loguea en `log_actividad` con `accion='SYNAPTIC_PLAYGROUND'`.

**Response exitosa:**
```json
{
  "status":           "success",
  "content":          "¡Bienvenido, Carlos! Qué alegría tenerte aquí...",
  "proveedor_usado":  "OpenAI",
  "modelo_usado":     "gpt-4o-mini",
  "tokens_total":     520,
  "duracion_ms":      1240,
  "compiled_system":  "Eres AXON_GENESIS... Partner: Carlos Reyes..."
}
```

### 3.4 Mapa de Variables — AXON_GENESIS

Las variables disponibles para inyección en los prompts del agente genesis se obtienen del perfil del partner en el momento del onboarding. El backend que invoca el agente (endpoint de onboarding, por implementar en `/api/partners/genesis.php`) debe compilarlas y pasarlas a `AiOrchestrator`.

| Variable | Fuente | Tabla/Campo | Ejemplo |
| :--- | :--- | :--- | :--- |
| `{{nombre}}` | Perfil del partner | `usuarios.nombre` | `"Carlos Reyes"` |
| `{{id_nivel}}` | Nivel de gamificación | `partners.id_nivel` → `niveles_gamificacion.nombre` | `"El Roble"` |
| `{{geolocalizacion}}` | IP del request | Geolocalización vía servicio externo (ip-api.com o similar) | `"Los Cabos, BCS, México"` |
| `{{clima_local}}` | API clima (OpenWeatherMap) | Llamada externa por IP/coordenadas | `"Despejado 34°C"` |
| `{{contexto_regional}}` | Regla de negocio | Temporada turística según `geolocalizacion` | `"Alta temporada de verano"` |
| `{{nombre_empresa}}` | Primer proyecto del partner | `prospectos.empresa` (primer registro) | `"TechBaja Solutions"` |

> **Mandamiento 12:** Ninguna API Key de servicios de clima o geolocalización va en código PHP. Todo en `.env`.

### 3.5 Estructura de Tablas

#### 3.5.1 `ai_proveedores`

| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | INT | PK auto-incremental |
| `nombre` | VARCHAR(100) | Nombre legible (OpenAI, Anthropic, etc.) |
| `slug` | VARCHAR(50) | Identificador de código (`openai`, `anthropic`) — UNIQUE |
| `modelo_primario` | VARCHAR(100) | Modelo a usar por defecto |
| `api_key_env` | VARCHAR(100) | Nombre de la variable de entorno que contiene la API Key |
| `base_url` | VARCHAR(500) | Override de endpoint. NULL = SDK por defecto |
| `temperatura_default` | DECIMAL(3,2) | Temperatura por defecto si no hay override |
| `max_tokens_default` | SMALLINT | Max tokens por defecto si no hay override |
| `prioridad` | TINYINT | 1=primera opción de failover del AiOrchestrator |
| `es_activo` | TINYINT(1) | 1=disponible para despacho |

#### 3.5.2 `synaptic_prompts`

| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | INT | PK auto-incremental |
| `context_key` | VARCHAR(100) | Clave de código inmutable. Solo `[a-z0-9_]` |
| `version` | SMALLINT | Auto-incremental por versión del mismo key |
| `nombre` | VARCHAR(255) | Nombre descriptivo legible |
| `descripcion` | TEXT | Propósito y contexto del prompt |
| `prompt_sistema` | MEDIUMTEXT | System prompt. Usa `{{variable}}` |
| `prompt_usuario_tpl` | MEDIUMTEXT | Template del mensaje de usuario |
| `variables_requeridas` | JSON | Array declarativo de variables. Ej: `["nombre","geolocalizacion"]` |
| `temporada_codigo` | VARCHAR(50) | Código de temporada/campaña. Ej: `VERANO_2026` |
| `etiquetas` | JSON | Array de etiquetas. Ej: `["onboarding","genesis"]` |
| `id_proveedor_default` | INT | FK → `ai_proveedores.id`. NULL = failover automático |
| `temperatura_override` | DECIMAL(3,2) | Override de temperatura. NULL = usa default del proveedor |
| `max_tokens_override` | SMALLINT | Override de max_tokens. NULL = usa default del proveedor |
| `es_activo` | TINYINT(1) | 1=versión activa. 0=archivada. Solo una versión activa por context_key |
| `created_at` / `updated_at` | DATETIME | Timestamps automáticos |

**UK:** `(context_key, version)` — No pueden existir dos versiones iguales del mismo key.

#### 3.5.3 `log_actividad`

Registro de todas las acciones administrativas en el sistema. Append-only.

| Acción registrada | `accion` | Módulo |
| :--- | :--- | :--- |
| Crear prompt nuevo | `SYNAPTIC_CREATE` | synaptic_core.php POST |
| Publicar nueva versión | `SYNAPTIC_UPDATE` | synaptic_core.php PUT |
| Ejecutar Playground | `SYNAPTIC_PLAYGROUND` | synaptic_core.php POST?action=test |
| Error en cualquier operación | `SYNAPTIC_ERROR` | synaptic_core.php |

#### 3.5.4 `consumo_tokens`

Registro por llamada al `AiOrchestrator::dispatch()` cuando `trackUsage=true`.
El Playground usa `trackUsage=false` y **no** inserta aquí.

#### 3.5.5 `axon_genesis_vectores`

| Context Key | Código | Nombre | Blueprint |
| :--- | :--- | :--- | :--- |
| `axon_genesis_vector_cero` | `VECTOR_CERO` | Génesis de Marca Absoluto | CLAUDE.md, contratos_api.md, schema_v1.sql, arquitectura_v1.md |
| `axon_genesis_vector_reingenieria` | `VECTOR_REINGENIERIA` | Reingeniería de Identidad | CLAUDE.md, auditoria_legacy.md, plan_migracion.md, contratos_api_v2.md |
| `axon_genesis_vector_expansion` | `VECTOR_EXPANSION` | Expansión de Ecosistemas | CLAUDE.md, mapa_modulos.md, contratos_integracion.md, guia_autonomia.md |

#### 3.5.6 `axon_genesis_sesiones`

Fase | Descripción
:--- | :---
`bienvenida` | Saludo hiper-personalizado con datos del perfil del partner
`calibracion` | Recolección de contexto: primer proyecto, clima local, región
`seleccion_vector` | El partner elige VECTOR_CERO / REINGENIERIA / EXPANSION
`fundacion` | Generación automática de archivos en `/knowledge`
`completado` | Onboarding terminado. Agente en modo operativo normal

**Estructura del Schema de Persistencia de Sesión** — cada llamada de bienvenida escribe una fila persistente e inmutable protegida por triggers anti-modificación:

```sql
CREATE TABLE `axon_genesis_sesiones` (
    `id`                    BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_partner`            INT UNSIGNED        NOT NULL,
    `geolocalizacion`       VARCHAR(255)        NOT NULL, -- "{{GEOLOCATION_PARAMETER}}"
    `clima_cached_status`   VARCHAR(50)         NULL,     -- "Broken Clouds"
    `temperatura_cached`    DECIMAL(5,2)        NULL,     -- 32.00
    `is_traveling`          TINYINT(1)          NOT NULL DEFAULT 0,
    `greeting_generado`     TEXT                NULL,
    `created_at`            DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
);
```

> **Nota de consolidación:** esta tabla guarda relación directa con la "DEUDA TÉCNICA HEREDADA" descrita en §2.1 — `genesis_saludo.php` referencia campos (`geolocalizacion`, `temperatura_cached`) que conviven aquí, pero `NexusMemoryAgent.php` los referencia incorrectamente contra `partners_memory`.

### 3.6 Triggers de Integridad

| Trigger | Tabla | Evento | Acción |
| :--- | :--- | :--- | :--- |
| `trg_synaptic_prompts_block_delete` | `synaptic_prompts` | BEFORE DELETE | SIGNAL — bloquea toda eliminación |
| `trg_synaptic_prompts_restrict_update` | `synaptic_prompts` | BEFORE UPDATE | SIGNAL — solo permite cambiar `es_activo` de 1→0 |
| `trg_synaptic_prompts_validate_key` | `synaptic_prompts` | BEFORE INSERT | SIGNAL — valida formato `[a-z0-9_]` del `context_key` |

### 3.7 Context Keys Reservados (AXON_GENESIS)

Estos `context_keys` son reservados del sistema. No crear prompts con estos nombres para otros propósitos:

```
axon_genesis                    — Prompt base del agente de onboarding
axon_genesis_vector_cero        — Instrucciones del VECTOR_CERO
axon_genesis_vector_reingenieria — Instrucciones del VECTOR_REINGENIERIA
axon_genesis_vector_expansion   — Instrucciones del VECTOR_EXPANSION
```

### 3.8 Consideraciones de Seguridad

1. **Prompts nunca al frontend:** El contenido de `prompt_sistema` es el ADN cognitivo del sistema. El endpoint GET lo devuelve solo a `super_admin` autenticado. Los endpoints de IA del partner (futuros) **nunca** exponen el prompt al cliente.
2. **Interpolación HTML-safe:** El Playground usa `htmlspecialchars()` sobre los valores de variables antes de inyectarlos. Los prompts de producción deben sanitizar por su propia lógica si reciben inputs de usuarios finales.
3. **Temporada y etiquetas:** Los campos `temporada_codigo` y `etiquetas` son solo metadata. No afectan la lógica de dispatch del AiOrchestrator actual. Son para filtrado futuro y auditoría de campañas.
4. **API Keys de clima/geolocalización:** Las llamadas a servicios externos para obtener `clima_local` y `geolocalizacion` del partner se hacen desde PHP (backend). Las API Keys viven en `.env`. El frontend nunca hace esas llamadas.

### 3.9 Historial de Versiones del Módulo Synaptic Core

| Versión | Fecha | Cambio |
| :--- | :--- | :--- |
| v1.0 | 2026-05 | Implementación inicial: CRUD de prompts + Playground |
| v2.0 | 2026-05 | Historial de versiones + modal dedicado |
| **v3.0** | **2026-05-28** | **Rediseño UI premium (WCAG/AURA), panel AXON_GENESIS, vectores tácticos, campos `variables_requeridas`/`temporada_codigo`/`etiquetas`, tablas `ai_proveedores`/`consumo_tokens`/`axon_genesis_vectores`/`axon_genesis_sesiones`, triggers de integridad** |

---

## SECCIÓN 4 — AXON_NEXUS: SISTEMA OPERATIVO RELACIONAL HUMANO / MEMORIA DE PARTNERS

**Versión del Módulo:** v2.0 | **Estatus:** Sembrado en Producción — Tablas pendientes de migración v8

### 4.1 Declaración de Misión — El Manifiesto de AXON_NEXUS

**AXON_NEXUS** es el subsistema cognitivo de memoria dual de The Deep Tech Matrix. No es un chatbot. No es un CRM. Es el **procesador de Átomos de Memoria** que convierte cada conversación, transcripción e interacción del Partner en contexto persistente, clasificado y recuperable.

#### Principio Rector
> "Un sistema que olvida no es inteligente — es una calculadora. AXON_NEXUS transforma la interacción efímera en ventaja operativa permanente."

#### Lo que AXON_NEXUS hace

| Función | Descripción |
|---|---|
| **Atomización** | Analiza texto vía IA y extrae los átomos mínimos de información relevante |
| **Clasificación Dual** | Segmenta cada átomo en canal TACTICAL (negocio) o PERSONAL (humano) |
| **Retención Inteligente** | Clasifica por permanencia: PERMANENT · SEMIPERMANENT · EPISODIC |
| **Archivado Inteligente** | Nunca borra — archiva con `is_active=0` manteniendo trazabilidad total |
| **Episodic Pruning** | Expira memorias EPISODIC a las 72 h de inactividad, moviéndolas al archivo |
| **Perfil Cognitivo** | Actualiza el `partner_cognitive_profile` con cada atomización |

### 4.2 Átomos de Memoria — Taxonomía Completa

#### 4.2.1 Canal TACTICAL_CONTEXT
Información sobre los **prospectos y negocios** que el Partner trabaja.

| Campo | Tipo | Retención |
|---|---|---|
| `empresa` | Nombre del prospecto | PERMANENT |
| `industria` | Sector del cliente | PERMANENT |
| `problema` | Dolor o necesidad detectada | SEMIPERMANENT |
| `status` | Etapa del pipeline | SEMIPERMANENT |
| `lead_score` | Puntuación 0–100 | SEMIPERMANENT (≥50) / EPISODIC (<30) |

#### 4.2.2 Canal PERSONAL_CONTEXT
Información sobre la **vida, contexto y preferencias** del Partner humano.

| Campo | Tipo | Retención |
|---|---|---|
| `preferencias` | Array de gustos/hábitos detectados | PERMANENT |
| `familia` | Personas cercanas mencionadas | PERMANENT |
| `viajes` | Destinos o viajes en curso | EPISODIC |
| `eventos` | Eventos personales/profesionales | SEMIPERMANENT |

#### 4.2.3 Clasificador de Retención
```
PERMANENT    → Se conserva indefinidamente. No tiene expires_at.
               Ejemplos: nombre del cliente, acuerdos cerrados, preferencias del Partner.

SEMIPERMANENT → Se conserva hasta revisión manual o actualización.
               Ejemplos: negociaciones, lead_score >= 50, patrones de comportamiento.

EPISODIC     → TTL = 72 horas desde la última actualización.
               Ejemplos: estado de ánimo, viaje activo, contexto de sesión única.
```

Estos tres niveles de retención (`PERMANENT`, `SEMIPERMANENT`, `EPISODIC`) son los mismos invocados por la Telemetría ASFL (§2.1) al segmentar los canales `TACTICAL_CONTEXT` / `Personal Context` durante cada inferencia cognitiva.

### 4.3 Pipeline de Atomización

```
Conversación / Transcripción
         │
         ▼
NexusMemoryAgent::atomize($text)
         │
         ▼
AiOrchestrator::dispatch(contextKey='axon_nexus', trackUsage=true)
         │
         ▼
Modelo IA → JSON { TACTICAL_CONTEXT, PERSONAL_CONTEXT }
         │
         ├─→ NexusMemoryAgent::persistTactical() → INSERT/UPDATE partners_memory
         ├─→ NexusMemoryAgent::persistPersonal()  → INSERT/UPDATE partners_memory + partner_events
         ├─→ NexusMemoryAgent::updateCognitiveProfile() → UPSERT partner_cognitive_profile
         └─→ NexusMemoryAgent::logAtomization()   → INSERT nexus_atomization_log
         │
         ▼
UsageTracker::record()  ← tokens registrados en consumo_tokens (vía AiOrchestrator)
Logger::sistema()       ← traza NEXUS_ATOMIZATION_COMPLETE en log_sistema
```

### 4.4 Archivado Inteligente y Episodic Pruning

#### 4.4.1 Principio de No Borrado
**Regla absoluta:** ningún dato se elimina físicamente del sistema. El campo `is_active` es el único mecanismo de activación/desactivación.

```
is_active = 1  →  Átomo activo, visible para AURA y el Panel del Partner
is_active = 0  →  Átomo archivado, conservado para auditoría y recuperación
```

#### 4.4.2 Ciclo de Episodic Pruning
```
1. AXON_NEXUS crea memorias EPISODIC con expires_at = NOW() + 72h
2. El cron de purgado (o llamada manual) ejecuta runEpisodicPruning()
3. Memorias expiradas se copian a nexus_pruning_archive con pruning_reason='TTL_EXPIRED'
4. La fila original en partners_memory queda is_active=0, archived_at=NOW()
5. El log de purgado registra NEXUS_EPISODIC_PRUNING en log_sistema
```

### 4.5 Esquemas de las 6 Nuevas Tablas — schema_patch_v8_nexus_memory.sql

> **Autorización:** pendiente de aprobación del Arquitecto para deploy en producción (Mandamiento 9).
> **Archivo de migración:** `backups_y_pruebas/schema_patch_v8_nexus_memory.sql`

#### Tabla 1: `partners_memory`
Almacén central de todos los átomos de memoria. Una fila por `(id_partner, memory_key)`.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED PK AUTO | Identificador interno |
| `id_partner` | INT UNSIGNED NOT NULL | FK → partners.id |
| `memory_key` | VARCHAR(200) NOT NULL | Slug semántico único: `tactical_empresa`, `personal_familia` |
| `memory_type` | ENUM(TACTICAL, PERSONAL) | Canal de memoria |
| `retention` | ENUM(PERMANENT, SEMIPERMANENT, EPISODIC) | Política de retención |
| `content` | TEXT NOT NULL | Contenido del átomo |
| `source_module` | VARCHAR(100) DEFAULT 'axon_nexus' | Módulo creador |
| `confidence` | DECIMAL(4,2) DEFAULT 1.00 | Confianza del modelo |
| `is_active` | TINYINT(1) DEFAULT 1 | Archivado inteligente |
| `archived_at` | DATETIME NULL | Fecha de archivado |
| `expires_at` | DATETIME NULL | TTL para EPISODIC |
| `created_at / updated_at` | DATETIME | Timestamps automáticos |

**UNIQUE KEY:** `(id_partner, memory_key)` — garantiza el upsert determinístico.

> **⚠️ DISCREPANCIA ACTIVA (ver también §2.1 — Deuda Técnica Heredada, Codex §253):** este esquema propuesto (`schema_patch_v8_nexus_memory.sql`) define las columnas `id_partner` y `content` para `partners_memory`. Sin embargo, la tabla real ya sembrada en producción usa `partner_id` y `memory_value` (Codex §175–176). `NexusMemoryAgent.php` y `genesis_saludo.php` referencian el esquema propuesto (`id_partner`, `content`, `retention`, `fase`, `geolocalizacion`, `temperatura_cached`), que **NO EXISTE** en las tablas reales. La refactorización de ambos archivos para usar `partner_id` / `memory_value` reales es **prerrequisito obligatorio** antes de expandir la Atomización de Memoria.

#### Tabla 2: `partner_events`
Eventos personales y profesionales detectados en conversaciones.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED PK AUTO | ID del evento |
| `id_partner` | INT UNSIGNED NOT NULL | FK → partners.id |
| `event_type` | VARCHAR(100) | viaje · reunion · familiar · negocio · cumpleanos · detected |
| `event_date` | DATE NULL | Fecha del evento si fue detectada |
| `description` | TEXT NOT NULL | Descripción del evento |
| `location` | VARCHAR(255) NULL | Lugar del evento |
| `people` | JSON NULL | Array de personas involucradas |
| `is_active` | TINYINT(1) DEFAULT 1 | Archivado inteligente |

#### Tabla 3: `nexus_atomization_log`
Log inmutable (APPEND-ONLY) de cada invocación de `NexusMemoryAgent::atomize()`.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED PK AUTO | ID del log |
| `id_partner / id_usuario` | INT UNSIGNED | Actores de la operación |
| `input_text` | TEXT | Texto analizado (truncado a 2000 chars) |
| `output_json` | JSON NULL | Respuesta del modelo |
| `tokens_entrada / tokens_salida` | INT | Consumo de tokens |
| `proveedor_usado` | VARCHAR(100) | Proveedor que respondió |
| `duracion_ms` | INT | Latencia total |
| `estatus` | ENUM | exitoso · error_modelo · error_json · error_db |

#### Tabla 4: `nexus_pruning_archive`
Archivo permanente de memorias EPISODIC purgadas por el Episodic Pruning.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED PK AUTO | ID del archivo |
| `id_partner` | INT UNSIGNED | FK → partners.id |
| `original_memory_key` | VARCHAR(200) | Clave original de la memoria |
| `memory_type` | ENUM(TACTICAL, PERSONAL) | Canal original |
| `content` | TEXT | Contenido archivado |
| `original_created_at` | DATETIME | Cuándo se creó la memoria |
| `pruned_at` | DATETIME DEFAULT NOW() | Cuándo fue purgada |
| `pruning_reason` | VARCHAR(100) | TTL_EXPIRED · MANUAL_OVERRIDE · CAPACITY_LIMIT |

#### Tabla 5: `partner_cognitive_profile`
Perfil agregado por partner. Una fila por partner (UNIQUE `id_partner`).

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED PK AUTO | ID del perfil |
| `id_partner` | INT UNSIGNED UNIQUE | Un perfil por partner |
| `lead_score_promedio` | DECIMAL(5,2) | Promedio histórico de lead_scores |
| `total_atomizaciones` | INT UNSIGNED | Contador de atomizaciones |
| `industrias_detectadas` | JSON NULL | Array de industrias históricas |
| `ciudades_detectadas` | JSON NULL | Array de ciudades/regiones |
| `patron_horario` | VARCHAR(50) | mañana · tarde · noche · mixto |
| `ultimo_prospecto` | VARCHAR(255) | Nombre del último cliente detectado |
| `ultima_industria` | VARCHAR(100) | Industria más reciente |
| `last_sync_at` | DATETIME | Última sincronización AXON_NEXUS |

#### Tabla 6: `nexus_vector_index`
Índice semántico ligero para agrupación y recuperación de memorias por tópico.

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | INT UNSIGNED PK AUTO | ID del índice |
| `id_partner` | INT UNSIGNED | FK → partners.id |
| `memory_key` | VARCHAR(200) | Clave del átomo indexado |
| `vector_tag` | VARCHAR(100) | Tópico semántico: finanzas · salud · tecnologia · familia |
| `weight` | DECIMAL(4,3) DEFAULT 1.000 | Relevancia del tópico (0.000–1.000) |
| `is_active` | TINYINT(1) DEFAULT 1 | Estado del índice |

**UNIQUE KEY:** `(id_partner, memory_key, vector_tag)`

### 4.6 Integración con el Ecosistema {{PROJECT_NAME}}

| Sistema | Integración |
|---|---|
| `AiOrchestrator` | Dispatch con `contextKey='axon_nexus'`, `trackUsage=true` |
| `UsageTracker` | Tokens registrados automáticamente vía AiOrchestrator |
| `Logger::sistema()` | Traza NEXUS_ATOMIZATION_COMPLETE, NEXUS_EPISODIC_PRUNING, errores |
| `Synaptic Core` | El prompt `axon_nexus` puede ser editado vía Admin UI (sobreescribe el canónico) |
| `GenesisWelcomeBanner` | El AURA Input Panel envía consultas que pueden desencadenar atomización |
| `partners_memory` | Fuente de contexto enriquecido para el saludo de AXON_GENESIS |

### 4.7 Reglas de Hierro — AXON_NEXUS

1. **El modelo NUNCA responde al usuario** — solo emite JSON estructurado.
2. **El JSON parseado DEBE contener** `TACTICAL_CONTEXT` y `PERSONAL_CONTEXT` — si no, se descarta.
3. **El archivado es inteligente** — `is_active=0`, nunca `DELETE`.
4. **Cada atomización registra tokens** — `trackUsage=true` es no negociable.
5. **El Episodic Pruning archiva, no destruye** — `nexus_pruning_archive` es el destino final.
6. **El módulo no tiene endpoints HTTP directos** — es instanciado únicamente por servicios internos.

### 4.8 Comportamiento del Subsistema Cognitivo Humano (AXON_GENESIS v2)

El Onboarding del Partner en la vista `/mtx1` opera como una experiencia relacional interactiva y asíncrona estructurada en tres sub-capas de software:

#### 4.8.1 Flujo Secuencial Anti-Condición de Carrera
Para erradicar los errores del renderizado asíncrono, la inicialización del dashboard se ejecuta bajo una cadena lineal determinista:
`perfil.php` ➔ `clima.php` ➔ `genesis_saludo.php`

#### 4.8.2 Calibración Emocional y Geográfica (Mood Awareness)
El endpoint `api/partner/genesis_saludo.php` extrae el prompt maestro `axon_genesis_prompt` (Versión 2) e inyecta dinámicamente las variables capturadas del Partner de control (`demo@{{PRODUCTION_DOMAIN}}`):
* **Usuario:** {{OPERATOR_NAME}}
* **Ubicación Base:** {{GEOLOCATION_PARAMETER}}
* **Clima en Vivo:** 32°C · Calor Regional (Broken Clouds)
* **Detección de Viajes (OSINT Tracking):** Si el payload de geolocalización de la IP difiere de su base de operaciones en su {{GEOLOCATION_PARAMETER}} de base, la IA activa el `travelMode=true`, inyectando un badge ámbar animado y variando el comportamiento del prompt para entablar una conversación humana informal sobre sus vacaciones o viaje de negocios antes de forzar la apertura de proyectos.

### 4.9 Geo-Fencing Cognitivo — Especificación Técnica

#### 4.9.1 Flujo Determinístico de Detección de Ubicación

```
Partner abre /mtx1
     │
     ▼
perfil.php  →  ciudad_base = profile.ciudad  (Ej: "{{GEOLOCATION_PARAMETER}}")
     │
     ├── geolocation API (navigator.geolocation.getCurrentPosition)
     │        │
     │        ▼
     │   api/partner/detectar_ubicacion.php
     │        │  Nominatim OSM reverse geocoding (lat, lng)
     │        │  GET https://nominatim.openstreetmap.org/reverse?lat=&lon=&format=json
     │        │  → { ciudad, estado, pais }
     │        │  Logger::sistema(GEO_DETECTION_OK) ← auditoría en log_sistema
     │        ▼
     │   VALIDACIÓN BINARIA:
     │   ┌─ ciudad_actual ≈ ciudad_base? ──► CASO A (En Sede)
     │   └─ ciudad_actual ≠ ciudad_base? ──► CASO B (De Viaje)
     │
     └── clima.php (paralelo, no bloquea)
```

#### 4.9.2 Caso A — En Sede Base

**Condición:** `ciudad_actual.includes(ciudad_base) || ciudad_base.includes(ciudad_actual)` → `true`

**Badge UI (fusionado):**
```
☀ 32°C · Soleado · {{GEOLOCATION_PARAMETER}}
```
Un único badge integra: icono celestial + temperatura + estado clima + ciudad + estado abreviado.

**genesis_saludo:** `is_traveling=false`, `ciudad_actual=""` → saludo normal.

#### 4.9.3 Caso B — Fuera de Base (Viaje)

**Condición:** comparación anterior → `false` → `is_traveling=1`

**Badges UI:**
```
Badge 1: ☀ 32°C · Soleado                          (clima de la sesión)
Badge 2: 💼 Base: {{GEOLOCATION_PARAMETER}} → Actual: CDMX       (amber, viaje activo)
```

**Trigger automático:** Re-dispara `genesis_saludo.php` con:
```json
{
  "is_traveling": true,
  "ciudad_actual": "CDMX",
  "clima_status": "clear",
  "temperatura": 32
}
```

**Prompt AXON_GENESIS en modo viaje:**
> "{{OPERATOR_NAME}}, veo que estás fuera de tu base en {{GEOLOCATION_PARAMETER}}. ¿Es un viaje de negocios en CDMX o estás descansando? Mantengo tu perímetro protegido..."

#### 4.9.4 Diccionario de Estados del Badge
| Condición | Badge clima | Badge ubicación | is_traveling |
|---|---|---|---|
| Sin geolocation | `☀ Soleado` | (oculto) | false |
| En sede base | `☀ 32°C · Soleado · {{GEOLOCATION_PARAMETER}}` | (fusionado en clima) | false |
| De viaje | `☀ 32°C · Soleado` | `💼 Base: {{GEOLOCATION_PARAMETER}} → Actual: CDMX` | true |

#### 4.9.5 Abreviaturas de Estado Canónicas
```
Baja California Sur → BCS    |  Nuevo León → NL
Baja California     → BC     |  Jalisco → JAL
Ciudad de México    → CDMX   |  Sonora → SON
Estado de México    → EdoMex |  Quintana Roo → QRoo
```

### 4.10 Variables Climáticas — Especificación del Badge

#### 4.10.1 Fuente de Datos
**Primaria:** OpenWeatherMap API (`WEATHER_API_KEY` en `CORE/.env`)
```
GET https://api.openweathermap.org/data/2.5/weather?q={ciudad},{pais}&appid={key}&units=metric&lang=es
```
**Fallback de desarrollo:** datos de demo {{GEOLOCATION_PARAMETER}} — `32°C · Broken Clouds`

#### 4.10.2 Mapeo de Estado → WeatherState
| OWM ID range | Hora | WeatherState | ClimaCopy |
|---|---|---|---|
| 200–599 | cualquier | `rain` | "Lluvia" |
| 801+ | día | `cloudy` | "Nublado" |
| 801+ | noche | `night_cloudy` | "Noche nublada" |
| 800 | día | `clear` | "Soleado" |
| 800 | noche | `night_clear` | "Noche despejada" |
| default | día | `cloudy` | "Nublado" |
| default | noche | `night_cloudy` | "Noche nublada" |

**Criterio noche:** `hora >= 19 || hora < 6`

#### 4.10.3 Icono Celestial por WeatherState
| WeatherState | Componente Lucide |
|---|---|
| `clear` | `Sun` |
| `cloudy` | `CloudSun` |
| `rain` | `CloudRain` |
| `night_clear` | `Moon` |
| `night_cloudy` | `Moon` |

#### 4.10.4 Formato del Badge Premium
```
{Icono}  {Math.round(temperatura)}°C · {getClimaCopy(estado_clima)} · {ciudad}, {estado_abrev}
Ejemplo: ☀  32°C · Soleado · {{GEOLOCATION_PARAMETER}}
```

#### 4.10.5 Cache y Tracking
- `clima.php` → `Cache-Control: max-age=900` (15 min)
- Logger::sistema(`CLIMA_FETCH_OK`) → auditoría en `log_sistema`
- `detectar_ubicacion.php` → `Cache-Control: max-age=300` (5 min)
- Logger::sistema(`GEO_DETECTION_OK` / `GEO_NOMINATIM_ERROR`)

### 4.11 Integración de Audio Bidireccional (AURA VOICE)

El sistema dota a la Consola TDTM de capacidades auditivas y de habla, puenteando las operaciones multimedia del navegador directamente hacia las neuronas de AURA.

```
[UI /mtx1] ──(Graba WebM)──> [api/audio/transcribe.php] ──> [AURA Whisper] ──> [CaptureBar]
[UI /mtx1] <──(Stream MP3)── [api/audio/tts.php] <──────── [AURA TTS] <───── [Saludo IA]
```

#### 4.11.1 Voz de Salida (Text-to-Speech / TTS)
* **Ruta Oficial:** `api/audio/tts.php`
* **Funcionamiento:** Captura el texto dinámico emitido por el prompt de AXON_GENESIS, procesa el ruteo hacia el modelo `tts-1` (Voz: `alloy`) utilizando la llave del proveedor activo descifrada desde la BD, y devuelve un stream binario limpio bajo la cabecera `Content-Type: audio/mpeg`. El frontend Next.js inicializa el buffer nativo de HTML5 `Audio().play()` de forma transparente.

#### 4.11.2 Entrada por Micrófono (Speech-to-Text / Whisper)
* **Ruta Oficial:** `api/audio/transcribe.php`
* **Funcionamiento:** El componente multimedia captura el flujo del micrófono del Partner en formato binario `audio/webm` durante el estado activo del botón pulsante. Envía el FormData mediante un POST cross-origin al backend. `AudioOrchestrator` selecciona el motor de Groq (Whisper Large V3) por eficiencia de latencia. Transcribe el archivo y devuelve un JSON con el token de texto limpio.
* **CaptureBar Integration:** El texto resultante se inyecta de forma determinista en una barra de edición verde esmeralda animada con Tailwind CSS (`CaptureBar`). El Partner puede pulir el texto capturado por voz antes de darle clic al disparador de ejecución final.

### 4.12 Diccionario de Endpoints — AURA VOICE

Todos los endpoints de audio siguen el contrato de la Arquitectura Proxy/Puente (Mandamiento 11 CORE). Ninguna API Key toca el frontend.

#### `POST /api/audio/tts.php` — Text-to-Speech
**Auth:** cookie `axon_token` requerida.
**Payload:**
```json
{ "text": "string — máximo 4096 caracteres" }
```
**200:** stream de audio MPEG (`Content-Type: audio/mpeg`)
**Error:** `{ "status": "error", "message": "string" }`

**Flujo interno:**
1. Valida token contra `usuarios`
2. Llama a `AudioOrchestrator::tts($text)`
3. AudioOrchestrator descifra la OpenAI key de `ai_proveedores`
4. POST a OpenAI `/audio/speech` → modelo `tts-1`, voz `onyx`
5. Stream binario al frontend → reproducción por Web Audio API

#### `POST /api/audio/transcribe.php` — Speech-to-Text (Whisper)
**Auth:** cookie `axon_token` requerida.
**Payload:** `multipart/form-data` con campo `audio` (blob WebM/Opus).
**200:**
```json
{ "status": "success", "transcript": "texto transcrito" }
```
**Flujo interno:**
1. Valida token
2. Guarda el blob como archivo temporal
3. POST a OpenAI `/audio/transcriptions` → modelo `whisper-1`, idioma `es`
4. Retorna JSON con `transcript`
5. Frontend inyecta el texto en `CaptureBar` o `bannerQuery`

---

## SECCIÓN 5 — AI COPILOT STRATEGY

### 5.1 Objetivo Operativo

Llevar al ecosistema **TDTM (The Deep Tech Matrix)** a la **"Fricción Cero Operativa"**: las etapas de onboarding de Partners, captura de prospectos, análisis de leads y generación de propuestas se automatizan al máximo. El equipo y los Partners enfocan su energía exclusivamente en el cierre comercial y la entrega de valor.

### 5.2 Arquitectura del Plan de IA (v1)

#### 5.2.1 Red de Captación — Agente AXON_GENESIS
- IA de onboarding conectada al panel del Partner inmediatamente después de la aprobación.
- Comunicación adaptativa por perfil: calibración emocional **70% humano cálido · 20% mentor ejecutivo · 10% humor ligero**.
- Identificación automática de perfil: nivel en el Árbol de Evolución (`Roble | Cedro | Baobab | Secuoya`).
- Enrutamiento de Vectores Tácticos: `VECTOR_CERO | VECTOR_REINGENIERÍA | VECTOR_EXPANSIÓN`.
- Inyección de contexto geolocalizado (clima, temporada turística/regional) para personalización hiper-local.

#### 5.2.2 Backoffice TDTM — Panel de Operaciones
- **Pipeline Kanban de Prospectos:** El Partner registra dominios y empresas; el motor {{PROJECT_NAME}} Scanner genera automáticamente el `cyber_score`.
- **Cotización IA:** El CFO Pricing Engine propone precios basados en el `cyber_score`, nivel del partner y descuento tipo (fundacional / invisible / urgencia).
- **Propuesta PDF Automatizada:** El módulo `pdf_dictamen_maestro` genera el dictamen de blindaje ORO.
- **Copiloto de Análisis:** Consultas en lenguaje natural sobre la cartera de prospectos.

#### 5.2.3 Copiloto Avanzado — Agentes Fase 2
- **Misiones Urbanas:** Asignación de objetivos geolocalizados al Partner según zona y contexto de mercado.
- **Contención de Rechazo:** Reencuadre empático automático cuando una propuesta no avanza.
- **Monitor de Consumo:** Tracking de tokens y costos por agente para control de presupuesto IA.

### 5.3 Vacíos y Edge Cases Críticos

#### EC-01 — Consentimiento y Privacidad de Datos del Partner
> **Problema:** El sistema procesa datos del partner (nombre, ubicación, empresa, geolocalización) antes de que el partner haya confirmado explícitamente los términos del servicio.
> **Solución:** Implementar un **Consent Gate** durante el flujo de aprobación del partner. Ningún dato se procesa por IA hasta confirmación activa.

#### EC-02 — Deduplicación de Prospectos
> **Problema:** Un mismo prospecto puede ser registrado bajo distintos nombres o dominios por diferentes Partners.
> **Solución:** Motor de normalización de `dominio` + fuzzy-match de `empresa` antes de insertar en `prospectos`. Sin esto, el `cyber_score` se duplica y el pipeline kanban se contamina.

#### EC-03 — Degradación del Contexto en Conversaciones Largas
> **Problema:** AXON_GENESIS puede perder el hilo del vector seleccionado en sesiones de onboarding largas.
> **Solución:** Persistencia de la `axon_genesis_sesion` en BD con cada cambio de fase. El prompt incluye el historial resumido de la sesión.

#### EC-04 — Ausencia de Human Handoff Graceful
> **Problema:** Cuando el agente no clasifica la intención del partner en 3 turnos, entra en bucle.
> **Solución:** Criterio explícito de escalación: tras 3 intentos sin clasificación exitosa, el agente indica al Partner que un miembro del equipo {{HOLDING_NAME}} le contactará. El historial completo se envía al Admin Dashboard.

#### EC-05 — Cotización sin Baseline de Mercado
> **Problema:** El CFO Pricing Engine cotiza en función del `cyber_score` pero sin referencia al segmento o industria del prospecto.
> **Solución:** Añadir `industria_objetivo` en la tabla `cupones` y extender el motor de cotización con percentiles por sector.

### 5.4 Ideas Innovadoras — Nivel "Magia Pura"

#### IDEA-01 — "El Espejo Predictivo" (Pre-Session Intelligence Brief)
**Descripción:** Antes de que el Partner ingrese al panel, el sistema genera automáticamente un briefing privado con:
- Prospectos con mayor probabilidad de cierre (score > 70) que lleven más de 7 días sin avanzar.
- Costo oculto acumulado de su cartera sin propuesta enviada.
- Recomendación de acción específica basada en el patrón histórico del partner.

**Impacto:** El Partner siente que la plataforma lo conoce antes de que él tenga que pensar en qué hacer.

#### IDEA-02 — "Radar de Riesgo de Abandono del Partner"
**Descripción:** Monitor predictivo sobre patrones de uso del panel:
- Días desde última sesión activa.
- Reducción en número de prospectos nuevos registrados.
- Porcentaje de propuestas en etapa `draft` sin enviar.

El sistema etiqueta al partner en semáforo. En rojo, dispara una misión de reenganche personalizada del agente AXON_GENESIS referenciando el último logro específico del partner — nunca un mensaje genérico.

#### IDEA-03 — "Gemelo Digital del Prospecto"
**Descripción:** Cada prospecto tiene un perfil dinámico que predice:
- Probabilidad de cierre ajustada en tiempo real según las interacciones registradas.
- Tipo de argumento que más resuena (precio / seguridad / compliance / competencia).
- Momento óptimo para re-contacto basado en ciclos de decisión detectados en el sector del prospecto.

### 5.5 Protocolo Anti-Alucinación para Agentes TDTM

#### Arquitectura en 5 Capas — Obligatoria para todos los agentes IA de TDTM

**Capa 1 — Constitución del System Prompt (Synaptic Core)**
Todas las instrucciones de dominio viven exclusivamente en la tabla `synaptic_prompts`. Ningún prompt se hardcodea en código PHP o JavaScript.
- **Prohibido:** Emitir cotizaciones, precios o compromisos sin recuperarlos de la BD.
- **Prohibido:** Recomendar acciones financieras de alto impacto sin intervención del Partner.
- **Obligatorio:** Ante señales de escalación → transferencia inmediata al Admin Dashboard.

**Capa 2 — RAG sobre Datos Validados del Sistema**
Los agentes de cotización y diagnóstico **no generan** respuestas desde conocimiento paramétrico. Recuperan:
- `cyber_score` en tiempo real de `prospectos`.
- Precios desde la lógica del CFO Pricing Engine.
- Historial de interacciones de `interacciones_ia`.

**Capa 3 — Confidence Gating**
Scoring de confianza por respuesta. Umbral: **0.75**.
Respuestas sobre dominio financiero o jurídico por debajo del umbral → mensaje de escalación al Admin.

**Capa 4 — Disclaimers Contextuales**
No usar disclaimers boilerplate. Cuando el agente toque temas financieros o legales, insertar:
> *"Esta propuesta es orientativa. Para validar los términos específicos de tu contrato, el equipo {{HOLDING_NAME}} revisará contigo los detalles."*

**Capa 5 — Audit Log (consumo_tokens + log_actividad)**
Toda llamada a IA queda registrada en `consumo_tokens` con `context_key`, `modelo`, `tokens_total` y `costo_usd`. Permite supervisión, control de presupuesto y mejora iterativa de prompts.

**Capa 6 — Principio de Inferencia Probabilística**
Toda comunicación generada automáticamente por el motor cognitivo ante consultas de carácter sensible del dominio (financiero, legal, de seguridad) se redacta mandatoriamente utilizando lenguaje de probabilidad, sugerencia calificada y humildad epistemológica — nunca juicios definitivos o deterministas. Complementa el Desacoplamiento Absoluto de Prompts (Capa 1): ningún prompt maestro hardcodea afirmaciones categóricas sobre el dominio del negocio; siempre se redactan en términos de estimación, rango o recomendación sujeta a revisión humana.

### 5.6 Prioridad de Implementación Cognitiva

| Prioridad | Componente | Fase | Motivo |
|---|---|---|---|
| 🔴 P0 | AXON_GENESIS — Prompt Base | Génesis | Primer punto de contacto del partner |
| 🔴 P0 | Vectores Tácticos (×3) | Génesis | Enrutamiento de la Fundación Cognitiva |
| 🟡 P1 | CFO Pricing Engine | Beta | Sin esto no hay cotización automática |
| 🟡 P1 | Protocolo de Contención | Beta | Retención de deals en riesgo |
| 🟡 P1 | Dictamen PDF Maestro | Beta | Propuesta ejecutiva de valor |
| 🟢 P2 | Misiones Urbanas | Escala | Diferenciación geolocalizada |
| 🟢 P2 | Radar de Abandono del Partner | Escala | Retención de Partners |
| 🟢 P2 | Gemelo Digital del Prospecto | Escala | Predicción de cierre |

> **Regla de oro:** Las ideas de "magia" se implementan **después** de que los cimientos sean sólidos. Un agente que falla en el onboarding destruye la confianza del Partner desde el primer minuto.

---

## SECCIÓN 6 — MATRIZ DE RESOLUCIÓN COGNITIVA (TDTM v3.5)

**Versión origen:** 3.5 — Edición Maestra Definitiva | **Fecha origen:** 2026-06-11
**División:** {{PROJECT_NAME}} / CORE / PARTNERS ({{HOLDING_NAME}}) | **Entorno:** Motor de Inteligencia e Inferencia AURA / Ecosistema TDTM (The Deep Tech Matrix)
**Fuente:** `MATRIZ DE RESOLUCIÓN COGNITIVA TDTM - PROTOCOLO DE INTERVENCIÓN IA (1).txt` (11-Jun-2026), reestructurado a Markdown.

> **⚠️ NOTA DE JERARQUÍA:** Para datos financieros canónicos (comisiones, tabuladores, retenciones modulares), la **única fuente de verdad** es [`05_MATRIZ_FINANCIERA_Y_VENTAS.md`](05_MATRIZ_FINANCIERA_Y_VENTAS.md). Esta sección describe el **protocolo cognitivo y de orquestación IA** que consume esos datos — no los redefine.

### 6.1 Directriz Principal Inviolable

El sistema opera bajo la estricta filosofía de **Fricción Cero Operativa** y **Costo Cero Cognitivo**. No se comercializa software modular aislado; se implanta una **Capa de Inteligencia Operativa** y una **Infraestructura de Confianza Operativa** autónoma que erradica vulnerabilidades y automatiza flujos de forma transparente.

Todo el tono cognitivo del ecosistema se rige bajo la pauta de **Executive Calm** (cero lenguaje agresivo, bélico o transaccional minorista). Se prohíbe el uso de términos comerciales tradicionales en favor de conceptos de alta ingeniería y certidumbre operativa. (Ver Registro Semántico en [`02_CODEX_Y_SCHEMA_MAESTRO.md`](02_CODEX_Y_SCHEMA_MAESTRO.md)).

### 6.2 Marco Operativo y Protocolo Arquitectónico

#### 6.2.1 Política Proxy/Puente, Encriptación y AURA Engine

> **Nota de consolidación:** esta política ya se documenta de forma completa en §1.1–1.2 (Topología de Alimentación y Seguridad Criptográfica de AURA AiOrchestrator). Se reproduce aquí el Contrato Base de Comunicación y la Sanitización FQDN por continuidad narrativa del protocolo cognitivo de la Matriz, sin redefinir el detalle ya cubierto en la Sección 1.

La máquina **AURA (AiOrchestrator)** constituye el Núcleo Único de Inteligencia. Se implementa un aislamiento estricto de credenciales bajo el estándar **AES-256-CBC**: las API Keys corporativas jamás tocan el frontend ni los repositorios públicos de control de versiones (consolidado, ver también §1.1–1.2).

**Contrato Base de Comunicación (JSON UTF-8)** — todo flujo entre el frontend TDTM y el backend de inferencia debe cumplirlo rigurosamente:

```json
{
  "status": "success|error",
  "message": "string",
  "data": {}
}
```

**Sanitización FQDN Estricta:** Antes de interactuar con el escáner o cualquier capa de persistencia, todo dominio proveído por el Partner se normaliza de forma obligatoria eliminando protocolos (`http://`, `https://`), paths, puertos y queries mediante expresiones regulares estructuradas.

#### 6.2.2 Regla de Inmutabilidad Financiera (Contrato DB Append-Only)

> **Nota de consolidación:** ver versión completa en §1.7 (consolidado, ver también §1.7).

El motor de IA estructura y ejecuta sus cálculos asumiendo que las tablas transaccionales y de control de MariaDB (`cotizaciones`, `transacciones`, `log_auditoria_financiera`) son **estrictamente append-only**, bajo los triggers `trg_cotizaciones_block_update`, `trg_cotizaciones_block_delete` y `trg_cotizaciones_price_floor_check` (SIGNAL SQLSTATE '45000'). Toda corrección genera un nuevo `INSERT` con el mismo `quote_id`, incrementando `version` y recalculando `financial_hash` (SHA-256).

#### 6.2.3 Telemetría en Caliente (ASFL) y Memoria Episódica (AXON_NEXUS)

> **Nota de consolidación:** ver versión completa en §2.1 (Telemetría Cognitiva ASFL) y §4 (AXON_NEXUS). Los campos `network_latency_ms`, `db_query_status`, `synaptic_input_payload`, `tokens_in_flight` y los tres niveles de retención `PERMANENT` / `SEMIPERMANENT` / `EPISODIC` segmentados en `TACTICAL_CONTEXT` / `Personal Context` se documentan íntegramente ahí (consolidado, ver también §2.1 y §4).

#### 6.2.4 Control de Accesos y Auth Gates Integrados

> **Nota de consolidación:** ver versión completa en §1.6 (consolidado, ver también §1.6).

El motor de inferencia valida la identidad del operador cruzando la petición con las cookies `HttpOnly` de Next.js (`axon_token` y `axon_user`). Si el `estatus` del usuario en la tabla `usuarios` está marcado como `pendiente`, `inactivo` o `suspendido`, AURA aborta el hilo de ejecución arrojando un error `401 Unauthorized` de manera inmediata (Mandamiento 14: "CORS ≠ Auth").

### 6.3 Enrutamiento Cognitivo Multinivel (AURA AiOrchestrator)

> **Nota de consolidación:** la tabla completa de NIVEL 1/2/3, el flujo de orquestación interna y la nota de implementación sobre `ai_proveedores` ya se documentan íntegramente en §1.3–1.5 (consolidado, ver también §1.3–1.5).

Para erradicar la dependencia financiera absoluta de APIs de cobro por token y maximizar el margen operativo del ecosistema, AURA actúa como un **orquestador inteligente de enrutamiento cognitivo multinivel**. El sistema evalúa la complejidad conceptual de la tarea y decide dinámicamente el motor óptimo entre NIVEL 1 (Ollama Local, $0.00 MXN), NIVEL 2 (Gemini CLI / Scripts Python Free Tier, $0.00 MXN) y NIVEL 3 (OpenAI/Claude, fallback transaccional de emergencia).

### 6.4 Intervención Quirúrgica por Perfiles de Cliente

#### 6.4.1 Perfil A: GÉNESIS DIGITAL (Vector Comercial: "Crear desde Cero")

- **Código de Activación Backend:** `axon_genesis_vector_cero`
- **Condición del Prospecto:** Negocios tradicionales en etapa fundacional. Carecen de sitio web, redes sociales, eslogan o activos de marca.
- **Misión Operativa de la IA:** Ejecutar el protocolo de **Fundación Cognitiva Automatizada**. La IA recibe el Brief Fundacional del Partner mediante el formulario estructurado y procesa localmente en **NIVEL 1** (Ollama con modelos optimizados `llama3:8b` o `mistral:7b`) para generar de forma secuencial, determinística y libre de costo de tokens:

  1. 3 propuestas estratégicas de **Naming** de alto impacto comercial.
  2. 3 propuestas conceptuales de **Eslogan** adaptadas a la marca.
  3. 3 directrices visuales y conceptos abstractos para el diseño de **Logotipo**.
  4. Redacción corporativa final de la **Matriz Filosófica** (Misión, Visión y Valores).
  5. Estructuración de un análisis **FODA** de mercado inicial y **Roadmap de Marketing Orgánico** de tracción inmediata.

**Contrato JSON del Prompt Maestro de Sistema (NIVEL 1):**

```json
{
  "naming_proposals": [],
  "slogan_proposals": [],
  "logo_concepts": [],
  "mission": "string",
  "vision": "string",
  "values": [],
  "swot": {
    "strengths": [],
    "weaknesses": [],
    "opportunities": [],
    "threats": []
  }
}
```

Si el motor de Ollama local sufre un desbordamiento de contexto o genera un JSON inválido, AURA ejecuta un **escalamiento automático hacia NIVEL 2** (`gemini --prompt genesis_prompt.txt --json`) para garantizar la resiliencia del flujo sin detener la experiencia de usuario.

El entregable final se consolida bajo la marca registrada **AXON BUSINESS FOUNDATION™** (Diagnóstico Estratégico de Madurez Digital), cotizando únicamente los activos generados desde cero como una Propuesta de Fricción Cero.

#### 6.4.2 Perfil B: EVOLUCIÓN DIGITAL Y BLINDAJE (Vector Comercial: "Rediseñar y Optimizar")

- **Código de Activación Backend:** `axon_genesis_vector_reingenieria`
- **Condición del Prospecto:** Negocios activos que cuentan con infraestructura digital básica (Nombre, Logo, Dominio, Redes) pero operan con brechas críticas de seguridad perimetral y carecen de automatizaciones inteligentes.
- **Misión Operativa de la IA:** Actuar como **Analizador de Riesgo B2B forense**. El backend invoca de forma automatizada las herramientas nativas del servidor Linux para recolectar datos OSINT en tiempo real y alimentar de forma gratuita las fases de **The Scanner**.

**Script Unificado de Extracción OSINT** (`/CORE/src/scanner_engine.sh` — referencia conceptual del protocolo; la implementación PHP activa equivalente es `SECURITY/GEM/core/axon_external_scanner.php`):

```bash
#!/bin/bash
# Script Unificado de Extracción OSINT - Ecosistema TDTM
DOMAIN=$1

whois $DOMAIN > whois.txt                                       # Fase Recon: Registro
dig $DOMAIN ANY > dns.txt                                        # Fase Recon: DNS / IP
curl -s -I https://$DOMAIN > headers.txt                         # Fase Military: HTTP Security Headers
openssl s_client -connect $DOMAIN:443 </dev/null > ssl.txt 2>&1  # Fase Military SSL
nmap -Pn -p 21,22,80,443,3306 $DOMAIN > ports.txt                # Fase Port Scan (solo autorizados)
wpscan --url https://$DOMAIN --disable-tls-check --format json > wp_audit.json # Fase WP Audit
```

**Fórmula determinista del Cyber Score (base 100):**

```
Cyber Score = 100 - ( SSL_vencido(10)
                     + Puertos_abiertos(15)
                     + DNS_mal_config(20)
                     + WP_vulnerable(25)
                     + Headers_faltantes(10) )
```

##### Restricción Crítica de Base de Datos MariaDB (VARCHAR 100)

> **⚠️ DISCREPANCIA ACTIVA — REQUIERE RESOLUCIÓN DEL ARQUITECTO:**
> El documento fuente describe una tabla `leads` con columna `project_type VARCHAR(100)` como destino de la "compactación semántica". **Esta tabla no existe** en el Schema Maestro TDTM v2.0 (29 tablas, Codex §245) ni en el dump `{{DB_NAME}}.sql`. La tabla canónica equivalente para diagnósticos de prospectos es `prospectos` (Codex Tabla 7), que **no tiene** una columna `project_type`.
>
> Por Regla Cero del Codex, la IA **no debe** crear ni escribir sobre `leads.project_type` sin autorización explícita. Se documenta el algoritmo de compactación a continuación como **especificación funcional**, pendiente de mapeo a una columna real (candidatas: nueva columna `prospectos.diagnostico_compacto VARCHAR(100)`, o reutilizar `prospectos.notas_privadas` con prefijo estructurado).

**Algoritmo de compactación semántica (especificación funcional, pendiente de mapeo de columna):**

| Estado de Vulnerabilidad Detectado | Cadena Compacta Propuesta |
| :--- | :--- |
| Cyber Score crítico con fallas en SSL, DNS y WordPress | `CYBER:45\|RISK:CRITICO\|SSL\|DNS\|PORTS\|WP` |
| Score moderado con debilidades en cabeceras y DNS | `CYBER:72\|RISK:MODERADO\|DNS\|HEADERS` |
| Infraestructura óptima con riesgos de puertos mínimos | `CYBER:92\|RISK:MINIMO\|PORTS` |

Los desgloses ejecutivos extensos generados por la IA se inyectan en el **Dictamen de Blindaje ORO**, aplicando el tabulador financiero oficial (`05_MATRIZ_FINANCIERA_Y_VENTAS.md` §2) mediante la psicología de contrastes de **Anchor Pricing** (Codex §144).

#### 6.4.3 Perfil C: SISTEMAS A LA MEDIDA (Vector Comercial: "Inyectar Inteligencia")

- **Código de Activación Backend:** `axon_genesis_vector_expansion`
- **Condición del Prospecto:** Corporativos o plataformas complejas que exigen integraciones de software a la medida, automatización SaaS autónoma o el despliegue del motor centralizado `dcd_engine`.
- **Misión Operativa de la IA:** Actuar como **Master Architect** y orquestador del **Friction Analyzer Component**. AURA recibe la descripción de la necesidad del cliente y mapea flujos lógicos bajo una pregunta existencial rígida:

  > ¿Esta funcionalidad elimina fricción operativa o la añade?

  Si el diseño añade procesos manuales, pasos de validación redundantes o interfaces complejas, el agente **rechaza la arquitectura de software de forma autónoma**.

El sistema devuelve un JSON de diagnóstico con:
- El **Score de Fricción** estimado.
- El stack de desarrollo recomendado (respetando la infraestructura core PHP/MariaDB para desarrollo modular asíncrono).
- Una estimación de iguala mensual indexada directamente al volumen transaccional proyectado y al consumo de `total_tokens_ia` mediante trabajadores locales.

### 6.5 Motor Financiero, Matriz de Precios y Comisiones

> **Recordatorio de jerarquía:** Todas las cifras de esta sección están sujetas a [`05_MATRIZ_FINANCIERA_Y_VENTAS.md`](05_MATRIZ_FINANCIERA_Y_VENTAS.md) como única fuente de verdad. Se conservan aquí por continuidad narrativa del protocolo cognitivo.

#### 6.5.1 Pricing Dinámico Regionalizado (Multiplicador MMR)

El motor financiero de la IA no calcula tarifas planas. Detecta de forma nativa la geolocalización y el estado de la República Mexicana registrado en el perfil del Partner (`partners.geolocalizacion`). Con base en esto, aplica el **Multiplicador de Mercado Regional (MMR)** para ajustar el valor percibido local sin perforar jamás el costo mínimo de rentabilidad corporativa:

| Zona | Cobertura | Multiplicador |
| :--- | :--- | :---: |
| **Zona AAA** | CDMX, Monterrey, Guadalajara | **1.20** (Aumenta el valor de lista de mercado) |
| **Zona AA** | Querétaro, Puebla, Mérida, La Paz | **1.00** (Ecosistema base estandarizado) |
| **Zona A** | Resto del país / economías en desarrollo | **0.90** (Piso comercial adaptado y seguro) |

**Fórmula de conversión visual en interfaz (Algoritmo de Anclaje de Impacto):**

```
Valor Lista = Tarifa Base × Multiplicador Regional
```

El sistema renderiza el costo real tachado frente a la promoción de arranque con el **Descuento Fundacional del 50%** aplicado (Codex §144), optimizando la psicología de urgencia.

> **⚠️ DISCREPANCIA ACTIVA:** La columna `partners.geolocalizacion` referenciada aquí **no existe** en el Schema Maestro (Codex Tabla 3 `partners` define `pais`, `ciudad`, `codigo_postal`). El MMR debe derivarse de `partners.ciudad` / `partners.codigo_postal`, no de un campo `geolocalizacion` inexistente.

#### 6.5.2 Tabulador Canónico Oficial de Inversión

| Tipo de Solución / Diagnóstico | Inversión Setup Retail Base | Inversión Setup (`[PCT_DESCUENTO_FUNDACIONAL]` OFF Fundacional) | Iguala Mensual (MRR) |
| :--- | :---: | :---: | :---: |
| Plan Básico de Protección (Cyber Score 70–89) | `[PRECIO_SETUP_PLAN_BASICO_RETAIL]` `{{CURRENCY}}` | `[PRECIO_SETUP_PLAN_BASICO]` `{{CURRENCY}}` | Opcional: `[PRECIO_MRR_PLAN_BASICO]` `{{CURRENCY}}` / mes |
| Plan Seguridad ORO (Cyber Score 0–69) | `[PRECIO_SETUP_PLAN_ORO_RETAIL]` `{{CURRENCY}}` | `[PRECIO_SETUP_PLAN_ORO_MIN]` – `[PRECIO_SETUP_PLAN_ORO_MAX]` `{{CURRENCY}}` | Sujeto a Sentinel: `[PRECIO_MRR_PLAN_ORO]` `{{CURRENCY}}` / mes |
| Póliza Sentinel (Defensa Activa Aislada) | N/A | N/A | `[PRECIO_MRR_POLIZA_SENTINEL]` `{{CURRENCY}}` / mes |

**Cláusula Legal Automática:** Al estructurar la cotización del MRR para los planes de protección, la IA estampará de forma mandatoria una **vigencia forzosa de la Póliza Sentinel a 1 año** en los términos del contrato, garantizando la retención prolongada de ingresos recurrentes (MRR). (Cláusula canónica completa en Codex §193).

#### 6.5.3 Especificación Estructural de Entregables: Póliza Sentinel

La activación de la Póliza Sentinel (`[PRECIO_MRR_POLIZA_SENTINEL]` `{{CURRENCY}}`/mes recurrente) no constituye un servicio de hospedaje web pasivo, sino un protocolo blindado de **Continuidad Operativa y Evolución Física** del sistema operado a **Costo Cero Cognitivo**. Los entregables inmutables incluidos en la iguala mensual son:

1. **Telemetría y Monitoreo Perimetral Activo**
   - Ejecución automatizada de cron jobs asíncronos (`scanner_engine.sh` / `axon_external_scanner.php`) para la auditoría continua de las 5 Armas Militares (Cabeceras HTTP, Capa SSL/TLS, WP-Hunter y Puertos).
   - Despacho de un Dictamen Semanal de Salud Digital con actualización en tiempo real del `cyber_score`.
   - Mitigación activa perimetral ante anomalías de red o ataques automatizados de denegación de servicio.

2. **Evolución de Infraestructura Modular (Suite de IA)** — ver [`05_MATRIZ_FINANCIERA_Y_VENTAS.md`](05_MATRIZ_FINANCIERA_Y_VENTAS.md) §2.2 para el detalle canónico y las variables `retencion_modulo_secuencial_pct` (`[PCT_RETENCION_MODULO_SECUENCIAL]`) / `retencion_modulo_anticipado_pct` (`[PCT_RETENCION_MODULO_ANTICIPADO]`).
   - **Incentivo de Retención Modular (`[PCT_RETENCION_MODULO_SECUENCIAL]` OFF Fundacional — activación secuencial):** por cada mes de permanencia activa en la Póliza Sentinel, el cliente adquiere el derecho de inyectar un nuevo operador autónomo de IA (Chatbots de atención, motor de agendamiento *Pegaso Booking*, o Pasarelas de Pago) con `[PCT_RETENCION_MODULO_SECUENCIAL]` de descuento sobre el costo setup base retail.
   - **Despliegue Anticipado (`[PCT_RETENCION_MODULO_ANTICIPADO]` OFF Fundacional):** si el cliente exige la implementación de un nuevo módulo avanzado antes de cumplir el ciclo mensual estándar, se aplica un descuento inmediato del `[PCT_RETENCION_MODULO_ANTICIPADO]` sobre su valor real.

3. **Indexación Orgánica Autónoma (SEO Predictivo) — AURA Nivel 2**
   - Procesamiento mensual mediante **AURA Nivel 2** (Gemini CLI + Search Grounding) para analizar tendencias de búsqueda y movimientos de la competencia regionalizada.
   - Reescritura y actualización autónoma de meta-tags, descripciones y microdatos directamente en el servidor local vía Ollama (NIVEL 1), posicionando el negocio en buscadores sin pautas publicitarias costosas.

4. **Soporte de Ingeniería y Canal de Alerta Temprana**
   - Bolsa mensual de optimización física en el servidor (mantenimiento de base de datos MariaDB y optimización de código frontend) para garantizar velocidades de carga críticas con **Score superior a 90**.
   - Vinculación técnica perimetral con redes sociales y canales oficiales del cliente para asegurar la consistencia del flujo de datos.
   - Acceso exclusivo y prioritario a nuevos operadores cognitivos liberados por {{HOLDING_NAME}} en modalidad *Beta Cerrada*, siendo los primeros en recibir propuestas de configuración.

#### 6.5.4 Gamificación Orgánica y Contabilidad Automatizada del Partner

La IA calcula en tiempo real, en el instante exacto de generar un Dictamen de Blindaje, la proyección de ganancias netas que impactarán la **Bóveda Financiera** (`/mtx1/wallet`) del Partner leyendo el nivel jerárquico inmutable mapeado en `niveles_gamificacion.comision_pct` (Codex Tabla 1):

| Tier | Nombre Canónico | % Comisión Base | Requisito Operativo | Ganancia Setup (ej. `[PRECIO_SETUP_PLAN_BASICO_RETAIL]`) | Ganancia MRR (Póliza Base `[PRECIO_MRR_POLIZA_SENTINEL]`) |
| :--- | :--- | :---: | :--- | :---: | :---: |
| T1 | El Roble | `[PCT_COMISION_T1]` | Nivel base / ingreso al sistema | `[PCT_COMISION_T1]` × Setup | `[PCT_COMISION_T1]` × MRR |
| T2 | El Cedro | `[PCT_COMISION_T2]` | [N]° clientes activos simultáneos | `[PCT_COMISION_T2]` × Setup | `[PCT_COMISION_T2]` × MRR |
| T3 | El Baobab | `[PCT_COMISION_T3]` | [N]° clientes activos simultáneos | `[PCT_COMISION_T3]` × Setup | `[PCT_COMISION_T3]` × MRR |
| T4 | La Secuoya | `[PCT_COMISION_T4]` | Operadores Top Élite / excelencia | `[PCT_COMISION_T4]` × Setup | `[PCT_COMISION_T4]` × MRR |

Las funciones avanzadas de tiers bloqueados permanecen completamente invisibles para el Partner en su panel de control, activando previews estratégicos que disparan el deseo de ascenso y evitan la saturación de interfaz.

#### 6.5.5 Guardrails del Price Floor Engine y Doble Aprobación

El sistema bloquea de raíz la destrucción de margen mediante la ecuación determinística del **Price Floor Engine**, gestionada en `/mtx1/admin/finance`:

```
Precio Mínimo = (Costo Base + Infraestructura + Consumo Tokens IA + Costo Operativo)
                × Multiplicador Riesgo
                + Margen Mínimo CFO
```

Queda estrictamente prohibido el *stacking* (acumulación) de cupones comerciales. Si un trato exige variaciones manuales de precio, la IA detiene el flujo y enruta la transacción hacia la tabla `aprobaciones_descuento` bajo una jerarquía estricta anti-abuso:

| Rango de Descuento | Nivel Requerido (`nivel_req`) | Resolución |
| :--- | :--- | :--- |
| 0% – 10% | `automatico` | Aprobación instantánea por el motor de IA |
| 11% – 20% | `supervisor` | Aprobación digital de rango gerencial |
| 21% – 35% | `director` | Escalado al Director Comercial de {{PROJECT_NAME}} |
| +35% | `cfo` | Bloqueo absoluto del sistema; requiere firma criptográfica única del CFO por pérdida estratégica |

#### 6.5.6 Protocolos de Conversión Cognitiva (Cupones Estratégicos B2B)

Los cupones calculados por la IA se inyectan exclusivamente en la etapa Kanban `radiografia_lista` como catalizadores de urgencia psicológica:

- **TIPO A (Urgencia):** descuentos con temporizador regresivo visible (*countdown*) parametrizado por geolocalización e industria.
- **TIPO B (Riesgo):** activado automáticamente si `cyber_score < 45` o si se detectan los puertos 21, 22 o 3306 abiertos. El copy asocia directamente la brecha técnica perimetral con la pérdida inminente de capital financiero.
- **TIPO C (Invisibles):** exclusivo para niveles El Baobab o superior. La IA aumenta artificialmente el precio base visual de lista y aplica un descuento encubierto internamente; el cliente final nunca visualiza la etiqueta de oferta, dotando al Partner de capital relacional y autoridad al presentarse como gestor de un "trato exclusivo de laboratorio".
- **TIPO D (Recuperación):** disparador automático de re-enganche tras detectar inactividad prolongada en la etapa Kanban `propuesta_enviada`, despachando flujos de seguimiento vía WhatsApp o correo transaccional (`Mailer.php`).

Al concluir de forma exitosa cualquier implementación, la IA acciona obligatoriamente el re-enganche comercial hacia el MRR recurrente emitiendo el **Gap Fear Trigger**:

> "El Ecosistema ha sido protegido con éxito y cuenta con protocolos Nivel Enterprise. Sin embargo, al tratarse de un entorno dinámico de red, nuevas amenazas perimetrales automatizadas emergerán en las próximas 72 horas. Se requiere la activación permanente de The Sentinel para sostener la Continuidad Operativa."

### 6.6 Contratos de Integración y Especificaciones Linux

#### 6.6.1 Especificaciones de Endpoints Core de Servidor

Para salvaguardar la sincronización síncrona y asíncrona de datos, la IA se adecúa a los archivos de runtime auditados en [PROVEEDOR_HOSTING] Cloud, compartiendo la base de datos central de forma segura mediante PDO **con la emulación de preparados desactivada**:

```php
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
```

> ✅ **Estado verificado:** Esta directiva ya está implementada en `CORE/src/conexion.php` (línea 106). Todo nuevo módulo PHP que instancie PDO directamente debe replicar esta configuración o, preferentemente, reutilizar la clase `Database` existente.

**Endpoint de Mensajería (legacy ChatBot):** `inc/chat_ai.php` (`POST`, `application/json`)

```json
{
  "message": "string",
  "history": [
    { "role": "user", "content": "string" },
    { "role": "assistant", "content": "string" }
  ],
  "language": "es"
}
```

- **Response exitoso:** `{ "success": true, "response": "string" }`.
- Ante fallos de red externa, AURA inyecta un mensaje determinista de protección perimetral para salvaguardar la experiencia de usuario.

**Endpoint de Persistencia y Extracción (legacy ChatBot):** `inc/save_chat_lead.php` (`POST`, `application/json`)

```json
{
  "action": "save_lead",
  "lead_id": "uuid-v4-opcional",
  "nombre": "string",
  "email": "string",
  "telefono": "string",
  "proyecto": "string",
  "chatHistory": [
    { "role": "user", "content": "string" }
  ]
}
```

Si `lead_id` se omite, el sistema ejecuta de forma síncrona un `INSERT` generando un UUID nativo en MariaDB. Las tareas de alto procesamiento (OSINT, Scanner, Cyber Score extendido y Business Foundation) se desvían de inmediato de forma **asíncrona a un Worker en Linux** mediante Cron o Jobs en segundo plano para asegurar una respuesta HTTP menor a 200ms en el frontend.

> **⚠️ DISCREPANCIA ACTIVA:** `inc/chat_ai.php` e `inc/save_chat_lead.php` corresponden al módulo **legacy ChatBot SaaS** (`CORE/ChatBot AI-generated/`), no al ecosistema TDTM principal. Antes de extender estos contratos al flujo TDTM, validar si la persistencia debe redirigirse a `prospectos` + `interacciones_ia` (Codex Tablas 7 y 8) en lugar de la tabla `leads` descrita abajo.

#### 6.6.2 Esquema Físico Canónico de Base de Datos (`leads`) — Anexo Histórico

> **⚠️ NO IMPLEMENTAR SIN AUTORIZACIÓN:** La tabla `leads` descrita en el documento fuente **no forma parte del Schema Maestro TDTM v2.0** (Codex §245–668, 29 tablas verificadas contra el dump real). Se documenta tal cual para preservar la intención original del protocolo, pero queda **prohibido** crearla por inferencia (Mandamiento 4 — Anti-Alucinación). Cualquier necesidad de persistencia de "leads" del chatbot legacy debe resolverse contra `prospectos` o mediante consulta directa al Arquitecto.

```sql
CREATE TABLE `leads` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `nombre` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `origen` varchar(50) DEFAULT 'Web Chat',
  `ip_address` varchar(45) DEFAULT NULL,
  `company` varchar(100) DEFAULT 'Particular',
  `project_type` varchar(100) DEFAULT 'Consulta General', -- Restricción inmutable VARCHAR 100
  `status` varchar(50) DEFAULT 'pending',                  -- Whitelist: pending, atendido, entregado
  `fecha` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 6.6.3 Traductor de Mapeo Inmutable (Diccionario de Naming)

Para garantizar la cohesión total de datos y evitar colisiones de variables, la IA implementa un traductor único bidireccional que mapea estrictamente las convenciones de nomenclatura del sistema (Mandamiento 7):

- **Capa de Backend y Columnas DB:** estrictamente `snake_case` (ej. `project_type`, `ip_address`, `lead_id`).
- **Variables de Runtime Frontend (JavaScript/React/JSON):** estrictamente `camelCase` (ej. `projectType`, `ipAddress`, `leadId`, `chatHistory`).

```php
function normalizeLead(array $payload): array {
    return [
        'project_type' => substr(trim(strip_tags($payload['projectType'] ?? '')), 0, 100),
        'ip_address'   => filter_var($payload['ipAddress'] ?? '', FILTER_VALIDATE_IP) ?: null,
    ];
}
```

> **Nota:** Esta función es ilustrativa del patrón de traducción `snake_case` ↔ `camelCase` con sanitización de longitud (`substr(..., 0, 100)`) y validación de tipo (`FILTER_VALIDATE_IP`). El patrón es reutilizable para cualquier endpoint nuevo, independientemente de si el destino final es `leads` o `prospectos`.

### 6.7 Checklist de Implementación (Capas Backend / Frontend)

> Ver checklist exhaustivo de hitos en [`CLAUDE.md`](../CLAUDE.md) §8 — "Hoja de Ruta Activa: Matriz de Resolución Cognitiva". Este documento es la fuente de detalle técnico; CLAUDE.md mantiene el estado de avance.

---

## SECCIÓN 7 — PROMPTS MAESTROS

Esta sección preserva, como artefactos literales, los dos prompts maestros que gobiernan la generación de contenido cognitivo del ecosistema: el system prompt operativo de **GEM** (motor comercial/auditor desplegado en Gemini/ChatGPT, ver `CLAUDE.md` §2.B) y el blueprint de ingeniería de prompts para el **Synaptic Core** (ver Sección 3).

### 7.1 GEM — {{PROJECT_NAME}} Master Architect & Sales Engine

**Nombre del GEM:** `{{PROJECT_NAME}} - Master Architect & Sales Engine`

**Descripción:** Motor principal de inteligencia artificial y ciberseguridad para {{HOLDING_NAME}}. Encargado de auditar vulnerabilidades, diseñar arquitecturas escalables y generar propuestas comerciales para los Business Partners.

**Instrucciones (System Prompt) — texto canónico:**

> Actúa como el núcleo de operaciones técnicas y comerciales de {{PROJECT_NAME}}, la división tecnológica de {{HOLDING_NAME}}. Tienes dos funciones principales, las cuales debes ejecutar con máxima precisión, tono profesional y enfoque en resultados (ROI):
>
> **FUNCIÓN 1: Auditoría de Ciberseguridad (The Scanner & The Sentinel)**
>
> Cuando recibas el comando `AXON_COTIZA` seguido de un reporte, debes analizarlo bajo la Metodología GEM:
> - **Paso A/B:** Evalúa el Escáner OSINT, las 5 Armas Militares (Cabeceras, SSL, WP-Hunter, Puertos) y el Checklist Oro.
> - **Paso C:** Calcula el 'Cyber Score' (0-100) y genera el diagnóstico.
> - **Paso D:** Cotiza dinámicamente:
>   - Score 90-100: Felicitar y ofrecer Póliza Sentinel (`[PRECIO_MRR_POLIZA_SENTINEL]` `{{CURRENCY}}`/mes).
>   - Score 70-89: Plan Básico (`[PRECIO_SETUP_PLAN_BASICO]` `{{CURRENCY}}`).
>   - Score 0-69: Plan Seguridad ORO (`[PRECIO_SETUP_PLAN_ORO_MIN]` - `[PRECIO_SETUP_PLAN_ORO_MAX]` `{{CURRENCY}}`).
>
> Nota: Siempre incluye la opción de 'Póliza Sentinel' para activar la defensa activa (Troll Mode / Bcrypt) en toda propuesta.
>
> **FUNCIÓN 2: Soporte a Business Partners (Generador de Negocios IA)**
>
> Cuando un Partner ingrese la URL o datos de un prospecto, debes operar como el 'Analizador de Riesgo B2B':
> - Realiza un análisis rápido y genera un FODA de su presencia digital.
> - Crea una 'Propuesta de Solución IA' (Chatbots, Agendamiento, Cobros).
> - Redacta una propuesta persuasiva destacando el ROI inmediato y la iguala mensual. El tono debe estar listo para que el Partner cierre la venta.

> **Nota de consolidación:** los montos de cotización dinámica (`[PRECIO_MRR_POLIZA_SENTINEL]` / `[PRECIO_SETUP_PLAN_BASICO]` / `[PRECIO_SETUP_PLAN_ORO_MIN]`–`[PRECIO_SETUP_PLAN_ORO_MAX]` `{{CURRENCY}}`) citados en este prompt corresponden a la versión de cotización activa documentada en `05_MATRIZ_FINANCIERA_Y_VENTAS.md` §2.1 (consolidado de `REGLA_AXON_VENTAS.txt`). Ante cualquier discrepancia numérica futura, `05_MATRIZ_FINANCIERA_Y_VENTAS.md` prevalece como única fuente de verdad financiera (Mandamiento 9).

### 7.2 Creador de Prompts — Blueprint de Ingeniería para Synaptic Core

Motor de Ingeniería de Prompts Avanzado de {{HOLDING_NAME}}. Su única función es generar la estructura técnica y el contenido exacto de los prompts que alimentan el **Synaptic Core** de la plataforma TDTM (Sección 3), asegurando compatibilidad total con el esquema de base de datos relacional (`synaptic_prompts`, Codex Tabla 18) y los contratos de API establecidos (`03_CONTRATOS_API_Y_RUTAS.md`).

**Instrucción operativa canónica:**

> Actúa como el Motor de Ingeniería de Prompts Avanzado de {{HOLDING_NAME}}. Tu única función es generar la estructura técnica y el contenido exacto de los prompts que alimentarán el "Synaptic Core" de la plataforma TDTM, asegurando que encajen al 100% con nuestro esquema de base de datos relacional y los contratos de API establecidos.
>
> Cuando se te solicite un prompt bajo este sistema, tu respuesta DEBE estructurarse estrictamente bajo el siguiente catálogo de campos para la base de datos:

**🗄️ BLUEPRINT DE REGISTRO — SYNAPTIC CORE**

| Campo | Especificación |
| :--- | :--- |
| `context_key` | Identificador único en `snake_case`. Ej: `axon_genesis_maestro`, `deal_assistant_pricing` |
| `nombre_prompt` | Nombre elegante e institucional visible en la UI de administración |
| `descripcion` | Explicación ejecutiva de una sola línea sobre qué fricción operativa resuelve este prompt |
| `variables_requeridas` | Array JSON con las variables exactas que el backend PHP interpolará (ej: `["nombre", "geolocalizacion", "id_nivel"]`) |
| `provider_id` | Asignación sugerida del LLM objetivo (1=OpenAI gpt-4o-mini, 2=Claude 3.5 Sonnet, 3=Gemini 1.5 Pro) |

**🌌 CAPA 1: SYSTEM PROMPT (Reglas del Ser)**

> Inserta aquí el prompt maestro del sistema enriquecido con la psicología DCD: inyección de Calibración Emocional, sistema de Mood Awareness, controles anti-robotización, enfoque absoluto en el lenguaje de ROI/Continuidad Operativa y las restricciones explícitas de tono e identidad institucional. Recuerda incluir los tags de interpolación `{{variable}}` de forma orgánica.

**👤 CAPA 2: PROMPT USUARIO / TEMPLATE (Reglas del Hacer)**

> Inserta aquí la estructura del mensaje del usuario o la plantilla del evento de la aplicación que se le enviará al LLM, asegurando incluir el tag obligatorio `{{test_message}}` para que el Playground de TDTM (`03_CONTRATOS_API_Y_RUTAS.md`, Contrato 5 — `POST ?action=test`) pueda ejecutar simulaciones dinámicas.

**📊 OBJETIVO Y RESULTADO ESPERADO**

- **Impacto Cognitivo:** explica qué efecto psicológico o dependencia funcional positiva generará este prompt en el Partner o Cliente final.
- **Resultado Financiero / ROI:** detalla cómo esta instrucción ayuda a proteger los márgenes del Price Floor Engine (Sección 6.5.5) o a acelerar el cierre comercial del proyecto.

**Formato de entrada para iniciar la generación de un nuevo prompt:**

```
PROPÓSITO DEL PROMPT: [Insertar propósito aquí]
CONTEXTO DEL DESPLIEGUE: [Insertar contexto aquí]
```

> **Nota de consolidación:** este blueprint es el procedimiento operativo recomendado para poblar nuevas filas de `synaptic_prompts` vía el endpoint `api/admin/synaptic_core.php` (ver `03_CONTRATOS_API_Y_RUTAS.md`, Contrato 5, y Sección 3 de este documento para el modelo de versionado `context_key`/`version`).

---
