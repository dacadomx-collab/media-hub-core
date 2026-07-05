# 05 · MATRIZ FINANCIERA Y COMERCIAL — MEDIA HUB

> **Version:** 2.0 (Reescritura total — reemplaza contenido generico no relacionado con el proyecto)
> **Clasificacion:** Unica Fuente de Verdad Financiera
> **Fuente de verdad:** `api/finance.php`, `calls.advance_required_pct`/`advance_paid`/`total_amount` (ver `02_CODEX_Y_SCHEMA_MAESTRO.md`)

---

## 0. NOTA DE CONSOLIDACION

La version anterior de este archivo describia un modelo de negocio de reventa B2B con Partners, comisiones por niveles ("El Roble/El Cedro/El Baobab/La Secuoya"), Cyber Score y cupones de descuento — ninguno de esos conceptos existe en Media HUB. Ese contenido fue **descartado por completo**. Media HUB es un estudio de producción audiovisual que factura directamente a sus Clientes Jornal por llamado (sesión de grabación), no una red de socios comerciales.

---

## 1. MODELO COMERCIAL — FACTURACION POR LLAMADO

Media HUB no vende suscripciones ni tiers de comisión. Cada **llamado** (`calls`) es la unidad de facturación: un cliente contrata una sesión de grabación con un `total_amount` definido, y el estudio cobra un **anticipo obligatorio** antes de confirmar personal y locación.

### 1.1 Protocolo de Anticipo (50% minimo)

Ver el detalle operativo completo en `01_LEY_Y_PROTOCOLOS_DE_VUELO.md` §4. Resumen financiero:

| Regla | Valor |
| :--- | :--- |
| `advance_required_pct` por defecto | `50.00%` |
| Monto de anticipo | `total_amount * (advance_required_pct / 100)` |
| Excepción al porcentaje | Solo `Administrador`/`Lider_Proyecto`, dejando constancia en `calls.notes` |
| Compuerta de asignación de staff | Bloqueada mientras `advance_paid = 0` (`api/agenda.php?action=assign_staff` → `422`) |
| Compuerta de confirmación de llamado | `status = 'Confirmado'` exige `advance_paid = 1` |

**Regla estratégica:** el anticipo no es solo un control administrativo — es la garantía comercial que protege al estudio de cancelaciones de último minuto y asegura la asignación de Van Terrestre/Embarcación Marítima con antelación.

### 1.2 Clientes Jornal — Modelo Recurrente

Media HUB opera con **Clientes Jornal**: clientes con **programas recurrentes** (`programs`), no eventos aislados (ver `00_ADN_Y_FILOSOFIA.md` §7). El valor comercial del cliente se mide por la cadencia de llamados que produce su programa a lo largo del tiempo, no por una venta única.

---

## 2. CENTRO DE COMANDO EJECUTIVO — KPIs FINANCIEROS

Fuente: `api/finance.php?action=kpis` (acceso exclusivo `Administrador`). Ver contrato completo en `03_CONTRATOS_API_Y_RUTAS.md` §Contrato 9.

| KPI | Cálculo | Constante |
| :--- | :--- | :--- |
| **Ingresos mensuales** | `SUM(total_amount)` de llamados `Confirmado`/`Completado` del mes en curso | — |
| **Utilidad neta proyectada** | `ingresos_mensuales * 0.40` | `MH_PROJECTED_PROFIT_MARGIN = 0.40` |
| **IVA acumulado** | `ingresos_mensuales * 0.16` | `MH_IVA_RATE = 0.16` (IVA de ley MX) |
| **Anticipos pendientes por cobrar** | `SUM(total_amount * advance_required_pct / 100)` de llamados no cancelados con `advance_paid = 0` | — |
| **Horas de estudio consumidas** | `SUM(TIMEDIFF(end_time, start_time))` de llamados `Confirmado`/`Completado` del mes, en horas | — |
| **Alertas de flota** | Listado de `fleet_vehicles` con `status = 'Mantenimiento'` | — |

> **Nota de precisión:** la "utilidad neta proyectada" es una simplificación operativa (40% fijo sobre ingresos) — no existe todavía una matriz de costos fijos/variables persistida por tipo de llamado o locación. Ver `08_CHECKLIST_MAESTRO_BACKEND.md` §7 para el trabajo pendiente de refinamiento de este cálculo.

---

## 3. REGLAS DE INMUTABILIDAD FINANCIERA

1. **`calls.total_amount` y `advance_paid` no se editan libremente:** todo cambio de anticipo pasa por `api/agenda.php?action=verify_advance` (solo `Administrador`), nunca por escritura directa a la tabla.
2. **Ningún llamado se factura sin registro en `calls`:** no existen cobros fuera del flujo de Agenda.
3. **Auditoría mínima viable:** cada verificación de anticipo dispara automáticamente el correo "Fecha Confirmada + Personal Reservado" (`mh_mail_call_confirmed()`) al cliente, dejando rastro operativo del momento de confirmación comercial.

> **Pendiente de definición con el Arquitecto (no inventar sin autorización — Mandamiento 9):** un log de auditoría dedicado a cambios financieros (`before_value`/`after_value`/`actor_id`/`timestamp`) para `calls.total_amount` y `advance_paid`, si el volumen de operación lo justifica en fases futuras.

---

## 4. ROLES Y VISIBILIDAD FINANCIERA

| Rol | Acceso a datos financieros |
| :--- | :--- |
| `Administrador` | Total — único rol con acceso a `api/finance.php` y a `verify_advance` |
| `Lider_Proyecto` | Puede ver `advance_paid`/`total_amount` en el contexto de agenda operativa, pero **no** accede al Centro de Comando Ejecutivo (KPIs) |
| `Staff_Tecnico`, `Lider_Logistica` | Sin acceso a datos financieros |
| `Cliente` | Sin acceso al panel — futuro Portal de Cliente Jornal (Fase 3) mostraría únicamente su propia agenda, no cifras internas del estudio |

---

## 5. ROADMAP FINANCIERO (No implementado — requiere autorización del Arquitecto antes de codificarse)

- Matriz de costos fijos/variables por tipo de llamado y locación (Estudio vs. Van Terrestre vs. Embarcación Marítima), para refinar la utilidad neta proyectada más allá del 40% fijo actual.
- Reportes de rentabilidad por Cliente Jornal / programa a lo largo del tiempo.
- Facturación electrónica (CFDI) si el volumen de operación lo requiere — actualmente fuera de alcance de Fase 1/Fase 2.

---

*Este documento describe el modelo comercial real de Media HUB (Fase 1–2). Cualquier concepto financiero nuevo (comisiones, cupones, planes de suscripción) requiere autorización explícita del Arquitecto antes de documentarse aquí — no se copian modelos de negocio de otros proyectos del holding.*
