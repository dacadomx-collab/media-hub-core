# 07_UI_MODULOS_Y_PANTALLAS — {{PROJECT_NAME}}
## Dashboard TDTM, Partner Academy, Módulos IA y Flujos UX
**Versión:** 1.0 | **Fecha de consolidación:** 2026-06-11 | **Clasificación:** Pilar Canónico — Documento Vivo
**Fuentes consolidadas:** `07_TDTM_DASHBOARD_Z.md`, `06_PARTNER_ACADEMY_X.md`, `modulos_IA_instalar.md`, `TDTM_The_Deep_Tech_Matrix.txt`

> Este pilar es el mapa de pantallas, componentes y flujos UX del ecosistema Next.js `/z/` (The Deep Tech Matrix), del módulo HTML `/x/` (Partner Academy) y del Panel Super Admin V2 (módulos de administración IA, pagos y auditoría). Es la Fuente de Verdad para la capa de presentación de {{PROJECT_NAME}}.

---

## 0. VISIÓN GENERAL DEL ECOSISTEMA TDTM

*(Origen: `TDTM_The_Deep_Tech_Matrix.txt` — Blueprint Táctico V.1.1)*

"The Deep Tech Matrix" (TDTM) es un Asistente Cognitivo de Ventas B2B. Su objetivo es tomar a un socio comercial (Partner) con cero conocimientos técnicos, guiarlo paso a paso, generarle herramientas de cierre mediante IA y mantenerlo motivado mediante un sistema de gamificación orgánico, transparencia financiera absoluta y mejora continua.

### Arquitectura de Roles (Vista Admin)

| Rol | Vista / Alcance |
| :--- | :--- |
| **Super Admin** | Vista global de ingresos, aprobación de retiros financieros, gestión de los 3 dashboards. |
| **Soporte/Técnico** | Vista de consola, logs del Sentinel (`/SECURITY`), tickets y monitoreo. |
| **Partner (Socio Comercial)** | Núcleo operativo — ver Sección 2 (Flujo UX) y Sección 1 (Dashboard TDTM). |

---

## 1. TDTM DASHBOARD `/z` — IDENTIDAD Y ARQUITECTURA

*(Origen: `07_TDTM_DASHBOARD_Z.md` — Auditoría directa de código, Next.js App Router)*

### 1.1 Identidad del Módulo

| Parámetro | Valor |
| :--- | :--- |
| **Nombre Oficial** | The Deep Tech Matrix (TDTM) |
| **Ruta local** | `C:\xampp\htdocs\{{PROJECT_NAME}}\z\` |
| **Ruta de acceso** | `http://localhost:3000` (servidor de desarrollo Next.js) |
| **Stack real** | Next.js 14 (App Router) + TypeScript + Tailwind CSS + Framer Motion + Lucide Icons |
| **Gestor de paquetes** | pnpm (ver `pnpm-lock.yaml`) |
| **Componentes UI** | shadcn/ui (ver `components.json`) |
| **Propósito** | Panel operativo diario para Partners de {{PROJECT_NAME}} |
| **Roles soportados** | Super Admin, Partner (Socio Comercial), Soporte Técnico |

### 1.2 Estructura de Rutas (App Router)

```
z/app/
├── layout.tsx              ← Layout raíz (fuentes, globals.css)
├── globals.css             ← Variables CSS, tema light/dark
├── page.tsx                ← Landing Principal de {{PROJECT_NAME}} (dcd.{{PRODUCTION_DOMAIN}})
└── mtx1/                   ← El "Sistema Operativo" del Partner (ruta protegida)
    ├── page.tsx            ← Dashboard Principal (Hub de las 3 Divisiones)
    ├── ai-operators/
    │   └── page.tsx        ← Motor de Prospección IA (Input → Radiografía → Propuesta PDF)
    ├── wallet/
    │   └── page.tsx        ← Bóveda Financiera (Saldo, Retiro, Gamificación, Fiscal)
    ├── security/
    │   └── page.tsx        ← Consola de Auditoría Perimetral (The Scanner UI)
    ├── prospects/
    │   └── page.tsx        ← Pipeline Kanban Ejecutivo (CRM Cognitivo)
    ├── analytics/
    │   └── page.tsx        ← Analytics Global + Centro de Innovación
    ├── admin/
    │   └── page.tsx        ← Super Admin — Centro de Comando CEO
    └── support/
        └── page.tsx        ← Panel Soporte Técnico — The Sentinel + Tickets
```

**⚠️ Rutas pendientes de crear (mencionadas en prompts pero sin archivo):**
- `/mtx1/admin/finance` — Motor de Precios y Comisiones CFO
- `/mtx1/foundry` — The Foundry (Solutions Marketplace)
- `/mtx1/messages` — Sistema de Mensajería Interna
- `/mtx1/help` — Helpdesk del Partner

### 1.3 Componentes Globales

```
z/components/
├── dashboard-layout.tsx    ← Layout con Sidebar + TopNav para todas las páginas /mtx1
├── command-center.tsx      ← Componente de búsqueda global / command palette
├── navbar.tsx              ← Navbar de la landing pública (app/page.tsx)
├── hero-section.tsx        ← Hero de la landing con NetworkVisualization canvas
├── trust-ticker.tsx        ← Ticker de logos/métricas de confianza
├── problem-section.tsx     ← Sección "El Problema" de la landing
├── engine-section.tsx      ← Sección "El Motor IA" de la landing
├── roi-section.tsx         ← Sección ROI/resultados de la landing
├── process-section.tsx     ← Sección "Implementación Fricción Cero" (3 pasos)
├── cta-section.tsx         ← CTA final de la landing
├── footer.tsx              ← Footer de la landing
├── floating-utilities.tsx  ← Elementos flotantes de la landing
└── ui/                     ← Componentes shadcn/ui (button, card, etc.)
```

---

## 1.4 HITO — Executive Calm UX Layer (2026-06-06)

### Nuevos Componentes: AXON_GENESIS Console v2

Los siguientes 3 componentes fueron añadidos en `z/app/mtx1/admin/genesis/`:

#### `MissionControlOnboarding.tsx`

| Atributo | Valor |
| :--- | :--- |
| **Propósito** | Onboarding premium de primera sesión para la Consola de Orquestación |
| **Persistencia** | `localStorage.getItem("axon_genesis_onboarded")` — key canónica |
| **Activación** | Se muestra si el flag es null/falsy. Se cierra al completar la simulación o al hacer skip. |
| **Simulación** | `SIM_EVENTS[]`: array de 16 eventos timed que recrean el pipeline 1→4 incluyendo error SMTP y auto-healing |
| **Regla #01** | Se estampa al finalizar: "Si observas una alerta, NO modifiques tablas ni reinicies manualmente." |
| **Dependencias** | framer-motion, lucide-react (Cpu, CheckCircle2, Zap, ShieldCheck, ArrowRight, X) |
| **Estado reactivo** | `visible`, `simRunning`, `simDone`, `events[]`, `phasesDone: Set<number>` |

