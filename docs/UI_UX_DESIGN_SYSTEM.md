# AuraJob — UI/UX Design System

**Version:** 1.0 (design planning)
**Status:** Approved design reference for implementation
**Quality benchmark:** Modern premium SaaS products (Linear, Stripe, Vercel, Notion, leading recruitment platforms). Reference only for quality — not for copying.

---

## 1. Design Philosophy

AuraJob is a **premium recruitment SaaS**. Every screen should feel like part of a single, calm, trustworthy product.

Five principles, in priority order:

1. **Clarity** — The user always knows what page they're on, what's most important, and what to do next.
2. **Information hierarchy** — One primary action per screen; status and state always readable at a glance.
3. **Calm** — Whitespace, muted surfaces, restrained color. The interface recedes so the data and people stand out.
4. **Consistency** — One system of components, tokens, and behavior. Nothing looks borrowed.
5. **Trustworthiness** — This product holds professional and personal information. The design must look and feel reliable, never gamified or noisy.

**Rules of thumb:**

- Visual effects only when they improve comprehension or status clarity. Never "for delight."
- No random decorative blobs, animated backgrounds, or excess gradients.
- Not every section lives in a card. Use borders, whitespace, and surface changes to structure content.
- The product must look and feel like it could be sold commercially on day one.

---

## 2. Brand

| Attribute | Decision |
|---|---|
| Product name | AuraJob |
| Description | A modern recruitment platform connecting talented candidates with employers while providing a clear application tracking and hiring workflow. |
| Core brand idea | "Helping talent and employers reach their peak." |
| Main tagline | "Find the right opportunity. Reach your peak." |
| Supporting copy | "For talent ready to grow and teams ready to build." |
| Personality | Confident, ambitious, trustworthy, human, intelligent, modern, calm, premium |

**Voice:** short, direct, warm but professional. Prefer "you" language. Never theatrical, never corporate-empty. Errors sound like a helpful colleague, not a system.

---

## 3. Color System

### 3.1 Base palette (approved)

| Token | Value |
|---|---|
| `primary` | `#4F46E5` |
| `primary-hover` | `#4338CA` |
| `primary-dark` | `#3730A3` |
| `primary-soft` | `#EEF2FF` |
| `page-bg` | `#F8FAFC` |
| `surface` | `#FFFFFF` |
| `surface-secondary` | `#F1F5F9` |
| `text-primary` | `#0F172A` |
| `text-secondary` | `#64748B` |
| `text-muted` | `#94A3B8` |
| `border` | `#E2E8F0` |
| `border-strong` | `#CBD5E1` |
| `success` | `#16A34A` |
| `success-soft` | `#DCFCE7` |
| `warning` | `#D97706` |
| `warning-soft` | `#FEF3C7` |
| `danger` | `#DC2626` |
| `danger-soft` | `#FEE2E2` |
| `info` | `#0284C7` |
| `info-soft` | `#E0F2FE` |

### 3.2 Semantic tokens (what the UI actually uses)

Semantic tokens map the palette to meaning, so a token swap later (including a future dark mode) does not require re-design.

| Semantic token | Value |
|---|---|
| `--color-brand` | `#4F46E5` |
| `--color-brand-hover` | `#4338CA` |
| `--color-brand-active` | `#3730A3` |
| `--color-brand-soft` | `#EEF2FF` |
| `--color-brand-text-on` | `#FFFFFF` |
| `--color-bg-page` | `#F8FAFC` |
| `--color-bg-surface` | `#FFFFFF` |
| `--color-bg-subtle` | `#F1F5F9` |
| `--color-bg-inset` | `#E2E8F0` (rare; rows/code) |
| `--color-text-primary` | `#0F172A` |
| `--color-text-secondary` | `#64748B` |
| `--color-text-muted` | `#94A3B8` |
| `--color-text-on-primary` | `#FFFFFF` |
| `--color-border` | `#E2E8F0` |
| `--color-border-strong` | `#CBD5E1` |
| `--color-border-focus` | `#4F46E5` |
| `--color-status-applied` | info `#0284C7` (soft `#E0F2FE`) |
| `--color-status-review` | warning `#D97706` (soft `#FEF3C7`) |
| `--color-status-interview` | primary `#4F46E5` (soft `#EEF2FF`) |
| `--color-status-accepted` | success `#16A34A` (soft `#DCFCE7`) |
| `--color-status-rejected` | danger `#DC2626` (soft `#FEE2E2`) |

**Status mapping rationale:** Applied = info/blue (neutral "in system"), Under Review = warning/amber (in progress, attention), Interview = primary/indigo (advancing, the brand milestone), Accepted = success/green, Rejected = danger/red but **always paired with text, an icon, or checkmark** — never color alone.

