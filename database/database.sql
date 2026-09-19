-- ============================================================================
-- AuraJob — Combined Database Import
-- Generated from database/schema.sql + database/seed.sql
-- Import this file into a fresh MySQL 8+ instance for local/demo setup.
-- ============================================================================

-- ============================================================================
-- AuraJob — Database Schema
-- Job Board & Recruitment Management System
-- Engine: MySQL 8 / InnoDB
-- Charset/Collation: utf8mb4 / utf8mb4_0900_ai_ci (utf8mb4_general_ci suitable too)
-- This schema can be executed top-to-bottom without manual edits.
-- Note: SQL statements in this file are for reference. Passwords are stored as
--       hashes (see seed.sql); application code will generate them with
--       PHP password_hash().
--
-- TABLE CREATION ORDER (dependency-safe):
--   1. users             (no FK)
--   2. applicant_profiles (FK -> users)
--   3. companies         (FK -> users)
--   4. categories        (no FK)
--   5. jobs              (FK -> companies, users, categories)
--   6. applications      (FK -> users, jobs)
--   7. saved_jobs        (junction, FK -> users, jobs)
--   8. notifications     (FK -> users)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- DROP EXISTING (for reproducible dev rebuilds)
-- Order matters: drop dependents before parents.
-- ----------------------------------------------------------------------------
-- Uncomment the following block only if you want to drop and recreate.
-- DROP TABLE IF EXISTS notifications;
-- DROP TABLE IF EXISTS saved_jobs;
-- DROP TABLE IF EXISTS applications;
-- DROP TABLE IF EXISTS jobs;
-- DROP TABLE IF EXISTS categories;
-- DROP TABLE IF EXISTS companies;
-- DROP TABLE IF EXISTS applicant_profiles;
-- DROP TABLE IF EXISTS users;
-- DROP DATABASE IF EXISTS hirepeak_db;

CREATE DATABASE IF NOT EXISTS hirepeak_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

USE hirepeak_db;

-- ----------------------------------------------------------------------------
-- 1. users
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    id             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name           VARCHAR(100)     NOT NULL,
    email          VARCHAR(190)     NOT NULL,
    password_hash  VARCHAR(255)     NOT NULL,
    role           ENUM('applicant','employer') NOT NULL DEFAULT 'applicant',
    status         ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
    created_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- 2. applicant_profiles
