-- ============================================================================
-- MEDIA HUB | MIGRACION ADITIVA FASE 2
-- Ejecutar UNA VEZ sobre tecnidepot_mediahub_db (phpMyAdmin / consola MySQL).
-- No elimina ni reescribe tablas existentes: solo agrega columnas/tablas.
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. Baja logica de Clientes Jornal (gestion de estados, igual que `programs`).
-- ----------------------------------------------------------------------------
ALTER TABLE `clients`
  ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `company`;

-- ----------------------------------------------------------------------------
-- 2. CHECKLISTS OPERATIVOS (Antes / Durante / Despues)
-- ----------------------------------------------------------------------------
-- Catalogo maestro de tareas por fase. Es el mismo para todos los llamados;
-- el progreso especifico por llamado vive en `call_checklist_progress`.
CREATE TABLE IF NOT EXISTS `checklist_templates` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `phase` ENUM('Antes','Durante','Despues') NOT NULL,
  `item_key` VARCHAR(60) NOT NULL,
  `item_label` VARCHAR(255) NOT NULL,
  `sort_order` INT(3) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phase_item_key` (`phase`,`item_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Progreso del checklist por llamado. Marcar `is_checked` = 1 deja firma
-- digital (checked_by + checked_at) del usuario autenticado.
CREATE TABLE IF NOT EXISTS `call_checklist_progress` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `call_id` INT(11) NOT NULL,
  `template_id` INT(11) NOT NULL,
  `is_checked` TINYINT(1) NOT NULL DEFAULT 0,
  `checked_by` INT(11) DEFAULT NULL,
  `checked_at` DATETIME DEFAULT NULL,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_template` (`call_id`,`template_id`),
  CONSTRAINT `fk_progress_call` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_template` FOREIGN KEY (`template_id`) REFERENCES `checklist_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 3. RECUPERACION SEGURA DE CONTRASENA (token HMAC, expiracion 1h)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_user` (`user_id`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- DATOS SEMILLA: Catalogo de checklists operativos de set.
-- ============================================================================
INSERT INTO `checklist_templates` (`phase`, `item_key`, `item_label`, `sort_order`) VALUES
-- ANTES (Montaje)
('Antes', 'checkout_hardware',   'Check-out digital de equipo (camaras, opticas, luces, audio) en el modulo de Inventario', 1),
('Antes', 'calibracion_led',     'Calibracion de iluminacion LED fria (temperatura de color y exposicion)', 2),
('Antes', 'encuadres',           'Verificacion de encuadres / framing de cada camara', 3),
('Antes', 'aislamiento_acustico','Aislamiento acustico del set (puertas cerradas, ruido externo controlado)', 4),
('Antes', 'bitrate_simulcast',   'Verificacion de Bitrate para Simulcast Facebook/YouTube (15 minutos antes de salir al aire)', 5),
-- DURANTE (Live)
('Durante', 'monitoreo_gain',    'Monitoreo continuo de ganancia (gain) de audio', 1),
('Durante', 'clima_set',         'Control de clima del set y del servidor (temperatura estable)', 2),
('Durante', 'modo_avion',        'Celulares del personal en set en modo avion', 3),
('Durante', 'prohibicion_liquidos', 'Prohibicion de liquidos y alimentos cerca de las consolas', 4),
-- DESPUES (Desmontaje y Respaldo)
('Despues', 'respaldo_raw',      'Respaldo de archivos RAW/editables en el almacenamiento del HUB', 1),
('Despues', 'limpieza_set',      'Limpieza general del set tras la grabacion', 2),
('Despues', 'apagado_master',    'Apagado seguro del master y equipos de transmision', 3),
('Despues', 'checkin_hardware',  'Check-in digital de equipo en el modulo de Inventario (deslinde de responsabilidad)', 4)
ON DUPLICATE KEY UPDATE `item_label` = VALUES(`item_label`), `sort_order` = VALUES(`sort_order`);
