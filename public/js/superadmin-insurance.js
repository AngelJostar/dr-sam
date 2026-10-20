(() => {
  const root = document.querySelector('[data-insurance-plan-admin]');
  if (!root) return;
  const preview = document.querySelector('[data-plan-preview-dialog]');
  root.querySelector('[data-plan-preview]').addEventListener('click', () => preview.showModal());
  preview.querySelector('[data-plan-preview-close]').addEventListener('click', () => preview.close());
})();
