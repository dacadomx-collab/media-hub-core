-- =====================================================================
-- MEDIA HUB — Migracion Fase 5.2: Calendario de Produccion de Shows Nativos
-- Ejecutar UNA SOLA VEZ sobre tecnidepot_mediahub_db (servidor remoto).
-- Migracion ADITIVA. No se elimina ninguna tabla ni fila existente.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- production_schedule es DISTINTA de public_social_links (esa columna ya
-- tiene un proposito propio -- redes sociales publicas del show -- y ya es
-- consumida como texto por el catalogo publico de index.php). No se
-- reutiliza para evitar romper esa vista y para no mezclar dos conceptos
-- en una sola columna (Mandamiento 10).
ALTER TABLE programs
  ADD COLUMN production_schedule JSON NULL AFTER public_social_links;

-- Forma esperada del JSON (validada/generada en PHP, no en SQL):
--   { "days": ["Lunes","Miercoles"], "start_time": "18:00", "end_time": "20:00" }

SET FOREIGN_KEY_CHECKS = 1;