-- (One-to-One with users; UNIQUE user_id)
-- ----------------------------------------------------------------------------
CREATE TABLE applicant_profiles (
    id                 INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id            INT UNSIGNED   NOT NULL,
    professional_title VARCHAR(100)   NULL,
    bio                TEXT           NULL,
    phone              VARCHAR(255)   NULL,
    location           VARCHAR(120)   NULL,
    experience_years   TINYINT UNSIGNED NULL,
    skills             TEXT           NULL,
    cv_path            VARCHAR(255)   NULL,
    created_at         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_applicant_profiles_user (user_id),
    CONSTRAINT fk_applicant_profiles_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- 3. companies
-- (One-to-One with users; UNIQUE user_id)
-- ----------------------------------------------------------------------------
CREATE TABLE companies (
    id             INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    user_id        INT UNSIGNED   NOT NULL,
    name           VARCHAR(150)   NOT NULL,
    description    TEXT           NULL,
    industry       VARCHAR(100)   NULL,
    location       VARCHAR(150)   NULL,
    website        VARCHAR(255)   NULL,
    logo_path      VARCHAR(255)   NULL,
    contact_email  VARCHAR(190)   NULL,
    contact_phone  VARCHAR(30)    NULL,
    created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_companies_user (user_id),
    CONSTRAINT fk_companies_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- 4. categories
-- (Reference/lookup data; RESTRICT on delete)
-- ----------------------------------------------------------------------------
CREATE TABLE categories (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name       VARCHAR(80)   NOT NULL,
    slug       VARCHAR(100)  NOT NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- 5. jobs
-- ----------------------------------------------------------------------------
CREATE TABLE jobs (
    id                    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    company_id            INT UNSIGNED  NOT NULL,
    employer_id           INT UNSIGNED  NOT NULL,
    category_id           INT UNSIGNED  NOT NULL,
    title                 VARCHAR(150)  NOT NULL,
    description           TEXT          NOT NULL,
    requirements          TEXT          NULL,
    location              VARCHAR(150)  NOT NULL,
    salary_min            DECIMAL(12,2) NULL,
    salary_max            DECIMAL(12,2) NULL,
    employment_type       ENUM('full_time','part_time','contract','internship','freelance') NOT NULL,
    work_mode             ENUM('onsite','remote','hybrid') NOT NULL DEFAULT 'onsite',
    experience_level      ENUM('entry','junior','mid','senior','lead') NOT NULL DEFAULT 'entry',
    application_deadline  DATE          NULL,
    status                ENUM('active','closed','draft') NOT NULL DEFAULT 'active',
    created_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_jobs_status (status),
    KEY idx_jobs_category (category_id),
    KEY idx_jobs_location (location),
    KEY idx_jobs_company (company_id),
    KEY idx_jobs_employer (employer_id),
    KEY idx_jobs_created (created_at),
    CONSTRAINT fk_jobs_company
        FOREIGN KEY (company_id) REFERENCES companies (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_jobs_employer
        FOREIGN KEY (employer_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_jobs_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- 6. applications
-- (UNIQUE(applicant_id, job_id) prevents duplicate applications)
-- ----------------------------------------------------------------------------
CREATE TABLE applications (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    applicant_id INT UNSIGNED  NOT NULL,
    job_id       INT UNSIGNED  NOT NULL,
    status       ENUM('applied','under_review','interview','accepted','rejected') NOT NULL DEFAULT 'applied',
    cover_letter TEXT          NULL,
    applied_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_applications_applicant_job (applicant_id, job_id),
    KEY idx_applications_applicant (applicant_id),
    KEY idx_applications_job (job_id),
    KEY idx_applications_status (status),
    CONSTRAINT fk_applications_applicant
        FOREIGN KEY (applicant_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_applications_job
        FOREIGN KEY (job_id) REFERENCES jobs (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- 7. saved_jobs
-- (Many-to-Many junction; composite PK prevents duplicate saved jobs)
-- ----------------------------------------------------------------------------
CREATE TABLE saved_jobs (
    applicant_id INT UNSIGNED NOT NULL,
    job_id       INT UNSIGNED NOT NULL,
    saved_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (applicant_id, job_id),
    KEY idx_saved_jobs_job (job_id),
    CONSTRAINT fk_saved_jobs_applicant
        FOREIGN KEY (applicant_id) REFERENCES users (id)
        ON DELETE CASCADE,
    CONSTRAINT fk_saved_jobs_job
        FOREIGN KEY (job_id) REFERENCES jobs (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ----------------------------------------------------------------------------
-- 8. notifications
-- ----------------------------------------------------------------------------
CREATE TABLE notifications (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED  NOT NULL,
    type       ENUM('application_status','application_new','system','job_update') NOT NULL DEFAULT 'system',
    message    VARCHAR(500)  NOT NULL,
    is_read    TINYINT(1)    NOT NULL DEFAULT 0,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_notifications_user (user_id),
    KEY idx_notifications_read (is_read),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================================
-- End of schema.sql
-- ============================================================================


-- ============================================================================
-- AuraJob — Development Seed Data
-- Job Board & Recruitment Management System
-- Fictional data ONLY for local development.
--
-- IMPORTANT — PASSWORDS:
--   All password_hash values below are valid bcrypt hashes of the string:
--       password
--   generated with PHP-style $2y$-compatible bcrypt ($2b$ variant).
--   Use password_verify('password', <hash>) to validate a login in PHP.
--
--   These hashes are DEV-ONLY and fictional. Replace all accounts before any
--   real/production use. NEVER store plaintext passwords.
--
-- Run AFTER schema.sql (which creates hirepeak_db and all tables).
-- All IDs and foreign keys below are consistent with the insert order.
-- ============================================================================

USE hirepeak_db;

-- ----------------------------------------------------------------------------
-- Categories
-- ----------------------------------------------------------------------------
INSERT INTO categories (name, slug) VALUES
    ('Information Technology', 'information-technology'),
    ('Finance', 'finance'),
    ('Healthcare', 'healthcare'),
    ('Marketing', 'marketing');

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
-- End of seed.sql
-- ============================================================================
