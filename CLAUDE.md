# CLAUDE.md — Manual Operativo del Agente IA
## MEDIA HUB | Terminal de Operaciones Audiovisuales — La Paz, BCS
**Versión:** 1.0 | **Fecha:** 2026-07-04 | **Arquitecto:** ACADEP DESKTOP

---

## 1. IDENTIDAD DEL PROYECTO

**Proyecto:** Media HUB
**Objetivo:** Sistema centralizado de gestión de personal, agenda con control de colisiones, inventario dinámico (check-in/check-out), cumplimiento legal con firma digital y Landing Page pública para un estudio de producción audiovisual con cobertura terrestre (Van BCS-01) y marítima (Embarcación Mar de Cortés) en La Paz, Baja California Sur.
**Entorno local:** `C:\xampp\htdocs\MediaHUB\`
**Base de datos activa:** `tecnidepot_mediahub_db`
**Repositorio:** GitHub → rama `main` → auto-deploy vía GitHub Actions FTP (`.github/workflows/deploy.yml`)

### Stack Tecnológico

- **Backend:** PHP 8.x nativo, patrón **Singleton PDO** (`config/Database.php`), sentencias preparadas obligatorias (`PDO::ATTR_EMULATE_PREPARES => false`)
- **Frontend:** Tailwind CSS (CDN) + JavaScript Vanilla / jQuery 3.7, sin build step ni SPA
- **Base de Datos:** MySQL/MariaDB InnoDB, charset `utf8mb4`
- **Servidor:** Apache/XAMPP local (paridad Local-Servidor con GreenGeeks en producción)
- **IA:** N/A — Media HUB no integra IA generativa en su alcance actual (ver `knowledge/06_NUCLEO_COGNITIVO_Y_PROMPTS.md`)

---

## 2. ESTRUCTURA DE CARPETAS (ESTADO REAL)

```
MediaHUB/
├── index.php                  ← Landing pública + Modal "Portal Staff" (login)
├── troll.php                  ← Página 403 para atacantes detectados (Troll Mode)
├── reset_password.php / setup_superadmin.php / db_test.php / test_hub_connection.php  ← SOLO desarrollo, nunca a producción
├── .env                       ← Credenciales reales (NUNCA en Git)
├── .gitignore                 ← Protege .env, seguridad.log y scripts de diagnóstico
├── CLAUDE.md                  ← Este archivo
├── DB_STRUCTURE.md            ← Notas de estructura de BD (histórico)
│
├── config/
│   └── Database.php           ← PDO Singleton + parser de .env tolerante a fallos
│
├── api/                        ← Capa API: único punto de entrada para mutaciones (ver knowledge/03)
│   ├── csrf.php · security.php (Troll Mode) · response.php (helpers JSON) · auth_guard.php
│   ├── login.php · logout.php · signature.php · forgot_password.php · reset_password.php
│   ├── agenda.php · clients.php · programs.php · checklist.php · inventory.php · users.php · finance.php · mailer.php
│   └── debug_db.php · test_integration.php · test_smtp_delivery.php   ← Diagnóstico, NUNCA a producción
│
├── legal/
│   └── firma.php               ← Vista del flujo de firma digital obligatoria
│
├── dashboard/
│   └── index.php               ← Dashboard operativo por rol
│
├── assets/{css,js,img}/         ← Estilos, scripts cliente, isotipo
│
├── database/
│   ├── schema.sql               ← Script de inicialización (9 tablas + seed)
│   └── migration_fase2.sql / migration_fase3_security.sql / migration_fase4_roles_seed.sql
│
├── .github/workflows/deploy.yml ← Pipeline CI/CD (push a main = deploy FTP)
│
└── knowledge/                   ← Memoria del sistema (bloqueada en .htaccess cuando exista)
    ├── 00_ADN_Y_FILOSOFIA.md
    ├── 01_LEY_Y_PROTOCOLOS_DE_VUELO.md
    ├── 02_CODEX_Y_SCHEMA_MAESTRO.md
    ├── 03_CONTRATOS_API_Y_RUTAS.md
    ├── 04_ARQUITECTURA_Y_BLINDAJE.md
    ├── 05_MATRIZ_FINANCIERA_Y_VENTAS.md
    ├── 06_NUCLEO_COGNITIVO_Y_PROMPTS.md (N/A — sin IA en Fase 1-2)
    ├── 07_UI_MODULOS_Y_PANTALLAS.md
    └── 08_CHECKLIST_MAESTRO_BACKEND.md
