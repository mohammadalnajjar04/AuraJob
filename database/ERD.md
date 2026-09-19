# AuraJob — Database Architecture (ERD)

## Database Overview

AuraJob uses a normalized relational MySQL database (InnoDB, utf8mb4) to power a job board and recruitment management system. The schema is organized around two primary roles — **Applicant** and **Employer** — and supports job publishing, job searching, job applications, application tracking, saved jobs, and notifications.

The design emphasizes:

- **Normalization** — minimal duplication; shared attribute sets live in their own tables.
- **Referential integrity** — foreign keys with carefully chosen delete rules.
- **Ownership clarity** — every job and every company is tied to a concrete employer user.
- **Uniqueness constraints** — no duplicate accounts, duplicate applications, or duplicate saved jobs.
- **Sensible indexing** — indexes target search/filter columns and foreign keys.

### Design Choices at a Glance

- All role-specific identity fields for applicants live in `applicant_profiles`, not `users`. The `users` table holds only what every account shares (identity, credentials, role, status).
- Companies are owned by one employer user (one-to-one) but can have many jobs (one-to-many).
- `categories` is a lookup table rather than an ENUM so categories are dynamic and manageable.
- Employment type, work mode, and experience level are stored as ENUM columns because they are fixed, small, closed sets defined at design time.
- Application and user `status` values are ENUMs (closed, known value sets).
- Notifications are user-scoped (not role-scoped) so the same mechanism serves applicants and employers later.

---

## Entities

| Entity | Description |
|--------|-------------|
| `users` | Account records for every applicant and employer. Holds shared identity + credentials. |
| `applicant_profiles` | Applicant-specific professional information (one-to-one with a user). |
| `companies` | Employer-owned company/recruiting entity (one-to-one with an employer user). |
| `categories` | Lookup list of job categories (e.g., IT, Finance, Healthcare). |
| `jobs` | Job postings created by company employers. |
| `applications` | An applicant's application to a job; tracks the recruitment status. |
| `saved_jobs` | Junction table linking applicants to jobs they bookmark. |
| `notifications` | In-app messages delivered to a specific user. |

---

## Table Definitions

### users

Common account data for both applicants and employers.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(100) | NOT NULL | Full display name |
| `email` | VARCHAR(190) | NOT NULL, UNIQUE | Login identity; must be unique |
| `password_hash` | VARCHAR(255) | NOT NULL | `password_hash()` output (bcrypt/argon2). **Never store plain text.** |
| `role` | ENUM('applicant','employer') | NOT NULL, DEFAULT 'applicant' | Denormalized from the design; closed set |
| `status` | ENUM('active','inactive','banned') | NOT NULL, DEFAULT 'active' | Account lifecycle/ban control |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Notes**
- `email` uses length 190 so the UNIQUE index fits within InnoDB's index-size limits under utf8mb4 (190×4 = 760 bytes).
- No applicant-specific fields here — those live in `applicant_profiles` to avoid NULL-heavy, cross-role columns.
- **Encryption target (see Security section): `email` is NOT encrypted (needed for uniqueness/login). See Security notes for the field chosen for AES-256-GCM.**

---

### applicant_profiles

One-to-one detail record for applicant users.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | INT UNSIGNED | FK → `users.id`, UNIQUE, NOT NULL | One profile per applicant |
| `professional_title` | VARCHAR(100) | NULL | e.g. "Senior PHP Developer" |
| `bio` | TEXT | NULL | Short professional summary |
| `phone` | VARCHAR(255) | NULL | Contact number — **stored as an AES-256-GCM encrypted payload** (`v1:base64(...)`) at rest; widest column sized to hold ciphertext + IV + tag |
| `location` | VARCHAR(120) | NULL | Searchable preference |
| `experience_years` | TINYINT UNSIGNED | NULL | Years of experience |
| `skills` | TEXT | NULL | Comma-separated or free text; kept simple |
| `cv_path` | VARCHAR(255) | NULL | Reserved for CV upload (bonus feature) |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Notes**
- `user_id` is UNIQUE because the relationship is one-to-one.
- The table is optional — a profile row exists only once the applicant fills it in. Columns are nullable accordingly.
- `cv_path` stores a file path string, never the file binary.

