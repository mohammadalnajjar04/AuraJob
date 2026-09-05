# HirePeak — Component Inventory

Master list of reusable UI components. For each: **purpose, variants, states, where used.**

Base notes: all components pull from the design tokens in `UI_UX_DESIGN_SYSTEM.md` (radius, spacing, color, shadows, motion). Minimum touch target 44px on touch devices.

---

## 1. Navbar (`AppNav`)

- **Purpose:** primary wayfinding + role identity + primary CTA.
- **Variants:** Public / Applicant / Employer / Auth-minimal (auth pages show logo only).
- **States:** scrolled (adds border/`shadow-sm`), mobile (collapsed to drawer trigger).
- **Used in:** every page; role-specific items & CTAs per section 11 of the design system.

## 2. Sidebar (`DashboardSidebar`)

- **Purpose:** role-scoped secondary navigation inside dashboards.
- **Variants:** Applicant links / Employer links.
- **States:** active item (brand left rail + soft bg + semibold), collapsed to off-canvas drawer on <1200px.
- **Used in:** Applicant Dashboard, My Applications, Saved Jobs, Profile; Employer Dashboard, My Jobs, Job Applicants, Company.

## 3. Button (`Button`)

- **Purpose:** trigger an action.
- **Variants:** primary / secondary / outline / ghost / danger / icon; sizes sm/md/lg.
- **States:** default, hover, focus (2px brand ring), pressed, disabled, loading (inline spinner), `aria-busy`.
- **Used in:** everywhere: `Search Jobs`, `Apply Now`, `Post a Job`, `Save`, pagination, forms.

## 4. IconButton (`IconBtn`)

- **Purpose:** compact icon trigger (save, close, menu, notification, more).
- **Variants:** default/ghost; with optional badge (Save/unread count).
- **States:** default, hover (subtle bg), focus, active (toggled = brand), disabled.
- **Used in:** navbar bell, mobile menu, job card save, table row `[⋯]`, modal close.

## 5. Avatar (`Avatar`)

- **Purpose:** user/company identity mark.
- **Variants:** initials (user, colored by name hash), image (company logo/profile), sizes sm(28)/md(40)/lg(56).
- **States:** — (static).
- **Used in:** navbar user menu, applicant cards, company in job cards/details, profile.

## 6. Logo (`BrandLogo`)

- **Purpose:** brand identity mark + wordmark.
- **Variants:** default (mark+word), mark-only (mobile), light-on-primary (auth/CTA bands).
- **Used in:** navbar, auth pages, footer, 404/403 pages.

## 7. JobCard (`JobCard`)

- **Purpose:** at-a-glance job offering.
- **Variants:** default / saved / closed-or-expired / condensed (recommended jobs) / skeleton.
- **States:** default, hover (lift + title brand), focus, saved (filled bookmark), disabled-apply (closed).
- **Used in:** Home latest jobs, Jobs results, Recommended Jobs, Saved Jobs, Related Jobs.

## 8. JobList (container)

- **Purpose:** ordered stack of JobCards with consistent gaps.
- **Variants:** results view / recommended rails.
- **States:** empty (renders `EmptyState`), loading (3–6 skeletons).
- **Used in:** Jobs page, Home, Recommended Jobs.

## 9. StatusBadge (`StatusBadge`)

- **Purpose:** communicate application state at a glance (never color-only).
- **Variants:** Applied / Under Review / Interview / Accepted / Rejected (+ optional neutral "Not selected" wording for rejected).
- **States:** — (static; `<span>` with status color text + soft bg + icon).
- **Used in:** My Applications, tracking, Job Applicants, Employer Application Details, notification messages, tables, cards, mobile rows.

## 10. Pill / Chip (`Tag`)

- **Purpose:** short metadata (job type, work mode, experience, location).
- **Variants:** neutral, brand-soft, outline.
- **Used in:** job cards, job details header, filter chips, role selection cards.

## 11. Input (`TextField`)

- **Purpose:** text capture (text, email, password; with optional icon & toggle).
- **Variants:** text/email/password/search events; sm/md.
- **States:** default, hover, focus, filled, disabled, error (danger border + message + `role=alert`), success (optional check).
- **Used in:** all forms, search fields, filters.

## 12. Textarea (`TextArea`)

- **Purpose:** longer free text (bio, description, cover letter, requirements).
- **States:** same as Input + resize-y; optional character counter.
- **Used in:** Job create/edit description & requirements, apply cover letter, applicant profile summary.

## 13. Select (`Select`)

- **Purpose:** single choice from a list; custom chevron, native options.
- **Variants:** md; optional grouped options (categories by industry).
- **States:** default, focus, disabled, error.
- **Used in:** filters (category, job type, work mode, experience, status), create-job fields, sort.

