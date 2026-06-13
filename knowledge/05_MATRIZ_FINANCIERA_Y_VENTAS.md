# 05_MATRIZ_FINANCIERA_Y_VENTAS — {{PROJECT_NAME}}
## Tabuladores, Comisiones, Cyber Score, Cupones y Estrategia PLG
**Versión:** 1.1 | **Fecha de consolidación:** 2026-06-11 | **Clasificación:** ÚNICA FUENTE DE VERDAD FINANCIERA
**Fuentes consolidadas:** 08_MATRIZ_FINANCIERA_Y_OPERATIVA.md, 08_PLG_SIMULATOR_STRATEGY.md, REGLA_AXON_VENTAS.txt
**Fuentes originales (heredadas):** Estructura_Financiera_AXON_Partners (20-May-2026), MANUAL FINANCIERO Y OPERATIVO (18-May-2026), CÓDICE TDTM v5.0 (20-May-2026)

> Toda lógica de precios, comisiones, cotización y retención del ecosistema vive en este documento. Cualquier dato financiero en archivos anteriores queda **descartado y reemplazado** por este documento.

> El Partner no cotiza manualmente. El sistema cierra el negocio por él.

---

## SECCIÓN 1 — GAMIFICACIÓN ORGÁNICA (NIVELES CANÓNICOS)

### 1.1 Filosofía
Erradicamos las medallas infantiles y los puntos abstractos. El estatus de los Partners crece mediante la **retención de clientes (MRR)**, reflejándose en un **Árbol de Evolución Orgánica**. El crecimiento debe sentirse como evolución, estatus, acceso y poder — no solo como un porcentaje.

### 1.2 Jerarquía Oficial — Los 4 Niveles (DATOS CANÓNICOS)

| Tier | Nombre Simbólico | Comisión Base | Requisito de Acceso | Habilidades Desbloqueadas |
| :--- | :--- | :---: | :--- | :--- |
| **T1** | **El Roble** | **[PCT_COMISION_T1]** | Nivel Base — Ingreso al ecosistema | Venta básica, acceso a consola operativa (TDTM) y reportes automatizados |
| **T2** | **El Cedro** | **[PCT_COMISION_T2]** | [N]° clientes activos simultáneos | Acceso a soporte prioritario, crear campañas, cupones limitados |
| **T3** | **El Baobab** | **[PCT_COMISION_T3]** | [N]° clientes activos simultáneos | Capacidad de emitir Cupones Invisibles para cierres estratégicos, ajuste de descuentos, MRR avanzado |
| **T4** | **La Secuoya** | **[PCT_COMISION_T4]** | Operadores Top — métricas élite | White Label bajo marca propia, ajustes de margen libre, branding propio, comisión especial |

> **Nota de plantilla:** `[PCT_COMISION_T1]`...`[PCT_COMISION_T4]` y los requisitos numéricos de acceso (`[N]°`) son valores de configuración propios de cada proyecto, definidos por el Arquitecto/CFO al instanciar este pilar. La progresión debe mantenerse estrictamente creciente entre tiers (T1 < T2 < T3 < T4).
>
> ⛔ **DATOS DESCARTADOS:** Cualquier tabulador de comisiones previo o borrador queda descartado. Los únicos porcentajes válidos son los definidos en esta tabla para el proyecto activo.

### 1.3 Descripción Filosófica de Cada Nivel

- **El Roble** — Fundamentos sólidos. Estabilidad, raíces profundas. El Partner establece sus bases operativas.
- **El Cedro** — Etapa de expansión. Resiliencia y recompensas en herramientas comerciales avanzadas.
- **El Baobab** — Consolidación de ingresos recurrentes. Abundancia y poder de negociación con Cupones Invisibles.
- **La Secuoya** — Nivel Élite. Control absoluto. Capacidad de operar ecosistemas en modalidad White Label bajo marca propia.

### 1.4 Variables de Evolución (Ponderación del Sistema)

| Métrica | Peso |
| :--- | :--- |
| Revenue generado | Alto |
| MRR retenido | **Muy alto** |
| Churn bajo | **Muy alto** |
| Tiempo promedio de cierre | Medio |
| Satisfacción de clientes | Alto |

**Regla estratégica:** NO premiar solo ventas. Premiar permanencia, renovaciones, estabilidad y calidad comercial.

### 1.5 Restricción de Visibilidad
El Partner **NO puede ver** las funciones de niveles superiores bloqueados de forma completa. Solo accede a previews estratégicos. Esto genera aspiración, curiosidad y motivación de ascenso.

---