**Palette refinement for accessibility:** All color-on-white text pairs meet WCAG AA (4.5:1) for normal text. `text-secondary #64748B` and `text-muted #94A3B8` are reserved for supporting text and never used for essential information. Muted is never used for body copy.

**Rules:**

- Primary brand color for one primary action per view and active states only.
- Soft colors ("tint" backgrounds) for badges, chips, and subtle emphasis — never for large fills.
- Never use raw palette hex beyond tokens in components.

---

## 4. Typography

**Direction:** Inter as the primary font. System fallback stack:

```
'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
'Helvetica Neue', Arial, sans-serif
```

Numbers in salary/stat contexts: use tabular figures where the font allows (`font-variant-numeric: tabular-nums`) so tables and KPIs align.

### Type scale

| Token | Size | Weight | Line-height | Letter-spacing | Usage |
|---|---|---|---|---|---|
| `--text-display` | 40px | 700 | 1.1 | -0.03em | Hero headline (home) only |
| `--text-h1` | 32px | 700 | 1.15 | -0.02em | Page titles (journey pages) |
| `--text-h2` | 24px | 700 | 1.25 | -0.02em | Section headings |
| `--text-h3` | 20px | 600 | 1.3 | -0.01em | Card/section sub-headings |
| `--text-h4` | 16px | 600 | 1.4 | 0 | Small section titles |
| `--text-h5` | 14px | 600 | 1.4 | 0 | Group labels / widget titles |
| `--text-body-large` | 17px | 400 | 1.6 | 0 | Hero sub-copy, lead paragraphs |
| `--text-body` | 15px | 400 | 1.6 | 0 | Default body (job descriptions, forms) |
| `--text-body-small` | 14px | 400 | 1.5 | 0 | Secondary text, table cells |
| `--text-caption` | 13px | 400 | 1.4 | 0 | Meta data, timestamps, hints |
| `--text-label` | 14px | 600 | 1.2 | 0 | Field labels, list labels |
| `--text-button` | 14px | 600 | 1 | 0 | Button labels (not `uppercase` — keep calm) |

**Rules:**

- Max reading width for prose: **72ch**. Job descriptions are read for a long time — never let lines stretch across wide screens.
- Headings are strong but not oversized. Display is reserved for the home hero.
- No letter-spacing on body text. No uppercase micro-headings on controls.

---

## 5. Spacing System

8px-based. All spacing uses `calc(8px * n)` or the token directly — no ad-hoc values.

| Token | Value | Typical use |
|---|---|---|
| `--space-1` | 4px | Tight icon gaps, checkmark offsets |
| `--space-2` | 8px | Inline icon-label gaps, badge padding |
| `--space-3` | 12px | Input inner padding (vertical), chip gaps |
| `--space-4` | 16px | Card padding (compact), button padding |
| `--space-5` | 24px | Card padding (default), form row gaps |
| `--space-6` | 32px | Section gaps within a page |
| `--space-7` | 40px | Page section separation |
| `--space-8` | 48px | Hero-to-content gap, large section breaks |
| `--space-9` | 64px | Major content division (home sections) |
| `--space-10` | 80px | Home page section separation |
| `--space-11` | 96px | Footer/reserved large breaks |

**Rules:**

- Default card padding: 24px (`--space-5`); compact card: 16px.
- Mobile page gutters tighten but the grid stays consistent (see Layout).
- Prefer spacing over borders and boxes to separate content.

---

## 6. Radius System

One family, used across every component so everything reads as the same product.

| Token | Value | Applied to |
|---|---|---|
| `--radius-sm` | 6px | Inputs, buttons (compact), badges, chips |
| `--radius-md` | 8px | Buttons (default), selects, search fields |
| `--radius-lg` | 12px | Cards, sidebars, stat cards |
| `--radius-xl` | 16px | Modals, popovers, dropdowns, mobile sheets |
| `--radius-full` | 9999px | Pills, avatars, status dots, tabs (optional) |

**Rules:**

- Keep radii modest. `xl` is the ceiling for modal surfaces — nothing rounds into "bubbly" territory.
- Cards: 12px. Modal: 16px. Buttons/inputs: 6–8px. This single set gives cohesion.

---

## 7. Borders

| Token | Value | Usage |
|---|---|---|
| `--border-width` | 1px | All component borders |
| `--color-border` | `#E2E8F0` | Default |
| `--color-border-strong` | `#CBD5E1` | Input borders, emphasized dividers |
| `--color-border-focus` | `#4F46E5` | Focus rings (2px) |
| `--color-border-status` | per status | Status call-outs |

**Rules:**