## 14. SearchField (`SearchField`)

- **Purpose:** search entry with persistent icon + clear button.
- **Variants:** hero (pill, larger), compact (toolbars), filter-search.
- **States:** default, focus (ring), filled-with-clear.
- **Used in:** Home hero, Jobs header, My Applications, Job Applicants, My Jobs.

## 15. Checkbox / Radio (`Checkbox`, `Radio`, `RoleCard`)

- **Purpose:** multi/single selection; role selection uses two selectable cards.
- **Variants:** checkbox, radio, rolect-radio cards (Applicant/Employer).
- **States:** unchecked, checked (brand), indeterminate (checkbox only), disabled, focus.
- **Used in:** filters (job type checkboxes), registration role selection, employer form toggles.

## 16. Toggle / Switch (`Switch`)

- **Purpose:** on/off boolean (show salary, publish immediately, notifications).
- **States:** on/off, disabled, focus.
- **Used in:** form settings, notification preferences.

## 17. FileUpload (`FileUpload`)

- **Purpose:** CV upload & company logo upload.
- **Variants:** dropzone card (dashed border), inline button + filename.
- **States:** idle, dragging-over, uploading (progress), done (check + filename), error, remove.
- **Used in:** Applicant Profile (CV), Company Profile (logo).

## 18. FormGroup / FieldWrapper

- **Purpose:** label + control + help + error as one unit (persistent label always visible).
- **Variants:** required, optional, with-help, error.
- **Used in:** every form field (auth, profile, job forms, filters).

## 19. FormLayout (`FormSection`, `FormStepper`)

- **Purpose:** grouped sections with optional step progress for long forms.
- **Variants:** flat sections (profile), stepper (Create/Edit Job, Register-role onboarding).
- **States:** steps: done ✓ / current / upcoming; validation summary.
- **Used in:** Create/Edit Job, Applicant Profile, Company Profile, Registration.

## 20. StatCard (`StatCard`)

- **Purpose:** one KPI (number + label), calm and readable.
- **Variants:** default (label above), with trend marker (subtle), skeleton.
- **States:** — (static; link optional).
- **Used in:** Applicant Dashboard (4 KPIs), Employer Dashboard (4 KPIs).

## 21. ApplicantCard (`ApplicantCard`, `CandidateRow`)

- **Purpose:** candidate identity + summary + status + action.
- **Variants:** table row (desktop) / card (mobile).
- **States:** hover (`bg-subtle`), selected, menu open.
- **Used in:** Job Applicants, Recent Applications (employer), tracking list.

## 22. Table (`DataTable`)

- **Purpose:** structured row data with header, pagination, optional selection.
- **Variants:** standard, responsive-collapse-to-cards, skeleton rows, empty.
- **States:** header, row hover, selected, empty, loading.
- **Used in:** My Applications, My Jobs, Job Applicants, dashboards.

## 23. Pagination (`Pagination`)

- **Purpose:** navigate result/list pages.
- **Variants:** numbered + prev/next; compact (prev/next + count) on mobile; "Load more" alternative for feeds.
- **States:** current, hover, focus, disabled (at bounds).
- **Used in:** Jobs results, My Applications, My Jobs, Job Applicants.

## 24. Tabs (`Tabs`)

- **Purpose:** switch between related views (status filters, dashboards sections).
- **Variants:** underline (indicator animates 200ms), pill (soft active).
- **States:** active (brand underline/pill + semibold), hover, focus, disabled.
- **Used in:** My Applications status filter, My Jobs status filter, dashboard section toggles.

## 25. Modal / Dialog (`Modal`)

- **Purpose:** focused task or confirmation.
- **Variants:** confirmation, form-dialog, mobile bottom-sheet.
- **States:** open (scrim + focus trap), closing, Escape/backdrop/close; destructive keeps focus on Cancel.
- **Used in:** Confirm Job Deletion, Confirm Application Cancel, Update/Confirm Status (employer), Confirm Account Delete.

## 26. Toast (`Toast`)

- **Purpose:** ephemeral feedback.
- **Variants:** success / info / danger;
- **States:** enter, visible (4s, pause on hover), exit; dismiss button.
- **Used in:** Application submitted, Job posted/updated/saved, Status updated, Profile saved; error toasts.

## 27. InlineAlert / AlertBanner (`Alert`)

- **Purpose:** non-blocking status within content.
- **Variants:** success/warning/info/danger; inline (in-flow) or banner (top of view).
- **States:** —.
- **Used in:** validation summary, search failure, "needs attention" banners, sync/update notes.

## 28. EmptyState (`EmptyState`)