#### `ProjectLifeline.tsx`

| Atributo | Valor |
| :--- | :--- |
| **Propósito** | Timeline lateral contextual estilo GitHub Actions con micro-briefings ejecutivos por fase |
| **Props** | `statuses: Record<number,string>`, `curPhase: number\|null`, `streaming: boolean` |
| **Hover briefing** | Cada fase despliega `brief` + `details[]` al hacer hover |
| **Fallo contingency** | Si `status === 'fallido'`, muestra el bloque de contingencia específico de cada fase |
| **Running indicator** | Muestra "En ejecución · Esperar Auto-Healing" cuando la fase está activa |
| **Tiempo estimado** | "Tiempo estimado de recuperación: 18 segundos" en contingencia |
| **Dependencias** | framer-motion, lucide-react (Search, Palette, Hammer, ShieldCheck, etc.) |

#### `FlightRecorder.tsx`

| Atributo | Valor |
| :--- | :--- |
| **Propósito** | Reemplaza `TerminalConsole`. Caja Negra Operativa con traducción semántica humano↔sistema |
| **Props** | `logs: SseEntry[]`, `streaming: boolean`, `onAbort: () => void` |
| **Traducción** | `TRANSLATIONS[]`: 20+ pares `{pattern: RegExp, human: string}`. El raw se preserva en `title` |
| **Priority badges** | Función `getPriority()` → 4 niveles: success (verde), cyan, amber, red |
| **Indicador FAULT** | Si algún evento es error/CRITICAL, el borde del componente muta a rojo con `box-shadow` |
| **Leyenda** | Barra de leyenda de badges en el header de la consola |
| **Marker `[~]`** | Indica que el mensaje fue traducido al humano (original en tooltip) |
| **Dependencias** | framer-motion, lucide-react (Square, HardDrive) |

### Replay Histórico — Terminal Inteligente

Cuando el usuario selecciona un proyecto con fases completadas, `loadExecutions()` construye automáticamente un `SseEntry[]` sintético desde `genesis_ejecuciones.output_json`:

| Campo de output_json | Comportamiento en FlightRecorder |
| :--- | :--- |
| `foundation.total_artefactos` | Log: "FoundationExecutor: N artefactos generados" |
| `_workspace_path` | Log: ruta absoluta del workspace |
| `smoke_test.report_lines[]` | Líneas del bloque monoespaciado coloreadas por contenido |
| `smoke_ok = true` | Log: "🎯 Proyecto mutado a estatus 'activo'" |
| `deploy_fisico_ok + deploy_destino` | Log: ruta de deploy Apache |
| `error_mensaje` en fallido | Log rojo con el error crudo |

El `output_json` es ahora expuesto por `GET_PROJECT` (gateway.php) para habilitar este replay.

### Contratos de LocalStorage

| Key | Tipo | Valor | Propósito |
| :--- | :--- | :--- | :--- |
| `axon_genesis_onboarded` | `string \| null` | `"1"` cuando completado | Flag de onboarding para `MissionControlOnboarding` |

### Contratos semánticos de traducción (`TRANSLATIONS[]`)

| Patrón | Traducción humana |
| :--- | :--- |
| `SQLSTATE[42S22]` | Columna no encontrada — mismatch entre código y schema de BD |
| `SMTP_CONNECTION_TIMEOUT` | Canal SMTP temporalmente inaccesible — reintentando con fallback |
| `Lease Lock activo` | Pipeline bloqueado por ejecución paralela — espera a que el lock expire (10s) |
| `Saga Recovery Protocol` | Divergencia estado BD↔Manifest detectada — el motor intentará auto-healing |
| `Auto-Healing.*recuperado` | Auto-Healing exitoso — el canal fue restaurado automáticamente |
| `SMOKE TEST FALLIDO` | Smoke Test falló — corrige las credenciales en la Bóveda antes del deploy |
| *(+15 pares adicionales)* | Ver `FlightRecorder.tsx::TRANSLATIONS[]` |

---

## 1.5 Páginas Detalladas — Inventario de Funcionalidades

### 1.5.1 Landing Pública — `app/page.tsx`

**Propósito:** Página principal de `dcd.{{PRODUCTION_DOMAIN}}` (face pública del producto)

**Secciones:**
- Navbar → HeroSection (H1 + NetworkVisualization canvas animado + AISearchBar) → TrustTicker → ProblemSection → EngineSection → ROISection → ProcessSection → CTASection → Footer + FloatingUtilities

**Terminología confirmada en código:**
- "Fricción Cero Operativa" ✅ (hero-section.tsx:266)
- "Implementación Fricción Cero" ✅ (process-section.tsx:23)

---

### 1.5.2 Dashboard Principal — `/mtx1` (`app/mtx1/page.tsx`)

**Componentes internos:**
- `CognitiveGreeting` — Header de saludo + input bar para "Ingresa URL de prospecto..."
  - Label "Silent Director" ⚠️ (en inglés — debería ser "Copiloto IA" o "Director Silencioso")
  - 3 botones de input: texto + Upload + Mic
- `HubCards` — Grid 3 cards: Security Scanner | AI Business Operators | The Foundry
- `QuickLinksRow` — Atajos: Finanzas | Analytics | Prospectos
- `GamificationWidget` — Muestra nivel actual "El Roble" + progress bar hacia "El Baobab"
- `WalletWidget` — Saldo `[MONTO_MOCK_SALDO]` `{{CURRENCY}}` (mock) + botón "Retirar Fondos" → link a `/mtx1/wallet`
- `ExecutivePipeline` — Tabla de prospectos con etapas y valores en MXN
- `InnovationCenter` — Ideas en desarrollo → link a `/mtx1/analytics`

---

### 1.5.3 AI Business Operators — `/mtx1/ai-operators`

**Flujo en 3 secciones (mockup):**
1. **Input Engine** — "¿A quién vamos a diagnosticar hoy?" + URL + Redes Sociales + Notas Secretas/Audio
2. **Analysis State** — Animación de scanning con textos: "Extrayendo arquitectura web..." etc.
3. **Result View** — FODA Digital izquierda + Preview PDF derecha + "Costo Oculto Detectado"

**Acciones disponibles:** "Descargar PDF Ejecutivo" | "Enviar Propuesta por WhatsApp" | "Guardar en Pipeline"

**Terminología confirmada:** "Propuesta de Fricción Cero" ✅ (ai-operators/page.tsx:538)

---

### 1.5.4 Bóveda Financiera (Wallet) — `/mtx1/wallet`

**4 secciones UI:**
1. **Banking Header** — Saldo animado (`[MONTO_MOCK_SALDO_ANIMADO]` `{{CURRENCY}}`) + Comisiones Pendientes (`[MONTO_MOCK_COMISIONES_PENDIENTES]` `{{CURRENCY}}`) + botón "Retirar Fondos"
2. **Sistema de Crecimiento (Gamificación)** — Nivel actual + Progress Bar hacia siguiente nivel
3. **Pipeline de Comisiones** — Tabla de transacciones con badges Pagado/En Tránsito
4. **Datos de Retiro** — Cuenta CLABE + Constancia Fiscal (datos mockeados)

