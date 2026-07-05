-- =====================================================================
-- MEDIA HUB — Migracion Fase 5.8b: Re-seed de legal_document_roles
-- El seed original (Fase 5.8, INSERT...SELECT...CROSS JOIN con UNION ALL
-- anidado) inserto 0 filas en el servidor remoto. Este script usa VALUES
-- explicitos con subconsultas escalares -- mas simple y portable.
-- Ejecutar UNA SOLA VEZ. Idempotente: si ya hay filas, no duplica gracias
-- a la UNIQUE KEY uq_legal_doc_role (document_id, role) ya creada en 5.8.
-- =====================================================================

SET NAMES utf8mb4;

-- REGLAS_ESTUDIO aplica a TODOS los roles (incluye Conductor y Cliente).
INSERT IGNORE INTO legal_document_roles (document_id, role) VALUES
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_ESTUDIO'), 'Super_admin'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_ESTUDIO'), 'Admin'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_ESTUDIO'), 'Lider_Proyecto'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_ESTUDIO'), 'Staff_Tecnico'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_ESTUDIO'), 'Lider_Logistica'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_ESTUDIO'), 'Cliente'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_ESTUDIO'), 'Team'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_ESTUDIO'), 'Conductor');

-- CONTRATO_STAFF, REGLAS_GRABACION, REGLAS_GENERALES: solo staff interno
-- (excluye Conductor y Cliente).
INSERT IGNORE INTO legal_document_roles (document_id, role) VALUES
  ((SELECT id FROM legal_documents WHERE code = 'CONTRATO_STAFF'), 'Super_admin'),
  ((SELECT id FROM legal_documents WHERE code = 'CONTRATO_STAFF'), 'Admin'),
  ((SELECT id FROM legal_documents WHERE code = 'CONTRATO_STAFF'), 'Lider_Proyecto'),
  ((SELECT id FROM legal_documents WHERE code = 'CONTRATO_STAFF'), 'Staff_Tecnico'),
  ((SELECT id FROM legal_documents WHERE code = 'CONTRATO_STAFF'), 'Lider_Logistica'),
  ((SELECT id FROM legal_documents WHERE code = 'CONTRATO_STAFF'), 'Team'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GRABACION'), 'Super_admin'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GRABACION'), 'Admin'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GRABACION'), 'Lider_Proyecto'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GRABACION'), 'Staff_Tecnico'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GRABACION'), 'Lider_Logistica'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GRABACION'), 'Team'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GENERALES'), 'Super_admin'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GENERALES'), 'Admin'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GENERALES'), 'Lider_Proyecto'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GENERALES'), 'Staff_Tecnico'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GENERALES'), 'Lider_Logistica'),
  ((SELECT id FROM legal_documents WHERE code = 'REGLAS_GENERALES'), 'Team');
