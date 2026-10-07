(() => {
  'use strict';
  const dialog = document.getElementById('login-dialog');
  const feedback = document.getElementById('login-feedback');
  const form = document.getElementById('login-form');
  const password = document.getElementById('password');
  const passwordToggle = document.getElementById('password-toggle');
  const signupForm = document.getElementById('signup-form');
  const signupPassword = document.getElementById('signup-password');
  const signupConfirm = document.getElementById('signup-confirm');
  const signupFeedback = document.getElementById('signup-feedback');
  const signupPasswordToggle = document.getElementById('signup-password-toggle');
  const authSwitch = document.getElementById('auth-switch-button');
  const menu = document.querySelector('.mobile-menu');
  const nav = document.getElementById('main-nav');
  let trigger = null;
  let authMode = 'login';

  function resetForms() {
    form.reset();
    signupForm.reset();
    signupConfirm.setCustomValidity('');
    feedback.hidden = true;
    signupFeedback.hidden = true;
    [[password, passwordToggle], [signupPassword, signupPasswordToggle]].forEach(([input, button]) => {
      input.type = 'password';
      button.setAttribute('aria-label', 'Mostrar contraseña');
      button.setAttribute('aria-pressed', 'false');
    });
  }
  function setAuthMode(mode) {
    authMode = mode;
    const signup = mode === 'signup';
    resetForms();
    form.hidden = signup;
    signupForm.hidden = !signup;
    document.getElementById('login-title').textContent = signup ? 'Únete, crea tu cuenta' : 'Qué gusto verte.';
    document.getElementById('login-intro').textContent = signup ? 'Tu próxima comunidad empieza contigo.' : 'Ingresa a tu espacio de bienestar.';
    document.getElementById('auth-switch-question').textContent = signup ? '¿Ya tienes cuenta?' : '¿No tienes cuenta?';
    authSwitch.textContent = signup ? 'Ingresar a Klini' : 'Únete, crea tu cuenta';
    document.getElementById('auth-preview-note').textContent = signup ? 'Vista previa del registro. La creación de cuentas aún no está conectada.' : 'Vista previa del acceso. La validación de cuentas aún no está conectada.';
    dialog.classList.toggle('signup-mode', signup);
    if (dialog.open) focusAuth();
  }
  function focusAuth() {
    document.getElementById(authMode === 'signup' ? 'signup-role' : 'username').focus();
    dialog.scrollTop = 0;
  }

  function closeMenu() {
    nav.classList.remove('open');
    menu.setAttribute('aria-expanded', 'false');
    menu.setAttribute('aria-label', 'Abrir menú');
  }
  document.querySelectorAll('[data-login], [data-signup]').forEach(button => {
    button.addEventListener('click', () => {
      trigger = button;
      closeMenu();
      setAuthMode(button.hasAttribute('data-signup') ? 'signup' : 'login');
      dialog.showModal();
      document.body.classList.add('modal-open');
      focusAuth();
    });
  });
  document.querySelector('.dialog-close').addEventListener('click', () => dialog.close());
  dialog.addEventListener('click', event => {
    const bounds = dialog.getBoundingClientRect();
    if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
  });
  dialog.addEventListener('close', () => {
    document.body.classList.remove('modal-open');
    resetForms();
    if (trigger) trigger.focus();
  });
  authSwitch.addEventListener('click', () => setAuthMode(authMode === 'signup' ? 'login' : 'signup'));
  passwordToggle.addEventListener('click', () => {
    const showing = password.type === 'password';
    password.type = showing ? 'text' : 'password';
    passwordToggle.setAttribute('aria-label', showing ? 'Ocultar contraseña' : 'Mostrar contraseña');
    passwordToggle.setAttribute('aria-pressed', String(showing));
  });
  signupPasswordToggle.addEventListener('click', () => {
    const showing = signupPassword.type === 'password';
    signupPassword.type = showing ? 'text' : 'password';
    signupPasswordToggle.setAttribute('aria-label', showing ? 'Ocultar contraseña' : 'Mostrar contraseña');
    signupPasswordToggle.setAttribute('aria-pressed', String(showing));
  });
  function validateMatchingPasswords() {
    signupConfirm.setCustomValidity(signupConfirm.value && signupPassword.value !== signupConfirm.value ? 'Las contraseñas no coinciden.' : '');
  }
  signupPassword.addEventListener('input', validateMatchingPasswords);
  signupConfirm.addEventListener('input', validateMatchingPasswords);
  signupForm.addEventListener('submit', event => {
    event.preventDefault();
    validateMatchingPasswords();
    if (!signupForm.reportValidity()) return;
    // Preview only: no account is created and no data leaves this page.
    signupPassword.value = '';
    signupConfirm.value = '';
    signupConfirm.setCustomValidity('');
    signupFeedback.textContent = 'La creación de cuentas aún no está disponible en esta página. Falta conectar el servicio de Klini. Tu cuenta no se ha creado y tus datos no se han enviado.';
    signupFeedback.hidden = false;
  });
  function showFeedback(message) {
    feedback.textContent = message;
    feedback.hidden = false;
  }
  form.addEventListener('submit', event => {
    event.preventDefault();
    // This landing page has no account service. Credentials are never sent or persisted.
    password.value = '';
    showFeedback('El acceso a cuentas aún no está disponible en esta página. Falta conectar el servicio de Klini. Tus datos no se han enviado.');
  });
  document.getElementById('forgot-password').addEventListener('click', () => {
    showFeedback('La recuperación de contraseña estará disponible cuando se conecte el acceso de Klini. Desde esta vista previa no se envían correos.');
  });
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
