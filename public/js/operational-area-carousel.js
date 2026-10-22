(() => {
  'use strict';
  document.querySelectorAll('[data-operational-catalog-carousel]').forEach(carousel => {
    const track = carousel.querySelector('.operational-oncology-carousel-track');
    const previous = carousel.querySelector('[data-area-carousel-prev]');
    const next = carousel.querySelector('[data-area-carousel-next]');
    const refresh = () => {
      previous.disabled = track.scrollLeft <= 1;
      next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
    };
    previous.addEventListener('click', () => track.scrollBy({ left: -280, behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left: 280, behavior: 'smooth' }));
    track.addEventListener('scroll', refresh, { passive: true });
    new ResizeObserver(refresh).observe(track);
    const active = track.querySelector('.is-active');
    if (active) track.scrollLeft = Math.max(0, active.offsetLeft - track.offsetLeft - track.clientWidth + active.offsetWidth);
    refresh();
  });
})();
