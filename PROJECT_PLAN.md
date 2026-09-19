# AuraJob

## Project Type
Job Board & Recruitment Management System

## Tagline
Connecting Talent With Opportunity

## Main Goal
Create a full-stack recruitment platform that connects job seekers with employers and allows employers to manage jobs and applications.

## User Roles

### Applicant
- Register and login
- Browse jobs
- Search and filter jobs
- View job details
- Apply for jobs
- View their applications
- Track application status
- Manage their profile

### Employer
- Register and login
- Manage company profile
- Create jobs
- View jobs they created
- Edit jobs
- Delete jobs
- View applicants
- Manage application statuses

## Core Features
- Authentication
- Role-based authorization
- Job management
- Job applications
- Application tracking
- Job search
- Job filtering
- Applicant dashboard
- Employer dashboard
- AJAX functionality
- Responsive design
- Security and validation

## Application Statuses
- Applied
- Under Review
- Interview
- Accepted
- Rejected


## Current Implementation Notes

The current AuraJob build now includes the mandatory capstone requirements and the completed bonus functionality used by the application:

- Job CRUD includes create, read, update, close, and permanent delete for employer-owned jobs.
- Applicants can cancel eligible in-progress applications.
- Job search/filtering is server-side and progressively enhanced with AJAX `fetch()` + History API.
- Saved Jobs uses AJAX persistence.
- Notifications are implemented with PHP OOP (`interface` + service class).
- Applicant phone data is encrypted at rest with AES-256-GCM.
- Authentication uses secure PHP sessions, CSRF protection, and an optional 30-day Remember-me session cookie.
- HTML5, JavaScript, PHP, and database validation are layered together.

# Page Map

## Public Pages

### 1. Home
Purpose:
- Introduce AuraJob
- Show a job search area
- Show latest jobs
- Show popular job categories
- Explain how AuraJob works
- Provide calls to action for applicants and employers

### 2. Jobs
Purpose:
- Display all available jobs
- Search jobs
- Filter by category
- Filter by location
- Filter by job type
- Filter by work mode

### 3. Job Details
Purpose:
- Display complete information about one job
- Show company information
- Show requirements
- Show salary range
- Show deadline
- Provide Apply button for eligible applicants

### 4. Login
Purpose:
- Allow registered users to log in

### 5. Register
Purpose:
- Allow a new user to create an account
- User chooses Applicant or Employer role


# Applicant Pages

### 6. Applicant Dashboard
Purpose:
- Show application statistics
- Show recent applications
- Show application status summaries

### 7. My Applications
Purpose:
- Display jobs the applicant applied for
- Display current status of every application
- Allow cancellation where appropriate

### 8. Applicant Profile
Purpose:
- Manage applicant profile information
- Manage professional information
- Manage the applicant CV upload and replacement workflow (PDF only)

### 9. Saved Jobs
Purpose:
- Display saved jobs
Note:
This feature is implemented and available to applicants.


# Employer Pages

### 10. Employer Dashboard
Purpose:
- Show active jobs
- Show total applications
- Show application statistics
- Show recent applicant activity

### 11. Company Profile
Purpose:
- Manage company information

### 12. My Jobs
Purpose:
- Display all jobs created by the employer
- Allow employer to manage them

### 13. Create Job
Purpose:
- Allow employer to publish a new job

### 14. Edit Job
Purpose:
- Allow employer to update their own job

### 15. Job Applicants
Purpose:
- Show applicants for a specific job
- Show applicant and application information

### 16. Application Details
Purpose:
- View a specific application
- Allow employer to update application status


# Application Status Workflow

Applied
→ Under Review
→ Interview
→ Accepted

Alternative final result:
→ Rejected


# Mandatory Features

- Registration
- Login
- Logout
- Sessions
- Applicant and Employer roles
- Role-based authorization
- Job CRUD
- Job applications
- Application management
- Search
- Filtering
- AJAX functionality
- Prepared statements
- Password hashing
- Data encryption
- Form validation
- Responsive design


# Bonus Features / Enhancements

- Saved jobs — implemented
- CV upload — implemented
- Company logo upload — implemented
- Notifications
- Dashboard statistics
- Pagination
- Charts
- Advanced filtering


# Development Rule

Mandatory features must be completed and tested before bonus features are started.

# User Flows