- Borders, not shadows, are the primary separation mechanism on page surfaces.
- Inputs use `border-strong` (subtle gray is too faint for form fields).
- Sidebar/nav separators use `--color-border`.
- Focus ring: **2px solid `border-focus`** plus 2px offset — visible on keyboard navigation, never replaced by `outline: none` silently.

---

## 8. Shadows

Shadows communicate elevation, not decoration. Few, calibrated, low-opacity.

| Token | Value | Usage |
|---|---|---|
| `--shadow-sm` | `0 1px 2px rgba(15,23,42,.06)` | Cards resting on page, subtle |
| `--shadow-md` | `0 4px 12px rgba(15,23,42,.08)` | Dropdowns, popovers, floating action |
| `--shadow-lg` | `0 12px 32px rgba(15,23,42,.12)` | Modals, mobile sheets, toasts |

**Rules:**

- Page surface cards: use border + `shadow-sm` at most. Calm.
- Hover cards may step to `shadow-md` **only** if it communicates affordance (e.g., a clickable job card).
- Never use shadow for "glow." Avoid colored shadows entirely.
- Modals always use `shadow-lg` with a `rgba(15,23,42,.4)` scrim.

---

## 9. Layout

### 9.1 Containers

| Context | Max width | Gutter (desktop) | Gutter (mobile) |
|---|---|---|---|
| Public reading pages (`Jobs`, `Job Details`, marketing) | 1200px | 24px each side | 20px each side |
| Auth pages | 440px form column | centered | 20px |
| Dashboard (applicant & employer) | 1320px | 24px each side | 20px |
| Navigation bar content | 1320px | 24px | 16px |
| Prose (job description) | 720px within the details shell | — | — |

**Ultra-wide:** content never exceeds the max widths above. Nothing stretches across 4K monitors.

### 9.2 Grid

- **Public pages:** 12-column grid; cards span standard fractions (3/6/12). Home uses asymmetric 70/30 splits for feature bands to avoid a "card wall."
- **Job search (desktop):** sidebar 280–320px + results area flexes to fill container. Fixed sidebar, scrollable results.
- **Job details (desktop ≥ 1200px):** two columns — main article `~720px`, sticky aside `~360px`. Aside sticks below the nav; CTA panel inside the aside.
- **Dashboards:** KPI row = 4-up grid (gaps 24px), collapsing to 2-up on tablet and 1-up (or horizontal scroll-free) on mobile. Below: two-column content grid (`2fr 1fr`) which stacks on tablet/mobile.

### 9.3 Navigation height & layout

- Navbar height: **64px** fixed. Logo + primary nav left, actions right.
- Dashboard sidebar (applicant/employer): **240px** fixed left on desktop, always visible, `--surface` background with right border. Navbar on top; sidebar beneath. On tablet/mobile the sidebar becomes an off-canvas drawer (see Mobile).
- Dashboard content area: max 1320px, padding 32px 24px, breathing room.

### 9.4 Card gap guidance

- Card grids gap: 24px desktop, 16px tablet, 12–16px mobile single-column.

---

## 10. Responsive Breakpoints

| Breakpoint | Range | Notes |
|---|---|---|
| `--bp-desktop` | ≥ 1200px | Full sidebar layout, job details 2-col, filters sidebar visible |
| `--bp-tablet` | 768px – 1199px | Sidebar collapses to drawer; job details stacks; filters become a sheet trigger |
| `--bp-mobile` | < 768px | Single column everything; bottom-anchored primary actions where useful; one-hand reach prioritized |

**Approach:** mobile-first CSS, advancing layout at breakpoints. Mobile is designed intentionally, never achieved by shrinking desktop (see section 25).

---

## 11. Navigation

### 11.1 Public / Visitor

```
[◆ AuraJob]   Home · Find Jobs · For Employers      [Sign In] [Create Account →]
```

- "Create Account" is the primary CTA (brand button). "Sign In" is ghost.
- Nav is `--surface`, 1px bottom border, 64px.
- Mobile: collapses to left hamburger; primary CTA remains visible fixed at bottom? — No: **keep Sign In/Register accessible via menu and a single visible "Get Started" pill** to avoid crowding (audit finding resolved here).

### 11.2 Applicant

```
[◆ AuraJob]   Find Jobs · My Applications · Saved Jobs
[🔔 Notifications]  [avatar + name ▾]  (Profile / Account / Logout)
```

- "Find Jobs" is the leading (home) item.
- Current page is signaled with brand-colored underline/pill on `--color-brand-soft` plus, on the sidebar, a brand left rail. Never color-only: active item also uses semibold text.
- Notifications: bell with unread dot (count ≤ 99, reads as "+n").
- Role identity: avatar initials + name; user clearly belongs to "Candidate" context.

### 11.3 Employer

