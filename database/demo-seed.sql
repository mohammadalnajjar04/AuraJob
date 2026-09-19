-- ============================================================================
-- AuraJob — OPTIONAL Demo Seed Data
-- Job Board & Recruitment Management System
--
-- Fictional development/demo data only.
-- This file is NOT imported by database.sql.
--
-- Prerequisites:
--   1. Import schema.sql
--   2. Import seed.sql (baseline categories)
--   3. Import this file only when demo accounts/data are intentionally wanted
--
-- Demo account password hashes below correspond to the development password:
--     password
--
-- Never use these accounts or hashes in production.
-- Applicant phone values remain NULL so encrypted phone data is never seeded
-- as plaintext and no encryption key is committed.
-- ============================================================================

USE aurajob_db;

-- ----------------------------------------------------------------------------
-- Users
-- id 1: employer  (password = "password")
-- id 2: applicant (password = "password")
-- id 3: applicant (password = "password")
-- ----------------------------------------------------------------------------
INSERT INTO users (id, name, email, password_hash, role, status) VALUES
    (1, 'Alice Manager', 'alice.manager@techcorp.test',
     '$2b$10$g4eNOkjAC6v6XFqdZ4n3oundlgfSYJVtTKN.u2s347ArLiwh1h8jy', 'employer', 'active'),
    (2, 'John Applicant', 'john.applicant@example.test',
     '$2b$10$mSbY9Hk4UttUqAheGdvkXOnDYAb1hEyxRmjiIG0qcr1ruwgqNCsji', 'applicant', 'active'),
    (3, 'Emma Seekers', 'emma.seekers@example.test',
     '$2b$10$/PxcbrnGQ0t0E7m4xOZfJ.YuxZJFTJydMUMDyGFh9H1B72KF/p4du', 'applicant', 'active');

-- ----------------------------------------------------------------------------
-- Company (owned by employer user id 1)
-- ----------------------------------------------------------------------------
INSERT INTO companies (id, user_id, name, description, industry, location, website, contact_email, contact_phone) VALUES
    (1, 1, 'TechCorp Solutions',
     'A fictional software and services company used for local development.',
     'Software', 'Manila, Philippines', 'https://techcorp.example.test',
     'hr@techcorp.example.test', '+63 900 000 0000');

-- ----------------------------------------------------------------------------
-- Applicant profiles
--   user 2 -> profile 1
--   user 3 -> profile 2
--
-- Phone is intentionally NULL in seed data. applicant_profiles.phone is an
-- AES-256-GCM encrypted field whose ciphertext depends on the local
-- APP_ENCRYPTION_KEY and a random nonce. Populate a demo phone through the
-- Applicant Profile form after import so it is encrypted by the application;
-- never seed this field as plaintext or commit an encryption key.
-- ----------------------------------------------------------------------------
INSERT INTO applicant_profiles (id, user_id, professional_title, bio, phone, location, experience_years, skills) VALUES
    (1, 2, 'Senior PHP Developer',
     'Fictional developer profile for local testing.', NULL,
     'Makati City', 6, 'PHP, MySQL, JavaScript'),
    (2, 3, 'Marketing Specialist',
     'Fictional marketing profile for local testing.', NULL,
     'Quezon City', 3, 'SEO, Content Marketing, Analytics');

-- ----------------------------------------------------------------------------
-- Jobs (all posted by company id 1, employer user id 1)
--   job 1: active
--   job 2: active
--   job 3: closed
--   job 4: draft
-- ----------------------------------------------------------------------------
INSERT INTO jobs
    (id, company_id, employer_id, category_id, title, description, requirements,
     location, salary_min, salary_max, employment_type, work_mode, experience_level,
     application_deadline, status)
VALUES
    (1, 1, 1, 1, 'Senior PHP Developer',
     'Develop and maintain web applications using PHP and MySQL.',
     'At least 4 years of PHP experience; strong SQL knowledge.',
     'Makati City', 80000.00, 120000.00, 'full_time', 'hybrid', 'senior',
     '2026-12-31', 'active'),
    (2, 1, 1, 4, 'Marketing Specialist',
     'Plan and execute marketing campaigns for our products.',
     'Experience with SEO and analytics tools.',
     'Remote', 50000.00, 70000.00, 'full_time', 'remote', 'mid',
     '2026-11-15', 'active'),
    (3, 1, 1, 2, 'Financial Analyst',
     'Analyze financial data and prepare reports.',
     'Degree in Finance or Accounting; attention to detail.',
     'Manila City', 45000.00, 65000.00, 'contract', 'onsite', 'junior',
     '2026-06-30', 'closed'),
    (4, 1, 1, 1, 'Junior Web Developer',
     'Assist the development team with frontend and backend tasks.',
     'Basic knowledge of HTML, CSS, PHP and MySQL.',
     'Makati City', 25000.00, 35000.00, 'internship', 'onsite', 'entry',
     '2027-01-31', 'draft');

-- ----------------------------------------------------------------------------
-- Applications (no duplicate applicant/job pairs)
--   app 1: applicant 2 -> job 1 (under_review)
--   app 2: applicant 3 -> job 1 (applied)
--   app 3: applicant 2 -> job 2 (interview)
--   app 4: applicant 3 -> job 2 (rejected)
-- ----------------------------------------------------------------------------
INSERT INTO applications
    (id, applicant_id, job_id, status, cover_letter, applied_at)
VALUES
    (1, 2, 1, 'under_review', 'I am very interested in the Senior PHP Developer role.', DEFAULT),
    (2, 3, 1, 'applied', 'Looking forward to joining TechCorp.', DEFAULT),
    (3, 2, 2, 'interview', 'Excited about the marketing opportunity.', DEFAULT),
    (4, 3, 2, 'rejected', 'Here is my application for the Marketing Specialist role.', DEFAULT);

-- ----------------------------------------------------------------------------
-- Saved jobs (bonus feature example; no duplicate applicant/job pairs)
-- ----------------------------------------------------------------------------
INSERT INTO saved_jobs (applicant_id, job_id) VALUES
    (2, 2),
    (3, 1);

-- ----------------------------------------------------------------------------
-- Notifications (optional example)
-- ----------------------------------------------------------------------------
INSERT INTO notifications (user_id, type, message, is_read) VALUES
    (2, 'application_status', 'Your application for Senior PHP Developer is Under Review.', 0),
    (1, 'application_new', 'A new application was submitted for Senior PHP Developer.', 0);

-- ============================================================================
-- End of demo-seed.sql
-- ============================================================================
