-- ============================================================================
-- Migration 2026-09-14 — widen applicant_profiles.phone for AES-256-GCM storage
-- ============================================================================
-- The design (database/ERD.md) targets applicant_profiles.phone for reversible
-- application-layer encryption (AES-256-GCM). The versioned payload
-- "v1:base64(nonce+tag+ciphertext)" needs ~80+ characters even for short
-- numbers, which does not fit the original VARCHAR(30). This migration widens
-- the column to VARCHAR(255) (same size as cv_path) so encrypted values fit.
-- No data is lost; existing plaintext rows are untouched (the application
-- layer migrates them lazily on the next profile save).
-- ============================================================================

ALTER TABLE applicant_profiles
    MODIFY COLUMN phone VARCHAR(255) NULL;