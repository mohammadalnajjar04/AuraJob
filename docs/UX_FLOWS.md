# AuraJob — Implemented UX Flows

## 1. Visitor → Job Discovery
Home → Search → Find Jobs → Filter/Sort/Paginate → Job Details.

## 2. Registration / Login
Register → choose Applicant or Employer → validate → create account → role dashboard.  
Login uses `password_verify()` and session-based authentication. Remember Me extends the session-cookie lifetime.

## 3. Applicant Profile
Applicant Dashboard → Profile → professional details → encrypted phone → skills/experience → optional PDF CV → Save.

## 4. Save and Apply
Find Jobs → Job Details → optional Save → Apply → review profile/CV → optional Cover Letter → Submit → Tracking.

The optional cover letter is stored in `applications.cover_letter`. Duplicate applications are prevented by the database unique constraint and server-side handling.

## 5. Applicant Tracking
My Applications → Track Application.

Progression:
Applied → Under Review → Interview → Accepted.  
Rejected is a separate terminal outcome.

Cancellation uses a dedicated confirmation page and **deletes the application record** while its status is still cancellable. There is no `cancelled` database status.

## 6. Employer Company Profile
Employer Dashboard → Company Profile → company details → optional company logo → Save.

## 7. Employer Posts / Edits Job
Post Job or Edit Job → one page with six grouped sections → Save Draft or Publish.

This is not a multi-step wizard.

## 8. Employer Manages Jobs
My Jobs → View / Edit / Applicants / Close / Delete.

Close and Delete use dedicated confirmation pages. Active jobs must be closed before deletion.

## 9. Employer Reviews Applicants
Applicants → filter by application status → Review Application → applicant profile / optional cover letter / CV → choose an allowed next status.

Status changes are POST + CSRF + ownership-scoped and are validated against the server transition map.

## 10. Notifications
Applicant submits → Employer in-app notification.  
Employer updates status → Applicant in-app notification.

Users can mark notifications read individually or mark all as read. No email-notification workflow is claimed.

## Cross-flow Security
- Prepared statements
- CSRF protection
- Applicant/Employer authorization
- Ownership checks
- Password hashing
- AES-256-GCM phone encryption
- Server-side validation
- Safe file upload validation
- Safe branded error pages

This document reflects the final application, not future/planned behavior.