**Estructura de Niveles de Gamificación (implementada en código):**

| Nivel | Nombre | Umbral de Ingresos |
| :--- | :--- | :--- |
| 1 | La Semilla | `[UMBRAL_NIVEL_1]` `{{CURRENCY}}` |
| 2 | El Brote | `[UMBRAL_NIVEL_2]` `{{CURRENCY}}` |
| 3 | El Roble | `[UMBRAL_NIVEL_3]` `{{CURRENCY}}` |
| 4 | El Baobab | `[UMBRAL_NIVEL_4]` `{{CURRENCY}}` |
| 5 | La Sequoia | `[UMBRAL_NIVEL_5]` `{{CURRENCY}}` |

> **Nota de plantilla:** los umbrales `[UMBRAL_NIVEL_N]` deben ser estrictamente crecientes (`UMBRAL_NIVEL_1 < ... < UMBRAL_NIVEL_5`), con `UMBRAL_NIVEL_1 = 0`. Se definen por el Arquitecto/CFO al instanciar este pilar.

**⚠️ NOTA CRÍTICA:** Los porcentajes de comisión por nivel NO están implementados en este módulo. El código usa umbrales de ingresos acumulados para determinar el nivel. Los porcentajes de comisión deben implementarse en `/mtx1/admin/finance` (pendiente de crear).

---

### 1.5.5 Security Scanner — `/mtx1/security`

**3 secciones UI (mockup):**
1. **Target Acquisition** — Input para dominio/IP + botón "Desplegar Escáner OSINT"
2. **Live Sentinel Radar** — Animación de scanning + terminal feed (Framer Motion)
3. **Executive Cyber Score** — Gauge circular 45/100 (ejemplo crítico, naranja/rojo)

**Findings mockeados:** Riesgo de Suplantación CRÍTICO | Puertos Expuestos: 3 | Protección WAF: AUSENTE

**Integración pendiente:** Conectar con `SECURITY/GEM/api_scan.php` para escaneos reales

---

### 1.5.6 Pipeline de Prospectos (Kanban) — `/mtx1/prospects`

**2 secciones:**
1. **Kanban Board** — 4 columnas: Leads (Sin Analizar) | Radiografía Lista | Propuesta Enviada | Cerrado (Fricción Cero)
2. **AI Prospect Detail** — Slide-over con: Nombre/Dominio + Sugerencias Upsell IA + PDF FODA + Link de Pago

**Etiqueta especial:** "Costo Oculto Detectado" (badge rojo en cards urgentes)

---

### 1.5.7 Analytics & Evolución — `/mtx1/analytics`

**2 secciones:**
1. **Global Impact Analytics** — Métricas: "Tasa de Cierre de Fricción Cero: `[PCT_MOCK_TASA_CIERRE]`", "Ingresos Totales: `[MONTO_MOCK_INGRESOS_TOTALES]` `{{CURRENCY}}`", "Tiempo Promedio: `[N_MOCK_DIAS_PROMEDIO]` días"
2. **Centro de Innovación** — Input de sugerencias + Roadmap de ideas con badges Implementado/En Desarrollo

---

### 1.5.8 Super Admin — `/mtx1/admin`

**4 secciones:**
1. **Role Switcher** — Dropdown: Vista Actual (Super Admin / Partner / Soporte)
2. **Global KPIs** — Ingresos Globales MTD: `[MONTO_MOCK_INGRESOS_MTD]` `{{CURRENCY}}` | Comisiones por Pagar: `[MONTO_MOCK_COMISIONES_PAGAR]` `{{CURRENCY}}` | Partners Activos: `[N_MOCK_PARTNERS_ACTIVOS]`
3. **Financial Approvals** — Tabla de Retiros Pendientes con botones Aprobar/Rechazar (funcional en UI, sin backend)
4. **User Management** — Tabla de usuarios con Rol, Nivel (árbol), Estado + menú de acciones

**Tiers mostrados en datos mock:** La Semilla, El Roble, El Baobab (sin Cedro, sin Sequoia en tabla)

---

### 1.5.9 Soporte Técnico — `/mtx1/support`

**3 secciones:**
1. **System Health** — Uptime 99.9% + Server Load + API Latency + Logs del Sentinel
2. **Evolution Center** — Kanban/Lista de tickets y sugerencias de Partners
3. **Partner Assistance** — Búsqueda de Partner + Reset Password / Desbloquear Cuenta / Forzar Sync IA

---

## 1.6 Sistema de Gamificación — Árbol de Niveles (consolidado)

*(Consolidado de `07_TDTM_DASHBOARD_Z.md` §5 y `TDTM_The_Deep_Tech_Matrix.txt` Fase 4 — versión más completa del código preservada con detalles complementarios del Blueprint)*

### Árbol de Niveles (implementado en `wallet/page.tsx`)

```typescript
// Valores de ejemplo — sustituir por UMBRAL_NIVEL_1..5 definidos para el proyecto (estrictamente crecientes)
const treeLevels = [
  { name: "La Semilla",  min: 0       },
  { name: "El Brote",    min: 5000    },
  { name: "El Roble",    min: 15000   },
  { name: "El Baobab",   min: 50000   },
  { name: "La Sequoia",  min: 150000  },
]
```

**Lógica:** El nivel se determina por los ingresos totales acumulados del Partner.

**Progress:** `((currentEarnings - currentLevel.min) / (nextLevel.min - currentLevel.min)) * 100`

### Narrativa de Crecimiento Orgánico (Blueprint TDTM — complemento conceptual)

El estatus del Partner se mide en árboles, representando raíces sólidas y crecimiento progresivo. Los nombres canónicos del árbol corresponden 1:1 a `treeLevels` (arriba):

| Nivel Blueprint | Nombre Canónico (código) | Narrativa |
| :--- | :--- | :--- |
| Base | La Semilla | Punto de partida — `[UMBRAL_NIVEL_1]` `{{CURRENCY}}` |
| Crecimiento inicial | El Brote | Primeros ingresos — `[UMBRAL_NIVEL_2]` `{{CURRENCY}}` |
| Nivel 1 | **El Roble** | Fuerte, estableciendo bases — `[UMBRAL_NIVEL_3]` `{{CURRENCY}}` |
| Nivel 2 | **El Baobab** | Resiliente, dando abundancia — `[UMBRAL_NIVEL_4]` `{{CURRENCY}}` |
| Nivel 3 | **La Secuoya** (La Sequoia) | Gigante, inquebrantable, élite — `[UMBRAL_NIVEL_5]` `{{CURRENCY}}` |

