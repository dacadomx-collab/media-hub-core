# 07 · UI, MODULOS Y PANTALLAS — MEDIA HUB

> **Version:** 2.0 (Reescritura total — reemplaza contenido generico no relacionado con el proyecto)
> **Clasificacion:** Pilar Canonico — Documento Vivo
> **Fuente de verdad:** `index.php`, `legal/firma.php`, `dashboard/index.php` (auditados directamente)

---

## 0. NOTA DE CONSOLIDACION

La version anterior de este archivo documentaba pantallas de un dashboard Next.js ("TDTM", "Partner Academy", roles `super_admin`/`partner`/`soporte`) que no existen en Media HUB — este proyecto es 100% PHP renderizado en servidor (sin Next.js, sin `/z/`, sin `/x/`). Ese contenido fue **descartado por completo**.

---

## 1. VISION GENERAL — DOS SUPERFICIES

Media HUB tiene dos superficies de presentación, ambas servidas por Apache/PHP, sin framework de frontend:

| Superficie | Archivo | Publico |
| :--- | :--- | :--- |
| **Landing + Portal Staff** | `index.php` | Visitantes públicos + acceso de staff via modal |
| **Dashboard operativo** | `dashboard/index.php` | Staff autenticado (todos los roles, contenido condicionado por rol) |
| **Firma legal obligatoria** | `legal/firma.php` | Staff autenticado con firmas pendientes (gate previo al Dashboard) |
| **Onboarding de Invitados** (Fase 5) | `guest_form.php` | Público, sin sesión — acceso exclusivo por token de `guest_invite_links` |

Stack de presentación: Tailwind CSS (CDN, solo en `index.php`) + CSS nativo (`assets/css/*.css`) + JavaScript Vanilla/jQuery 3.7 (`assets/js/*.js`). Sin build step, sin bundlers, sin SPA. `guest_form.php` **no** carga Tailwind CDN (criterio de "Ligereza Marítima" — mínimo peso para conexiones débiles en el Mar de Cortés); usa únicamente `assets/css/main.css` + `assets/css/guest-form.css`, igual que `legal/firma.php`.

---

## 2. LANDING PUBLICA — `index.php`

### 2.1 Secciones (en orden de scroll)

| `id` | Sección | Contenido |
| :--- | :--- | :--- |
| `#inicio` | Hero | Presentación de Media HUB como terminal audiovisual |
| `#estudio` | Infraestructura | Estudio 5 de Mayo, Van de Producción BCS-01, Embarcación Mar de Cortés |
| `#simulcast` | Diferenciador tecnológico | Transmisión simultánea a Facebook y YouTube (ver `00_ADN_Y_FILOSOFIA.md` §9) |
| `#programas` | Catálogo ARF-Grid (Fase 5) | Shows nativos de Media HUB (`is_native_show=1`), cargados vía `fetch()` a `api/programs.php?action=public_catalog` (`assets/js/programas-catalog.js`). Incluye grid "Highlights" placeholder para clips editados — **aún sin contenido real, no inventar shows/clips** (Mandamiento 4) |
| `#clientes` | Testimoniales | Clientes Jornal (Medicina del Siglo XXI, CCBCS) |
| `#contacto` | Contacto + footer | Datos de contacto, año dinámico (`#year`) |

### 2.1.1 Sección `#programas` — Detalle ARF-Grid

Contenedor padre `#programasGrid`: `flex flex-wrap justify-center gap-6` (sin anchos fijos en px). Tarjetas hijas: `w-full sm:basis-[47%] lg:basis-[23%] max-w-sm` (2 columnas nativas en móvil, hasta 4 en escritorio), imagen del logo en contenedor `aspect-square`. Estado vacío (sin shows nativos sembrados aún) renderiza un mensaje, nunca datos ficticios.

### 2.2 Modal "Portal Staff" (`#loginModal`)

Modal oculto por defecto (`.hidden`), activado por cualquier elemento `[data-open-login]` (botón "Portal Staff" en nav de escritorio, "Ingresar" en nav móvil, enlace del footer).

