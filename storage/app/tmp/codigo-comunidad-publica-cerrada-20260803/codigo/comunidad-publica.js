(function () {
  "use strict";

  const root = document.getElementById("communityPublicApp");
  const bridge = window.KliniWellnessCommunity;
  const params = new URLSearchParams(window.location.search);
  const appState = {
    communityId: params.get("community") || bridge?.defaultCommunityId || "community-yoga-mente",
    section: params.get("section") || "home",
    query: "",
    activeEventId: "",
    pendingReservationEventId: "",
    reviewsExpanded: false,
    membersExpanded: false,
    membersVisibleCount: 10,
    activeShareTarget: "",
    activeCommentsPostId: "",
    landingSection: "",
    postsVisibleCount: 10,
    eventFiltersOpen: true,
    eventFilters: { country: "", city: "", community: "", location: "", day: "" },
    classFiltersOpen: false,
    classFilters: { category: "", intensity: "", duration: "" },
    classesListMode: false,
    likedPosts: {}
  };

  const defaultImages = {
    hero: "https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=1500&q=80",
    post: "https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=1100&q=80",
    yoga: "https://images.unsplash.com/photo-1545205597-3d9d02c29597?auto=format&fit=crop&w=620&q=80",
    meditation: "https://images.unsplash.com/photo-1593811167562-9cef47bfc4d7?auto=format&fit=crop&w=620&q=80",
    outdoors: "https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=620&q=80",
    nutrition: "https://images.unsplash.com/photo-1498837167922-ddd27525d352?auto=format&fit=crop&w=620&q=80",
    running: "https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?auto=format&fit=crop&w=620&q=80",
    mental: "https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=620&q=80"
  };

  const memberProfileImages = [
    "https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1527980965255-d3b416303d12?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=260&q=80",
    "https://images.unsplash.com/photo-1547425260-76bcadfb4f2c?auto=format&fit=crop&w=260&q=80"
  ];

  const generatedMemberNames = [
    "Laura Hernandez", "Sofia Martinez", "Valeria Torres", "Andres Molina", "Paola Castillo",
    "Roberto Sanchez", "Camila Flores", "Miguel Ortega", "Daniela Vega", "Fernando Rios",
    "Regina Navarro", "Mateo Salazar", "Elena Campos", "Hector Luna", "Natalia Bravo",
    "Jorge Medina", "Claudia Paredes", "Emilio Vargas", "Isabel Fuentes", "Ricardo Leon"
  ];

  const publicReviews = [
    {
      author: "Ana Lopez",
      initials: "AL",
      rating: 5,
      date: "Hace 2 semanas",
      text: "Las sesiones de respiracion me ayudaron a crear una rutina real. Me gusta que las actividades sean claras y faciles de seguir.",
      photos: [defaultImages.yoga, defaultImages.meditation]
    },
    {
      author: "Diego Ramirez",
      initials: "DR",
      rating: 4,
      date: "Hace 1 mes",
      text: "La comunidad se siente cercana. Reserve un evento desde aqui y el recordatorio del administrador fue muy util.",
      photos: [defaultImages.outdoors, defaultImages.yoga]
    },
    {
      author: "Mariana Lopez",
      initials: "ML",
      rating: 5,
      date: "Hace 3 meses",
      text: "Me gusto encontrar publicaciones de otros miembros sin sentir presion. Es un espacio tranquilo para retomar habitos.",
      photos: [defaultImages.meditation, defaultImages.hero]
    },
    {
      author: "Carlos Ruiz",
      initials: "CR",
      rating: 4,
      date: "Hace 4 meses",
      text: "Los eventos presenciales estan bien organizados y el panel muestra rapido los cupos disponibles.",
      photos: [defaultImages.running, defaultImages.outdoors]
    }
  ];

  const publicClasses = [
    {
      id: "class-yoga-flow",
      name: "Yoga Flow",
      category: "Yoga",
      intensity: "Suave",
      duration: "45 min",
      description: "Secuencia fluida para movilidad, respiracion y presencia.",
      image: defaultImages.yoga
    },
    {
      id: "class-sculpt-core",
      name: "Sculpt Core",
      category: "Sculpt",
      intensity: "Media",
      duration: "40 min",
      description: "Trabajo de fuerza funcional con enfoque en abdomen y postura.",
      image: "https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=720&q=80"
    },
    {
      id: "class-pilates-mat",
      name: "Pilates Mat",
      category: "Pilates",
      intensity: "Media",
      duration: "50 min",
      description: "Control, estabilidad y fuerza profunda con ejercicios en tapete.",
      image: "https://images.unsplash.com/photo-1510894347713-fc3ed6fdf539?auto=format&fit=crop&w=720&q=80"
    },
    {
      id: "class-bici-cardio",
      name: "Bici Ritmo",
      category: "Bici",
      intensity: "Alta",
      duration: "35 min",
      description: "Clase energica de pedaleo con intervalos guiados.",
      image: "https://images.unsplash.com/photo-1534787238916-9ba6764efd4f?auto=format&fit=crop&w=720&q=80"
    },
    {
      id: "class-rueda-balance",
      name: "Rueda Balance",
      category: "Rueda",
      intensity: "Suave",
      duration: "45 min",
      description: "Practica con rueda para apertura, equilibrio y movilidad.",
      image: "https://images.unsplash.com/photo-1575052814086-f385e2e2ad1b?auto=format&fit=crop&w=720&q=80"
    },
    {
      id: "class-yoga-restaurativo",
      name: "Yoga Restaurativo",
      category: "Yoga",
      intensity: "Suave",
      duration: "30 min",
      description: "Posturas tranquilas para bajar revoluciones y descansar mejor.",
      image: defaultImages.meditation
    },
    {
      id: "class-sculpt-pierna",
      name: "Sculpt Pierna",
      category: "Sculpt",
      intensity: "Alta",
      duration: "45 min",
      description: "Rutina de fuerza para piernas, gluteos y resistencia.",
      image: defaultImages.running
    }
  ];

  const aboutItems = [
    { icon: "globe", title: "Comunidad abierta", detail: "Cualquiera puede unirse si el administrador mantiene acceso abierto." },
    { icon: "shield", title: "Respeto y amabilidad", detail: "Conversaciones moderadas para mantener un ambiente positivo." },
    { icon: "spark", title: "Crecimiento personal", detail: "Eventos, publicaciones y retos para avanzar con constancia." },
    { icon: "heart", title: "Bienestar integral", detail: "Cuerpo, mente y espiritu en equilibrio." }
  ];

  function icon(name) {
    const paths = {
      home: '<path d="M3 11.5 12 4l9 7.5"></path><path d="M5.5 10.5V20h13v-9.5"></path><path d="M9.5 20v-5h5v5"></path>',
      search: '<circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path>',
      heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"></path>',
      users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.9"></path><path d="M16 3.1a4 4 0 0 1 0 7.8"></path>',
      "user-plus": '<path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8" cy="7" r="4"></circle><path d="M19 8v6"></path><path d="M16 11h6"></path>',
      calendar: '<rect x="3" y="4" width="18" height="17" rx="3"></rect><path d="M8 2v4"></path><path d="M16 2v4"></path><path d="M3 10h18"></path>',
      file: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path>',
      info: '<circle cx="12" cy="12" r="9"></circle><path d="M12 11v5"></path><path d="M12 8h.01"></path>',
      share: '<circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><path d="M8.6 10.6l6.8-4.2"></path><path d="M8.6 13.4l6.8 4.2"></path>',
      message: '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>',
      image: '<rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="8" cy="10" r="1.5"></circle><path d="M21 16l-5-5L5 19"></path>',
      poll: '<path d="M5 19V9"></path><path d="M12 19V5"></path><path d="M19 19v-7"></path>',
      filter: '<path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path><circle cx="8" cy="6" r="2"></circle><circle cx="16" cy="12" r="2"></circle><circle cx="10" cy="18" r="2"></circle>',
      trash: '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M6 6l1 15h10l1-15"></path><path d="M10 11v6"></path><path d="M14 11v6"></path>',
      more: '<circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle>',
      camera: '<path d="M4 7h4l2-3h4l2 3h4v13H4z"></path><circle cx="12" cy="13" r="3"></circle>',
      instagram: '<rect x="4" y="4" width="16" height="16" rx="5"></rect><circle cx="12" cy="12" r="3.5"></circle><circle cx="17.2" cy="6.8" r=".7"></circle>',
      facebook: '<path d="M15 8h2.5V4H15c-3 0-5 2-5 5v3H7v4h3v4h4v-4h3l1-4h-4V9c0-.6.4-1 1-1z"></path>',
      tiktok: '<path d="M14 4v10.2a4.2 4.2 0 1 1-4.2-4.2"></path><path d="M14 4c.7 2.7 2.6 4.5 5 4.8"></path>',
      map: '<path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11z"></path><circle cx="12" cy="10" r="2.5"></circle>',
      globe: '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3a15 15 0 0 1 0 18"></path><path d="M12 3a15 15 0 0 0 0 18"></path>',
      shield: '<path d="M12 3l7 3v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3z"></path><path d="M9 12l2 2 4-5"></path>',
      spark: '<path d="M12 2l1.6 5.1L19 9l-5.4 1.9L12 16l-1.6-5.1L5 9l5.4-1.9L12 2z"></path><path d="M19 14l.8 2.4L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.6L19 14z"></path>',
      link: '<path d="M10 13a5 5 0 0 0 7.1 0l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1"></path><path d="M14 11a5 5 0 0 0-7.1 0l-2 2A5 5 0 0 0 12 20.1l1.1-1.1"></path>',
      close: '<path d="M18 6L6 18"></path><path d="M6 6l12 12"></path>'
    };
    return `<svg viewBox="0 0 24 24" aria-hidden="true">${paths[name] || paths.spark}</svg>`;
  }

  function chevron(direction = "right") {
    const paths = {
      left: '<path d="M15 18l-6-6 6-6"></path>',
      right: '<path d="M9 18l6-6-6-6"></path>',
      up: '<path d="M18 15l-6-6-6 6"></path>',
      down: '<path d="M6 9l6 6 6-6"></path>'
    };
    const path = paths[direction] || paths.right;
    return `<svg viewBox="0 0 24 24" aria-hidden="true">${path}</svg>`;
  }

  function safeText(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function safeUrl(value, fallback = "") {
    const url = String(value || "").trim();
    if (!url) return fallback;
    if (/^(https?:\/\/|data:image\/|assets\/|\.\/|\/)/i.test(url)) {
      return url.replace(/[)"\\'<>\r\n]/g, "");
    }
    return fallback;
  }

  function cssImage(url) {
    return `url('${safeUrl(url, defaultImages.hero)}')`;
  }

  function eventCost(event) {
    const raw = event?.cost ?? event?.price ?? event?.creditCost ?? 0;
    const cost = Number(raw || 0);
    return Number.isFinite(cost) ? Math.max(0, cost) : 0;
  }

  function creditLabel(amount) {
    const value = Number(amount || 0);
    return `${safeText(value)} ${value === 1 ? "credito" : "creditos"}`;
  }

  function pesoLabel(amount) {
    const value = Number(amount || 0);
    return `$${safeText(value.toLocaleString("es-MX"))} pesos`;
  }

  function eventPriceLabel(event) {
    const cost = eventCost(event);
    return cost > 0 ? pesoLabel(cost) : "gratuito";
  }

  function walletCredits(payload) {
    const value = Number(payload?.walletCredits ?? payload?.patient?.walletCredits);
    return Number.isFinite(value) ? Math.max(0, value) : 0;
  }

  function uniqueOptions(values) {
    return Array.from(new Set(values.map((value) => String(value || "").trim()).filter(Boolean))).sort((a, b) => a.localeCompare(b, "es"));
  }

  function eventCountry(event, payload) {
    return event.country || payload.community.country || "Mexico";
  }

  function eventCity(event, payload) {
    return event.city || payload.community.city || payload.community.region || "Ciudad de Mexico";
  }

  function eventCommunityName(payload) {
    return payload.community.name || "Comunidad";
  }

  function eventLocation(event) {
    if (event.modality === "remote") return event.locationName || event.remoteUrl || "En linea";
    return event.locationName || event.address || "Presencial";
  }

  function dateKeyFromDate(date, timezone = "America/Mexico_City") {
    if (!(date instanceof Date) || Number.isNaN(date.getTime())) return "";
    const parts = new Intl.DateTimeFormat("es-MX", {
      timeZone: timezone,
      year: "numeric",
      month: "2-digit",
      day: "2-digit"
    }).formatToParts(date).reduce((memo, part) => {
      memo[part.type] = part.value;
      return memo;
    }, {});
    return `${parts.year}-${parts.month}-${parts.day}`;
  }

  function eventDayKey(event) {
    return dateKeyFromDate(new Date(event.startAt), event.timezone || "America/Mexico_City");
  }

  function eventDateOptions(events) {
    const dates = events
      .map((event) => new Date(event.startAt))
      .filter((date) => !Number.isNaN(date.getTime()))
      .sort((a, b) => a - b);
    const anchor = dates[0] ? new Date(dates[0]) : new Date();
    anchor.setHours(12, 0, 0, 0);
    return Array.from({ length: 7 }, (_, index) => {
      const date = new Date(anchor);
      date.setDate(anchor.getDate() + index);
      const parts = formatDateParts(date);
      return { key: dateKeyFromDate(date), day: parts.day, weekday: parts.weekday };
    });
  }

  function selectOptions(options, selected, placeholder) {
    return `<option value="">${safeText(placeholder)}</option>${options.map((option) => `<option value="${safeText(option)}" ${option === selected ? "selected" : ""}>${safeText(option)}</option>`).join("")}`;
  }

  function matchesEventFilters(event, payload) {
    const filters = appState.eventFilters || {};
    return (!filters.country || eventCountry(event, payload) === filters.country)
      && (!filters.city || eventCity(event, payload) === filters.city)
      && (!filters.community || eventCommunityName(payload) === filters.community)
      && (!filters.location || eventLocation(event) === filters.location)
      && (!filters.day || eventDayKey(event) === filters.day);
  }

  function initials(name) {
    return String(name || "KW")
      .split(/\s+/)
      .filter(Boolean)
      .map((part) => part[0])
      .join("")
      .slice(0, 3)
      .toUpperCase() || "KW";
  }

  function getPayload() {
    return bridge?.getPublicPayload?.(appState.communityId) || null;
  }

  function showToast(message) {
    const toast = document.getElementById("publicToast");
    if (!toast || !message) return;
    toast.textContent = message;
    toast.classList.add("show");
    clearTimeout(window.__publicCommunityToastTimer);
    window.__publicCommunityToastTimer = setTimeout(() => toast.classList.remove("show"), 2600);
  }

  function formatDateParts(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return { weekday: "PROX", day: "--", month: "" };
    return {
      weekday: date.toLocaleDateString("es-MX", { weekday: "short" }).replace(".", "").toUpperCase(),
      day: date.toLocaleDateString("es-MX", { day: "2-digit" }),
      month: date.toLocaleDateString("es-MX", { month: "short" }).replace(".", "").toUpperCase(),
      full: date.toLocaleDateString("es-MX", { weekday: "long", day: "2-digit", month: "long" }),
      time: date.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" })
    };
  }

  function formatDateTime(value, endValue) {
    const start = formatDateParts(value);
    const end = formatDateParts(endValue);
    return `${start.full}, ${start.time}${end.time && end.time !== "--" ? ` - ${end.time}` : ""}`;
  }

  function relativeDate(value) {
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return "";
    const minutes = Math.max(1, Math.round((Date.now() - date.getTime()) / 60000));
    if (minutes < 60) return `hace ${minutes} min`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `hace ${hours} h`;
    return date.toLocaleDateString("es-MX", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" });
  }

  function eventImage(event, index = 0) {
    if (event.imageUrl) return safeUrl(event.imageUrl, defaultImages.yoga);
    const category = String(event.category || "").toLowerCase();
    if (category.includes("nutric")) return defaultImages.nutrition;
    if (category.includes("running") || category.includes("camin")) return defaultImages.running;
    if (category.includes("medit") || category.includes("mental")) return defaultImages.meditation;
    return [defaultImages.yoga, defaultImages.meditation, defaultImages.outdoors][index % 3];
  }

  function postImage(post, index = 0) {
    if (post.imageUrl) return safeUrl(post.imageUrl, defaultImages.post);
    if (post.pinned) return defaultImages.post;
    return index % 2 ? defaultImages.outdoors : "";
  }

  function renderLogo(community, className = "public-hero-mark") {
    const logo = safeUrl(community.logoImage, "");
    if (logo) return `<span class="${className}"><img src="${safeText(logo)}" alt="Logotipo de ${safeText(community.name)}"></span>`;
    return `<span class="${className}" title="${safeText(community.logoInitials || initials(community.name))}">${icon("heart")}</span>`;
  }

  function accessLabel(community) {
    return community.accessType === "open" ? "Comunidad abierta" : "Acceso con solicitud";
  }

  function primaryJoinLabel(payload) {
    if (payload.membership) return "Miembro activo";
    if (payload.request) return "Solicitud enviada";
    return payload.community.accessType === "open" ? "Unirse a la comunidad" : "Solicitar acceso";
  }

  function renderTopbar(payload) {
    return `
      <header class="public-topbar">
        <a class="public-brand" href="comunidad-publica.html?community=${safeText(payload.community.id)}" aria-label="Inicio de comunidad">
          <span class="public-brand-mark">K</span>
          <span class="public-brand-copy"><strong>Klini Wellness</strong><span>${safeText(payload.community.category || "Comunidad")}</span></span>
        </a>
        <div class="public-top-actions">
          <label class="public-search">
            <input data-public-search value="${safeText(appState.query)}" placeholder="Buscar en la comunidad..." aria-label="Buscar en la comunidad" />
            ${icon("search")}
          </label>
          <a class="public-pill-button" href="paciente.html">Iniciar sesion</a>
          <button class="public-primary-button" type="button" data-public-action="join" data-community-id="${safeText(payload.community.id)}" ${payload.membership || payload.request ? "disabled" : ""}>${safeText(primaryJoinLabel(payload))}</button>
        </div>
      </header>
    `;
  }

  function renderHero(payload) {
    const community = payload.community;
    const heroImage = safeUrl(community.backgroundImage, defaultImages.hero);
    return `
      <section class="public-hero" style="--hero-image:${cssImage(heroImage)}">
        <div class="public-hero-copy">
          <span class="public-badge">${icon("globe")} ${safeText(accessLabel(community))}</span>
          <h1>${safeText(community.name)}</h1>
          <p>${safeText(community.shortDescription || community.description)}</p>
          <div class="public-hero-stats" aria-label="Metricas de comunidad">
            <div class="public-stat"><span>${icon("users")}</span><div><strong>${safeText(community.memberCount || payload.activeMembers.length)}</strong><small>Miembros</small></div></div>
            <div class="public-stat"><span>${icon("calendar")}</span><div><strong>${safeText(payload.events.length)}</strong><small>Eventos activos</small></div></div>
            <div class="public-stat"><span>${icon("file")}</span><div><strong>${safeText(payload.posts.length)}</strong><small>Publicaciones</small></div></div>
          </div>
          <div class="public-hero-actions">
            <button class="public-primary-button" type="button" data-public-action="join" data-community-id="${safeText(community.id)}" ${payload.membership || payload.request ? "disabled" : ""}>${icon("user-plus")} ${safeText(primaryJoinLabel(payload))}</button>
            <div class="public-share-action-wrap">
              <button class="public-secondary-button" type="button" data-public-action="share-toggle" data-public-share-target="community-hero" aria-expanded="${safeText(appState.activeShareTarget === "community-hero")}">${icon("share")} Compartir</button>
              ${appState.activeShareTarget === "community-hero" ? `<div class="public-inline-share-options">${renderSocialShareButtons("comunidad")}</div>` : ""}
            </div>
          </div>
        </div>
        <div class="public-hero-side">
          <article class="public-hero-card">
            <span>${safeText(community.city || community.region || "Online")}</span>
            <strong>${safeText(payload.events[0]?.title || "Actividad semanal")}</strong>
            <p>${safeText(payload.events[0] ? formatDateTime(payload.events[0].startAt, payload.events[0].endAt) : community.pinnedMessage || "Participa en las actividades y publicaciones de la comunidad.")}</p>
          </article>
        </div>
      </section>
    `;
  }

  function renderTabs(payload) {
    const activeSection = appState.landingSection || appState.section;
    const tabs = [
      ["home", "Inicio", "home", "teal", ""],
      ["events", "Eventos", "calendar", "ink", payload.events.length],
      ["classes", "Clases", "spark", "gold", publicClasses.length],
      ["posts", "Publicaciones", "file", "blue", payload.posts.length],
      ["members", "Miembros", "users", "green", payload.community.memberCount || payload.activeMembers.length],
      ["about", "Acerca de", "info", "purple", ""]
    ];
    return `
      <nav class="public-tabs" aria-label="Secciones de la comunidad">
        ${tabs.map(([id, label, iconName, tone, count]) => `
          <button class="public-tab-${safeText(tone)} ${activeSection === id ? "active" : ""}" type="button" data-public-section="${id}">
            ${icon(iconName)}
            <span>${safeText(label)}</span>
            ${count ? `<em>${safeText(count)}</em>` : ""}
          </button>
        `).join("")}
      </nav>
    `;
  }

  function matchesQuery(...values) {
    const query = appState.query.trim().toLowerCase();
    if (!query) return true;
    return values.some((value) => String(value || "").toLowerCase().includes(query));
  }

  function renderComposer(payload) {
    const disabled = !payload.membership || payload.community.allowMemberPosts === false;
    return `
      <section class="public-panel">
        <div class="public-panel-heading">
          <div>
            <h2>Comparte algo con la comunidad</h2>
            <p>${disabled ? "Unete a la comunidad para participar en las publicaciones." : "Cuenta tus sentimientos, experiencias y reflexiones"}</p>
          </div>
        </div>
        <div class="public-composer">
          <span class="public-avatar">${safeText(payload.patient.initials)}</span>
          <form data-public-post-form>
            <textarea name="body" placeholder="Cuenta tu reflexion" ${disabled ? "disabled" : ""}></textarea>
            <div class="public-composer-tools">
              <span class="public-tool-group">
                <button type="button" data-public-action="tool-info">${icon("image")} Foto / Video</button>
                <button type="button" data-public-action="tool-info">${icon("poll")} Encuesta</button>
              </span>
              <button type="submit" ${disabled ? "disabled" : ""}>${icon("message")} Publicar</button>
            </div>
          </form>
        </div>
      </section>
    `;
  }

  function renderStars(rating) {
    const score = Math.max(0, Math.min(5, Number(rating || 0)));
    return Array.from({ length: 5 }, (_, index) => `<span class="${index < score ? "filled" : ""}">★</span>`).join("");
  }

  function renderSocialShareButtons(type = "opinion") {
    const label = { publicacion: "publicacion", comunidad: "comunidad", opinion: "opinion" }[type] || "opinion";
    return `
      <div class="public-social-share-row" aria-label="Compartir ${safeText(label)}">
        <button class="public-social-share-button dr-sam" type="button" data-public-action="share-dr-sam" aria-label="Compartir ${safeText(label)} en el feed de Dr. Sam"><span aria-hidden="true">DR</span></button>
        <button class="public-social-share-button instagram" type="button" data-public-action="share-instagram" aria-label="Compartir ${safeText(label)} en Instagram">${icon("instagram")}</button>
        <button class="public-social-share-button facebook" type="button" data-public-action="share-facebook" aria-label="Compartir ${safeText(label)} en Facebook">${icon("facebook")}</button>
        <button class="public-social-share-button tiktok" type="button" data-public-action="share-tiktok" aria-label="Compartir ${safeText(label)} en TikTok">${icon("tiktok")}</button>
      </div>
    `;
  }

  function renderReviewCard(review) {
    return `
      <article class="public-review-card">
        <header>
          <span class="public-review-avatar">${safeText(review.initials || initials(review.author))}</span>
          <div>
            <strong>${safeText(review.author)}</strong>
            <div class="public-review-stars" aria-label="${safeText(review.rating)} de 5 estrellas">${renderStars(review.rating)}</div>
          </div>
          <small>${safeText(review.date)}</small>
        </header>
        <p>${safeText(review.text)} <button type="button" data-public-action="review-more">mas</button></p>
        <div class="public-review-photos">
          ${(review.photos || []).slice(0, 2).map((photo, index) => `<span style="--review-photo:${cssImage(photo)}" role="img" aria-label="Foto de opinion ${index + 1}"></span>`).join("")}
        </div>
      </article>
    `;
  }

  function renderAllReviews() {
    if (!appState.reviewsExpanded) return "";
    return `
      <div class="public-review-stack" id="publicAllReviews" role="region" aria-label="Todas las opiniones">
        ${publicReviews.map(renderReviewCard).join("")}
      </div>
    `;
  }

  function renderReviewsPanel(payload) {
    const allReviewsLabel = appState.reviewsExpanded ? "Ocultar" : "Ver mas";
    return `
      <section class="public-panel public-reviews-panel">
        <div class="public-reviews-heading">
          <div>
            <span class="public-section-kicker">Opiniones</span>
            <h2>Opiniones</h2>
            <p><strong>4.8</strong><span class="public-rating-star">★</span> (${safeText(4371 + Number(payload.community.memberCount || 0))}) <button type="button" data-public-action="review-info" aria-label="Informacion de opiniones">${icon("info")}</button></p>
          </div>
          <div class="public-review-controls" aria-label="Mover opiniones">
            <button type="button" data-public-review-pan="left" aria-label="Mover opiniones a la izquierda">${chevron("left")}</button>
            <button type="button" data-public-review-pan="right" aria-label="Mover opiniones a la derecha">${chevron("right")}</button>
          </div>
        </div>
        <div class="public-review-carousel" data-public-review-carousel>
          ${publicReviews.map(renderReviewCard).join("")}
        </div>
        <button class="public-review-all" type="button" data-public-action="review-all" aria-expanded="${safeText(appState.reviewsExpanded)}" aria-controls="publicAllReviews">${safeText(allReviewsLabel)} ${chevron(appState.reviewsExpanded ? "up" : "down")}</button>
        ${renderAllReviews()}
      </section>
    `;
  }

  function isPostLiked(postId) {
    return Boolean(appState.likedPosts[postId]);
  }

  function currentPatientLikeProfile(payload) {
    const name = payload.patient?.name || "Tu perfil";
    return {
      id: "current-patient-like",
      displayName: name,
      initials: payload.patient?.initials || initials(name),
      profileImage: payload.patient?.profileImage || payload.patient?.avatarUrl || ""
    };
  }

  function postLikers(post, payload, index, liked) {
    const members = getAllPublicMembers(payload);
    if (!members.length) return liked ? [currentPatientLikeProfile(payload)] : [];
    const offset = (index * 3) % members.length;
    const ordered = members.map((_, memberIndex) => members[(offset + memberIndex) % members.length]);
    const base = liked
      ? [currentPatientLikeProfile(payload), ...ordered.filter((member) => member.id !== "current-patient-like")]
      : ordered;
    return base.slice(0, 5);
  }

  function renderLikeAvatar(member, index) {
    const image = safeUrl(member.profileImage || member.avatarUrl || member.photoUrl || member.image || member.photo || memberProfileImages[index % memberProfileImages.length], "");
    const label = safeText(member.displayName || "Miembro");
    if (image) {
      return `<span class="public-like-avatar" style="--like-avatar:${cssImage(image)}" role="img" aria-label="${label}" title="${label}"></span>`;
    }
    return `<span class="public-like-avatar" aria-label="${label}" title="${label}">${safeText(member.initials || initials(member.displayName))}</span>`;
  }

  function renderPostLikeProof(post, payload, index, liked, totalReactions) {
    const likers = postLikers(post, payload, index, liked);
    if (!likers.length) return "";
    return `
      <span class="public-like-proof" aria-label="Perfiles que dieron me gusta">
        <span class="public-like-stack">${likers.map(renderLikeAvatar).join("")}</span>
        ${totalReactions > likers.length ? `<span class="public-like-more">y otros</span>` : ""}
      </span>
    `;
  }

  function postComments(post, payload, index) {
    const total = Math.max(0, Number(post.comments || 0));
    if (!total) return [];
    const members = getAllPublicMembers(payload);
    const fallbackMember = currentPatientLikeProfile(payload);
    const messages = [
      "Gracias por compartirlo, me ayuda a organizar mejor mi rutina.",
      "Me gusto mucho esta recomendacion. La voy a intentar esta semana.",
      "Tambien me pasa, respirar con calma cambia mucho el dia.",
      "Que buena actividad, me interesa participar en la siguiente sesion.",
      "Me sirve leer experiencias asi, se siente muy acompanado.",
      "Excelente recordatorio para seguir con constancia."
    ];
    return Array.from({ length: Math.min(total, 6) }, (_, commentIndex) => {
      const member = members[(index + commentIndex + 1) % Math.max(1, members.length)] || fallbackMember;
      return {
        member,
        text: messages[(index + commentIndex) % messages.length],
        date: commentIndex < 2 ? "Hace unos minutos" : `Hace ${commentIndex + 1} h`
      };
    });
  }

  function renderCommentAvatar(member, index) {
    const image = safeUrl(member.profileImage || member.avatarUrl || member.photoUrl || member.image || member.photo || memberProfileImages[index % memberProfileImages.length], "");
    const label = safeText(member.displayName || "Miembro");
    if (image) {
      return `<span class="public-comment-avatar" style="--comment-avatar:${cssImage(image)}" role="img" aria-label="${label}"></span>`;
    }
    return `<span class="public-comment-avatar" aria-label="${label}">${safeText(member.initials || initials(member.displayName))}</span>`;
  }

  function renderPostCommentsPanel(post, payload, index, postId) {
    const comments = postComments(post, payload, index);
    const total = Math.max(Number(post.comments || 0), comments.length);
    return `
      <div class="public-post-comments-panel" id="public-comments-${safeText(postId)}">
        <div class="public-comments-heading">
          <strong>Comentarios</strong>
          <span>${safeText(total)} comentarios</span>
        </div>
        <div class="public-comments-list">
          ${comments.length ? comments.map((comment, commentIndex) => `
            <article class="public-comment-item">
              ${renderCommentAvatar(comment.member, commentIndex)}
              <div>
                <header>
                  <strong>${safeText(comment.member.displayName || "Miembro")}</strong>
                  <small>${safeText(comment.date)}</small>
                </header>
                <p>${safeText(comment.text)}</p>
              </div>
            </article>
          `).join("") : `<p class="public-empty-comment">Aun no hay comentarios en esta publicacion.</p>`}
        </div>
        ${total > comments.length ? `<p class="public-comments-more">y ${safeText(total - comments.length)} comentarios mas</p>` : ""}
      </div>
    `;
  }

  function renderPostCard(post, payload, index) {
    const image = postImage(post, index);
    const authorInitials = initials(post.authorName);
    const role = post.authorId === payload.admin.id ? "Administrador" : post.pinned ? "Fijada" : "Miembro";
    const postId = String(post.id || `post-${index}`);
    const liked = isPostLiked(postId);
    const commentsOpen = appState.activeCommentsPostId === postId;
    const totalReactions = Number(post.reactions || 0) + (liked ? 1 : 0);
    return `
      <article class="public-post-card">
        <div class="public-post-inner">
          <div class="public-post-top">
            <span class="public-mini-avatar">${safeText(authorInitials)}</span>
            <div class="public-post-author">
              <strong>${safeText(post.authorName || "Miembro")} <span class="public-role-badge">${safeText(role)}</span></strong>
              <small>${safeText(relativeDate(post.createdAt))}</small>
            </div>
            <button class="public-icon-button" type="button" data-public-action="post-menu" aria-label="Mas opciones">${icon("more")}</button>
          </div>
          <p class="public-post-body">${safeText(post.body)}</p>
          ${image ? `<div class="public-post-image" style="--post-image:${cssImage(image)}" role="img" aria-label="Imagen de publicacion"></div>` : ""}
        </div>
        <div class="public-post-stats">
          <span>${icon("heart")} ${safeText(totalReactions)} reacciones</span>
          <span>${safeText(post.comments)} comentarios</span>
        </div>
        <div class="public-post-actions">
          <div class="public-like-action-group">
            <button class="public-post-action public-like-button ${liked ? "is-liked" : ""}" type="button" data-public-action="react" data-post-id="${safeText(postId)}" aria-pressed="${safeText(liked)}">${icon("heart")} Me gusta</button>
            ${renderPostLikeProof(post, payload, index, liked, totalReactions)}
          </div>
          <button class="public-post-action public-comment-button ${commentsOpen ? "is-open" : ""}" type="button" data-public-action="comment" data-post-id="${safeText(postId)}" aria-expanded="${safeText(commentsOpen)}" aria-controls="public-comments-${safeText(postId)}">${icon("message")} Comentarios</button>
          <div class="public-share-action-wrap">
            <button class="public-post-action" type="button" data-public-action="share-toggle" data-public-share-target="${safeText(postId)}" aria-expanded="${safeText(appState.activeShareTarget === postId)}">${icon("share")} Compartir</button>
            ${appState.activeShareTarget === postId ? `<div class="public-inline-share-options">${renderSocialShareButtons("publicacion")}</div>` : ""}
          </div>
        </div>
        ${commentsOpen ? renderPostCommentsPanel(post, payload, index, postId) : ""}
      </article>
    `;
  }

  function renderPosts(payload, limit = 10) {
    const allPosts = payload.posts.filter((post) => matchesQuery(post.title, post.body, post.authorName));
    const posts = allPosts.slice(0, limit);
    const showMoreButton = posts.length < allPosts.length;
    return `
      <section class="public-panel public-posts-panel" id="publicPostsSection">
        <div class="public-panel-heading">
          <div>
            <h2>Publicaciones recientes</h2>
            <p>${safeText(posts.length)} de ${safeText(allPosts.length)} publicaciones visibles</p>
          </div>
        </div>
        <div class="public-feed">
          ${posts.length ? posts.map((post, index) => renderPostCard(post, payload, index)).join("") : renderEmpty("No hay publicaciones que coincidan con tu busqueda.")}
        </div>
        ${showMoreButton ? `<button class="public-review-all public-post-more" type="button" data-public-action="post-more">Ver mas ${chevron("down")}</button>` : ""}
      </section>
    `;
  }

  function matchesClassFilters(item) {
    const filters = appState.classFilters || {};
    return matchesQuery(item.name, item.category, item.description, item.intensity)
      && (!filters.category || item.category === filters.category)
      && (!filters.intensity || item.intensity === filters.intensity)
      && (!filters.duration || item.duration === filters.duration);
  }

  function renderClassFilters(classes) {
    const filters = appState.classFilters || {};
    const categories = uniqueOptions(classes.map((item) => item.category));
    const intensities = uniqueOptions(classes.map((item) => item.intensity));
    const durations = uniqueOptions(classes.map((item) => item.duration));
    return `
      <div class="public-class-filter-wrap">
        <button class="public-class-filter-toggle" type="button" data-public-action="class-filter-toggle" aria-expanded="${safeText(appState.classFiltersOpen)}">
          ${icon("filter")}
          <span>Filtrar clases</span>
          ${chevron(appState.classFiltersOpen ? "up" : "down")}
        </button>
        ${appState.classFiltersOpen ? `
          <form class="public-class-filters" data-public-class-filter-form>
            <div class="public-class-filter-grid">
              <label class="public-class-filter-field">
                <span>Categoria</span>
                <select name="category">${selectOptions(categories, filters.category, "Seleccionar categoria")}</select>
              </label>
              <label class="public-class-filter-field">
                <span>Intensidad</span>
                <select name="intensity">${selectOptions(intensities, filters.intensity, "Seleccionar intensidad")}</select>
              </label>
              <label class="public-class-filter-field">
                <span>Duracion</span>
                <select name="duration">${selectOptions(durations, filters.duration, "Seleccionar duracion")}</select>
              </label>
            </div>
            <div class="public-class-filter-actions">
              <button type="button" class="public-class-filter-clear" data-public-action="class-filter-clear">Limpiar filtros</button>
              <button type="button" class="public-class-filter-apply" data-public-action="class-filter-apply">Aplicar filtros</button>
            </div>
          </form>
        ` : ""}
      </div>
    `;
  }

  function renderClassCarouselCard(item) {
    return `
      <article class="public-class-card" style="--class-image:${cssImage(item.image)}">
        <div class="public-class-card-overlay">
          <span>${safeText(item.category)}</span>
          <h3>${safeText(item.name)}</h3>
          <small>${safeText(item.intensity)} - ${safeText(item.duration)}</small>
        </div>
      </article>
    `;
  }

  function renderClassListCard(item) {
    return `
      <article class="public-class-list-card">
        <span class="public-class-list-image" style="--class-image:${cssImage(item.image)}" role="img" aria-label="Clase ${safeText(item.name)}"></span>
        <div>
          <span>${safeText(item.category)}</span>
          <h3>${safeText(item.name)}</h3>
          <p>${safeText(item.description)}</p>
          <small>${safeText(item.intensity)} - ${safeText(item.duration)}</small>
        </div>
      </article>
    `;
  }

  function renderClasses() {
    const classes = publicClasses.filter(matchesClassFilters);
    return `
      <section class="public-panel public-classes-panel" id="publicClassesSection">
        <div class="public-panel-heading">
          <div>
            <h2>Clases</h2>
            <p>Elige una categoria para moverte a tu ritmo.</p>
          </div>
          ${appState.classesListMode ? "" : `
            <div class="public-review-controls public-class-controls" aria-label="Mover clases">
              <button type="button" data-public-class-pan="left" aria-label="Mover clases a la izquierda">${chevron("left")}</button>
              <button type="button" data-public-class-pan="right" aria-label="Mover clases a la derecha">${chevron("right")}</button>
            </div>
          `}
        </div>
        ${renderClassFilters(publicClasses)}
        ${appState.classesListMode ? `
          <div class="public-class-list">
            ${classes.length ? classes.map(renderClassListCard).join("") : renderEmpty("No hay clases que coincidan con los filtros.")}
          </div>
        ` : `
          <div class="public-class-carousel" data-public-class-carousel>
            ${classes.length ? classes.map(renderClassCarouselCard).join("") : renderEmpty("No hay clases que coincidan con tu busqueda.")}
          </div>
        `}
      </section>
    `;
  }

  function renderEventCard(event, index) {
    const date = formatDateParts(event.startAt);
    const image = eventImage(event, index);
    const reserved = event.reservation && !["cancelled", "no_show"].includes(event.reservation.status);
    const full = Number(event.seats?.available || 0) <= 0;
    const actionLabel = full && event.allowWaitlist ? "Lista de espera" : full ? "Lleno" : "Reservar";
    const priceLabel = eventPriceLabel(event);
    return `
      <article class="public-event-card">
        <div class="public-date-tile"><span>${safeText(date.weekday)}</span><strong>${safeText(date.day)}</strong><small>${safeText(date.month)}</small></div>
        <div class="public-event-copy">
          <h3>${safeText(event.title)}</h3>
          <p>${safeText(event.shortDescription || event.description)}</p>
          <div class="public-event-meta">
            <span>${icon("calendar")} ${safeText(date.time)}</span>
            <span>${icon(event.modality === "remote" ? "globe" : "map")} ${safeText(event.modality === "remote" ? event.locationName || "En linea" : event.locationName || event.address || "Presencial")}</span>
            <span>${icon("users")} ${safeText(event.seats?.available || 0)} cupos disponibles</span>
          </div>
          <span class="public-event-price">Precio: ${safeText(priceLabel)}</span>
        </div>
        <div class="public-event-image" style="--event-image:${cssImage(image)}" role="img" aria-label="Imagen del evento"></div>
        <div class="public-event-footer">
          ${reserved ? `<span class="public-event-action-stack"><span class="public-event-status" aria-label="Reserva activa">Reservado</span><small>${safeText(priceLabel)}</small></span>` : `<span class="public-event-action-stack"><button class="public-event-action primary" type="button" data-public-action="reserve" data-event-id="${safeText(event.id)}" ${full && !event.allowWaitlist ? "disabled" : ""}>${safeText(actionLabel)}</button><small>${safeText(priceLabel)}</small></span>`}
        </div>
      </article>
    `;
  }

  function renderEventFilterControl({ label, name, options, selected, placeholder, prefixHtml }) {
    const value = selected || placeholder;
    return `
      <label class="public-event-filter-field">
        <span>${safeText(label)}</span>
        <span class="public-event-filter-select">
          <span class="public-event-filter-leading">${prefixHtml}</span>
          <span class="public-event-filter-value">${safeText(value)}</span>
          <select name="${safeText(name)}" data-public-event-filter-control aria-label="${safeText(label)}">
            ${selectOptions(options, selected, placeholder)}
          </select>
          <button class="public-event-field-clear" type="button" data-public-action="event-filter-field-clear" data-filter-field="${safeText(name)}" aria-label="Borrar ${safeText(label)}">${icon("close")}</button>
          <span class="public-event-filter-chevron">${chevron("down")}</span>
        </span>
      </label>
    `;
  }

  function renderEventDateFilter(events, filters) {
    const dates = eventDateOptions(events);
    const activeDay = filters.day || dates[0]?.key || "";
    return `
      <div class="public-event-date-filter">
        <strong>Fecha del evento</strong>
        <div class="public-event-date-row" aria-label="Filtrar por fecha de evento">
          ${dates.map((date) => `
            <button class="public-event-date-chip ${activeDay === date.key ? "active" : ""}" type="button" data-public-action="event-filter-day" data-public-event-day="${safeText(date.key)}" aria-pressed="${safeText(filters.day === date.key)}">
              <strong>${safeText(date.day)}</strong>
              <span>${safeText(date.weekday)}</span>
            </button>
          `).join("")}
          <button class="public-event-date-next" type="button" data-public-action="event-filter-next-day" aria-label="Ver mas fechas">${chevron("right")}</button>
        </div>
        <small>Selecciona un dia para ver los eventos disponibles.</small>
      </div>
    `;
  }

  function renderEventFilters(payload, events) {
    const filters = appState.eventFilters || {};
    const countries = uniqueOptions(events.map((event) => eventCountry(event, payload)));
    const cities = uniqueOptions(events.map((event) => eventCity(event, payload)));
    const communities = uniqueOptions([eventCommunityName(payload)]);
    const locations = uniqueOptions(events.map(eventLocation));
    const countryPlaceholder = countries[0] || "Mexico";
    const cityPlaceholder = cities[0] || "Ciudad de Mexico";
    const communityPlaceholder = communities[0] || payload.community.name || "Comunidad";
    const locationPlaceholder = locations[0] || "Buscar ubicacion";
    return `
      <div class="public-event-filter-wrap">
        <div class="public-event-filter-top">
          <button class="public-event-filter-toggle" type="button" data-public-action="event-filter-toggle" aria-expanded="${safeText(appState.eventFiltersOpen)}">
            ${icon("filter")}
            <span>Filtrar eventos</span>
            ${chevron(appState.eventFiltersOpen ? "up" : "down")}
          </button>
          <button class="public-event-filter-reset" type="button" data-public-action="event-filter-clear">${icon("trash")} Borrar filtros</button>
        </div>
        ${appState.eventFiltersOpen ? `
          <form class="public-event-filters" data-public-event-filter-form>
            <div class="public-event-filter-grid">
              ${renderEventFilterControl({ label: "Pais", name: "country", options: countries, selected: filters.country, placeholder: countryPlaceholder, prefixHtml: '<span class="public-event-flag">🇲🇽</span>' })}
              ${renderEventFilterControl({ label: "Ciudad", name: "city", options: cities, selected: filters.city, placeholder: cityPlaceholder, prefixHtml: icon("map") })}
              ${renderEventFilterControl({ label: "Comunidad", name: "community", options: communities, selected: filters.community, placeholder: communityPlaceholder, prefixHtml: icon("users") })}
              ${renderEventFilterControl({ label: "Ubicacion", name: "location", options: locations, selected: filters.location, placeholder: locationPlaceholder, prefixHtml: icon("map") })}
            </div>
            ${renderEventDateFilter(events, filters)}
          </form>
        ` : ""}
      </div>
    `;
  }

  function renderEvents(payload, limit = 4) {
    const allEvents = payload.events.filter((event) => matchesQuery(event.title, event.description, event.locationName, event.category));
    const filteredEvents = allEvents.filter((event) => matchesEventFilters(event, payload));
    const events = filteredEvents.slice(0, limit);
    const showMoreButton = appState.section !== "events";
    return `
      <section class="public-panel">
        <div class="public-panel-heading">
          <div>
            <h2>Proximos eventos</h2>
            <p>Actividades publicadas por el administrador.</p>
          </div>
        </div>
        ${renderEventFilters(payload, allEvents)}
        <div class="public-event-list">
          ${events.length ? events.map(renderEventCard).join("") : renderEmpty("No hay eventos publicados que coincidan con tu busqueda.")}
        </div>
        ${showMoreButton ? `<button class="public-review-all public-event-more" type="button" data-public-section="events">Ver mas ${chevron("down")}</button>` : ""}
      </section>
    `;
  }

  function renderAbout(payload) {
    const community = payload.community;
    return `
      <section class="public-panel">
        <div class="public-panel-heading">
          <div>
            <h2>Acerca de la comunidad</h2>
            <p>${safeText(community.category || "Bienestar")} - ${safeText(community.city || "Online")}</p>
          </div>
        </div>
        <p class="public-about-copy">${safeText(community.description)}</p>
        ${community.pinnedMessage ? `<p class="public-about-copy"><strong>Aviso:</strong> ${safeText(community.pinnedMessage)}</p>` : ""}
        <div class="public-about-list">
          ${aboutItems.map((item) => `
            <article class="public-about-item">
              <span>${icon(item.icon)}</span>
              <span><strong>${safeText(item.title)}</strong><small>${safeText(item.detail)}</small></span>
            </article>
          `).join("")}
        </div>
      </section>
    `;
  }

  function roleLabel(role) {
    return {
      owner: "Propietario",
      admin: "Administrador",
      creator: "Creador",
      moderator: "Moderador",
      member: "Miembro"
    }[role] || role || "Miembro";
  }

  function getAllPublicMembers(payload) {
    const baseMembers = Array.isArray(payload.activeMembers) ? payload.activeMembers : [];
    const requestedTotal = Math.max(baseMembers.length, Number(payload.community.memberCount || baseMembers.length || 0));
    const members = baseMembers.map((member, index) => ({
      ...member,
      displayName: member.displayName || member.name || `Miembro ${index + 1}`,
      initials: member.initials || initials(member.displayName || member.name),
      participation: Number(member.participation || Math.min(98, 54 + (index * 7))),
      profileImage: member.profileImage || member.avatarUrl || member.photoUrl || member.image || member.photo || memberProfileImages[index % memberProfileImages.length]
    }));

    for (let index = members.length; index < requestedTotal; index += 1) {
      const name = generatedMemberNames[index % generatedMemberNames.length];
      const repeat = Math.floor(index / generatedMemberNames.length);
      const displayName = repeat ? `${name} ${repeat + 1}` : name;
      members.push({
        id: `public-member-${index + 1}`,
        displayName,
        initials: initials(displayName),
        role: "member",
        participation: Math.min(98, 52 + ((index * 5) % 43)),
        profileImage: memberProfileImages[index % memberProfileImages.length]
      });
    }

    return members;
  }

  function getPublicMembers(payload) {
    return getAllPublicMembers(payload).filter((member) => matchesQuery(member.displayName, member.role, roleLabel(member.role)));
  }

  function renderMemberPhoto(member, index, className = "") {
    const image = safeUrl(member.profileImage || member.avatarUrl || member.photoUrl || member.image || member.photo || memberProfileImages[index % memberProfileImages.length], "");
    const label = safeText(member.displayName || "Miembro");
    if (image) {
      return `<span class="public-member-photo ${safeText(className)}" style="--member-photo:${cssImage(image)}" role="img" aria-label="Foto de perfil de ${label}"></span>`;
    }
    return `<span class="public-member-photo ${safeText(className)}">${safeText(member.initials || initials(member.displayName))}</span>`;
  }

  function renderMemberCarouselCard(member, index) {
    return `
      <article class="public-member-card public-member-carousel-card">
        ${renderMemberPhoto(member, index, "large")}
        <div>
          <h3>${safeText(member.displayName)}</h3>
          <small>${safeText(roleLabel(member.role))}</small>
        </div>
        <strong class="public-member-score">${safeText(member.participation)}%</strong>
      </article>
    `;
  }

  function renderMemberListCard(member, index) {
    return `
      <article class="public-member-card public-member-list-card">
        ${renderMemberPhoto(member, index)}
        <div>
          <h3>${safeText(member.displayName)}</h3>
          <small>${safeText(roleLabel(member.role))}</small>
        </div>
        <strong class="public-member-score">${safeText(member.participation)}%</strong>
      </article>
    `;
  }

  function renderMemberDrawer(members) {
    if (!appState.membersExpanded) return "";
    const visible = members.slice(0, appState.membersVisibleCount);
    const hasMore = visible.length < members.length;
    return `
      <div class="public-member-drawer" id="publicAllMembers" role="region" aria-label="Todos los miembros activos">
        <div class="public-member-drawer-heading">
          <strong>${safeText(visible.length)} de ${safeText(members.length)} miembros</strong>
          <span>Listado vertical de perfiles activos.</span>
        </div>
        <div class="public-member-list">
          ${visible.map(renderMemberListCard).join("")}
        </div>
        <div class="public-member-drawer-actions">
          ${hasMore ? `<button class="public-member-more-button" type="button" data-public-action="member-more">Mostrar 10 mas ${chevron("down")}</button>` : ""}
          <button class="public-member-hide-button" type="button" data-public-action="member-hide">${chevron("up")} Ocultar informacion</button>
        </div>
      </div>
    `;
  }

  function renderMembers(payload) {
    const members = getPublicMembers(payload);
    return `
      <section class="public-panel public-members-panel">
        <div class="public-panel-heading">
          <div>
            <h2>Miembros activos</h2>
            <p>${safeText(members.length)} perfiles visibles</p>
          </div>
          <div class="public-review-controls public-member-controls" aria-label="Mover miembros">
            <button type="button" data-public-member-pan="left" aria-label="Mover miembros a la izquierda">${chevron("left")}</button>
            <button type="button" data-public-member-pan="right" aria-label="Mover miembros a la derecha">${chevron("right")}</button>
          </div>
        </div>
        ${members.length ? `
          <div class="public-member-carousel" data-public-member-carousel>
            ${members.map(renderMemberCarouselCard).join("")}
          </div>
          ${!appState.membersExpanded ? `<button class="public-review-all public-member-all" type="button" data-public-action="member-all" aria-expanded="false" aria-controls="publicAllMembers">Ver mas ${chevron("down")}</button>` : ""}
          ${renderMemberDrawer(members)}
        ` : renderEmpty("No hay miembros que coincidan con tu busqueda.")}
      </section>
    `;
  }

  function renderRelated(payload) {
    return `
      <section class="public-panel">
        <div class="public-panel-heading">
          <div>
            <h2>Comunidades relacionadas</h2>
            <p>Otros espacios de bienestar.</p>
          </div>
        </div>
        <div class="public-related-list">
          ${payload.relatedCommunities.length ? payload.relatedCommunities.map((community) => `
            <article class="public-related-card">
              <span class="public-related-mark">${safeText(community.logoInitials || initials(community.name))}</span>
              <div><h3>${safeText(community.name)}</h3><small>${safeText(community.category)} - ${safeText(community.memberCount || 0)} miembros</small></div>
              <button class="public-community-action" type="button" data-public-open-community="${safeText(community.id)}">Ver</button>
            </article>
          `).join("") : renderEmpty("No hay comunidades relacionadas visibles.")}
        </div>
      </section>
    `;
  }

  function renderResources(payload) {
    const resources = [
      { icon: "file", title: "Guia de respiracion", detail: "Lectura recomendada para iniciar practicas suaves." },
      { icon: "calendar", title: "Calendario comunitario", detail: `${payload.events.length} eventos publicados.` },
      { icon: "shield", title: "Reglas de convivencia", detail: payload.community.rules || "Respeto, empatia y participacion responsable." },
      { icon: "message", title: "Mensajes del administrador", detail: payload.community.pinnedMessage || "Avisos importantes apareceran aqui." }
    ];
    return `
      <section class="public-panel">
        <div class="public-panel-heading">
          <div>
            <h2>Recursos</h2>
            <p>Materiales y accesos visibles para usuarios.</p>
          </div>
        </div>
        <div class="public-about-list">
          ${resources.map((item) => `
            <article class="public-about-item">
              <span>${icon(item.icon)}</span>
              <span><strong>${safeText(item.title)}</strong><small>${safeText(item.detail)}</small></span>
            </article>
          `).join("")}
        </div>
      </section>
    `;
  }

  function renderContent(payload) {
    if (appState.section === "events") {
      return `${renderEvents(payload, 20)}${renderReviewsPanel(payload)}${renderResources(payload)}`;
    }
    if (appState.section === "classes") {
      return `${renderClasses()}${renderComposer(payload)}${renderEvents(payload, 3)}${renderPosts(payload, appState.postsVisibleCount)}`;
    }
    if (appState.section === "members") {
      return `${renderMembers(payload)}${renderReviewsPanel(payload)}${renderRelated(payload)}`;
    }
    if (appState.section === "resources") {
      return `${renderResources(payload)}${renderEvents(payload, 3)}${renderAbout(payload)}`;
    }
    if (appState.section === "about") {
      return `${renderAbout(payload)}${renderMembers(payload)}${renderReviewsPanel(payload)}${renderRelated(payload)}`;
    }
    if (appState.section === "posts") {
      return `${renderReviewsPanel(payload)}${renderComposer(payload)}${renderClasses()}${renderPosts(payload, appState.postsVisibleCount)}${renderEvents(payload, 3)}`;
    }
    return `${renderMembers(payload)}${renderReviewsPanel(payload)}${renderComposer(payload)}${renderClasses()}${renderEvents(payload, 3)}${renderPosts(payload, appState.postsVisibleCount)}${renderAbout(payload)}${renderRelated(payload)}`;
  }

  function renderCta(payload) {
    const community = payload.community;
    const heroImage = safeUrl(community.backgroundImage, defaultImages.hero);
    return `
      <section class="public-hero public-cta-hero" style="--hero-image:${cssImage(heroImage)}">
        <div class="public-hero-copy">
          <span class="public-badge">${icon("globe")} ${safeText(accessLabel(community))}</span>
          <h2>${safeText(community.name)}</h2>
          <p>${safeText(community.shortDescription || community.description)}</p>
          <div class="public-hero-stats" aria-label="Metricas de comunidad">
            <div class="public-stat"><span>${icon("users")}</span><div><strong>${safeText(community.memberCount || payload.activeMembers.length)}</strong><small>Miembros</small></div></div>
            <div class="public-stat"><span>${icon("calendar")}</span><div><strong>${safeText(payload.events.length)}</strong><small>Eventos activos</small></div></div>
            <div class="public-stat"><span>${icon("file")}</span><div><strong>${safeText(payload.posts.length)}</strong><small>Publicaciones</small></div></div>
          </div>
          <div class="public-hero-actions">
            <button class="public-primary-button" type="button" data-public-action="join" data-community-id="${safeText(community.id)}" ${payload.membership || payload.request ? "disabled" : ""}>${icon("user-plus")} ${safeText(primaryJoinLabel(payload))}</button>
            <div class="public-share-action-wrap">
              <button class="public-secondary-button" type="button" data-public-action="share-toggle" data-public-share-target="community-cta" aria-expanded="${safeText(appState.activeShareTarget === "community-cta")}">${icon("share")} Compartir</button>
              ${appState.activeShareTarget === "community-cta" ? `<div class="public-inline-share-options">${renderSocialShareButtons("comunidad")}</div>` : ""}
            </div>
          </div>
        </div>
        <div class="public-hero-side">
          <article class="public-hero-card">
            <span>${safeText(community.city || community.region || "Online")}</span>
            <strong>${safeText(payload.events[0]?.title || "Actividad semanal")}</strong>
            <p>${safeText(payload.events[0] ? formatDateTime(payload.events[0].startAt, payload.events[0].endAt) : community.pinnedMessage || "Participa en las actividades y publicaciones de la comunidad.")}</p>
          </article>
        </div>
      </section>
    `;
  }

  function renderFooter(payload) {
    return `
      <footer class="public-footer">
        <div>
          <span class="public-footer-brand"><span class="public-footer-mark">K</span><span><strong>${safeText(payload.community.name)}</strong></span></span>
          <p>Un espacio para conectar, compartir y crecer juntos en bienestar.</p>
        </div>
        <div class="public-footer-links">
          <h3>Enlaces</h3>
          <button class="public-quiet-link" type="button" data-public-section="home">Inicio</button>
          <button class="public-quiet-link" type="button" data-public-section="events">Eventos</button>
          <button class="public-quiet-link" type="button" data-public-section="classes">Clases</button>
          <button class="public-quiet-link" type="button" data-public-section="posts">Publicaciones</button>
          <button class="public-quiet-link" type="button" data-public-section="members">Miembros</button>
          <button class="public-quiet-link" type="button" data-public-section="about">Acerca de</button>
        </div>
        <div class="public-footer-links">
          <h3>Soporte</h3>
          <a href="paciente.html">Inicio de sesion</a>
          <a href="comunidad-admin.html">Administrar comunidad</a>
          <button class="public-quiet-link" type="button" data-public-action="share">Compartir</button>
        </div>
        <div class="public-footer-links">
          <h3>Siguenos</h3>
          <span class="public-social-row">
            <a href="#" aria-label="Instagram">${icon("camera")}</a>
            <a href="#" aria-label="Comunidad">${icon("users")}</a>
            <a href="#" aria-label="Enlace">${icon("link")}</a>
          </span>
        </div>
      </footer>
    `;
  }

  function renderModal(payload) {
    if (!appState.activeEventId) return "";
    const event = payload.events.find((item) => item.id === appState.activeEventId);
    if (!event) return "";
    const image = eventImage(event);
    const reserved = event.reservation && !["cancelled", "no_show"].includes(event.reservation.status);
    return `
      <div class="public-modal-backdrop" data-public-modal-backdrop>
        <section class="public-modal" role="dialog" aria-modal="true" aria-labelledby="publicEventTitle">
          <div class="public-modal-cover" style="--event-image:${cssImage(image)}"></div>
          <div class="public-modal-body">
            <div class="public-panel-heading">
              <div>
                <h2 id="publicEventTitle">${safeText(event.title)}</h2>
                <p>${safeText(formatDateTime(event.startAt, event.endAt))}</p>
              </div>
              <button class="public-icon-button" type="button" data-public-close-modal aria-label="Cerrar">${icon("close")}</button>
            </div>
            <p>${safeText(event.description || event.shortDescription)}</p>
            <div class="public-event-meta">
              <span>${icon(event.modality === "remote" ? "globe" : "map")} ${safeText(event.modality === "remote" ? event.remoteUrl || event.locationName || "En linea" : event.locationName || event.address || "Presencial")}</span>
              <span>${icon("users")} ${safeText(event.seats?.available || 0)} lugares disponibles de ${safeText(event.capacity || 0)}</span>
            </div>
            <div class="public-modal-actions">
              <button class="public-secondary-button" type="button" data-public-close-modal>Cerrar</button>
              ${reserved ? `<span class="public-event-status">Reservado</span>` : `<span class="public-event-action-stack"><button class="public-primary-button" type="button" data-public-action="reserve" data-event-id="${safeText(event.id)}">Reservar espacio</button><small>${safeText(eventPriceLabel(event))}</small></span>`}
            </div>
          </div>
        </section>
      </div>
    `;
  }

  function renderReservationModal(payload) {
    if (!appState.pendingReservationEventId) return "";
    const event = payload.events.find((item) => item.id === appState.pendingReservationEventId);
    if (!event) return "";
    const image = eventImage(event);
    const cost = eventCost(event);
    const balance = walletCredits(payload);
    const hasCredits = balance >= cost;
    const full = Number(event.seats?.available || 0) <= 0;
    const reservationLabel = full && event.allowWaitlist ? "Unirme a lista de espera" : "Confirmar reserva";
    return `
      <div class="public-modal-backdrop" data-public-reservation-backdrop>
        <section class="public-reservation-modal" role="dialog" aria-modal="true" aria-labelledby="publicReservationTitle">
          <div class="public-reservation-cover" style="--event-image:${cssImage(image)}"></div>
          <div class="public-reservation-body">
            <div class="public-panel-heading">
              <div>
                <span class="public-section-kicker">Reserva de evento</span>
                <h2 id="publicReservationTitle">Quieres reservar este evento?</h2>
              </div>
              <button class="public-icon-button" type="button" data-public-close-reservation aria-label="Cerrar">${icon("close")}</button>
            </div>
            <article class="public-reservation-summary">
              <h3>${safeText(event.title)}</h3>
              <p>${safeText(event.shortDescription || event.description)}</p>
              <div class="public-event-meta">
                <span>${icon("calendar")} ${safeText(formatDateTime(event.startAt, event.endAt))}</span>
                <span>${icon(event.modality === "remote" ? "globe" : "map")} ${safeText(event.modality === "remote" ? event.locationName || event.remoteUrl || "En linea" : event.locationName || event.address || "Presencial")}</span>
                <span>${icon("users")} ${safeText(event.seats?.available || 0)} lugares disponibles</span>
              </div>
            </article>
            <div class="public-reservation-wallet">
              <div><span>Precio</span><strong>${safeText(eventPriceLabel(event))}</strong></div>
              <div class="${hasCredits ? "" : "is-insufficient"}"><span>Tu billetera</span><strong>${safeText(creditLabel(balance))}</strong></div>
            </div>
            ${hasCredits ? "" : `<p class="public-reservation-warning">No tienes suficientes creditos para reservar este evento.</p>`}
            <div class="public-modal-actions">
              <button class="public-secondary-button" type="button" data-public-close-reservation>Cerrar</button>
              <button class="public-primary-button" type="button" data-public-action="confirm-reservation" data-event-id="${safeText(event.id)}" ${hasCredits ? "" : "disabled"}>${safeText(reservationLabel)}</button>
            </div>
          </div>
        </section>
      </div>
    `;
  }

  function renderEmpty(message) {
    return `<div class="public-empty">${safeText(message)}</div>`;
  }

  function scrollReviews(direction) {
    const carousel = document.querySelector("[data-public-review-carousel]");
    if (!carousel) return;
    const distance = Math.max(260, Math.round(carousel.clientWidth * 0.78));
    carousel.scrollBy({ left: direction === "left" ? -distance : distance, behavior: "smooth" });
  }

  function scrollMembers(direction) {
    const carousel = document.querySelector("[data-public-member-carousel]");
    if (!carousel) return;
    const distance = Math.max(230, Math.round(carousel.clientWidth * 0.72));
    carousel.scrollBy({ left: direction === "left" ? -distance : distance, behavior: "smooth" });
  }

  function scrollClasses(direction) {
    const carousel = document.querySelector("[data-public-class-carousel]");
    if (!carousel) return;
    const distance = Math.max(240, Math.round(carousel.clientWidth * 0.7));
    carousel.scrollBy({ left: direction === "left" ? -distance : distance, behavior: "smooth" });
  }

  function setupPanCarousel(selector) {
    const carousel = document.querySelector(selector);
    if (!carousel || carousel.dataset.panReady === "true") return;
    carousel.dataset.panReady = "true";
    let isDown = false;
    let startX = 0;
    let startScroll = 0;
    let moved = false;

    carousel.addEventListener("pointerdown", (event) => {
      isDown = true;
      moved = false;
      startX = event.clientX;
      startScroll = carousel.scrollLeft;
      carousel.classList.add("is-panning");
      carousel.setPointerCapture?.(event.pointerId);
    });

    carousel.addEventListener("pointermove", (event) => {
      if (!isDown) return;
      const delta = event.clientX - startX;
      if (Math.abs(delta) > 4) moved = true;
      carousel.scrollLeft = startScroll - delta;
    });

    ["pointerup", "pointercancel", "pointerleave"].forEach((eventName) => {
      carousel.addEventListener(eventName, (event) => {
        if (!isDown) return;
        isDown = false;
        carousel.classList.remove("is-panning");
        carousel.releasePointerCapture?.(event.pointerId);
        if (moved) {
          event.preventDefault?.();
        }
      });
    });
  }

  function setupReviewCarousel() {
    setupPanCarousel("[data-public-review-carousel]");
  }

  function setupMemberCarousel() {
    setupPanCarousel("[data-public-member-carousel]");
  }

  function setupClassCarousel() {
    setupPanCarousel("[data-public-class-carousel]");
  }

  function scrollToPublicSection(id) {
    requestAnimationFrame(() => {
      document.getElementById(id)?.scrollIntoView({ block: "start", behavior: "smooth" });
    });
  }

  function render() {
    if (!root) return;
    if (!bridge) {
      root.innerHTML = `<main class="public-page">${renderEmpty("No se pudo conectar con el modulo de comunidad.")}</main>`;
      return;
    }
    const payload = getPayload();
    if (!payload) {
      root.innerHTML = `<main class="public-page">${renderEmpty("No encontramos una comunidad publica disponible.")}</main>`;
      return;
    }
    appState.communityId = payload.community.id;
    document.title = `${payload.community.name} - Klini Wellness`;
    root.innerHTML = `
      <main class="public-page">
        ${renderHero(payload)}
        ${renderTabs(payload)}
        <section class="public-content">${renderContent(payload)}</section>
        ${renderCta(payload)}
        ${renderFooter(payload)}
      </main>
      ${renderModal(payload)}
      ${renderReservationModal(payload)}
    `;
    setupReviewCarousel();
    setupMemberCarousel();
    setupClassCarousel();
  }

  function perform(action, data = {}) {
    const result = bridge?.performPublicAction?.(action, { communityId: appState.communityId, ...data });
    showToast(result?.message || "Listo.");
    render();
  }

  document.addEventListener("click", (event) => {
    const sectionButton = event.target.closest("[data-public-section]");
    if (sectionButton) {
      const nextSection = sectionButton.dataset.publicSection || "home";
      if (nextSection === "posts") {
        appState.section = "home";
        appState.landingSection = "posts";
        appState.postsVisibleCount = 10;
        appState.activeEventId = "";
        appState.pendingReservationEventId = "";
        appState.membersExpanded = false;
        appState.membersVisibleCount = 10;
        appState.activeShareTarget = "";
        appState.activeCommentsPostId = "";
        render();
        scrollToPublicSection("publicPostsSection");
        return;
      }
      if (nextSection === "classes") {
        appState.section = "home";
        appState.landingSection = "classes";
        appState.activeEventId = "";
        appState.pendingReservationEventId = "";
        appState.membersExpanded = false;
        appState.membersVisibleCount = 10;
        appState.activeShareTarget = "";
        appState.activeCommentsPostId = "";
        render();
        scrollToPublicSection("publicClassesSection");
        return;
      }
      appState.section = nextSection;
      appState.landingSection = "";
      appState.activeEventId = "";
      appState.pendingReservationEventId = "";
      appState.membersExpanded = false;
      appState.membersVisibleCount = 10;
      appState.activeShareTarget = "";
      appState.activeCommentsPostId = "";
      render();
      window.scrollTo({ top: 0, behavior: "smooth" });
      return;
    }

    const openCommunity = event.target.closest("[data-public-open-community]");
    if (openCommunity) {
      appState.communityId = openCommunity.dataset.publicOpenCommunity;
      appState.section = "home";
      appState.landingSection = "";
      appState.query = "";
      appState.activeEventId = "";
      appState.pendingReservationEventId = "";
      appState.membersExpanded = false;
      appState.membersVisibleCount = 10;
      appState.activeShareTarget = "";
      appState.activeCommentsPostId = "";
      appState.postsVisibleCount = 10;
      appState.classesListMode = false;
      appState.eventFilters = { country: "", city: "", community: "", location: "", day: "" };
      const url = new URL(window.location.href);
      url.searchParams.set("community", appState.communityId);
      window.history.replaceState({}, "", url);
      render();
      window.scrollTo({ top: 0, behavior: "smooth" });
      return;
    }

    const closeModalButton = event.target.closest("[data-public-close-modal]");
    const closeModalBackdrop = event.target.matches("[data-public-modal-backdrop]");
    if (closeModalButton || closeModalBackdrop) {
      appState.activeEventId = "";
      render();
      return;
    }

    const closeReservationButton = event.target.closest("[data-public-close-reservation]");
    const closeReservationBackdrop = event.target.matches("[data-public-reservation-backdrop]");
    if (closeReservationButton || closeReservationBackdrop) {
      appState.pendingReservationEventId = "";
      render();
      return;
    }

    const reviewPanButton = event.target.closest("[data-public-review-pan]");
    if (reviewPanButton) {
      scrollReviews(reviewPanButton.dataset.publicReviewPan);
      return;
    }

    const memberPanButton = event.target.closest("[data-public-member-pan]");
    if (memberPanButton) {
      scrollMembers(memberPanButton.dataset.publicMemberPan);
      return;
    }

    const classPanButton = event.target.closest("[data-public-class-pan]");
    if (classPanButton) {
      scrollClasses(classPanButton.dataset.publicClassPan);
      return;
    }

    const actionButton = event.target.closest("[data-public-action]");
    if (!actionButton) return;
    const action = actionButton.dataset.publicAction;
    if (action === "join" || action === "leave") {
      perform(action);
      return;
    }
    if (action === "event-filter-toggle") {
      appState.eventFiltersOpen = !appState.eventFiltersOpen;
      render();
      return;
    }
    if (action === "event-filter-clear") {
      appState.eventFilters = { country: "", city: "", community: "", location: "", day: "" };
      appState.eventFiltersOpen = true;
      render();
      return;
    }
    if (action === "event-filter-field-clear") {
      const field = actionButton.dataset.filterField || "";
      if (["country", "city", "community", "location"].includes(field)) {
        appState.eventFilters = { ...(appState.eventFilters || {}), [field]: "" };
        appState.eventFiltersOpen = true;
        render();
      }
      return;
    }
    if (action === "event-filter-day") {
      const day = actionButton.dataset.publicEventDay || "";
      appState.eventFilters = { ...(appState.eventFilters || {}), day: appState.eventFilters?.day === day ? "" : day };
      appState.eventFiltersOpen = true;
      render();
      return;
    }
    if (action === "event-filter-next-day") {
      showToast("Desliza las fechas para ver mas dias.");
      return;
    }
    if (action === "event-filter-apply") {
      const form = actionButton.closest("[data-public-event-filter-form]");
      if (!form) return;
      const data = new FormData(form);
      appState.eventFilters = {
        country: String(data.get("country") || ""),
        city: String(data.get("city") || ""),
        community: String(data.get("community") || ""),
        location: String(data.get("location") || ""),
        day: appState.eventFilters?.day || ""
      };
      appState.eventFiltersOpen = true;
      render();
      return;
    }
    if (action === "class-filter-toggle") {
      appState.classFiltersOpen = !appState.classFiltersOpen;
      render();
      return;
    }
    if (action === "class-filter-clear") {
      appState.classFilters = { category: "", intensity: "", duration: "" };
      appState.classFiltersOpen = true;
      appState.classesListMode = false;
      render();
      scrollToPublicSection("publicClassesSection");
      return;
    }
    if (action === "class-filter-apply") {
      const form = actionButton.closest("[data-public-class-filter-form]");
      if (!form) return;
      const data = new FormData(form);
      appState.classFilters = {
        category: String(data.get("category") || ""),
        intensity: String(data.get("intensity") || ""),
        duration: String(data.get("duration") || "")
      };
      appState.classFiltersOpen = true;
      appState.classesListMode = true;
      render();
      scrollToPublicSection("publicClassesSection");
      return;
    }
    if (action === "review-all") {
      appState.reviewsExpanded = !appState.reviewsExpanded;
      render();
      if (appState.reviewsExpanded) {
        requestAnimationFrame(() => {
          document.getElementById("publicAllReviews")?.scrollIntoView({ block: "nearest", behavior: "smooth" });
        });
      }
      return;
    }
    if (action === "member-all") {
      appState.membersExpanded = true;
      appState.membersVisibleCount = Math.max(10, appState.membersVisibleCount || 10);
      render();
      requestAnimationFrame(() => {
        document.getElementById("publicAllMembers")?.scrollIntoView({ block: "nearest", behavior: "smooth" });
      });
      return;
    }
    if (action === "member-more") {
      appState.membersVisibleCount += 10;
      render();
      requestAnimationFrame(() => {
        document.getElementById("publicAllMembers")?.scrollIntoView({ block: "nearest", behavior: "smooth" });
      });
      return;
    }
    if (action === "member-hide") {
      appState.membersExpanded = false;
      appState.membersVisibleCount = 10;
      render();
      return;
    }
    if (action === "share-toggle") {
      const target = actionButton.dataset.publicShareTarget || "";
      appState.activeShareTarget = appState.activeShareTarget === target ? "" : target;
      appState.activeCommentsPostId = "";
      render();
      return;
    }
    if (action === "post-more") {
      appState.section = "home";
      appState.landingSection = "posts";
      appState.postsVisibleCount += 10;
      appState.activeShareTarget = "";
      appState.activeCommentsPostId = "";
      render();
      scrollToPublicSection("publicPostsSection");
      return;
    }
    if (action === "react") {
      const postId = actionButton.dataset.postId || "";
      if (postId) {
        appState.likedPosts[postId] = !appState.likedPosts[postId];
        if (!appState.likedPosts[postId]) delete appState.likedPosts[postId];
      }
      appState.activeShareTarget = "";
      render();
      return;
    }
    if (action === "comment") {
      const postId = actionButton.dataset.postId || "";
      appState.activeCommentsPostId = appState.activeCommentsPostId === postId ? "" : postId;
      appState.activeShareTarget = "";
      render();
      if (appState.activeCommentsPostId) {
        requestAnimationFrame(() => {
          document.getElementById(`public-comments-${postId}`)?.scrollIntoView({ block: "nearest", behavior: "smooth" });
        });
      }
      return;
    }
    if (action === "reserve") {
      appState.pendingReservationEventId = actionButton.dataset.eventId || "";
      appState.activeEventId = "";
      appState.activeShareTarget = "";
      appState.activeCommentsPostId = "";
      render();
      return;
    }
    if (action === "confirm-reservation") {
      const eventId = actionButton.dataset.eventId || appState.pendingReservationEventId;
      appState.pendingReservationEventId = "";
      perform("reserve", { eventId });
      return;
    }
    if (action === "cancel-reservation") {
      showToast("Solo el administrador puede cancelar eventos.");
      return;
    }
    if (action === "share") {
      const payload = getPayload();
      const shareUrl = `${window.location.origin}${window.location.pathname}?community=${encodeURIComponent(appState.communityId)}`;
      const text = `${payload?.community?.name || "Comunidad Klini Wellness"} - ${payload?.community?.shortDescription || ""}`;
      if (navigator.share) {
        navigator.share({ title: payload?.community?.name || "Comunidad Klini Wellness", text, url: shareUrl }).catch(() => showToast("Comparte cuando estes listo."));
      } else {
        navigator.clipboard?.writeText(shareUrl);
        showToast("Enlace copiado.");
      }
      return;
    }
    if (action === "share-dr-sam" || action === "share-instagram" || action === "share-facebook" || action === "share-tiktok") {
      const platform = { "share-dr-sam": "el feed de comunidad de Dr. Sam", "share-instagram": "Instagram", "share-facebook": "Facebook", "share-tiktok": "TikTok" }[action];
      const shareUrl = `${window.location.origin}${window.location.pathname}?community=${encodeURIComponent(appState.communityId)}`;
      navigator.clipboard?.writeText(shareUrl);
      appState.activeShareTarget = "";
      render();
      showToast(`Enlace listo para compartir en ${platform}.`);
      return;
    }
    showToast({
      "tool-info": "Herramienta preparada para el flujo completo.",
      "post-menu": "Opciones de publicacion abiertas.",
      "review-info": "Promedio basado en opiniones visibles de la comunidad.",
      "review-more": "Opinion expandida en el panel de opiniones.",
      "review-all": "Listado completo de opiniones preparado.",
      react: "Reaccion registrada.",
      comment: "Comentarios preparados."
    }[action] || "Accion preparada.");
  });

  document.addEventListener("submit", (event) => {
    const form = event.target.closest("[data-public-post-form]");
    if (!form) return;
    event.preventDefault();
    const body = String(new FormData(form).get("body") || "").trim();
    perform("create-post", { body });
    form.reset();
  });

  document.addEventListener("input", (event) => {
    const search = event.target.closest("[data-public-search]");
    if (!search) return;
    appState.query = search.value;
    appState.activeCommentsPostId = "";
    render();
    const nextSearch = document.querySelector("[data-public-search]");
    if (nextSearch) {
      nextSearch.focus();
      nextSearch.setSelectionRange(appState.query.length, appState.query.length);
    }
  });

  document.addEventListener("change", (event) => {
    const eventControl = event.target.closest("[data-public-event-filter-control]");
    if (!eventControl) return;
    const form = eventControl.closest("[data-public-event-filter-form]");
    if (!form) return;
    const data = new FormData(form);
    appState.eventFilters = {
      country: String(data.get("country") || ""),
      city: String(data.get("city") || ""),
      community: String(data.get("community") || ""),
      location: String(data.get("location") || ""),
      day: appState.eventFilters?.day || ""
    };
    appState.eventFiltersOpen = true;
    render();
  });

  window.addEventListener("storage", (event) => {
    if (String(event.key || "").startsWith("kliniPatientWellnessV1:")) render();
  });

  render();
})();