---

### companies

Employer-owned company/recruiting entity.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | INT UNSIGNED | FK → `users.id`, UNIQUE, NOT NULL | Owning employer user |
| `name` | VARCHAR(150) | NOT NULL | Company/display name |
| `description` | TEXT | NULL | About the company |
| `industry` | VARCHAR(100) | NULL | Free text industry |
| `location` | VARCHAR(150) | NULL | Company headquarters |
| `website` | VARCHAR(255) | NULL | Company URL |
| `logo_path` | VARCHAR(255) | NULL | Uploaded logo path (bonus feature) |
| `contact_email` | VARCHAR(190) | NULL | Public contact email |
| `contact_phone` | VARCHAR(30) | NULL | Public contact phone |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Notes**
- `user_id` UNIQUE = one company per employer user (one-to-one), but a single company can own many jobs (one-to-many).
- `website` and `logo_path` hold URLs/paths, not files.

---

### categories

Dynamic lookup of job categories.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `name` | VARCHAR(80) | NOT NULL, UNIQUE | Display name, e.g. "Information Technology" |
| `slug` | VARCHAR(100) | NOT NULL, UNIQUE | URL-safe key for filtering |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |

**Notes**
- Stored as a lookup table (not an ENUM) so admins/employers can add categories without schema changes and the dropdown can be populated from the DB.

---

### jobs

Core job posting.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `company_id` | INT UNSIGNED | FK → `companies.id`, NOT NULL | Posting company |
| `employer_id` | INT UNSIGNED | FK → `users.id`, NOT NULL | Owning employer user (denormalized convenience for authorization) |
| `category_id` | INT UNSIGNED | FK → `categories.id`, NOT NULL | Job category |
| `title` | VARCHAR(150) | NOT NULL | |
| `description` | TEXT | NOT NULL | Full description |
| `requirements` | TEXT | NULL | Requirements list |
| `location` | VARCHAR(150) | NOT NULL | Workplace location (searchable) |
| `salary_min` | DECIMAL(12,2) | NULL | Lower bound |
| `salary_max` | DECIMAL(12,2) | NULL | Upper bound |
| `employment_type` | ENUM('full_time','part_time','contract','internship','freelance') | NOT NULL | Fixed set (see design notes) |
| `work_mode` | ENUM('onsite','remote','hybrid') | NOT NULL, DEFAULT 'onsite' | Fixed set |
| `experience_level` | ENUM('entry','junior','mid','senior','lead') | NOT NULL, DEFAULT 'entry' | Fixed set |
| `application_deadline` | DATE | NULL | Last date applications accepted |
| `status` | ENUM('active','closed','draft') | NOT NULL, DEFAULT 'active' | Publishing lifecycle |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Indexes**
- `idx_jobs_status` ON (`status`)
- `idx_jobs_category` ON (`category_id`)
- `idx_jobs_location` ON (`location`)
- `idx_jobs_company` ON (`company_id`)
- `idx_jobs_employer` ON (`employer_id`)
- `idx_jobs_created` ON (`created_at`) — for "latest jobs"

**Notes**
- **ENUM choices**: `employment_type`, `work_mode`, `experience_level`, and `status` are fixed, small, closed sets decided at design time — ENUMs are appropriate and avoid extra join tables.
- **Category** is a related table (`categories`) because it's open-ended and user-manageable.
- **Location** is a VARCHAR (free text) since there is no fixed location taxonomy; it is indexed for filtering/search.
- Salary is stored as two DECIMAL columns rather than a single string so range queries are possible. Min/max are nullable because salary display is optional.
- `employer_id` duplicates the company owner for direct, cheap authorization checks ("does this employer own this job?") and for indexing employer job lists.

---

### applications

Tracks an applicant's application to a specific job.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `applicant_id` | INT UNSIGNED | FK → `users.id`, NOT NULL | The applying applicant (user) |
| `job_id` | INT UNSIGNED | FK → `jobs.id`, NOT NULL | The job applied to |
| `status` | ENUM('applied','under_review','interview','accepted','rejected') | NOT NULL, DEFAULT 'applied' | Recruitment pipeline |
| `cover_letter` | TEXT | NULL | Optional notes from the applicant |
| `applied_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Application date |
| `updated_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Status-change tracking |