- **Formulario:** `#loginForm`, `action="api/login.php"`, `method="post"`.
- **Campos:** `#email` (acepta correo o `user_id`, label "Correo corporativo o ID de usuario"), `#password`, `#csrf_token` (hidden, generado por `csrf_token()`).
- **Feedback:** `#emailMessage`, `#passwordMessage`, `#systemMessage` (muestra `$_GET['error']` traducido a mensaje legible si está presente, y auto-abre el modal).
- **Botón:** `#submitBtn` — "Ingresar al HUB".

### 2.3 Modal de recuperación de contraseña (`#forgotModal`)

Activado por `#openForgotBtn` dentro del modal de login. Formulario con `#forgot_email`, envía a `api/forgot_password.php`.

### 2.4 Tema y navegación

- `#themeToggle` con iconos `#iconSun`/`#iconMoon` — persistencia en `localStorage`.
- `#navToggle` / `#mobileNav` — menú hamburguesa responsive (Mandamiento 1 — Mobile-First).

---

## 3. FIRMA LEGAL OBLIGATORIA — `legal/firma.php`

Vista pura (sin lógica de mutación — el procesamiento vive en `api/signature.php`, ver `03_CONTRATOS_API_Y_RUTAS.md` §Contrato 8).

- Renderiza los 4 reglamentos pendientes en acordeón (`<details>`).
- Por cada documento: casilla de reconocimiento (`ack[doc_id]`) + campo `signature_name` (debe coincidir con `users.full_name`, validación case-insensitive).
- `action="../api/signature.php"`. Traduce `$_GET['error']` (`mismatch`, `csrf`) a mensajes legibles.
- Es un **gate bloqueante**: ningún usuario con firmas pendientes accede a `dashboard/index.php` (ver `01_LEY_Y_PROTOCOLOS_DE_VUELO.md` §1).

---

## 3.5 ONBOARDING DE INVITADOS — `guest_form.php` (Fase 5)

Vista pública **sin sesión de usuario** — shell ligero que delega toda la lógica de datos y TTL a `api/guest_submissions.php` vía `assets/js/guest-form.js` (`fetch`). El PHP del archivo solo extrae `$_GET['token']` y lo expone a JS (`window.MH_GUEST_TOKEN`); **no consulta la base de datos directamente** para evitar incrementar el contador de clics dos veces.

- **Estados de UI** (`#gfLoading` / `#gfExpired` / `#gfForm`, mutuamente excluyentes vía clase `.hidden`): cargando → formulario u enlace expirado, según la respuesta del `GET` inicial.
- **Cabecera dinámica:** nombre y descripción del show (`data.program`), logo si existe. CTA "Ver ubicación en Google Maps" con URL fija a la dirección real del Estudio 5 de Mayo (Josefa Ortiz de Domínguez & Calle 5 de Mayo).
- **⚠️ Gap de datos conocido:** el diseño original pedía mostrar "fecha y hora de la grabación", pero `guest_invite_links` (Fase 5) solo referencia `program_id`, no un `call_id` específico — no existe una fecha real que mostrar. Se usa un texto de marcador (`#gfDateNotice`): *"se confirmarán directamente contigo con el equipo de producción."* Para mostrar la fecha real se requiere una migración aditiva futura (`guest_invite_links.call_id` nullable → `calls.id`), pendiente de autorización del Arquitecto (Mandamiento 9).
- **Formulario:** campos obligatorios `full_name`/`title_position`; opcionales `social_links`, `whatsapp`, `website`, `email`, `invite_message`, `qa_notes`. Banner de aviso de uso público de los datos.
- **Persistencia:** el mismo formulario sirve para el clic 1 (alta) y clic 2 (edición, precargado desde `guest_submissions`) — sin distinción visual de modo, ya que el backend resuelve `INSERT ... ON DUPLICATE KEY UPDATE` de forma transparente.
- **Identidad visual:** `assets/css/guest-form.css`, variables heredadas de `assets/css/main.css` (`--deep-sea-blue`, `--pacific-turquoise`, `--sunset-orange: #FF5733` para alertas/campos obligatorios).

---

## 4. DASHBOARD OPERATIVO — `dashboard/index.php`

