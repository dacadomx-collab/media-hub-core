# 00 · ADN Y FILOSOFIA — MEDIA HUB

> **Version:** 1.0 (Fase 1 — Estandar Oro)
> **Ubicacion fisica:** Estudio 5 de Mayo, Josefa Ortiz de Dominguez & Calle 5 de Mayo, Zona Central, 23000 La Paz, Baja California Sur, Mexico.
> **Naturaleza:** Terminal inteligente de produccion audiovisual y convergencia digital frente al Mar de Cortes.

---

## 1. ¿QUE ES MEDIA HUB?

Media HUB **no es una productora tradicional**. Es una **terminal de operaciones audiovisuales**: un punto de convergencia donde el talento humano (staff tecnico, choferes de logistica, lideres de proyecto), la infraestructura fisica (Estudio 5 de Mayo, Van de Produccion BCS-01, Embarcacion Mar de Cortes) y la plataforma digital (este sistema) operan como un solo organismo.

El nombre lo dice todo: somos el **HUB** — el nodo central — desde donde se coordina, agenda, transmite y resguarda toda la actividad audiovisual de nuestros Clientes Jornal en La Paz, BCS.

La plataforma desarrollada en esta Fase 1 es el **sistema nervioso digital** de esa terminal: organigrama de personal, agenda con control de colisiones, inventario dinamico con check-in/check-out, modulo legal con firma digital obligatoria, y una Landing Page publica que proyecta la imagen profesional del estudio hacia clientes nuevos.

---

## 2. MISION

> Coordinar, producir y transmitir contenido audiovisual de calidad profesional para los clientes recurrentes ("Clientes Jornal") de La Paz, BCS, eliminando la friccion operativa mediante una plataforma digital que organiza personal, agenda, inventario y cumplimiento legal en un solo lugar — **"Tu produccion, sin caos."**

Cada llamado (sesion de grabacion) que pasa por Media HUB debe salir con:
- Personal asignado y notificado (Staff Tecnico, Lider de Proyecto, Chofer de Logistica si aplica).
- Equipo verificado mediante check-in/check-out (camaras, opticas, luces LED, audio).
- Locacion confirmada sin colisiones de horario (Estudio 5 de Mayo, Van Terrestre o Embarcacion Maritima).
- Condiciones comerciales claras (anticipo minimo del 50% antes de confirmar el llamado).

---

## 3. VISION

> Ser el centro de operaciones audiovisuales de referencia en Baja California Sur, reconocido por la confiabilidad de su agenda, la calidad de su Estudio 5 de Mayo y su capacidad unica de cobertura terrestre y maritima en el Mar de Cortes — extendiendo su alcance mediante transmision **Simulcast** simultanea a Facebook y YouTube para que cada Cliente Jornal proyecte su marca sin limites de locacion.

---

## 4. VALORES CORE

### 4.1 Precision Operativa
Cada llamado tiene una fecha, hora, locacion y equipo asignado de forma exacta. El modulo de Agenda valida colisiones de horario por `location`, `call_date`, `start_time` y `end_time` **antes** de confirmar cualquier reserva nueva. No existen "dobles bookings" del Estudio 5 de Mayo.

### 4.2 Transparencia Comercial
Todo llamado define un `advance_required_pct` (50% por defecto). Un programa no se considera **Confirmado** hasta que el anticipo (`advance_paid`) ha sido registrado. Esto protege tanto al estudio como al Cliente Jornal.

### 4.3 Disciplina de Inventario
Cada activo fisico (camaras, opticas, paneles LED, kits de audio, Van de Produccion, Embarcacion Mar de Cortes) tiene un registro individual con `status` (`Disponible`, `En Uso`, `Mantenimiento`) y una bitacora (`checkinout_log`) que documenta quien lo tomo, para que llamado y en que condiciones lo regreso.

### 4.4 Cumplimiento Legal Inquebrantable
Ningun integrante del staff opera dentro del ecosistema Media HUB sin haber **firmado digitalmente** los 4 reglamentos fundacionales (Contrato de Staff, Reglas del Estudio, Reglas por Grabacion, Reglas Generales). El sistema fuerza esta validacion en cada inicio de sesion — ver `01_LEY_Y_PROTOCOLOS_DE_VUELO.md`.

### 4.5 Blindaje Tecnico ("Estandar Oro")
La plataforma protege datos de clientes, personal y operaciones mediante sentencias preparadas (PDO), tokens CSRF, hashing Bcrypt y un sistema activo de deteccion de intrusos ("Troll Mode"). Ver `04_ARQUITECTURA_Y_BLINDAJE.md`.

