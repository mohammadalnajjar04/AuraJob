<?php
declare(strict_types=1);

/**
 * HirePeak shared site footer + page scripts.
 *
 * Pages may set $pageScripts (array of script src paths) before including so
 * each page ends with exactly the scripts it needs.
 */

require_once __DIR__ . '/../Support/html.php';

$pageScripts = $pageScripts ?? [];
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a class="brand" href="<?php echo hirepeak_url('index.php'); ?>" aria-label="HirePeak home">
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
            <li><a href="<?php echo hirepeak_url('jobs.php'); ?>">Find Jobs</a></li>
            <li><a href="<?php echo hirepeak_url('jobs.php'); ?>">Browse Categories</a></li>
            <li><a href="<?php echo hirepeak_url('index.php#why-hirepeak'); ?>">How It Works</a></li>
            <li><a href="<?php echo hirepeak_url('register.php'); ?>">Create Account</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h3 class="footer-col__title">Employers</h3>
          <ul>
            <li><a href="<?php echo hirepeak_url('employer/post-job.php'); ?>">Post a Job</a></li>
            <li><a href="<?php echo hirepeak_url('index.php#for-employers'); ?>">For Employers</a></li>
            <li><a href="<?php echo hirepeak_url('employer/my-jobs.php'); ?>">Applicant Pipeline</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h3 class="footer-col__title">HirePeak</h3>
          <ul>
            <li><a href="<?php echo hirepeak_url('index.php'); ?>">Home</a></li>
            <li><a href="<?php echo hirepeak_url('index.php#why-hirepeak'); ?>">How It Works</a></li>
            <li><a href="<?php echo hirepeak_url('index.php#for-employers'); ?>">For Employers</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="footer-bottom__credit">
        <p>&copy; <span id="year">2026</span> Mohammad Al-Najjar. All rights reserved.</p>
        <span class="footer-bottom__separator" aria-hidden="true">&bull;</span>
        <p>Developed in collaboration with <a href="https://unicoders.co/" target="_blank" rel="noopener noreferrer">UniCoders</a>.</p>
      </div>
      <p class="footer-bottom__legal">HirePeak &mdash; Job Board &amp; Recruitment Management System</p>
    </div>
  </div>
</footer>

<?php foreach ($pageScripts as $hpScript): ?>
  <script src="<?php echo htmlspecialchars((string) $hpScript, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php endforeach; ?>