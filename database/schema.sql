-- ============================================================================
-- HirePeak — Database Schema
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
