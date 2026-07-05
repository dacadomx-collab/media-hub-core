-- =====================================================================
-- MEDIA HUB — Migracion Fase 5.3: Identidad Email-Only
-- Ejecutar UNA SOLA VEZ sobre tecnidepot_mediahub_db (servidor remoto).
-- Migracion ADITIVA (ensanchar columna, sin perdida de datos existentes).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- users.user_id pasa de VARCHAR(50) a VARCHAR(150) para poder alojar el
-- correo electronico completo como identificador unico (Hito 1, Fase 5.3:
-- se elimina el campo manual "ID de usuario" de los formularios -- el
-- backend usa el email como user_id internamente). VARCHAR(150) iguala el
-- ancho de la columna users.email para evitar truncamientos futuros.
ALTER TABLE users
  MODIFY COLUMN user_id VARCHAR(150) NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;
