CREATE DATABASE IF NOT EXISTS pasos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pasos;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(128) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    phone VARCHAR(16) NULL,
    role ENUM('user', 'administrator', 'professional') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consultations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    professional_id INT UNSIGNED NULL,
    description TEXT NOT NULL,
    contact_preference VARCHAR(40) NOT NULL,
    status ENUM('received', 'under_review', 'scheduled', 'closed') NOT NULL DEFAULT 'received',
    professional_note TEXT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    scheduled_at DATETIME NULL,
    meeting_link VARCHAR(2048) NULL,
    google_event_id VARCHAR(190) NULL,
    INDEX idx_consultation_schedule (professional_id, status, scheduled_at),
    CONSTRAINT fk_consultations_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_consultations_professional
        FOREIGN KEY (professional_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

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

-- Demo administrator: create this email in Firebase Authentication. On first
-- login, crear_sesion.php replaces the placeholder UID with Firebase's real UID.
INSERT INTO users (firebase_uid, name, email, role)
VALUES ('replace-with-firebase-uid', 'Demo administrator', 'admin@pasos.test', 'administrator')
ON DUPLICATE KEY UPDATE role = 'administrator';

INSERT INTO users (firebase_uid, name, email, role)
VALUES ('pending-abogadopasos', 'Abogado PASOS', 'abogadopasos@gmail.com', 'professional')
ON DUPLICATE KEY UPDATE name = VALUES(name), role = 'professional';