**Constraints**
- **UNIQUE(`applicant_id`, `job_id`)** — an applicant cannot apply to the same job more than once.

**Indexes**
- `idx_applications_applicant` ON (`applicant_id`) — applicant's application list
- `idx_applications_job` ON (`job_id`) — employer's applicant list for a job
- `idx_applications_status` ON (`status`) — status filtering/statistics

**Notes**
- The UNIQUE composite key is the primary `ON DUPLICATE KEY` guard against duplicate applications.
- `applied_at` is distinct from `created_at` for semantic clarity (there is no separate `created_at`; `applied_at` doubles as it).
- The FK `applicant_id` points to `users.id` (not `applicant_profiles.id`) because `applicant_profiles` may not exist yet at apply-time and authorization is user-scoped.

---

### saved_jobs

Junction table for applicants bookmarking jobs (many-to-many). **Bonus feature — reserved schema (implemented later).**

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `applicant_id` | INT UNSIGNED | FK → `users.id`, NOT NULL, composite PK part | Saving applicant |
| `job_id` | INT UNSIGNED | FK → `jobs.id`, NOT NULL, composite PK part | Saved job |
| `saved_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | When saved |

**Constraints**
- **PRIMARY KEY (`applicant_id`, `job_id`)** — prevents duplicate saved jobs and doubles as the join index.

**Notes**
- Composite primary key naturally enforces "an applicant may save a job only once."

---

### notifications

User-scoped in-app messages (base infrastructure for the notification bonus feature).

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | INT UNSIGNED | PK, AUTO_INCREMENT | |
| `user_id` | INT UNSIGNED | FK → `users.id`, NOT NULL | Recipient (applicant or employer) |
| `type` | ENUM('application_status','application_new','system','job_update') | NOT NULL, DEFAULT 'system' | Notification category |
| `message` | VARCHAR(500) | NOT NULL | Human-readable text |
| `is_read` | TINYINT(1) | NOT NULL, DEFAULT 0 | 0 = unread, 1 = read |
| `created_at` | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |

**Indexes**
- `idx_notifications_user` ON (`user_id`)
- `idx_notifications_read` ON (`is_read`)

**Notes**
- `type` is an ENUM because the known types are fixed and small; extensible by schema change.
- The table is keyed to a `user_id` (not a role) so a single mechanism serves both roles.

---

## Relationships

```
users 1 ── 0..1 applicant_profiles      (One-to-One)
users 1 ── 0..1 companies               (One-to-One)
users 1 ── 1..* applications            (as applicant)     (One-to-Many)
users 1 ── 0..* saved_jobs              (as applicant)     (One-to-Many)
users 1 ── 0..* notifications           (as recipient)     (One-to-Many)
users 1 ── 1..* jobs                    (as employer owner)(One-to-Many)

companies 1 ── 1..* jobs                (One-to-Many)

categories 1 ── 1..* jobs               (One-to-Many)

jobs 1 ── 1..* applications             (One-to-Many)
jobs 1 ── 0..* saved_jobs               (One-to-Many)

