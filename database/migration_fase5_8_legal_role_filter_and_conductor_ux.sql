-- =====================================================================
-- MEDIA HUB — Migracion Fase 5.8: Firmas Legales por Rol + UX del
-- Conductor (tema de episodio, notas, contacto opt-in)
-- Ejecutar UNA SOLA VEZ sobre tecnidepot_mediahub_db (servidor remoto).
-- Migracion ADITIVA. No se elimina ninguna tabla, columna ni fila existente.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- BLOQUE 1 — Filtrado de reglamentos exigidos por rol
-- =====================================================================
-- Tabla de mapeo: que roles deben firmar que documento. Sin esta tabla,
-- el gate de firmas (api/login.php, api/auth_guard.php, legal/firma.php)
-- no tiene forma de saber que REGLAS_ESTUDIO aplica a todos pero
-- CONTRATO_STAFF/REGLAS_GRABACION/REGLAS_GENERALES son solo para staff
-- interno. NO se toca `user_legal_signatures` (sigue siendo el producto
-- cartesiano users x legal_documents ya sembrado) -- la logica de PHP
-- (siguiente hito) simplemente IGNORARA las filas de documentos que no
-- apliquen al rol del usuario al contar pendientes.
CREATE TABLE IF NOT EXISTS legal_document_roles (
  id          INT(11) NOT NULL AUTO_INCREMENT,
  document_id INT(11) NOT NULL,
  role        ENUM('Super_admin','Admin','Lider_Proyecto','Staff_Tecnico','Lider_Logistica','Cliente','Team','Conductor') NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_legal_doc_role (document_id, role),
  CONSTRAINT fk_legal_doc_role_document FOREIGN KEY (document_id) REFERENCES legal_documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed: REGLAS_ESTUDIO aplica a TODOS los roles (incluye Conductor y Cliente).
INSERT INTO legal_document_roles (document_id, role)
SELECT d.id, r.role
FROM legal_documents d
CROSS JOIN (
  SELECT 'Super_admin' AS role UNION ALL SELECT 'Admin' UNION ALL SELECT 'Lider_Proyecto'
  UNION ALL SELECT 'Staff_Tecnico' UNION ALL SELECT 'Lider_Logistica' UNION ALL SELECT 'Cliente'
  UNION ALL SELECT 'Team' UNION ALL SELECT 'Conductor'
) r
WHERE d.code = 'REGLAS_ESTUDIO';

-- Seed: CONTRATO_STAFF, REGLAS_GRABACION, REGLAS_GENERALES aplican SOLO a
-- staff interno -- EXCLUYE Conductor (talento externo, confirmado) y
-- Cliente (rol externo, asumido por simetria -- confirmar con el Arquitecto).
INSERT INTO legal_document_roles (document_id, role)
SELECT d.id, r.role
FROM legal_documents d
CROSS JOIN (
  SELECT 'Super_admin' AS role UNION ALL SELECT 'Admin' UNION ALL SELECT 'Lider_Proyecto'
  UNION ALL SELECT 'Staff_Tecnico' UNION ALL SELECT 'Lider_Logistica' UNION ALL SELECT 'Team'
) r
WHERE d.code IN ('CONTRATO_STAFF', 'REGLAS_GRABACION', 'REGLAS_GENERALES');

-- Snapshot inmutable del firmante (Fase 5.8): signed_at (DATETIME) e
-- ip_address ya existian desde Fase 1 -- se agrega el nombre completo
-- CONGELADO al momento de firmar, para que la evidencia legal no dependa
-- de que `users.full_name` no cambie despues (ej. correccion de nombre).
ALTER TABLE user_legal_signatures
  ADD COLUMN signer_full_name VARCHAR(150) NULL AFTER ip_address;

-- =====================================================================
-- BLOQUE 2 — UX del Conductor: tema de episodio, notas, canal afiliado
-- =====================================================================

-- Tema/eje central de CADA episodio (varia por llamado, no por show).
ALTER TABLE calls
  ADD COLUMN episode_theme TEXT NULL AFTER notes;

-- Recomendaciones del Conductor: se escriben UNA VEZ por show (no por
-- llamado) y las leen todos los invitados de ese show.
ALTER TABLE programs
  ADD COLUMN conductor_notes TEXT NULL AFTER production_schedule;

-- Medio/canal afiliado del show nativo (ej. "El Jornal BCS"). Los shows
-- nativos NO tienen client_id (confirmado Fase 5.7) -- este es un campo de
-- texto libre para el nombre del medio, NO una fila de `clients`. El link
-- de Facebook/redes del medio ya tiene hogar en la columna JSON existente
-- `programs.public_social_links` -- no se duplica aqui.
ALTER TABLE programs
  ADD COLUMN affiliated_channel VARCHAR(150) NULL AFTER conductor_notes;

-- =====================================================================
-- BLOQUE 3 — Contacto Opt-In del Conductor (perfil de usuario)
-- =====================================================================
-- El WhatsApp del Conductor no existia en `users` (solo email). Los
-- toggles de visibilidad son POR USUARIO (perfil), no por show -- el
-- Conductor decide una vez si su WhatsApp/Email son publicos, y esa
-- preferencia aplica a todos los guest_form.php que genere.
ALTER TABLE users
  ADD COLUMN whatsapp VARCHAR(30) NULL AFTER email,
  ADD COLUMN show_whatsapp_publicly TINYINT(1) NOT NULL DEFAULT 0 AFTER whatsapp,
  ADD COLUMN show_email_publicly TINYINT(1) NOT NULL DEFAULT 0 AFTER show_whatsapp_publicly;

SET FOREIGN_KEY_CHECKS = 1;
