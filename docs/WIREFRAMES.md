# HirePeak — Wireframes (Text/ASCII)

Layout reference for implementation. Companion to `UI_UX_DESIGN_SYSTEM.md`.

Conventions used below:

- `[◆ HirePeak]` — logo lockup (mark + wordmark).
- `[ btn ]` — button (bold outline = primary/brand).
- `[▾]` — dropdown / menu.
- `[🔔]` — notifications with unread dot.
- `[≡]` — mobile hamburger.
- `(x)` — avatar circle with initials.
- `◇` — status marker; `✓` — completed check; `○` — upcoming step.
- `{ ... }` — repeated region (e.g., job card list).

Breakpoints: desktop ≥1200px / tablet 768–1199px / mobile <768px. Each wireframe notes desktop; mobile behavior described inline.

---

## 1. Home (Public / Visitor)

Desktop (1200px container):

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [◆ HirePeak]  Home · Find Jobs · For Employers        [Sign In] [Create Account]│  ← nav 64px
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│        Find the right opportunity.                                       │
│        Reach your peak.                                                  │   ← display type
│                                                                          │
│        Discover meaningful opportunities from growing teams or find      │
│        talented people ready to make an impact.                          │
│                                                                          │
│        ┌──────────────────────────┐ ┌──────────────────┐ ┌────────────┐ │
│        │  🔍 Keyword / Job title  │ │  📍 Location     │ │[Search Jobs]│ │  ← hero search
│        └──────────────────────────┘ └──────────────────┘ └────────────┘ │
│                                                                          │
│        Popular:  <Backend>  <Design>  <Remote>  <Finance>               │
│                                                                          │
├──────────────────────────────────────────────────────────────────────────┤
│  Latest Jobs                                    [ View all jobs → ]      │
│  ┌──────────────────────────┐ ┌──────────────────────────┐               │
│  │ [logo] Senior PHP Dev    │ │ [logo] Marketing Spec    │               │
│  │ TechCorp · Makati       │ │ TechCorp · Remote        │               │
│  │ [Full-time][Hybrid]      │ │ [Full-time][Remote]      │               │
│  │ 80k–120k  · posted 3d    │ │ 50k–70k   · posted 2d    │               │
│  └──────────────────────────┘ └──────────────────────────┘               │
│                                                                          │
│  Popular Categories                                                     │
│  [ 💻 IT ] [ 💰 Finance ] [ 🩺 Healthcare ] [ 📣 Marketing ]  ...        │
│                                                                          │
├──────────────────────────────────────────────────────────────────────────┤
│  Why HirePeak                   │  How It Works                         │
│  - Clear tracking               │  1 Create account →2 Apply →3 Track    │
│  - One place for everything     │  (two-column band, no card wall)       │
├──────────────────────────────────────────────────────────────────────────┤
│  For Applicants                                    [ Explore Jobs → ]    │
│  For Employers                                      [ Post a Job → ]    │  ← benefit bands
├──────────────────────────────────────────────────────────────────────────┤
│  [ Final CTA band:  "Ready to reach your peak?"     [Create Account] ]   │
├──────────────────────────────────────────────────────────────────────────┤
│  Footer:  HirePeak         Job Seekers | Employers | About | Support     │
│          © 2026 HirePeak                                                  │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile:** nav collapses to `[≡]`; hero headline smaller; search fields stack with full-width `[Search Jobs]` button; category tiles wrap 2×2; benefit bands stack; final CTA button full width.

---

## 2. Jobs (Search & Results)

