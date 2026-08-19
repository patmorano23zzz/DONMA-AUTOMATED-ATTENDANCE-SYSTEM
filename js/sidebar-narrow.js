/**
 * sidebar-narrow.js
 * On medium screens (< 1200px) where CoreUI uses mobile-overlay mode,
 * intercept the sidebar toggle and switch it to a proper icon-only narrow mode.
 */
(function () {
  'use strict';

  // Breakpoint below which CoreUI treats sidebar as mobile overlay
  var NARROW_BREAKPOINT = 1200;

  function isMediumScreen() {
    return window.innerWidth >= 576 && window.innerWidth < NARROW_BREAKPOINT;
  }

  function hideLabels(sidebar) {
    sidebar.querySelectorAll('.nav-label, .nav-badge, .nav-title').forEach(function (el) {
      el.style.setProperty('display', 'none', 'important');
    });
    sidebar.querySelectorAll('.nav-link').forEach(function (el) {
      el.style.setProperty('justify-content', 'center', 'important');
      el.style.setProperty('padding-left', '0', 'important');
      el.style.setProperty('padding-right', '0', 'important');
      el.style.setProperty('overflow', 'hidden', 'important');
    });
    var brandFull = sidebar.querySelector('.sidebar-brand-full');
    var brandNarrow = sidebar.querySelector('.sidebar-brand-narrow');
    if (brandFull)   brandFull.style.setProperty('display', 'none', 'important');
    if (brandNarrow) brandNarrow.style.setProperty('display', 'block', 'important');
  }

  function showLabels(sidebar) {
    sidebar.querySelectorAll('.nav-label, .nav-badge, .nav-title').forEach(function (el) {
      el.style.removeProperty('display');
    });
    sidebar.querySelectorAll('.nav-link').forEach(function (el) {
      el.style.removeProperty('justify-content');
      el.style.removeProperty('padding-left');
      el.style.removeProperty('padding-right');
      el.style.removeProperty('overflow');
    });
    var brandFull = sidebar.querySelector('.sidebar-brand-full');
    var brandNarrow = sidebar.querySelector('.sidebar-brand-narrow');
    if (brandFull)   brandFull.style.removeProperty('display');
    if (brandNarrow) brandNarrow.style.removeProperty('display');
  }

  function applyNarrow(sidebar) {
    sidebar.classList.remove('show', 'hide');
    sidebar.classList.add('sidebar-narrow');
    sidebar.style.setProperty('display', 'flex', 'important');
    sidebar.style.setProperty('position', 'fixed', 'important');
    sidebar.style.setProperty('width', '4rem', 'important');
    sidebar.style.setProperty('flex', '0 0 4rem', 'important');
    hideLabels(sidebar);
  }

  function init() {
    var sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    if (isMediumScreen()) {
      applyNarrow(sidebar);

      // Re-apply on window resize
      window.addEventListener('resize', function () {
        if (isMediumScreen()) {
          applyNarrow(sidebar);
        } else {
          // On large screens, restore normal sidebar
          sidebar.classList.remove('sidebar-narrow');
          sidebar.style.removeProperty('display');
          sidebar.style.removeProperty('position');
          sidebar.style.removeProperty('width');
          sidebar.style.removeProperty('flex');
          showLabels(sidebar);
        }
      });

      // Override topbar hamburger toggle — prevent CoreUI from showing full overlay
      var topbarToggle = document.querySelector('.header-toggler');
      if (topbarToggle) {
        topbarToggle.addEventListener('click', function (e) {
          if (isMediumScreen()) {
            e.stopImmediatePropagation();
            // Toggle between narrow and hidden
            if (sidebar.style.display === 'none' || sidebar.classList.contains('hide')) {
              applyNarrow(sidebar);
            } else {
              sidebar.style.setProperty('display', 'none', 'important');
            }
          }
        }, true); // capture phase — fires before CoreUI
      }
    }

    // Always: watch class changes and keep narrow state on medium screens
    var observer = new MutationObserver(function () {
      if (isMediumScreen()) {
        // If CoreUI added 'show' (mobile overlay), override back to narrow
        if (sidebar.classList.contains('show') && !sidebar.classList.contains('sidebar-narrow')) {
          applyNarrow(sidebar);
        }
      }
    });
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class', 'style'] });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
