# 09 · CHECKLIST DE MIGRACIÓN FINAL — Dominio Provisional → Dominio Corporativo

> **Versión:** 1.0 | **Clasificación:** Documento Canónico — Fuente de Verdad de la Migración de Dominio
> **Contexto:** Media HUB opera actualmente bajo la URL provisional `https://mediahub.tecnidepot.com` (GreenGeeks). Este documento es el checklist quirúrgico a ejecutar el día que el proyecto se mude al dominio corporativo final. No inventar variables nuevas al ejecutar este checklist — todo lo listado aquí corresponde a variables/archivos reales ya auditados en `api/`, `.env` y `.htaccess` (Fases 5.1–5.5).

---

## 0. PRINCIPIO RECTOR

La mayoría de los enlaces generados por el sistema (correos transaccionales, enlaces de invitado) **ya no dependen de un valor estático** gracias a `mh_detect_base_url()` (`api/response.php`, Fase 5.5), que deriva la URL base de la petición HTTP real en vez de leer `APP_URL` ciegamente. Esto reduce el riesgo de 404 tras la migración, pero **no elimina la necesidad de actualizar `.env`, DNS y Apache** — sigue detallado abajo.

---

## 1. VARIABLES DE ENTORNO (`.env`) — A ACTUALIZAR

| Variable | Uso | Acción al migrar |
| :--- | :--- | :--- |
| `APP_URL` | Fallback de `mh_detect_base_url()` cuando no hay contexto HTTP (CLI/cron) | Cambiar a `https://[dominio-final]` |
| `APP_ENV` | Gate de `Secure` en cookies (`mh_remember_cookie_options()`, `api/auth_guard.php`) | Confirmar `production` (nunca `local`) |
| `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASS` | Conexión PDO (`config/Database.php`) | Actualizar **solo si la BD también se muda** — si sigue en GreenGeeks, no tocar |
| `MAIL_FROM_ADDRESS` | Remitente visible (`From:`) de todos los correos — **debe coincidir con el dominio que autentica el envío SMTP** (causa confirmada de rechazo por Yahoo/Hotmail en Fase 5.5) | Candidato ya identificado: `hola@mediahubbcs.com` — **confirmar que ese buzón exista realmente en el hosting final antes del corte** y que el SPF/DKIM/DMARC del §2 se emitan para ese mismo dominio |
| `MAIL_HOST` / `MAIL_USER` / `MAIL_PASS` / `MAIL_PORT` / `MAIL_PROTOCOL` | Cliente SMTP nativo (`api/smtp_mailer.php`) | Apuntar al servidor de correo del nuevo hosting/dominio; regenerar `MAIL_PASS` si el buzón es nuevo |
| `MH_MAIL_TEST_RECIPIENT` / `MH_MAIL_TEST_CC` | **Interceptor transitorio de pruebas** (`api/mailer.php`) — mientras existan, **TODO correo del sistema se redirige a estas direcciones** | **🚨 VACIAR/BORRAR ambas antes de servir a usuarios reales.** Si se migra sin quitarlas, ningún cliente/staff recibirá sus correos reales — todo seguirá llegando a la bandeja de pruebas |
| `MH_DEBUG_TOKEN` | Puente forense (`api/login.php`, `api/programs.php`) y gate de `api/test_smtp_delivery.php` | Rotar a un valor nuevo (el actual quedó expuesto en conversaciones de desarrollo) |
| `CSRF_SECRET` | HMAC de tokens de `password_resets` y `user_remember_tokens` | Opcional rotar; si se rota, todos los tokens de reset/activación/recuerdame pendientes quedan invalidados de inmediato |

---

## 2. DNS Y ALINEACIÓN SPF/DMARC (dominio nuevo — desde cero)

La alineación SPF/DMARC resuelta en Fase 5.5 es **específica del dominio `tecnidepot.com`**. Un dominio nuevo empieza sin reputación ni registros — repetir desde cero:

- [ ] **Registro SPF** (TXT en el dominio nuevo) autorizando el servidor de correo del hosting (ej. `v=spf1 include:[servidor-hosting] ~all`).
- [ ] **DKIM** — generarlo desde cPanel del hosting nuevo y publicar el TXT que cPanel indique.
- [ ] **DMARC** — publicar política (`v=DMARC1; p=quarantine; ...` o `p=none` durante la transición) apuntando a un buzón de reportes real.
- [ ] **Registros MX** apuntando al servidor de correo correcto del hosting nuevo.
- [ ] Confirmar que **`MAIL_FROM_ADDRESS` (§1) vive en el mismo dominio que firma el SPF/DKIM** — la lección de Fase 5.5 fue exactamente esta desalineación.
- [ ] Volver a ejecutar `api/test_smtp_delivery.php` (con `MH_DEBUG_TOKEN` rotado) contra el nuevo dominio antes de dar por buena la entrega a Yahoo/Hotmail/Gmail.

