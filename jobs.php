<?php
declare(strict_types=1);

require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/auth.php';

hirepeak_start_session();

$pageActive = 'jobs';
$pageScripts = ['src/js/main.js', 'src/js/jobs.js'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Find Jobs — HirePeak</title>
  <meta name="description" content="Search for jobs on HirePeak. Filter roles by category, work mode, employment type, experience level, and location.">
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
    <section class="jobs-hero">
      <div class="container jobs-hero__inner">
        <h1 class="jobs-hero__title">Find your next opportunity</h1>
        <p class="jobs-hero__sub">Explore roles that match your skills, goals, and preferred way of working.</p>

        <form class="hero-search jobs-search" role="search" method="get" action="jobs.php" data-jobs-search>
          <label class="sr-only" for="jobs-keyword">Job title, skill, or company</label>
          <div class="hero-search__field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <circle cx="11" cy="11" r="7"/>
              <path d="m20 20-3.5-3.5"/>
            </svg>
            <input id="jobs-keyword" name="keyword" type="search" placeholder="Job title, skill, or company" autocomplete="off">
          </div>

          <div class="hero-search__divider" aria-hidden="true"></div>

          <label class="sr-only" for="jobs-location">Location</label>
          <div class="hero-search__field">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <input id="jobs-location" name="location" type="text" placeholder="City, or remote" autocomplete="off">
          </div>

          <button class="btn btn--primary hero-search__submit" type="submit">
            Search Jobs
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M5 12h14"/>
              <path d="m13 6 6 6-6 6"/>
            </svg>
          </button>
        </form>
      </div>
    </section>

    <section class="jobs" aria-labelledby="results-count">
      <div class="container jobs-layout">
        <aside class="jobs-sidebar" id="filter-panel" aria-label="Filters">
          <div class="jobs-sidebar__head">
            <h2 class="jobs-sidebar__title">Filters</h2>
            <button class="jobs-sidebar__clear" type="button">Clear all</button>
          </div>

          <form class="jobs-filters">
            <fieldset class="jobs-filter">
              <legend class="jobs-filter__title">Category</legend>
              <div class="jobs-filter__options">
                <label class="jobs-check"><input type="checkbox" name="category" value="Software Development"><span>Software Development</span></label>
                <label class="jobs-check"><input type="checkbox" name="category" value="QA &amp; Testing"><span>QA &amp; Testing</span></label>
                <label class="jobs-check"><input type="checkbox" name="category" value="UI/UX Design"><span>UI/UX Design</span></label>
                <label class="jobs-check"><input type="checkbox" name="category" value="Engineering"><span>Engineering</span></label>
                <label class="jobs-check"><input type="checkbox" name="category" value="Marketing"><span>Marketing</span></label>
                <label class="jobs-check"><input type="checkbox" name="category" value="Finance"><span>Finance</span></label>
                <label class="jobs-check"><input type="checkbox" name="category" value="Customer Service"><span>Customer Service</span></label>
              </div>
            </fieldset>

            <fieldset class="jobs-filter">
              <legend class="jobs-filter__title">Employment type</legend>
              <div class="jobs-filter__options">
                <label class="jobs-check"><input type="checkbox" name="employment" value="Full Time"><span>Full Time</span></label>
                <label class="jobs-check"><input type="checkbox" name="employment" value="Part Time"><span>Part Time</span></label>
                <label class="jobs-check"><input type="checkbox" name="employment" value="Internship"><span>Internship</span></label>
                <label class="jobs-check"><input type="checkbox" name="employment" value="Contract"><span>Contract</span></label>
              </div>
            </fieldset>

            <fieldset class="jobs-filter">
              <legend class="jobs-filter__title">Work mode</legend>
              <div class="jobs-filter__options">
                <label class="jobs-check"><input type="checkbox" name="mode" value="On-site"><span>On-site</span></label>
                <label class="jobs-check"><input type="checkbox" name="mode" value="Hybrid"><span>Hybrid</span></label>
                <label class="jobs-check"><input type="checkbox" name="mode" value="Remote"><span>Remote</span></label>
              </div>
            </fieldset>

            <fieldset class="jobs-filter">
              <legend class="jobs-filter__title">Experience level</legend>
              <div class="jobs-filter__options">
                <label class="jobs-check"><input type="checkbox" name="experience" value="Entry Level"><span>Entry Level</span></label>
                <label class="jobs-check"><input type="checkbox" name="experience" value="Junior"><span>Junior</span></label>
                <label class="jobs-check"><input type="checkbox" name="experience" value="Mid Level"><span>Mid Level</span></label>
                <label class="jobs-check"><input type="checkbox" name="experience" value="Senior"><span>Senior</span></label>
              </div>
            </fieldset>

            <fieldset class="jobs-filter">
              <legend class="jobs-filter__title">Location</legend>
              <div class="jobs-filter__options">
                <label class="jobs-check"><input type="checkbox" name="location" value="Amman"><span>Amman</span></label>
                <label class="jobs-check"><input type="checkbox" name="location" value="Irbid"><span>Irbid</span></label>
                <label class="jobs-check"><input type="checkbox" name="location" value="Zarqa"><span>Zarqa</span></label>
                <label class="jobs-check"><input type="checkbox" name="location" value="Remote"><span>Remote</span></label>
              </div>
            </fieldset>
          </form>
        </aside>

        <div class="jobs-results">
          <div class="jobs-toolbar">
            <div class="jobs-toolbar__row">
              <h2 class="jobs-count" id="results-count" aria-live="polite">9 opportunities</h2>
              <div class="jobs-toolbar__controls">
                <button class="btn btn--outline jobs-filter-trigger" type="button" aria-expanded="false" aria-controls="filter-drawer">
                  Filters <span class="jobs-filter-trigger__badge" hidden>0</span>
                </button>
                <label class="jobs-sort">
                  <span class="sr-only">Sort results</span>
                  <select>
                    <option value="newest">Newest</option>
                    <option value="salary-desc">Salary: High to Low</option>
                    <option value="salary-asc">Salary: Low to High</option>
                  </select>
                </label>
              </div>
            </div>
          </div>

          <div class="active-filters" data-active-filters hidden>
            <ul aria-label="Active filters"></ul>
            <button class="active-filters__clear" type="button" data-clear-all>Clear all</button>
          </div>

          <div class="jobs-list">

            <article class="job-card job-card--row" data-keywords="php laravel mysql api sql backend">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--nl" aria-hidden="true">NL</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=1">Backend Developer</a></h3>
                  <p class="job-card--row__company">Northstar Labs <span class="job-card__dot" aria-hidden="true">&middot;</span> Amman <span class="job-card__dot" aria-hidden="true">&middot;</span> Hybrid</p>
                  <p class="job-card--row__meta">Full Time <span class="job-card__dot" aria-hidden="true">&middot;</span> Senior</p>
                  <p class="job-card--row__salary">800 <span aria-hidden="true">&ndash;</span> 1,100 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">Software Development <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 2 days ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Backend Developer at Northstar Labs">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=1">View Job</a>
              </div>
            </article>

            <article class="job-card job-card--row" data-keywords="figma prototyping wireframes ui design">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--vd" aria-hidden="true">VD</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=2">Product Designer</a></h3>
                  <p class="job-card--row__company">Vertex Digital <span class="job-card__dot" aria-hidden="true">&middot;</span> Worldwide <span class="job-card__dot" aria-hidden="true">&middot;</span> Remote</p>
                  <p class="job-card--row__meta">Full Time <span class="job-card__dot" aria-hidden="true">&middot;</span> Mid Level</p>
                  <p class="job-card--row__salary">700 <span aria-hidden="true">&ndash;</span> 950 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">UI/UX Design <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 1 day ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Product Designer at Vertex Digital">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=2">View Job</a>
              </div>
            </article>

            <article class="job-card job-card--row" data-keywords="testing automation selenium qa manual">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--as" aria-hidden="true">AS</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=3">QA Engineer</a></h3>
                  <p class="job-card--row__company">Atlas Systems <span class="job-card__dot" aria-hidden="true">&middot;</span> Amman <span class="job-card__dot" aria-hidden="true">&middot;</span> On-site</p>
                  <p class="job-card--row__meta">Full Time <span class="job-card__dot" aria-hidden="true">&middot;</span> Junior</p>
                  <p class="job-card--row__salary">500 <span aria-hidden="true">&ndash;</span> 650 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">QA &amp; Testing <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 3 days ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save QA Engineer at Atlas Systems">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=3">View Job</a>
              </div>
            </article>

            <article class="job-card job-card--row" data-keywords="marketing social media seo campaigns">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--nw" aria-hidden="true">NW</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=4">Marketing Specialist</a></h3>
                  <p class="job-card--row__company">NovaWorks <span class="job-card__dot" aria-hidden="true">&middot;</span> Amman <span class="job-card__dot" aria-hidden="true">&middot;</span> On-site</p>
                  <p class="job-card--row__meta">Full Time <span class="job-card__dot" aria-hidden="true">&middot;</span> Entry Level</p>
                  <p class="job-card--row__salary">500 <span aria-hidden="true">&ndash;</span> 650 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">Marketing <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 4 days ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Marketing Specialist at NovaWorks">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=4">View Job</a>
              </div>
            </article>

            <article class="job-card job-card--row" data-keywords="javascript react css frontend">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--ol" aria-hidden="true">OL</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=5">Frontend Developer</a></h3>
                  <p class="job-card--row__company">Orbit Labs <span class="job-card__dot" aria-hidden="true">&middot;</span> Worldwide <span class="job-card__dot" aria-hidden="true">&middot;</span> Remote</p>
                  <p class="job-card--row__meta">Contract <span class="job-card__dot" aria-hidden="true">&middot;</span> Mid Level</p>
                  <p class="job-card--row__salary">700 <span aria-hidden="true">&ndash;</span> 950 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">Software Development <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 1 week ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Frontend Developer at Orbit Labs">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=5">View Job</a>
              </div>
            </article>

            <article class="job-card job-card--row" data-keywords="finance analysis excel modeling">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--bp" aria-hidden="true">BP</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=6">Financial Analyst</a></h3>
                  <p class="job-card--row__company">BridgePoint <span class="job-card__dot" aria-hidden="true">&middot;</span> Amman <span class="job-card__dot" aria-hidden="true">&middot;</span> On-site</p>
                  <p class="job-card--row__meta">Full Time <span class="job-card__dot" aria-hidden="true">&middot;</span> Junior</p>
                  <p class="job-card--row__salary">600 <span aria-hidden="true">&ndash;</span> 800 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">Finance <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 5 days ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Financial Analyst at BridgePoint">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=6">View Job</a>
              </div>
            </article>

            <article class="job-card job-card--row" data-keywords="customer success support communication">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--lt" aria-hidden="true">LT</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=7">Customer Success Specialist</a></h3>
                  <p class="job-card--row__company">Lumina Technologies <span class="job-card__dot" aria-hidden="true">&middot;</span> Irbid <span class="job-card__dot" aria-hidden="true">&middot;</span> Hybrid</p>
                  <p class="job-card--row__meta">Full Time <span class="job-card__dot" aria-hidden="true">&middot;</span> Entry Level</p>
                  <p class="job-card--row__salary">500 <span aria-hidden="true">&ndash;</span> 650 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">Customer Service <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 3 days ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Customer Success Specialist at Lumina Technologies">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=7">View Job</a>
              </div>
            </article>

            <article class="job-card job-card--row" data-keywords="javascript python problem solving">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--cd" aria-hidden="true">CD</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=8">Junior Software Engineer</a></h3>
                  <p class="job-card--row__company">Cedar Digital <span class="job-card__dot" aria-hidden="true">&middot;</span> Zarqa <span class="job-card__dot" aria-hidden="true">&middot;</span> On-site</p>
                  <p class="job-card--row__meta">Full Time <span class="job-card__dot" aria-hidden="true">&middot;</span> Junior</p>
                  <p class="job-card--row__salary">600 <span aria-hidden="true">&ndash;</span> 800 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">Software Development <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 2 days ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Junior Software Engineer at Cedar Digital">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=8">View Job</a>
              </div>
            </article>

            <article class="job-card job-card--row" data-keywords="sql python etl data pipelines">
              <div class="job-card--row__main">
                <span class="job-card__logo job-card__logo--as" aria-hidden="true">AS</span>
                <div class="job-card--row__body">
                  <h3 class="job-card__title"><a href="job-details.php?id=9">Data Engineer</a></h3>
                  <p class="job-card--row__company">Atlas Systems <span class="job-card__dot" aria-hidden="true">&middot;</span> Amman <span class="job-card__dot" aria-hidden="true">&middot;</span> Hybrid</p>
                  <p class="job-card--row__meta">Full Time <span class="job-card__dot" aria-hidden="true">&middot;</span> Senior</p>
                  <p class="job-card--row__salary">900 <span aria-hidden="true">&ndash;</span> 1,200 JOD <span class="job-card--row__period">/ month</span></p>
                  <p class="job-card--row__foot">Engineering <span class="job-card__dot" aria-hidden="true">&middot;</span> Posted 6 days ago</p>
                </div>
              </div>
              <div class="job-card--row__actions">
                <button class="icon-button job-card__save" type="button" data-bookmark aria-pressed="false" aria-label="Save Data Engineer at Atlas Systems">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M19 21 12 16 5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2Z"/>
                  </svg>
                </button>
                <a class="btn btn--outline btn--sm" href="job-details.php?id=9">View Job</a>
              </div>
            </article>
          </div>

          <div class="jobs-empty" hidden>
            <div class="jobs-empty__icon" aria-hidden="true">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="7"/>
                <path d="m21 21-4.35-4.35"/>
                <path d="M8.5 8.5l5 5"/>
                <path d="M13.5 8.5l-5 5"/>
              </svg>
            </div>
            <h3 class="jobs-empty__title">No jobs match those filters.</h3>
            <p class="jobs-empty__text">Try adjusting your search or clearing some filters.</p>
            <button class="btn btn--primary" type="button">Clear Filters</button>
          </div>

          <nav class="pagination" aria-label="Results pagination">
            <p class="pagination__meta">Showing 1&ndash;5 of 9</p>
            <div class="pagination__controls">
              <button class="pagination__btn" type="button" data-page-prev aria-label="Previous page" disabled>Previous</button>
              <div class="pagination__nums" data-page-nums>
                <button class="pagination__num is-current" type="button" aria-current="page">1</button>
                <button class="pagination__num" type="button">2</button>
              </div>
              <button class="pagination__btn" type="button" data-page-next aria-label="Next page">Next</button>
            </div>
          </nav>
        </div>
      </div>
    </section>

    <div class="scrim filter-drawer__scrim" data-filter-scrim hidden></div>

    <aside class="filter-drawer" id="filter-drawer" data-filter-drawer aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="filter-drawer-title" tabindex="-1">
      <div class="filter-drawer__head">
        <h2 class="filter-drawer__title" id="filter-drawer-title">Filters</h2>
        <button class="icon-button" type="button" data-filter-close id="filter-drawer-close" aria-label="Close filters">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M6 6l12 12"/>
            <path d="M18 6 6 18"/>
          </svg>
        </button>
      </div>
      <div class="filter-drawer__body" data-filter-drawer-body></div>
      <div class="filter-drawer__foot">
        <button class="btn btn--ghost" type="button" data-filter-reset>Reset</button>
        <button class="btn btn--primary" type="button" data-filter-apply>Show results</button>
      </div>
    </aside>
  </main>

  <?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>