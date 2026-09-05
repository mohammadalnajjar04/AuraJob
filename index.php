<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HirePeak — Find the right opportunity. Reach your peak.</title>
  <meta name="description" content="HirePeak is a modern recruitment platform connecting talented candidates with employers, with clear application tracking and a straightforward hiring workflow.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='1' y='1' width='22' height='22' rx='7' fill='%234F46E5'/%3E%3Cpath d='M12 4.8 18 16H6Z' fill='%23FFFFFF' stroke='%23FFFFFF' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M12 6.6 8.8 16H5.9Z' fill='%23EEF2FF' stroke='%23EEF2FF' stroke-width='3' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="src/css/style.css">
</head>
<body>
  <svg class="svg-defs" aria-hidden="true">
    <defs>
      <symbol id="mark-peak" viewBox="0 0 24 24">
        <rect x="1" y="1" width="22" height="22" rx="7" fill="#4F46E5"/>
        <path d="M12 4.8 18 16H6Z" fill="#FFFFFF" stroke="#FFFFFF" stroke-width="3" stroke-linejoin="round"/>
        <path d="M12 6.6 8.8 16H5.9Z" fill="#EEF2FF" stroke="#EEF2FF" stroke-width="3" stroke-linejoin="round"/>
      </symbol>
    </defs>
  </svg>

  <a class="skip-link" href="#main">Skip to content</a>

  <header class="site-header">
    <div class="container site-header__inner">
      <a class="brand" href="index.php" aria-label="HirePeak home">
        <svg class="brand__mark" width="26" height="26" aria-hidden="true">
          <use href="#mark-peak"/>
        </svg>
        <span class="brand__name">HirePeak</span>
      </a>

      <div class="site-header__actions">
        <a class="btn btn--primary btn--sm site-header__mobile-cta" href="register.php">Get Started</a>
        <button class="icon-button nav-toggle" type="button" data-drawer-toggle aria-expanded="false" aria-controls="site-nav" aria-label="Open menu">
          <svg class="nav-toggle__icon-open" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M4 7h16"/>
            <path d="M4 12h16"/>
            <path d="M4 17h16"/>
          </svg>
          <svg class="nav-toggle__icon-close" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M6 6l12 12"/>
            <path d="M18 6 6 18"/>
          </svg>
        </button>
      </div>

      <nav class="site-nav" id="site-nav" data-drawer aria-hidden="true" aria-label="Main">
        <div class="site-nav__head">
          <span class="brand">
            <svg class="brand__mark" width="26" height="26" aria-hidden="true">
              <use href="#mark-peak"/>
            </svg>
            <span class="brand__name">HirePeak</span>
          </span>
          <button class="icon-button" type="button" data-drawer-close aria-label="Close menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <path d="M6 6l12 12"/>
              <path d="M18 6 6 18"/>
            </svg>
          </button>
        </div>

        <ul class="site-nav__links">
          <li><a class="site-nav__link is-active" href="index.php" aria-current="page">Home</a></li>
          <li><a class="site-nav__link" href="jobs.php">Find Jobs</a></li>
          <li><a class="site-nav__link" href="#for-employers">For Employers</a></li>
        </ul>

        <div class="site-nav__auth">
          <a class="btn btn--ghost" href="login.php">Sign In</a>
          <a class="btn btn--primary" href="register.php">Create Account</a>
        </div>
      </nav>
    </div>
  </header>

  <div class="scrim" data-scrim hidden></div>

  <main id="main">
    <section class="hero">
      <div class="container hero__inner">
        <h1 class="hero__title">
          <span class="hero__title-line">Find the right opportunity.</span>
          <span class="hero__title-line">Reach your peak.</span>
        </h1>
        <p class="hero__sub">Discover meaningful opportunities from growing teams — or find talented people ready to make an impact.</p>

        <form class="hero-search" role="search" action="jobs.php" method="get" data-hero-search>
          <label class="sr-only" for="hero-keyword">Keyword or job title</label>
          <div class="hero-search__field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="7"/>
              <path d="m20 20-3.5-3.5"/>
            </svg>
            <input id="hero-keyword" name="keyword" type="search" placeholder="Job title, skill, or company" autocomplete="off">
          </div>

          <div class="hero-search__divider" aria-hidden="true"></div>

          <label class="sr-only" for="hero-location">Location</label>
          <div class="hero-search__field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <input id="hero-location" name="location" type="text" placeholder="City, or remote" autocomplete="off">
          </div>

          <button class="btn btn--primary hero-search__submit" type="submit">
            Search Jobs
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </button>
        </form>

        <div class="hero-popular">
          <span class="hero-popular__label" id="popular-label">Popular:</span>
          <ul aria-labelledby="popular-label">
            <li><button type="button" class="chip" data-chip="Backend">Backend</button></li>
            <li><button type="button" class="chip" data-chip="Design">Design</button></li>
            <li><button type="button" class="chip" data-chip="Remote">Remote</button></li>
            <li><button type="button" class="chip" data-chip="Finance">Finance</button></li>
          </ul>
