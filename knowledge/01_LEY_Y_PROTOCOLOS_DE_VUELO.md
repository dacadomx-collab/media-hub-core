# 01 · LEY Y PROTOCOLOS DE VUELO — MEDIA HUB

> **Version:** 1.0 (Fase 1 — Estandar Oro)
> **Clasificacion:** Ley Inmutable — Firma Digital Obligatoria
> **Fuente de verdad:** tabla `legal_documents` en `database/schema.sql` (codigos `CONTRATO_STAFF`, `REGLAS_ESTUDIO`, `REGLAS_GRABACION`, `REGLAS_GENERALES`)

---

## 1. PRINCIPIO RECTOR: NADIE OPERA SIN FIRMAR

Media HUB sembro en el primer arranque de la base de datos **4 reglamentos inmutables** dentro de la tabla `legal_documents`. Cada usuario del organigrama (sin importar su rol) recibe, al ser creado, una fila por cada documento en `user_legal_signatures` con `signed = 0`.

El flujo de `process_login.php` es estricto:

1. El usuario se autentica correctamente (email + password via `password_verify()`).
2. Inmediatamente se consulta: `SELECT COUNT(*) FROM user_legal_signatures WHERE user_id = :id AND signed = 0`.
3. Si el conteo es **mayor a 0**, la sesion se crea pero el usuario es **forzado** a `legal/firma.php` — no puede llegar a `dashboard/index.php` hasta firmar todos los documentos pendientes.
4. En `legal/firma.php`, el usuario debe: (a) abrir cada documento (acordeon `<details>`), (b) marcar la casilla de reconocimiento (`ack[doc_id]`) y (c) escribir su nombre completo exactamente como aparece en `users.full_name` (validacion case-insensitive) en el campo `signature_name`.
5. Al enviarse el formulario, cada fila correspondiente se actualiza: `signed = 1`, `signed_at = NOW()`, `ip_address = <IP del firmante>`.
6. Solo cuando **las 4 firmas** estan completas, el usuario accede al Dashboard.

Esta es la "Ley de Vuelo": **no hay despegue operativo (acceso al sistema) sin las 4 firmas registradas.**

---

## 2. LOS 4 REGLAMENTOS INMUTABLES (Texto Sembrado — `version 1.0`)

> El texto siguiente es **exactamente** el contenido sembrado por `database/schema.sql` en la tabla `legal_documents`. Cualquier modificacion futura debe incrementar el campo `version` y quedar documentada en este archivo.

### 2.1 — `CONTRATO_STAFF` (sort_order 1)
**Titulo:** Contrato / Acuerdo de Integrante de Staff

> Al firmar este acuerdo, el integrante de staff de Media HUB reconoce su vinculacion operativa con el estudio, acepta el codigo de conducta profesional, la confidencialidad sobre proyectos de clientes (Clientes Jornal), el uso responsable del equipo asignado y el cumplimiento de los llamados conforme a la agenda publicada.

**Aplica a:** todos los roles (`Administrador`, `Lider_Proyecto`, `Staff_Tecnico`, `Chofer_Logistica`, `Cliente`).
**Puntos clave:** vinculacion operativa, codigo de conducta, confidencialidad de Clientes Jornal, uso responsable de equipo, cumplimiento de llamados segun agenda.

---

### 2.2 — `REGLAS_ESTUDIO` (sort_order 2)
**Titulo:** Reglas del Estudio 5 de Mayo

> Queda prohibido el ingreso de liquidos y alimentos dentro del set de grabacion. Se debe respetar el control termico (clima) del estudio manteniendo puertas cerradas durante grabacion activa. El equipo tecnico (camaras, opticas, luces LED) debe mantenerse en las marcas asignadas y reportarse en el inventario antes y despues de cada llamado.

**Aplica a:** principalmente `Staff_Tecnico` y `Lider_Proyecto`, pero es de lectura obligatoria para todos.
**Puntos clave:** prohibicion de liquidos/alimentos en set, control termico (puertas cerradas durante grabacion activa), equipo en marcas asignadas, registro de inventario antes/despues de cada llamado (`checkinout_log`).

---

### 2.3 — `REGLAS_GRABACION` (sort_order 3)
**Titulo:** Reglas por Grabacion (Set Activo)

> Durante cualquier grabacion activa, todos los celulares deben permanecer en silencio o modo avion. Es obligatorio realizar pruebas de continuidad de audio antes de iniciar. Ninguna persona ajena a la produccion puede ingresar al set sin autorizacion del Lider de Proyecto.

