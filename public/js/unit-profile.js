(() => {
  'use strict';
  const carousel = document.querySelector('[data-unit-profile-carousel]');
  const root = document.querySelector('[data-unit-profile]');
  if (!carousel || !root) return;
  const track = carousel.querySelector('.unit-service-carousel');
  const carouselButtons = [...carousel.querySelectorAll('[data-profile-section]')];
  const buttons = [...carouselButtons, ...document.querySelectorAll('[data-unit-profile-navigation] [data-profile-section]')];
  const fields = [...root.querySelectorAll('[data-profile-panel]')];
  const editor = root.querySelector('[data-profile-editor]');
  const title = editor.querySelector('h2');
  const description = editor.querySelector('header p');
  const descriptions = {
    general: 'Nombre público e información de la unidad',
    image: 'Imagen institucional y nota para papelería',
    services: 'Servicios disponibles en la unidad',
    news: 'Noticias y novedades de la unidad',
    social: 'Información para redes sociales',
  };
  const activate = section => {
    const current = carouselButtons.find(button => button.dataset.profileSection === section);
    if (!current) return;
    buttons.forEach(button => {
      const active = button.dataset.profileSection === section;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-pressed', String(active));
    });
    fields.forEach(field => { field.hidden = field.dataset.profilePanel !== section; });
    editor.hidden = section === 'preview';
    root.classList.toggle('is-preview-only', section === 'preview');
    title.textContent = section === 'general' ? 'Perfil de la unidad' : current.querySelector('strong').textContent;
    description.textContent = descriptions[section] || '';
  };
  buttons.forEach(button => button.addEventListener('click', () => activate(button.dataset.profileSection)));
  const scroll = direction => track.scrollBy({left: direction * 320, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
  carousel.querySelector('[data-profile-prev]').addEventListener('click', () => scroll(-1));
  carousel.querySelector('[data-profile-next]').addEventListener('click', () => scroll(1));
  editor.querySelector('form').addEventListener('invalid', event => {
    const field = event.target.closest('[data-profile-panel]');
    if (field) activate(field.dataset.profilePanel);
  }, true);
  activate('general');
})();
