-- ============================================================================
-- MEDIA HUB | SCRIPT DE INICIALIZACION DE BASE DE DATOS (FASE 1)
-- Estandar Oro: InnoDB + utf8mb4 / utf8mb4_general_ci
-- Fuente de verdad complementaria: DB_STRUCTURE.md
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. MODULO DE USUARIOS Y ORGANIGRAMA DIGITAL
-- ----------------------------------------------------------------------------
-- Roles operativos del HUB:
--   Administrador     -> Control total del sistema.
--   Lider_Proyecto     -> Supervisa programas, agenda y staff (ej. German Lage).
--   Staff_Tecnico      -> Personal de produccion en locacion (ej. Gibran Morales, Antonio Murillo).
--   Chofer_Logistica   -> Operacion de unidades moviles (Van / Embarcacion).
--   Cliente            -> Acceso restringido a su propia agenda/programas.
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(50) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('Administrador','Lider_Proyecto','Staff_Tecnico','Chofer_Logistica','Cliente') NOT NULL DEFAULT 'Staff_Tecnico',
  `status` ENUM('Activo','Suspendido','Troll_Mode') NOT NULL DEFAULT 'Activo',
  `failed_attempts` INT(1) NOT NULL DEFAULT 0,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 2. MODULO LEGAL (Reglamentos inmutables + Firma Digital Obligatoria)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `legal_documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `version` VARCHAR(10) NOT NULL DEFAULT '1.0',
  `sort_order` INT(2) NOT NULL DEFAULT 0,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bandera de firma por usuario y documento. Si existe al menos un registro
-- con `signed` = 0 (o ausente) para el usuario, el login debe forzar
-- la redireccion a legal/firma.php antes de permitir acceso al Dashboard.
CREATE TABLE IF NOT EXISTS `user_legal_signatures` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `document_id` INT(11) NOT NULL,
  `signed` TINYINT(1) NOT NULL DEFAULT 0,
  `signed_at` DATETIME DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_document` (`user_id`,`document_id`),
  CONSTRAINT `fk_signature_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_signature_document` FOREIGN KEY (`document_id`) REFERENCES `legal_documents` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 3. MODULO DE PROGRAMAS Y CLIENTES JORNAL
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `company` VARCHAR(120) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `programs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `client_id` INT(11) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program_client` (`client_id`),
  CONSTRAINT `fk_program_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 4. MODULO DE AGENDA Y CALENDARIO INTELIGENTE (Control de Colisiones)
-- ----------------------------------------------------------------------------
-- Regla de negocio: antes de INSERT/UPDATE, la capa de aplicacion debe
-- ejecutar un query de colision sobre `calls` filtrando por `location`,
-- `call_date` y solapamiento de `start_time`/`end_time` (start < existing_end
-- AND end > existing_start) para impedir doble booking del Estudio 5 de Mayo.
CREATE TABLE IF NOT EXISTS `calls` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `program_id` INT(11) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `location` ENUM('Estudio 5 de Mayo','Locacion Externa','Van Terrestre','Embarcacion Maritima') NOT NULL DEFAULT 'Estudio 5 de Mayo',
  `call_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('Pendiente','Confirmado','Cancelado','Completado') NOT NULL DEFAULT 'Pendiente',
  `advance_required_pct` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
  `advance_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(10,2) DEFAULT NULL,
  `notes` TEXT,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_call_program` (`program_id`),
  KEY `idx_call_collision` (`location`,`call_date`,`start_time`,`end_time`),
  CONSTRAINT `fk_call_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_call_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tareas/responsabilidades especificas asignadas al staff por llamado.
-- Esto alimenta la lista de tareas que cada perfil ve en su sesion.
CREATE TABLE IF NOT EXISTS `call_assignments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `call_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `task_description` TEXT,
  `status` ENUM('Pendiente','En Progreso','Completado') NOT NULL DEFAULT 'Pendiente',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `call_user` (`call_id`,`user_id`),
  CONSTRAINT `fk_assignment_call` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_assignment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ----------------------------------------------------------------------------
-- 5. MODULO DE INVENTARIOS DINAMICOS Y FLOTA (Check-In / Check-Out)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `category` ENUM('Camara','Optica','Luz LED','Audio','Otro') NOT NULL DEFAULT 'Otro',
  `serial_number` VARCHAR(80) DEFAULT NULL,
  `status` ENUM('Disponible','En Uso','Mantenimiento') NOT NULL DEFAULT 'Disponible',
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serial_number` (`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `fleet_vehicles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `type` ENUM('Van Terrestre','Embarcacion Maritima') NOT NULL,
  `registration` VARCHAR(60) DEFAULT NULL,
  `status` ENUM('Disponible','En Uso','Mantenimiento') NOT NULL DEFAULT 'Disponible',
  `notes` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bitacora de Check-In/Check-Out digital. `asset_type` + `asset_id` apunta
-- a `inventory_items.id` o `fleet_vehicles.id` segun corresponda.
CREATE TABLE IF NOT EXISTS `checkinout_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `asset_type` ENUM('Inventario','Vehiculo') NOT NULL,
  `asset_id` INT(11) NOT NULL,
  `call_id` INT(11) DEFAULT NULL,
  `user_id` INT(11) NOT NULL,
  `action` ENUM('Check-In','Check-Out') NOT NULL,
  `condition_notes` TEXT,
  `logged_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_asset` (`asset_type`,`asset_id`),
  CONSTRAINT `fk_log_call` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- DATOS SEMILLA (SEED DATA)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Usuarios del organigrama digital.
-- Password temporal para TODAS las cuentas semilla: MediaHub2026!
-- (hash bcrypt generado con password_hash() / PASSWORD_BCRYPT)
-- Las cuentas inician con las 4 banderas legales en FALSE -> forzaran firma.
-- ----------------------------------------------------------------------------
INSERT INTO `users` (`user_id`, `full_name`, `email`, `password_hash`, `role`, `status`) VALUES
('admin.root',        'Administrador Media HUB', 'admin@mediahubbcs.com',     '$2y$10$O7R.0vYy9/RpA0XnzNwL0urswEgjDo8vVwY/wiq/WwdL8QP00PRgq', 'Administrador',   'Activo'),
('lider.glage',       'German Lage',             'german.lage@mediahubbcs.com', '$2y$10$O7R.0vYy9/RpA0XnzNwL0urswEgjDo8vVwY/wiq/WwdL8QP00PRgq', 'Lider_Proyecto',  'Activo'),
('staff.gmorales',    'Gibran Morales',          'gibran.morales@mediahubbcs.com', '$2y$10$O7R.0vYy9/RpA0XnzNwL0urswEgjDo8vVwY/wiq/WwdL8QP00PRgq', 'Staff_Tecnico',   'Activo'),
('staff.amurillo',    'Antonio Murillo',         'antonio.murillo@mediahubbcs.com', '$2y$10$O7R.0vYy9/RpA0XnzNwL0urswEgjDo8vVwY/wiq/WwdL8QP00PRgq', 'Staff_Tecnico',   'Activo'),
('chofer.logistica1', 'Chofer Logistica 1',      'chofer1@mediahubbcs.com',   '$2y$10$O7R.0vYy9/RpA0XnzNwL0urswEgjDo8vVwY/wiq/WwdL8QP00PRgq', 'Chofer_Logistica', 'Activo');

-- ----------------------------------------------------------------------------
-- Modulo Legal: 4 reglamentos inmutables.
-- ----------------------------------------------------------------------------
INSERT INTO `legal_documents` (`code`, `title`, `content`, `version`, `sort_order`) VALUES
('CONTRATO_STAFF',
 'Contrato / Acuerdo de Integrante de Staff',
 'Al firmar este acuerdo, el integrante de staff de Media HUB reconoce su vinculacion operativa con el estudio, acepta el codigo de conducta profesional, la confidencialidad sobre proyectos de clientes (Clientes Jornal), el uso responsable del equipo asignado y el cumplimiento de los llamados conforme a la agenda publicada.',
 '1.0', 1),
('REGLAS_ESTUDIO',
 'Reglas del Estudio 5 de Mayo',
 'Queda prohibido el ingreso de liquidos y alimentos dentro del set de grabacion. Se debe respetar el control termico (clima) del estudio manteniendo puertas cerradas durante grabacion activa. El equipo tecnico (camaras, opticas, luces LED) debe mantenerse en las marcas asignadas y reportarse en el inventario antes y despues de cada llamado.',
 '1.0', 2),
('REGLAS_GRABACION',
 'Reglas por Grabacion (Set Activo)',
 'Durante cualquier grabacion activa, todos los celulares deben permanecer en silencio o modo avion. Es obligatorio realizar pruebas de continuidad de audio antes de iniciar. Ninguna persona ajena a la produccion puede ingresar al set sin autorizacion del Lider de Proyecto.',
 '1.0', 3),
('REGLAS_GENERALES',
 'Reglas Generales de la Empresa',
 'Todo el personal de Media HUB debe presentarse puntualmente a los llamados asignados, mantener una comunicacion profesional con clientes y companeros, y reportar cualquier incidente con equipo, unidades moviles (Van/Embarcacion) o instalaciones de forma inmediata al Lider de Proyecto o Administrador.',
 '1.0', 4);

-- Inicializa la matriz de firmas pendientes para cada usuario semilla
-- (signed = 0) contra cada uno de los 4 documentos legales.
INSERT INTO `user_legal_signatures` (`user_id`, `document_id`, `signed`)
SELECT u.id, d.id, 0
FROM `users` u
CROSS JOIN `legal_documents` d;

-- ----------------------------------------------------------------------------
-- Clientes Jornal y Programas recurrentes (casos de exito reales).
-- ----------------------------------------------------------------------------
INSERT INTO `clients` (`full_name`, `company`) VALUES
('Dr. Efrain Torres', 'Medicina del Siglo XXI'),
('Efrain Torres', 'CCBCS');

INSERT INTO `programs` (`client_id`, `name`, `description`, `is_active`) VALUES
((SELECT id FROM `clients` WHERE `full_name` = 'Dr. Efrain Torres' LIMIT 1),
 'Medicina del Siglo XXI',
 'Programa recurrente de entrevistas a especialistas de la salud, producido en el Estudio 5 de Mayo.',
 1),
((SELECT id FROM `clients` WHERE `full_name` = 'Efrain Torres' AND `company` = 'CCBCS' LIMIT 1),
 'CCBCS',
 'Programa recurrente del Consejo Coordinador (CCBCS), produccion y transmision Simulcast.',
 1);

-- ----------------------------------------------------------------------------
-- Inventario y flota base.
-- ----------------------------------------------------------------------------
INSERT INTO `inventory_items` (`name`, `category`, `serial_number`, `status`) VALUES
('Camara Principal Set A', 'Camara', 'CAM-A-001', 'Disponible'),
('Camara Secundaria Set B', 'Camara', 'CAM-B-002', 'Disponible'),
('Optica 24-70mm f2.8', 'Optica', 'LENS-2470-001', 'Disponible'),
('Panel LED Frio 1', 'Luz LED', 'LED-001', 'Disponible'),
('Panel LED Frio 2', 'Luz LED', 'LED-002', 'Disponible'),
('Kit Microfonia Lavalier', 'Audio', 'AUD-LAV-001', 'Disponible');

INSERT INTO `fleet_vehicles` (`name`, `type`, `registration`, `status`) VALUES
('Van de Produccion BCS-01', 'Van Terrestre', 'BCS-0145-A', 'Disponible'),
('Embarcacion Mar de Cortes', 'Embarcacion Maritima', 'MX-MC-002', 'Disponible');