applications * ── 1 users               (Many-to-One)
saved_jobs   * ── * jobs                (Many-to-Many via junction)
```

### Relationship Summary

| Relationship | Type | Enforced by |
|--------------|------|-------------|
| User ↔ Applicant Profile | One-to-One | `applicant_profiles.user_id` UNIQUE FK |
| User ↔ Company | One-to-One | `companies.user_id` UNIQUE FK |
| User (employer) → Jobs | One-to-Many | `jobs.employer_id` FK |
| Company → Jobs | One-to-Many | `jobs.company_id` FK |
| Category → Jobs | One-to-Many | `jobs.category_id` FK |
| User (applicant) → Applications | One-to-Many | `applications.applicant_id` FK |
| Job → Applications | One-to-Many | `applications.job_id` FK |
| User (applicant) ↔ Job (saved) | Many-to-Many | `saved_jobs` junction (composite PK) |
| User → Notifications | One-to-Many | `notifications.user_id` FK |

---

## Foreign Key Delete Actions

| FK | Action | Rationale |
|----|--------|-----------|
| `applicant_profiles.user_id → users.id` | **ON DELETE CASCADE** | Deleting a user should remove their profile (no orphan sensitive data). |
| `companies.user_id → users.id` | **ON DELETE CASCADE** | A company cannot exist without its owner user. |
| `jobs.employer_id → users.id` | **ON DELETE CASCADE** | Jobs belong to their employer; if the account is gone the postings go too. |
| `jobs.company_id → companies.id` | **ON DELETE CASCADE** | Jobs are meaningless without their company. |
| `jobs.category_id → categories.id` | **ON DELETE RESTRICT** | Prevent deleting a category still in use (protects data integrity). |
| `applications.applicant_id → users.id` | **ON DELETE CASCADE** | Removing a user removes their applications (privacy). |
| `applications.job_id → jobs.id` | **ON DELETE CASCADE** | Deleting a job removes its applications (avoid orphan applications). |
| `saved_jobs.applicant_id → users.id` | **ON DELETE CASCADE** | Clean up saved entries when the user is deleted. |
| `saved_jobs.job_id → jobs.id` | **ON DELETE CASCADE** | Clean up saved entries when the job is deleted. |
| `notifications.user_id → users.id` | **ON DELETE CASCADE** | No reason to keep notifications for a deleted user. |

**Key decisions explained**

- **CASCADE is used deliberately on ownership chains** (user → profile/company/jobs/applications/saved_jobs/notifications, company → jobs, job → applications). These are owned/child records with no life beyond their parent.
- **RESTRICT (not CASCADE/SET NULL) on `categories`** — categories are reference data. Silently removing a category that jobs reference would corrupt postings; RESTRICT forces the developer to first reassign or remove affected jobs.
- **No ON DELETE SET NULL is used** here: every FK in this schema represents an ownership/mandatory association where the child cannot meaningfully survive without the parent. Optional fields (salary, phone, website) are ordinary nullable columns, not foreign keys, so they need no SET NULL behavior.

---

## Security / Encryption Notes

- **Passwords**: stored as a hash in `users.password_hash` using PHP `password_hash()` (bcrypt/argon2id). Never reversible; never stored in plain text. **Hash only — do not encrypt.**
- **Encryption target (recoverable sensitive field):** `applicant_profiles.phone` — a sensitive personal data point that may need to be recovered/read back de-encrypted (unlike a password). It will later be encrypted **in the application layer using PHP AES-256-GCM**.
  - Rationale: it's personally identifying, is not used for lookups (so encryption does not block queries), and may need to be shown back to the user or to employers.
  - The encrypted value will be stored in a column large enough to hold the ciphertext + IV + tag (**`phone` was widened from VARCHAR(30) → VARCHAR(255)` for this; see `database/migrations/2026-09-14-phone-encryption.sql`**). The schema keeps the column as an opaque text field; the key lives only in the application configuration (`.env`), **never in the database**.
- No encryption keys, salts, or IVs are stored in this schema.
- `email` remains plain (with UNIQUE) because it is the login identifier and must be searchable.

> **Recommendation for the `.env`/config:** store the AES-256-GCM key outside the database, e.g. a 256-bit random key loaded from an environment variable, and rotate it separately from schema migrations.

---

## Application Status Workflow

Statuses stored in `applications.status` (ENUM):

```
Applied
  → Under Review
    → Interview
      → Accepted        (hired)
    → Rejected
```

Allowed transitions (enforced in application logic, not in the DB):

- `applied` → `under_review`, `rejected`
- `under_review` → `interview`, `rejected`
- `interview` → `accepted`, `rejected`
- `accepted`/`rejected` are terminal.

The database stores the current status; transition validation is handled by the PHP application layer.

---

## Mermaid ER Diagram