---

## 3. CERTIFICADO SSL Y CONTEXTO TLS DEL MAILER

`api/smtp_mailer.php` tiene `verify_peer_name => false` (Fase 5.4) porque el certificado del servidor de correo de GreenGeeks no coincidía con `mediahub.tecnidepot.com`. Al migrar:

- [ ] Verificar si el certificado del nuevo `MAIL_HOST` coincide con el hostname real.
- [ ] Si coincide, **restaurar `verify_peer_name => true`** en `api/smtp_mailer.php` y `api/test_smtp_delivery.php` — no dejar la validación relajada más de lo necesario.
- [ ] `verify_peer` debe permanecer siempre en `true` (nunca desactivar la validación de la cadena de confianza completa).

---

## 4. APACHE / `.htaccess` / DOCUMENT ROOT

- [ ] **Document Root final:** si el hosting nuevo sirve el proyecto en una ruta tipo `/public_html/mediahub` (subcarpeta) en vez de la raíz del dominio, `mh_detect_base_url()` (`api/response.php`, Fase 5.5) ya la detecta sola a partir de `SCRIPT_NAME` — **no requiere tocar código** siempre que la estructura relativa de `api/`, `legal/`, `dashboard/` respecto a la raíz del proyecto se mantenga intacta al copiar los archivos.
- [ ] Confirmar que el `.htaccess` de producción **no incluye** la excepción de IP para `api/test_smtp_delivery.php` en un dominio público final (o que el archivo directamente no se sube — ver `.gitignore`/`CLAUDE.md` §4).
- [ ] Confirmar `ServerSignature Off` y las cabeceras de seguridad (`X-Frame-Options`, `HSTS` si aplica) siguen activas bajo el nuevo Virtual Host.
- [ ] Revisar que `RewriteRule` de bloqueo de `knowledge/`, `config/`, `database/` sigan intactas tras cualquier copia manual de archivos.
- [ ] Si el hosting nuevo requiere un Document Root distinto al de GreenGeeks, confirmar con el proveedor que `/uploads/` (logos de shows nativos) se copió completo y conserva sus permisos (§5).

---

## 5. PERMISOS DE ARCHIVOS (Estándar Oro)

- [ ] Directorios: **755** (`api/`, `config/`, `database/`, `uploads/`, `legal/`, `dashboard/`, `assets/`).
- [ ] Archivos PHP/CSS/JS/SQL: **644**.
- [ ] `.env`: **600**, propietario = usuario del proceso PHP, nunca accesible vía HTTP.
- [ ] `/uploads/`: verificar que la regla de `.htaccess` que bloquea ejecución de PHP dentro de esa carpeta sigue activa tras la migración.

---

## 6. ARCHIVOS DE DIAGNÓSTICO — NUNCA AL DOMINIO FINAL

Confirmar que **ninguno** de estos llegó al servidor de producción final (ver `.gitignore` y `CLAUDE.md` §4/§8):

`genesis.php`, `setup_superadmin.php`, `db_test.php`, `test_hub_connection.php`, `api/debug_db.php`, `api/test_integration.php`, `api/test_smtp_delivery.php`.

---

## 7. PENDIENTES YA CONOCIDOS (heredados de Fases 5.1–5.5, no relacionados al dominio)

Estos siguen abiertos independientemente de la migración — no confundir con el checklist de dominio:

- `.htaccess` con excepción de IP para `test_smtp_delivery.php` (§4) es de uso exclusivo de desarrollo.
- Matriz de costos fijos/variables para refinar la utilidad neta proyectada (`05_MATRIZ_FINANCIERA_Y_VENTAS.md` §5) — sigue en 40% fijo.
- Portal de Cliente Jornal (rol `Cliente`, Fase 3 futura) — sin implementar.

---

*Este documento se actualiza la próxima vez que se ejecute una migración de dominio real — no antes. Cualquier variable de entorno nueva que se agregue al proyecto entre esta fecha y la migración debe añadirse aquí también (Mandamiento 17 — Documentación Viva).*