## SECCIÓN 2 — TABULADOR DE SOLUCIONES TECNOLÓGICAS

El Partner recibe su porcentaje de comisión tanto en el pago inicial (Setup) como en la iguala mensual (MRR).

| Tipo de Solución / Diagnóstico | Inversión Setup (`{{CURRENCY}}`) | Póliza Mensual (MRR) |
| :--- | :---: | :---: |
| **Plan Básico de Protección** (Cyber Score: 70–89) | `[PRECIO_SETUP_PLAN_BASICO]` | Opcional: `[PRECIO_MRR_PLAN_BASICO]` / mes |
| **Plan Seguridad ORO** (Cyber Score: 0–69) — Recomendado | `[PRECIO_SETUP_PLAN_ORO_MIN]` – `[PRECIO_SETUP_PLAN_ORO_MAX]` | Sujeto a Póliza Sentinel: `[PRECIO_MRR_PLAN_ORO]` / mes |
| **Póliza Sentinel** (Defensa Activa Permanente) | N/A | `[PRECIO_MRR_POLIZA_SENTINEL]` / mes |

> **Nota de plantilla:** Todos los valores `[PRECIO_*]` se definen en `{{CURRENCY}}` (moneda local del proyecto) por el Arquitecto/CFO al instanciar este pilar. La relación Setup ORO > Setup Básico y MRR ORO > MRR Básico > MRR Sentinel debe preservarse.

**Regla comercial:** La Póliza Sentinel siempre debe incluirse como opción en toda propuesta comercial.

---

### 2.1 Cotización Dinámica por Cyber Score — Comando `AXON_COTIZA` (consolidado)

Esta es la lógica **activa** de cotización ejecutada por el GEM (Núcleo de Operaciones Técnicas y Comerciales) ante el comando **`AXON_COTIZA` + [reporte]**, conforme a `REGLA_AXON_VENTAS.txt` y ratificada en `CLAUDE.md` §2.B. El GEM lee el campo `"SCORE: X/100"` al final del reporte del Scanner para armar la cotización:

| Rango de Cyber Score | Diagnóstico | Acción Comercial / Costo |
| :--- | :--- | :--- |
| **90 – 100** (Blindaje Oro) | Cliente en excelente estado perimetral | Felicitar al cliente y ofrecer la **Póliza Sentinel** por **`[PRECIO_MRR_POLIZA_SENTINEL]` `{{CURRENCY}}`/mes** para mantenimiento continuo |
| **70 – 89** (Riesgo Medio) | Vulnerabilidades menores detectadas | Sugerir el **Plan Básico**. Costo estimado: **`[PRECIO_SETUP_PLAN_BASICO]` `{{CURRENCY}}`** |
| **0 – 69** (Alto Riesgo a Crítico) | Exposición crítica de infraestructura | Sugerir el **Plan Seguridad ORO**. Costo estimado: **`[PRECIO_SETUP_PLAN_ORO_MIN]` – `[PRECIO_SETUP_PLAN_ORO_MAX]` `{{CURRENCY}}`** |

**Regla comercial obligatoria:** Siempre incluir la opción de **"Póliza Sentinel"** en la propuesta comercial, sin importar el rango de score (upsell de defensa activa: Troll Mode / Bcrypt).

> **Nota de reconciliación:** Esta tabla de cotización dinámica (basada en hallazgos en vivo del Scanner) opera junto con el Tabulador de Soluciones Tecnológicas de la Sección 2 (tabulador de referencia de Setup/MRR por tipo de plan). Ambas tablas son vigentes; la Sección 2.1 gobierna la **cotización conversacional inmediata** del comando `AXON_COTIZA`, mientras que la Sección 2 gobierna el **tabulador de referencia** para propuestas formales TDTM. Cualquier discrepancia numérica entre ambas debe resolverse con el Arquitecto antes de unificarse (Mandamiento 9 — Inmutabilidad del Sistema).

### 2.1.1 Copy Comercial Persuasivo por Vulnerabilidad Detectada (consolidado)

El GEM utiliza el siguiente copy persuasivo, tono Executive Calm, al reportar hallazgos específicos del Scanner dentro de la cotización:

| Hallazgo del Scanner | Copy Comercial a Utilizar |
| :--- | :--- |
| Puertos abiertos (ej. 3306, 22, 21) | **"Exposición Crítica de Infraestructura"** — *"Su panel de control y accesos directos al servidor están expuestos al internet público, permitiendo intentos de intrusión directos."* |
| Falta de WAF / Fingerprinting expuesto | **"Ausencia de Escudos Perimetrales"** — *"El servidor expone públicamente la tecnología que utiliza y carece de un cortafuegos activo, facilitando ataques dirigidos y automatizados."* |

