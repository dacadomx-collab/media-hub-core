-- =====================================================================
-- MEDIA HUB — Migracion Fase 5.1: Remember Me (60 dias) + Onboarding
-- por correo electronico (2 pasos: invitacion -> set_password).
-- Ejecutar UNA SOLA VEZ sobre tecnidepot_mediahub_db (servidor remoto).
-- Migracion ADITIVA. No se elimina ninguna tabla ni fila existente.
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- BLOQUE 1 — users.status: agregar 'Pendiente' (cuenta invitada, sin
-- password establecida todavia). El default se conserva en 'Activo'
-- para no alterar el comportamiento de altas ya existentes (users.php
-- action=create seguira dando de alta en 'Activo' hasta que el
-- siguiente hito de PHP cambie ese flujo al onboarding de 2 pasos).
-- =====================================================================
ALTER TABLE users
  MODIFY COLUMN status ENUM('Activo', 'Suspendido', 'Troll_Mode', 'Pendiente')
  NOT NULL DEFAULT 'Activo';

-- =====================================================================
-- BLOQUE 2 — password_resets: distinguir "reset" (recuperacion) de
-- "activation" (primer password del onboarding por correo). Misma
-- tabla, mismo mecanismo de hash HMAC-SHA256 (CSRF_SECRET) ya usado por
-- api/forgot_password.php / api/reset_password.php — no se duplica
-- logica ni se crea una tabla nueva para esto (Mandamiento 10).
-- =====================================================================
ALTER TABLE password_resets
  ADD COLUMN purpose ENUM('reset', 'activation') NOT NULL DEFAULT 'reset' AFTER user_id;

-- =====================================================================
-- BLOQUE 3 — user_remember_tokens: sesion prolongada "Recuerdame" (60 dias)
-- =====================================================================
CREATE TABLE IF NOT EXISTS user_remember_tokens (
  id           INT(11) NOT NULL AUTO_INCREMENT,
  user_id      INT(11) NOT NULL,
  token_hash   VARCHAR(255) NOT NULL,
  user_agent   VARCHAR(255) NULL,
  ip_address   VARCHAR(45) NULL,
  expires_at   DATETIME NOT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_remember_token_hash (token_hash),
  KEY idx_remember_user (user_id),
  CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Notas de diseno (implementacion PHP pendiente, siguiente hito):
--   - token_hash = hash('sha256', $rawToken) — el valor crudo de 256 bits
--     SOLO vive en la cookie httpOnly del navegador, nunca en BD.
--   - Cookie: HttpOnly, Secure, SameSite=Strict, Max-Age=60 dias.
--   - auth_guard.php debe borrar el token usado y emitir uno nuevo en cada
--     restauracion (rotacion), para reducir la ventana de robo de cookie.
--   - Logout debe hacer DELETE del/los token(s) del usuario + expirar la cookie.
--   - Limpieza de expirados: DELETE FROM user_remember_tokens WHERE expires_at < NOW()
--     (ejecutar periodicamente o de forma perezosa en cada login).

SET FOREIGN_KEY_CHECKS = 1;