Desktop:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [◆ HirePeak]  Find Jobs · My Applications · Saved Jobs    [🔔] [ (AM) ▾ ]│
├──────────────────────────────────────────────────────────────────────────┤
│  ┌───────────────┐ ┌──────────────────────────────────────────────────┐  │
│  │ FILTERS (280px)│ │ 1,240 jobs                                      │  │
│  │               │ │ [Sort: Most recent ▾]                            │  │
│  │ Category       │ │ ┌────────────────────────────────────────────┐  │  │
│  │ ( ) All        │ │ │ [logo] Senior PHP Developer         [☆]     │  │  │
│  │ ( ) IT         │ │ │ TechCorp · Makati                            │  │  │
│  │ ( ) Finance    │ │ │ [Full-time][Hybrid] · 80k–120k · 3d ago     │  │  │
│  │ ( ) Healthcare │ │ │ ...                                          │  │  │
│  │ ( ) ...        │ │ └────────────────────────────────────────────┘  │  │
│  │                │ │ ┌────────────────────────────────────────────┐  │  │
│  │ Job Type       │ │ │ ... next card ...                          │  │  │
│  │ [ ] Fulltime   │ │ └────────────────────────────────────────────┘  │  │
│  │ [ ] Parttime   │ │ (x more cards…)                                 │  │
│  │ ...            │ │                                                 │  │
│  │ Work Mode      │ │ [ < Prev ]  1  2  3 … 40   [ Next > ]           │  │
│  │ [ ] Onsite     │ │ "Showing 1–10 of 1,240"                        │  │
│  │ [ ] Remote     │ │                                                 │  │
│  │ [ ] Hybrid     │ └──────────────────────────────────────────────────┘  │
│  │                │                                                    │
│  │ Experience     │                                                    │
│  │ ( ) Entry...   │                                                    │
│  │                │                                                    │
│  │ [Clear all]    │                                                    │
│  └───────────────┘ └──────────────────────────────────────────────────┘  │
│  Search & keyword filter live in header bar across top                    │
├──────────────────────────────────────────────────────────────────────────┤
│ Footer                                                                │
└──────────────────────────────────────────────────────────────────────────┘
```

**Tablet:** filters collapse to a `[Filters ▾ (2)]` trigger above results → bottom sheet (see Mobile).
**Mobile:** header search stacks; `[Filters (n active)]` primary button opens bottom-sheet filter drawer with sticky `[Apply filters]` + `[Reset]`; result cards single column; pagination → `[Load more]` or compact prev/next + count.

---

## 3. Job Details

Desktop:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [◆ HirePeak]  Find Jobs · My Applications · Saved Jobs    [🔔] [ (AM) ▾] │
├──────────────────────────────────────────────────────────────────────────┤
│  ← Back to results                                                      │
│  ┌──────────────────────────────────────────┐  ┌───────────────────────┐ │
│  │ [logo]  Senior PHP Developer             │  │ Apply panel (sticky)  │ │
│  │         TechCorp Solutions               │  │ ┌───────────────────┐ │ │
│  │         📍 Makati · [Full-time][Hybrid]   │  │ [Apply Now →]      │ │ │
│  │         PHP 80,000–120,000 · 3d ago      │  │ [☆ Save job]        │ │ │
│  │                                          │  │ ──────────────────   │ │ │
│  │  About the role                          │  │ Deadline: 31 Dec 2026│ │ │
│  │  Responsibilities                        │  │ Applications: 24     │ │ │
│  │  Requirements                            │  │ Posted: 3d ago       │ │ │
│  │  Benefits                                │  │ Job ID: HP-1024      │ │ │
│  │                                          │  │ ──────────────────   │ │ │
│  │  ————— prose max 72ch —————               │  │ [logo] About company │ │ │
│  │                                          │  │ TechCorp Solutions   │ │ │
│  │                                          │  │ Software · 50–200    │ │ │
│  │                                          │  │ [Visit website]      │ │ │
│  └──────────────────────────────────────────┘  └───────────────────────┘ │
│                                                                          │
│  Related Jobs                                                            │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐                     │
│  │ job card     │ │ job card     │ │ job card     │                     │
│  └──────────────┘ └──────────────┘ └──────────────┘                     │
├──────────────────────────────────────────────────────────────────────────┤
│ Footer                                                                    │
└──────────────────────────────────────────────────────────────────────────┘
```