---

### 2.2 Desglose de Entregables Canónicos - Póliza Sentinel

La Póliza Sentinel se sostiene sobre 4 pilares operativos que justifican el MRR ante el cliente y habilitan al AI Deal Assistant a calcular retenciones y upsells de forma determinística.

| Pilar | Entregable | Descripción |
| :--- | :--- | :--- |
| **1. Telemetría Perimetral** | Monitoreo continuo vía The Scanner / The Sentinel | Vigilancia activa de cabeceras, SSL, puertos críticos y exposición WordPress, con alertas automáticas ante degradación del Cyber Score. |
| **2. Evolución Modular** | Retención escalonada en upgrades de módulos | **`[PCT_RETENCION_MODULO_SECUENCIAL]` OFF** en la activación **secuencial** de nuevos módulos (el cliente ya activo en Sentinel adquiere el siguiente módulo en el orden de roadmap). **`[PCT_RETENCION_MODULO_ANTICIPADO]` OFF** en la activación **anticipada** (el cliente adquiere un módulo fuera de secuencia, antes de su turno de roadmap). |
| **3. SEO Predictivo Autónomo** | Operado vía **AURA Nivel 2** | Análisis predictivo de posicionamiento y oportunidades SEO, ejecutado de forma autónoma por el motor cognitivo AURA, sin intervención manual del Partner. |
| **4. Bolsa de Soporte de Velocidad Crítica** | Disponible solo con Cyber Score **+90** | Bolsa de horas de soporte prioritario reservada para clientes que mantienen el score perimetral por encima de 90, garantizando atención de incidentes en ventana crítica. |

**Regla de cálculo:** Los porcentajes de retención (`retencion_modulo_secuencial_pct` y `retencion_modulo_anticipado_pct`) son leídos por el AI Deal Assistant desde el Codex (`02_CODEX_Y_SCHEMA_MAESTRO.md`) y nunca deben hardcodearse en frontend o backend.

---

## SECCIÓN 3 — REGLAS DE INMUTABILIDAD FINANCIERA

### 3.1 Principio Fundamental
Las cotizaciones son **inmutables**. Ninguna cotización se sobrescribe, edita ni elimina lógicamente. Toda modificación genera una nueva versión con trazabilidad completa.

### 3.2 Protocolo de Modificación (Versionado Obligatorio)
Toda modificación genera:
- Nueva versión (`v1`, `v2`, `v3`…)
- Nuevo hash criptográfico (`financial_hash`)
- Nuevo timestamp exacto
- Nuevo registro en el log de auditoría

### 3.3 Estructura de Cotización (Schema Requerido en BD)

| Campo | Descripción |
| :--- | :--- |
| `quote_id` | ID maestro (inmutable) |
| `version` | v1, v2, v3… (auto-incremental) |
| `created_by` | ID del usuario generador |
| `approved_by` | ID del supervisor autorizador |
| `timestamp` | Fecha y hora exacta (UTC) |
| `financial_hash` | Hash SHA interno de integridad |
| `status` | `Draft` / `Sent` / `Approved` / `Paid` |

### 3.4 Congelamiento Post-Pago
Una vez confirmado el pago, el sistema bloquea **de forma permanente e irreversible**:
- Edición de precios
- Aplicación de descuentos
- Modificación de comisiones
- Cambio de impuestos
- Uso de cupones

### 3.5 Log de Auditoría (Datos Obligatorios por Evento)

| Campo | Descripción |
| :--- | :--- |
| `actor_id` | Usuario que ejecuta la acción |
| `IP` | Dirección IP de origen |
| `device` | Navegador y dispositivo |
| `before_value` | Valor anterior |
| `after_value` | Nuevo valor |
| `timestamp` | Fecha y hora exacta |
| `reason` | Motivo documentado |

### 3.6 Eventos que Generan Log Obligatorio

| Evento | Registrar |
| :--- | :---: |
| Cambio de precio | ✅ |
| Cambio de comisión | ✅ |
| Aplicación de cupón | ✅ |
| Cambio de status | ✅ |
| Reembolso | ✅ |
| Cancelación | ✅ |

---

## SECCIÓN 4 — SISTEMA DE DOBLE APROBACIÓN (ANTI-ABUSO)

### 4.1 Objetivo
Evitar destrucción de margen o abuso comercial por parte de Partners.

### 4.2 Jerarquía de Aprobaciones