```

> **⚠️ Pendientes de Arranque Blindado (Mandamiento 11):** a la fecha de este documento **no existen** `.htaccess` ni `.env.example` en la raíz del proyecto. Ambos son obligatorios antes de cualquier despliegue a producción — ver checklist en `knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md` §F y `knowledge/08_CHECKLIST_MAESTRO_BACKEND.md` §6.1.

---

## 3. LOS 18 MANDAMIENTOS — LEY SUPREMA

Referencia completa: `knowledge/01_LEY_Y_PROTOCOLOS_DE_VUELO.md`

| # | Mandamiento | Resumen Ejecutivo |
| :--- | :--- | :--- |
| 1 | Mobile-First | Todo componente nace para celular. Sin anchos fijos (px) en contenedores. |
| 2 | Seguridad Nivel Militar | Sanitización + Prepared Statements. Blindaje SQLi, XSS, CSRF. |
| 3 | Modo Oscuro | Contraste mínimo WCAG 4.5:1. Tema fluido Light/Dark (`localStorage`). |
| 4 | Anti-Alucinación | Prohibido inventar variables/endpoints/tablas. Si no está en `02_CODEX_Y_SCHEMA_MAESTRO.md`, detenerse. |
| 5 | Contrato de API Estricto | No alterar propiedades JSON sin actualizar `03_CONTRATOS_API_Y_RUTAS.md`. |
| 6 | Ejecución Determinística | Sin "mejoras" ni extensiones no solicitadas. |
| 7 | Naming Registry | `snake_case` backend/DB. `camelCase`/vanilla JS en frontend. |
| 8 | Dead Code | Auditoría de huérfanos antes de cada entrega. |
| 9 | Inmutabilidad del Sistema | No crear tablas ni alterar schema sin autorización explícita. |
| 10 | Sinónimos Prohibidos | Un solo nombre válido por concepto (ej. `Lider_Logistica`, no `Chofer_Logistica`). |
| 11 | Arranque Blindado | `.env`, `.htaccess` y PDO Singleton antes de cualquier módulo nuevo. |
| 12 | **Bóveda de Secretos** | **Prohibido hardcodear credenciales. Todo en `.env`, leído por `Database::loadEnv()`.** |
| 13 | Aislamiento de Entornos | Local NUNCA apunta a la BD de producción de GreenGeeks. |
| 14 | CORS ≠ Auth | Todo endpoint POST/PUT/DELETE requiere sesión real. Sin sesión = 401. |
| 15 | Agente Residente | Este archivo (`CLAUDE.md`) debe mantenerse actualizado con cada hito. |
| 16 | CI/CD Inquebrantable | Deploy automático vía `.github/workflows/deploy.yml`. Despliegue manual prohibido. |
| 17 | Documentación Viva | Módulo sin documentar en `knowledge/07` = módulo no terminado. |
| 18 | Auditoría de Cierre | Ningún módulo a producción sin pasar el checklist de blindaje (`knowledge/08` + Sección 8 de este documento). |

---

## 4. REGLAS DE HIERRO — SEGURIDAD ("ESTÁNDAR ORO")

### PROHIBIDO absolutamente:
- Hardcodear contraseñas, tokens o credenciales de BD en cualquier archivo PHP o JS.
- Construir SQL concatenando `$_POST`/`$_GET`/`$_SESSION` — solo `prepare()` + `execute([...])`.
- Usar `header('Location: ...')` con ruta absoluta (`/algo`) — Media HUB corre en subcarpeta (`localhost/MediaHUB/`); todas las redirecciones son relativas (`../`), ver `knowledge/04_ARQUITECTURA_Y_BLINDAJE.md` §10.
- Crear nuevas tablas o alterar el schema de BD sin autorización explícita.
- Mostrar errores de PDO/PHP en el frontend — siempre `try/catch` + `error_log()` + mensaje genérico al cliente.
- Subir a producción: `debug_db.php`, `test_integration.php`, `test_smtp_delivery.php`, `setup_superadmin.php`, `db_test.php`, `test_hub_connection.php`.

### OBLIGATORIO siempre:
- Toda conexión a BD pasa por `Database::getInstance()->getConnection()` (Singleton, `config/Database.php`).
- Todo endpoint de mutación en `api/` sigue el patrón de 6 pasos: sesión (`mh_require_session`) → rol (`mh_require_role`) → payload JSON (`mh_read_json_body`) → Troll Mode (`mh_guard_request`) → CSRF (`mh_require_csrf`) → lógica con `try/catch`. Ver `knowledge/03_CONTRATOS_API_Y_RUTAS.md`.
- Passwords: `password_hash(..., PASSWORD_BCRYPT, ['cost' => 12])`, verificados con `password_verify()`.
- Todo formulario de firma digital valida `signature_name` contra `users.full_name` (case-insensitive) — ver `knowledge/01_LEY_Y_PROTOCOLOS_DE_VUELO.md` §1.
- Antes de generar código: verificar que la tabla/columna existe en `knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md`.

### Troll Mode (Defensa Activa)
Todo input de formulario se escanea contra `MH_ATTACK_PATTERNS` (SQLi, XSS) antes de tocar la BD. Un patrón detectado → redirección a `troll.php` + registro en `seguridad.log`. 5 intentos fallidos de login → `users.status = 'Troll_Mode'`. Ver `knowledge/04_ARQUITECTURA_Y_BLINDAJE.md` §4.

---

## 5. COMPORTAMIENTO DEL AGENTE (MODO DE OPERACIÓN)

**Modo:** Determinístico. No creativo. No expansivo.

### Antes de escribir código:
1. Consultar `knowledge/03_CONTRATOS_API_Y_RUTAS.md` — respetar contratos de API existentes.
2. Verificar que las variables/tablas están en `knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md`.
3. Confirmar que no se altera el schema de BD sin autorización (Mandamiento 9).
4. Revisar `knowledge/08_CHECKLIST_MAESTRO_BACKEND.md` — puede que la tarea ya esté planificada ahí.

### Al terminar un módulo:
1. Actualizar `knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md` con nuevas tablas/columnas.
2. Actualizar `knowledge/03_CONTRATOS_API_Y_RUTAS.md` si se creó/modificó un endpoint.
3. Actualizar `knowledge/07_UI_MODULOS_Y_PANTALLAS.md` si se creó/modificó una pantalla.
4. Marcar la casilla correspondiente en `knowledge/08_CHECKLIST_MAESTRO_BACKEND.md` solo si hay código verificado con `php -l` y prueba funcional.

### Regla de Cierre de Hito (3 condiciones simultáneas):
1. El código está escrito, guardado y funcional en el entorno local (XAMPP).
2. Todos los artefactos nuevos están registrados en el Codex y/o Contratos.
3. Se ha reportado al Arquitecto el estado del módulo.

---

## 6. PIPELINE CI/CD (GitHub Actions → FTP)

**Archivo:** `.github/workflows/deploy.yml` (ya presente en el repositorio)
**Trigger:** Push a rama `main`

**GitHub Secrets requeridos:** `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`, `FTP_REMOTE_DIR`.

**Excluido del deploy** (ver `.gitignore` y bloque `exclude` de `deploy.yml`):
- `.env`, `seguridad.log`
- `knowledge/` (documentación interna)
- `genesis.php`, `setup_superadmin.php`, `db_test.php`, `test_hub_connection.php`
- `api/debug_db.php`, `api/test_integration.php`, `api/test_smtp_delivery.php`

---

## 7. ARCHIVOS QUE NUNCA SE MODIFICAN SIN AUTORIZACIÓN

- `knowledge/01_LEY_Y_PROTOCOLOS_DE_VUELO.md` — los reglamentos y mandamientos son ley.
- `.env` — credenciales de producción.
- Schema de BD (`database/schema.sql` y migraciones aplicadas) — inmutabilidad del sistema.
- `.htaccess` (cuando se cree) — blindaje crítico de seguridad.

## 8. CHECKLIST DE CIERRE ANTES DE PUSH A `main`

- [ ] `genesis.php`, `setup_superadmin.php`, `db_test.php`, `test_hub_connection.php`, `api/debug_db.php`, `api/test_integration.php`, `api/test_smtp_delivery.php` **no están staged**.
- [ ] `.env` y `seguridad.log` no aparecen en `git status`.
- [ ] Ningún `header('Location: ...')` nuevo empieza con `/`.
- [ ] `php -l` sin errores en todo archivo `.php` modificado.
- [ ] Todo endpoint nuevo en `api/` documentado en `knowledge/03_CONTRATOS_API_Y_RUTAS.md`.

---

## 9. HISTORIAL DE VERSIONES

| Versión | Fecha | Cambio Principal |
| :--- | :--- | :--- |
| v1.0 | 2026-07-04 | Creación del manual operativo — consolidación de `knowledge/` personalizado para Media HUB, reemplazo de contenido genérico ajeno detectado en `03`, `05`, `06`, `07`. |
