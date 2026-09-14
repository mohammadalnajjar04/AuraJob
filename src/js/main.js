(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var toggle = document.querySelector('[data-drawer-toggle]');
  var drawer = document.querySelector('[data-drawer]');
  var scrim = document.querySelector('[data-scrim]');

  function openDrawer() {
    if (!toggle || !drawer || !scrim) return;
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close menu');
    drawer.setAttribute('aria-hidden', 'false');
    drawer.classList.add('is-open');
    scrim.hidden = false;
    document.body.classList.add('no-scroll');
    window.requestAnimationFrame(function () {
      scrim.classList.add('is-visible');
    });
  }

  function closeDrawer() {
    if (!toggle || !drawer || !scrim) return;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.classList.remove('is-open');
    document.body.classList.remove('no-scroll');
    scrim.classList.remove('is-visible');
    var delay = reduceMotion.matches ? 0 : 200;
    window.setTimeout(function () {
      scrim.hidden = true;
    }, delay);
  }

  if (toggle && drawer && scrim) {
    if (window.innerWidth >= 768) {
      drawer.setAttribute('aria-hidden', 'false');
    }

    toggle.addEventListener('click', function () {
      var open = toggle.getAttribute('aria-expanded') === 'true';
      if (open) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    var closeButtons = drawer.querySelectorAll('[data-drawer-close]');
    Array.prototype.forEach.call(closeButtons, function (btn) {
      btn.addEventListener('click', closeDrawer);
    });

    scrim.addEventListener('click', closeDrawer);

    var drawerLinks = drawer.querySelectorAll('a');
    Array.prototype.forEach.call(drawerLinks, function (link) {
      if (link.getAttribute('href') === '#') return;
      link.addEventListener('click', closeDrawer);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        closeDrawer();
      }
    });

    window.addEventListener('resize', function () {
      if (window.innerWidth >= 768 && toggle.getAttribute('aria-expanded') === 'true') {
        closeDrawer();
      }
    });
  }

  var header = document.querySelector('.site-header');

  function updateHeaderState() {
    if (!header) return;
    if (window.scrollY > 4) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }
  }

  if (header) {
    var ticking = false;
    window.addEventListener('scroll', function () {
      if (!ticking) {
        window.requestAnimationFrame(function () {
          updateHeaderState();
          ticking = false;
        });
        ticking = true;
      }
    }, { passive: true });
    updateHeaderState();
  }

  var chips = document.querySelectorAll('[data-chip]');
  var keyword = document.getElementById('hero-keyword');

  Array.prototype.forEach.call(chips, function (chip) {
    chip.addEventListener('click', function () {
      if (!keyword) return;
      keyword.value = chip.getAttribute('data-chip');
      keyword.focus();
    });
  });

  var bookmarkButtons = document.querySelectorAll('[data-bookmark]');

  Array.prototype.forEach.call(bookmarkButtons, function (btn) {
    if (btn.closest && btn.closest('form[data-save-job]')) return;
    btn.addEventListener('click', function () {
      var pressed = btn.getAttribute('aria-pressed') === 'true';
      btn.setAttribute('aria-pressed', pressed ? 'false' : 'true');
    });
  });

  var saveForms = document.querySelectorAll('form[data-save-job]');

  if ('fetch' in window && 'FormData' in window) {
    Array.prototype.forEach.call(saveForms, function (form) {
      form.addEventListener('submit', function (event) {
        var btn = form.querySelector('[data-bookmark]');

        if (btn && btn.disabled) {
          event.preventDefault();
          return;
        }

        event.preventDefault();
        if (btn) { btn.disabled = true; btn.setAttribute('aria-disabled', 'true'); }

        var payload = new FormData(form);

        fetch(form.getAttribute('action') || 'save-job.php', {
          method: 'POST',
          body: payload,
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' }
        }).then(function (response) {
          if (response.status === 401) {
            window.location.assign('login.php');
            return null;
          }
          if (response.status === 403 || response.status === 404 || response.status === 405 || response.status >= 500) {
            window.location.reload();
            return null;
          }
          return response.json().then(function (data) {
            if (!data || !data.ok) { window.location.reload(); return null; }
            return data;
          })['catch'](function () { window.location.reload(); return null; });
        }).then(function (data) {
          if (!data) return;
          var saved = !!data.saved;

          if (btn) {
            btn.disabled = false;
            btn.removeAttribute('aria-disabled');
            btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
            btn.setAttribute('aria-label', saved
              ? (form.getAttribute('data-saved-label') || btn.getAttribute('aria-label') || 'Unsave job')
              : (form.getAttribute('data-save-label') || btn.getAttribute('aria-label') || 'Save job'));
          }

          var actionInput = form.querySelector('input[name="action"]');
          if (actionInput) actionInput.value = saved ? 'unsave' : 'save';
        })['catch'](function () {
          if (btn) { btn.disabled = false; btn.removeAttribute('aria-disabled'); }
          form.submit();
        });
      });
    });
  }

  var heroSearch = document.querySelector('[data-hero-search]');

  if (heroSearch) {
    heroSearch.addEventListener('submit', function (event) {
      event.preventDefault();
    });
  }

  var year = document.getElementById('year');

  if (year) {
    year.textContent = String(new Date().getFullYear());
  }

  // UX-only double-submit guard on the apply review form. The real protection
  // against duplicate applications is the schema UNIQUE(applicant_id, job_id).
  var applyForm = document.querySelector('[data-apply-form]');

  if (applyForm) {
    applyForm.addEventListener('submit', function () {
      var button = applyForm.querySelector('[data-apply-submit]');
      if (button) {
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
      }
    });
  }
})();