> Nota de naming: el Blueprint usa "La Secuoya"; el código (`treeLevels`) usa "La Sequoia". Mandamiento 10 (Sinónimos Prohibidos) requiere que el Arquitecto confirme la grafía canónica única — se preserva ambas formas aquí hasta resolución.

### ⚠️ Brecha Detectada — Porcentajes de Comisión

Los porcentajes de comisión por nivel **NO ESTÁN IMPLEMENTADOS** en ningún archivo de `/z`. El módulo `/mtx1/admin/finance` (pendiente) es donde deben vivir y gestionarse. Hasta su implementación, los porcentajes canónicos deben ser confirmados por el Arquitecto y registrados en `knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md`.

---

## 1.7 Sidebar — Navegación Global

El sidebar es gestionado por `components/dashboard-layout.tsx` y renderizado en todas las rutas `/mtx1/*`.

**Secciones del Sidebar (en orden):**
- Dashboard (`/mtx1`)
- AI Operators (`/mtx1/ai-operators`)
- The Foundry (`/mtx1/foundry`) ⚠️ Página pendiente de crear
- Wallet / Finanzas (`/mtx1/wallet`)
- Analytics (`/mtx1/analytics`)
- Prospectos (`/mtx1/prospects`)
- Security (`/mtx1/security`)
- Support (`/mtx1/support`)
- Admin (`/mtx1/admin`) — visible para Super Admin

**Bottom del Sidebar:** Toggle Dark/Light Mode | Configuración | Cerrar Sesión

---

## 1.8 Cómo Levantar el Servidor Local

```bash
cd C:\xampp\htdocs\{{PROJECT_NAME}}\z
pnpm install          # Instalar dependencias (ya instaladas si node_modules existe)
pnpm dev              # Levantar en http://localhost:3000
```

**Rutas de acceso:**

| Ruta | Descripción |
| :--- | :--- |
| `http://localhost:3000/` | Landing pública {{PROJECT_NAME}} |
| `http://localhost:3000/mtx1` | Dashboard Partner (sin auth real) |
| `http://localhost:3000/mtx1/wallet` | Bóveda Financiera |
| `http://localhost:3000/mtx1/admin` | Super Admin |

---

## 1.9 Pendientes Prioritarios (Fase 2 — Integración)

| # | Pendiente | Impacto | Archivo Target |
| :--- | :--- | :--- | :--- |
| 1 | Crear `/mtx1/admin/finance` (Motor CFO de Precios y Comisiones) | CRÍTICO | `z/app/mtx1/admin/finance/page.tsx` |
| 2 | Crear `/mtx1/foundry` (The Foundry / Solutions Marketplace) | ALTO | `z/app/mtx1/foundry/page.tsx` |
| 3 | Crear `/mtx1/messages` (Mensajería Interna) | ALTO | `z/app/mtx1/messages/page.tsx` |
| 4 | Crear `/mtx1/help` (Helpdesk del Partner) | MEDIO | `z/app/mtx1/help/page.tsx` |
| 5 | Corregir label "Silent Director" a "Copiloto IA" | BAJO | `z/app/mtx1/page.tsx:54` |
| 6 | Conectar Scanner UI con `SECURITY/GEM/api_scan.php` | ALTO | `z/app/mtx1/security/page.tsx` |
| 7 | Implementar autenticación real (sesión PHP o JWT) | CRÍTICO | Requiere diseño en `knowledge/03_CONTRATOS_API_Y_RUTAS.md` |
| 8 | Conectar botones del Wallet con backend PHP | ALTO | `api/wallet.php` (pendiente de crear) |

---

## 2. FLUJO UX DEL PARTNER DASHBOARD — 6 FASES

*(Origen: `TDTM_The_Deep_Tech_Matrix.txt` — Blueprint Táctico V.1.1, El Dashboard del Partner)*

Este es el flujo de experiencia narrativo que describe cómo un Partner recorre `/mtx1` desde el primer login hasta el ciclo de mejora continua. Las fases 2-5 mapean directamente a las páginas documentadas en la Sección 1.5.

### Fase 1 — El Recibimiento Cognitivo (AXON IA)

- La IA saluda por su nombre, detecta su hora/clima local y lee el historial para hacer plática empática (ej. pregunta por sus proyectos anteriores de forma amigable).
- Ofrece **3 vías de input**: Botones, Texto o Audio.
- **Mapeo a UI real:** `CognitiveGreeting` en `/mtx1` (Sección 1.5.2) — header de saludo + input bar + 3 botones (texto/Upload/Mic).

### Fase 2 — El Hub de las 3 Divisiones (UI Dinámica)

- **Perimeter Intelligence (SECURITY):** Botón al "{{PROJECT_NAME}} Global Monitor".
- **AI Business Operators (PARTNERS):** Prospección IA.
- **The Foundry (CORE):** Fábrica de software.
- **Mapeo a UI real:** `HubCards` en `/mtx1` — Grid 3 cards: Security Scanner | AI Business Operators | The Foundry (Sección 1.5.2).

### Fase 3 — El Motor de Ventas (AI Business Operators)

- **Input:** URL, redes sociales y audios/notas secretas del Partner.
- **Procesamiento:** Generación de FODA Digital y Propuesta de Solución IA.
- **Entregable:** PDF ejecutivo automático, listo para WhatsApp o Email.
- **Mapeo a UI real:** `/mtx1/ai-operators` — Input Engine → Analysis State → Result View (Sección 1.5.3).

### Fase 4 — Pipeline y Gamificación Orgánica (Los Árboles)

- El Partner ve su embudo de ventas y sugerencias de "Upsells" (qué más venderle a ese cliente).
- **Niveles de Gamificación (Crecimiento Orgánico):** el estatus del Partner se mide en árboles, representando raíces sólidas y crecimiento — ver Árbol de Niveles consolidado en Sección 1.6.
- **Mapeo a UI real:** `/mtx1/prospects` (Kanban + Upsells IA, Sección 1.5.6) + `GamificationWidget` / Wallet (Sección 1.5.4 y 1.6).

### Fase 5 — La Bóveda Financiera (Wallet & Pagos)

- **Wallet B2B:** vista financiera ultra pro y sin mareos. Muestra el saldo disponible por comisiones ganadas.
- **Retiro 1-Click:** botón estilo PayPal ("Retirar Fondos"). El Partner decide exactamente cuándo quiere su dinero en su cuenta.
- **Datos Fiscales:** sección segura para subir cuenta CLABE/Bancaria y datos de facturación.
- **Mapeo a UI real:** `/mtx1/wallet` — Banking Header, Sistema de Crecimiento, Pipeline de Comisiones, Datos de Retiro (Sección 1.5.4).

### Fase 6 — Bucle de Evolución (Escucha Activa)

- **Centro de Sugerencias:** módulo donde el Partner puede dejar comentarios, reportar fricciones o sugerir nuevas herramientas para vender más.
- El sistema "escucha" a sus usuarios para evolucionar la Matrix en la versión 2.0.
- **Mapeo a UI real:** `InnovationCenter` (Sección 1.5.2) + `/mtx1/analytics` Centro de Innovación (Sección 1.5.7).

