-- ============================================================================
-- MEDIA HUB | MIGRACION FASE 4 - REFACTOR SEMANTICO DE ROLES + SEED OFICIAL
-- Estandar Oro: InnoDB + utf8mb4 / utf8mb4_general_ci
-- Aplica de forma aditiva sobre tecnidepot_mediahub_db (no destructiva).
--
-- Contenido:
--   1) Renombre seguro del valor ENUM 'Chofer_Logistica' -> 'Lider_Logistica'
--      en la columna `users`.`role` (3 pasos: ampliar, migrar datos, cerrar).
--   2) Alta oficial de cuenta Administrador: German Lage (admin.glage).
--      - Firmas legales precargadas (signed = 1) para acceso inmediato.
--   3) Alta oficial de cuenta Lider_Logistica: Gibran Morales
--      (logistica.gmorales).
--      - Firmas legales pendientes (signed = 0) -> forzara firma en su
--        primer inicio de sesion via legal/firma.php.
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1) RENOMBRE SEGURO DEL ENUM `users`.`role`
--    Chofer_Logistica -> Lider_Logistica
-- ----------------------------------------------------------------------------

-- Paso 1: Ampliar el ENUM para soportar ambos valores temporalmente.
ALTER TABLE `users`
  MODIFY `role` ENUM('Administrador','Lider_Proyecto','Staff_Tecnico','Chofer_Logistica','Lider_Logistica','Cliente')
  NOT NULL DEFAULT 'Staff_Tecnico';

-- Paso 2: Migrar las filas existentes al nuevo valor canonico.
UPDATE `users` SET `role` = 'Lider_Logistica' WHERE `role` = 'Chofer_Logistica';

-- Paso 3: Cerrar el ENUM dejando solo los 5 roles definitivos.
ALTER TABLE `users`
  MODIFY `role` ENUM('Administrador','Lider_Proyecto','Staff_Tecnico','Lider_Logistica','Cliente')
  NOT NULL DEFAULT 'Staff_Tecnico';

-- ----------------------------------------------------------------------------
-- 2) ALTA OFICIAL: German Lage (Administrador)
--    Password temporal: MediaHub2026!  (Bcrypt cost=12)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `status`)
VALUES (
  'admin.glage',
  'German Lage',
  'leolageacadep@gmail.com',
  '$2y$12$VipJvx7A5hDZmztarEjcXeBnSuti8Kbg2TolBVx9mi.QQu7LOiVFu',
  'Administrador',
  'Activo'
);

-- Precarga de firmas legales aceptadas (acceso directo al Dashboard sin bloqueo).
INSERT IGNORE INTO `user_legal_signatures` (`user_id`, `document_id`, `signed`, `signed_at`, `ip_address`)
SELECT u.`id`, ld.`id`, 1, NOW(), '127.0.0.1'
FROM `users` u
CROSS JOIN `legal_documents` ld
WHERE u.`user_id` = 'admin.glage';

-- ----------------------------------------------------------------------------
-- 3) ALTA OFICIAL: Gibran Morales (Lider_Logistica)
--    Password temporal: MediaHub2026!  (Bcrypt cost=12)
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `status`)
VALUES (
  'logistica.gmorales',
  'Gibran Morales',
  'gibranmorales700@gmail.com',
  '$2y$12$t1FMFKMXzTkfulEQ5GD4Z.e1RfP4ReLR8GljMBMxlo646Qqp6skwy',
  'Lider_Logistica',
  'Activo'
);

-- Firmas legales pendientes -> forzara firma de reglamentos en su primer login.
INSERT IGNORE INTO `user_legal_signatures` (`user_id`, `document_id`, `signed`)
SELECT u.`id`, ld.`id`, 0
FROM `users` u
CROSS JOIN `legal_documents` ld
WHERE u.`user_id` = 'logistica.gmorales';