- **Purpose:** guide the user when no data with a clear CTA.
- **Variants:** applications / saved / jobs / applicants / no-results / notifications.
- **States:** — (static; CTA required).
- **Used in:** all list views, table territories, notification center.

## 29. Skeleton (`Skeleton` / `SkeletonGroup`)

- **Purpose:** loading placeholder matching final content shape.
- **Variants:** block, card, row, stats, avatar+lines.
- **States:** shimmer (reduced-motion: static), loop 1.2s.
- **Used in:** job lists, KPI tiles, application rows, search results, profile loading.

## 30. ProgressStepper (`ProgressStepper` / `Timeline`)

- **Purpose:** application tracking (signature).
- **Variants:** horizontal stepper (desktop/tablet), vertical timeline (mobile).
- **States:** completed (✓ + date), current (brand ring + "current"), upcoming (muted ○), rejected (terminal branch, calm).
- **Used in:** Application Tracking page, Application Details (employer shows stage with counts).

## 31. Dropdown / Menu (`Menu`, `UserMenu`, `ActionsMenu`)

- **Purpose:** compact action groups.
- **Variants:** user menu (avatar ▾), row actions `[⋯]`, filter dropdowns.
- **States:** closed, open (`shadow-md` + `role=menu` + focus ring), item hover, separator, danger-item.
- **Used in:** navbar user, My Jobs row actions, table row menus, filter applicators.

## 32. Popover / Tooltip (`Tooltip`)

- **Purpose:** brief explanation on icon hover/focus.
- **Variants:** tooltip (short text), popover (small content).
- **States:** show on hover/focus; hide on Escape/click; `prefers-reduced-motion` → instant.
- **Used in:** icon buttons, salary tooltips, "what does this mean" hints in forms.

## 33. NotificationBell + NotificationCenter (`Notifications`)

- **Purpose:** surface periodic updates with unread dot.
- **Variants:** bell+dot (0–99, "+n"), panel (list w/ time + destination), "Mark all read".
- **States:** unread (dot), hover, item click → navigate & mark read, empty.
- **Used in:** applicant & employer navbars (see UX_FLOWS).

## 34. AuthShell (`AuthCard`)

- **Purpose:** centered focused auth layout (logo, form, links, footer).
- **Variants:** login, register-with-role-selection.
- **States:** loading submit, inline validation, success redirect.
- **Used in:** /login, /register.

## 35. StickyActionBar (`StickyBar`)

- **Purpose:** keep primary action reachable on mobile (and job details aside).
- **Variants:** job-details apply bar, form publish bar, status-update bar.
- **States:** visible when action below the fold; recedes when in view.
- **Used in:** Job Details (mobile), Create Job (mobile), Application Details (employer, mobile).

## 36. FilterDrawer / FilterSidebar (`Filters`)

- **Purpose:** filter controls, desktop sidebar or mobile bottom-sheet.
- **Variants:** sidebar (desktop ≥1200), drawer (mobile/tablet) with sticky Apply/Reset + active-count badge.
- **States:** open/closed, active filter chip removal.
- **Used in:** Jobs page, My Applications status filter, Job Applicants filters.

## 37. PipelineBoard (`Pipeline`)

- **Purpose:** employer lightweight recruitment pipeline (counts per stage).
- **Variants:** horizontal stage columns with counts + mini candidate chips; rejected column separate.
- **States:** stage emphasized when dragging (optional), empty stage, hover.
- **Used in:** Employer Dashboard, Application Details counts.

## 38. ErrorPage (`ErrorPage`)

- **Purpose:** full-screen human-read error with app chrome.
- **Variants:** 404, 403, 500.
- **States:** —.
- **Used in:** route fallbacks, permission failures, server errors.

## 39. Footer (`AppFooter`)

- **Purpose:** site info, navigation, legal.
- **Variants:** public (rich), minimal (auth).
- **Used in:** public pages, auth pages.

## 40. SkipLink (`SkipLink`)

- **Purpose:** accessibility — jump to main content.
- **States:** visible on focus only.
- **Used in:** public + dashboard layouts.

---

## Cross-cutting notes

- **Too many cards:** Section 14 of the design system forbids card-walls; dashboards use bounded surfaces and bands for secondary content.
- **KPI discipline:** only 4 stat cards per dashboard; never decorate with fake charts.
- **Status across contexts:** one `StatusBadge` component is reused everywhere (badges, tables, tracking, mobile) so states are instantly recognizable.
- **Destructive safety:** Delete/reject lives behind `ActionsMenu` or confirmation modal — no bare inline destructive clicks.
- **Loading discipline:** skeletons, not full-screen spinners, for data regions (section 20).

*Component naming is implementation-agnostic (PHP server-rendered or JS-enhanced as decided later).*