</div>
      </div>

      <div class="hero-preview" aria-hidden="true">
        <div class="hero-preview__job">
          <span class="hero-preview__tile">BD</span>
          <div>
            <p class="hero-preview__title">Backend Developer</p>
            <p class="hero-preview__company">Northstar Labs &middot; Amman &middot; Hybrid</p>
          </div>
        </div>
        <div class="hero-preview__track">
          <span class="hero-preview__step is-done"><span class="hero-preview__dot"></span>Applied</span>
          <span class="hero-preview__step is-current"><span class="hero-preview__dot"></span>Under Review <span class="hero-preview__now">now</span></span>
          <span class="hero-preview__step is-next"><span class="hero-preview__dot"></span>Interview</span>
        </div>
      </div>
    </div>
  </section>

  <section class="section" aria-labelledby="latest-jobs-title">
      <div class="container">
        <div class="section-head">
          <h2 class="section-title" id="latest-jobs-title">Latest jobs</h2>
          <a class="link-arrow" href="jobs.php">
            View all jobs
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </a>
        </div>

        <div class="job-grid">
          <article class="job-card">
            <div class="job-card__top">
              <span class="job-card__logo job-card__logo--nl" aria-hidden="true">NL</span>
              <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Senior PHP Developer at Northstar Labs">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                </svg>
              </button>
            </div>
            <h3 class="job-card__title"><a href="job-details.php?id=1">Senior PHP Developer</a></h3>
            <p class="job-card__company">Northstar Labs &middot; Makati</p>
            <ul class="job-card__meta" aria-label="Job type and work mode">
              <li class="chip">Full-time</li>
              <li class="chip">Hybrid</li>
            </ul>
            <p class="job-card__salary">PHP 80,000 &ndash; 120,000 / month</p>
            <p class="job-card__posted">Posted 2 days ago</p>
          </article>

          <article class="job-card">
            <div class="job-card__top">
              <span class="job-card__logo job-card__logo--vd" aria-hidden="true">VD</span>
              <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Product Designer at Vertex Digital">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                </svg>
              </button>
            </div>
            <h3 class="job-card__title"><a href="job-details.php?id=1">Product Designer</a></h3>
            <p class="job-card__company">Vertex Digital &middot; Remote</p>
            <ul class="job-card__meta" aria-label="Job type and work mode">
              <li class="chip">Full-time</li>
              <li class="chip">Remote</li>
            </ul>
            <p class="job-card__salary">PHP 60,000 &ndash; 90,000 / month</p>
            <p class="job-card__posted">Posted yesterday</p>
          </article>

          <article class="job-card">
            <div class="job-card__top">
              <span class="job-card__logo job-card__logo--nw" aria-hidden="true">NW</span>
              <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Marketing Specialist at NovaWorks">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                </svg>
              </button>
            </div>
            <h3 class="job-card__title"><a href="job-details.php?id=1">Marketing Specialist</a></h3>
            <p class="job-card__company">NovaWorks &middot; Quezon City</p>
            <ul class="job-card__meta" aria-label="Job type and work mode">
              <li class="chip">Full-time</li>
              <li class="chip">On-site</li>
            </ul>
            <p class="job-card__salary">PHP 45,000 &ndash; 70,000 / month</p>
            <p class="job-card__posted">Posted 4 days ago</p>
          </article>
        </div>
      </div>
    </section>

    <section class="section section--subtle" aria-labelledby="categories-title">
      <div class="container">
        <div class="section-head">
          <h2 class="section-title" id="categories-title">Popular categories</h2>
          <a class="link-arrow" href="jobs.php">
            All categories
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </a>
        </div>

        <div class="category-grid">
          <a class="category-card" href="jobs.php">
            <span class="category-card__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m8 7-5 5 5 5"/>
                <path d="m16 7 5 5-5 5"/>
                <path d="m13 4-2 16"/>
              </svg>
            </span>
            <span class="category-card__body">
              <span class="category-card__name">IT &amp; Software</span>
              <span class="category-card__cta">
                Browse jobs
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </span>
            </span>
          </a>

          <a class="category-card" href="jobs.php">
            <span class="category-card__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 3a9 9 0 1 0 0 18c1.6 0 2-.9 2-1.7 0-.8-.6-1.3-.6-2.3 0-1.1 1-2 2.3-2H19a5 5 0 0 0 .5-10A9 9 0 0 0 12 3Z"/>
                <circle cx="7.5" cy="10.5" r="1"/>
                <circle cx="12" cy="7.5" r="1"/>
                <circle cx="16.5" cy="10.5" r="1"/>
              </svg>
            </span>
            <span class="category-card__body">
              <span class="category-card__name">Design &amp; Creative</span>
              <span class="category-card__cta">
                Browse jobs
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </span>
            </span>
          </a>

          <a class="category-card" href="jobs.php">
            <span class="category-card__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 21h18"/>
                <rect x="6" y="13" width="3" height="5" rx="1"/>
                <rect x="11" y="9" width="3" height="9" rx="1"/>
                <rect x="16" y="5" width="3" height="13" rx="1"/>
              </svg>
            </span>
            <span class="category-card__body">
              <span class="category-card__name">Finance &amp; Accounting</span>
              <span class="category-card__cta">
                Browse jobs
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </span>
            </span>
          </a>

          <a class="category-card" href="jobs.php">
            <span class="category-card__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 20s-7-4.5-9-9a5 5 0 0 1 9-3 5 5 0 0 1 9 3c-2 4.5-9 9-9 9Z"/>
              </svg>
            </span>
            <span class="category-card__body">
              <span class="category-card__name">Healthcare</span>
              <span class="category-card__cta">
                Browse jobs
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </span>
            </span>
          </a>

          <a class="category-card" href="jobs.php">
            <span class="category-card__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 11v2a1 1 0 0 0 1 1h2l3 4a1 1 0 0 0 1.7-.7V6.7A1 1 0 0 0 9 6l-3 4H4a1 1 0 0 0-1 1Z"/>
                <path d="M18 9a4 4 0 0 1 0 6"/>
                <path d="M21 7a8 8 0 0 1 0 10"/>
              </svg>
            </span>
            <span class="category-card__body">
              <span class="category-card__name">Marketing</span>
              <span class="category-card__cta">
                Browse jobs
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </span>
            </span>
          </a>

          <a class="category-card" href="jobs.php">
            <span class="category-card__icon">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v18H6.5A2.5 2.5 0 0 0 4 22V4.5Z"/>
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
              </svg>
            </span>
            <span class="category-card__body">
              <span class="category-card__name">Education</span>
              <span class="category-card__cta">
                Browse jobs
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </span>
            </span>
          </a>
        </div>
      </div>
    </section>

    <section class="section" id="why-hirepeak" aria-label="Why HirePeak and how it works">
      <div class="container why-grid">
        <div class="why-col">
          <h2 class="why-col__title">Why HirePeak</h2>
          <ul class="feature-list">
            <li class="feature">
              <span class="feature__icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m5 12 5 5L20 7"/>
                </svg>
              </span>
              <div>
                <h3 class="feature__title">Clear application tracking</h3>
                <p class="feature__text">Know exactly where every application stands, from applied to interview to decision.</p>
              </div>
            </li>
            <li class="feature">
              <span class="feature__icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m5 12 5 5L20 7"/>
                </svg>
              </span>
              <div>
                <h3 class="feature__title">Everything in one place</h3>
                <p class="feature__text">Jobs, applications, and updates in a single calm view — nothing scattered across emails.</p>
              </div>
            </li>
            <li class="feature">
              <span class="feature__icon" aria-hidden="true">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="m5 12 5 5L20 7"/>
                </svg>
              </span>
              <div>
                <h3 class="feature__title">Apply in minutes</h3>
                <p class="feature__text">One click and an optional cover letter — no lengthy application forms.</p>
              </div>
            </li>
          </ul>
        </div>

        <div class="why-col">
          <h2 class="why-col__title">How it works</h2>
          <ol class="step-list">
            <li class="step">
              <span class="step__num" aria-hidden="true">1</span>
              <div>
                <h3 class="step__title">Create an account</h3>
                <p class="step__text">Choose your role — talent or employer — and set up in a minute.</p>
              </div>
            </li>
            <li class="step">
              <span class="step__num" aria-hidden="true">2</span>
              <div>
                <h3 class="step__title">Apply to jobs</h3>
                <p class="step__text">Search, save, and apply to the roles that match your goals.</p>
              </div>
            </li>
            <li class="step">
              <span class="step__num" aria-hidden="true">3</span>
              <div>
                <h3 class="step__title">Track your application</h3>
                <p class="step__text">Follow every step of the process until the final decision.</p>
              </div>
            </li>
          </ol>
        </div>
      </div>
    </section>

    <section class="section section--subtle" aria-label="For talent">
      <div class="container">
        <div class="benefit">
          <div>
            <p class="benefit__eyebrow">For talent</p>
            <h2 class="benefit__title">Find work that fits your goals.</h2>
            <p class="benefit__text">Browse meaningful roles, apply in minutes, and follow every application through to the final decision — all in one calm place.</p>
            <div class="benefit__actions">
              <a class="btn btn--primary" href="jobs.php">
                Explore Jobs
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </a>
            </div>
          </div>

          <div class="benefit__preview" aria-hidden="true">
            <div class="preview-card__head">
              <span class="job-card__logo job-card__logo--nl">NL</span>
              <div>
                <p class="preview-card__title">Senior PHP Developer</p>
                <p class="preview-card__sub">Northstar Labs &middot; Makati</p>
              </div>
            </div>
            <ul class="preview-track">
              <li class="preview-track__step is-done">
                <span class="preview-track__dot"></span>
                Applied
              </li>
              <li class="preview-track__step is-current">
                <span class="preview-track__dot"></span>
                <span class="preview-track__label">Under Review <span class="preview-track__now">now</span></span>
              </li>
              <li class="preview-track__step">
                <span class="preview-track__dot"></span>
                Interview
              </li>
              <li class="preview-track__step">
                <span class="preview-track__dot"></span>
                Accepted
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="for-employers" aria-label="For employers">
      <div class="container">
        <div class="benefit">
          <div>
            <p class="benefit__eyebrow">For employers</p>
            <h2 class="benefit__title">Build your team with confidence.</h2>
            <p class="benefit__text">Post a job in minutes, review applicants from one place, and update every application as it moves through your pipeline.</p>
            <div class="benefit__actions">
              <a class="btn btn--outline" href="#">
                Post a Job
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </a>
            </div>
          </div>

          <div class="benefit__preview" aria-hidden="true">
            <p class="preview-head">Recruitment pipeline</p>
            <ul class="pipeline">
              <li class="pipeline__stage"><span class="pipeline__dot"></span>Applied</li>
              <li class="pipeline__stage is-active"><span class="pipeline__dot"></span>Under Review</li>
              <li class="pipeline__stage"><span class="pipeline__dot"></span>Interview</li>
              <li class="pipeline__stage is-done"><span class="pipeline__dot"></span>Hired</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="cta" aria-labelledby="cta-title">
      <div class="container cta__inner">
        <h2 class="cta__title" id="cta-title">Ready to reach your peak?</h2>
        <p class="cta__text">Create your account for free and start exploring opportunities today.</p>
        <div class="cta__actions">
          <a class="btn btn--primary" href="register.php">Create Account</a>
          <a class="btn btn--ghost" href="jobs.php">Explore jobs</a>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a class="brand" href="index.php" aria-label="HirePeak home">
            <svg class="brand__mark" width="26" height="26" aria-hidden="true">
              <use href="#mark-peak"/>
            </svg>
            <span class="brand__name">HirePeak</span>
          </a>
          <p class="footer-brand__text">Helping talent and employers reach their peak.</p>
        </div>

        <div class="footer-links">
          <div class="footer-col">
            <h3 class="footer-col__title">Job Seekers</h3>
            <ul>
              <li><a href="jobs.php">Find Jobs</a></li>
              <li><a href="jobs.php">Browse Categories</a></li>
              <li><a href="#why-hirepeak">How It Works</a></li>
              <li><a href="register.php">Create Account</a></li>
            </ul>
          </div>

          <div class="footer-col">
            <h3 class="footer-col__title">Employers</h3>
            <ul>
              <li><a href="#">Post a Job</a></li>
              <li><a href="#for-employers">For Employers</a></li>
              <li><a href="#">Applicant Pipeline</a></li>
            </ul>
          </div>

          <div class="footer-col">
            <h3 class="footer-col__title">Company</h3>
            <ul>
              <li><a href="#">About</a></li>
              <li><a href="#">Support</a></li>
              <li><a href="#">Contact</a></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="footer-bottom">
        <p>&copy; <span id="year">2026</span> HirePeak. All rights reserved.</p>
        <ul class="footer-bottom__legal">
          <li><a href="#">Terms</a></li>
          <li><a href="#">Privacy</a></li>
        </ul>
      </div>
    </div>
  </footer>

  <script src="src/js/main.js"></script>
</body>
</html>