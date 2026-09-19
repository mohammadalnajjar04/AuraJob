(function () {
  'use strict';

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const toggle = document.querySelector('[data-drawer-toggle]');
  const drawer = document.querySelector('[data-drawer]');
  const scrim = document.querySelector('[data-scrim]');

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
    const delay = reduceMotion.matches ? 0 : 200;
    window.setTimeout(function () {
      scrim.hidden = true;
    }, delay);
  }

  if (toggle && drawer && scrim) {
    if (window.innerWidth >= 768) {
      drawer.setAttribute('aria-hidden', 'false');
    }

    toggle.addEventListener('click', function () {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      if (open) {
        closeDrawer();
      } else {
        openDrawer();
      }
    });

    const closeButtons = drawer.querySelectorAll('[data-drawer-close]');
    for (const btn of closeButtons) {
      btn.addEventListener('click', closeDrawer);
    }

    scrim.addEventListener('click', closeDrawer);

    const drawerLinks = drawer.querySelectorAll('a');
    for (const link of drawerLinks) {
      if (link.getAttribute('href') === '#') continue;
      link.addEventListener('click', closeDrawer);
    }

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

  const header = document.querySelector('.site-header');

  function updateHeaderState() {
    if (!header) return;
    if (window.scrollY > 4) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }
  }

  if (header) {
    let ticking = false;
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

  /* The Home and For Employers links share index.php, so PHP cannot see the
   * URL fragment. Keep the active navigation state in sync with the hash on
   * initial load and whenever the fragment changes. */
  const navLinks = document.querySelectorAll('.site-nav__link[data-nav-key]');

  function syncHashNavigation() {
    if (!navLinks.length) return;

    const path = window.location.pathname.replace(/\/+$/, '');
    const isHomePage = path === '' || path === '/' || /\/index\.php$/i.test(path);
    if (!isHomePage) return;

    const activeKey = window.location.hash === '#for-employers' ? 'employers' : 'home';

    for (const link of navLinks) {
      const isActive = link.getAttribute('data-nav-key') === activeKey;
      link.classList.toggle('is-active', isActive);

      if (isActive) {
        link.setAttribute('aria-current', activeKey === 'employers' ? 'location' : 'page');
      } else {
        link.removeAttribute('aria-current');
      }
    }
  }

  syncHashNavigation();
  window.addEventListener('hashchange', syncHashNavigation);

  const chips = document.querySelectorAll('[data-chip]');
  const keyword = document.getElementById('hero-keyword');

  for (const chip of chips) {
    chip.addEventListener('click', function () {
      if (!keyword) return;
      keyword.value = chip.getAttribute('data-chip');
      keyword.focus();
    });
  }

  const bookmarkButtons = document.querySelectorAll('[data-bookmark]');

  Array.prototype.forEach.call(bookmarkButtons, function (btn) {
    if (btn.closest && btn.closest('form[data-save-job]')) return;
    btn.addEventListener('click', function () {
      const pressed = btn.getAttribute('aria-pressed') === 'true';
      btn.setAttribute('aria-pressed', pressed ? 'false' : 'true');
    });
  });

  /* Save/unsave uses delegated submit handling so job cards injected by the
   * AJAX jobs search keep the same progressive-enhancement behaviour. */
  if ('fetch' in window && 'FormData' in window) {
    document.addEventListener('submit', function (event) {
      const form = event.target && event.target.closest
        ? event.target.closest('form[data-save-job]')
        : null;

      if (!form) return;

      const btn = form.querySelector('[data-bookmark]');

      if (btn && btn.disabled) {
        event.preventDefault();
        return;
      }

      event.preventDefault();
      if (btn) { btn.disabled = true; btn.setAttribute('aria-disabled', 'true'); }

      const payload = new FormData(form);

      fetch(form.getAttribute('action') || '/save-job.php', {
        method: 'POST',
        body: payload,
        credentials: 'same-origin',
        headers: { 'Accept': 'application/json' }
      }).then(function (response) {
        if (response.status === 401) {
          window.location.assign('/login.php');
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
        const saved = !!data.saved;

        if (btn) {
          btn.disabled = false;
          btn.removeAttribute('aria-disabled');
          btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
          btn.setAttribute('aria-label', saved
            ? (form.getAttribute('data-saved-label') || btn.getAttribute('aria-label') || 'Unsave job')
            : (form.getAttribute('data-save-label') || btn.getAttribute('aria-label') || 'Save job'));
        }

        const actionInput = form.querySelector('input[name="action"]');
        if (actionInput) actionInput.value = saved ? 'unsave' : 'save';
      })['catch'](function () {
        if (btn) { btn.disabled = false; btn.removeAttribute('aria-disabled'); }
        form.submit();
      });
    });
  }

  const year = document.getElementById('year');

  if (year) {
    year.textContent = String(new Date().getFullYear());
  }

  // UX-only double-submit guard on the apply review form. The real protection
  // against duplicate applications is the schema UNIQUE(applicant_id, job_id).
  const applyForm = document.querySelector('[data-apply-form]');

  if (applyForm) {
    applyForm.addEventListener('submit', function () {
      const button = applyForm.querySelector('[data-apply-submit]');
      if (button) {
        button.disabled = true;
        button.setAttribute('aria-disabled', 'true');
      }
    });
  }
})();
