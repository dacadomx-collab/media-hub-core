-- =====================================================================
-- MEDIA HUB — Migracion Fase 5.7: Alta de Show Nativo "Enfoque 360"
-- Ejecutar UNA SOLA VEZ sobre tecnidepot_mediahub_db (servidor remoto).
-- Migracion ADITIVA. No se elimina ninguna tabla ni fila existente.
--
-- Contexto (confirmado con el Arquitecto): Enfoque 360, El Ring y En
-- Privado son SHOWS NATIVOS de Media HUB (is_native_show=1, sin
-- client_id), cada uno con su propio Conductor. "David de la Paz" es el
-- contacto/representante del holding, NO una fila de la tabla `clients`.
-- Pepe Pecas (users.id = 31, dacado12_mx@hotmail.com) ya existe como
-- usuario con role='Conductor' -- este script SOLO crea el programa y lo
-- amarra a el via conductor_user_id, sin tocar la tabla `users`.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

INSERT INTO programs (client_id, name, description, catalog_description, is_native_show, conductor_user_id, is_active)
VALUES (NULL, 'Enfoque 360', 'Show nativo de Media HUB', 'Enfoque 360 — produccion propia de Media HUB', 1, 31, 1);

-- El Ring y En Privado quedan registrados SIN conductor todavia (se
-- asignara despues con un Conductor real distinto de Pepe Pecas, via
-- api/programs.php?action=create_native o UPDATE explicito futuro):
INSERT INTO programs (client_id, name, description, catalog_description, is_native_show, conductor_user_id, is_active)
VALUES (NULL, 'El Ring', 'Show nativo de Media HUB', 'El Ring — produccion propia de Media HUB', 1, NULL, 1);

INSERT INTO programs (client_id, name, description, catalog_description, is_native_show, conductor_user_id, is_active)
VALUES (NULL, 'En Privado', 'Show nativo de Media HUB', 'En Privado — produccion propia de Media HUB', 1, NULL, 1);

SET FOREIGN_KEY_CHECKS = 1;
