-- ============================================================================
-- AuraJob — Baseline Seed Data
-- Job Board & Recruitment Management System
--
-- This file intentionally contains ONLY non-user lookup data required for a
-- clean installation. It creates no demo accounts, companies, jobs,
-- applications, saved jobs, or notifications.
--
-- Run AFTER schema.sql.
-- ============================================================================

USE aurajob_db;

-- ----------------------------------------------------------------------------
-- Job categories
-- ----------------------------------------------------------------------------
INSERT INTO categories (name, slug) VALUES
    ('Information Technology', 'information-technology'),
    ('Finance', 'finance'),
    ('Healthcare', 'healthcare'),
    ('Marketing', 'marketing');

-- ============================================================================
-- End of seed.sql
-- ============================================================================
