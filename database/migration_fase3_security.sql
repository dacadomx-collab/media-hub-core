-- ============================================================================
-- MEDIA HUB | MIGRACION FASE 3 - ESCALAMIENTO PERIMETRAL "TROLL MODE"
-- Estandar Oro: InnoDB + utf8mb4 / utf8mb4_general_ci
-- Aplica de forma aditiva sobre tecnidepot_mediahub_db (no destructiva).
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Bitacora de eventos de seguridad (ataques detectados, logins fallidos, etc.)
-- Alimenta el contador de escalamiento por IP.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `event_type` VARCHAR(80) NOT NULL,
  `context` VARCHAR(60) DEFAULT NULL,
  `payload` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_security_logs_ip_time` (`ip_address`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- Bloqueo temporal de IP (3 intentos en 15 minutos -> 30 minutos de bloqueo).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ip_blocks` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `blocked_until` DATETIME NOT NULL,
  `reason` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- Lista negra inmutable del servidor (10 intentos en 15 minutos -> alta
-- permanente). Solo se permite INSERT desde la aplicacion; nunca
-- UPDATE/DELETE para preservar la integridad de la bitacora.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ip_blacklist` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `reason` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ip_blacklist_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