**Aplica a:** todo el personal presente durante un llamado activo (`calls.status = 'Confirmado'` en curso).
**Puntos clave:** celulares en silencio/modo avion, pruebas de continuidad de audio obligatorias antes de iniciar, acceso al set restringido y autorizado unicamente por el Lider de Proyecto.

---

### 2.4 — `REGLAS_GENERALES` (sort_order 4)
**Titulo:** Reglas Generales de la Empresa

> Todo el personal de Media HUB debe presentarse puntualmente a los llamados asignados, mantener una comunicacion profesional con clientes y companeros, y reportar cualquier incidente con equipo, unidades moviles (Van/Embarcacion) o instalaciones de forma inmediata al Lider de Proyecto o Administrador.

**Aplica a:** todos los roles.
**Puntos clave:** puntualidad en llamados asignados, comunicacion profesional con clientes y companeros, reporte inmediato de incidentes (equipo, Van Terrestre, Embarcacion Maritima, instalaciones) al Lider de Proyecto o Administrador.

---

## 3. PROTOCOLO DE COLISION DE AGENDA (Mandamiento Operativo)

Antes de insertar o actualizar cualquier registro en `calls`, la capa de aplicacion **debe** ejecutar una consulta de colision:

```sql
SELECT id FROM calls
WHERE location = :location
  AND call_date = :call_date
  AND status NOT IN ('Cancelado')
  AND start_time < :end_time
  AND end_time   > :start_time;
```

Si la consulta devuelve **una o mas filas**, la reserva debe ser **rechazada** con un mensaje claro al usuario (ej. "El Estudio 5 de Mayo ya tiene un llamado confirmado en ese horario"). Este indice esta optimizado por `idx_call_collision` (`location`, `call_date`, `start_time`, `end_time`).

---

## 4. PROTOCOLO DE ANTICIPO (50% MINIMO)

Todo registro nuevo en `calls` nace con `advance_required_pct = 50.00` y `advance_paid = 0`.

- Un llamado **no puede** pasar de `status = 'Pendiente'` a `status = 'Confirmado'` si `advance_paid = 0`.
- El campo `total_amount` (si esta definido) es la base sobre la cual se calcula el anticipo requerido: `total_amount * (advance_required_pct / 100)`.
- Excepciones a este porcentaje requieren que un usuario con rol `Administrador` o `Lider_Proyecto` modifique manualmente `advance_required_pct` para ese llamado especifico, dejando constancia en `notes`.

---

## 5. PROTOCOLO DE ACCESO Y BLOQUEO (Troll Mode)

Complementario a los 4 reglamentos, el sistema aplica una "ley tecnica" de acceso:

- 5 intentos fallidos de password (`users.failed_attempts >= 5`) cambian automaticamente `users.status` a `'Troll_Mode'`.
- Un usuario en `Troll_Mode` o `Suspendido` **no puede iniciar sesion** aunque la contrasena sea correcta.
- Cualquier patron de inyeccion (SQLi/XSS) detectado en un formulario activa `mh_troll_redirect()` y registra el evento en `seguridad.log`.

El detalle tecnico completo de este protocolo esta documentado en `04_ARQUITECTURA_Y_BLINDAJE.md`.

---

## 6. RESUMEN DE CUMPLIMIENTO

| Reglamento | Codigo | Orden | Rol principal afectado |
|---|---|---|---|
| Contrato de Staff | `CONTRATO_STAFF` | 1 | Todos |
| Reglas del Estudio | `REGLAS_ESTUDIO` | 2 | Staff_Tecnico, Lider_Proyecto |
| Reglas por Grabacion | `REGLAS_GRABACION` | 3 | Todos (set activo) |
| Reglas Generales | `REGLAS_GENERALES` | 4 | Todos |

**Estado de cumplimiento por usuario:** se consulta en tiempo real con
```sql
SELECT d.code, d.title, uls.signed, uls.signed_at
FROM legal_documents d
LEFT JOIN user_legal_signatures uls ON uls.document_id = d.id AND uls.user_id = :user_id
ORDER BY d.sort_order;
```

---

*Estos 4 reglamentos son inmutables en su texto base (version 1.0). Cualquier reforma debe pasar por un nuevo registro versionado en `legal_documents` y notificarse a todo el organigrama antes de exigir nueva firma.*
