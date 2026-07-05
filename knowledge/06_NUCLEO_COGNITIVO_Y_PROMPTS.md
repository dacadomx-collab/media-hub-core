# 06 · NUCLEO COGNITIVO Y PROMPTS — MEDIA HUB

> **Version:** 2.0 (Reescritura total — reemplaza contenido generico no relacionado con el proyecto)
> **Clasificacion:** N/A EN FASE 1–2 — Pilar en reserva
> **Estado:** Media HUB **no integra IA generativa** en su alcance actual (Fase 1 — Estándar Oro, Fase 2 — Módulos Operativos).

---

## 0. NOTA DE CONSOLIDACION

La version anterior de este archivo describía un orquestador de IA ("AURA AiOrchestrator"), telemetría cognitiva ASFL, memoria relacional AXON_NEXUS y prompts maestros de un producto SaaS ajeno — ningún componente de ese sistema existe en el código de Media HUB (no hay `AiOrchestrator`, no hay tabla `synaptic_prompts`, no hay endpoints de IA en `api/`). Ese contenido fue **descartado por completo**.

---

## 1. ESTADO ACTUAL — SIN CAPA COGNITIVA

Media HUB es, a la fecha de este documento, un sistema de gestión operativa (organigrama, agenda, inventario, legal) sin componente de inteligencia artificial generativa. No existen:

- Endpoints de chat, prompts o modelos de lenguaje en `api/`.
- Tablas de prompts, telemetría de IA o consumo de tokens en el esquema (`02_CODEX_Y_SCHEMA_MAESTRO.md`).
- Integraciones con OpenAI, Anthropic u otro proveedor de IA en `.env` o en el código.

Conforme a la regla de la plantilla base del holding (`knowledge/compare/06_...md`): *"Este pilar documenta la capa cognitiva del proyecto SOLO si el proyecto integra IA generativa. Si el proyecto no usa IA, dejar este documento marcado como N/A."* Este es ese caso.

---

## 2. CANDIDATOS FUTUROS (Roadmap — NO autorizados para implementación)

Si en una fase futura Media HUB decide incorporar IA (por ejemplo, asistencia en la redacción de reportes de producción, resumen automático de bitácoras de check-in/check-out, o un copiloto de agenda), este documento debe llenarse con:

1. Proveedor de IA elegido y modelo (ej. `gpt-4o-mini`, Claude).
2. Arquitectura Proxy/Puente: la API Key nunca toca el frontend, vive únicamente en `.env` y se consume desde un endpoint en `api/`.
3. Esquema de la tabla de prompts versionados (si aplica) en `02_CODEX_Y_SCHEMA_MAESTRO.md`, antes de escribir cualquier query.
4. Contrato de request/response del endpoint de IA en `03_CONTRATOS_API_Y_RUTAS.md`.

**Ningún endpoint de IA se implementa sin que este documento se actualice primero con la decisión real del Arquitecto (Mandamiento 9 — Inmutabilidad del Sistema; Mandamiento 4 — Anti-Alucinación).**

---

*Este pilar permanece en reserva hasta que Media HUB defina una necesidad real de IA generativa. No copiar prompts, nombres de motor ni esquemas de otros proyectos del holding hacia este documento.*
