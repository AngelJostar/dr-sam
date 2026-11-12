(() => {
  const dialog = document.getElementById('new-distributor-dialog');
  if (!dialog) return;

  const workspace = document.querySelector('.pharmacy-store-admin');
  const triggers = workspace.querySelectorAll('[data-new-distributor], [data-admin-filter="create"]');
  let opener;
  let previousOverflow;
  let position;

  const open = trigger => {
    if (dialog.open) return;
    opener = trigger;
    position = {top: workspace.scrollTop, left: workspace.scrollLeft};
    previousOverflow = workspace.style.overflow;
    workspace.style.overflow = 'hidden';
    dialog.showModal();
    workspace.scrollTo({...position, behavior: 'instant'});
    dialog.querySelector('h2').focus({preventScroll: true});
  };

  triggers.forEach(trigger => {
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-controls', dialog.id);
    trigger.addEventListener('click', event => {
      event.preventDefault();
      open(trigger);
    });
  });

  dialog.querySelectorAll('[data-close-distributor]').forEach(button => {
    button.addEventListener('click', () => dialog.close());
  });
  dialog.addEventListener('close', () => {
    workspace.style.overflow = previousOverflow;
    workspace.scrollTo({...position, behavior: 'instant'});
    opener?.focus({preventScroll: true});
  });

  if (dialog.dataset.openOnLoad === 'true') open(workspace.querySelector('[data-new-distributor]'));
})();