**Mobile:** two columns stack; sticky bottom action bar `[Apply Now] [☆]` appears over content and recedes near the apply panel; prose padding 20px; related jobs single column. Deadline & company facts move into content flow (after requirements) rather than a crammed aside.

---

## 4. Login

Centered auth shell (440px):

```
┌─────────────────────────────────────────┐
│              [◆ HirePeak]               │
│                                         │
│  Welcome back                          │
│  Sign in to continue to HirePeak.      │
│                                         │
│  Email                                  │
│  ┌───────────────────────────────────┐ │
│  │ you@company.com                   │ │
│  └───────────────────────────────────┘ │
│  Password                  [ Show 👁 ] │
│  ┌───────────────────────────────────┐ │
│  │  •••••••••••                      │ │
│  └───────────────────────────────────┘ │
│  [ Forgot password? ]                  │
│                                         │
│  [           Sign In         ]         │  ← primary, loading state
│                                         │
│  New to HirePeak?  [ Create account ]  │
├─────────────────────────────────────────┤
│  © 2026 HirePeak · Terms · Privacy      │
└─────────────────────────────────────────┘
```

Behavior: persistent labels; inline validation (`role="alert"` on error); loading spinner in button; disabled until valid; success → redirect to role dashboard.

---

## 5. Register (with Role Selection)

```
┌─────────────────────────────────────────┐
│              [◆ HirePeak]               │
│                                         │
│  Create your account                   │
│  Choose how you'll use HirePeak.       │
│                                         │
│  ┌─────────────────────┐ ┌─────────────┐ │
│  │ (•)  I'm an applicant│ │ ( ) Employer│ │  ← radio cards
│  │      Find work.      │ │  Post jobs. │ │
│  └─────────────────────┘ └─────────────┘ │
│                                         │
│  Full name                              │
│  ┌───────────────────────────────────┐ │
│  └───────────────────────────────────┘ │
│  Email                                  │
│  ┌───────────────────────────────────┐ │
│  └───────────────────────────────────┘ │
│  Password                [ Show 👁 ]    │
│  ┌───────────────────────────────────┐ │
│  │  •••••••••••                      │ │
│  └───────────────────────────────────┘ │
│  requirements: 8+ chars, letter+number │
│                                         │
│  [          Create Account          ]  │
│                                         │
│  Already have an account? [ Sign in ]  │
└─────────────────────────────────────────┘
```

Roles obvious & compare: applicant vs employer toggles which onboarding follows. After create → verification/toast → role landing (Applicant Dashboard or Employer onboarding → Company Profile).

---

## 6. Applicant Dashboard