## 1. Visitor Flow

Visitor
→ Home
→ Browse Jobs
→ Search / Filter Jobs
→ Open Job Details

If the visitor clicks Apply:
→ Redirect to Login/Register

Visitor can also:
→ Register as Applicant
or
→ Register as Employer


## 2. Applicant Registration Flow

Register
→ Choose Applicant role
→ Enter account information
→ Account created
→ Login
→ Applicant Dashboard


## 3. Applicant Job Search Flow

Applicant Dashboard
→ Browse Jobs
→ Search by keyword
→ Filter jobs
→ Open Job Details
→ Review job information
→ Apply for Job
→ Application created
→ Application Status = Applied
→ View application in My Applications


## 4. Applicant Application Tracking Flow

My Applications
→ Select Application
→ View Job
→ View Current Status

Possible statuses:

Applied
→ Under Review
→ Interview
→ Accepted

or

Applied / Under Review / Interview
→ Rejected


## 5. Employer Registration Flow

Register
→ Choose Employer role
→ Enter account information
→ Login
→ Employer Dashboard
→ Complete Company Profile


## 6. Employer Job Management Flow

Employer Dashboard
→ My Jobs
→ Create Job
→ Publish Job

Employer can:

My Jobs
→ Select Job
→ Edit Job

or

My Jobs
→ Select Job
→ Delete Job

or

My Jobs
→ Select Job
→ View Applicants


## 7. Employer Applicant Management Flow

Employer Dashboard
→ My Jobs
→ Select Job
→ Job Applicants
→ Select Applicant
→ View Application Details
→ Update Application Status

Example:

Applied
→ Under Review
→ Interview
→ Accepted

or

→ Rejected


## 8. Authorization Flow

If Applicant attempts to access Employer functionality:
→ Server checks user role
→ Access denied

If Employer attempts to modify another employer's job:
→ Server checks ownership
→ Access denied

If unauthenticated user attempts to access protected page:
→ Redirect to Login


## 9. Logout Flow

Logged-in User
→ Logout
→ Session terminated
→ Redirect to Home or Login


# Main System Flow

JOB SEEKER
→ Register
→ Login
→ Search Jobs
→ View Job
→ Apply
→ Track Application
→ Receive Status Updates

EMPLOYER
→ Register
→ Login
→ Create Company Profile
→ Post Job
→ Receive Applications
→ Review Applicants
→ Update Application Status
→ Hire or Reject Applicant


# Project Development Priority

Build and test the system in this order:

1. Public pages
2. Authentication
3. Roles and authorization
4. Job management
5. Job applications
6. Application tracking
7. Search and filtering
8. AJAX
9. Security
10. Responsive design
11. Testing
12. Bonus features


# Database Architecture

MySQL (InnoDB, utf8mb4). Database name: `aurajob_db`.

**Final table list (8):**
`users`, `applicant_profiles`, `companies`, `categories`, `jobs`, `applications`, `saved_jobs`, `notifications`.

**Important relationships:**
- User ↔ Applicant Profile: one-to-one (`applicant_profiles.user_id` unique)
- User ↔ Company: one-to-one (`companies.user_id` unique) — employer owns one company
- Company → Jobs: one-to-many; Employer → Jobs: one-to-many (`jobs.company_id`, `jobs.employer_id`)
- Category → Jobs: one-to-many
- Applicant → Applications, Job → Applications: one-to-many
- Applicant ↔ Job (saved): many-to-many via `saved_jobs`

**Application tracking approach:**
Current status stored on `applications.status` (applied / under_review / interview / accepted / rejected). Workflow transitions validated in the application layer.

**Duplicate-prevention rules (database-enforced):**
- Unique `users.email`
- Unique `(applicant_id, job_id)` on `applications` — no duplicate applications
- Composite PK `(applicant_id, job_id)` on `saved_jobs` — no duplicate saves
- Unique `user_id` on `applicant_profiles` and `companies`

**Password strategy:**
Hashed only via PHP `password_hash()` / `password_verify()`. Never plaintext, never reversible.

**Encryption target:**
`applicant_profiles.phone` is encrypted in PHP with AES-256-GCM (not the password). The encryption key lives outside the database in `.env` and is never stored in the schema or seed data.

Full details: see `database/ERD.md` and `database/schema.sql`.
