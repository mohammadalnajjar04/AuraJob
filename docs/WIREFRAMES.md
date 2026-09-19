# AuraJob — Final Interface Wireframes

These text wireframes match the final implementation.

## Home
```text
AuraJob nav
Hero: headline + keyword/location search + visual
Latest Jobs (database-driven)
Popular Categories (database-driven)
Applicant benefits
Employer section
Footer
```

## Find Jobs
```text
Search + location
Filters | Result count + sort
        | Job cards
        | Pagination
```
AJAX progressively updates the result area; GET fallback remains functional.

## Job Details
```text
Company identity + Job title
Job metadata
Description / Requirements | Apply / Save / Share
Company details             | Salary / deadline
Related jobs
```

## Applicant Dashboard
```text
Greeting + Find Jobs
Application KPI cards
Recent applications
Profile completion / shortcuts
```

## Applicant Profile
```text
Professional profile fields
Encrypted phone / location / experience / skills
Resume/CV: PDF upload / download / remove
Save Profile
```

## Apply
```text
Job summary
Applicant profile review + CV readiness
Optional Cover Letter textarea (max 5,000)
Sharing notice
Back to Job | Submit Application
```

## Application Tracking
```text
Applied → Under Review → Interview → Accepted
Rejected = separate terminal closed outcome
```
Mobile uses a vertical timeline.

## Employer Dashboard
```text
Employer greeting + Post a Job
KPI cards
Recent applications
Active jobs / recruitment summary
```

## Company Profile
```text
Company branding: logo preview/upload/remove
Company details
Save Company Profile
```

## My Jobs
```text
Job | Status | Applications | Published | Deadline | Actions
Actions: View / Edit / Applicants / Close / Delete
```
Close/Delete route to dedicated confirmation pages.

## Post / Edit Job
```text
1 Basic information
2 Role details
3 Work arrangement
4 Compensation
5 Description
6 Publishing

Cancel | Save Draft | Publish Job
```
One responsive grouped page; no multi-step wizard.

## Job Applicants
```text
Job title + counts
Status filter tabs
Candidate | Applied | Status | Review
```

## Employer Application Detail
```text
Candidate + Job
Current Status
Allowed next-status buttons
Application: applied date / job / optional cover letter
Applicant: email / decrypted phone / skills / optional CV / bio
```
Status buttons submit directly through CSRF-protected server validation; no confirmation modal is required.

## Confirmation Pages
```text
Close Job: explanation → Cancel / Close Job
Delete Job: impact summary → Keep Job / Delete Job
Cancel Application: deletion warning → Keep Application / Cancel Application
```

All layouts are responsive through `public/assets/css/style.css`.