---

## 3. PARTNER ACADEMY `/x`

*(Origen: `06_PARTNER_ACADEMY_X.md` — Auditoría directa de `x/code.html`, `x/COPY_NoteBookLM.txt`, `x/DESIGN.md`)*

### 3.1 Identidad del Módulo

| Parámetro | Valor |
| :--- | :--- |
| **Nombre Oficial** | Partner Academy |
| **Ruta local** | `C:\xampp\htdocs\{{PROJECT_NAME}}\x\` |
| **Archivo principal** | `x/code.html` |
| **Stack** | HTML estático + Tailwind CDN + Material Symbols + JS nativo |
| **Propósito** | Landing Page de bienvenida y capacitación B2B para prospectos de Partners |
| **Audiencia** | Prospectos con perfil ejecutivo, sin necesidad de conocimiento técnico previo |
| **Posicionamiento** | "No eres un perfil de ventas tradicional, eres una Arquitecta de Confianza Operativa" |

### 3.2 Sistema de Diseño (`x/DESIGN.md` — Cognitive Infrastructure System)

| Parámetro | Valor Canónico |
| :--- | :--- |
| **Paleta Principal** | Light Mode únicamente. Fondos "broken white" (#F8FAFC) |
| **Authority Navy** | `#0F172A` — tipografía principal, componentes de autoridad |
| **Steel Blue Trust** | `#475569` — subtítulos, componentes secundarios |
| **AI Cyan Action** | `#22D3EE` — acentos IA, estados activos, CTAs primarios |
| **Fuente Principal** | Inter (todos los pesos) |
| **Fuente Mono** | JetBrains Mono — labels de sistema, datos técnicos |
| **Border** | 1px, `rgba(15, 23, 42, 0.08)` — ultra-soft |
| **Bordes redondeados** | Cards: 1rem. Hero cards: 1.5rem. Contenedores ejecutivos: 2rem+ |
| **Filosofía** | "Friction Zero" — glassmorphism + minimalismo. "Executive Calm". |

**REGLA DE COLOR:** Sin gradientes saturados. Tintes Cyan al 5-10% de opacidad para glows de fondo.

### 3.3 Estructura de Secciones (Flujo de la Página)

#### 3.3.1 Navegación Fija (Top)
- Logo {{PROJECT_NAME}} (imagen externa via lh3.googleusercontent.com)
- Label: "Partner Academy"
- Links: Ecosistema (`#claridad`), Metodología (`#empatia`), Mercado (`#mercado`), Proceso (`#pasos`)
- CTA Nav: Botón "Onboarding" (sin funcionalidad activa — pendiente conectar)

#### 3.3.2 Hero Section — "El Recibimiento"
- **H1:** "Bienvenida a tu nuevo Sistema Operativo Cognitivo: The Deep Tech Matrix"
- **H2:** "No eres un perfil de ventas tradicional, eres una **Arquitecta de Confianza Operativa**"
- Elemento Audio Briefing: Botón Play mockup + waveform animado (sin audio real conectado)
- Imagen lateral: aspect-ratio 4/5, placeholder visual

#### 3.3.3 Módulo de Claridad — `#claridad` (Los 3 Pilares)
- **Staggered Grid** de 3 columnas (desktop) / 1 columna (mobile)
- Pilar 1: **DCD_LABS** — "El laboratorio de R&D" (fondo slate-50)
- Pilar 2: **{{PROJECT_NAME}}** — "Tu interfaz principal / Sistema Operativo Cognitivo" (fondo authority-navy, destacado)
- Pilar 3: **The Deep Tech Matrix** — "La metodología exclusiva" (fondo slate-50, offset visual)
- Animación: hover eleva la card y expande una barra inferior cyan

#### 3.3.4 Módulo de Empatía — `#empatia` (Fricción Cero)
- Fondo oscuro (slate-900) con imagen overlay en grayscale
- **H2:** "**Fricción Cero** para tu Desarrollo Profesional."
- Texto: "Con tu **Copiloto IA**, transformas la incertidumbre en continuidad operativa absoluta"
- Métricas visibles: 98.4% Precisión Técnica | 24/7 Soporte IA
- Panel decorativo (mockup AI análisis, sin funcionalidad real)

#### 3.3.5 El Mercado — `#mercado`
- Grid de 3 cards: Negocios Locales | Despachos y Clínicas | Corporativos
- Copy: "tu misión es llevarles tranquilidad"

#### 3.3.6 El Paso a Paso — `#pasos` (Tu Trayectoria en 3 Pasos)
- Timeline horizontal con línea conectora (desktop)
- **Paso 1:** "Ingreso" — cargar datos del prospecto en la consola AXON
- **Paso 2:** "Radiografía" — la IA genera una propuesta táctica
- **Paso 3:** "Entrega" — presentas el diagnóstico y aseguras la continuidad

#### 3.3.7 CTA Dual — "¿Lista para redefinir tu trayectoria?"
- **Botón Principal (cyan):** `"Aceptar Términos e Iniciar Registro"` ⚠️ SIN FUNCIONALIDAD REAL AÚN
- **Botón Secundario:** `"Entrar al Simulador Interactivo"` ⚠️ SIN FUNCIONALIDAD REAL AÚN
- Ambos botones están implementados como `<button>` estáticos, sin action/form/href

#### 3.3.8 Footer
- Logo {{PROJECT_NAME}} (grayscale, 50% opacidad)
- Copyright: "© 2024 {{PROJECT_NAME}}. All Rights Reserved."
- Links: Privacidad | Términos | Contacto (todos `href="#"` — pendiente de crear)

### 3.4 Flujo de Sign-Up (Estado Actual vs. Estado Objetivo)

#### Estado Actual (Implementado):
```
Usuario llega a /x → Lee la landing page → Hace clic en "Aceptar Términos e Iniciar Registro"
                                           → ❌ BOTÓN ESTÁTICO — no hace nada
```

#### Estado Objetivo (Pendiente de implementar):
```
Usuario llega a /x
  → Lee la landing page (secciones 3.3.2 a 3.3.7)
  → Clic en "Entrar al Simulador Interactivo" → Accede a TDTM en modo demo (sin cuenta)
  → Clic en "Aceptar Términos e Iniciar Registro"
    → Se muestra modal/página de Términos y NDA
    → El usuario acepta
    → Formulario de registro (nombre, email, teléfono, empresa referidora)
    → El backend crea registro en tabla `usuarios` (rol: Partner) + tabla `partners`
    → El usuario recibe email de bienvenida + acceso a /mtx1
```

#### Pendientes Críticos para el Flujo de Sign-Up

