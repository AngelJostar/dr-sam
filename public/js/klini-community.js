/* Laravel host for the supplied Klini community demo. It never sends messages externally. */
(() => {
  'use strict';
  let timer;
  window.showToast = message => {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    clearTimeout(timer);
    timer = setTimeout(() => toast.classList.remove('show'), 3500);
  };
  const view = document.getElementById('wellnessView');
  view.addEventListener('click', event => window.handlePatientWellnessAction?.(event));
  view.addEventListener('submit', event => window.handlePatientWellnessSubmit?.(event));
  view.addEventListener('change', event => window.handlePatientWellnessChange?.(event));
  view.addEventListener('input', event => window.handlePatientWellnessInput?.(event));
  if (document.body.dataset.communityMode === 'admin') window.openCommunityAdminPanel?.('community-yoga-mente');
  else window.openPatientWellnessSection?.('communities');
})();
