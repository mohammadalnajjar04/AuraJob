(function () {
  'use strict';

  /*
   * Jobs search/filter progressive enhancement.
   *
   * The server remains the single source of truth: every request still goes to
   * jobs.php, which validates GET parameters and runs the prepared MySQL query.
   * When fetch is available, the returned server-rendered <main> is swapped
   * into the page and the URL is updated with History API. Without JavaScript,
   * the same forms and links continue to perform normal GET navigation.
   */

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const desktopMedia = window.matchMedia('(min-width: 1025px)');
  let drawerOpen = false;
  let requestController = null;
  let searchTimer = null;

  function jobsMain() {
    return document.getElementById('main');
  }

  function formUrl(form) {
    const url = new URL(form.getAttribute('action') || '/jobs.php', window.location.href);
    const params = new URLSearchParams(new FormData(form));
    url.search = params.toString();
    return url;
  }

  function setLoading(loading) {
    const main = jobsMain();
    if (!main) return;
    if (loading) main.setAttribute('aria-busy', 'true');
    else main.removeAttribute('aria-busy');
  }

  function resetDrawerState() {
    drawerOpen = false;
    document.body.classList.remove('no-scroll');
  }

  function replaceMain(html, url, pushHistory) {
    const parser = new DOMParser();
    const nextDocument = parser.parseFromString(html, 'text/html');
    const nextMain = nextDocument.getElementById('main');
    const currentMain = jobsMain();

    if (!nextMain || !currentMain) {
      throw new Error('Jobs response did not contain #main.');
    }

    resetDrawerState();
    currentMain.innerHTML = nextMain.innerHTML;

    if (pushHistory) {
      window.history.pushState({}, '', url.pathname + url.search + url.hash);
    }
  }

  function loadJobs(url, pushHistory) {
    if (!('fetch' in window) || !('DOMParser' in window) || !('URL' in window)) {
      window.location.assign(url.toString());
      return;
    }

    if (requestController) requestController.abort();
    requestController = 'AbortController' in window ? new AbortController() : null;
    const activeController = requestController;

    setLoading(true);

    const options = {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'text/html',
        'X-Requested-With': 'XMLHttpRequest'
      }
    };
    if (activeController) options.signal = activeController.signal;

    fetch(url.toString(), options).then(function (response) {
      if (!response.ok) throw new Error('Jobs request failed with status ' + response.status + '.');
      return response.text();
    }).then(function (html) {
      replaceMain(html, url, pushHistory);
    })['catch'](function (error) {
      if (error && error.name === 'AbortError') return;
      window.location.assign(url.toString());
    }).then(function () {
      if (requestController === activeController) {
        requestController = null;
        setLoading(false);
      }
    });
  }

  function submitJobsForm(form) {
    if (!form) return;
    loadJobs(formUrl(form), true);
  }

  function syncDrawerCopy() {
    const drawerBody = document.querySelector('[data-filter-drawer-body]');
    const sidebarForm = document.querySelector('.jobs-sidebar [data-jobs-filters]');
    if (!drawerBody || !sidebarForm) return;
    drawerBody.innerHTML = '';
    drawerBody.appendChild(sidebarForm.cloneNode(true));
  }

  function openDrawer() {
    const drawer = document.querySelector('[data-filter-drawer]');
    const drawerScrim = document.querySelector('[data-filter-scrim]');
    const trigger = document.querySelector('.jobs-filter-trigger');
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

    const closeBtn = document.getElementById('filter-drawer-close');
    if (closeBtn) closeBtn.focus();
  }

  function closeDrawer(restoreFocus) {
    const drawer = document.querySelector('[data-filter-drawer]');
    const drawerScrim = document.querySelector('[data-filter-scrim]');
    const trigger = document.querySelector('.jobs-filter-trigger');
    if (!drawer || !drawerScrim || !trigger) return;

    drawerOpen = false;
    trigger.setAttribute('aria-expanded', 'false');
    drawer.setAttribute('aria-hidden', 'true');
    drawer.classList.remove('is-open');
    document.body.classList.remove('no-scroll');
    drawerScrim.classList.remove('is-visible');

    const delay = reduceMotion.matches ? 0 : 200;
    window.setTimeout(function () {
      drawerScrim.hidden = true;
    }, delay);

    if (restoreFocus !== false) trigger.focus();
  }

  document.addEventListener('submit', function (event) {
    const form = event.target && event.target.closest
      ? event.target.closest('[data-jobs-search], [data-jobs-filters], [data-jobs-sort]')
      : null;
    if (!form) return;

    event.preventDefault();
    submitJobsForm(form);
  });

  document.addEventListener('change', function (event) {
    const target = event.target;
    if (!target || !target.closest) return;

    const filterForm = target.closest('[data-jobs-filters]');
    if (filterForm && target.type === 'checkbox' && !target.closest('[data-filter-drawer-body]')) {
      submitJobsForm(filterForm);
      return;
    }

    const sortForm = target.closest('[data-jobs-sort]');
    if (sortForm && target.matches('select[name="sort"]')) {
      submitJobsForm(sortForm);
    }
  });

  document.addEventListener('input', function (event) {
    const target = event.target;
    if (!target || !target.closest) return;
    const searchForm = target.closest('[data-jobs-search]');
    if (!searchForm || (target.id !== 'jobs-keyword' && target.id !== 'jobs-location')) return;

    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(function () {
      submitJobsForm(searchForm);
    }, 450);
  });

  document.addEventListener('click', function (event) {
    const target = event.target;
    if (!target || !target.closest) return;

    const trigger = target.closest('.jobs-filter-trigger');
    if (trigger) {
      event.preventDefault();
      if (drawerOpen) closeDrawer(true);
      else openDrawer();
      return;
    }

    if (target.closest('[data-filter-close]') || target.closest('[data-filter-scrim]')) {
      event.preventDefault();
      closeDrawer(true);
      return;
    }

    if (target.closest('[data-filter-apply]')) {
      event.preventDefault();
      const drawerForm = document.querySelector('[data-filter-drawer-body] [data-jobs-filters]');
      if (drawerForm) {
        closeDrawer(false);
        submitJobsForm(drawerForm);
      }
      return;
    }

    const jobsNavLink = target.closest(
      '.jobs-sidebar__clear, .active-filters a, .pagination a, .jobs-empty a, .filter-drawer__foot a'
    );
    if (!jobsNavLink) return;

    const url = new URL(jobsNavLink.href, window.location.href);
    if (url.origin !== window.location.origin || !url.pathname.endsWith('/jobs.php')) return;

    event.preventDefault();
    if (drawerOpen) closeDrawer(false);
    loadJobs(url, true);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && drawerOpen) {
      closeDrawer(true);
      return;
    }

    if (event.key !== 'Tab' || !drawerOpen) return;
    const drawer = document.querySelector('[data-filter-drawer]');
    if (!drawer) return;

    const focusables = drawer.querySelectorAll(
      'button, [href], input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );
    if (!focusables.length) return;

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  });

  window.addEventListener('popstate', function () {
    if (!window.location.pathname.endsWith('/jobs.php')) return;
    loadJobs(new URL(window.location.href), false);
  });
})();