```mermaid
erDiagram
    USERS {
        INT UNSIGNED id PK
        VARCHAR(100) name
        VARCHAR(190) email UNIQUE
        VARCHAR(255) password_hash
        ENUM role
        ENUM status
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    APPLICANT_PROFILES {
        INT UNSIGNED id PK
        INT UNSIGNED user_id FK,UNIQUE
        VARCHAR(100) professional_title
        TEXT bio
        VARCHAR(30) phone
        VARCHAR(120) location
        TINYINT UNSIGNED experience_years
        TEXT skills
        VARCHAR(255) cv_path
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    COMPANIES {
        INT UNSIGNED id PK
        INT UNSIGNED user_id FK,UNIQUE
        VARCHAR(150) name
        TEXT description
        VARCHAR(100) industry
        VARCHAR(150) location
        VARCHAR(255) website
        VARCHAR(255) logo_path
        VARCHAR(190) contact_email
        VARCHAR(30) contact_phone
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    CATEGORIES {
        INT UNSIGNED id PK
        VARCHAR(80) name UNIQUE
        VARCHAR(100) slug UNIQUE
        TIMESTAMP created_at
    }

    JOBS {
        INT UNSIGNED id PK
        INT UNSIGNED company_id FK
        INT UNSIGNED employer_id FK
        INT UNSIGNED category_id FK
        VARCHAR(150) title
        TEXT description
        TEXT requirements
        VARCHAR(150) location
        DECIMAL(12,2) salary_min
        DECIMAL(12,2) salary_max
        ENUM employment_type
        ENUM work_mode
        ENUM experience_level
        DATE application_deadline
        ENUM status
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    APPLICATIONS {
        INT UNSIGNED id PK
        INT UNSIGNED applicant_id FK
        INT UNSIGNED job_id FK
        ENUM status
        TEXT cover_letter
        TIMESTAMP applied_at
        TIMESTAMP updated_at
    }

    SAVED_JOBS {
        INT UNSIGNED applicant_id PK,FK
        INT UNSIGNED job_id PK,FK
        TIMESTAMP saved_at
    }

    NOTIFICATIONS {
        INT UNSIGNED id PK
        INT UNSIGNED user_id FK
        ENUM type
        VARCHAR(500) message
        TINYINT(1) is_read
        TIMESTAMP created_at
    }

    USERS ||--o| APPLICANT_PROFILES : "has"
    USERS ||--o| COMPANIES : "manages"
    USERS ||--o{ JOBS : "employs as owner"
    USERS ||--o{ APPLICATIONS : "submits"
    USERS ||--o{ SAVED_JOBS : "saves"
    USERS ||--o{ NOTIFICATIONS : "receives"
    COMPANIES ||--o{ JOBS : "posts"
    CATEGORIES ||--o{ JOBS : "classifies"
    JOBS ||--o{ APPLICATIONS : "receives"
    JOBS ||--o{ SAVED_JOBS : "is saved in"
    APPLICANT_PROFILES }o--|| USERS : ""
    COMPANIES }o--|| USERS : ""
    APPLICATIONS }o--|| USERS : ""
    SAVED_JOBS }o--|| USERS : ""
    NOTIFICATIONS }o--|| USERS : ""
```

---

## Important Design Decisions (Summary)

1. **`users` stays lean.** Shared account attributes only. Role-specific data is split into `applicant_profiles` and `companies`, avoiding wide, sparse rows and letting each role evolve independently.
2. **Reversible encryption (AES-256-GCM) targets `applicant_profiles.phone`**, while the password uses hashing (`password_hash`). Encryption is applied in PHP, keys live outside the DB.
3. **Categories are a lookup table** (dynamic), while `employment_type`, `work_mode`, `experience_level`, `status`, and `role` are **ENUMs** (small, closed, fixed sets).
4. **Composite UNIQUE/PK constraints** prevent duplicate applications (`applications`) and duplicate saved jobs (`saved_jobs`) at the database level.
5. **Delete rules are intentional**: CASCADE for ownership chains, RESTRICT for reference data (`categories`), no blind global CASCADE.
6. **`jobs.employer_id`** is stored alongside `company_id` to make ownership authorization and "my jobs" queries cheap and index-friendly.
7. **Indexes are targeted** at search (title/location via category/status/created indexes) and the most common joins (applicant ↔ applications, job ↔ applications, employer ↔ jobs, user ↔ notifications), without over-indexing.
8. **`saved_jobs` and `notifications` are designed now** but are bonus features; their schema is reserved and ready for later implementation without altering mandatory tables.