| Nivel de Descuento | Rol Requerido para Aprobar |
| :--- | :--- |
| 0% – 10% | Automático (sin intervención humana) |
| 11% – 20% | Supervisor |
| 21% – 35% | Director Comercial |
| +35% | **CFO Only** |

### 4.3 Validaciones Técnicas Obligatorias
El sistema **NO debe permitir jamás**:
- Autoaprobaciones (el mismo usuario no puede aprobarse a sí mismo)
- Aprobación retroactiva
- Modificación posterior a la aprobación
- Bypass manual de cualquier nivel
- Manipulación vía API directa

Toda aprobación debe generar: token interno + hash + registro de sesión + timestamp + snapshot financiero completo.

---

## SECCIÓN 5 — PRICE FLOOR ENGINE (MOTOR DE PISOS FINANCIEROS)

### 5.1 Objetivo
Impedir que los Partners destruyan el margen, vendan por debajo del costo operativo o apliquen descuentos irracionales.

### 5.2 Variables del Cálculo

| Variable | Descripción |
| :--- | :--- |
| `base_cost` | Costo real del producto/servicio |
| `infra_cost` | Costo de servidor e infraestructura |
| `ai_cost` | Consumo de tokens / IA externa (OpenAI, Claude) |
| `labor_cost` | Costo operativo humano |
| `risk_multiplier` | Factor de complejidad técnica del cliente |
| `margin_min` | Margen mínimo corporativo (definido por CFO) |
| `emergency_multiplier` | Factor de urgencia para casos críticos |

### 5.3 Fórmula Conceptual — Precio Mínimo Permitido

```
Precio Mínimo = (base_cost + infra_cost + ai_cost + labor_cost)
                × risk_multiplier
                + margin_min
```

### 5.4 Reglas de Bloqueo Automático
El sistema impide:
- Precios negativos
- Margen por debajo del `margin_min` global
- Descuentos acumulativos que erosionen el margen
- Stacking (acumulación) de cupones

### 5.5 Modo Excepción (Pérdida Estratégica)
Solo pueden romper el floor de forma manual:
- CFO
- Founder / Director General
- Finance Director

Toda excepción debe documentarse con motivo en el log de auditoría.

---

## SECCIÓN 6 — CUPONES ESTRATÉGICOS

### 6.1 Filosofía
Los cupones **NO son descuentos**. Son **eventos psicológicos de conversión**. Su diseño responde a la psicología de ventas B2B, no a promociones de retail.

---

### 6.2 TIPO A — Cupones de Urgencia

**Objetivo:** Crear presión temporal controlada que acelere la decisión del prospecto.

**Variables configurables:**

| Parámetro | Configurable |
| :--- | :---: |
| Duración | ✅ |
| Countdown visible | ✅ |
| Límite de usos | ✅ |
| Geolocalización | ✅ |
| Industria objetivo | ✅ |

**UX Requerida:** Badge rojo premium + timer dinámico en cuenta regresiva + texto "Expira pronto" + sensación de exclusividad.

---

### 6.3 TIPO B — Cupones de Riesgo

**Objetivo:** Descuento contextual auto-activado por la IA cuando The Scanner detecta una vulnerabilidad real.

**Triggers automáticos:**
- SSL inválido o expirado
- WordPress con versión vulnerable
- Puertos críticos expuestos (22, 21, 3306)
- Malware o reputación comprometida
- Cyber Score < 45 (alto riesgo)

**Acción automática:** La IA genera cupón contextual + mensaje emocional que conecta la vulnerabilidad con la pérdida potencial + urgencia automatizada.

**Ejemplo de mensaje:**
> "Tu infraestructura presenta exposición crítica a suplantación DNS. Activa Blindaje ORO hoy con `[PCT_DESCUENTO_CUPON_RIESGO]` OFF antes de que el próximo ciclo de escaneo masivo te encuentre."

---

### 6.4 TIPO C — Cupones Invisibles

**Objetivo:** Empoderar al Partner para aparentar una "gestión comercial exclusiva" sin revelar al cliente que existe un descuento.

**Principio:**
- El **cliente NO visualiza** el descuento ni conoce la existencia del cupón.
- El **Partner aparenta** tener capacidad de "conseguir un trato especial".
- Resultado: incremento de confianza y autoridad percibida del Partner.

**Flujo técnico:**
1. Partner genera propuesta → el AI Deal Assistant evalúa el deal
2. El sistema evalúa: margen disponible, riesgo del deal, historial del Partner, tier actual
3. La IA recomienda el descuento invisible óptimo
4. El sistema sube el precio base visual y aplica el ajuste oculto internamente
5. El cliente recibe el precio "especial" sin ver el cupón
6. El margen mínimo se preserva automáticamente

