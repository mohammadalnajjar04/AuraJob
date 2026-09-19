# AuraJob — Implemented UI/UX Design System

This document describes the final implemented AuraJob interface.

## Core Design
- Brand: AuraJob
- Typeface: Inter
- Primary visual family: indigo/purple with dark slate text
- Responsive layouts use Flexbox, CSS Grid, and mobile breakpoints
- Shared styles live in `public/assets/css/style.css`

## Navigation
AuraJob has public, Applicant, and Employer navigation states. The mobile menu is JavaScript-enhanced and the notification badge is role-aware for signed-in users.

## Buttons and Destructive Actions
Implemented button variants include primary, outline, ghost, and danger.

Destructive workflows do **not** depend on confirmation modals:
- Close Job uses a dedicated confirmation page.
- Delete Job uses a dedicated confirmation page and active jobs must be closed first.
- Cancel Application uses a dedicated confirmation page and deletes the applicant-owned application when cancellation is allowed.
- Application status changes use explicit POST buttons protected by CSRF, ownership checks, and a server-side transition whitelist.

## Forms
All important forms use visible labels, hints, validation messages, and server-side validation.

The Post/Edit Job form is a **single responsive page with six stacked sections**:
1. Basic information
2. Role details
3. Work arrangement
4. Compensation
5. Description
6. Publishing

Employers can Save Draft or Publish Job.

## Apply Flow
The Apply page is a full review page. It shows:
- job summary;
- applicant profile summary;
- profile completeness;
- CV readiness;
- an **optional cover letter** up to 5,000 characters;
- final Submit Application action.

The cover letter is stored in `applications.cover_letter`. The applicant CV is profile-scoped and becomes available to the employer only after an application exists.

## Application Statuses
Implemented statuses:
- Applied
- Under Review
- Interview
- Accepted
- Rejected

Allowed transitions:
- Applied → Under Review / Rejected
- Under Review → Interview / Rejected
- Interview → Accepted / Rejected
- Accepted → terminal
- Rejected → terminal

Applicant Tracking renders the progression responsively; Rejected is a separate terminal closed outcome.

## Search and AJAX
Find Jobs supports normal GET requests and progressive AJAX enhancement with `fetch()`, History API URL updates, loading feedback, focus preservation, ARIA live announcements, and reduced-motion support.

## Saved Jobs
Applicants can save and unsave jobs. The JavaScript path uses AJAX/JSON and the PHP endpoint remains server-authoritative. Data is stored in `saved_jobs`.

## Notifications
AuraJob implements **in-app notifications**:
- new application → Employer notification;
- application status change → Applicant notification;
- read/unread state and Mark All as Read.

The project does not claim an email-notification service.

## Uploads
### Applicant CV
- PDF only
- max 5 MB
- MIME + PDF signature validation
- random filename
- stored outside `public/` under `storage/uploads/cvs/`
- applicant can download their own CV
- employer access is scoped through an owned application

### Company Logo
- PNG/JPEG/WebP
- max 2 MB
- MIME + dimension validation
- random filename
- stored under `storage/uploads/company-logos/`
- served through `public/company-logo.php`
- initials avatar fallback when no logo exists

## Accessibility
Implemented patterns include semantic headings/landmarks, persistent labels, visible focus, ARIA where dynamic state needs it, text labels in addition to color, responsive touch targets, AJAX live feedback, and reduced-motion support.

## Error States
AuraJob includes branded 404/500 pages and safe database/load error states. Technical exception details remain in server logs instead of being shown to visitors.

This document intentionally describes implemented behavior only.