Panel único (una sola vista con secciones internas por `id`), visibilidad de secciones condicionada por rol de sesión.

| `id` | Módulo | Rol con acceso | Descripción |
| :--- | :--- | :--- | :--- |
| `#kpis` | Centro de Comando Ejecutivo | `Administrador` | Tarjetas de KPIs financieros (`api/finance.php?action=kpis`) |
| `#inicio` | Resumen / Mis Tareas | Todos | Vista de bienvenida y tareas asignadas (`users.php?action=me`) |
| `#agenda` | Agenda | `Administrador`, `Lider_Proyecto` (gestión) / lectura para staff asignado | Listado de llamados con badges de anticipo y colisiones (`api/agenda.php`) |
| `#checklist` | Checklist Operativo | `Administrador`, `Lider_Proyecto`, staff asignado al llamado | 3 pestañas (`data-tab`): **Antes** (Montaje), **Durante** (Live), **Después** (Desmontaje) — `api/checklist.php` |
| `#inventario` | Inventario / Flota | Todos (check-in/out según asignación) | Equipo (`inventory_items`) y unidades móviles (`fleet_vehicles`) — `api/inventory.php` |
| `#admin` | Panel Administrativo Multi-Rol | `Administrador` | Gestión de Clientes, Programas y Usuarios/Organigrama (`clients.php`, `programs.php`, `users.php`) |

### 4.1 Pestañas del Checklist (`#checklistTabs`)

```
data-tab="Antes"    → data-tab-panel="Antes"    (🎬 Montaje)
data-tab="Durante"  → data-tab-panel="Durante"  (📢 Live)
data-tab="Despues"  → data-tab-panel="Despues"  (📦 Desmontaje)
```

Catálogo real de `item_key` por fase documentado en `08_CHECKLIST_MAESTRO_BACKEND.md` §3.

### 4.2 Tema y navegación del Dashboard

- `#themeToggle` — persistencia en `localStorage['mh-theme']`, clase `.light-mode` en `<html>`.
- `#sidebarToggle` / `#sidebarClose` / `#sidebarOverlay` — menú lateral móvil, cierre automático al navegar.
- Logo institucional con favicon (`assets/img/logo.png`), escalado mobile-first (`h-16 md:h-22`).

---

## 5. IDENTIDAD VISUAL APLICADA (Resumen — ver `00_ADN_Y_FILOSOFIA.md` §5)

| Clase / Variable | Valor | Uso |
| :--- | :--- | :--- |
| `deep-sea` / `--deep-sea-blue` | `#022D53` | Fondos oscuros, navegación, texto en modo claro |
| `turquoise` / `--pacific-turquoise` | `#00BFB2` | CTAs, acentos, bordes activos, iconografía de tema |
| `digital-white` | `#FFFFFF` | Texto sobre fondo oscuro |
| Tipografía | Montserrat (títulos) / Roboto (cuerpo) | Toda la superficie |

Ambas superficies (`index.php` y `dashboard/index.php`) soportan modo claro/oscuro nativo (Mandamiento 3), con Tailwind configurado `darkMode: 'class'`.

---

## 6. MODULOS PENDIENTES (Roadmap — Fase 2 en curso / Fase 3 futura)

| Módulo | Estado | Referencia |
| :--- | :--- | :--- |
| Máquina de estados extendida de `calls.status` (`DRAFT` → `ARCHIVED`) | Pendiente | `08_CHECKLIST_MAESTRO_BACKEND.md` §2.1 |
| Alertas automáticas T-15min antes de Simulcast | Pendiente | `08_CHECKLIST_MAESTRO_BACKEND.md` §3.1 |
| Portal de Cliente Jornal (rol `Cliente`) | Fase 3 — futura | `00_ADN_Y_FILOSOFIA.md` §10 |
| CRUD de Proyectos (agrupador superior a `programs`) | Pendiente | `08_CHECKLIST_MAESTRO_BACKEND.md` §1.1 |

---

*Este documento es el inventario vivo de pantallas de Media HUB (Mandamiento 17 — Documentación Viva). Toda pantalla o módulo nuevo debe registrarse aquí antes de darse por terminado.*