**Restricciones:**

| Restricción | Estado |
| :--- | :---: |
| Stacking de cupones invisibles | ❌ Bloqueado |
| Romper el Price Floor | ❌ Bloqueado |
| Abuso por frecuencia excesiva | Monitoreado por IA |

**Desbloqueo:** Solo disponible a partir del nivel **El Baobab (T3)**.

---

### 6.5 TIPO D — Cupones de Recuperación

**Objetivo:** Reconectar automáticamente con leads abandonados.

**Trigger:** Lead sin actividad después de tiempo configurable.

**Canales:** Email automatizado / WhatsApp / Notificación push.

**Variables configurables:** Tiempo de abandono trigger, porcentaje de descuento, fecha de expiración, canal de entrega.

---

## SECCIÓN 7 — ANCHOR PRICING (PSICOLOGÍA DE ANCLAJE)

### 7.1 Principio
La IA contrasta el **enorme riesgo de un colapso empresarial** frente a una **inversión estructural mínima**. El cliente percibe: *"Es más caro NO protegerme."*

### 7.2 Descuento Fundacional {{PROJECT_NAME}}

| Elemento | Valor |
| :--- | :--- |
| **Descuento Fundacional** | **`[PCT_DESCUENTO_FUNDACIONAL]`** sobre el precio "retail" |
| Aplicación | Automática en todas las propuestas TDTM |
| Visibilidad | El cliente SÍ ve el contraste (táctica deliberada de anclaje) |
| Objetivo | Efecto Anchor Pricing + urgencia psicológica inmediata |

### 7.3 Gap Fear Trigger (Conversión Nivel 2 → Nivel 3)

Después de completar la implementación y cerrar vulnerabilidades detectadas, el sistema activa:

> *"La infraestructura fue blindada exitosamente. Sin monitoreo continuo, nuevas amenazas podrían reaparecer en las próximas 72 horas."*

Acompañado de: mapa de amenazas globales, ataques recientes a empresas similares, score de riesgo vivo actualizándose, intentos bloqueados en el último período.

---

## SECCIÓN 8 — AI DEAL ASSISTANT

### 8.1 Funciones del Motor IA

| Función | Descripción |
| :--- | :--- |
| **Sugerencia de Descuento** | Recomienda el descuento óptimo para maximizar probabilidad de cierre sin destruir margen |
| **Alerta de Margen** | Advierte en tiempo real sobre erosión de margen o abuso de descuentos |
| **Predicción de Cierre** | Score + probabilidad + nivel de confianza de la IA |
| **Predicción de Churn** | Detecta riesgo de cancelación y baja actividad en clientes activos |
| **Motor de Upsell** | Sugiere upgrades premium, Sentinel mensual y auditorías periódicas |

### 8.2 Variables Analizadas por la IA

| Variable | Uso |
| :--- | :--- |
| Industria del prospecto | Cálculo de riesgo base |
| Tamaño de la empresa | Estimación del ticket promedio |
| Historial de cierres del Partner | Predicción de éxito |
| Sensibilidad al precio | Calibración del descuento |
| Riesgo de churn estimado | Estrategia de retención |

### 8.3 Restricciones Absolutas del Motor IA
La IA **NUNCA debe**:
- Romper el Price Floor establecido
- Otorgar descuentos fuera de los permisos del tier
- Alterar comisiones establecidas
- Modificar historial financiero

Toda decisión de la IA debe: registrarse, versionarse, auditarse y tener trazabilidad completa.

---

## SECCIÓN 9 — KPIs DEL CFO MODE

### 9.1 KPIs Financieros

| KPI | Objetivo |
| :--- | :--- |
| MRR Total | Crecimiento recurrente |
| ARR Proyectado | Escalabilidad |
| Gross Margin | Rentabilidad bruta |
| Net Margin | Salud financiera |
| CAC | Eficiencia comercial |
| LTV | Valor del cliente a largo plazo |

### 9.2 KPIs de Conversión

| KPI | Objetivo |
| :--- | :--- |
| L1 → L2 | Conversión inicial (Lead a Diagnóstico) |
| L2 → L3 | Conversión a MRR |
| Win Rate por Partner | Tasa de cierre individual |
| Avg Deal Size | Ticket promedio |

### 9.3 KPIs de Riesgo

| KPI | Objetivo |
| :--- | :--- |
| Discount Abuse Rate | Detección de fraude comercial |
| Margin Erosion Index | Protección de rentabilidad |
| Churn Risk Score | Riesgo MRR mensual |
| Refund Rate | Calidad del proceso de venta |

