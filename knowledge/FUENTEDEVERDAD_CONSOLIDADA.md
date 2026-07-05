# FUENTE DE VERDAD CONSOLIDADA — MEDIA HUB
## Índice Maestro de Gobernanza y Estado Real del Proyecto

> **Version:** 2.0 (Adaptado de la plantilla genérica "Bóveda Madre" DCD LABS/VECTOR_CERO)
> A diferencia de la plantilla base (`knowledge/compare/FUENTEDEVERDAD_CONSOLIDADA.md`), que documenta el estado de un machote genérico sin proyecto comercial activo, **este documento describe el estado real de Media HUB** — un proyecto en producción activa (Fase 1–2), no una plantilla de clonación.

---

## 1. ESTADO REAL POR CAPA/COMPONENTE

| Capa | Componentes | Estado |
| :--- | :--- | :--- |
| **Seguridad (Foundation)** | `api/csrf.php`, `api/security.php` (Troll Mode), `config/Database.php` (PDO `ATTR_EMULATE_PREPARES=false`) | ✅ Implementado |
| **`.htaccess` raíz** | Blindaje Apache (bloqueo de `.env`, `.sql`, `.log`, `/knowledge/`) | ⬜ **Pendiente** — no existe en la raíz a la fecha |
| **`.env.example`** | Plantilla pública de variables de entorno | ⬜ **Pendiente** — no existe a la fecha |
| **Datos (Foundation)** | `config/Database.php` (Singleton PDO + parser `.env` tolerante a fallos, `parseEnvFile()`) | ✅ Implementado |
| **Autenticación y Sesión** | `api/login.php`, `api/logout.php`, `api/signature.php` (firma legal), `api/auth_guard.php` | ✅ Implementado |
| **Firma Digital Legal** | `legal/firma.php` + tabla `user_legal_signatures` — 4 reglamentos obligatorios | ✅ Implementado (ver `01_LEY_Y_PROTOCOLOS_DE_VUELO.md`) |
| **Agenda / Control de Colisiones** | `api/agenda.php` — algoritmo anti-sobre-reservas + compuerta de anticipo 50% | ✅ Implementado |
| **Inventario / Flota (Check-In/Out)** | `api/inventory.php` + tabla `checkinout_log` | ✅ Implementado |
| **Checklist Operativo (3 fases)** | `api/checklist.php` + `call_checklist_progress` | ✅ Implementado (parcial — ver `08_CHECKLIST_MAESTRO_BACKEND.md` para pendientes) |
| **Clientes / Programas (CRUD)** | `api/clients.php`, `api/programs.php` | ✅ Implementado |
| **Organigrama de Usuarios** | `api/users.php` | ✅ Implementado |
| **Motor de Correo Transaccional** | `api/mailer.php` (bienvenida, nuevo programa, confirmación de fecha, recuperación de contraseña) | ✅ Implementado |
| **Centro de Comando Ejecutivo (KPIs)** | `api/finance.php?action=kpis` | ✅ Implementado (simplificado — ver `05_MATRIZ_FINANCIERA_Y_VENTAS.md`) |
| **Landing Pública + Portal Staff** | `index.php` | ✅ Implementado |
| **Dashboard Operativo** | `dashboard/index.php` + `assets/js/dashboard.js` | ✅ Implementado |
| **Capa Cognitiva / IA** | N/A — ver `06_NUCLEO_COGNITIVO_Y_PROMPTS.md` | ⬜ Sin alcance en Fase 1–2 |
| **CI/CD** | `.github/workflows/deploy.yml` | ✅ Presente |
| **Knowledge Base (`knowledge/00`–`08`)** | Auditada y corregida el 2026-07-04 — 4 pilares (`03`, `05`, `06`, `07`) contenían contenido genérico ajeno al proyecto y fueron reescritos | ✅ Consolidada |

---

## 2. REGLA CERO — AISLAMIENTO DE ENTORNOS

El desarrollo es local (`http://localhost/MediaHUB/`, XAMPP). La base de datos de producción vive en GreenGeeks (`tecnidepot_mediahub_db`). Ver la directiva completa de Remote MySQL y `APP_ENV` en `knowledge/04_ARQUITECTURA_Y_BLINDAJE.md`.

---

## 3. PENDIENTE DE AUTORIZACIÓN EXPLÍCITA (Mandamiento #9)

- `.htaccess` en la raíz del proyecto (bloqueo de `/knowledge/`, `.env`, `.sql`, `.log`, desactivación de `Indexes`).
- `.env.example` como plantilla pública de variables de entorno.
- Máquina de estados extendida de `calls.status` (`DRAFT` → `ARCHIVED`) — ver `08_CHECKLIST_MAESTRO_BACKEND.md` §2.1.
- Matriz de costos fijos/variables para refinar la utilidad neta proyectada (actualmente 40% fijo) — ver `05_MATRIZ_FINANCIERA_Y_VENTAS.md` §5.
- Portal de Cliente Jornal (rol `Cliente`) — Fase 3 futura.

---

## 4. CHECKLIST DE ARRANQUE BLINDADO PENDIENTE (Mandamiento #11)

1. Crear `.htaccess` en raíz con blindaje Apache Nivel Militar.
2. Crear `.env.example` a partir del `.env` real (sin valores reales).
3. Verificar permisos `755`/`644`/`600` en el servidor de producción antes del primer deploy (ver `CLAUDE.md` §4 y `knowledge/04_ARQUITECTURA_Y_BLINDAJE.md` §6).
4. Ejecutar el checklist de auditoría perimetral (Mandamiento #18, `CLAUDE.md` §8) antes de cualquier salida a producción.

---

## 5. REFERENCIAS

- Manual operativo del agente: [`CLAUDE.md`](../CLAUDE.md)
- ADN y filosofía: [`00_ADN_Y_FILOSOFIA.md`](00_ADN_Y_FILOSOFIA.md)
- Mandamientos y protocolos: [`01_LEY_Y_PROTOCOLOS_DE_VUELO.md`](01_LEY_Y_PROTOCOLOS_DE_VUELO.md)
- Codex y schema maestro: [`02_CODEX_Y_SCHEMA_MAESTRO.md`](02_CODEX_Y_SCHEMA_MAESTRO.md)
- Contratos de API: [`03_CONTRATOS_API_Y_RUTAS.md`](03_CONTRATOS_API_Y_RUTAS.md)
- Arquitectura y blindaje: [`04_ARQUITECTURA_Y_BLINDAJE.md`](04_ARQUITECTURA_Y_BLINDAJE.md)
- Matriz financiera: [`05_MATRIZ_FINANCIERA_Y_VENTAS.md`](05_MATRIZ_FINANCIERA_Y_VENTAS.md)
- Núcleo cognitivo (N/A): [`06_NUCLEO_COGNITIVO_Y_PROMPTS.md`](06_NUCLEO_COGNITIVO_Y_PROMPTS.md)
- UI y pantallas: [`07_UI_MODULOS_Y_PANTALLAS.md`](07_UI_MODULOS_Y_PANTALLAS.md)
- Checklist maestro backend: [`08_CHECKLIST_MAESTRO_BACKEND.md`](08_CHECKLIST_MAESTRO_BACKEND.md)

---

*Este documento se actualiza cada vez que cambia el estado real de una capa/componente (Mandamiento 17 — Documentación Viva). No es una plantilla de clonación — es la fuente de verdad operativa de Media HUB.*
