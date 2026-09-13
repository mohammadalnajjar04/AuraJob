<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/html.php';

hirepeak_start_session();

$pageActive = 'jobs';
$pageScripts = ['src/js/main.js'];

$apply = hirepeak_apply_cta();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Backend Developer at Northstar Labs — HirePeak</title>
  <meta name="description" content="Backend Developer job at Northstar Labs in Amman, Jordan. Hybrid · Full-time · 800 – 1,100 JOD / month. Apply on HirePeak before September 30, 2026.">
  <meta name="theme-color" content="#ffffff">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect x='1' y='1' width='22' height='22' rx='7' fill='%234F46E5'/%3E%3Cpath d='M12 4.8 18 16H6Z' fill='%23FFFFFF' stroke='%23FFFFFF' stroke-width='3' stroke-linejoin='round'/%3E%3Cpath d='M12 6.6 8.8 16H5.9Z' fill='%23EEF2FF' stroke='%23EEF2FF' stroke-width='3' stroke-linejoin='round'/%3E%3C/svg%3E">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="src/css/style.css">
</head>
<body>
  <?php require __DIR__ . '/includes/header.php'; ?>

  <div class="scrim" data-scrim hidden></div>

  <main id="main">
    <div class="jd-topbar">
      <div class="container jd-topbar__inner">
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li><a href="jobs.php">Find Jobs</a></li>
            <li aria-current="page">Backend Developer</li>
          </ol>
        </nav>
        <a class="jd-back" href="jobs.php">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5"/>
            <path d="m11 18-6-6 6-6"/>
          </svg>
          Back to jobs
        </a>
      </div>
    </div>

    <section class="jd-section" aria-label="Backend Developer job details">
      <div class="container jd-layout">
        <article class="jd-main">
          <header class="jd-head">
            <div class="jd-head__row">
              <span class="job-card__logo job-card__logo--nl jd-head__logo" aria-hidden="true">NL</span>
              <div>
                <h1 class="jd-head__title">Backend Developer</h1>
                <p class="jd-head__company">Northstar Labs</p>
                <p class="jd-head__location">Amman, Jordan <span aria-hidden="true">&middot;</span> Hybrid</p>
              </div>
            </div>

            <ul class="jd-head__chips" aria-label="Job type and experience level">
              <li class="chip">Full-time</li>
              <li class="chip">Senior</li>
            </ul>

            <div class="jd-head__pay">
              <p class="jd-head__salary">800 <span aria-hidden="true">&ndash;</span> 1,100 JOD <span class="jd-head__period">/ month</span></p>
              <p class="jd-head__posted">Posted 2 days ago</p>
            </div>
          </header>

          <section class="jd-prose" aria-labelledby="jd-about">
            <h2 id="jd-about">About the role</h2>
            <p>We're looking for a Backend Developer to design, build, and maintain the APIs and services that power our platform. You'll work with product, design, and frontend engineers to ship reliable features, improve performance, and keep our systems secure as we grow.</p>
            <p>This is a full-time, hybrid role based in Amman, Jordan with team days in the office and flexible remote days. You'll own services end to end, from design through deployment and monitoring.</p>
          </section>

          <section class="jd-prose" aria-labelledby="jd-responsibilities">
            <h2 id="jd-responsibilities">Responsibilities</h2>
            <ul class="jd-list">
              <li>Design and build RESTful APIs and backend services in PHP and Laravel.</li>
              <li>Write clean, tested, well-documented code that is easy to maintain.</li>
              <li>Work with product and design teams to scope features and estimate effort.</li>
              <li>Improve query performance, caching, and overall platform reliability.</li>
              <li>Troubleshoot and fix issues in production, and prevent regressions with tests.</li>
              <li>Participate in code reviews and help raise the engineering bar.</li>
            </ul>
          </section>

          <section class="jd-prose" aria-labelledby="jd-requirements">
            <h2 id="jd-requirements">Requirements</h2>
            <ul class="jd-list">
              <li>4+ years of experience building backend systems in production.</li>
              <li>Strong knowledge of PHP and a modern PHP framework, ideally Laravel.</li>
              <li>Solid understanding of relational databases, especially MySQL.</li>
              <li>Experience designing and consuming RESTful APIs.</li>
              <li>Comfortable with version control (Git) and CI/CD workflows.</li>
              <li>Clear communication and good written English.</li>
            </ul>
          </section>

          <section class="jd-prose" aria-labelledby="jd-nice">
            <h2 id="jd-nice">Nice to have</h2>
            <ul class="jd-list">
              <li>Experience with Redis or similar caching layers.</li>
              <li>Familiarity with message queues or background jobs.</li>
              <li>Exposure to Docker and container-based deployments.</li>
              <li>Some experience with automated testing, such as PHPUnit or Pest.</li>
            </ul>
          </section>

          <section class="jd-prose" aria-labelledby="jd-benefits">
            <h2 id="jd-benefits">Benefits</h2>
            <ul class="jd-list">
              <li>Competitive salary, reviewed annually.</li>
              <li>Hybrid work — combine office and home days.</li>
              <li>Health insurance.</li>
              <li>Learning budget for courses, books, and conferences.</li>
              <li>21 days of paid leave per year.</li>
            </ul>
          </section>
        </article>

        <aside class="jd-aside" aria-label="Apply and job overview">
          <div class="jd-panel jd-apply">
            <p class="jd-apply__deadline">Apply before <strong>September 30, 2026</strong></p>
            <div class="jd-apply__actions">
              <?php echo $apply['cta']; ?>
              <button class="btn btn--secondary" type="button" data-bookmark aria-pressed="false">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                </svg>
                Save Job
              </button>
            </div>
            <p class="jd-apply__note"><?php echo htmlspecialchars($apply['note'], ENT_QUOTES, 'UTF-8'); ?></p>
          </div>

          <div class="jd-panel jd-facts">
            <h2 class="jd-facts__title">Job overview</h2>
            <dl class="jd-facts__list">
              <div class="jd-fact"><dt>Salary</dt><dd>800 &ndash; 1,100 JOD / month</dd></div>
              <div class="jd-fact"><dt>Location</dt><dd>Amman, Jordan</dd></div>
              <div class="jd-fact"><dt>Job type</dt><dd>Full-time</dd></div>
              <div class="jd-fact"><dt>Work mode</dt><dd>Hybrid</dd></div>
              <div class="jd-fact"><dt>Experience</dt><dd>Senior</dd></div>
              <div class="jd-fact"><dt>Deadline</dt><dd>September 30, 2026</dd></div>
              <div class="jd-fact"><dt>Category</dt><dd>Software Development</dd></div>
              <div class="jd-fact"><dt>Job ID</dt><dd>HP-2026-0184</dd></div>
            </dl>
          </div>

          <div class="jd-panel jd-company">
            <h2 class="jd-facts__title">About the company</h2>
            <div class="jd-company__head">
              <span class="job-card__logo job-card__logo--nl jd-company__logo" aria-hidden="true">NL</span>
              <div>
                <p class="jd-company__name">Northstar Labs</p>
                <p class="jd-company__meta">Software &middot; Amman, Jordan</p>
              </div>
            </div>
            <p class="jd-company__text">Northstar Labs is a small product team building simple, dependable tools for busy people. We value calm, clarity, and the craft of shipping useful software.</p>
            <a class="jd-company__link" href="#">
              View company
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14"/>
                <path d="m13 6 6 6-6 6"/>
              </svg>
            </a>
          </div>
        </aside>
      </div>
    </section>

    <section class="jd-related" aria-labelledby="related-title">
      <div class="container">
        <div class="section-head">
          <h2 class="section-title" id="related-title">Related jobs</h2>
        </div>

        <div class="job-grid related-grid">
          <article class="job-card job-card--related">
            <div class="job-card__top">
              <span class="job-card__logo job-card__logo--ol" aria-hidden="true">OL</span>
              <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Frontend Developer at Orbit Labs">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                </svg>
              </button>
            </div>
            <h3 class="job-card__title"><a href="job-details.php?id=5">Frontend Developer</a></h3>
            <p class="job-card__company">Orbit Labs &middot; Worldwide &middot; Remote</p>
            <ul class="job-card__meta" aria-label="Job type and work mode">
              <li class="chip">Contract</li>
              <li class="chip">Remote</li>
            </ul>
            <p class="job-card__salary">700 &ndash; 950 JOD <span class="job-card--row__period">/ month</span></p>
            <p class="job-card__posted">Posted 1 week ago</p>
          </article>

          <article class="job-card job-card--related">
            <div class="job-card__top">
              <span class="job-card__logo job-card__logo--as" aria-hidden="true">AS</span>
              <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save QA Engineer at Atlas Systems">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                </svg>
              </button>
            </div>
            <h3 class="job-card__title"><a href="job-details.php?id=3">QA Engineer</a></h3>
            <p class="job-card__company">Atlas Systems &middot; Amman &middot; On-site</p>
            <ul class="job-card__meta" aria-label="Job type and work mode">
              <li class="chip">Full-time</li>
              <li class="chip">On-site</li>
            </ul>
            <p class="job-card__salary">500 &ndash; 650 JOD <span class="job-card--row__period">/ month</span></p>
            <p class="job-card__posted">Posted 3 days ago</p>
          </article>

          <article class="job-card job-card--related">
            <div class="job-card__top">
              <span class="job-card__logo job-card__logo--cd" aria-hidden="true">CD</span>
              <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Junior Software Engineer at Cedar Digital">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                </svg>
              </button>
            </div>
            <h3 class="job-card__title"><a href="job-details.php?id=8">Junior Software Engineer</a></h3>
            <p class="job-card__company">Cedar Digital &middot; Zarqa &middot; On-site</p>
            <ul class="job-card__meta" aria-label="Job type and work mode">
              <li class="chip">Full-time</li>
              <li class="chip">On-site</li>
            </ul>
            <p class="job-card__salary">600 &ndash; 800 JOD <span class="job-card--row__period">/ month</span></p>
            <p class="job-card__posted">Posted 2 days ago</p>
          </article>
        </div>
      </div>
    </section>
  </main>

  <div class="jd-bar">
    <div class="jd-bar__inner">
      <button class="btn btn--secondary" type="button" data-bookmark aria-pressed="false">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
        </svg>
        Save
      </button>
      <?php echo $apply['cta']; ?>
    </div>
  </div>

  <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>