# AuraJob — Implemented Component Inventory

## Brand / Navbar
Logo, public links, role-aware links, mobile navigation, notification badge.

## Footer
Copyright and UniCoders attribution.

## Buttons
Primary, outline, ghost, danger, and size/full-width modifiers.

## Form Field
Label + input/select/textarea/file + hint + error. Used across auth, profiles, job forms, filters, Apply, and uploads.

## Job Card
Job title, company identity, location, metadata, salary/time, and Save state where applicable.

## Company Identity
Uploaded company logo when present; deterministic initials fallback otherwise.

## Status Badge
Text + visual status treatment for job and application states.

## Dashboard Panel / Stat Card
Reusable bounded surfaces for KPIs, activity, jobs, applications, and profile completion.

## Search / Filter / Pagination
Keyword/location search, job filters, sort, pagination, mobile filter drawer, AJAX result region.

## Saved Job Action
AJAX/JSON enhancement + CSRF-protected PHP/MySQL persistence.

## Applicant Profile
Professional fields, encrypted phone, skills, experience, and secure PDF CV upload.

## Company Profile
Company details plus secure logo upload/remove/preview.

## File Upload
CV: PDF, 5 MB max, MIME/signature validation.  
Logo: PNG/JPEG/WebP, 2 MB max, MIME/dimension validation.

## Job Form
Shared six-section single-page renderer in `app/Support/job-form.php`. Actions: Cancel, Save Draft, Publish Job.

## Apply Form
Job/profile review, CV readiness, optional cover-letter textarea, CSRF, submit action.

## Application Row / Applicant Row
Responsive job/candidate list pattern with date, status, and action.

## Application Tracking Timeline
Applied / Under Review / Interview / Accepted progression with Rejected terminal outcome.

## Application Status Actions
Allowed-next-status buttons backed by server-side transition validation, CSRF, ownership checks, and terminal-state enforcement.

## Notifications
Unread badge, center, mark read, mark all read, new-application and status-update in-app notifications.

## Alert / Flash / Empty State
Validation errors, success feedback, no-data states, and load errors.

## Branded Error Page
Shared 404/500 presentation with safe non-technical messaging.

## Destructive Confirmation Page
Dedicated pages for Close Job, Delete Job, and Cancel Application. The implementation does not depend on confirmation modals.

## Share Job
Native Web Share API where supported, with clipboard fallback and accessible status feedback.

## Accessibility Patterns
Semantic structure, persistent labels, visible focus, ARIA live regions, keyboard focus handling, reduced-motion support.

This inventory lists implemented components only.
