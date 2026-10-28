CREATE DATABASE IF NOT EXISTS pasos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pasos;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(128) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    role ENUM('user', 'administrator', 'professional') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS consultations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    contact_preference VARCHAR(40) NOT NULL,
    status ENUM('received', 'under_review', 'scheduled', 'closed') NOT NULL DEFAULT 'received',
    professional_note TEXT NULL,
    requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_consultations_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Demo administrator: create this email in Firebase Authentication. On first
-- login, crear_sesion.php replaces the placeholder UID with Firebase's real UID.
INSERT INTO users (firebase_uid, name, email, role)
VALUES ('replace-with-firebase-uid', 'Demo administrator', 'admin@pasos.test', 'administrator')
ON DUPLICATE KEY UPDATE role = 'administrator';
