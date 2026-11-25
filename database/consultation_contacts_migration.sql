-- Run against the selected existing MariaDB schema, never rerun pasos.sql seeds.
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(16) NULL;
CREATE INDEX IF NOT EXISTS idx_consultation_schedule ON consultations (professional_id, status, scheduled_at);
