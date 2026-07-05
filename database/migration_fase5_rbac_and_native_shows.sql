-- =====================================================================
-- MEDIA HUB — Migracion Fase 5: RBAC Escalado + Shows Nativos + Guest Links
-- Ejecutar UNA SOLA VEZ sobre tecnidepot_mediahub_db (servidor remoto).
-- Migracion ADITIVA. No se elimina ninguna tabla ni fila existente.
-- Referencia de decisiones: knowledge/02_CODEX_Y_SCHEMA_MAESTRO.md
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- BLOQUE A — RBAC ESCALADO: users.role
-- Reconciliacion: se PRESERVAN los roles operativos ya existentes
-- (Lider_Proyecto, Staff_Tecnico, Lider_Logistica, Cliente) y se agrega
-- la jerarquia de negocio de Fase 5 (Super_admin, Admin, Team, Conductor)
-- como valores adicionales del MISMO enum. 'Administrador' se renombra
-- a 'Admin' (migracion segura de 3 pasos: ampliar -> migrar filas -> cerrar,
-- mismo patron usado en migration_fase4_roles_seed.sql).
-- =====================================================================

-- Paso 1: ampliar el ENUM para que contengan tanto el valor viejo como los nuevos
ALTER TABLE users
  MODIFY COLUMN role ENUM(
    'Administrador',   -- valor legado, se elimina en el Paso 3
    'Lider_Proyecto',
    'Staff_Tecnico',
    'Lider_Logistica',
    'Cliente',
    'Super_admin',
    'Admin',
    'Team',
    'Conductor'
  ) NOT NULL DEFAULT 'Staff_Tecnico';

-- Paso 2: migrar filas existentes del valor legado al nuevo nombre
UPDATE users SET role = 'Admin' WHERE role = 'Administrador';

-- Paso 3: cerrar el ENUM, retirando 'Administrador' ya vacio
ALTER TABLE users
  MODIFY COLUMN role ENUM(
    'Super_admin',
    'Admin',
    'Lider_Proyecto',
    'Staff_Tecnico',
    'Lider_Logistica',
    'Cliente',
    'Team',
    'Conductor'
  ) NOT NULL DEFAULT 'Staff_Tecnico';

-- Alta de David Cabrera — Super_admin (unica cuenta de este rol)
-- Password temporal: MediaHub2026! (mismo patron de altas previas, Bcrypt cost=12)
INSERT INTO users (user_id, full_name, email, password_hash, role, status)
VALUES (
  'super.dcabrera',
  'David Cabrera',
  'davidcabrera@mediahubbcs.com',
  '$2y$12$fsxwxTR7DHgnkc8AvX6V0uAUsQTbuOjnvjwEpsfRIStpqDBsspxu6',
  'Super_admin',
  'Activo'
);

-- Firmas legales precargadas (Super_admin fundador, mismo criterio aplicado
-- a admin.glage en migration_fase4_roles_seed.sql — acceso directo sin bloqueo)
INSERT INTO user_legal_signatures (user_id, document_id, signed, signed_at, ip_address)
SELECT
  (SELECT id FROM users WHERE user_id = 'super.dcabrera'),
  ld.id,
  1,
  NOW(),
  '127.0.0.1'
FROM legal_documents ld;

-- =====================================================================
-- BLOQUE B — TROLL MODE
-- Sin cambios de esquema: la logica de 5 intentos fallidos -> 'Troll_Mode'
-- y el escaneo de patrones (OR 1=1, DROP, <script>) ya viven en
-- api/security.php + users.failed_attempts + users.status (ver
-- knowledge/04_ARQUITECTURA_Y_BLINDAJE.md Seccion 4). No requiere DDL nuevo.
-- =====================================================================

-- =====================================================================
-- BLOQUE C/D — SHOWS NATIVOS: extension de la tabla programs existente
-- Un "show nativo" es un registro de programs con client_id = NULL,
-- is_native_show = 1 y un conductor_user_id asignado. Los programas de
-- Clientes Jornal (client_id NOT NULL) no se ven afectados.
-- =====================================================================

-- client_id pasa a ser NULLABLE (un show nativo no pertenece a un cliente externo)
ALTER TABLE programs
  MODIFY COLUMN client_id INT(11) NULL;

ALTER TABLE programs
  ADD COLUMN is_native_show TINYINT(1) NOT NULL DEFAULT 0 AFTER client_id,
  ADD COLUMN conductor_user_id INT(11) NULL AFTER is_native_show,
  ADD COLUMN catalog_description TEXT NULL AFTER description,
  ADD COLUMN logo_url VARCHAR(255) NULL AFTER catalog_description,
  ADD COLUMN public_social_links JSON NULL AFTER logo_url,
  ADD CONSTRAINT fk_program_conductor
    FOREIGN KEY (conductor_user_id) REFERENCES users(id) ON DELETE SET NULL,
  ADD INDEX idx_program_native (is_native_show);

-- Regla de integridad de negocio (a validar en PHP, MySQL no soporta CHECK
-- condicional entre columnas de forma portable en todas las versiones):
-- is_native_show = 1  => conductor_user_id IS NOT NULL, client_id IS NULL
-- is_native_show = 0  => client_id IS NOT NULL (comportamiento legado intacto)

-- =====================================================================
-- BLOQUE C/D — ENLACES DE INVITADOS CON TTL DE 3 CLICS
-- =====================================================================

CREATE TABLE IF NOT EXISTS guest_invite_links (
  id               INT(11) NOT NULL AUTO_INCREMENT,
  program_id       INT(11) NOT NULL,
  token            VARCHAR(64) NOT NULL,
  created_by       INT(11) NULL,
  click_count      TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  max_clicks       TINYINT(1) UNSIGNED NOT NULL DEFAULT 3,
  status           ENUM('Activo', 'Expirado') NOT NULL DEFAULT 'Activo',
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expired_at       DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_guest_link_token (token),
  KEY idx_guest_link_program (program_id),
  CONSTRAINT fk_guest_link_program
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
  CONSTRAINT fk_guest_link_creator
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS guest_submissions (
  id               INT(11) NOT NULL AUTO_INCREMENT,
  link_id          INT(11) NOT NULL,
  full_name        VARCHAR(120) NOT NULL,
  title_position   VARCHAR(150) NOT NULL,
  social_links     JSON NULL,
  whatsapp         VARCHAR(30) NULL,
  website          VARCHAR(255) NULL,
  email            VARCHAR(150) NULL,
  invite_message   TEXT NULL,
  qa_notes         TEXT NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_guest_submission_link (link_id),
  CONSTRAINT fk_guest_submission_link
    FOREIGN KEY (link_id) REFERENCES guest_invite_links(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Nota de logica de clics (implementacion en PHP, Paso siguiente):
--   Click 1 (click_count 0->1): formulario vacio, INSERT en guest_submissions.
--   Click 2 (click_count 1->2): formulario precargado desde guest_submissions, UPDATE.
--   Click 3 (click_count 2->3): status = 'Expirado', expired_at = NOW(),
--     redirect a la web publica. Cualquier acceso posterior con status='Expirado'
--     deniega y redirige, sin tocar guest_submissions.

SET FOREIGN_KEY_CHECKS = 1;
