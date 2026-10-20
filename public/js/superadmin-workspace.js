(() => {
  const feedback = document.querySelector('[data-admin-feedback-dialog]');
  if (feedback) {
    feedback.querySelector('[data-admin-feedback-close]').addEventListener('click', () => feedback.close());
    feedback.addEventListener('close', () => {
      document.querySelector('.superadmin-native-workspace')?.focus({preventScroll: true});
    });
    feedback.showModal();
  }
  const menu = document.querySelector('.superadmin-native-menu');
  if (!menu) return;
  const desktop = matchMedia('(min-width: 641px)');
  const key = 'klini-superadmin-menu-scroll';
  if (desktop.matches) {
    try { menu.scrollTop = Number(sessionStorage.getItem(key)) || 0; } catch (_) {}
    const active = menu.querySelector('.is-active');
    if (active) {
      const row = active.getBoundingClientRect();
      const viewport = menu.getBoundingClientRect();
      const scale = Number(getComputedStyle(document.body).zoom) || 1;
      if (row.top < viewport.top) menu.scrollTop -= (viewport.top - row.top) / scale;
      if (row.bottom > viewport.bottom) menu.scrollTop += (row.bottom - viewport.bottom) / scale;
    }
  }
  const rememberMenu = () => {
    if (!desktop.matches) return;
    try { sessionStorage.setItem(key, String(menu.scrollTop)); } catch (_) {}
  };
  menu.addEventListener('scroll', rememberMenu, {passive: true});
  addEventListener('pagehide', rememberMenu);
  if (!('startViewTransition' in document)) document.documentElement.classList.add('superadmin-motion-fallback');

  const workspace = document.querySelector('.superadmin-native-workspace');
  workspace?.querySelectorAll('[data-institution-delete]').forEach(form => {
    form.addEventListener('submit', event => {
      if (!window.confirm(form.dataset.confirmMessage)) event.preventDefault();
    });
  });
  workspace?.querySelectorAll('[data-institution-row]').forEach(row => {
    row.addEventListener('click', event => {
      if (event.button !== 0 || window.getSelection()?.type === 'Range') return;
      const control = event.target.closest('a, button, input, select, textarea, label, form');
      if (control && !control.matches('[data-institution-select]')) return;
      window.location.assign(row.dataset.institutionHref);
    });
  });
  const reducedMotion = matchMedia('(prefers-reduced-motion: reduce)');
  document.querySelectorAll('[data-admin-carousel]').forEach(carousel => {
    const track = carousel.querySelector('[data-admin-carousel-track]');
    const previous = carousel.querySelector('[data-admin-carousel-prev]');
    const next = carousel.querySelector('[data-admin-carousel-next]');
    const tiles = [...track.querySelectorAll('[data-admin-filter]')];
    const updateEdges = () => {
      previous.disabled = track.scrollLeft <= 1;
      next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
    };
    const move = direction => track.scrollBy({left: direction * Math.max(78, track.clientWidth * .75), behavior: reducedMotion.matches ? 'instant' : 'smooth'});
    previous.addEventListener('click', () => move(-1));
    next.addEventListener('click', () => move(1));
    track.addEventListener('scroll', updateEdges, {passive: true});
    new ResizeObserver(updateEdges).observe(track);
    track.addEventListener('keydown', event => {
      const index = tiles.indexOf(document.activeElement);
      if (index < 0 || !['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
      event.preventDefault();
      const target = event.key === 'Home' ? 0 : event.key === 'End' ? tiles.length - 1 : Math.max(0, Math.min(tiles.length - 1, index + (event.key === 'ArrowRight' ? 1 : -1)));
      tiles[target].focus();
    });
    const select = value => {
      tiles.forEach(tile => {
        const active = tile.dataset.adminFilter === value;
        tile.classList.toggle('is-active', active);
        tile.setAttribute('aria-pressed', String(active));
      });
      carousel.querySelectorAll('[data-admin-filter-dot]').forEach(dot => dot.classList.toggle('is-active', dot.dataset.adminFilterDot === value));
      workspace.querySelectorAll('[data-admin-filter-panel]').forEach(panel => {
        const show = value === 'all' || panel.dataset.adminFilterPanel === value;
        const wasHidden = panel.hidden;
        panel.hidden = !show;
        if (show && wasHidden && !reducedMotion.matches) panel.animate([{opacity: 0}, {opacity: 1}], {duration: 450, easing: 'ease'});
      });
      carousel.dispatchEvent(new CustomEvent('admin:filter', {bubbles: true, detail: {value}}));
    };
    tiles.filter(tile => tile.tagName === 'BUTTON').forEach(tile => tile.addEventListener('click', () => select(tile.dataset.adminFilter)));
    workspace.querySelectorAll('[data-admin-filter-reset]').forEach(button => button.addEventListener('click', () => {
      select('all');
      tiles[0].focus({preventScroll: true});
      workspace.scrollTo({top: 0, behavior: reducedMotion.matches ? 'instant' : 'smooth'});
    }));
    const selected = track.querySelector('.is-active');
    if (selected) track.scrollLeft = Math.max(0, selected.offsetLeft - track.offsetLeft - 3);
    updateEdges();
  });

  if (!workspace) return;
  const rail = document.createElement('div');
  rail.className = 'superadmin-bottom-scroll';
  rail.setAttribute('role', 'region');
  rail.setAttribute('aria-label', 'Desplazamiento horizontal de la tabla');
  rail.tabIndex = 0;
  rail.hidden = true;
  const spacer = document.createElement('div');
  rail.append(spacer);
  document.body.append(rail);
  let activeScroller = null;
  let frame = 0;
  const candidates = () => {
    const containers = new Set();
    workspace.querySelectorAll('table').forEach(table => {
      for (let element = table.parentElement; element && element !== workspace; element = element.parentElement) {
        if (['auto', 'scroll'].includes(getComputedStyle(element).overflowX)) {
          containers.add(element);
          break;
        }
      }
    });
    return [...containers];
  };
  const updateRail = () => {
    frame = 0;
    const bounds = workspace.getBoundingClientRect();
    const bottom = Math.min(innerHeight - 18, bounds.bottom);
    const visible = candidates().filter(element => {
      const rect = element.getBoundingClientRect();
      return rect.width > 0 && element.scrollWidth > element.clientWidth + 1 && rect.bottom > bounds.top && rect.top < bottom;
    });
    activeScroller = visible.sort((a, b) => {
      const area = element => { const r = element.getBoundingClientRect(); return Math.min(r.bottom, bottom) - Math.max(r.top, bounds.top); };
      return area(b) - area(a);
    })[0] || null;
    rail.hidden = !activeScroller;
    workspace.classList.toggle('has-bottom-scroll', !!activeScroller);
    if (!activeScroller) return;
    const scale = Number(getComputedStyle(document.body).zoom) || 1;
    rail.style.left = `${bounds.left / scale}px`;
    rail.style.width = `${workspace.clientWidth}px`;
    // Match the table's scroll range even when its container is narrower than the workspace.
    spacer.style.width = `${rail.clientWidth + activeScroller.scrollWidth - activeScroller.clientWidth}px`;
    rail.scrollLeft = activeScroller.scrollLeft;
  };
  const scheduleRail = () => { if (!frame) frame = requestAnimationFrame(updateRail); };
  rail.addEventListener('scroll', () => {
    if (activeScroller && Math.abs(activeScroller.scrollLeft - rail.scrollLeft) > 1) activeScroller.scrollLeft = rail.scrollLeft;
  }, {passive: true});
  workspace.addEventListener('scroll', event => {
    if (event.target === activeScroller) rail.scrollLeft = activeScroller.scrollLeft;
    else scheduleRail();
  }, {capture: true, passive: true});
  addEventListener('resize', scheduleRail);
  addEventListener('scroll', scheduleRail, {passive: true});
  workspace.addEventListener('admin:filter', scheduleRail);
  new ResizeObserver(scheduleRail).observe(workspace);
  candidates().forEach(element => new ResizeObserver(scheduleRail).observe(element));
  workspace.querySelectorAll('table').forEach(element => new ResizeObserver(scheduleRail).observe(element));
  new MutationObserver(scheduleRail).observe(workspace, {subtree: true, childList: true, attributes: true, attributeFilter: ['hidden']});
  scheduleRail();
})();