| # | Pendiente | Archivo a Crear |
| :--- | :--- | :--- |
| 1 | Modal o página de Términos & NDA | `PARTNERS/terminos.php` o modal en `x/code.html` |
| 2 | Formulario de registro | `api/register.php` (endpoint backend) |
| 3 | Integración con BD (tabla `usuarios` + `partners`) | Schema pendiente de autorización (Mandamiento 9) |
| 4 | Email de bienvenida | `CORE/src/Mailer.php` (pendiente de crear) |
| 5 | Simulador Interactivo | `/z/` en modo demo (sin auth) |
| 6 | Enlace del botón "Onboarding" en nav | `href` apuntando al formulario |

### 3.5 Copy Oficial — Terminología Validada

Los siguientes términos aparecen en `x/code.html` y son los canónicos para la landing:

| Concepto | Término Canónico (Landing) |
| :--- | :--- |
| Rol del Partner | "Arquitecta/o de Confianza Operativa" |
| Asistente IA | "Copiloto IA" (en landing) / "Copiloto Táctico" (en copy fuente) |
| Modelo sin fricción | "Fricción Cero" |
| El sistema | "The Deep Tech Matrix" / "Sistema Operativo Cognitivo" |
| El proceso de diagnóstico | "Radiografía" |
| Los 3 pasos | Ingreso → Radiografía → Entrega |

#### ⚠️ Término a Corregir en `x/COPY_NoteBookLM.txt`

```
ACTUAL (línea 28):   "no necesitas...experiencia en ventas agresivas"
CORRECTO:            "no necesitas...operar bajo modelos de venta transaccional"
```

El HTML ya está limpio. Solo el documento fuente del copy tiene el término prohibido.

### 3.6 Archivos del Módulo

| Archivo | Propósito | Estado |
| :--- | :--- | :--- |
| `x/code.html` | Landing Page Partner Academy (HTML estático) | ✅ Activo |
| `x/COPY_NoteBookLM.txt` | Copy fuente para la landing (documento de trabajo) | ⚠️ Un término a corregir |
| `x/DESIGN.md` | Sistema de diseño "Cognitive Infrastructure System" | ✅ Activo (referencia) |
| `x/Info.txt` | Notas de contexto del módulo | Referencia interna |
| `x/screen.png` | Captura de pantalla de referencia visual | Referencia interna |
| `x/stitch_linear_steel_design_system.zip` | Assets del design system (Stitch/Linear) | Referencia interna |

---

## 4. MÓDULOS IA Y ADMINISTRACIÓN — PANEL SUPER ADMIN V2

*(Origen: `modulos_IA_instalar.md` — Reporte Forense Exhaustivo del Panel Super Admin V2)*

> Este reporte documenta los módulos administrables de IA, pagos y auditoría disponibles en el Panel Super Admin V2 — la capa de configuración operativa que sostiene al AIService y a los módulos de cara al Partner descritos en las Secciones 1-3.

### 4.0 Arquitectura de Autenticación

El panel Super Admin usa **Passport Bearer token** (`?access_token=` en la URL), distinto del **bridge-token de 60 s** que usan las demás SPAs V2.

- El boot de `security.js` limpia el token de la URL vía `history.replaceState()` inmediatamente tras capturarlo — nunca queda en el historial del navegador.
- Cada llamada posterior usa `apiFetch()`, que inyecta `Authorization: Bearer <token>` en todos los headers automáticamente.
- Un `401` de respuesta lanza excepción directa y muestra el error en pantalla sin ciclos.

---

### 4.1 MÓDULO A — Administradores

**¿Qué hace cada botón?**

| Botón | Flujo JS | Endpoint |
| :--- | :--- | :--- |
| Toggle rol (Promover / Degradar) | `execToggleRole(userId)` → confirmación inline | `POST /api/v2/admin/users/{id}/toggle-role` |
| Reset pwd | Abre `#sa-modal` con email + advertencia → `execResetPassword()` | `POST /api/v2/admin/users/{id}/reset-password` |
| Copiar contraseña | `navigator.clipboard.writeText()` del bloque `#password-result` | — |

**Validaciones JS antes de enviar:**
- `openModal()` requiere `state.pendingReset !== null`; el confirm cierra el modal ANTES de llamar al backend (evita doble click)
- No hay validación de campo extra: el backend genera la contraseña aleatoria en formato `XXXX-XXXX-xxxx-9999`

**Seguridad backend notable:**
- `toggleRole()` rechaza al propio actor (`$request->user()->id === $userId`)
- `resetPassword()` devuelve `temporary_password` en plaintext pero solo en esa respuesta — se guarda en BD como bcrypt, nunca más se puede recuperar
- Ambas acciones escriben audit log antes de retornar

---

### 4.2 MÓDULO B — Orquestador IA

#### Subvista izquierda: Escalera de Failover

`renderLadder()` filtra `settings` por `is_active === true`, ordena por `priority_order ASC` y renderiza badges con flechas ↓. Al final siempre añade un nodo estático **FALLBACK** que informa: *"Si todos fallan → RuntimeException (log del sistema)"* — esto es informativo, no interactivo.

#### Tabla de proveedores

Cada fila muestra: badge de proveedor, API key enmascarada (`••••••••XXXX` — viene del backend, nunca la real), prioridad, toggle activo/inactivo, scope (Global/`#company_id`), estado del último test, y tres botones: Test, Editar, Eliminar.

#### Botón ⚡ Test (Ping Forense)

**Flujo completo:**
1. JS deshabilita el botón, muestra "⏳ Probando…"
2. `POST /api/v2/admin/ai-settings/{id}/test`
3. Backend (`testAiSetting()`): instancia el adaptador directo del proveedor (NO pasa por AIService con failover), envía payload de diagnóstico: `"Ping. Reply strictly with the single word: pong"` con `max_tokens: 5, temperature: 0`
4. Persiste en `ai_settings`: `last_tested_at`, `last_test_status` (ok/error/failed), `last_test_latency_ms`, `last_test_error` (sanitizado)
5. Escribe audit log `TEST_AI_SETTING` con status (`test_ok`/`test_error`/`test_failed`)
6. JS recibe la respuesta y actualiza la celda "Último Test" inline (sin recargar la tabla entera)
7. Abre modal `#ai-ping-modal` con resultado

**Modal de Diagnóstico Forense:**

- Si `isOk`: fondo verde, muestra latencia en ms + fecha completa + respuesta del modelo
- Si error: `classifyApiError()` clasifica el mensaje en 7 categorías con ícono y hint específico:
  - 🔑 API Key inválida (detecta: `invalid api key`, `401`, `api_key_invalid`, `permission_denied`, `api key not valid`, `invalid x-goog`)
  - ⏱ Rate limit / 429
  - 🔒 Error SSL / certificado
  - 🌐 Red / timeout / no route
  - 🔐 Error de desencriptado (APP_KEY cambiada)
  - ⚙️ Adaptador no implementado
  - 💳 Saldo insuficiente / billing