Desktop (dashboard shell, sidebar 240px):

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [◆ HirePeak]  Find Jobs · My Apps · Saved     [🔔] [ (JA) ▾ ]           │
├──────────────┬───────────────────────────────────────────────────────────┤
│ ▸ Overview   │   Good morning, John.                                    │
│  Applications│   Here's what's happening with your applications.         │
│  Saved Jobs  │                                                           │
│  Profile     │   ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐            │
│  Settings    │   │ 8 Total│ │ 2 Under│ │ 1 Inter│ │ 1 Offer│            │  ← 4 KPIs
│              │   │ Apps   │ │ Review │ │ view   │ │ /Accep.│            │
│              │   └────────┘ └────────┘ └────────┘ └────────┘            │
│              │                                                           │
│              │  Recent Applications            ┌────────────────────┐   │
│              │  ┌────────────────────────────┐ │ Recommended Jobs  │   │
│              │  │ Senior PHP Dev   Under Rev │ │ [card] [card]     │   │
│              │  │ TechCorp · 3d ago  [View]  │ │ [card] [card]     │   │
│              │  │ Marketing Spec   Interview │ │ [View all →]      │   │
│              │  │ TechCorp · 2d ago  [View]  │ └────────────────────┘   │
│              │  └────────────────────────────┘                            │
│              │  [ View all applications → ]                              │
│              │  ┌────────────────────┐                                  │
│              │  │ Profile completion │  (optional card)                 │
│              │  │  60% ██████░░░░    │                                  │
│              │  └────────────────────┘                                  │
└──────────────┴───────────────────────────────────────────────────────────┘
```

**Mobile:** sidebar → drawer; KPIs 2×2; recent apps cards; recommended sliders horizontal scroll with visible affordance; greeting first.

---

## 7. My Applications

```
┌────────┬──────────────────────────────────────────────────────────────────┐
│ ▸ App  │   My Applications  (12)      [Status: All ▾] [Search]           │
│  ...   │                                                               │
│        │  ┌─────────────────────────────┬──────┬─────────┬──────────┐   │
│        │  │ Job / Company               │ Date │ Status  │ Action   │   │
│        │  ├─────────────────────────────┼──────┼─────────┼──────────┤   │
│        │  │ Senior PHP Developer        │ 3d   │ ◇Under  │ [View]   │   │
│        │  │ TechCorp · Makati           │      │ Review  │ [Cancel] │   │
│        │  │ Marketing Specialist        │ 2d   │ ◇Interv │ [View]   │   │
│        │  │ TechCorp · Remote           │      │          │          │   │
│        │  │ Financial Analyst           │ 12d  │ ◇Reject │ [View]   │   │
│        │  │ ...                         │      │          │          │   │
│        │  └─────────────────────────────┴──────┴─────────┴──────────┘   │
│        │                                                               │
│        │  [ ← Prev ]  1 2 3 …   [ Next → ]                            │
└────────┴─────────────────────────────────────────────────────────────────┘
```

Status filter = chips (`All | Applied | Under Review | Interview | Accepted | Rejected`), or select. **Cancel** visible only for cancellable statuses (Applied/Under Review) and triggers confirmation. Mobile: rows → stacked cards (Job/Company, chips, Applied date, View).

---

## 8. Application Tracking (signature)

Open application detail — "Tracking view," applicant side.

Desktop:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [◆ HirePeak]  My Applications > Backend Developer — Application        │
│                                                                          │
│  Senior Backend Developer                         Status: ◇ Interview   │
│  TechCorp Solutions · Applied 12 Jul 2026                               │
│                                                                          │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  ✓ Applied             ✓ Under Review        ● Interview            │ │
│  │  12 Jul 2026           18 Jul 2026           (current — brand ring)  │ │
│  │  Submitted success.    Recruiter reviewed.   Next: recruiter call    │ │
│  │                                                                       │ │
│  │         ────────────  →  ──────────────  →        ○ Accepted         │ │
│  │                                                                       │ │
│  │  (Rejected is a terminal branch shown as a quiet red alternative,    │ │
│  │   never as a default onward step.)                                   │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                                                                          │
│  Job details (condensed)          │  Company: TechCorp                  │
│  Deadline · salary · location     │  [Visit website]                    │
│                                                                          │
│  [ Withdraw application ]  (visible only if cancellable)                │
└──────────────────────────────────────────────────────────────────────────┘
```

Rules: current step emphasized with brand ring + label "current", completed steps show `✓` + date, upcoming muted `○` with no dates. Rejected never placed visually in the happy path. Mobile: vertical timeline, steps top→bottom.

---

## 9. Applicant Profile

```
┌────────┬──────────────────────────────────────────────────────────────────┐
│ ▸ Ovr  │  My Profile                                        [ Save ]     │
│  App   │                                                               │
│  Saved │  ┌─────────────────── Personal ──────────────────────────────┐  │
│ ▸Profile│  │ First name   Last name                                   │  │
│  ...   │  │ [ John      ] [ Applicant ]                              │  │
│        │  │ Headline                                                │  │
│        │  │ [ Senior PHP Developer ]                                │  │
│        │  └──────────────────────────────────────────────────────────┘  │
│        │                                                               │
│        │  ┌─────────────────── Professional ──────────────────────────┐  │
│        │  │ Summary [...................]                             │  │
│        │  │ Skills  [ PHP, MySQL, JS ]   Experience [ 6 years ▾ ]     │  │
│        │  └──────────────────────────────────────────────────────────┘  │
│        │                                                               │
│        │  ┌─────────────────── Contact ───────────────────────────────┐  │
│        │  │ Email [ locked ]  Phone [ +63... ]  Location [ Makati ]    │  │
│        │  └──────────────────────────────────────────────────────────┘  │
│        │                                                               │
│        │  ┌─────────────────── CV ────────────────────────────────────┐  │
│        │  │ [ Upload CV ]  or  current: resume.pdf (23 Mar)  [×]      │  │
│        │  └──────────────────────────────────────────────────────────┘  │
│        │                                                               │
│        │  ┌─────────────────── Account ───────────────────────────────┐  │
│        │  │ Email · Password [Change] · Danger zone (delete account)   │  │
│        │  └──────────────────────────────────────────────────────────┘  │
└────────┴─────────────────────────────────────────────────────────────────┘
```

