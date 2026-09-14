(function () {
  'use strict';

  /* jobs.js no longer filters/sorts/paginates client-side — the server is the
   * single source of truth (jobs.php applies GET params via MySQL). This file
   * only wires progressive-enhancement UX: instant form submission on change,
   * and the mobile filter drawer open/close behaviour. Without JS every control
   * still works through native GET forms and links. */

  var sidebarForm = document.querySelector('[data-jobs-filters]');
  var sortForm = document.querySelector('[data-jobs-sort]');
  var searchForm = document.querySelector('[data-jobs-search]');
  var keywordInput = document.getElementById('jobs-keyword');
  var locationInput = document.getElementById('jobs-location');
  var trigger = document.querySelector('.jobs-filter-trigger');
  var drawer = document.querySelector('[data-filter-drawer]');
  var drawerBody = document.querySelector('[data-filter-drawer-body]');
  var drawerScrim = document.querySelector('[data-filter-scrim]');
  var drawerClose = document.querySelector('[data-filter-close]');
  var drawerApply = document.querySelector('[data-filter-apply]');

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var desktopMedia = window.matchMedia('(min-width: 1025px)');
  var drawerOpen = false;

  function submitForm(form) {
    if (!form) return;
    if (typeof form.requestSubmit === 'function') form.requestSubmit();
    else form.submit();
  }

  /* Instant-apply progressive enhancement. Each change submits the GET form, so
   * the URL always reflects the filter state (refresh-safe / shareable). */
  if (sidebarForm) {
    sidebarForm.addEventListener('change', function (event) {
      if (event.target && event.target.type === 'checkbox') submitForm(sidebarForm);
    });
  }

  if (sortForm) {
    sortForm.addEventListener('change', function () {
      submitForm(sortForm);
    });
  }

  function debounce(fn, delay) {
    var timer = null;
    return function () {
      clearTimeout(timer);
      timer = setTimeout(fn, delay);
    };
  }

  if (searchForm && (keywordInput || locationInput)) {
    var submitSearch = debounce(function () {
      submitForm(searchForm);
    }, 450);
    if (keywordInput) keywordInput.addEventListener('input', submitSearch);
    if (locationInput) locationInput.addEventListener('input', submitSearch);
  }

  /* --- Mobile filter drawer ------------------------------------------------ */

  function syncDrawerCopy() {
    if (!drawerBody || !sidebarForm) return;
    drawerBody.innerHTML = '';
    drawerBody.appendChild(sidebarForm.cloneNode(true));
  }

  function openDrawer() {
    if (!drawer || !drawerScrim || !trigger || desktopMedia.matches) return;
    syncDrawerCopy();
    drawerOpen = true;
    trigger.setAttribute('aria-expanded', 'true');
    drawer.setAttribute('aria-hidden', 'false');
    drawer.classList.add('is-open');
    drawerScrim.hidden = false;
    document.body.classList.add('no-scroll');
    window.requestAnimationFrame(function () {
      drawerScrim.classList.add('is-visible');
    });
    var closeBtn = document.getElementById('filter-drawer-close');
    if (closeBtn) closeBtn.focus();
  }

  function closeDrawer() {
    if (!drawer || !drawerScrim || !trigger) return;
    drawerOpen = false;
    trigger.setAttribute('aria-expanded', 'false');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.classList.remove('is-open');
    document.body.classList.remove('no-scroll');
    drawerScrim.classList.remove('is-visible');
    var delay = reduceMotion.matches ? 0 : 200;
    window.setTimeout(function () {
      drawerScrim.hidden = true;
    }, delay);
    trigger.focus();
  }

  if (trigger) trigger.addEventListener('click', function () {
    if (drawerOpen) closeDrawer();
    else openDrawer();
  });
  if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
  if (drawerScrim) drawerScrim.addEventListener('click', closeDrawer);

  /* The drawer holds a cloned copy of the sidebar GET form; "Show results"
   * submits it so navigation carries every selected filter. */
  if (drawerApply) drawerApply.addEventListener('click', function () {
    submitForm(drawerBody ? drawerBody.querySelector('form') : null);
  });

  if (drawer) drawer.addEventListener('keydown', function (event) {
    if (event.key !== 'Tab') return;
    var focusables = drawer.querySelectorAll('button, [href], input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])');
    if (!focusables.length) return;
    var first = focusables[0];
    var last = focusables[focusables.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && drawerOpen) closeDrawer();
  });
})();