**Validaciones JS del formulario de AI (Crear/Editar):**
1. `provider_name` requerido
2. `api_key` requerido SI es CREATE (en EDIT puede omitirse para no cambiarla)
3. `priority_order`: número >= 1
4. `extra_config`: si no está vacío, `JSON.parse()` válido — si falla muestra error y hace focus al campo
5. Estado del botón: `disabled=true` + texto "Guardando…" hasta recibir respuesta
6. JSON de `extra_config` nunca se envía si está vacío (solo si `extraVal !== ''`)

**Sincronización de hints de modelo por proveedor:**

`syncProviderHints()` actualiza el placeholder y hint del textarea `extra_config` según el proveedor seleccionado:

| Proveedor | Hint / Ejemplo |
| :--- | :--- |
| OpenAI | `{"model":"gpt-4o","max_tokens":1024}` |
| Groq | `{"model":"llama3-8b-8192","max_tokens":1024}` |
| Anthropic | `claude-sonnet-4-6` |
| Gemini | `gemini-1.5-flash-latest` |
| Mistral | `mistral-small-latest` |

**Toggle de estado inline:** `PATCH /api/v2/admin/ai-settings/{id}/toggle` — recarga ambas vistas (escalera + tabla) tras confirmar.

#### Motor de Failover — `AIService.php`

```
dispatch($payload, $company_id):
  1. SELECT ai_settings WHERE is_active=1
     AND (company_id IS NULL OR company_id = $company_id)
     ORDER BY priority_order ASC
  2. Para cada setting en orden:
     a. Resuelve el adapter: ADAPTERS[$provider_name]
     b. Si no existe adaptador → Log::warning + continue (no falla, salta)
     c. $adapter->request($payload, ['api_key' => decryptedKey(), 'extra_config' => ...])
     d. Si result['status'] === 'ok' → return result (éxito inmediato)
     e. Si falla → Log::warning con provider, priority, error, company_id → siguiente
  3. Si ninguno tuvo éxito → throw RuntimeException
```

**Adaptadores registrados:** OpenAI, Groq, Mistral, Gemini. Anthropic está en la UI como opción pero **NO tiene adaptador** en `ADAPTERS` — el AIService lo saltará con warning en log.

**Diferencias críticas de `GeminiProvider`:**
- Auth: API Key en query param `?key=`, NO en header Bearer
- Body: `contents` con `parts` (no `messages` con `content`)
- System prompt: campo separado `systemInstruction` (no rol `system` en messages)
- Rol `"assistant"` → `"model"` en Gemini
- `max_tokens` → `generationConfig.maxOutputTokens`
- JSON mode: `generationConfig.responseMimeType = "application/json"` (no `response_format`)
- Tokens: `usageMetadata.totalTokenCount` (no `usage.total_tokens`)

---

### 4.3 MÓDULO C — Pasarelas de Pago

#### Grid de 4 tarjetas fijas

Siempre muestra las 4 tarjetas (Stripe, OpenPay, PayPal, Nuvei) aunque no haya registro en BD. Si no hay registro, los toggles aparecen `disabled`.

**Toggles de tarjeta:**
- Toggle Activo: `PATCH /api/v2/admin/payment-gateways/{id}/toggle`
- Toggle Sandbox/Producción: `PATCH /api/v2/admin/payment-gateways/{id}/toggle-sandbox`
- En caso de error de API, el toggle se revierte (`cb.checked = !cb.checked`)

#### Botón "Configurar" → Modal de llaves

- Limpia los 3 inputs (`public_key`, `secret_key`, `webhook_secret`) — nunca pre-llena con valores reales
- Si hay credenciales previas, muestra la versión enmascarada (`maskedCredentials()`: `••••••••XXXX`)
- Validación JS: al menos `pubKey` O `secKey` deben estar presentes antes de enviar

**Envío:**
- Si `gid` existe (proveedor ya registrado): `PUT /api/v2/admin/payment-gateways/{id}` con `{credentials}`
- Si no existe: `POST /api/v2/admin/payment-gateways` con `{provider_name, credentials}`

#### Modelo `PaymentGatewaySetting`

- `credentials` está en `$hidden` — nunca se serializa al exterior
- `decryptedCredentials()`: `json_decode(decrypt($this->credentials), true)` — doble capa: JSON dentro de `encrypt()`
- `maskedCredentials()`: arrow function PHP 8 → `str_repeat('•', 8) . substr($v, -4)` sobre cada clave del array

---

### 4.4 MÓDULO D — Gestión de Empresas

#### Tabla con sorting, paginación y búsqueda

- **Sort:** click en `<th class="co-sortable">` → toggle asc/desc → `loadCompanies(1)`
- **Search:** campo + botón buscar o Enter → `?search=` encoded
- **Paginación numérica:** botones 1…N

#### Dropdown de acciones (posicionado)

`coOpenDropdown()` se reconstruye en cada apertura usando `data.active` del objeto JSON guardado en `tr.dataset.companyJson`. Convierte con `Boolean(Number(data.active))` para manejar `"0"`/`"1"` string vs boolean.

| Acción | Flujo |
| :--- | :--- |
| Editar | Abre `#co-edit-modal` con campos: `email, phone, rfc, address, colony, zipcode` (`name` e `id` son readonly) |
| Suspender/Activar | Modal de confirmación → `PATCH /api/v2/admin/companies/{id}/toggle-status` |
| Eliminar | Modal de confirmación → `DELETE /api/v2/admin/companies/{id}` (soft-delete: `active=0`) |

#### Botón "Ajuste Manual de Suscripción" (dentro del modal de edición)

- Pre-pobla con datos actuales de la empresa desde `coDropCtx.data`
- Validaciones JS: `due_date` requerido + `reason` requerido (para el Audit Trail)
- Payload: `{due_date, reason, plan?, payday?}` → `PATCH /api/v2/admin/companies/{id}/subscription-override`
- Backend: inserta factura nueva con `status=2, cost_package=0`; actualiza `company.package` si plan cambió; escribe audit log `subscription_override` con motivo

---

### 4.5 MÓDULO E — Audit Trail

- **Carga:** `GET /api/v2/admin/audit-logs?per_page=50&page={n}` — paginación manual
- **Render de tabla:** columnas `id`, fecha/hora (es-MX), `actor_email`, acción (traducida por `ACTION_LABEL`), empresa (`target_name`), `from_status`, `to_status`

**Mapa de acciones en `ACTION_LABEL`:**

| Dominio | Acciones |
| :--- | :--- |
| Empresas | `activate`, `suspend`, `delete`, `update`, `subscription_override` |
| Usuarios | `toggle_role`, `reset_password` |
| Orquestador | `CREATE`/`UPDATE`/`DELETE`/`TOGGLE`/`TEST_AI_SETTING` |
| Prompts | `UPDATE_AI_PROMPT` |

**Exportación PDF (jsPDF):**
- Requiere `auditState.rows` con datos cargados
- Usa `window.jspdf.jsPDF` (UMD bundle)
- Landscape A4, `doc.autoTable()` con estilos: header azul `[48,119,183]`, filas alternadas
- Archivo: `audit-trail-YYYY-MM-DD.pdf`

