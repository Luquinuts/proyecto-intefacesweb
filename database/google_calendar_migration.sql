
USE pasos;

CREATE TABLE IF NOT EXISTS google_oauth_tokens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  google_email VARCHAR(190) NULL,
  access_token TEXT NULL,
  refresh_token TEXT NULL,
  token_type VARCHAR(40) NULL,
  expires_at DATETIME NULL,
  scope TEXT NULL,
  calendar_id VARCHAR(190) NOT NULL DEFAULT 'primary',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_google_oauth_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE consultations
  ADD COLUMN IF NOT EXISTS professional_id INT UNSIGNED NULL AFTER user_id,
  ADD COLUMN IF NOT EXISTS scheduled_at DATETIME NULL AFTER requested_at,
  ADD COLUMN IF NOT EXISTS meeting_link VARCHAR(2048) NULL AFTER scheduled_at,
  ADD COLUMN IF NOT EXISTS google_event_id VARCHAR(190) NULL AFTER meeting_link;

SET @professional_fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE CONSTRAINT_SCHEMA = DATABASE()
    AND TABLE_NAME = 'consultations'
    AND COLUMN_NAME = 'professional_id'
    AND REFERENCED_TABLE_NAME = 'users'
);
SET @professional_fk_sql = IF(
  @professional_fk_exists = 0,
  'ALTER TABLE consultations ADD CONSTRAINT fk_consultations_professional FOREIGN KEY (professional_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE professional_fk_statement FROM @professional_fk_sql;
EXECUTE professional_fk_statement;
DEALLOCATE PREPARE professional_fk_statement;
