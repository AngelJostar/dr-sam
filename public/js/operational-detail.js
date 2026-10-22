(() => {
  'use strict';
  const dialog = document.getElementById('operational-detail-dialog');
  if (!dialog) return;
  const content = dialog.querySelector('[data-operational-detail-content]');
  const close = dialog.querySelector('[data-operational-detail-close]');
  let trigger;

  document.addEventListener('click', event => {
    const button = event.target.closest('[data-operational-detail-target], [data-operational-detail-url]');
    if (!button || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    const template = button.dataset.operationalDetailTarget ? document.getElementById(button.dataset.operationalDetailTarget) : null;
    if (template && !(template instanceof HTMLTemplateElement)) return;
    if (!template && !button.hasAttribute('data-operational-detail-url')) return;
    let url;
    if (!template) {
      url = new URL(button.href, location.href);
      if (url.origin !== location.origin) return;
    }
    event.preventDefault();
    trigger = button;
    const source = template || button;
    dialog.querySelector('#operational-detail-title').textContent = source.dataset.detailTitle;
    dialog.querySelector('#operational-detail-context').textContent = source.dataset.detailContext;
    dialog.classList.toggle('is-wide', !template || source.dataset.detailWide === 'true');
    content.replaceChildren();
    if (template) {
      content.append(template.content.cloneNode(true));
    } else {
      const frame = document.createElement('iframe');
      frame.title = source.dataset.detailTitle;
      frame.setAttribute('data-operational-detail-frame', '');
      frame.setAttribute('data-unit-operation-frame', '');
      frame.src = url.href;
      content.append(frame);
    }
    dialog.showModal();
    close.focus({preventScroll: true});
  });
  close.addEventListener('click', () => dialog.close());
  dialog.addEventListener('close', () => {
    content.replaceChildren();
    trigger?.focus({preventScroll: true});
  });
})();