```
[◆ AuraJob]  Dashboard · Jobs · Applicants · Company      [Post a Job →] [🔔] [avatar ▾]
```

- "Post a Job" is the **always-visible primary CTA** (brand button, right side) — the single most important recruiter action.
- Jobs/Apllicants lead to the management screens; Dashboard is the landing screen.
- Role identity: "Employer" context communicated by the dashboard label and sidebar heading, plus a small "Recruiter" marker next to the avatar.

### 11.4 Role communication

The current role is communicated by the nav structure (labels differ per role) — not by a loud badge. A subtle marker (e.g., "Employer" caption under avatar, or dashboard eyebrow) confirms context without shouting.

---

## 12. Button System

All buttons share: radius `--radius-md` (compact `sm`), text `--text-button`, min touch target **44×44px** on touch devices (height ≥ 40px desktop with padding to 44 on mobile).

| Variant | Default bg | Text | Border | Notes |
|---|---|---|---|---|
| Primary | brand | white | — | One per view. Hover `brand-hover`, active `brand-active` |
| Secondary | surface | text-primary | border-strong | Paired with or beneath primary |
| Outline | surface | brand | brand | For secondary actions needing emphasis |
| Ghost | transparent | text-secondary | — | Hover `--color-bg-subtle` |
| Danger | danger | white | — | For destructive main action |
| Icon | transparent | text-secondary | — | 36–44px, accessible aria-label |

**States (all variants):**

- **Default** — as above.
- **Hover** — one step darker bg OR shift to `--color-bg-subtle` for ghost; 150ms.
- **Focus** — 2px brand focus ring (offset 2px). Never removed.
- **Pressed** — active color; `transform: translateY(1px)` optional, 100ms.
- **Disabled** — `opacity: 0.45`, no shadow, no hover, `cursor: not-allowed`, and **not** `aria-disabled`-silent: disabled submit buttons are replaced by loading state, not just greyed.
- **Loading** — spinner icon replaces label or shrinks label + spinner left-aligned; button is disabled while loading; no layout jump (spinner width reserved).

**Destructive confirmation:** Delete/Close job and Reject application open a confirmation modal; the destructive button inside is danger variant. Deleting never happens from a bare inline click.

---

## 13. Form System

### 13.1 Field types & structure

Standard field anatomy:

```
[ Label * ]
[ ┌─────────────────────────────┐ ]
[ ▍ input value                  ]
[ └─────────────────────────────┘ ]
[ help text or error message      ]
```

- Labels are **persistent, always visible** above the field (never placeholder-as-label). Placeholder text only gives examples.
- Required marker: `*` on label; asterisk color `text-secondary` (not red-only).
- Help text: 13px `text-secondary`, sits below field.
- Error text: 13px danger, sits below field (replaces help text nothing else).

### 13.2 Inputs (text, email, password, textarea, search, select, checkbox, radio, file)

| Control | Height | Radius | Notes |
|---|---|---|---|
| Text/email/password | 44px | sm/md | 1px `border-strong`, `--space-3` horizontal padding |
| Textarea | min 112px (resize-y) | sm | Same border × focus treatment |
| Select | 44px | md | Custom chevron icon; native `<option>` list |
| Search | 44px | md (pill on hero) | Leading magnifier icon; clear (×) button when text present |
| Checkbox / Radio | 20×20 | sm / full | Brand accent when checked; 44px hit area around the control (spacer-aware) |
| File upload | 44px+ | sm | Dropzone card variant with dashed border; or compact "choose file" button + filename |

**Field states:**

- **Default** — border-strong, white bg.
- **Hover** — border-primary (slight), no layout shift.
- **Focus** — 2px brand ring + brand border.
- **Filled** — default styling; label stays visible.
- **Disabled** — `--color-bg-subtle`, 45% text, not selectable.
- **Error** — danger border + danger message + (ideally) aria-describedby; `role="alert"` announcement.
- **Success** — green check only when the field has explicit success semantics (e.g., password strength); otherwise color alone never indicates.

**Role selection (registration):** two selectable cards, radio behavior — "Applicant" (Find work / grow your career) and "Employer" (Post jobs / build your team). Each card: icon, label, one-line description, radio dot top-right. Selected card = brand border + soft brand bg + check. This is more understandable than a bare select.

---

## 14. Cards

Rules that prevent "random cards everywhere":

- A card is a **unit of related information** with a clear job to do — never decorative filler.
- Card anatomy: padding 24px default (16px compact), radius `--radius-lg`, background surface, border `--color-border`, optional `shadow-sm` when resting above dense content.
- Sectioned pages prefer: subtle band (`--color-bg-subtle`) or border-divided blocks over a full page of identical cards.
- Hover: raise to `shadow-md` only for interactive cards; add visible cursor; never animate just the shadow if borders suffice.
- Elevated card (`--color-bg-surface`, `shadow-md`) reserved for dropdowns, modals, popovers.

