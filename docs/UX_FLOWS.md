# HirePeak — UX Flows

End-to-end journeys, drawn visually, mapping to screens in `WIREFRAMES.md` and components in `COMPONENT_INVENTORY.md`.

Guiding principle: **every flow ends with the user clearly knowing what happened and what to do next.**

---

## Flow 1 — Visitor Lands and Understands (5-second test)

```
              ┌──────────────────┐
   visitor ──▶│ HOME             │  ← "Find the right opportunity. Reach your peak."
              │ hero explains:   │       hero search: keyword + location + [Search Jobs]
              │ what/who/next    │
              └────────┬─────────┘
                       │
        ┌──────────────┴───────────────┐
        │                              │
   [Explore Jobs]                 [Create Account]
        │                              │
        ▼                              ▼
   JOBS page                    REGISTER → role choice
   (filters + results)      (Applicant │ Employer, see flows 2 & 5)
```

Pass criteria for the audit: a first-time visitor can say within 5 seconds — "It's a job site. I search jobs or I sign up." Confirmed in section UX Problems (audit) — hero is one viewport, one sentence each for what/who/next, no wall text.

---

## Flow 2 — Applicant Registration

```
REGISTER
  │  choose "I'm an applicant"
  │  enter name · email · password (labeled,Show toggle, strength hints)
  ▼
account created  →  toast "Welcome!" + optional verify banner
  ▼
Applicant Dashboard (greeting + zero-state guidance)
  │
  └── suggested first actions: Find Jobs (primary), complete Profile
```

Notes: no multi-step gauntlet. Registration = one short form + role card. Profile completion is encouraged afterward, never demanded first.

---

## Flow 3 — Applicant Browsing → Applying

```
JOBS (search page)
  │  keyword? location? filters (category/type/mode/exp)
  │  sort (recent/most relevant/oldest)
  ▼
Job card list (skeletons → results count + active filters + clear all)
  ▼
JOB DETAILS
  │  prose: about → responsibilities → requirements → benefits
  │  aside: apply panel, deadline, company
  │  actions: [Apply Now] (primary) · [☆ Save]
  ▼
if not signed in  ──▶  LOGIN / REGISTER  ──▶ return to job (preserve intent)
  │
Apply modal/drawer: optional cover letter
  ▼
APPLICATION SUBMITTED → toast "Application submitted" → View application
  ▼
APPLICATION TRACKING page (status = Applied)
```

Notes: back-navigation preserves filters (deep-link friendly). Duplicate apply blocked by DB + UI disables button if already applied ("Applied ✓").

---

## Flow 4 — Applicant Tracks an Application

```
MY APPLICATIONS
  │  rows: job/company · date · StatusBadge · actions (View / Cancel if allowed)
  │  filter by status chips
  ▼
APPLICATION TRACKING (signature view)
  │  ✓ Applied 12 Jul ← ✓ Under Review 18 Jul ← ● Interview (current)
  │  next: ○ Accepted  (rejected is a separate quiet terminal branch)
  ▼
employer updates status  ──▶  notification + toast on next visit
  ▼
revisit tracking → step advanced with date
```

Cancellation: only while status is Applied/Under Review → confirmation modal "Withdraw application?" → moved to a "Cancelled" neutral state and notified. Never destructive-looking.

---

## Flow 5 — Employer Registration & Onboarding

```
REGISTER
  │  choose "I'm an employer"
  │  name · work email · password
  ▼
account created
  ▼
EMPLOYER ONBOARDING: Company profile first (one short form: name, industry,
                    website, location, contact email)  ← setup wizard step 1
  ▼
COMPANY profile saved → toast "Company created"
  ▼
EMPLOYER DASHBOARD (empty zero-state: "Create your first job posting" → Post a Job)
```

Notes: employer lands ready to act; company basics captured early so the first job posting is not blocked by a missing company.

---

## Flow 6 — Employer Posts a Job

```
EMPLOYER DASHBOARD             (navbar "Post a Job" always visible)
  │  [Post a Job →]
  ▼
CREATE JOB (stepper form)
   1 Basic Information → title, category, summary
   2 Job Details       → description, requirements
   3 Compensation      → salary min/max (optional), currency
   4 Location & Mode   → location, work mode, employment type, experience level
   5 Application Set   → deadline, publish status
  │  [Save draft] secondary · [Publish job →] primary
  ▼
PUBLISHED → toast "Job posted" → View job / "Go to applicants"
  │
  ▼
JOB appears in My Jobs (status Active) + receives applicants
```

Notes: form never exposes > 12 fields at once; inline validation per step; draft supported.

---

## Flow 7 — Employer Reviews & Manages Applicants

```
EMPLOYER DASHBOARD → "3 applications need review" banner (attention anchor)
  ▼
JOB → Applicants (or navigation "Applicants")
  │  search + status filter + sort by date
  ▼
APPLICANT LIST (rows or mobile cards): candidate · applied · status · [Review]
  ▼
APPLICATION DETAILS (employer)
  │  hierarchy: candidate → status → profile → cover letter → CV → metadata
  │  primary: [Update Status ▾]
  ▼
STATUS CHANGE MODAL
  │  choose target: Interview / Accepted (advance), or Rejected (guarded)
  │  explicit confirm — never one unsafe click
  ▼
status saved → toast "Application status updated"
  ▼
applicant notified (notification + in-app), stage moves in Pipeline
```

Notes: rejection requires deliberate confirmation (selection + confirm). Pipeline widget on dashboard / details reflects live counts. Accepted/Hired counts feed Employer KPI "Hired".

---

## Flow 8 — Both Roles ↔ Notifications

```
(any page) [🔔] unread dot (n)
    │
    ▼ open notification panel
  recent unread first, each: message + time + destination link
  [Mark all as read]
    │
    ▼ click a notification
  navigate to destination · mark read · dot updates
```

Examples:
- Applicant: "Your application for Backend Developer moved to Interview." → Application Tracking.
- Employer: "A new candidate applied for Frontend Developer." → Job Applicants / Application Details.
- System: "Your job posting is about to close." → Job details / form.

Empty state: "You're all caught up."

---

## Flow 9 — Job Seeker Main Flow (condensed one-glance)

```
JOB SEEKER  ──▶ Register ──▶ Login ──▶ Search Jobs ──▶ View Job ──▶ Apply
        └──────────▶ Track Application ──▶ Interview ──▶ Accept / Result
```

## Flow 10 — Employer Main Flow (condensed one-glance)

```
EMPLOYER  ──▶ Register ──▶ Company Profile ──▶ Post Job ──▶ Receive Applicants
        ──▶ Review ──▶ Interview ──▶ Hire / Not selected
```

---

## Guardrails across all flows

- **Unauthenticated → protected page:** redirect to Login with a return-to destination; after login, land back where they were.
- **Applicant hits employer route:** role check → 403 with "You don't have access to this page" + CTA to the correct dashboard.
- **Employer edits another employer's job:** ownership check → 403; UI hides those actions entirely for non-owners.
- **Rejected/Cancelled states:** calm neutral treatment; always provide a next step (browse more, revisit other applications).
- **Every submit** shows a proper loading state and surfaces success/error via toast or inline alert (no double-submit).

---

*Flows map 1:1 to the wireframes and components documented in `WIREFRAMES.md` and `COMPONENT_INVENTORY.md`.*