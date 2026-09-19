# AuraJob — Database Notes

This document explains the AuraJob database in plain, professional language. It is meant to be read alongside `ERD.md` (the full design reference), `schema.sql` (the maintainable schema source), and `database.sql` (the clean combined schema + baseline-category import used for submission/setup).

## Database Overview

AuraJob is a job board and recruitment management platform with two main roles: **Applicant** (job seekers) and **Employer** (companies posting jobs). The database is a normalized MySQL relational database using the **InnoDB** engine and the **utf8mb4** character set, which fully supports all Unicode text (including emoji and non-Latin scripts).

The database is organized so that:

- Every person who uses the site has one **user account**.
- Role-specific information is kept separate from the shared account data.
- Each company owns its jobs, and each applicant's activity (applications, saved jobs) is tracked cleanly.
- Rules like "an applicant cannot apply twice to the same job" or "an applicant cannot save the same job twice" are enforced **by the database itself**, not just by the application code.

The database name is `aurajob_db`.

---

## Tables

### users

- **Purpose:** One row per registered person. Holds the information every account needs regardless of role.
- **Primary key:** `id`
- **Important columns:**
  - `email` — unique login identifier
  - `password_hash` — a hashed password (generated with PHP `password_hash()`); used with `password_verify()` at login. Never stored as plaintext.
  - `role` — `applicant` or `employer`
  - `status` — `active`, `inactive`, or `banned`
- **Foreign keys:** none (it is the root table)
- **Unique constraints:** `email` must be unique

### applicant_profiles

- **Purpose:** Detailed, applicant-only information (job title, bio, phone, skills, and a placeholder for a future CV). This keeps `users` lean by avoiding many empty (NULL) columns on shared table rows.
- **Primary key:** `id`
- **Important columns:** `professional_title`, `bio`, `phone`, `location`, `experience_years`, `skills`, `cv_path`
- **Foreign keys:** `user_id` → `users.id`
- **Unique constraints:** `user_id` is unique — one applicant profile per applicant.

### companies

- **Purpose:** The company that an employer manages. Companies are where jobs belong.
- **Primary key:** `id`
- **Important columns:** `name`, `description`, `industry`, `location`, `website`, `logo_path`, `contact_email`, `contact_phone`
- **Foreign keys:** `user_id` → `users.id`
- **Unique constraints:** `user_id` is unique — one company per employer.

### categories

- **Purpose:** A dynamic list of job categories (for example, Information Technology, Finance, Healthcare, Marketing). Stored as a table (not an "enum") so categories can be added without changing the schema.
- **Primary key:** `id`
- **Important columns:** `name`, `slug` (a URL-safe text key used for filtering)
- **Foreign keys:** none
- **Unique constraints:** `name` is unique and `slug` is unique.

### jobs

- **Purpose:** A single job posting created by an employer.
- **Primary key:** `id`
- **Important columns:** `title`, `description`, `requirements`, `location`, `salary_min`, `salary_max`, `employment_type`, `work_mode`, `experience_level`, `application_deadline`, and `status`
  - `employment_type` (full_time/part_time/contract/internship/freelance), `work_mode` (onsite/remote/hybrid), and `experience_level` are stored as **enums** because their possible values are fixed and known at design time.
  - `status` (`active`/`closed`/`draft`) controls whether a job is visible and accepting applications.
- **Foreign keys:**
  - `company_id` → `companies.id` (which company posted it)
  - `employer_id` → `users.id` (which employer owns it — makes authorization and "my jobs" queries fast and simple)
  - `category_id` → `categories.id`
- **Unique constraints:** none (multiple jobs may share the same title/category).

### applications

- **Purpose:** Records that a specific applicant applied to a specific job, and tracks where that application is in the recruitment workflow.
- **Primary key:** `id`
- **Important columns:** `status` (applied/under_review/interview/accepted/rejected), `cover_letter`, `applied_at`
- **Foreign keys:** `applicant_id` → `users.id`, `job_id` → `jobs.id`
- **Unique constraints:** the pair `(applicant_id, job_id)` is unique — an applicant cannot apply to the same job more than once.

### saved_jobs

- **Purpose:** A "bookmark" table (a junction table) letting applicants save jobs to view later. This is a **bonus feature** whose schema is reserved now.
- **Primary key:** the composite key `(applicant_id, job_id)` — which also automatically prevents an applicant from saving the same job twice.
- **Important columns:** `saved_at`
- **Foreign keys:** `applicant_id` → `users.id`, `job_id` → `jobs.id`
- **Unique constraints:** enforced by the composite primary key.

### notifications

- **Purpose:** In-app messages sent to a specific user (for example, "your application status changed"). A **bonus feature** whose schema is reserved now.
- **Primary key:** `id`
- **Important columns:** `type` (application_status/application_new/system/job_update), `message`, `is_read` (0 = unread, 1 = read)
- **Foreign keys:** `user_id` → `users.id`
- **Unique constraints:** none.