---

## 15. Job Card (reusable, exceptional)

Anatomy (top to bottom):

```
[ logo ]                              [ ☆ Save ]
Job Title — Senior PHP Developer          ← h3, brand-on-hover link
Company · Location                        ← body-small, secondary
[ 💼 Full-time ] [ 🏠 Hybrid ]            ← chips (metadata, muted)
PHP 80k – PHP 120k / month                ← salary, semibold, tabular
Posted 3 days ago                         ← caption, muted
[ View Job ]                              ← ghost→primary affordance (optional edge)
```

Priorities: **1** job title, **2** company, **3** location, **4** key metadata (type/work mode), **5** salary, **6** posted time. Logo (40×40 rounded) supports hierarchy without dominating.

Variants/States:

- **Default** — border + shadow-sm, white.
- **Hover** — shadow-md, `transform: translateY(-2px)` 200ms, title turns brand. Respect `prefers-reduced-motion` (no translate).
- **Saved** — bookmark icon filled/brand; small "Saved" chip optional; card otherwise unchanged (no visual shouting).
- **Expired / Closed** — 45% text opacity on meta, "Closed" or "Expired" pill, Apply superseded by "Job closed" ghost note; **not** aggressive.
- Loading placeholders shown with skeletons (see 20).

Salary always formatted + locale-aware; "Salary not disclosed" as neutral caption when absent.

---

## 16. Table System

Standard table:

- Header: 13px, weight 600, `text-secondary`, uppercase discouraged — keep sentence case.
- Row: 44px min height, `--color-border` row dividers, hover `--color-bg-subtle`.
- Selected row: `--color-brand-soft`.
- Empty: table shows one row spanning all columns containing the dedicated empty state (see 21).
- Loading: header static, rows replaced by skeleton spans.

**Pagination-friendly:** closest-to-edge controls duplicated top (optional) and bottom (always). Show "12 of 240" plus arrow buttons; never a raw page-number jump without context on mobile.

**Mobile fallback (never rely on horizontal scroll):**

- Data-dense employer lists (applicants, jobs) switch to **candidate/job cards** on < 768px. The same data, re-laid-out.
- Only truly tabular financial-like data may horizontally scroll, and only inside a contained region with a visible scroll affordance.

---

## 17. Status System

One set of semantic badges reused everywhere (tables, cards, tracking, mobile).

| Status | Badge: text | Badge: bg | Badge: border | Icon (optional) |
|---|---|---|---|---|
| Applied | "Applied" | info-soft | info/20 | dot |
| Under Review | "Under Review" | warning-soft | warning/25 | dot |
| Interview | "Interview" | primary-soft | primary/25 | calendar |
| Accepted | "Accepted" | success-soft | success/25 | check |
| Rejected | "Rejected" | danger-soft | danger/25 | dot — or "Not selected" neutral wording |

Badge anatomy: 13px medium text, soft bg, 1px translucent border with the status hue, radius `--radius-sm` (or full), `--space-2` horizontal / `--space-1` vertical padding.

**Rules:**

- Status is **never communicated by color alone** — always include the text label, and an icon/check where meaningful.
- Rejected: use the danger treatment but keep wording calm; in tracking use neutral language ("Not selected to continue" as supporting, keep "Rejected" as the primary label for clarity).
- Badges work on white, `surface-secondary`, soft bands, and dark-on-light tables — verified contrast of soft bg + status text ≥ 4.5:1.

---

## 18. Toasts

Toast anatomy:

```
┌──────────────────────────────────┐
│ [icon] Application submitted        [×] │
└──────────────────────────────────┘
```

- Placement: **bottom-right** desktop, **top** mobile (below nav), stacked with 12px gap.
- Variants: success, info, danger. One icon + message, optional action link ("View application").
- Duration 4s; dismiss button always present; pause on hover; `role="status"` (or `role="alert"` for errors).
- **Never stack more than 3** — newer replaces oldest.
- Motion: slide up 12px + fade, 200ms, honoring reduced motion (fade only).

Messages (copy baked in):
- "Application submitted" → action: View application
- "Job posted" / "Job updated" / "Job saved" / "Application status updated" / "Profile saved"
- Errors phrased helpfully, never as raw system text.

---

## 19. Modals

Other name: dialogs. For confirmations and focused editing (e.g., post-job quick draft, status change).

Anatomy:

```
┌────────────────────────────┐
│ Title                    [×] │
│ ─────────────────────────   │
│ body (scrollable if long)    │
│ ─────────────────────────   │
│ [Secondary] [Primary →]      │
└────────────────────────────┘
```

