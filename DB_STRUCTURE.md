# Media HUB - DB Structure (Fuente de Verdad)

Este documento define la especificacion tecnica oficial del esquema de base de datos para autenticacion y control de acceso en Media HUB.

## Estandar Global de Infraestructura

| Parametro | Valor Oficial | Razon Tecnica |
|---|---|---|
| Motor de almacenamiento | `InnoDB` | Soporte de transacciones ACID, bloqueo por fila y compatibilidad robusta con integridad referencial. |
| Charset | `utf8mb4` | Soporte completo para caracteres multilenguaje y simbolos extendidos. |
| Collation | `utf8mb4_general_ci` | Comparacion case-insensitive consistente para identificadores y texto operativo. |
| Nivel de seguridad | Estandar Oro | Flujo de autenticacion con hash fuerte, monitoreo de intentos fallidos y estados operativos controlados. |

> Regla de cumplimiento: toda tabla de seguridad y autenticacion debe mantenerse en `InnoDB` con cotejamiento `utf8mb4_general_ci`.

## Tabla: `users`

**Proposito funcional**

Gestion centralizada de identidades, roles, estado operativo y telemetria minima de acceso para el modulo de autenticacion de Media HUB.

### Esquema SQL Canonico

```sql
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('Administrador', 'Staff', 'Cliente') NOT NULL DEFAULT 'Staff',
  `status` ENUM('Activo', 'Suspendido', 'Troll_Mode') DEFAULT 'Activo',
  `failed_attempts` INT(1) DEFAULT 0,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### Matriz Tecnica de Campos

| Campo | Tipo de Dato | Nulidad | Key | Default | Descripcion / Logica de Negocio |
|---|---|---|---|---|---|
| `id` | `INT(11)` | `NOT NULL` | `PK` | `AUTO_INCREMENT` | Identificador interno unico del registro. Uso tecnico para joins y trazabilidad. |
| `user_id` | `VARCHAR(50)` | `NOT NULL` | `UNIQUE` | - | Identificador funcional unico (ej. talento/staff). Debe ser estable y no reutilizable. |
| `full_name` | `VARCHAR(100)` | `NOT NULL` | - | - | Nombre visible para operaciones, reportes y auditoria humana. |
| `password_hash` | `VARCHAR(255)` | `NOT NULL` | - | - | Almacena hash de credencial, nunca contrasena en texto plano. |
| `role` | `ENUM('Administrador','Staff','Cliente')` | `NOT NULL` | - | `'Staff'` | Control de autorizacion por perfil. Define alcance de permisos en la aplicacion. |
| `status` | `ENUM('Activo','Suspendido','Troll_Mode')` | `NULL` permitido | - | `'Activo'` | Estado operativo de cuenta. Permite aislar sesiones y bloquear acceso sin eliminar usuario. |
| `failed_attempts` | `INT(1)` | `NULL` permitido | - | `0` | Contador de intentos fallidos para mitigacion de fuerza bruta y accion automatica de seguridad. |
| `last_login` | `DATETIME` | `NULL` permitido | - | `NULL` | Ultimo acceso exitoso. Soporta auditoria, deteccion de anomalias y metricas de actividad. |
| `created_at` | `TIMESTAMP` | `NULL` permitido | - | `CURRENT_TIMESTAMP` | Marca de tiempo de alta del usuario para trazabilidad temporal. |

## Seguridad (Estandar Oro)

### 1) Credenciales: `password_hash` con Bcrypt

- La columna `password_hash` existe para guardar el resultado de `password_hash()` de PHP con algoritmo `PASSWORD_BCRYPT`.
- Validacion obligatoria en login: `password_verify($passwordIngresado, $password_hashAlmacenado)`.
- Prohibido almacenar o loguear contrasenas en texto plano en cualquier capa (frontend, backend, logs).
- Recomendacion operativa: evaluar `password_needs_rehash()` en autenticaciones exitosas para rotacion progresiva de costo de hash.

### 2) Estado de cuenta: `status` y Troll Mode

- `Activo`: usuario habilitado para autenticacion normal.
- `Suspendido`: acceso bloqueado por decision administrativa o politica interna.
- `Troll_Mode`: estado de contencion para actividad sospechosa (abuso, patrones de ataque o comportamiento anomalo).

Regla sugerida de negocio para seguridad automatizada:

1. Incrementar `failed_attempts` por intento fallido.
2. Al alcanzar umbral definido (ej. 5), transicionar a `Troll_Mode`.
3. En `Troll_Mode`, rechazar autenticacion y registrar evento de seguridad.
4. Resetear `failed_attempts` a `0` tras login exitoso autorizado o desbloqueo manual.

### 3) Roles de acceso

- `Administrador`: control total del HUB (gestion de usuarios, seguridad y configuracion).
- `Staff`: operacion diaria de modulos habilitados por negocio.
- `Cliente`: acceso restringido a recursos permitidos por contrato o relacion comercial.

## Reglas de Implementacion para Nuevos Desarrolladores

1. No modificar `ENUM` ni defaults de `role`/`status` sin aprobacion de arquitectura.
2. Toda consulta de autenticacion debe incluir validacion de `status` ademas de contrasena.
3. Mantener migraciones compatibles con `InnoDB` y `utf8mb4_general_ci`.
4. Cualquier ajuste de seguridad debe reflejarse primero en este documento antes de desplegarse.
