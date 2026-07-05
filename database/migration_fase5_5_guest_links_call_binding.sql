-- =====================================================================
-- MEDIA HUB — Migracion Fase 5.5: Enlaces de Invitado atados al Llamado
-- Ejecutar UNA SOLA VEZ sobre tecnidepot_mediahub_db (servidor remoto).
-- Migracion ADITIVA. No se elimina ninguna tabla ni fila existente.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Hasta Fase 5.2, guest_invite_links solo referenciaba el show (program_id),
-- por lo que guest_form.php no podia mostrar fecha/hora reales de
-- grabacion (gap documentado en knowledge/07_UI_MODULOS_Y_PANTALLAS.md
-- Seccion 3.5). Fase 5.5 cierra ese gap: cada enlace se ata opcionalmente
-- a un llamado especifico de calls.
ALTER TABLE guest_invite_links
  ADD COLUMN call_id INT(11) NULL AFTER program_id,
  ADD CONSTRAINT fk_guest_link_call FOREIGN KEY (call_id) REFERENCES calls(id) ON DELETE SET NULL,
  ADD INDEX idx_guest_link_call (call_id);

-- Regla de integridad (validar en PHP, no en CHECK de MySQL): call_id, si
-- se proporciona, debe pertenecer al mismo program_id del enlace
-- (calls.program_id = guest_invite_links.program_id).

SET FOREIGN_KEY_CHECKS = 1;