Sections are separate cards, not one giant form. "Save" persists whole form with toast "Profile saved". Mobile: single column, all good.

---

## 10. Employer Dashboard

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [◆ HirePeak]  Dashboard · Jobs · Applicants · Company   [Post a Job →]  │
├──────────┬───────────────────────────────────────────────────────────────┤
│ ▸ Overvw │   Good morning, Alice.                                       │
│  Jobs    │   You have 3 pending applications needing review.            │
│  Apps    │                                                              │
│ Company  │   ┌────────┐ ┌────────┐ ┌────────┐ ┌────────┐               │
│          │   │ 12     │ │ 48     │ │ 6      │ │ 2      │               │
│          │   │ Active │ │ App    │ │ Interview│ │ Hired  │              │
│          │   │ Jobs   │ │ lications │ │        │ │        │              │
│          │   └────────┘ └────────┘ └────────┘ └────────┘               │
│          │                                                              │
│          │  Recent Applications            ┌──────────────────────────┐ │
│          │  ┌────────────────────────────┐ │ Recruitment Pipeline    │ │
│          │  │ Emma S. → Frontend Dev     │ │ Applied 20 │ Review 11  │ │
│          │  │ 2h ago  [New]  [Review]    │ │ Interview 6│ Hired 2    │ │
│          │  │ John A. → Senior PHP Dev   │ │ (Rejected 6)            │ │
│          │  │ 1d ago [Review]            │ └──────────────────────────┘ │
│          │  │ ...                        │                              │
│          │  └────────────────────────────┘                              │
│          │  Active Jobs (top 4, condensed) [View all]                  │
└──────────┴───────────────────────────────────────────────────────────────┘
```

The "pending applications" banner is the attention anchor. Mobile: sidebar drawer; KPIs 2×2; pipeline wraps.

---

## 11. My Jobs

```
├──────────┬───────────────────────────────────────────────────────────────┤
│ ▸ Overvw │   My Jobs (17)                    [+ Post a Job]  [Search]   │
│ ▸Jobs    │                                                              │
│  Apps    │  ┌──────────────┬────────┬───────┬─────────┬──────────┬─────┐ │
│          │  │ Job          │ Status │ Apps  │ Published│ Deadline │ Act.│ │
│          │  ├──────────────┼────────┼───────┼─────────┼──────────┼─────┤ │
│          │  │ Senior PHP   │ Active │ 14    │ 1 Aug   │ 31 Dec   │ [⋯] │ │
│          │  │ Marketing    │ Active │ 9     │ 3 Aug   │ 15 Nov   │ [⋯] │ │
│          │  │ Fin Analyst  │ Closed │ 12    │ 2 Jun   │ 30 Jun   │ [⋯] │ │
│          │  │ Junior Web   │ Draft  │ —     │ —       │ —        │ [⋯] │ │
│          │  └──────────────┴────────┴───────┴─────────┴──────────┴─────┘ │
│          │  [⋯] = View · Edit · Applicants · Close · Delete (danger)     │
│          │  Table fallback → cards on mobile with same actions.          │
└──────────┴────────────────────────────────────────────────────────────────┘
```

Delete only inside the `[⋯]` menu, styled danger, with confirmation modal. Status filter chips: All/Active/Closed/Draft.

---

## 12. Create Job (form, grouped)

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [◆ HirePeak]  Jobs > Create Job                       [Save draft]  on   │
│                                                                          │
│  ┌────────────────────┐  ┌────────────────────────────────────────────┐  │
│  │ 1 Basic info      ✓  │  Basic Information                          │  │
│  │ 2 Job details     ✓  │  Job title     Category                     │  │
│  │ 3 Requirements    ○  │  [ Senior PHP Dev ] [ IT ▾ ]                │  │
│  │ 4 Compensation    ○  │  Summary / description [textarea]           │  │
│  │ 5 Location & mode ○  │                                             │  │
│  │ 6 Application set ○  │  ← stepper rail (progress)                 │  │
│  └────────────────────┘  └────────────────────────────────────────────┘  │
│                                                                          │
│  Each step: 30–40% of the form. Validation inline, summary on submit.    │
│  Primary CTA at the end:  [ Publish job → ]                             │
└──────────────────────────────────────────────────────────────────────────┘
```