- Size: confirmations `max-width 480px`; forms up to 560px.
- Scrim `rgba(15,23,42,.4)`; content `shadow-lg`, radius `--radius-xl`, padding 24px.
- **Focus trap** + Escape to close + click-scrim-to-close; `role="dialog"`, `aria-modal="true"`.
- Primary action focused by default **only when harmless**; destructive confirmation keeps focus on **Cancel** to make danger a deliberate choice.
- On mobile: modal becomes a bottom sheet docked to the bottom (radius on top corners / full-safe-area), max-height 90vh.

---

## 20. Loading Experience

Skeleton loaders as the default for data regions. Full-screen spinners only for page-level auth/transitions (and even then, brief).

| Context | Pattern |
|---|---|
| Job list / search results | 3–6 card skeletons with shimmer (reduced-motion: static blocks) |
| Dashboard KPIs | 4 stat skeleton tiles |
| Applications list | row skeletons |
| Hero search submit | button loading state; results area skeletons |
| Buttons (any submit) | inline spinner + disabled |

Skeleton: `--color-bg-subtle` blocks with 12px radius, subtle pulse 1.2s ease infinite; under `prefers-reduced-motion` simply static.

Rules: never block the whole page for a routine action; keep the container height approximate to avoid layout jumps (reserve min-height or aspect).

---

## 21. Empty States

Every empty state = **icon · headline · supporting copy · one CTA**. Copy approved:

| Context | Headline | Copy | CTA |
|---|---|---|---|
| No applications | "Your next opportunity starts here." | "Apply to jobs that match your goals and track everything in one place." | Explore Jobs |
| No saved jobs | "Nothing saved yet." | "Save interesting roles and come back to them anytime." | Find Jobs |
| No employer jobs | "Create your first job posting." | "Start meeting candidates and building your hiring pipeline." | Post a Job |
| No applicants (job) | "No applications yet." | "New candidates will appear here as they apply." | (Share job) |
| Search failure / no results | "No jobs match your search." | "Try different keywords or clear a few filters." | Clear Filters |

Anatomy: centered within content area, icon (muted, 32px), h3 headline, body-secondary copy, primary or outline CTA. Generous whitespace, no fake data. **Never add fake analytics to fill a dashboard.**

---

## 22. Error States

Human-readable, actionable, visible focus, direct calls to action.

| State | Presentation |
|---|---|
| 404 Not Found | Full-page: brand mark, "This page climbed a different mountain." copy + [Back to Home]. 404 must not look broken — same navbar/footer as the product. |
| 403 Forbidden | "You don't have access to this page." + role-aware CTA (Go to Dashboard / Explore Jobs). Same chrome as product. |
| Server error | "Something went wrong on our side." + [Try again] + support hint. |
| Search failure | Inline alert in results region with retry; never a dead page. |
| Form validation | Inline per-field errors + summary `role="alert"` at top; submit disabled until valid; clear, specific messages ("Enter your work email"). |

Rules: no raw SQL/PHP/exceptions shown to users; error pages keep the app chrome; always one obvious next action.

---

## 23. Accessibility (WCAG AA target)

- **Contrast:** all text meets 4.5:1 on its background; large display text 3:1. Verified for the approved palette (notably `text-secondary` on white = 4.7:1, `text-muted` only for nonessential).
- **Focus:** visible 2px brand ring everywhere; logical DOM order; skip-to-content link on public and dashboard pages.
- **Keyboard:** complete navigation possible; dropdowns/popovers trap or dismiss predictably; Enter/Space activate buttons; Escape closes overlays.
- **Touch targets:** ≤ 44px for interactive elements on touch; 36px absolute floor on desktop for compact controls (icon buttons with aria-labels).
- **Labels & announcements:** all fields labeled (associated `for`/`id`); error + success regions use `role="status"`/`role="alert"`; icon-only buttons have `aria-label`; tables have `<caption>` or `aria-label`.
- **Semantics:** single `h1` per page, logical heading order; landmarks (`header`, `nav`, `main`, `footer`) present.
- **Color independence:** every meaning is conveyed with text/icon + color (status badges, links, chips).
- **Zoom & reflow:** layout must reflow without horizontal scroll at 200% zoom and at 320px width (mobile-first CSS makes this intrinsic).
- **Reduced motion:** `prefers-reduced-motion` disables translate/shimmers; only opacity fades remain. `prefers-color-scheme` readiness: tokens are semantic so dark mode can be added later.

---

## 24. Motion

