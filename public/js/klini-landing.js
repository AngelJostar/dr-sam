(() => {
  'use strict';
  const dialog = document.getElementById('login-dialog');
  const menu = document.querySelector('.mobile-menu');
  const nav = document.getElementById('main-nav');
  let trigger = null;
  function closeMenu() {
    nav.classList.remove('open');
    menu.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-label', 'Abrir menú');
  }
  const signupDialog = document.getElementById('signup-dialog');
  function openDialog(target, button) {
    if (dialog.open) dialog.close();
    if (signupDialog.open) signupDialog.close();
    trigger = button;
    closeMenu();
    target.showModal();
    document.body.classList.add('modal-open');
  }
  document.querySelectorAll('[data-login]').forEach(button => button.addEventListener('click', () => openDialog(dialog, button)));
  document.querySelectorAll('[data-signup]').forEach(button => button.addEventListener('click', () => openDialog(signupDialog, button)));
  [dialog, signupDialog].forEach(target => {
    target.querySelector('.dialog-close').addEventListener('click', () => target.close());
    target.addEventListener('click', event => {
      const bounds = target.getBoundingClientRect();
      if (event.target === target && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) target.close();
    });
    target.addEventListener('close', () => {
      if (!dialog.open && !signupDialog.open) { document.body.classList.remove('modal-open'); trigger?.focus(); }
    });
  });
  const password = document.getElementById('klini-password');
  document.getElementById('toggle-login-password').addEventListener('click', event => {
    const show = password.type === 'password';
    password.type = show ? 'text' : 'password';
    event.currentTarget.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
    event.currentTarget.setAttribute('aria-pressed', String(show));
  });
  document.getElementById('forgot-login-password').addEventListener('click', () => { document.getElementById('password-help').hidden = false; });
  if (dialog.querySelector('[role="alert"]')) openDialog(dialog, document.querySelector('[data-login]'));
  menu.addEventListener('click', () => {
    const open = nav.classList.toggle('open');
    menu.setAttribute('aria-expanded', String(open));
    menu.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
  });
  nav.querySelectorAll('a').forEach(link => link.addEventListener('click', closeMenu));
  document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
  const tabs = [...document.querySelectorAll('[data-feature]')];
  function activateTab(tab, moveFocus = false) {
    tabs.forEach(item => {
      const active = item === tab;
      item.classList.toggle('active', active);
      item.setAttribute('aria-selected', String(active));
      item.tabIndex = active ? 0 : -1;
      document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
    });
    if (moveFocus) tab.focus();
  }
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activateTab(tab));
    tab.addEventListener('keydown', event => {
      let next;
      if (event.key === 'ArrowDown' || event.key === 'ArrowRight') next = (index + 1) % tabs.length;
      else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
      else if (event.key === 'Home') next = 0;
      else if (event.key === 'End') next = tabs.length - 1;
      if (next !== undefined) { event.preventDefault(); activateTab(tabs[next], true); }
    });
  });

  // The wellness preview is ephemeral: no storage, network or device access.
  const demoToggle = document.getElementById('demo-record-toggle');
  const demoFields = document.getElementById('demo-record-fields');
  const demoHabit = document.getElementById('demo-habit');
  const demoValue = document.getElementById('demo-value');
  const demoStatus = document.getElementById('demo-status');
  const habitConfig = {
    activity: { label: 'Minutos', max: 1440, step: 1, target: 'metric-activity' },
    water: { label: 'Litros', max: 20, step: 0.1, target: 'metric-water' },
    sleep: { label: 'Horas', max: 24, step: 0.1, target: 'metric-sleep' }
  };
  function updateDemoField() {
    const config = habitConfig[demoHabit.value];
    document.getElementById('demo-value-label').textContent = config.label;
    demoValue.max = String(config.max);
    demoValue.step = String(config.step);
    demoValue.value = document.getElementById(config.target).textContent;
  }
  demoToggle.addEventListener('click', () => {
    const open = demoFields.hidden;
    demoFields.hidden = !open;
    demoToggle.setAttribute('aria-expanded', String(open));
    if (open) { updateDemoField(); demoHabit.focus(); }
  });
  demoHabit.addEventListener('change', updateDemoField);
  document.getElementById('demo-save').addEventListener('click', () => {
    if (!demoValue.reportValidity()) return;
    const value = Number(demoValue.value);
    if (!Number.isFinite(value)) return;
    document.getElementById(habitConfig[demoHabit.value].target).textContent = String(value);
    demoStatus.textContent = 'Ejemplo actualizado. Este registro se reinicia al recargar la página.';
    demoStatus.hidden = false;
    demoFields.hidden = true;
    demoToggle.setAttribute('aria-expanded', 'false');
    demoToggle.focus();
  });

  const tabLayout = window.matchMedia('(max-width: 850px)');
  const updateOrientation = () => document.querySelector('[role="tablist"]').setAttribute('aria-orientation', tabLayout.matches ? 'horizontal' : 'vertical');
  updateOrientation();
  tabLayout.addEventListener('change', updateOrientation);
})();