Mobile: stepper collapses to a compact step indicator; fields stack; sticky bottom `[Publish job]`. Help text + character guidance under title/description.

---

## 13. Job Applicants

```
├──────────┬───────────────────────────────────────────────────────────────┤
│ ▸ Apps   │  Job: Senior PHP Developer   (14 applicants)   [View job]    │
│          │  [ Search candidates ]   [Status: All ▾]                      │
│          │                                                              │
│          │  ┌────────────────────────────┬────────┬─────────┬─────────┐  │
│          │  │ Candidate                  │ Applied│ Status  │ Action  │  │
│          │  ├────────────────────────────┼────────┼─────────┼─────────┤  │
│          │  │ (JA) John Applicant        │ 3d     │ ◇Under  │[Review] │  │
│          │  │     Senior PHP Dev · Makati│        │ Review  │         │  │
│          │  │ (ES) Emma Seekers          │ 2h     │ ◇Applied│[Review] │  │
│          │  │     Marketing Spec · Quezon│        │         │         │  │
│          │  │ ...                       │        │         │         │  │
│          │  └────────────────────────────┴────────┴─────────┴─────────┘  │
│          │                                                              │
│          │  [ ← Prev ]   [ Next → ]   Showing 1–10 of 14               │
└──────────┴─────────────────────────────────────────────────────────────────┘
```

Mobile: candidate cards (avatar, name, title, applied, status badge, Review button) — no forced horizontal scroll.

---

## 14. Employer Application Details

```
┌──────────────────────────────────────────────────────────────────────────┐
│ [◆ HirePeak]  Applicants > Senior PHP Developer > John Applicant        │
│                                                                          │
│  Candidate                        Status                                  │
│  (JA) John Applicant              ◇ Under Review                         │
│  Senior PHP Developer             Applied 3d ago                         │
│  Makati · John's phone (encrypted)                                       │
│  ┌────────────────────────────┐   ┌────────────────────────────────────┐ │
│  │ Profile (from applicant    │   │ [  Update Status ▾ ]              │ │
│  │  profile): summary, skills,│   │  Apply → Under Review (current)    │ │
│  │  experience                │   │  ── choose:  Interview │ Accepted  │ │
│  │  Cover letter              │   │  Rejected is separate & guarded    │ │
│  │  CV: [resume.pdf]          │   └────────────────────────────────────┘ │
│  └────────────────────────────┘    Counts: how many in each stage        │
│                                                                          │
│  Update Status opens a confirmation modal — selection + explicit confirm │
│  (reject requires typed choice, never one unsafe click).                 │
└──────────────────────────────────────────────────────────────────────────┘
```

Layout hierarchy: identity → status + action → profile → letter → CV → metadata/actions. Mobile: single column; status action becomes sticky bottom bar.

---

*End of wireframes. All layouts follow the tokens & rules in `UI_UX_DESIGN_SYSTEM.md`.*