(function () {
  'use strict';

  var sidebar = document.querySelector('.jobs-sidebar');
  var resultsList = document.querySelector('.jobs-list');
  var countEl = document.getElementById('results-count');
  var keywordInput = document.getElementById('jobs-keyword');
  var locationInput = document.getElementById('jobs-location');
  var sortSelect = document.querySelector('.jobs-sort select');
  var chipsWrap = document.querySelector('[data-active-filters]');
  var chipsList = chipsWrap ? chipsWrap.querySelector('ul') : null;
  var clearAllBtn = document.querySelector('[data-clear-all]');
  var sidebarClear = document.querySelector('.jobs-sidebar__clear');
  var emptyState = document.querySelector('.jobs-empty');
  var emptyClear = emptyState ? emptyState.querySelector('button') : null;
  var trigger = document.querySelector('.jobs-filter-trigger');
  var badge = trigger ? trigger.querySelector('.jobs-filter-trigger__badge') : null;
  var drawer = document.querySelector('[data-filter-drawer]');
  var drawerBody = document.querySelector('[data-filter-drawer-body]');
  var drawerScrim = document.querySelector('[data-filter-scrim]');
  var drawerClose = document.querySelector('[data-filter-close]');
  var drawerReset = document.querySelector('[data-filter-reset]');
  var drawerApply = document.querySelector('[data-filter-apply]');
  var prevBtn = document.querySelector('[data-page-prev]');
  var nextBtn = document.querySelector('[data-page-next]');
  var pageNums = document.querySelector('[data-page-nums]');
  var pageMeta = document.querySelector('.pagination__meta');
  var paginationNav = document.querySelector('.pagination');

  var PAGE_SIZE = 5;
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var desktopMedia = window.matchMedia('(min-width: 1025px)');
  var sortOrder = 'newest';
  var currentPage = 1;
  var cards = [];
  var articles = resultsList ? Array.prototype.slice.call(resultsList.children) : [];
  var drawerOpen = false;

  function textOf(el, selector) {
    var node = el.querySelector(selector);
    return node ? node.textContent.trim() : '';
  }

  function parts(line) {
    return line.split('·').map(function (part) {
      return part.trim();
    });
  }

  function buildCards() {
    cards = articles.map(function (article, index) {
      var companyParts = parts(textOf(article, '.job-card--row__company'));
      var metaParts = parts(textOf(article, '.job-card--row__meta'));
      var footParts = parts(textOf(article, '.job-card--row__foot'));
      var locationDisplay = companyParts[1] || '';
      var mode = companyParts[2] || '';
      var locKeys = [locationDisplay];
      if (mode.toLowerCase() === 'remote') locKeys.push('Remote');
      if (locationDisplay.toLowerCase() === 'anywhere') locKeys.push('Remote');
      var salaryText = textOf(article, '.job-card--row__salary');
      var numbers = (salaryText.match(/\d[\d,]*/g) || []).map(function (num) {
        return parseInt(num.replace(/,/g, ''), 10);
      });
      var period = textOf(article, '.job-card--row__period').toLowerCase();
      var multiplier = 1;
      if (period.indexOf('hour') !== -1) multiplier = 1920;
      else if (period.indexOf('day') !== -1) multiplier = 260;
      else if (period.indexOf('week') !== -1) multiplier = 52;
      else if (period.indexOf('month') !== -1) multiplier = 12;
      var salaryAnnual = numbers.length ? numbers[numbers.length - 1] * multiplier : null;
      return {
        article: article,
        title: textOf(article, '.job-card__title'),
        company: companyParts[0] || '',
        keywords: (article.getAttribute('data-keywords') || '').split(/\s+/),
        category: footParts[0] || '',
        employment: metaParts[0] || '',
        mode: mode,
        experience: metaParts[1] || '',
        locationDisplay: locationDisplay,
        locKeys: locKeys,
        order: index,
        salaryAnnual: salaryAnnual
      };
    });
  }

  function collectChecks(group) {
    var selected = [];
    if (!sidebar) return selected;
    Array.prototype.forEach.call(sidebar.querySelectorAll('input[type=checkbox][name="' + group + '"]:checked'), function (input) {
      selected.push(input.value);
    });
    return selected;
  }

  function valuesMatch(cardValues, selectedValues) {
    if (!selectedValues.length) return true;
    for (var i = 0; i < cardValues.length; i++) {
      if (selectedValues.indexOf(cardValues[i]) !== -1) return true;
    }
    return false;
  }

  function valueMatch(actual, selectedValues) {
    return !selectedValues.length || selectedValues.indexOf(actual) !== -1;
  }

  function matches(card, filters) {
    if (filters.keyword) {
      var haystack = (card.title + ' ' + card.company + ' ' + card.category + ' ' + card.keywords.join(' ')).toLowerCase();
      var words = filters.keyword.split(/\s+/);
      var anyMatch = false;
      for (var i = 0; i < words.length; i++) {
        if (haystack.indexOf(words[i]) !== -1) anyMatch = true;
      }
      if (!anyMatch) return false;
    }
    if (filters.searchLocation) {
      var locationHaystack = (card.locationDisplay + ' ' + card.mode + ' ' + card.locKeys.join(' ')).toLowerCase();
      if (locationHaystack.indexOf(filters.searchLocation) === -1) return false;
    }
    if (!valueMatch(card.category, filters.category)) return false;
    if (!valueMatch(card.employment, filters.employment)) return false;
    if (!valueMatch(card.mode, filters.mode)) return false;
    if (!valueMatch(card.experience, filters.experience)) return false;
    return valuesMatch(card.locKeys, filters.location);
  }

  function sortCards(list) {
    if (sortOrder === 'salary-desc') {
      return list.slice().sort(function (a, b) {
        if (a.salaryAnnual == null && b.salaryAnnual == null) return a.order - b.order;
        if (a.salaryAnnual == null) return 1;
        if (b.salaryAnnual == null) return -1;
        return b.salaryAnnual - a.salaryAnnual;
      });
    }
    if (sortOrder === 'salary-asc') {
      return list.slice().sort(function (a, b) {
        if (a.salaryAnnual == null && b.salaryAnnual == null) return a.order - b.order;
        if (a.salaryAnnual == null) return 1;
        if (b.salaryAnnual == null) return -1;
        return a.salaryAnnual - b.salaryAnnual;
      });
    }
    return list.slice().sort(function (a, b) {
      return a.order - b.order;
    });
  }

  function makeChip(group, value) {
    var label = value;
    if (group === 'keyword') label = 'Keyword: ' + value;
    if (group === 'searchLocation') label = 'Location: ' + value;
    var li = document.createElement('li');
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'active-filters__chip';
    button.setAttribute('data-remove-group', group);
    button.setAttribute('data-remove-value', value);
    button.setAttribute('aria-label', 'Remove ' + label + ' filter');
    button.textContent = label;
    var x = document.createElement('span');
    x.className = 'active-filters__x';
    x.setAttribute('aria-hidden', 'true');
    x.textContent = '×';
    button.appendChild(x);
    li.appendChild(button);
    return li;
  }

  function renderChips(filters) {
    var hasAny = filters.category.length || filters.employment.length || filters.mode.length ||
      filters.experience.length || filters.location.length || !!filters.keyword || !!filters.searchLocation;
    if (!chipsWrap || !chipsList) return;
    chipsWrap.hidden = !hasAny;
    chipsList.innerHTML = '';
    [
      { name: 'category', values: filters.category },
      { name: 'employment', values: filters.employment },
      { name: 'mode', values: filters.mode },
      { name: 'experience', values: filters.experience },
      { name: 'location', values: filters.location }
    ].forEach(function (group) {
      group.values.forEach(function (value) {
        chipsList.appendChild(makeChip(group.name, value));
      });
    });
    if (filters.keyword && keywordInput) chipsList.appendChild(makeChip('keyword', keywordInput.value.trim()));
    if (filters.searchLocation && locationInput) chipsList.appendChild(makeChip('searchLocation', locationInput.value.trim()));
  }

  function renderPagination(matched) {
    if (!prevBtn || !nextBtn || !pageNums || !pageMeta || !paginationNav) return;
    var totalPages = Math.max(1, Math.ceil(matched.length / PAGE_SIZE));
    if (currentPage > totalPages) currentPage = 1;
    if (currentPage < 1) currentPage = 1;
    var start = (currentPage - 1) * PAGE_SIZE;
    var end = Math.min(start + PAGE_SIZE, matched.length);
    pageMeta.textContent = matched.length ? 'Showing ' + (start + 1) + '–' + end + ' of ' + matched.length : 'Showing 0 of 0';
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    pageNums.innerHTML = '';
    for (var i = 1; i <= totalPages; i++) {
      var num = document.createElement('button');
      num.type = 'button';
      num.className = 'pagination__num' + (i === currentPage ? ' is-current' : '');
      num.textContent = String(i);
      if (i === currentPage) num.setAttribute('aria-current', 'page');
      pageNums.appendChild(num);
    }
    paginationNav.hidden = totalPages <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    return { start: start, end: end, totalPages: totalPages, page: currentPage };
  }

  function render() {
    if (!resultsList) return;
    var filters = {
      keyword: keywordInput ? keywordInput.value.trim().toLowerCase() : '',
      searchLocation: locationInput ? locationInput.value.trim().toLowerCase() : '',
      category: collectChecks('category'),
      employment: collectChecks('employment'),
      mode: collectChecks('mode'),
      experience: collectChecks('experience'),
      location: collectChecks('location')
    };

    var matched = sortCards(cards.filter(function (card) {
      return matches(card, filters);
    }));

    var paginationInfo = renderPagination(matched);
    var pageCards = matched.slice(paginationInfo.start, paginationInfo.end);

    pageCards.forEach(function (card) {
      card.article.hidden = false;
      resultsList.appendChild(card.article);
    });
    cards.forEach(function (card) {
      if (pageCards.indexOf(card) === -1) card.article.hidden = true;
    });

    if (countEl) {
      var noun = matched.length === 1 ? 'opportunity' : 'opportunities';
      countEl.textContent = matched.length + ' ' + noun;
    }

    if (emptyState) emptyState.hidden = matched.length !== 0;

    renderChips(filters);

    if (badge) {
      var activeCount = sidebar ? sidebar.querySelectorAll('input[type=checkbox]:checked').length : 0;
      if (activeCount > 0) {
        badge.hidden = false;
        badge.textContent = String(activeCount);
      } else {
        badge.hidden = true;
      }
    }
  }

  function clearChecks(scope) {
    if (!scope) return;
    Array.prototype.forEach.call(scope.querySelectorAll('input[type=checkbox]:checked'), function (input) {
      input.checked = false;
    });
  }

  function clearAll() {
    clearChecks(sidebar);
    if (keywordInput) keywordInput.value = '';
    if (locationInput) locationInput.value = '';
    currentPage = 1;
    render();
  }

  function removeFilter(group, value) {
    if (group === 'keyword') {
      if (keywordInput) keywordInput.value = '';
      currentPage = 1;
      render();
      return;
    }
    if (group === 'searchLocation') {
      if (locationInput) locationInput.value = '';
      currentPage = 1;
      render();
      return;
    }
    Array.prototype.forEach.call(sidebar.querySelectorAll('input[type=checkbox][name="' + group + '"]'), function (input) {
      if (input.value === value) input.checked = false;
    });
    currentPage = 1;
    render();
  }

  function syncDrawerCopy() {
    if (!drawerBody || !sidebar) return;
    var source = sidebar.querySelector('.jobs-filters');
    if (!source) return;
    drawerBody.innerHTML = '';
    drawerBody.appendChild(source.cloneNode(true));
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

  function applyDrawer() {
    if (!drawerBody) return;
    var checked = drawerBody.querySelectorAll('input[type=checkbox]:checked');
    clearChecks(sidebar);
    Array.prototype.forEach.call(checked, function (input) {
      var target = sidebar.querySelector('input[type=checkbox][name="' + input.name + '"][value="' + input.value + '"]');
      if (target) target.checked = true;
    });
    currentPage = 1;
    render();
    closeDrawer();
  }

  function initFromUrl() {
    var query = window.location.search || '';
    function getParam(name) {
      var match = query.match(new RegExp('[?&]' + name + '=([^&]*)'));
      if (!match) return '';
      return decodeURIComponent(match[1].replace(/\+/g, ' '));
    }
    var kw = getParam('keyword');
    var loc = getParam('location');
    if (kw && keywordInput) keywordInput.value = kw;
    if (loc && locationInput) locationInput.value = loc;
  }

  function boot() {
    buildCards();
    initFromUrl();
    if (sortSelect) sortOrder = sortSelect.value;
    render();
  }

  if (sidebar) {
    sidebar.addEventListener('change', function (event) {
      if (event.target && event.target.type === 'checkbox') {
        currentPage = 1;
        render();
      }
    });
  }

  if (chipsWrap) {
    chipsWrap.addEventListener('click', function (event) {
      var target = event.target;
      var button = target && typeof target.closest === 'function' ? target.closest('button[data-remove-group]') : null;
      if (button) {
        removeFilter(button.getAttribute('data-remove-group'), button.getAttribute('data-remove-value'));
      }
    });
  }

  if (clearAllBtn) clearAllBtn.addEventListener('click', clearAll);
  if (sidebarClear) sidebarClear.addEventListener('click', clearAll);
  if (emptyClear) emptyClear.addEventListener('click', clearAll);

  if (keywordInput) keywordInput.addEventListener('input', function () {
    currentPage = 1;
    render();
  });
  if (locationInput) locationInput.addEventListener('input', function () {
    currentPage = 1;
    render();
  });

  if (sortSelect) sortSelect.addEventListener('change', function () {
    sortOrder = sortSelect.value;
    currentPage = 1;
    render();
  });

  if (trigger) trigger.addEventListener('click', function () {
    if (drawerOpen) closeDrawer();
    else openDrawer();
  });
  if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
  if (drawerScrim) drawerScrim.addEventListener('click', closeDrawer);
  if (drawerReset) drawerReset.addEventListener('click', function () {
    clearChecks(drawerBody);
    clearAll();
    closeDrawer();
  });
  if (drawerApply) drawerApply.addEventListener('click', applyDrawer);
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

  if (prevBtn) prevBtn.addEventListener('click', function () {
    if (currentPage > 1) {
      currentPage -= 1;
      render();
    }
  });
  if (nextBtn) nextBtn.addEventListener('click', function () {
    currentPage += 1;
    render();
  });
  if (pageNums) pageNums.addEventListener('click', function (event) {
    var target = event.target;
    var button = target && typeof target.closest === 'function' ? target.closest('button.pagination__num') : null;
    if (button && button.textContent) {
      currentPage = parseInt(button.textContent, 10);
      render();
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();