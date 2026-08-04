(function () {
  "use strict";

  function $(selector, root) {
    return (root || document).querySelector(selector);
  }

  function $all(selector, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(selector));
  }

  function escapeHtml(value) {
    return String(value == null ? "" : value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function money(value) {
    try {
      return new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" }).format(value);
    } catch (error) {
      return "$" + value;
    }
  }

  function icon(name) {
    var paths = {
      plus: '<path d="M12 5v14M5 12h14"/>',
      close: '<path d="M6 6l12 12M18 6 6 18"/>',
      chevron: '<path d="m9 18 6-6-6-6"/>',
      wallet: '<path d="M4 7h15a2 2 0 0 1 2 2v9H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h12v3"/><path d="M16 11h5v4h-5a2 2 0 0 1 0-4Z"/>',
      support: '<path d="M4 13v-2a8 8 0 0 1 16 0v2"/><path d="M4 13h3v6H5a2 2 0 0 1-2-2v-2a2 2 0 0 1 1-2Zm16 0h-3v6h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-1-2ZM17 19c0 1.1-2.2 2-5 2"/>',
      request: '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 3h6v3H9zM9 11h6M9 15h4"/>',
      message: '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/><path d="M8 9h8M8 13h5"/>',
      bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM9 21h6"/>',
      sync: '<path d="M20 7h-6V1M4 17h6v6"/><path d="M20 7a9 9 0 0 0-15-3M4 17a9 9 0 0 0 15 3"/>',
      user: '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
      logout: '<path d="M10 17l5-5-5-5M15 12H3M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>',
      camera: '<path d="M5 7h3l2-2h4l2 2h3a2 2 0 0 1 2 2v9H3V9a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="3"/>',
      trash: '<path d="M4 7h16M9 7V4h6v3M7 7l1 14h8l1-14M10 11v6M14 11v6"/>',
      arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>',
      check: '<path d="m5 12 4 4L19 6"/>',
      card: '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/>',
      list: '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
      info: '<circle cx="12" cy="12" r="9"/><path d="M12 10v7M12 7h.01"/>',
      calendar: '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
      cart: '<path d="M4 5h2l2 11h10l2-7H8"/><circle cx="10" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>',
      dollar: '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/>',
      up: '<path d="M12 19V5M5 12l7-7 7 7"/>',
      down: '<path d="M12 5v14M19 12l-7 7-7-7"/>'
    };
    return '<svg aria-hidden="true" viewBox="0 0 24 24">' + (paths[name] || paths.user) + '</svg>';
  }

  function initials(name) {
    return String(name || "Paciente").split(/\s+/).filter(Boolean).slice(0, 2).map(function (part) {
      return part.charAt(0);
    }).join("").toUpperCase() || "PX";
  }

  var panel = $("[data-patient-profile-panel]");
  if (!panel) return;

  var shade = $("[data-patient-profile-shade]");
  var modal = $("[data-patient-profile-modal]");
  var modalDialog = $("[data-patient-profile-dialog]");
  var modalTitle = $("[data-patient-profile-modal-title]");
  var modalEyebrow = $("[data-patient-profile-modal-eyebrow]");
  var modalBody = $("[data-patient-profile-modal-body]");
  var photoInput = $("[data-patient-profile-photo]");
  var storageKey = panel.dataset.storageKey || "drsam_patient_profile_panel";
  var logoutUrl = panel.dataset.logoutUrl || "/logout";
  var loginUrl = panel.dataset.loginUrl || "/";
  var csrfToken = panel.dataset.csrfToken || "";

  var initialState = {
    profile: {
      name: panel.dataset.profileName || "Paciente",
      userId: panel.dataset.profileId || "100000001",
      curp: panel.dataset.profileCurp || "No registrado",
      email: panel.dataset.profileEmail || "paciente@demo.drsam.local",
      phone: panel.dataset.profilePhone || "No registrado",
      photo: "",
      status: panel.dataset.profileStatus || "Activo",
      doctor: panel.dataset.profileDoctor || "Sin asignar",
      completedAt: panel.dataset.profileCompleted || "Pendiente"
    },
    wallet: {
      balance: 860,
      movements: [
        { id: "MOV-003", date: "30/07/2026", concept: "Recarga con tarjeta", amount: 500 },
        { id: "MOV-002", date: "24/07/2026", concept: "Compra en farmacia digital", amount: -270 },
        { id: "MOV-001", date: "20/07/2026", concept: "Recarga con tarjeta", amount: 630 }
      ]
    },
    subscription: {
      current: "family",
      plans: [
        { id: "basic", name: "Historial Básico", label: "Gratis", price: 0, period: "Sin costo", description: "Para empezar a guardar y consultar información médica reciente." },
        { id: "complete", name: "Historial Completo", label: "Individual", price: 20, period: "Mensuales", description: "Acceso permanente a toda tu historia clínica digital." },
        { id: "smart", name: "Salud Inteligente", label: "Con IA", price: 190, period: "Mensuales", description: "Apoyo en la organización y seguimiento de tu salud." },
        { id: "family", name: "Cuidado Familiar", label: "Multiusuario", price: 799, period: "Hasta 5 usuarios", description: "Administra la salud de tus familiares desde una misma plataforma." },
        { id: "premium", name: "Salud Premium 360", label: "Avanzado", price: 950, period: "Plan avanzado", description: "Una experiencia médica digital completa y personalizada." }
      ]
    },
    requests: [
      { id: "REQ-001", source: "Hospital General Norte", type: "Unidad / área operativa", date: "01/08/2026", detail: "Solicita sincronizar resultados de laboratorio con tu historial clínico.", status: "pending" },
      { id: "REQ-002", source: "Dra. Ana Sofía Ramírez", type: "Médico tratante", date: "29/07/2026", detail: "Solicita enlazar notas de seguimiento con tu expediente.", status: "accepted" }
    ],
    messages: [
      { id: "MSG-001", sender: "Dr. Carter Jimmy", role: "Médico tratante", time: "Hoy, 09:20 a.m.", preview: "Tu seguimiento está confirmado para el viernes.", unread: true },
      { id: "MSG-002", sender: "Andrea Suarez", role: "Asesora de seguros", time: "Ayer, 04:10 p.m.", preview: "Recibí los documentos de tu solicitud.", unread: true },
      { id: "MSG-003", sender: "Hospital General Norte", role: "Unidad médica", time: "28 jul, 11:35 a.m.", preview: "Tus resultados ya se encuentran disponibles.", unread: true }
    ],
    alerts: [
      { id: "ALT-001", title: "Resultado disponible", detail: "Ya puedes consultar tu biometría hemática.", time: "Hoy", unread: true },
      { id: "ALT-002", title: "Próxima consulta", detail: "Tienes una consulta programada en 3 días.", time: "Ayer", unread: false }
    ],
    synchronizations: [
      { id: "SYN-001", name: "Dr. Carter Jimmy", type: "Médico", since: "16/06/2026", validity: "Indefinida" },
      { id: "SYN-002", name: "Hospital General Norte - Laboratorio", type: "Unidad / área operativa", since: "08/06/2026", validity: "Indefinida" }
    ]
  };

  var state = normalizeState(loadState());

  function clone(value) {
    return JSON.parse(JSON.stringify(value));
  }

  function normalizeState(value) {
    var next = value || clone(initialState);
    if (!next.wallet) next.wallet = clone(initialState.wallet);
    if (!Array.isArray(next.wallet.movements)) next.wallet.movements = clone(initialState.wallet.movements);
    if (typeof next.wallet.balance !== "number") next.wallet.balance = initialState.wallet.balance;
    if (!next.subscription) next.subscription = clone(initialState.subscription);
    if (!Array.isArray(next.subscription.plans) || !next.subscription.plans.length) next.subscription.plans = clone(initialState.subscription.plans);
    if (!next.subscription.current || !next.subscription.plans.some(function (plan) { return plan.id === next.subscription.current; })) next.subscription.current = initialState.subscription.current;
    if (!Array.isArray(next.messages)) next.messages = clone(initialState.messages);
    next.messages = next.messages.map(function (item) {
      return normalizeMessage(item);
    });
    return next;
  }

  function normalizeMessage(item) {
    if (!Array.isArray(item.thread) || !item.thread.length) {
      item.thread = [
        { id: item.id + "-in", from: "contact", sender: item.sender, text: item.preview, time: item.time }
      ];
      if (item.id === "MSG-001") {
        item.thread.push({ id: item.id + "-ack", from: "patient", sender: "Tú", text: "Gracias. He recibido la información.", time: "Ahora" });
      }
    }
    return item;
  }

  function loadState() {
    try {
      var saved = JSON.parse(localStorage.getItem(storageKey) || "null");
      if (saved && saved.profile) {
        saved.profile.name = initialState.profile.name;
        saved.profile.userId = initialState.profile.userId;
        saved.profile.status = initialState.profile.status;
        saved.profile.doctor = initialState.profile.doctor;
        saved.profile.completedAt = initialState.profile.completedAt;
        return saved;
      }
    } catch (error) {}
    return clone(initialState);
  }

  function messageTime() {
    try {
      return new Intl.DateTimeFormat("es-MX", { hour: "2-digit", minute: "2-digit", hour12: true }).format(new Date());
    } catch (error) {
      return "Ahora";
    }
  }

  function renderMessageBubble(item, message) {
    var isUser = message.from === "patient";
    return '<div class="patient-profile-chat-bubble ' + (isUser ? "is-user" : "") + '"><small>' + escapeHtml(isUser ? "Tú" : (message.sender || item.sender)) + '</small><p>' + escapeHtml(message.text) + '</p><span>' + escapeHtml(message.time || "Ahora") + '</span></div>';
  }

  function focusConversation() {
    window.setTimeout(function () {
      var chat = $("[data-profile-chat-log]", modalBody);
      var input = $("[data-profile-message-input]", modalBody);
      if (chat) chat.scrollTop = chat.scrollHeight;
      if (input) input.focus();
    }, 30);
  }

  function saveState() {
    try {
      localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (error) {}
    updateUI();
  }

  function emit(action, payload) {
    window.dispatchEvent(new CustomEvent("drsam:patient-profile", {
      detail: { action: action, payload: payload || {}, state: clone(state) }
    }));
  }

  function setAvatar(element) {
    if (!element) return;
    if (state.profile.photo) {
      element.innerHTML = '<img src="' + state.profile.photo + '" alt="Foto de perfil">';
    } else {
      element.textContent = initials(state.profile.name);
    }
  }

  function updateUI() {
    $all("[data-profile-panel-name]").forEach(function (element) { element.textContent = state.profile.name; });
    $all("[data-profile-panel-id]").forEach(function (element) { element.textContent = state.profile.userId; });
    $all("[data-profile-panel-avatar]").forEach(setAvatar);
    setCount("requests", state.requests.filter(function (item) { return item.status === "pending"; }).length, "Sin solicitudes pendientes", "Solicitud pendiente");
    setCount("messages", state.messages.filter(function (item) { return item.unread; }).length, "Sin mensajes pendientes", "Médicos, seguros y unidades");
    setCount("alerts", state.alerts.filter(function (item) { return item.unread; }).length, "Sin alertas pendientes", "Recordatorios y avisos importantes");
    setCount("sync", state.synchronizations.length, "Sin enlaces activos", state.synchronizations.length + " enlaces activos");
  }

  function setCount(name, count, emptyText, activeText) {
    var badge = $('[data-profile-count="' + name + '"]');
    var label = $('[data-profile-count-label="' + name + '"]');
    if (badge) {
      badge.textContent = count;
      badge.hidden = count === 0;
    }
    if (label) label.textContent = count ? activeText : emptyText;
  }

  function openPanel() {
    panel.hidden = false;
    shade.hidden = false;
    panel.setAttribute("aria-hidden", "false");
    document.body.classList.add("patient-profile-open");
    updateUI();
  }

  function closePanel() {
    panel.hidden = true;
    shade.hidden = true;
    panel.setAttribute("aria-hidden", "true");
    document.body.classList.remove("patient-profile-open");
  }

  function openModal(title, eyebrow, html, wide, variant) {
    modalTitle.textContent = title;
    modalEyebrow.textContent = eyebrow || "PERFIL DEL PACIENTE";
    modalBody.innerHTML = html;
    modalDialog.classList.toggle("is-wide", !!wide);
    modalDialog.classList.toggle("is-wallet", variant === "wallet");
    modal.hidden = false;
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("patient-profile-modal-open");
    modalBody.querySelectorAll("[data-profile-panel-avatar]").forEach(setAvatar);
    var firstControl = modal.querySelector("button, input, textarea, select");
    if (firstControl) window.setTimeout(function () { firstControl.focus(); }, 30);
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute("aria-hidden", "true");
    modalBody.innerHTML = "";
    modalDialog.classList.remove("is-wide", "is-wallet");
    document.body.classList.remove("patient-profile-modal-open");
  }

  function emptyState(iconName, title, description) {
    return '<div class="patient-profile-empty">' + icon(iconName) + '<h3>' + escapeHtml(title) + '</h3><p>' + escapeHtml(description) + '</p></div>';
  }

  function renderFinance() {
    var totalCredits = state.wallet.movements.reduce(function (sum, item) { return sum + Math.max(0, Number(item.amount) || 0); }, 0);
    var totalDebits = state.wallet.movements.reduce(function (sum, item) { return sum + Math.abs(Math.min(0, Number(item.amount) || 0)); }, 0);
    var plans = state.subscription && Array.isArray(state.subscription.plans) ? state.subscription.plans : initialState.subscription.plans;
    var currentPlan = plans.find(function (plan) { return plan.id === state.subscription.current; }) || plans[0];
    var subscriptionCards = plans.map(function (plan) {
      var isCurrent = plan.id === currentPlan.id;
      var price = Number(plan.price) || 0;
      return '<article class="patient-wallet-plan ' + (isCurrent ? "is-current" : "") + '">' +
        '<div class="patient-wallet-plan-head"><span>' + icon(isCurrent ? "check" : "card") + '</span><div><small>' + escapeHtml(plan.label || "Plan") + '</small><h4>' + escapeHtml(plan.name) + '</h4></div></div>' +
        '<strong>' + (price > 0 ? money(price) : "Gratis") + '</strong><em>' + escapeHtml(plan.period || "") + '</em><p>' + escapeHtml(plan.description || "") + '</p>' +
        '<button type="button" class="patient-profile-button ' + (isCurrent ? "is-ghost" : "is-primary") + '" data-profile-plan="' + escapeHtml(plan.id) + '"' + (isCurrent ? " disabled" : "") + '>' + (isCurrent ? "Plan activo" : "Elegir plan") + '</button>' +
      '</article>';
    }).join("");
    var movements = state.wallet.movements.map(function (item) {
      var amount = Number(item.amount) || 0;
      var isCredit = amount >= 0;
      return '<article class="patient-wallet-movement ' + (isCredit ? "is-credit" : "is-debit") + '">' +
        '<span class="patient-wallet-movement-icon">' + icon(isCredit ? "wallet" : "cart") + '</span>' +
        '<div class="patient-wallet-movement-copy"><strong>' + escapeHtml(item.concept) + '</strong><small>' + escapeHtml(item.date) + ' · ' + escapeHtml(item.id) + '</small></div>' +
        '<span class="patient-wallet-status ' + (isCredit ? "is-ok" : "is-debit") + '">' + (isCredit ? "Completado" : "Débito") + '</span>' +
        '<strong class="patient-wallet-amount ' + (isCredit ? "is-credit" : "is-debit") + '">' + (isCredit ? "+ " : "- ") + money(Math.abs(amount)) + '</strong>' +
        '<span class="patient-wallet-arrow">' + icon("chevron") + '</span>' +
      '</article>';
    }).join("");
    openModal("Billetera", "PAGOS Y RECARGAS", '<div class="patient-wallet-redesign">' +
      '<section class="patient-wallet-hero"><span class="patient-wallet-hero-icon">' + icon("wallet") + '</span><div><small>Saldo disponible</small><strong>' + money(state.wallet.balance) + '</strong><p>Administra tus pagos y recargas desde un solo lugar.</p></div><span class="patient-wallet-orb is-card">' + icon("card") + '</span><span class="patient-wallet-orb is-money">' + icon("dollar") + '</span></section>' +
      '<section class="patient-wallet-recharge"><div class="patient-wallet-recharge-main"><small>BILLETERA</small><h3>Recargar saldo</h3><form class="patient-wallet-recharge-form" data-profile-recharge-form><label><span>Monto a recargar</span><div class="patient-wallet-amount-field"><b>$</b><input name="amount" type="number" min="10" step="10" value="200" required></div></label><button class="patient-profile-button is-primary" type="submit">' + icon("card") + ' Recargar</button></form></div><aside class="patient-wallet-help"><span>' + icon("info") + '</span><div><h4>¿Cómo funciona?</h4><p>Ingresa el monto y recárgalo con tu método de pago preferido.</p></div></aside></section>' +
      '<section class="patient-wallet-subscriptions"><div class="patient-wallet-section-head"><div><span class="patient-wallet-section-icon">' + icon("check") + '</span><h3>Suscripciones</h3></div><span class="patient-wallet-current-plan">Plan actual: ' + escapeHtml(currentPlan.name) + '</span></div><div class="patient-wallet-plan-grid">' + subscriptionCards + '</div></section>' +
      '<section class="patient-wallet-movements"><div class="patient-wallet-section-head"><div><span class="patient-wallet-section-icon">' + icon("list") + '</span><h3>Movimientos recientes</h3></div><button type="button" class="patient-wallet-filter">' + icon("calendar") + ' Filtrar por fecha ' + icon("chevron") + '</button></div><div class="patient-wallet-movement-list">' + movements + '</div><div class="patient-wallet-summary"><div><span class="patient-wallet-summary-icon is-credit">' + icon("up") + '</span><small>Total recargas</small><strong>' + money(totalCredits) + '</strong></div><div><span class="patient-wallet-summary-icon is-debit">' + icon("down") + '</span><small>Total consumos</small><strong>' + money(totalDebits) + '</strong></div><div><span class="patient-wallet-summary-icon is-count">' + icon("dollar") + '</span><small>Movimientos</small><strong>' + state.wallet.movements.length + '</strong></div></div></section>' +
    '</div>', true, "wallet");
  }

  function renderRequests() {
    var pending = state.requests.filter(function (item) { return item.status === "pending"; });
    var history = state.requests.filter(function (item) { return item.status !== "pending"; });
    var pendingHtml = pending.length ? pending.map(function (item) {
      return '<article class="patient-profile-request-card"><div class="patient-profile-row-icon">' + icon("request") + '</div><div><small>' + escapeHtml(item.type) + ' · ' + escapeHtml(item.date) + '</small><h3>' + escapeHtml(item.source) + '</h3><p>' + escapeHtml(item.detail) + '</p><div class="patient-profile-actions"><button class="patient-profile-button is-primary" data-profile-request-action="accept" data-id="' + item.id + '">Aceptar</button><button class="patient-profile-button is-danger" data-profile-request-action="reject" data-id="' + item.id + '">Rechazar</button></div></div></article>';
    }).join("") : emptyState("request", "Sin solicitudes pendientes", "Las nuevas solicitudes aparecerán aquí.");
    var historyHtml = history.map(function (item) {
      return '<li class="patient-profile-list-item"><span><strong>' + escapeHtml(item.source) + '</strong><small>' + escapeHtml(item.type) + ' · ' + escapeHtml(item.date) + '</small></span><span class="patient-profile-status is-ok">Autorizada</span></li>';
    }).join("");
    openModal("Solicitudes", "SOPORTE MÉDICO", '<div class="patient-profile-stack"><section class="patient-profile-section-block"><div class="patient-profile-section-heading"><div><small>PENDIENTES</small><h3>Solicitudes de vinculación</h3></div><span class="patient-profile-badge">' + pending.length + '</span></div><div class="patient-profile-stack">' + pendingHtml + '</div></section>' + (history.length ? '<section class="patient-profile-section-block"><div class="patient-profile-section-heading"><h3>Actividad reciente</h3></div><ul class="patient-profile-list">' + historyHtml + '</ul></section>' : '') + '</div>');
  }

  function renderMessages() {
    var rows = state.messages.map(function (item) {
      return '<button class="patient-profile-message-row ' + (item.unread ? "is-unread" : "") + '" data-profile-message-id="' + item.id + '"><span class="patient-profile-message-avatar">' + initials(item.sender) + '</span><span><small>' + escapeHtml(item.role) + ' · ' + escapeHtml(item.time) + '</small><strong>' + escapeHtml(item.sender) + '</strong><p>' + escapeHtml(item.preview) + '</p></span><span class="patient-profile-message-dot" aria-hidden="true"></span></button>';
    }).join("");
    openModal("Mensajes", "SOPORTE MÉDICO", '<section class="patient-profile-section-block"><div class="patient-profile-section-heading"><div><small>BANDEJA</small><h3>Médicos, seguros y unidades</h3></div></div><div class="patient-profile-stack">' + rows + '</div></section>');
  }

  function renderConversation(messageId) {
    var item = state.messages.find(function (entry) { return entry.id === messageId; });
    if (!item) return;
    item = normalizeMessage(item);
    item.unread = false;
    var thread = item.thread.map(function (message) {
      return renderMessageBubble(item, message);
    }).join("");
    saveState();
    openModal(item.sender, item.role.toUpperCase(), '<div class="patient-profile-chat" data-profile-chat-log>' + thread + '</div><form class="patient-profile-composer" data-profile-message-form data-message-id="' + escapeHtml(item.id) + '" data-recipient="' + escapeHtml(item.sender) + '"><input name="message" placeholder="Escribe un mensaje" maxlength="700" autocomplete="off" data-profile-message-input required><button class="patient-profile-button is-primary" type="submit" aria-label="Enviar">' + icon("arrow") + '</button></form>');
    focusConversation();
  }

  function renderAlerts() {
    var rows = state.alerts.length ? state.alerts.map(function (item) {
      return '<article class="patient-profile-alert-row ' + (item.unread ? "is-unread" : "") + '"><div class="patient-profile-row-icon">' + icon("bell") + '</div><div><small>' + escapeHtml(item.time) + '</small><h3>' + escapeHtml(item.title) + '</h3><p>' + escapeHtml(item.detail) + '</p></div>' + (item.unread ? '<button class="patient-profile-button is-ghost" data-profile-alert-read="' + item.id + '">Marcar leída</button>' : '<span class="patient-profile-status">Leída</span>') + '</article>';
    }).join("") : emptyState("bell", "Sin alertas", "Las alertas de tu cuenta aparecerán aquí.");
    openModal("Alertas", "SOPORTE MÉDICO", '<section class="patient-profile-section-block"><div class="patient-profile-section-heading"><div><small>CENTRO DE ALERTAS</small><h3>Actividad de tu cuenta</h3></div><button class="patient-profile-button is-ghost" data-profile-read-all>Marcar todas</button></div><div class="patient-profile-stack">' + rows + '</div></section>');
  }

  function renderSynchronizations() {
    var rows = state.synchronizations.length ? state.synchronizations.map(function (item) {
      return '<article class="patient-profile-sync-card"><div class="patient-profile-row-icon">' + icon("sync") + '</div><div><small>' + escapeHtml(item.type) + '</small><h3>' + escapeHtml(item.name) + '</h3><p>Inicio: ' + escapeHtml(item.since) + ' · Vigencia: ' + escapeHtml(item.validity) + '</p></div><button class="patient-profile-button is-danger" data-profile-remove-sync="' + item.id + '">' + icon("trash") + ' Eliminar</button></article>';
    }).join("") : emptyState("sync", "Sin sincronizaciones activas", "Los enlaces con médicos, unidades y aseguradoras aparecerán aquí.");
    openModal("Sincronización", "SOPORTE MÉDICO", '<section class="patient-profile-section-block"><div class="patient-profile-section-heading"><div><small>ENLACES ACTIVOS</small><h3>Control de datos compartidos</h3></div><span class="patient-profile-badge">' + state.synchronizations.length + '</span></div><div class="patient-profile-stack">' + rows + '</div></section>');
  }

  function renderProfileInfo() {
    var p = state.profile;
    openModal("Información de perfil", "DATOS DEL PACIENTE", '<form class="patient-profile-stack" data-profile-form><div class="patient-profile-photo-editor"><button class="patient-profile-large-avatar" type="button" data-profile-change-photo data-profile-panel-avatar></button><span><strong>Foto de perfil</strong><small>Puedes cambiarla desde este panel.</small></span><button class="patient-profile-button is-ghost" type="button" data-profile-change-photo>' + icon("camera") + ' Cambiar</button></div><div class="patient-profile-field-grid"><label class="patient-profile-field"><span>Nombre completo</span><input name="name" value="' + escapeHtml(p.name) + '" readonly></label><label class="patient-profile-field"><span>ID de usuario</span><input name="userId" value="' + escapeHtml(p.userId) + '" readonly></label><label class="patient-profile-field"><span>CURP</span><input name="curp" value="' + escapeHtml(p.curp) + '"></label><label class="patient-profile-field is-locked"><span>Correo electrónico</span><input name="email" type="email" value="' + escapeHtml(p.email) + '" readonly aria-readonly="true"><small>Correo vinculado a la cuenta de la paciente.</small></label><label class="patient-profile-field"><span>Teléfono</span><input name="phone" value="' + escapeHtml(p.phone) + '"></label><label class="patient-profile-field"><span>Médico principal</span><input value="' + escapeHtml(p.doctor) + '" readonly></label></div><div class="patient-profile-identity-card"><div class="patient-profile-qr" aria-label="Código QR de identificación"></div><div><small>ID DE USUARIO</small><strong>' + escapeHtml(p.userId) + '</strong><p>Identificación digital del paciente.</p></div></div><button class="patient-profile-button is-primary" type="submit">Guardar información</button></form>');
  }

  function renderPhotoEditor() {
    openModal("Foto de perfil", "IDENTIDAD DEL PACIENTE", '<section class="patient-profile-photo-view"><button class="patient-profile-large-avatar" type="button" data-profile-change-photo data-profile-panel-avatar></button><h3>' + escapeHtml(state.profile.name) + '</h3><p>Selecciona una imagen cuadrada para obtener mejores resultados.</p><div class="patient-profile-actions" style="justify-content:center"><button class="patient-profile-button is-primary" data-profile-change-photo>' + icon("camera") + ' Cambiar foto</button>' + (state.profile.photo ? '<button class="patient-profile-button is-danger" data-profile-remove-photo>' + icon("trash") + ' Eliminar</button>' : "") + '</div></section>');
  }

  function renderLogout() {
    openModal("Cerrar sesión", "SEGURIDAD", '<section class="patient-profile-empty"><div class="patient-profile-row-icon" style="margin:0 auto 12px;color:#d72b43;background:#fff0f3">' + icon("logout") + '</div><h3>¿Quieres cerrar tu sesión?</h3><p>Tu información local permanecerá guardada en este dispositivo.</p><div class="patient-profile-actions" style="justify-content:center"><button class="patient-profile-button" data-profile-close-modal>Cancelar</button><button class="patient-profile-button is-danger" data-profile-confirm-logout>Cerrar sesión</button></div></section>');
  }

  function submitLogout(button) {
    if (button) {
      button.disabled = true;
      button.textContent = "Cerrando...";
    }
    emit("logout");

    if (!csrfToken) {
      window.location.assign(loginUrl);
      return;
    }

    var form = document.createElement("form");
    form.method = "post";
    form.action = logoutUrl;
    form.style.display = "none";

    var token = document.createElement("input");
    token.type = "hidden";
    token.name = "_token";
    token.value = csrfToken;
    form.appendChild(token);

    document.body.appendChild(form);
    form.submit();
  }

  function openView(view) {
    var renderers = {
      finance: renderFinance,
      requests: renderRequests,
      messages: renderMessages,
      alerts: renderAlerts,
      sync: renderSynchronizations,
      profile: renderProfileInfo,
      photo: renderPhotoEditor,
      logout: renderLogout
    };
    if (renderers[view]) {
      renderers[view]();
      emit("open:" + view);
    }
  }

  document.addEventListener("click", function (event) {
    var button = event.target.closest("button, [data-profile-view], [data-profile-panel-open]");
    if (!button) return;

    if (button.matches("[data-profile-panel-open]")) {
      event.preventDefault();
      openPanel();
      return;
    }
    if (button.matches("[data-profile-panel-close]")) closePanel();
    if (button.matches("[data-profile-close-modal]")) closeModal();
    if (button.matches("[data-profile-support-toggle]")) {
      var support = $("[data-profile-support-group]");
      var menu = $("[data-profile-support-menu]");
      var expanded = button.getAttribute("aria-expanded") === "true";
      button.setAttribute("aria-expanded", String(!expanded));
      support.classList.toggle("is-open", !expanded);
      menu.hidden = expanded;
    }
    if (button.dataset.profileView) openView(button.dataset.profileView);
    if (button.matches("[data-profile-change-photo]")) photoInput.click();
    if (button.matches("[data-profile-remove-photo]")) {
      state.profile.photo = "";
      saveState();
      emit("profile:photo-removed");
      renderPhotoEditor();
    }
    if (button.dataset.profileMessageId) renderConversation(button.dataset.profileMessageId);
    if (button.dataset.profileRequestAction) {
      var request = state.requests.find(function (item) { return item.id === button.dataset.id; });
      if (request) request.status = button.dataset.profileRequestAction === "accept" ? "accepted" : "rejected";
      saveState();
      renderRequests();
    }
    if (button.dataset.profileAlertRead) {
      var alert = state.alerts.find(function (item) { return item.id === button.dataset.profileAlertRead; });
      if (alert) alert.unread = false;
      saveState();
      renderAlerts();
    }
    if (button.matches("[data-profile-read-all]")) {
      state.alerts.forEach(function (item) { item.unread = false; });
      saveState();
      renderAlerts();
    }
    if (button.dataset.profileRemoveSync) {
      state.synchronizations = state.synchronizations.filter(function (item) { return item.id !== button.dataset.profileRemoveSync; });
      saveState();
      renderSynchronizations();
    }
    if (button.dataset.profilePlan) {
      state.subscription.current = button.dataset.profilePlan;
      saveState();
      emit("subscription:selected", { planId: button.dataset.profilePlan });
      renderFinance();
    }
    if (button.matches("[data-profile-confirm-logout]")) {
      event.preventDefault();
      submitLogout(button);
    }
  });

  document.addEventListener("submit", function (event) {
    if (event.target.matches("[data-profile-recharge-form]")) {
      event.preventDefault();
      var amount = Number(new FormData(event.target).get("amount"));
      if (!amount || amount < 10) return;
      state.wallet.balance += amount;
      state.wallet.movements.unshift({ id: "MOV-" + Date.now(), date: new Date().toLocaleDateString("es-MX"), concept: "Recarga con tarjeta", amount: amount });
      saveState();
      emit("wallet:recharged", { amount: amount });
      renderFinance();
    }
    if (event.target.matches("[data-profile-form]")) {
      event.preventDefault();
      var formData = new FormData(event.target);
      ["curp", "phone"].forEach(function (key) {
        state.profile[key] = String(formData.get(key) || "No registrado").trim();
      });
      saveState();
      emit("profile:updated", { profile: clone(state.profile) });
      closeModal();
    }
    if (event.target.matches("[data-profile-message-form]")) {
      event.preventDefault();
      var form = event.target;
      var text = String(new FormData(form).get("message") || "").trim();
      if (!text) return;
      var item = state.messages.find(function (entry) { return entry.id === form.dataset.messageId; });
      if (!item) return;
      item = normalizeMessage(item);
      var time = messageTime();
      item.thread.push({ id: "MSG-OUT-" + Date.now(), from: "patient", sender: "Tú", text: text, time: time });
      item.preview = text;
      item.time = time;
      item.unread = false;
      state.messages = [item].concat(state.messages.filter(function (entry) { return entry.id !== item.id; }));
      saveState();
      emit("message:sent", { messageId: item.id, recipient: item.sender, text: text, time: time });
      renderConversation(item.id);
    }
  });

  if (photoInput) {
    photoInput.addEventListener("change", function () {
      var file = photoInput.files && photoInput.files[0];
      if (!file || !file.type.match(/^image\//)) return;
      var reader = new FileReader();
      reader.onload = function () {
        state.profile.photo = reader.result;
        saveState();
        emit("profile:photo-updated");
        renderPhotoEditor();
        photoInput.value = "";
      };
      reader.readAsDataURL(file);
    });
  }

  if (shade) shade.addEventListener("click", closePanel);
  if (modal) {
    modal.addEventListener("click", function (event) {
      if (event.target === modal) closeModal();
    });
  }
  document.addEventListener("keydown", function (event) {
    if (event.key !== "Escape") return;
    if (!modal.hidden) closeModal();
    else if (!panel.hidden) closePanel();
  });

  window.DrSamPatientProfilePanel = {
    open: openPanel,
    close: closePanel,
    openView: openView,
    getState: function () { return clone(state); }
  };

  updateUI();
})();