**Nota estructural:** `AuditLog` no existe como modelo Eloquent. `writeAuditLog()` en `SuperAdminController` escribe directamente con `DB::table('audit_logs')->insert([...])`. Los campos son: `actor_id, actor_email, action, target_type, target_id, target_name` (max 191), `from_status, to_status` (max 50), `extra` (JSON), `created_at`. Los errores en el insert son capturados silenciosamente — nunca rompen la operación principal.

---

### 4.6 MÓDULO F — Monitor de Tokens IA

- **Filtros de período:** 4 botones (Todo / 90d / 30d / 7d) → `loadTokenStats(period)` → `GET /api/v2/admin/token-stats?period={period}`

**KPI Grid (5 tarjetas):**
1. Tokens totales (con `fmtTokens`: M/K/raw)
2. Mensajes IA procesados
3. Conversaciones abiertas
4. Tokens/mensaje promedio
5. Empresas activas con uso de IA

**Gráfico de barras CSS puro:**
- Top 12 empresas por tokens
- Barras animadas con `requestAnimationFrame` (width 0% → valor real)
- Color degradado HSL: índigo profundo para #1, se aclara hacia abajo
- Badges 🥇🥈🥉 para el top 3

**Exportar CSV:**
- Incluye BOM (`﻿`) para compatibilidad con Excel
- 3 filas de encabezado: título, período, totales globales
- Luego una fila por empresa: rank, ID, nombre, tokens, mensajes, conversaciones, avg, %, última actividad
- Descarga via `URL.createObjectURL()` + `a.click()` + `revokeObjectURL()`

**Backend (`SuperAdminController::tokenStats`):**

```sql
SELECT companies.id, companies.name,
       SUM(ai_messages.tokens_used) AS total_tokens,
       COUNT(ai_messages.id) AS total_messages,
       COUNT(DISTINCT ai_conversations.id) AS total_conversations,
       AVG(ai_messages.tokens_used) AS avg_tokens_per_msg,
       MAX(ai_messages.created_at) AS last_activity
FROM ai_messages
JOIN ai_conversations ON ai_messages.conversation_id = ai_conversations.id
JOIN companies ON ai_conversations.company_id = companies.id
[WHERE ai_messages.created_at >= now() - INTERVAL {period}]
GROUP BY companies.id, companies.name
ORDER BY total_tokens DESC
LIMIT 50
```

Luego calcula `pct_of_total = round((total / sum_total) * 100, 1)`.

---

### 4.7 MÓDULO G — Synaptic Core™ (Prompts Maestros)

#### Tarjetas de módulos

Cada tarjeta muestra: `slug` (badge `<code>`), nombre, modo (🧩 Modular / 📄 Legacy), versión (chip vN), fecha de actualización, preview de 220 chars del `system_role` o `prompt_text`, total de caracteres combinados de `system_role` + `prompt_text` + `immutable_rules`.

#### Modal Synaptic Core™ — 5 sub-tabs

| Tab | Campos |
| :--- | :--- |
| Identidad | `name`, `system_role` (textarea), `preferred_model` |
| Contexto | `business_context`, `tone_profile` (JSON textarea) |
| Reglas | `immutable_rules`, `prompt_text` (legacy fallback) |
| Esquemas | `output_schema` (JSON), `variables_schema` (JSON) |
| Playground | inputs de variables + mensaje test + área compilada + respuesta |

**Validaciones JS antes de guardar (`saveSynapticCore`):**
- `tone_profile`: si no vacío → `JSON.parse()` — error → mensaje + `activateScTab('context')`
- `output_schema`: si no vacío → `JSON.parse()` — error → mensaje + `activateScTab('schemas')`
- `variables_schema`: si no vacío → `JSON.parse()` — error → `activateScTab('schemas')`
- Al menos `system_role` O `prompt_text` con contenido — si ambos vacíos → error + `activateScTab('identity')`
- El tab incorrecto se activa automáticamente cuando falla la validación de su campo.

**Envío:** `PUT /api/v2/admin/ai-prompts/{slug}` con todos los campos. Backend archiva versión anterior en `ai_prompt_versions`, incrementa `version`, retorna `new_version`.

#### Playground (sin guardar)

- Recopila variables de: inputs con `data-var-key` (del schema) + filas manuales con key/value libre
- Parsea JSON fields del formulario actual (estado no guardado) para enviarlos en el test
- `POST /api/v2/admin/ai-prompts/{slug}/test` con payload completo + variables + `test_message`
- Backend crea `AiPrompt` temporal (no persistido), llama `compile($variables)`, llama `$aiService->dispatch()`
- Sin audit log (es operación de solo lectura)
- Respuesta: intenta `JSON.stringify(JSON.parse(raw), null, 2)` — si falla, muestra raw
- Botón "Mostrar/Ocultar prompt compilado" permite ver el system prompt final ensamblado

#### `AiPrompt::compile()` — lógica de ensamblado

```
Si system_role está vacío → modo legacy: devuelve prompt_text con variables inyectadas

Si system_role tiene contenido → ensambla en orden:
  1. system_role (identidad)
  2. "## CONTEXTO DEL NEGOCIO\n" + business_context
  3. "## DIRECTRICES DE TONO\n" + líneas del tone_profile JSON (language, formality, perspective, style[])
  4. "## REGLAS INMUTABLES\n" + immutable_rules
  5. "## ESTRUCTURA DE RESPUESTA OBLIGATORIA (JSON estricto)\n" + json_encode(output_schema)
  → join("\n\n") → injectVariables({{key}} → valor)
```

---

### 4.8 Resumen de Seguridad Transversal (Panel Super Admin V2)

| Mecanismo | Implementación |
| :--- | :--- |
| Auth super admin | Passport Bearer token + `role:super_admin` + `super_admin_company` middleware |
| Limpieza de URL | `history.replaceState()` en boot — token no queda en historial |
| Cifrado API Keys | `encrypt()` / `decrypt()` de Laravel (AES-256-CBC con `APP_KEY`) |
| Enmascarado en frontend | `••••••••XXXX` — backend calcula, nunca el JS |
| Sanitización de errores | `sanitizeErrorMessage()` en backend: regex reemplaza `?key=`, `?api_key=`, `?token=`, `Bearer ...` con `[REDACTED]` |
| Audit log | `DB::table('audit_logs')->insert()` antes de cada operación destructiva; fallo silencioso no bloquea la operación |
| Protección de empresa raíz | `ACADEP_COMPANY_ID` en `.env` — `deleteCompany()` verifica antes de soft-delete |
| Tenant scope | `resolveAllowedCompanyIds()` lee `SUPER_ADMIN_COMPANY_IDS` del `.env` — nunca del payload |
| XSS | `escHtml()` y `escAttr()` en todo el renderizado de datos externos al DOM |

---

*{{PROJECT_NAME}} — Fricción Cero Operativa. Pilar 07: mapa vivo de pantallas, componentes y flujos UX.*