### 9.4 KPIs de Partners

| KPI | Objetivo |
| :--- | :--- |
| Revenue generado | Producción individual |
| MRR retenido | Calidad de la cartera |
| Churn individual | Riesgo por socio |
| Tiempo promedio de cierre | Eficiencia operativa |
| Uso de cupones | Disciplina comercial |

---

## SECCIÓN 10 — BÓVEDA FINANCIERA DEL PARTNER (WALLET)

### 10.1 Principios Operativos
- **Visibilidad Absoluta:** El Partner ve en tiempo real: proyectos cerrados, estado de cobranza, saldo disponible.
- **Retiros sin Fricción:** Las comisiones se liberan en la bóveda virtual cuando el cliente liquida el Setup o la iguala. El Partner retira con un solo clic.
- **Ingreso Residual:** Mientras el cliente mantenga activa la Póliza Sentinel o AI Business Operators, el Partner cobra su porcentaje mes a mes.

### 10.2 Métricas Visibles en el Dashboard

| KPI | Significado |
| :--- | :--- |
| MRR Activo | Ingresos recurrentes vivos este mes |
| MRR Retenido | Estabilidad de la cartera |
| Ingresos Pasivos | Total de pólizas activas generando comisión |
| Renovaciones | Calidad comercial |
| Riesgo de Churn | Alertas de clientes en riesgo |

### 10.3 Alertas Automáticas
- Pólizas próximas a vencer (7 días antes)
- Clientes con baja actividad
- Caída de engagement detectada
- Riesgo de churn por IA

---

## SECCIÓN 11 — LEYES DE INGENIERÍA FINANCIERA (Del CÓDICE v5.0)

Estas leyes tienen el mismo peso que los 18 Mandamientos para todo lo relacionado con el módulo financiero.

| Ley | Descripción |
| :--- | :--- |
| **Inmutabilidad de BD** | Cotizaciones y operaciones NUNCA se sobrescriben. Cualquier ajuste genera nuevo hash criptográfico, nueva versión y log. |
| **Esquema Proxy/Puente** | La única pasarela admitida es `/api/`. Las API Keys y prompts maestros nunca tocan repositorios públicos. |
| **ARF-Grid obligatorio** | Todos los componentes financieros usan flexbox y clases utilitarias. Prohibido `width` fijo en px e `!important`. |
| **Price Floor inviolable** | Ningún Partner, sistema o IA puede vender bajo el Price Floor sin autorización explícita del CFO/Founder. |
| **Trazabilidad total** | Toda decisión financiera — humana o de IA — se registra, versiona y audita sin excepción. |

---

## SECCIÓN 12 — ESTRATEGIA PLG SIMULATOR (Product-Led Growth Cinematográfico)

> *"No vendemos software. Le damos acceso temporal a su futuro."*

### 12.1 Concepto Central

En lugar de un formulario de registro frío, {{PROJECT_NAME}} ofrece un **Executive Access Pass con Simulador Interactivo**. El prospecto no "se registra" — *es admitido* a una instancia real del sistema con credenciales temporales protegidas.

El framing cambia todo:
- ❌ "Regístrate gratis" → aburrido, commoditizado
- ✅ "Ingresa al Matrix" → exclusivo, cinematográfico, memorable

La tecnología es la demostración. El prospecto no lee features — los **experimenta**.

**Estado Operativo:** Fase 1 y 2 COMPLETADAS · Fase 3 PENDIENTE (ver §12.4).

### 12.2 Los 4 Estados Psicológicos del Recorrido

