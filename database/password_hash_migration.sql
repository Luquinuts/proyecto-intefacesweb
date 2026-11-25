-- Apply only to an existing PASOS installation; never rerun the seed to migrate.
USE pasos;
ALTER TABLE users ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL;
