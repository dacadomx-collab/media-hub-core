-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 13-06-2026 a las 00:24:16
-- Versión del servidor: 11.4.12-MariaDB
-- Versión de PHP: 8.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tecnidepot_mediahub_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calls`
--

CREATE TABLE `calls` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `location` enum('Estudio 5 de Mayo','Locacion Externa','Van Terrestre','Embarcacion Maritima') NOT NULL DEFAULT 'Estudio 5 de Mayo',
  `call_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('Pendiente','Confirmado','Cancelado','Completado') NOT NULL DEFAULT 'Pendiente',
  `advance_required_pct` decimal(5,2) NOT NULL DEFAULT 50.00,
  `advance_paid` tinyint(1) NOT NULL DEFAULT 0,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `call_assignments`
--

CREATE TABLE `call_assignments` (
  `id` int(11) NOT NULL,
  `call_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `task_description` text DEFAULT NULL,
  `status` enum('Pendiente','En Progreso','Completado') NOT NULL DEFAULT 'Pendiente',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `checkinout_log`
--

CREATE TABLE `checkinout_log` (
  `id` int(11) NOT NULL,
  `asset_type` enum('Inventario','Vehiculo') NOT NULL,
  `asset_id` int(11) NOT NULL,
  `call_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('Check-In','Check-Out') NOT NULL,
  `condition_notes` text DEFAULT NULL,
  `logged_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `company` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `clients`
--

INSERT INTO `clients` (`id`, `full_name`, `email`, `phone`, `company`, `created_at`) VALUES
(1, 'Dr. Efrain Torres', NULL, NULL, 'Medicina del Siglo XXI', '2026-06-13 00:22:24'),
(2, 'Efrain Torres', NULL, NULL, 'CCBCS', '2026-06-13 00:22:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fleet_vehicles`
--

CREATE TABLE `fleet_vehicles` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` enum('Van Terrestre','Embarcacion Maritima') NOT NULL,
  `registration` varchar(60) DEFAULT NULL,
  `status` enum('Disponible','En Uso','Mantenimiento') NOT NULL DEFAULT 'Disponible',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `category` enum('Camara','Optica','Luz LED','Audio','Otro') NOT NULL DEFAULT 'Otro',
  `serial_number` varchar(80) DEFAULT NULL,
  `status` enum('Disponible','En Uso','Mantenimiento') NOT NULL DEFAULT 'Disponible',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `legal_documents`
--

CREATE TABLE `legal_documents` (
  `id` int(11) NOT NULL,
  `code` varchar(40) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `version` varchar(10) NOT NULL DEFAULT '1.0',
  `sort_order` int(2) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `legal_documents`
--

INSERT INTO `legal_documents` (`id`, `code`, `title`, `content`, `version`, `sort_order`, `updated_at`) VALUES
(1, 'CONTRATO_STAFF', 'Contrato / Acuerdo de Integrante de Staff', 'Al firmar este acuerdo, el integrante de staff de Media HUB reconoce su vinculacion operativa con el estudio, acepta el codigo de conducta profesional, la confidencialidad sobre proyectos de clientes (Clientes Jornal), el uso responsable del equipo asignado y el cumplimiento de los llamados conforme a la agenda publicada.', '1.0', 1, '2026-06-13 00:22:24'),
(2, 'REGLAS_ESTUDIO', 'Reglas del Estudio 5 de Mayo', 'Queda prohibido el ingreso de liquidos y alimentos dentro del set de grabacion. Se debe respetar el control termico (clima) del estudio manteniendo puertas cerradas durante grabacion activa. El equipo tecnico (camaras, opticas, luces LED) debe mantenerse en las marcas asignadas y reportarse en el inventario antes y despues de cada llamado.', '1.0', 2, '2026-06-13 00:22:24'),
(3, 'REGLAS_GRABACION', 'Reglas por Grabacion (Set Activo)', 'Durante cualquier grabacion activa, todos los celulares deben permanecer en silencio o modo avion. Es obligatorio realizar pruebas de continuidad de audio antes de iniciar. Ninguna persona ajena a la produccion puede ingresar al set sin autorizacion del Lider de Proyecto.', '1.0', 3, '2026-06-13 00:22:24'),
(4, 'REGLAS_GENERALES', 'Reglas Generales de la Empresa', 'Todo el personal de Media HUB debe presentarse puntualmente a los llamados asignados, mantener una comunicacion profesional con clientes y companeros, y reportar cualquier incidente con equipo, unidades moviles (Van/Embarcacion) o instalaciones de forma inmediata al Lider de Proyecto o Administrador.', '1.0', 4, '2026-06-13 00:22:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `programs`
--

INSERT INTO `programs` (`id`, `client_id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 1, 'Medicina del Siglo XXI', 'Programa de entrevistas a especialistas de la salud en el Estudio 5 de Mayo.', 1, '2026-06-13 00:22:24'),
(2, 2, 'CCBCS', 'Programa recurrente del Consejo Coordinador (CCBCS), transmision Simulcast.', 1, '2026-06-13 00:22:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Administrador','Lider_Proyecto','Staff_Tecnico','Chofer_Logistica','Cliente') NOT NULL DEFAULT 'Staff_Tecnico',
  `status` enum('Activo','Suspendido','Troll_Mode') NOT NULL DEFAULT 'Activo',
  `failed_attempts` int(1) NOT NULL DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_legal_signatures`
--

CREATE TABLE `user_legal_signatures` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `signed` tinyint(1) NOT NULL DEFAULT 0,
  `signed_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `calls`
--
ALTER TABLE `calls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_call_program` (`program_id`),
  ADD KEY `fk_call_creator` (`created_by`);

--
-- Indices de la tabla `call_assignments`
--
ALTER TABLE `call_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `call_user` (`call_id`,`user_id`),
  ADD KEY `fk_assignment_user` (`user_id`);

--
-- Indices de la tabla `checkinout_log`
--
ALTER TABLE `checkinout_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_log_call` (`call_id`),
  ADD KEY `fk_log_user` (`user_id`);

--
-- Indices de la tabla `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_number` (`serial_number`);

--
-- Indices de la tabla `legal_documents`
--
ALTER TABLE `legal_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indices de la tabla `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program_client` (`client_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `user_legal_signatures`
--
ALTER TABLE `user_legal_signatures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_document` (`user_id`,`document_id`),
  ADD KEY `fk_signature_document` (`document_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `calls`
--
ALTER TABLE `calls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `call_assignments`
--
ALTER TABLE `call_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `checkinout_log`
--
ALTER TABLE `checkinout_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `fleet_vehicles`
--
ALTER TABLE `fleet_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `legal_documents`
--
ALTER TABLE `legal_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `user_legal_signatures`
--
ALTER TABLE `user_legal_signatures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `calls`
--
ALTER TABLE `calls`
  ADD CONSTRAINT `fk_call_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_call_program` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `call_assignments`
--
ALTER TABLE `call_assignments`
  ADD CONSTRAINT `fk_assignment_call` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_assignment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `checkinout_log`
--
ALTER TABLE `checkinout_log`
  ADD CONSTRAINT `fk_log_call` FOREIGN KEY (`call_id`) REFERENCES `calls` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `fk_program_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `user_legal_signatures`
--
ALTER TABLE `user_legal_signatures`
  ADD CONSTRAINT `fk_signature_document` FOREIGN KEY (`document_id`) REFERENCES `legal_documents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_signature_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