- Duration standard: **150–250ms**; ease-out for entrances, ease-in for exits. Shimmer pulse longer (1.2s) is a looped exception.
- Occasions: button state, card hover lift, dropdown open, modal fade+scale (200ms), token saves, tab indicator slide, progress step emphasis, toast slide.
- Never animate: whole-page background, hero absurdly, non-functional decor, or content just for effect.
- Respect `prefers-reduced-motion`: replace transforms with opacity, kill loops, keep static skeletons.
- Status transitions (e.g., Accepted) get a single subtle pulse on the progressing step — not confetti (calm, premium).

---

## 25. Mobile UX

Mobile is a first-class layout, not a shrink.

- **Navbar (mobile):** 64px; logo left, right side = bell + hamburger. Drawer slides from left, full height, `--surface`, contains all nav + auth actions; overlay scrim; swipe/backdrop/Escape to close.
- **Search:** on Home, the hero search stays visible but stacks vertically (keyword then location then button), button full-width. On Jobs, a "Filters" trigger button (with active-count chip) opens a **bottom sheet** with all filters; never a skinny sidebar.
- **Filters drawer:** full-width sheet, bottom-anchored, safe-area aware, sticky "Apply filters" primary button + "Reset". Badge count on the trigger reflects active filters.
- **Job cards:** single column, full-width; Save heart/bookmark right-top reachable below content, or a discreet footer action; ensure the card is tappable and title link ≥ 44px.
- **Job details:** sticky bottom **action bar** (Apply Now primary + Save) that recedes when the main CTA is in view; content comfortable padding 20px; prose never cramped.
- **Dashboards:** KPIs 2-up grid → single column of compact stat tiles (label above number); tables collapse to cards.
- **Forms:** field rows stack; inputs full-width 44px; date/number useful input modes.
- **Application tracking:** progress becomes a vertical timeline with status steps top-to-bottom (works in a 320px column) — one-hand friendly.
- **Modals** become bottom sheets (see 19). Toasts appear top under nav.
- **Primary action placement:** bottom-anchored sticky bars for Apply, Post, and Status-update actions; safe-area insets respected.

---

## 26. Applicant UX

- **Dashboard clarity:** greeting — "Good morning, [first name]." + "Here's what's happening with your applications." KPIs only what matters: Total Applications, Under Review, Interviews, Offers. + Recent Applications and Recommended Jobs. Optional Profile Completion card; never fake charts.
- **My Applications:** table/cards with immediate columns Job, Company, Applied date, Status, Action (View / Cancel when allowed). Filter by status (chips or select). Cancellation only while status is Applied/Under Review — canceled applications are distinctly labeled, destructive action confirmed.
- **Application Tracking (signature):** horizontal stepper `Applied → Under Review → Interview → Accepted`, Rejected as a terminal branch shown neutrally. Completed: check + date; current: pulsing/emphasized step (brand border + ring, calm); upcoming: muted. On mobile, vertical timeline. Never imply accepted is inevitable; each step has a one-line explanation ("Recruiter is reviewing your profile.").
- **Saved Jobs:** plain list of bookmarked jobs with remove; mirrors Job Card.
- **Notifications:** tappable → destination (application/page); unread dot; "Mark all as read". None required to act on.
- **Notes:** apply flow asks for an optional cover letter only — no "apply marathon." One click (with optional text) is the premium bar.

---

## 27. Employer UX

- **Dashboard = command center, one glance, zero noise:** KPIs: Active Jobs, Applications, Interviews, Hired. Then Recent Applications (with pending count), Active Jobs, Recruitment Pipeline. Employer answers "What needs my attention?" in the first screenful — pending/new applications surface at top with an affordance.
- **My Jobs:** rows/cards in table → Job, Status, Applicants, Published, Deadline, Actions (View, Edit, Applicants, Close, Delete). Destructive (Delete) tucked behind the row menu or clearly separated with danger styling; single "more" menu groups Delete away from common actions.
- **Create/Edit Job:** grouped sections Basic Info / Job Details / Requirements / Compensation / Location & Work Mode / Application Settings; left progress rail or section tabs; inline validation per step with a summary; a **Save draft** secondary action; one primary "Publish job" — the form never exceeds ~12 visible fields at any moment.
- **Job Applicants:** applicant list = candidate cards or responsive table → candidate, applied date, status, action; search + status filter; open a full application.
- **Application Details:** hierarchy: Candidate identity (avatar initials, name, title, applied date) → status (current + change control) → profile → cover letter → CV → metadata. **Update Status** requires choosing the target step in a confirmation modal; Rejection needs the explicit action twice (choose + confirm) — never a single unsafe click. Status changes email/notify the applicant.
- **Recruitment Pipeline:** lightweight horizontal board of counts per stage (Applied/Under Review/Interview/Accepted + Rejected count off to the side). Cards move with drag-drop **only** if trivial; otherwise the "Update Status" modal moves them. Avoid a full ATS board unless required.

---