---

## Relationships

Explained in everyday terms:

- **users → applicant_profiles:** A user *may have* at most one applicant profile (one-to-one). Profiles only exist for applicants who fill them in.
- **users → companies:** An employer user owns *one* company (one-to-one). Not every user is an employer, so a company is optional.
- **companies → jobs:** One company can post *many* jobs (one-to-many). Each job belongs to exactly one company.
- **categories → jobs:** One category can contain *many* jobs (one-to-many). Each job has exactly one category.
- **applicants → applications:** One applicant can submit *many* applications (one-to-many). Each application is tied to one applicant.
- **jobs → applications:** One job can receive *many* applications (one-to-many). Each application is tied to one job.
- **applicants ↔ saved jobs:** Many-to-many, handled by the `saved_jobs` junction table. An applicant can save many jobs, and many applicants can save the same job.
- **users → notifications:** One user can receive *many* notifications (one-to-many). Each notification is addressed to one user.

---

## Important Constraints

- **Unique email:** No two accounts can share the same email address, because the email is the login identifier.
- **One applicant profile per applicant:** The `applicant_profiles.user_id` column is unique, so a user cannot accidentally create multiple profiles.
- **One company per employer:** The `companies.user_id` column is unique, so an employer cannot have multiple company records.
- **Duplicate application prevention:** The unique key on `(applicant_id, job_id)` means an applicant can apply to a job only once. Re-applying is blocked at the database level.
- **Duplicate saved job prevention:** The composite primary key on `saved_jobs (applicant_id, job_id)` means an applicant can save a job only once.

---

## Delete Behavior

When a parent record is deleted, what happens to related records? The rules below are chosen deliberately, not applied blindly.

- **ON DELETE CASCADE** (used on ownership/child relationships):
  - Deleting a **user** removes their: applicant profile, company, jobs, applications, saved jobs, and notifications. Because those records belong to and make no sense without the user, they are cleaned up automatically.
  - Deleting a **company** removes its jobs.
  - Deleting a **job** removes its applications and saved-job links.
  - These cascade rules also protect privacy (for example, removing a user also removes their application data) and prevent "orphan" rows.
- **ON DELETE RESTRICT** (used for reference data):
  - The job-to-**category** relationship uses RESTRICT. A category that still has jobs attached **cannot be deleted**. Preventing the deletion protects job data from being corrupted. The developer must first reassign or delete the affected jobs.
- **ON DELETE SET NULL:** Not used. Every foreign key in this design represents a mandatory ownership link — a child record has no meaning without its parent. Optional values such as salary or website are ordinary nullable columns, not foreign keys, so they need no special delete action.

---

## Security

- **Passwords** are stored only as **hashes** in `users.password_hash`, generated by PHP `password_hash()` (bcrypt/argon2id) and checked with PHP `password_verify()`. They are one-way — they are never stored as plaintext and are never "encrypted back" to plain text. Hashes, not passwords, are what the database stores.
- **Applicant phone for encryption:** The `applicant_profiles.phone` field is protected with **recoverable AES-256-GCM encryption** in the PHP application (`app/Services/crypto.php`). Unlike a password, this is a personal detail that may need to be decrypted and shown back to the user (or to an employer). It is not used for lookups, so encrypting it does not interfere with queries.
- **Encryption keys are NOT stored in the database.** The AES-256-GCM key lives in application configuration (`APP_ENCRYPTION_KEY` in `.env`), never in the schema or seed data.
- **Clean baseline import:** `seed.sql` contains only job categories, and `database.sql` contains the schema plus those categories. They create no users or transactional records.
- **Optional demo data:** fictional users, company/job/application activity, and development-only password hashes live only in `demo-seed.sql`. Applicant demo phone values are `NULL`; no plaintext phone data or encryption key is committed.
- **No real secrets in the repository:** no real credentials, keys, salts, or IVs are committed.

---

## Indexing

Indexes speed up the queries the application runs most often. The main ones:

- `jobs.status` — quickly showing active/closed/draft jobs.
- `jobs.category_id` — filtering jobs by category.
- `jobs.location` — filtering/searching jobs by location.
- `jobs.company_id` — retrieving all jobs for a company.
- `jobs.employer_id` — retrieving "my jobs" for the logged-in employer and checking ownership.
- `jobs.created_at` — showing the newest jobs on the home page.
- `applications.applicant_id` — showing an applicant's application list.
- `applications.job_id` — showing an employer the applicants for one job.
- `applications.status` — filtering applications by status and building statistics.
- `saved_jobs` composite primary key — the join lookup for an applicant's saved list.
- `notifications.user_id` and `is_read` — pulling a user's unread notifications.

These cover the most frequent filter and join paths without over-indexing the tables.
