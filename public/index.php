<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/auth.php';

hirepeak_start_session();

$pageActive = 'home';
$pageScripts = ['/assets/js/main.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AuraJob — Find the right opportunity. Build your next move.</title>
  <meta name="description" content="AuraJob is a modern recruitment platform connecting talented candidates with employers, with clear application tracking and a straightforward hiring workflow.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="/assets/images/aurajob-mark.png" type="image/png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="home-page">
  <?php require dirname(__DIR__) . '/app/Views/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main">
    <section class="hero">
      <div class="container hero__layout">
        <div class="hero__inner">
        <h1 class="hero__title">
          <span class="hero__title-line">Find the right opportunity.</span>
          <span class="hero__title-line">Build your next move.</span>
        </h1>
        <p class="hero__sub">Discover meaningful opportunities from growing teams — or find talented people ready to make an impact.</p>

        <form class="hero-search" role="search" action="<?php echo hirepeak_url('jobs.php'); ?>" method="get" data-hero-search>
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

        <figure class="hero-visual">
          <img src="/assets/images/hero-career.webp" alt="Modern workspace with a laptop focused on career growth" width="960" height="514" fetchpriority="high">
          <figcaption class="hero-visual__card">
            <span class="hero-visual__eyebrow">Your next move</span>
            <strong>Search. Apply. Track.</strong>
            <span>One focused place for your career journey.</span>
          </figcaption>
        </figure>
      </div>
    </section>

  <section class="section" aria-labelledby="latest-jobs-title">
      <div class="container">
        <div class="section-head">
          <h2 class="section-title" id="latest-jobs-title">Latest jobs</h2>
          <a class="link-arrow" href="<?php echo hirepeak_url('jobs.php'); ?>">
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
            <h3 class="job-card__title"><a href="<?php echo hirepeak_url('job-details.php?id=1'); ?>">Senior PHP Developer</a></h3>
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
            <h3 class="job-card__title"><a href="<?php echo hirepeak_url('job-details.php?id=1'); ?>">Product Designer</a></h3>
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
            <h3 class="job-card__title"><a href="<?php echo hirepeak_url('job-details.php?id=1'); ?>">Marketing Specialist</a></h3>
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
          <a class="link-arrow" href="<?php echo hirepeak_url('jobs.php'); ?>">
            All categories
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </a>
        </div>

        <div class="category-grid">
          <a class="category-card" href="<?php echo hirepeak_url('jobs.php'); ?>">
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

          <a class="category-card" href="<?php echo hirepeak_url('jobs.php'); ?>">
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

          <a class="category-card" href="<?php echo hirepeak_url('jobs.php'); ?>">
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

          <a class="category-card" href="<?php echo hirepeak_url('jobs.php'); ?>">
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

          <a class="category-card" href="<?php echo hirepeak_url('jobs.php'); ?>">
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

          <a class="category-card" href="<?php echo hirepeak_url('jobs.php'); ?>">
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

    <section class="section" id="why-aurajob" aria-label="Why AuraJob and how it works">
      <div class="container why-grid">
        <div class="why-col">
          <h2 class="why-col__title">Why AuraJob</h2>
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
              <a class="btn btn--primary" href="<?php echo hirepeak_url('jobs.php'); ?>">
                Explore Jobs
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </a>
            </div>
          </div>

          <figure class="benefit-visual benefit-visual--talent">
            <img src="/assets/images/talent-career.webp" alt="Job seeker working on a laptop" width="720" height="816" loading="lazy">
            <figcaption class="benefit-visual__card">
              <span class="benefit-visual__icon" aria-hidden="true">✓</span>
              <span><strong>Stay in control</strong><small>Save jobs and track every application.</small></span>
            </figcaption>
          </figure>
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
              <a class="btn btn--outline" href="<?php echo hirepeak_url('employer/post-job.php'); ?>">
                Post a Job
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14"/>
                  <path d="m13 6 6 6-6 6"/>
                </svg>
              </a>
            </div>
          </div>

          <figure class="benefit-visual benefit-visual--employer">
            <img src="/assets/images/employer-hiring.webp" alt="Recruiter reviewing candidates on a laptop" width="900" height="574" loading="lazy">
            <figcaption class="benefit-visual__card">
              <span class="benefit-visual__icon" aria-hidden="true">↗</span>
              <span><strong>Hire with clarity</strong><small>Review applicants and move them through your pipeline.</small></span>
            </figcaption>
          </figure>
        </div>
      </div>
    </section>

    <section class="cta" aria-labelledby="cta-title">
      <div class="container cta__inner">
        <h2 class="cta__title" id="cta-title">Ready for your next move?</h2>
        <p class="cta__text">Create your account for free and start exploring opportunities today.</p>
        <div class="cta__actions">
          <a class="btn btn--primary" href="<?php echo hirepeak_url('register.php'); ?>">Create Account</a>
          <a class="btn btn--ghost" href="<?php echo hirepeak_url('jobs.php'); ?>">Explore jobs</a>
        </div>
      </div>
    </section>
  </main>

  <?php require dirname(__DIR__) . '/app/Views/footer.php'; ?>
</body>
</html>