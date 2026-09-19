<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/config.php';
require dirname(__DIR__) . '/app/Core/auth.php';
require dirname(__DIR__) . '/app/Core/db.php';
require dirname(__DIR__) . '/app/Support/html.php';

hirepeak_start_session();

$pageActive = 'home';
$pageScripts = ['/assets/js/main.js'];

$latestJobs = [];
$homeCategories = [];
$homeDbError = false;
$viewer = hirepeak_user();
$viewerRole = (string) ($viewer['role'] ?? '');
$saveCsrf = $viewerRole === 'applicant' ? hirepeak_csrf_token() : '';
$savedSet = [];

try {
    $db = hirepeak_db();

    $stmt = $db->prepare(
        "SELECT j.id, j.title, j.location, j.salary_min, j.salary_max,
                j.employment_type, j.work_mode, j.created_at,
                c.name AS company_name, c.logo_path AS company_logo_path, ct.name AS category_name
         FROM jobs j
         INNER JOIN companies c ON c.id = j.company_id
         INNER JOIN categories ct ON ct.id = j.category_id
         WHERE j.status = 'active'
           AND (j.application_deadline IS NULL OR j.application_deadline >= CURDATE())
         ORDER BY j.created_at DESC, j.id DESC
         LIMIT 3"
    );
    $stmt->execute();
    $latestJobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $result = $db->query(
        "SELECT ct.id, ct.name, ct.slug, COUNT(j.id) AS open_jobs
         FROM categories ct
         LEFT JOIN jobs j
           ON j.category_id = ct.id
          AND j.status = 'active'
          AND (j.application_deadline IS NULL OR j.application_deadline >= CURDATE())
         GROUP BY ct.id, ct.name, ct.slug
         ORDER BY open_jobs DESC, ct.name ASC
         LIMIT 6"
    );
    $homeCategories = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();

    if ($viewerRole === 'applicant' && $viewer !== null) {
        $savedSet = array_fill_keys(hirepeak_saved_job_ids($db, (int) $viewer['id']), true);
    }
} catch (\Throwable $e) {
    error_log('[aurajob] home data load failed: ' . $e->getMessage());
    $homeDbError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AuraJob — Find the right opportunity. Build your next move.</title>
  <meta name="description" content="AuraJob is a modern recruitment platform connecting talented candidates with employers, with clear application tracking and a straightforward hiring workflow.">
  <link rel="canonical" href="<?php echo hirepeak_esc(hirepeak_absolute_url('index.php')); ?>">
  <meta property="og:site_name" content="AuraJob">
  <meta property="og:type" content="website">
  <meta property="og:title" content="<?php echo hirepeak_esc('AuraJob — Find the right opportunity. Build your next move.'); ?>">
  <meta property="og:description" content="<?php echo hirepeak_esc('AuraJob is a modern recruitment platform connecting talented candidates with employers, with clear application tracking and a straightforward hiring workflow.'); ?>">
  <meta property="og:url" content="<?php echo hirepeak_esc(hirepeak_absolute_url('index.php')); ?>">
  <meta property="og:image" content="<?php echo hirepeak_esc(hirepeak_absolute_url('assets/images/aurajob-social-preview.jpg')); ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="AuraJob — Find the right opportunity. Build your next move.">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo hirepeak_esc('AuraJob — Find the right opportunity. Build your next move.'); ?>">
  <meta name="twitter:description" content="<?php echo hirepeak_esc('AuraJob is a modern recruitment platform connecting talented candidates with employers, with clear application tracking and a straightforward hiring workflow.'); ?>">
  <meta name="twitter:image" content="<?php echo hirepeak_esc(hirepeak_absolute_url('assets/images/aurajob-social-preview.jpg')); ?>">
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
          <span class="hero-popular__label" id="popular-label">Popular categories:</span>
          <ul aria-labelledby="popular-label">
            <?php foreach (array_slice($homeCategories, 0, 4) as $category): ?>
              <?php $categoryUrl = hirepeak_url('jobs.php?category%5B%5D=' . rawurlencode((string) $category['name'])); ?>
              <li><a class="chip" href="<?php echo hirepeak_esc($categoryUrl); ?>"><?php echo hirepeak_esc((string) $category['name']); ?></a></li>
            <?php endforeach; ?>
            <?php if ($homeCategories === []): ?>
              <li><a class="chip" href="<?php echo hirepeak_url('jobs.php'); ?>">Browse jobs</a></li>
            <?php endif; ?>
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
          <?php if ($homeDbError): ?>
            <section class="empty-state" aria-labelledby="home-jobs-error-title">
              <div class="empty-state__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><path d="M12 16h.01"/>
                </svg>
              </div>
              <h3 class="empty-state__title" id="home-jobs-error-title">Jobs are temporarily unavailable.</h3>
              <p class="empty-state__text">Please try again in a moment.</p>
              <a class="btn btn--outline btn--sm" href="<?php echo hirepeak_url('jobs.php'); ?>">Open Find Jobs</a>
            </section>
          <?php elseif ($latestJobs === []): ?>
            <section class="empty-state" aria-labelledby="home-jobs-empty-title">
              <div class="empty-state__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M3 12h18"/>
                </svg>
              </div>
              <h3 class="empty-state__title" id="home-jobs-empty-title">No open jobs yet.</h3>
              <p class="empty-state__text">New opportunities will appear here as soon as employers publish them.</p>
              <a class="btn btn--outline btn--sm" href="<?php echo hirepeak_url('jobs.php'); ?>">Browse all jobs</a>
            </section>
          <?php else: ?>
            <?php foreach ($latestJobs as $job): ?>
              <?php
              $jobId = (int) $job['id'];
              $title = (string) $job['title'];
              $company = (string) $job['company_name'];
              $location = (string) $job['location'];
              $saved = isset($savedSet[$jobId]);
              $saveLabel = 'Save ' . $title . ' at ' . $company;
              $savedLabel = 'Remove ' . $title . ' at ' . $company . ' from saved jobs';
              $salary = hirepeak_salary_range(
                  isset($job['salary_min']) ? (string) $job['salary_min'] : '',
                  isset($job['salary_max']) ? (string) $job['salary_max'] : ''
              );
              $posted = hirepeak_relative_time((string) $job['created_at']);
              ?>
              <article class="job-card">
                <div class="job-card__top">
                  <?php echo hirepeak_company_identity_markup($company, isset($job['company_logo_path']) ? (string) $job['company_logo_path'] : null, 'job-card__logo'); ?>
                  <?php if ($viewerRole === 'applicant'): ?>
                    <form class="save-form" method="post" action="<?php echo hirepeak_url('save-job.php'); ?>" data-save-job data-job-id="<?php echo $jobId; ?>" data-save-label="<?php echo hirepeak_esc($saveLabel); ?>" data-saved-label="<?php echo hirepeak_esc($savedLabel); ?>">
                      <input type="hidden" name="csrf_token" value="<?php echo hirepeak_esc($saveCsrf); ?>">
                      <input type="hidden" name="job_id" value="<?php echo $jobId; ?>">
                      <input type="hidden" name="action" value="<?php echo $saved ? 'unsave' : 'save'; ?>">
                      <button class="icon-button job-card__save" type="submit" data-bookmark aria-pressed="<?php echo $saved ? 'true' : 'false'; ?>" aria-label="<?php echo hirepeak_esc($saved ? $savedLabel : $saveLabel); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                        </svg>
                      </button>
                    </form>
                  <?php elseif ($viewerRole === ''): ?>
                    <a class="icon-button job-card__save" href="<?php echo hirepeak_url('login.php'); ?>" aria-label="Sign in to save <?php echo hirepeak_esc($title); ?> at <?php echo hirepeak_esc($company); ?>">
                      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                      </svg>
                    </a>
                  <?php endif; ?>
                </div>
                <h3 class="job-card__title"><a href="<?php echo hirepeak_url('job-details.php?id=' . $jobId); ?>"><?php echo hirepeak_esc($title); ?></a></h3>
                <p class="job-card__company"><?php echo hirepeak_esc($company); ?><?php echo $location !== '' ? ' &middot; ' . hirepeak_esc($location) : ''; ?></p>
                <ul class="job-card__meta" aria-label="Job type and work mode">
                  <li class="chip"><?php echo hirepeak_esc(hirepeak_employment_type_label((string) $job['employment_type'])); ?></li>
                  <li class="chip"><?php echo hirepeak_esc(hirepeak_work_mode_label((string) $job['work_mode'])); ?></li>
                </ul>
                <p class="job-card__salary"><?php echo $salary; ?></p>
                <p class="job-card__posted"><?php echo $posted !== '' ? 'Posted ' . hirepeak_esc($posted) : ''; ?></p>
              </article>
            <?php endforeach; ?>
          <?php endif; ?>
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
          <?php if ($homeDbError): ?>
            <section class="empty-state">
              <h3 class="empty-state__title">Categories are temporarily unavailable.</h3>
              <p class="empty-state__text">You can still browse all open roles.</p>
              <a class="btn btn--outline btn--sm" href="<?php echo hirepeak_url('jobs.php'); ?>">Browse jobs</a>
            </section>
          <?php elseif ($homeCategories === []): ?>
            <section class="empty-state">
              <h3 class="empty-state__title">No categories available yet.</h3>
              <p class="empty-state__text">Categories will appear here once they are added.</p>
            </section>
          <?php else: ?>
            <?php foreach ($homeCategories as $category): ?>
              <?php
              $categoryName = (string) $category['name'];
              $openJobs = (int) $category['open_jobs'];
              $categoryUrl = hirepeak_url('jobs.php?category%5B%5D=' . rawurlencode($categoryName));
              ?>
              <a class="category-card" href="<?php echo hirepeak_esc($categoryUrl); ?>">
                <span class="category-card__icon">
                  <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                  </svg>
                </span>
                <span class="category-card__body">
                  <span class="category-card__name"><?php echo hirepeak_esc($categoryName); ?></span>
                  <span class="category-card__cta">
                    <?php echo $openJobs === 1 ? '1 open job' : $openJobs . ' open jobs'; ?>
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M5 12h14"/>
                      <path d="m13 6 6 6-6 6"/>
                    </svg>
                  </span>
                </span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
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