### 4.6 Mobile-First / Dual Tema
Todo el ecosistema —desde la Landing Page publica hasta el Portal Staff— se disena primero para pantallas moviles (donde el staff confirma llamados y hace check-in/out desde locacion) y soporta tema claro y oscuro de forma nativa, respetando la paleta corporativa.

---

## 5. IDENTIDAD VISUAL (Inmutable)

| Token | Valor | Uso |
|---|---|---|
| **Deep Sea Blue** | `#022D53` | Color primario de marca, fondos en modo oscuro, texto en modo claro |
| **Turquoise Accent** | `#00BFB2` | Llamadas a la accion (CTAs), acentos, enlaces activos, bordes destacados |
| **Digital White** | `#FFFFFF` | Texto sobre fondos oscuros, fondos en modo claro |

Tipografias: **Montserrat** (titulos, `font-display`) y **Roboto** (cuerpo de texto).

---

## 6. EL ORGANIGRAMA DIGITAL (Roles Fase 1)

| Rol (`users.role`) | Funcion | Ejemplo asignado |
|---|---|---|
| `Administrador` | Control total del sistema: usuarios, catalogo legal, configuracion | admin.root |
| `Lider_Proyecto` | Supervisa programas, agenda y asignaciones de staff | German Lage |
| `Staff_Tecnico` | Personal de produccion en locacion (camaras, luces, audio) | Gibran Morales, Antonio Murillo |
| `Chofer_Logistica` | Operacion de unidades moviles (Van Terrestre / Embarcacion Maritima) | Chofer Logistica 1 |
| `Cliente` | Acceso restringido a su propia agenda/programas | (Clientes Jornal futuros) |

---

## 7. LOS CLIENTES JORNAL (Casos Fundacionales)

Media HUB opera bajo el concepto de **"Cliente Jornal"**: un cliente con un programa **recurrente** que se produce de forma periodica, no como evento aislado.

- **Medicina del Siglo XXI** (Dr. Efrain Torres) — Programa recurrente de entrevistas a especialistas de la salud, producido en el Estudio 5 de Mayo.
- **CCBCS** (Efrain Torres) — Programa institucional recurrente del Consejo Coordinador, con produccion y transmision Simulcast.

Ambos casos viven en las tablas `clients` y `programs` del esquema (ver `02_CODEX_Y_SCHEMA_MAESTRO.md`) y se exhiben como testimoniales en la Landing Page (`index.php`, seccion `#clientes`).

---

## 8. INFRAESTRUCTURA FISICA

1. **Estudio 5 de Mayo** — Set principal con camaras multicamara HD, opticas profesionales, iluminacion LED fria y kit de audio lavalier. Locacion por defecto para `calls.location`.
2. **Van de Produccion BCS-01** (`fleet_vehicles`, tipo `Van Terrestre`) — Cobertura externa terrestre en La Paz y alrededores.
3. **Embarcacion Mar de Cortes** (`fleet_vehicles`, tipo `Embarcacion Maritima`) — Cobertura unica en el Mar de Cortes para producciones especiales.

---

## 9. SIMULCAST: LA VENTAJA TECNOLOGICA

La sala de control del Estudio 5 de Mayo transmite cada produccion **en vivo y de forma simultanea** a Facebook y YouTube ("Simulcast"), con switching multicamara y pruebas de continuidad de audio previas a cada salida al aire. Esto se comunica en la Landing Page (`index.php`, seccion `#simulcast`) como diferenciador competitivo frente a productoras tradicionales de BCS.

---

## 10. RUTA DE FASES

- **Fase 1 (actual — Estandar Oro):** Organigrama digital, Login seguro con Troll Mode, Modulo Legal con firma digital, esquema de Programas/Clientes/Agenda/Inventario/Flota, Landing Page publica con Portal Staff integrado.
- **Fase 2 (proxima):** Implementacion funcional del Dashboard por rol — Agenda interactiva con validacion de colisiones en vivo, flujo de Check-In/Check-Out, gestion de anticipos y reportes de uso de inventario.
- **Fase 3 (futura):** Portal de Cliente Jornal (rol `Cliente`) para visualizar su propia agenda y programas.

---

*Este documento es la fuente de verdad sobre identidad y proposito de Media HUB. Cualquier nuevo modulo debe alinearse con la Mision, Vision y Valores aqui descritos antes de implementarse.*
