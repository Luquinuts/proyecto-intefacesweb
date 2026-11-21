-- Migración para integración Google Calendar / Google Meet
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

ALTER TABLE consultations ADD COLUMN google_event_id VARCHAR(190) NULL;
