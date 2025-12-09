-- =============================================================================
-- SQL Migration: Add Assignment Columns to llx_a_predmet
-- =============================================================================
-- Description: Adds columns for user assignment functionality
-- Date: 2025-12-03
-- Author: 8Core Association
-- =============================================================================

-- Add assignment columns to predmet table
ALTER TABLE llx_a_predmet
ADD COLUMN IF NOT EXISTS fk_user_assigned INT DEFAULT NULL COMMENT 'Dodijeljeni korisnik (assigned user)',
ADD COLUMN IF NOT EXISTS date_assigned DATETIME DEFAULT NULL COMMENT 'Datum dodjele',
ADD COLUMN IF NOT EXISTS assigned_by INT DEFAULT NULL COMMENT 'Admin koji je dodjelio',
ADD INDEX IF NOT EXISTS idx_assigned (fk_user_assigned),
ADD INDEX IF NOT EXISTS idx_assigned_by (assigned_by);

-- Add foreign key constraint (if it doesn't exist)
-- Note: This may fail if constraint already exists, but that's okay
ALTER TABLE llx_a_predmet
ADD CONSTRAINT fk_predmet_assigned_user FOREIGN KEY (fk_user_assigned)
    REFERENCES llx_user(rowid) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE llx_a_predmet
ADD CONSTRAINT fk_predmet_assigned_by FOREIGN KEY (assigned_by)
    REFERENCES llx_user(rowid) ON DELETE SET NULL ON UPDATE CASCADE;