#### Estado 1 — CURIOSIDAD (La Puerta de Entrada)
**Trigger:** Botón "INGRESAR AL MATRIX" en `x/index.html` (sección #simulador) y en el CTA final (#cta).
**URL:** `https://dcd.{{PRODUCTION_DOMAIN}}/?auth=required&demo=true`
**Mecanismo:** El prospecto llega a la Landing Page de {{PROJECT_NAME}}. El modal de login se abre automáticamente. El título cambia de "The Deep Tech Matrix" a "Estableciendo canal seguro...". Las credenciales se auto-rellenan de forma animada (email a los 450ms, contraseña a los 900ms). El botón dice "INGRESAR AL MATRIX".

**Efecto psicológico:** Tensión positiva. El sistema parece inteligente y consciente de su llegada. El prospecto no teclea nada — el sistema ya lo está esperando.

#### Estado 2 — ASOMBRO (El Aha Moment)
**Trigger:** Login exitoso con `demo@{{PRODUCTION_DOMAIN}}` / `[PASSWORD_DEMO]` → redirección a `/mtx1`.
**El Aha Moment:** El Copiloto IA (dashboard) lo saluda por nombre: *"Buenos días, Arquitecto Demo. Detecté X oportunidades de alto valor en tu radar..."*

**Efecto psicológico:** Sensación inmediata de que el sistema es **vivo, personalizado y poderoso**. El prospecto entiende el producto sin que nadie se lo explique. Esto es Product-Led Growth en su forma más pura.

> **NOTA PENDIENTE — Fase 3:** El saludo del Copiloto usa `nombre.split(" ")[0]` desde la cookie `axon_user`. Para el usuario Demo, el nombre será "Arquitecto". El copy del dashboard deberá tener un estado especial para roles `demo` o simplemente el nombre funciona naturalmente.

#### Estado 3 — PODER (La Experiencia del Control)
**Trigger:** El prospecto navega el dashboard con datos demo precargados.
**Contenido planeado:** KPIs con números reales pero de ejemplo, el scanner perimetral AXON en acción sobre un dominio de demostración, la bitácora de actividad mostrando acciones del "Arquitecto Demo".

**Efecto psicológico:** El prospecto experimenta la sensación de **tener control** sobre información de alto valor. No es un tour guiado — es libertad dentro de un sistema que impresiona.

> **NOTA PENDIENTE — Fase 3:** Definir qué datos demo se precargan. Opciones: (a) datos ficticios hardcodeados para rol `demo`, (b) datos reales anonimizados, (c) scanner en vivo sobre un dominio público de ejemplo.

#### Estado 4 — PERTENENCIA (El Cierre)
**Trigger:** Al final del recorrido demo, el sistema detecta que el usuario está en modo simulación y muestra un **Muro de Conversión**.

**El Muro de Conversión:**
Un overlay o página especial que dice algo como:
> *"Has explorado el 20% del sistema. Tu instancia demo expirará en 48 horas. ¿Listo para operar con datos reales de tus clientes?"*
> → CTA: **Solicitar mi Access Pass oficial** (enlace a `x/access-pass.html`)

**Efecto psicológico:** El prospecto ya se siente parte del sistema. El muro no bloquea — **invita**. La conversión no es una decisión de compra sino una decisión de pertenencia a un círculo exclusivo.

> **NOTA PENDIENTE — Fase 3:** Implementar la lógica de detección de rol `demo` para mostrar el Muro de Conversión. Candidato: un componente `<DemoWall>` en el dashboard `/mtx1` que se activa si `rol === 'partner'` Y `email === 'demo@{{PRODUCTION_DOMAIN}}'` (o un campo `is_demo` en la tabla `usuarios`).

### 12.3 Arquitectura Técnica Implementada (Fases 1 y 2)

#### Fase 1 — La Puerta de Entrada ✅ COMPLETADA

| Componente | Archivo | Estado |
|:---|:---|:---|
| Usuario Demo (DB) | `usuarios` tabla — ver SQL abajo | ✅ Listo para ejecutar |
| Detección de params URL | `z/components/navbar.tsx` | ✅ Implementado |
| Auto-fill animado | `z/components/navbar.tsx` | ✅ Implementado |
| Modal title: "Estableciendo canal seguro..." | `z/components/navbar.tsx` | ✅ Implementado |
| Botón: "INGRESAR AL MATRIX" | `z/components/navbar.tsx` | ✅ Implementado |
| Badge "Modo Simulación Activo" | `z/components/navbar.tsx` | ✅ Implementado |

**SQL del usuario demo:**
```sql
INSERT INTO usuarios (nombre, email, password_hash, rol, estatus)
VALUES (
  'Arquitecto Demo',
  'demo@{{PRODUCTION_DOMAIN}}',
  '[HASH_BCRYPT_GENERADO]',
  'partner',
  'activo'
);
```
> Contraseña plana: `[PASSWORD_DEMO]` · Hash BCrypt cost=12 generado el 2026-05-26.
> Columna `nivel_riesgo` NO incluida — es un valor calculado por código (`nivelRiesgo(created_at)`), no una columna física de la tabla.

#### Fase 2 — Partner Academy Landing ✅ COMPLETADA

| Componente | Archivo | Estado |
|:---|:---|:---|
| Sección Bloomberg Terminal | `x/index.html` #simulador | ✅ Implementado |
| CTA "INGRESAR AL MATRIX" | `x/index.html` #cta | ✅ Conectado |
| URL correcta con params demo | ambos botones | ✅ `?auth=required&demo=true` |

### 12.4 Fase 3 — El Recorrido Interno ⚠️ PENDIENTE

**Dependencias bloqueantes:**
1. **Motor IA** (`CORE/`) — el Copiloto IA debe estar funcional para el Aha Moment
2. **The Scanner** (`SECURITY/GEM/`) — integración con el dashboard demo
3. **Datos demo precargados** — definir estrategia de contenido demo

**Tareas pendientes de Fase 3:**
- [ ] Definir datos demo para el dashboard (KPIs, leads, cyber_score de ejemplo)
- [ ] Implementar detección de `rol demo` en `/mtx1` para personalizar la experiencia
- [ ] Crear el componente `DemoWall` — Muro de Conversión al final del recorrido
- [ ] Conectar scanner con un dominio de demostración predefinido
- [ ] Configurar expiración de sesión demo (48h o N visitas)
- [ ] A/B test: muro al final del recorrido vs. muro al superar X acciones

**Criterio de inicio de Fase 3:** Cuando los endpoints de IA (`/CORE/`) y scanner (`/SECURITY/GEM/api_scan.php`) estén integrados con el dashboard `/mtx1`.

### 12.5 Métricas de Éxito (Definir baseline post-lanzamiento)

| Métrica | Objetivo |
|:---|:---|
| CTR en botón "Ingresar al Matrix" | > 15% de visitantes de Partner Academy |
| Tasa de login exitoso en modo demo | > 80% de quienes hacen clic |
| Tiempo en sesión demo | > 4 minutos |
| Conversión demo → Access Pass | > 25% |
| Tasa de rechazo del Muro de Conversión | < 40% |

### 12.6 Principio de Diseño de Esta Estrategia

> *"No vendemos software. Le damos acceso temporal a su futuro."*

El simulador no es un tour — es una **promesa cumplida antes de la compra**. Cuando el prospecto llega al Muro de Conversión, ya sabe exactamente lo que obtiene. Su única pregunta ya no es "¿funcionará?" sino "¿cuándo puedo empezar con datos reales?".

Esa es la diferencia entre una demo y un **Executive Access Pass**.

---

## APÉNDICE — TABLA DE REFERENCIA RÁPIDA

### Comisiones por Nivel (Canónico — Vigente desde [FECHA_VIGENCIA])

| Nivel | Comisión | Requisito | Cupones Invisibles | White Label |
| :--- | :---: | :--- | :---: | :---: |
| El Roble | **[PCT_COMISION_T1]** | Nivel base | ❌ | ❌ |
| El Cedro | **[PCT_COMISION_T2]** | [N]° clientes activos | ❌ | ❌ |
| El Baobab | **[PCT_COMISION_T3]** | [N]° clientes activos | ✅ | ❌ |
| La Secuoya | **[PCT_COMISION_T4]** | Métricas élite | ✅ | ✅ |

### Descuento Fundacional

| Concepto | Valor |
| :--- | :--- |
| Descuento Fundacional {{PROJECT_NAME}} | **`[PCT_DESCUENTO_FUNDACIONAL]`** sobre precio retail — automático |

### Jerarquía de Aprobación de Descuentos

| Rango | Aprobador |
| :--- | :--- |
| 0%–10% | Automático |
| 11%–20% | Supervisor |
| 21%–35% | Director Comercial |
| +35% | CFO Only |

### Cotización Dinámica por Cyber Score (comando `AXON_COTIZA`)

| Score | Acción |
| :--- | :--- |
| 90–100 | Póliza Sentinel — `[PRECIO_MRR_POLIZA_SENTINEL]` `{{CURRENCY}}`/mes |
| 70–89 | Plan Básico — `[PRECIO_SETUP_PLAN_BASICO]` `{{CURRENCY}}` |
| 0–69 | Plan Seguridad ORO — `[PRECIO_SETUP_PLAN_ORO_MIN]`–`[PRECIO_SETUP_PLAN_ORO_MAX]` `{{CURRENCY}}` |

### Retención Modular (Póliza Sentinel)

| Variable | Valor |
| :--- | :---: |
| `retencion_modulo_secuencial_pct` | `[PCT_RETENCION_MODULO_SECUENCIAL]` |
| `retencion_modulo_anticipado_pct` | `[PCT_RETENCION_MODULO_ANTICIPADO]` |

---

*{{PROJECT_NAME}} — División de Inteligencia Operativa de {{HOLDING_NAME}}*
*`{{GEOLOCATION_PARAMETER}}` | {{HOLDING_NAME}}*
*Fricción Cero Operativa.*