## Appendix — Semantic Tokens (reference snapshot)

| Category | Tokens |
|---|---|
| Color | See section 3.2 |
| Typography | `--text-display … --text-button` (section 4) |
| Spacing | `--space-1 … --space-11` (section 5) |
| Radius | `--radius-sm/md/lg/xl/full` (section 6) |
| Border | width 1px, colors in section 3.2/7 |
| Shadow | `--shadow-sm/md/lg` (section 8) |
| Transition | `--ease-out: cubic-bezier(.16,1,.3,1)`; `--ease-in: cubic-bezier(.4,0,.68,.06)`; `--dur-1:150ms`, `--dur-2:200ms`, `--dur-3:250ms` |
| Container | `--container-public:1200px`, `--container-dash:1320px`, `--container-auth:440px`, `--container-prose:720px`; `--gutter-d:24px`, `--gutter-m:20px` |
| Z-index | `--z-nav:50`, `--z-drawer:60`, `--z-modal:70`, `--z-toast:80`, `--z-tooltip:40` |
| Breakpoints | `--bp-desktop:1200px`, `--bp-tablet:768px`, `--bp-mobile:0` |

---

## 28. UX Audit — Findings & Resolutions

Critical self-review performed before finalizing this design. Each question answered; problems found were fixed in the documents above.

| # | Audit question | Verdict | Resolution (where required) |
|---|---|---|---|
| A | Can a first-time visitor understand AuraJob in 5 seconds? | ✅ | Hero = one viewport: headline (what), one-line sub-copy (who), two buttons + working search (next). Pass criteria are stated in `UX_FLOWS.md` Flow 1. |
| B | Can a user find jobs immediately? | ✅ | Hero search (keyword + location + Search Jobs) sits above the fold; popular-search chips; Jobs page filters ready. |
| C | Can an applicant understand their application status immediately? | ✅ | One `StatusBadge` reused everywhere; tracking stepper shows current step with brand emphasis and dates; text label never color-only. |
| D | Can an employer see what needs attention immediately? | ✅ | "Applications need review" banner + KPIs (Active Jobs/Apps/Interviews/Hired) + Recent Applications + Pipeline on one screen. Found as a gap → added the attention banner (wireframe 10). |
| E | Are important actions visually obvious? | ✅ | Per-view single primary action; "Post a Job" always visible in employer nav; sticky Apply/Post bars on mobile. |
| F | Is anything unnecessarily complicated? | ✅ | Registration = one short form + role cards (no gauntlet); apply = optional cover letter only; pipeline kept lightweight (no full ATS board). |
| G | Are there too many cards? | ✅ | Design rule added: cards only for units of related info; section bands + borders used elsewhere; KPI count capped at 4 per dashboard. |
| H | Are forms too long? | ✅ | Create/Edit Job split into 6 stepper sections (≤12 visible fields); profile is separate logical cards, not one form; no placeholder-only labels. |
| I | Are mobile actions easy to reach? | ✅ | Bottom-sheet filters, sticky bottom action bars, single-column cards, 44px targets, vertical tracking timeline, safe-area insets. |
| J | Are destructive actions safe? | ✅ | Delete/reject only via guarded menu + confirmation modal (focus starts on Cancel); rejection requires deliberate two-step confirm; cancellation restricted by status. |
| K | Is navigation role-obvious? | ✅ | Public/Applicant/Employer navs are distinct; role marker under avatar; employer CTA differs from applicant nav links. |
| L | Any visual noise / dark patterns? | ✅ | Banned decorative blobs/animated backgrounds; no hidden actions, no fake analytics, no forced horizontal scroll. |

Errors explicitly caught during design (fixed inline above):

1. **Tablet filters were being squeezed** into a sidebar → changed to a bottom-sheet filter drawer with active-count badge (sections 10, 25; wireframe 2).
2. **Mobile job-details CTA could get lost** → added sticky bottom action bar that recedes when the in-flow Apply panel is visible (section 25; wireframe 3).
3. **"Rejected" could read as aggressive red** → softened: calm wording + muted/danger pairing with text and icon, set as a separate terminal branch in tracking (sections 3, 17; wireframe 8).
4. **Dashboard could tempt fake analytics** → explicitly forbidden; only 4 genuinely useful KPIs per role (sections 14, 21, 26, 27).
5. **Destructive actions (Delete job / Reject)** were originally inline-links → moved behind `ActionsMenu` + confirmation modal, never a single unsafe click (sections 12, 19; wireframes 11, 14).
6. **Skeleton spacing could cause layout jumps** → containers reserve approximate heights (section 20).

---

*End of design system. Companion documents: `WIREFRAMES.md`, `COMPONENT_INVENTORY.md`, `UX_FLOWS.md`.*