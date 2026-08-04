(function () {
  "use strict";

  const WELLNESS_STORAGE_PREFIX = "kliniPatientWellnessV1:";
  const WELLNESS_SECTION_KEY = "kliniPatientWellnessActiveSection";
  const DEFAULT_PATIENT_ID = "100000002";
  const COMMUNITY_ADMIN_ID = "community-admin-jimmy";
  const COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID = "community-yoga-mente";
  const COMMUNITY_ADMIN_PROFILE_STORAGE_KEY = `${WELLNESS_STORAGE_PREFIX}${COMMUNITY_ADMIN_ID}:profile`;
  const COMMUNITY_ADMIN_PROFILE = {
    id: COMMUNITY_ADMIN_ID,
    fullName: "Jimmy Carter",
    role: "Administrador",
    title: "Creador de comunidad",
    email: "jimmy.carter@kliniwellness.demo",
    city: "Ciudad de Mexico",
    initials: "JC",
    joinedAt: "2026-06-01T10:00:00-06:00",
    plan: "Creator Pro"
  };

  const wellnessUi = {
    section: localStorage.getItem(WELLNESS_SECTION_KEY) || "today",
    period: "week",
    modal: "",
    quickMetric: "water_intake",
    detailMetric: "",
    goalId: "",
    communityView: "landing",
    communityId: "",
    adminCommunityId: "",
    adminModule: "dashboard",
    notificationsOpen: false,
    messagesOpen: false,
    activeAdminMessageId: "",
    activeAdminRequestId: "",
    adminProfileRequestId: "",
    eventId: "",
    shareContext: null,
    eventGroupVisible: {}
  };

  const sections = [
    { id: "today", label: "Hoy", icon: "spark" },
    { id: "wellbeing", label: "Rachas", icon: "medal" },
    { id: "communities", label: "Comunidades", icon: "users" },
    { id: "history", label: "Historial", icon: "history" }
  ];

  if (wellnessUi.section === "achievements" || !sections.some((section) => section.id === wellnessUi.section)) {
    wellnessUi.section = "wellbeing";
    localStorage.setItem(WELLNESS_SECTION_KEY, wellnessUi.section);
  }

  const metricCatalog = [
    { type: "water_intake", label: "Agua", category: "Hidratacion", unit: "L", target: 2, source: "Registro manual", icon: "drop", accent: "aqua" },
    { type: "sleep_hours", label: "Sueno", category: "Sueno", unit: "h", target: 8, min: 7, max: 9, source: "Reloj o wearable", icon: "moon", accent: "blue" },
    { type: "activity_steps", label: "Actividad", category: "Actividad", unit: "pasos", target: 8000, source: "Google Health Connect", icon: "walk", accent: "green" },
    { type: "medications_taken", label: "Medicamentos", category: "Medicamentos", unit: "%", target: 100, source: "Medicamento registrado", icon: "pill", accent: "teal" },
    { type: "energy_score", label: "Energia", category: "Energia", unit: "/5", target: 4, source: "Registro manual", icon: "bolt", accent: "gold" },
    { type: "stress_score", label: "Estres", category: "Estres", unit: "/5", target: 2, source: "Registro manual", icon: "brain", accent: "orange", inverse: true },
    { type: "nutrition_score", label: "Nutricion", category: "Nutricion", unit: "/5", target: 4, source: "Registro manual", icon: "leaf", accent: "green" },
    { type: "mental_health_score", label: "Salud mental", category: "Salud mental", unit: "/5", target: 4, source: "Registro manual", icon: "chat", accent: "purple" },
    { type: "weight_kg", label: "Peso", category: "Peso", unit: "kg", target: 76, source: "Registro manual", icon: "scale", accent: "blue" },
    { type: "blood_pressure_systolic", label: "Presion arterial", category: "Presion arterial", unit: "mmHg", target: 120, min: 90, max: 130, source: "Dispositivo conectado", icon: "pulse", accent: "red" },
    { type: "glucose_mgdl", label: "Glucosa", category: "Glucosa", unit: "mg/dL", target: 100, min: 70, max: 130, source: "Expediente medico", icon: "test", accent: "orange" },
    { type: "heart_rate", label: "Frecuencia cardiaca", category: "Frecuencia cardiaca", unit: "lpm", target: 72, min: 55, max: 100, source: "Apple Health", icon: "heart", accent: "red" },
    { type: "oxygen_saturation", label: "Oxigeno", category: "Saturacion de oxigeno", unit: "%", target: 96, min: 92, max: 100, source: "Reloj o wearable", icon: "oxygen", accent: "blue" },
    { type: "pain_score", label: "Dolor", category: "Dolor", unit: "/10", target: 2, source: "Registro manual", icon: "alert", accent: "orange", inverse: true },
    { type: "mood_score", label: "Estado de animo", category: "Estado de animo", unit: "/5", target: 4, source: "Registro manual", icon: "smile", accent: "purple" }
  ];

  const quickEntryOptions = [
    "mood_score",
    "water_intake",
    "sleep_hours",
    "activity_steps",
    "weight_kg",
    "blood_pressure_systolic",
    "glucose_mgdl",
    "medications_taken",
    "nutrition_score",
    "pain_score",
    "stress_score",
    "energy_score",
    "personal_note"
  ];

  const moodOptions = [
    { label: "Excelente", value: 5, accent: "great", icon: "spark" },
    { label: "Bien", value: 4, accent: "good", icon: "smile" },
    { label: "Normal", value: 3, accent: "stable", icon: "circle" },
    { label: "Cansado", value: 2, accent: "attention", icon: "moon" },
    { label: "Me siento mal", value: 1, accent: "risk", icon: "alert" }
  ];

  function safeText(value) {
    if (typeof escapeHtml === "function") return escapeHtml(value);
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function notify(message) {
    if (typeof showToast === "function") showToast(message);
  }

  function currentPatient() {
    if (typeof activePatientProfile === "function") return activePatientProfile();
    return { fullName: "Guillermo Guerrero Herzig", patientId: DEFAULT_PATIENT_ID, platformId: DEFAULT_PATIENT_ID };
  }

  function currentCommunityAdmin() {
    const storedProfile = parseStored(localStorage.getItem(COMMUNITY_ADMIN_PROFILE_STORAGE_KEY), {});
    return { ...COMMUNITY_ADMIN_PROFILE, ...storedProfile };
  }

  function saveCommunityAdminProfilePatch(patch) {
    const storedProfile = parseStored(localStorage.getItem(COMMUNITY_ADMIN_PROFILE_STORAGE_KEY), {});
    const nextProfile = { ...storedProfile };
    Object.entries(patch || {}).forEach(([key, value]) => {
      if (value === null || value === undefined || value === "") {
        delete nextProfile[key];
      } else {
        nextProfile[key] = value;
      }
    });
    localStorage.setItem(COMMUNITY_ADMIN_PROFILE_STORAGE_KEY, JSON.stringify(nextProfile));
    return nextProfile;
  }

  function patientId() {
    const patient = currentPatient();
    return String(patient?.platformId || patient?.patientId || DEFAULT_PATIENT_ID);
  }

  function storageKey() {
    return `${WELLNESS_STORAGE_PREFIX}${patientId()}`;
  }

  function parseStored(value, fallback) {
    try {
      return value ? JSON.parse(value) : fallback;
    } catch {
      return fallback;
    }
  }

  function dateOnly(date = new Date()) {
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 10);
  }

  function dateTimeValue(date = new Date()) {
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
    return local.toISOString().slice(0, 16);
  }

  function dateOffset(days, hour = 8, minute = 0) {
    const date = new Date();
    date.setDate(date.getDate() + days);
    date.setHours(hour, minute, 0, 0);
    return date.toISOString();
  }

  function wellnessId(prefix) {
    return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
  }

  function metricConfig(type) {
    return metricCatalog.find((item) => item.type === type) || {
      type,
      label: type,
      unit: "",
      target: 1,
      source: "Registro manual",
      icon: "circle",
      accent: "teal"
    };
  }

  function icon(name) {
    const paths = {
      spark: '<path d="M12 2l1.6 5.1L19 9l-5.4 1.9L12 16l-1.6-5.1L5 9l5.4-1.9L12 2z"></path><path d="M19 14l.8 2.4L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.6L19 14z"></path>',
      home: '<path d="M3 11.5 12 4l9 7.5"></path><path d="M5.5 10.5V20h13v-9.5"></path><path d="M9.5 20v-5h5v5"></path>',
      heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"></path>',
      target: '<circle cx="12" cy="12" r="9"></circle><circle cx="12" cy="12" r="5"></circle><circle cx="12" cy="12" r="1.6"></circle>',
      medal: '<path d="M8 2h8l-2 6h-4L8 2z"></path><circle cx="12" cy="15" r="5"></circle><path d="M12 12v6"></path><path d="M9.5 15h5"></path>',
      history: '<path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 4v5h5"></path><path d="M12 7v5l3 2"></path>',
      drop: '<path d="M12 2s6 6.1 6 11a6 6 0 0 1-12 0c0-4.9 6-11 6-11z"></path>',
      moon: '<path d="M21 13a8 8 0 1 1-10-10 7 7 0 0 0 10 10z"></path>',
      walk: '<circle cx="12" cy="5" r="2"></circle><path d="M10 22l2-7"></path><path d="M16 22l-3-7"></path><path d="M8 11l3-2 3 2 2 4"></path>',
      pill: '<path d="M10 21a5 5 0 0 1-7-7l7-7a5 5 0 0 1 7 7l-7 7z"></path><path d="M8 8l8 8"></path>',
      bolt: '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8z"></path>',
      brain: '<path d="M9 5a3 3 0 0 1 6 0"></path><path d="M7 8a3 3 0 0 0-1 5.8A3.5 3.5 0 0 0 10 19h4a3.5 3.5 0 0 0 4-5.2A3 3 0 0 0 17 8"></path><path d="M12 5v14"></path>',
      leaf: '<path d="M5 21c8-2 14-8 14-18-10 0-16 6-14 18z"></path><path d="M5 21c3-5 7-8 12-10"></path>',
      chat: '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>',
      scale: '<path d="M6 3h12l3 7H3l3-7z"></path><path d="M7 10v11h10V10"></path><path d="M12 14v3"></path>',
      pulse: '<path d="M4 13h4l2-6 4 12 2-6h4"></path>',
      test: '<path d="M9 3h6"></path><path d="M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4a2 2 0 0 0 1.8-3l-5-9V3"></path><path d="M8 15h8"></path>',
      oxygen: '<circle cx="9" cy="12" r="5"></circle><path d="M15 19h5"></path><path d="M17.5 16.5V21"></path>',
      alert: '<path d="M12 2 2 20h20L12 2z"></path><path d="M12 8v5"></path><path d="M12 17h.01"></path>',
      smile: '<circle cx="12" cy="12" r="9"></circle><path d="M8 14s1.5 2 4 2 4-2 4-2"></path><path d="M9 9h.01"></path><path d="M15 9h.01"></path>',
      circle: '<circle cx="12" cy="12" r="8"></circle>',
      plus: '<path d="M12 5v14"></path><path d="M5 12h14"></path>',
      bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path>',
      send: '<path d="M22 2 11 13"></path><path d="M22 2 15 22l-4-9-9-4 20-7z"></path>',
      share: '<circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><path d="M8.6 10.6l6.8-4.2"></path><path d="M8.6 13.4l6.8 4.2"></path>',
      shield: '<path d="M12 3l7 3v5c0 4.5-2.8 8.4-7 10-4.2-1.6-7-5.5-7-10V6l7-3z"></path><path d="M9 12l2 2 4-5"></path>',
      gift: '<path d="M20 12v8H4v-8"></path><path d="M2 7h20v5H2z"></path><path d="M12 7v13"></path><path d="M12 7H8a2 2 0 1 1 2-2c0 2 2 2 2 2z"></path><path d="M12 7h4a2 2 0 1 0-2-2c0 2-2 2-2 2z"></path>',
      lock: '<rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path>',
      camera: '<path d="M4 7h4l2-3h4l2 3h4v13H4z"></path><circle cx="12" cy="13" r="3"></circle>',
      "chevron-left": '<path d="M15 18l-6-6 6-6"></path>',
      "chevron-right": '<path d="M9 18l6-6-6-6"></path>',
      users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.9"></path><path d="M16 3.1a4 4 0 0 1 0 7.8"></path>',
      "user-plus": '<path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8" cy="7" r="4"></circle><path d="M19 8v6"></path><path d="M16 11h6"></path>',
      map: '<path d="M12 21s7-4.4 7-11a7 7 0 1 0-14 0c0 6.6 7 11 7 11z"></path><circle cx="12" cy="10" r="2.5"></circle>',
      calendar: '<rect x="3" y="4" width="18" height="17" rx="3"></rect><path d="M8 2v4"></path><path d="M16 2v4"></path><path d="M3 10h18"></path>',
      video: '<rect x="3" y="6" width="13" height="12" rx="2"></rect><path d="M16 10l5-3v10l-5-3"></path>',
      phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.4 2.1L8.1 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.9.6 2.9.7A2 2 0 0 1 22 16.9z"></path>',
      mic: '<path d="M12 3a3 3 0 0 0-3 3v6a3 3 0 0 0 6 0V6a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><path d="M12 19v3"></path><path d="M8 22h8"></path>',
      image: '<rect x="3" y="5" width="18" height="14" rx="2"></rect><circle cx="8.5" cy="10" r="1.5"></circle><path d="M21 15l-5-5L5 19"></path>',
      search: '<circle cx="11" cy="11" r="7"></circle><path d="M21 21l-4.3-4.3"></path>',
      ticket: '<path d="M4 5h16v5a2 2 0 0 0 0 4v5H4v-5a2 2 0 0 0 0-4V5z"></path><path d="M13 5v14"></path>',
      globe: '<circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3a14 14 0 0 1 0 18"></path><path d="M12 3a14 14 0 0 0 0 18"></path>',
      clock: '<circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path>',
      edit: '<path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>',
      check: '<path d="M20 6 9 17l-5-5"></path>',
      x: '<path d="M18 6 6 18"></path><path d="M6 6l12 12"></path>',
      trash: '<path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 15H6L5 6"></path><path d="M10 11v6"></path><path d="M14 11v6"></path>'
    };
    return `<svg viewBox="0 0 24 24" aria-hidden="true">${paths[name] || paths.circle}</svg>`;
  }

  function formatLongDate(value = new Date()) {
    return new Date(value).toLocaleDateString("es-MX", {
      weekday: "long",
      day: "2-digit",
      month: "long",
      year: "numeric"
    });
  }

  function formatMetricTime(value) {
    return new Date(value).toLocaleString("es-MX", {
      day: "2-digit",
      month: "short",
      hour: "2-digit",
      minute: "2-digit"
    });
  }

  function firstName(fullName) {
    return String(fullName || "Paciente").trim().split(/\s+/)[0] || "Paciente";
  }

  function initialsText(name) {
    if (typeof initials === "function") return initials(name);
    return String(name || "GG")
      .split(/\s+/)
      .map((part) => part[0])
      .join("")
      .slice(0, 2)
      .toUpperCase();
  }

  function seedWellnessState() {
    const patient = currentPatient();
    const id = patientId();
    const metrics = [];
    const add = (days, type, value, unit, source, notes, metadata = {}) => {
      const config = metricConfig(type);
      metrics.push({
        id: wellnessId("metric"),
        patientId: id,
        type,
        value,
        unit: unit || config.unit || "",
        recordedAt: dateOffset(days, 8 + Math.abs(days % 5), 15),
        source: source || config.source || "manual",
        notes: notes || "",
        metadata
      });
    };

    const water = [2.1, 1.8, 2.3, 2, 2.4, 2.2, 1.9];
    const sleep = [7.4, 6.8, 7.9, 8.1, 7.1, 6.5, 7.6];
    const steps = [8200, 6400, 9100, 7600, 8400, 5300, 8800];
    for (let index = 0; index < 7; index += 1) {
      const offset = -6 + index;
      add(offset, "water_intake", water[index], "L", "manual");
      add(offset, "sleep_hours", sleep[index], "h", "device");
      add(offset, "activity_steps", steps[index], "pasos", "integration");
      add(offset, "medications_taken", index === 1 ? 75 : 100, "%", "medical_record");
      add(offset, "energy_score", [4, 3, 4, 4, 5, 3, 4][index], "/5", "manual");
      add(offset, "stress_score", [2, 3, 2, 2, 1, 3, 2][index], "/5", "manual");
      add(offset, "nutrition_score", [4, 3, 4, 3, 4, 3, 4][index], "/5", "manual");
      add(offset, "mental_health_score", [4, 4, 3, 4, 5, 3, 4][index], "/5", "manual");
    }

    add(0, "weight_kg", 76.4, "kg", "manual");
    add(0, "blood_pressure_systolic", 126, "mmHg", "device", "", { diastolic: 82 });
    add(-2, "blood_pressure_systolic", 132, "mmHg", "device", "Lectura ligeramente arriba del rango configurado.", { diastolic: 86 });
    add(0, "glucose_mgdl", 104, "mg/dL", "medical_record");
    add(-1, "heart_rate", 76, "lpm", "integration");
    add(0, "oxygen_saturation", 97, "%", "device");
    add(0, "pain_score", 1, "/10", "manual");
    add(0, "mood_score", 4, "/5", "manual", "Me siento bien.");

    const today = dateOnly();
    return {
      schemaVersion: 1,
      patientId: id,
      patientName: patient?.fullName || "Paciente",
      walletCredits: 120,
      selectedMood: "Bien",
      moodRecordedAt: dateOffset(0, 7, 45),
      metrics,
      goals: [
        {
          id: "goal-water-weekly",
          patientId: id,
          title: "Tomar 2 litros de agua",
          description: "Cumplir al menos cinco dias por semana.",
          category: "Hidratacion",
          metricType: "water_intake",
          targetValue: 2,
          targetUnit: "L",
          comparisonOperator: "greater_than_or_equal",
          periodType: "weekly",
          requiredOccurrences: 5,
          minimumCompliancePercentage: 100,
          streakRequired: 0,
          startDate: dateOffset(-14),
          endDate: "",
          status: "active",
          privacyLevel: "shareable",
          createdBy: "patient"
        },
        {
          id: "goal-medication-30",
          patientId: id,
          title: "Tomar medicamentos indicados",
          description: "Mantener adherencia diaria durante 30 dias.",
          category: "Medicamentos",
          metricType: "medications_taken",
          targetValue: 100,
          targetUnit: "%",
          comparisonOperator: "greater_than_or_equal",
          periodType: "monthly",
          requiredOccurrences: 30,
          minimumCompliancePercentage: 90,
          streakRequired: 7,
          startDate: dateOffset(-6),
          status: "active",
          privacyLevel: "care_team",
          createdBy: "doctor"
        },
        {
          id: "goal-pressure",
          patientId: id,
          title: "Registrar presion arterial",
          description: "Cuatro registros semanales y seguimiento del rango configurado.",
          category: "Presion arterial",
          metricType: "blood_pressure_systolic",
          targetValue: 4,
          targetUnit: "registros",
          comparisonOperator: "number_of_records",
          periodType: "weekly",
          requiredOccurrences: 4,
          minimumCompliancePercentage: 100,
          validRangeMin: 90,
          validRangeMax: 130,
          startDate: dateOffset(-14),
          status: "active",
          privacyLevel: "care_team",
          createdBy: "doctor"
        },
        {
          id: "goal-sleep-range",
          patientId: id,
          title: "Dormir entre 7 y 9 horas",
          description: "Buscar constancia sin usarlo como diagnostico.",
          category: "Sueno",
          metricType: "sleep_hours",
          comparisonOperator: "within_range",
          periodType: "weekly",
          requiredOccurrences: 5,
          validRangeMin: 7,
          validRangeMax: 9,
          startDate: dateOffset(-14),
          status: "active",
          privacyLevel: "private",
          createdBy: "patient"
        }
      ],
      goalProgress: [],
      achievements: achievementCatalog(),
      patientAchievements: [],
      rewards: rewardCatalog(today),
      alerts: [
        {
          id: "alert-pressure-followup",
          patientId: id,
          type: "blood_pressure_systolic",
          severity: "attention",
          title: "Resultado fuera del rango configurado",
          message: "Una lectura de presion arterial estuvo arriba del rango configurado. Considera compartirla con tu profesional de salud.",
          createdAt: dateOffset(-2, 19, 20),
          status: "open"
        }
      ],
      privacy: {
        shareOnlyAchievement: true,
        includePercentage: true,
        includeStreak: true,
        includeName: false,
        includePhoto: false,
        hideMedicalCategory: true
      },
      settings: {
        reminders: true,
        reminderTime: "08:30",
        quietMode: false
      },
      integrations: [
        { id: "apple-health", name: "Apple Health", status: "Conectado", lastSync: dateOffset(0, 6, 55) },
        { id: "google-health-connect", name: "Google Health Connect", status: "Disponible", lastSync: "" },
        { id: "wearable", name: "Reloj o wearable", status: "Conectado", lastSync: dateOffset(0, 7, 10) }
      ]
    };
  }

  function achievementCatalog() {
    return [
      {
        id: "ach-first-record",
        code: "first_record",
        title: "Primer registro",
        description: "Comenzaste tu seguimiento personal en Wellness.",
        category: "Constancia",
        level: "initial",
        icon: "spark",
        criteria: { minRecords: 1 },
        shareTemplateId: "basic"
      },
      {
        id: "ach-water-week",
        code: "well_hydrated_week",
        title: "Semana bien hidratada",
        description: "Cumpliste cinco dias de hidratacion en la semana.",
        category: "Hidratacion",
        level: "silver",
        icon: "drop",
        criteria: { goalId: "goal-water-weekly", minimumCompliancePercentage: 100 },
        shareTemplateId: "wellness"
      },
      {
        id: "ach-med-streak",
        code: "medication_streak_7",
        title: "7 dias de adherencia",
        description: "Confirmaste tus medicamentos durante una racha semanal.",
        category: "Medicamentos",
        level: "bronze",
        icon: "pill",
        criteria: { goalId: "goal-medication-30", streakRequired: 7 },
        shareTemplateId: "wellness"
      },
      {
        id: "ach-pressure-followup",
        code: "pressure_records",
        title: "Seguimiento constante",
        description: "Completaste registros de presion para revisar tendencias.",
        category: "Seguimiento medico",
        level: "bronze",
        icon: "pulse",
        criteria: { goalId: "goal-pressure", completedOccurrences: 4 },
        shareTemplateId: "medical-followup"
      },
      {
        id: "ach-monthly-progress",
        code: "monthly_90",
        title: "Mes en progreso",
        description: "Alcanza 90% de cumplimiento mensual para desbloquearlo.",
        category: "Objetivos personalizados",
        level: "gold",
        icon: "medal",
        criteria: { overallCompliance: 90 },
        shareTemplateId: "summary"
      }
    ];
  }

  function rewardCatalog(today) {
    return [
      {
        id: "reward-lab-discount",
        title: "Descuento en analisis de laboratorio",
        description: "Beneficio aplicable con proveedores autorizados.",
        provider: "Klini Labs",
        achievementRequirement: "ach-water-week",
        pointsRequired: 0,
        validFrom: today,
        validUntil: "2026-08-31",
        redemptionType: "coupon",
        code: "WELL-LAB-10",
        status: "active"
      },
      {
        id: "reward-nutrition-session",
        title: "Sesion de nutricion",
        description: "Orientacion general para reforzar habitos de bienestar.",
        provider: "Red Klini",
        achievementRequirement: "ach-monthly-progress",
        pointsRequired: 120,
        validFrom: today,
        validUntil: "2026-09-15",
        redemptionType: "link",
        code: "NUTRI-KLINI",
        status: "inactive"
      }
    ];
  }

  function communitySeed(id) {
    const now = new Date().toISOString();
    return {
      communities: [
        {
          id: "community-yoga-mente",
          ownerId: COMMUNITY_ADMIN_ID,
          name: "Respira y Avanza",
          slug: "respira-y-avanza",
          shortDescription: "Yoga suave, respiracion y constancia semanal.",
          description: "Comunidad para pacientes que buscan construir habitos de calma, movilidad y respiracion consciente con acompanamiento respetuoso.",
          category: "Yoga",
          coverTone: "mint",
          logoImage: "",
          logoInitials: "RA",
          backgroundImage: "",
          accessType: "open",
          visibility: "public",
          city: "Ciudad de Mexico",
          region: "CDMX",
          language: "Espanol",
          memberCount: 128,
          status: "active",
          allowMemberPosts: true,
          rules: "Mantener respeto, compartir experiencias de bienestar y evitar recomendaciones medicas sin supervision profesional.",
          pinnedMessage: "Bienvenidos a Respira y Avanza. Revisa los eventos de la semana y reserva tu lugar con anticipacion.",
          createdAt: "2026-06-01T10:00:00-06:00",
          updatedAt: now
        },
        {
          id: "community-running-cardio",
          ownerId: "100000071",
          name: "Ritmo Cardiometabolico",
          slug: "ritmo-cardiometabolico",
          shortDescription: "Caminatas, running ligero y metas medicas seguras.",
          description: "Grupo de seguimiento para mejorar actividad fisica, compartir avances y organizar sesiones presenciales con enfoque preventivo.",
          category: "Running",
          coverTone: "aqua",
          logoImage: "",
          logoInitials: "RC",
          backgroundImage: "",
          accessType: "closed",
          visibility: "public",
          city: "Ciudad de Mexico",
          region: "CDMX",
          language: "Espanol",
          memberCount: 86,
          status: "active",
          createdAt: "2026-05-18T09:30:00-06:00",
          updatedAt: now
        },
        {
          id: "community-nutricion-inteligente",
          ownerId: id,
          name: "Nutricion Inteligente Klini",
          slug: "nutricion-inteligente-klini",
          shortDescription: "Planificacion, adherencia y recetas saludables.",
          description: "Espacio para organizar objetivos de nutricion, revisar progreso semanal y compartir sesiones educativas.",
          category: "Nutricion",
          coverTone: "gold",
          logoImage: "",
          logoInitials: "NK",
          backgroundImage: "",
          accessType: "closed",
          visibility: "public",
          city: "Ciudad de Mexico",
          region: "CDMX",
          language: "Espanol",
          memberCount: 42,
          status: "active",
          createdAt: "2026-07-02T13:00:00-06:00",
          updatedAt: now
        },
        {
          id: "community-mental-balance",
          ownerId: "100000119",
          name: "Mente en Equilibrio",
          slug: "mente-en-equilibrio",
          shortDescription: "Salud mental, meditacion y apoyo emocional.",
          description: "Comunidad cerrada con eventos remotos, ejercicios de respiracion y conversaciones moderadas.",
          category: "Salud mental",
          coverTone: "purple",
          logoImage: "",
          logoInitials: "ME",
          backgroundImage: "",
          accessType: "closed",
          visibility: "public",
          city: "Ciudad de Mexico",
          region: "CDMX",
          language: "Espanol",
          memberCount: 64,
          status: "active",
          createdAt: "2026-06-12T18:00:00-06:00",
          updatedAt: now
        }
      ],
      communityMembers: [
        { id: "member-yoga-patient", communityId: "community-yoga-mente", userId: id, role: "member", status: "active", joinedAt: "2026-07-08T08:00:00-06:00" },
        { id: "member-nutrition-owner", communityId: "community-nutricion-inteligente", userId: id, role: "owner", status: "active", joinedAt: "2026-07-02T13:00:00-06:00" },
        { id: "member-yoga-admin", communityId: "community-yoga-mente", userId: COMMUNITY_ADMIN_ID, role: "owner", status: "active", joinedAt: "2026-06-01T10:00:00-06:00" },
        { id: "member-yoga-ana", communityId: "community-yoga-mente", userId: "100000088", role: "creator", status: "active", joinedAt: "2026-06-01T10:00:00-06:00" },
        { id: "member-yoga-diego", communityId: "community-yoga-mente", userId: "100000071", role: "admin", status: "active", joinedAt: "2026-06-09T10:00:00-06:00" },
        { id: "member-yoga-mariana", communityId: "community-yoga-mente", userId: "100000119", role: "moderator", status: "active", joinedAt: "2026-06-18T10:00:00-06:00" },
        { id: "member-yoga-carlos", communityId: "community-yoga-mente", userId: "100000207", role: "moderator", status: "active", joinedAt: "2026-07-02T10:00:00-06:00" },
        { id: "member-yoga-laura", communityId: "community-yoga-mente", userId: "100000218", role: "member", status: "invited", joinedAt: "2026-07-10T10:00:00-06:00" },
        { id: "member-running-owner", communityId: "community-running-cardio", userId: "100000071", role: "owner", status: "active", joinedAt: "2026-05-18T09:30:00-06:00" }
      ],
      communityJoinRequests: [
        {
          id: "request-yoga-mariana",
          communityId: "community-yoga-mente",
          userId: "100000305",
          userName: "Mariana Lopez",
          userInitials: "ML",
          brief: "Solicitud para unirse a la comunidad.",
          sharedInterests: ["Yoga", "Respiracion"],
          status: "pending",
          requestedAt: "2026-07-21T09:15:00-06:00"
        },
        {
          id: "request-yoga-diego",
          communityId: "community-yoga-mente",
          userId: "100000306",
          userName: "Diego Ramirez",
          userInitials: "DR",
          brief: "Quiere participar en eventos presenciales.",
          sharedInterests: ["Yoga", "Bienestar"],
          status: "pending",
          requestedAt: "2026-07-21T08:42:00-06:00"
        },
        {
          id: "request-yoga-ana",
          communityId: "community-yoga-mente",
          userId: "100000307",
          userName: "Ana Sofia Ruiz",
          userInitials: "AS",
          brief: "Busca sesiones suaves de respiracion.",
          sharedInterests: ["Respiracion", "Movilidad"],
          status: "pending",
          requestedAt: "2026-07-20T17:30:00-06:00"
        },
        {
          id: "request-nutrition-ana",
          communityId: "community-nutricion-inteligente",
          userId: "100000305",
          userName: "Ana Sofia Martinez",
          userInitials: "AM",
          brief: "Interesada en apego nutricional y recetas saludables.",
          sharedInterests: ["Nutricion", "Habitos saludables"],
          status: "pending",
          requestedAt: "2026-07-19T18:20:00-06:00"
        },
        {
          id: "request-running-patient",
          communityId: "community-running-cardio",
          userId: id,
          userName: currentPatient()?.fullName || "Paciente",
          userInitials: initialsText(currentPatient()?.fullName),
          brief: "Busca caminatas guiadas y eventos presenciales.",
          sharedInterests: ["Running", "Prevencion"],
          status: "pending",
          requestedAt: "2026-07-18T12:15:00-06:00"
        }
      ],
      communityEvents: [
        {
          id: "event-yoga-breathing",
          communityId: "community-yoga-mente",
          creatorId: "100000088",
          title: "Respiracion y movilidad suave",
          shortDescription: "Sesion presencial para iniciar la semana con movimiento ligero.",
          description: "Clase guiada de respiracion, movilidad articular y cierre con meditacion breve. No sustituye indicaciones medicas.",
          category: "Yoga",
          coverTone: "mint",
          modality: "in_person",
          locationName: "Casa Klini Roma Norte",
          address: "Col. Roma Norte, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-07-22T19:00:00-06:00",
          endAt: "2026-07-22T20:00:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 18,
          allowWaitlist: true,
          bookingDeadline: "2026-07-22T16:00:00-06:00",
          status: "published"
        },
        {
          id: "event-yoga-meditation",
          communityId: "community-yoga-mente",
          creatorId: COMMUNITY_ADMIN_ID,
          title: "Meditacion guiada al amanecer",
          shortDescription: "Respiracion consciente para iniciar el dia.",
          description: "Sesion guiada de respiracion y meditacion suave, ideal para personas que buscan calma y constancia.",
          category: "Meditacion",
          coverTone: "aqua",
          modality: "remote",
          locationName: "Sala virtual Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/meditacion-amanecer",
          startAt: "2026-07-25T07:30:00-06:00",
          endAt: "2026-07-25T08:15:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 50,
          cost: 300,
          allowWaitlist: true,
          bookingDeadline: "2026-07-24T20:00:00-06:00",
          status: "published"
        },
        {
          id: "event-running-park",
          communityId: "community-running-cardio",
          creatorId: "100000071",
          title: "Caminata metabolica",
          shortDescription: "Ruta ligera con control de esfuerzo percibido.",
          description: "Encuentro presencial de caminata guiada. Lleva agua y tenis comodos. Si tienes sintomas, consulta a tu medico.",
          category: "Running",
          coverTone: "aqua",
          modality: "in_person",
          locationName: "Bosque de Chapultepec",
          address: "Puerta de Leones, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-07-24T08:00:00-06:00",
          endAt: "2026-07-24T09:15:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 24,
          allowWaitlist: true,
          bookingDeadline: "2026-07-23T20:00:00-06:00",
          status: "published"
        },
        {
          id: "event-yoga-posture",
          communityId: "community-yoga-mente",
          creatorId: COMMUNITY_ADMIN_ID,
          title: "Postura y respiracion",
          shortDescription: "Practica suave para mejorar movilidad y conciencia corporal.",
          description: "Sesion presencial de yoga terapeutico con enfoque preventivo y adaptaciones sencillas.",
          category: "Yoga",
          coverTone: "mint",
          modality: "in_person",
          locationName: "Casa Klini Condesa",
          address: "Col. Condesa, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-07-23T18:00:00-06:00",
          endAt: "2026-07-23T19:00:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 20,
          allowWaitlist: true,
          bookingDeadline: "2026-07-23T15:00:00-06:00",
          status: "published"
        },
        {
          id: "event-nutrition-labels",
          communityId: "community-nutricion-inteligente",
          creatorId: id,
          title: "Lectura de etiquetas",
          shortDescription: "Aprende a identificar informacion util al comprar alimentos.",
          description: "Sesion practica para revisar porciones, sodio, azucares y recomendaciones generales.",
          category: "Nutricion",
          coverTone: "gold",
          modality: "remote",
          locationName: "Sala virtual Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/lectura-etiquetas",
          startAt: "2026-07-24T17:30:00-06:00",
          endAt: "2026-07-24T18:15:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 35,
          allowWaitlist: true,
          bookingDeadline: "2026-07-24T16:00:00-06:00",
          status: "published"
        },
        {
          id: "event-running-balance",
          communityId: "community-running-cardio",
          creatorId: "100000071",
          title: "Balance y movilidad",
          shortDescription: "Sesion ligera para preparar articulaciones antes de caminar.",
          description: "Encuentro presencial con movilidad articular, respiracion y recomendaciones generales de calentamiento.",
          category: "Running",
          coverTone: "aqua",
          modality: "in_person",
          locationName: "Parque Lincoln",
          address: "Polanco, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-07-26T08:30:00-06:00",
          endAt: "2026-07-26T09:20:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 20,
          allowWaitlist: true,
          bookingDeadline: "2026-07-25T19:00:00-06:00",
          status: "published"
        },
        {
          id: "event-nutrition-remote",
          communityId: "community-nutricion-inteligente",
          creatorId: id,
          title: "Menu semanal practico",
          shortDescription: "Planeacion sencilla para adherencia alimentaria.",
          description: "Sesion remota para organizar menu semanal y compras basicas con opciones saludables.",
          category: "Nutricion",
          coverTone: "gold",
          modality: "remote",
          locationName: "Zoom Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/menu-semanal",
          startAt: "2026-07-28T18:30:00-06:00",
          endAt: "2026-07-28T19:15:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 40,
          allowWaitlist: false,
          bookingDeadline: "2026-07-28T17:00:00-06:00",
          status: "published"
        },
        {
          id: "event-mental-sleep",
          communityId: "community-mental-balance",
          creatorId: "100000119",
          title: "Rutina de descanso",
          shortDescription: "Tecnicas generales para preparar el sueno.",
          description: "Taller remoto con recomendaciones de higiene del sueno y seguimiento de habitos.",
          category: "Salud mental",
          coverTone: "purple",
          modality: "remote",
          locationName: "Sala virtual Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/rutina-descanso",
          startAt: "2026-07-29T20:00:00-06:00",
          endAt: "2026-07-29T20:45:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 30,
          allowWaitlist: true,
          bookingDeadline: "2026-07-29T18:00:00-06:00",
          status: "published"
        },
        {
          id: "event-running-strength",
          communityId: "community-running-cardio",
          creatorId: "100000071",
          title: "Fuerza para caminata",
          shortDescription: "Ejercicios basicos de fuerza y estabilidad.",
          description: "Clase presencial de baja intensidad para apoyar rutinas de caminata segura.",
          category: "Running",
          coverTone: "aqua",
          modality: "in_person",
          locationName: "Parque Mexico",
          address: "Hipodromo, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-07-30T07:30:00-06:00",
          endAt: "2026-07-30T08:30:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 22,
          allowWaitlist: true,
          bookingDeadline: "2026-07-29T20:00:00-06:00",
          status: "published"
        },
        {
          id: "event-yoga-energy",
          communityId: "community-yoga-mente",
          creatorId: COMMUNITY_ADMIN_ID,
          title: "Energia y enfoque",
          shortDescription: "Movilidad ligera para iniciar el dia.",
          description: "Practica breve de respiracion, estiramientos y presencia corporal.",
          category: "Yoga",
          coverTone: "mint",
          modality: "in_person",
          locationName: "Casa Klini Roma Norte",
          address: "Col. Roma Norte, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-07-31T08:00:00-06:00",
          endAt: "2026-07-31T08:50:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 18,
          allowWaitlist: true,
          bookingDeadline: "2026-07-30T18:00:00-06:00",
          status: "published"
        },
        {
          id: "event-nutrition-breakfast",
          communityId: "community-nutricion-inteligente",
          creatorId: id,
          title: "Desayunos practicos",
          shortDescription: "Ideas sencillas para mantener adherencia.",
          description: "Sesion educativa con opciones de desayuno y planeacion semanal.",
          category: "Nutricion",
          coverTone: "gold",
          modality: "remote",
          locationName: "Sala virtual Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/desayunos-practicos",
          startAt: "2026-08-01T09:00:00-06:00",
          endAt: "2026-08-01T09:45:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 40,
          allowWaitlist: false,
          bookingDeadline: "2026-07-31T20:00:00-06:00",
          status: "published"
        },
        {
          id: "event-mental-journal",
          communityId: "community-mental-balance",
          creatorId: "100000119",
          title: "Diario emocional",
          shortDescription: "Herramientas para registrar emociones sin juicio.",
          description: "Taller remoto para organizar notas personales y detectar patrones de bienestar.",
          category: "Salud mental",
          coverTone: "purple",
          modality: "remote",
          locationName: "Sala virtual Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/diario-emocional",
          startAt: "2026-08-02T18:00:00-06:00",
          endAt: "2026-08-02T18:45:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 25,
          allowWaitlist: true,
          bookingDeadline: "2026-08-02T15:00:00-06:00",
          status: "published"
        },
        {
          id: "event-mindfulness",
          communityId: "community-mental-balance",
          creatorId: "100000119",
          title: "Pausa consciente",
          shortDescription: "Meditacion guiada y herramientas de calma.",
          description: "Practica remota para revisar respiracion y tension. En crisis o sintomas graves solicita atencion inmediata.",
          category: "Meditacion",
          coverTone: "purple",
          modality: "remote",
          locationName: "Sala virtual Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/pausa-consciente",
          startAt: "2026-08-03T20:00:00-06:00",
          endAt: "2026-08-03T20:45:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 12,
          allowWaitlist: true,
          bookingDeadline: "2026-08-03T18:00:00-06:00",
          status: "published"
        },
        {
          id: "event-yoga-restorative",
          communityId: "community-yoga-mente",
          creatorId: COMMUNITY_ADMIN_ID,
          title: "Yoga restaurativo",
          shortDescription: "Sesion suave para recuperacion y descanso.",
          description: "Practica presencial con movimientos lentos, respiracion y cierre de relajacion.",
          category: "Yoga",
          coverTone: "mint",
          modality: "in_person",
          locationName: "Casa Klini Roma Norte",
          address: "Col. Roma Norte, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-08-05T19:00:00-06:00",
          endAt: "2026-08-05T20:00:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 16,
          allowWaitlist: true,
          bookingDeadline: "2026-08-05T16:00:00-06:00",
          status: "published"
        },
        {
          id: "event-running-hills",
          communityId: "community-running-cardio",
          creatorId: "100000071",
          title: "Caminata con inclinacion",
          shortDescription: "Ruta moderada con pausas de control.",
          description: "Actividad presencial para mejorar tolerancia al esfuerzo con seguimiento seguro.",
          category: "Running",
          coverTone: "aqua",
          modality: "in_person",
          locationName: "Parque La Mexicana",
          address: "Santa Fe, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-08-08T08:00:00-06:00",
          endAt: "2026-08-08T09:30:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 28,
          allowWaitlist: true,
          bookingDeadline: "2026-08-07T20:00:00-06:00",
          status: "published"
        },
        {
          id: "event-nutrition-shopping",
          communityId: "community-nutricion-inteligente",
          creatorId: id,
          title: "Lista de compras inteligente",
          shortDescription: "Organiza compras saludables para la semana.",
          description: "Sesion remota para construir una lista simple segun metas de salud y preferencias.",
          category: "Nutricion",
          coverTone: "gold",
          modality: "remote",
          locationName: "Sala virtual Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/lista-compras",
          startAt: "2026-08-10T18:30:00-06:00",
          endAt: "2026-08-10T19:15:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 35,
          allowWaitlist: false,
          bookingDeadline: "2026-08-10T16:00:00-06:00",
          status: "published"
        },
        {
          id: "event-mental-breathing",
          communityId: "community-mental-balance",
          creatorId: "100000119",
          title: "Respiracion para ansiedad",
          shortDescription: "Herramientas generales de regulacion emocional.",
          description: "Sesion remota educativa. Si hay sintomas intensos, contacta a tu profesional de salud.",
          category: "Salud mental",
          coverTone: "purple",
          modality: "remote",
          locationName: "Sala virtual Klini",
          address: "",
          remoteUrl: "https://klini.demo/evento/respiracion-ansiedad",
          startAt: "2026-08-12T20:00:00-06:00",
          endAt: "2026-08-12T20:45:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 30,
          allowWaitlist: true,
          bookingDeadline: "2026-08-12T18:00:00-06:00",
          status: "published"
        },
        {
          id: "event-yoga-family",
          communityId: "community-yoga-mente",
          creatorId: COMMUNITY_ADMIN_ID,
          title: "Movilidad en familia",
          shortDescription: "Rutina ligera para compartir habitos saludables.",
          description: "Encuentro presencial con movilidad suave y ejercicios de respiracion aptos para principiantes.",
          category: "Yoga",
          coverTone: "mint",
          modality: "in_person",
          locationName: "Casa Klini Coyoacan",
          address: "Coyoacan, Ciudad de Mexico",
          remoteUrl: "",
          startAt: "2026-08-15T10:00:00-06:00",
          endAt: "2026-08-15T11:00:00-06:00",
          timezone: "America/Mexico_City",
          capacity: 24,
          allowWaitlist: true,
          bookingDeadline: "2026-08-14T18:00:00-06:00",
          status: "published"
        }
      ],
      eventReservations: [
        { id: "reservation-yoga-patient", eventId: "event-yoga-breathing", userId: id, status: "confirmed", reservedAt: "2026-07-16T10:40:00-06:00" },
        { id: "reservation-yoga-ana", eventId: "event-yoga-breathing", userId: "100000088", status: "confirmed", reservedAt: "2026-07-16T10:50:00-06:00" },
        { id: "reservation-yoga-diego", eventId: "event-yoga-breathing", userId: "100000071", status: "confirmed", reservedAt: "2026-07-16T11:00:00-06:00" },
        { id: "reservation-yoga-mariana", eventId: "event-yoga-breathing", userId: "100000119", status: "confirmed", reservedAt: "2026-07-16T11:10:00-06:00" },
        { id: "reservation-yoga-meditation-ana", eventId: "event-yoga-meditation", userId: "100000088", status: "confirmed", reservedAt: "2026-07-18T09:00:00-06:00" },
        { id: "reservation-yoga-meditation-diego", eventId: "event-yoga-meditation", userId: "100000071", status: "confirmed", reservedAt: "2026-07-18T09:10:00-06:00" }
      ],
      communityInvitations: [
        { id: "invite-nutrition-all", eventId: "event-nutrition-remote", userId: "all-members", status: "sent", sentAt: "2026-07-17T09:00:00-06:00" }
      ],
      communityPosts: [
        {
          id: "post-yoga-welcome",
          communityId: "community-yoga-mente",
          authorId: "100000088",
          authorName: "Ana Sofia Ruiz",
          title: "Bienvenida a la semana",
          body: "Recuerda reservar tu espacio y llevar ropa comoda para la sesion de respiracion.",
          status: "published",
          pinned: true,
          createdAt: "2026-07-19T09:00:00-06:00",
          updatedAt: now
        },
        {
          id: "post-yoga-reminder",
          communityId: "community-yoga-mente",
          authorId: COMMUNITY_ADMIN_ID,
          authorName: "Jimmy Carter",
          title: "Recordatorio de clase",
          body: "Nuestra sesion de meditacion del sabado sera enfocada en respiracion consciente. Reserva tu lugar desde el panel de eventos.",
          status: "published",
          pinned: false,
          createdAt: "2026-07-20T11:00:00-06:00",
          updatedAt: now
        },
        {
          id: "post-nutrition-menu",
          communityId: "community-nutricion-inteligente",
          authorId: id,
          authorName: currentPatient()?.fullName || "Administrador",
          title: "Menu semanal practico",
          body: "Compartan sus dudas de planeacion y cualquier restriccion que quieran considerar en la sesion remota.",
          status: "published",
          pinned: true,
          createdAt: "2026-07-20T12:30:00-06:00",
          updatedAt: now
        },
        {
          id: "post-nutrition-draft",
          communityId: "community-nutricion-inteligente",
          authorId: id,
          authorName: currentPatient()?.fullName || "Administrador",
          title: "Guia de compras",
          body: "Borrador para publicar antes del proximo evento.",
          status: "draft",
          pinned: false,
          createdAt: "2026-07-21T08:15:00-06:00",
          updatedAt: now
        }
      ]
    };
  }

  function ensureCommunityState(state) {
    const seed = communitySeed(state.patientId || patientId());
    state.communities = Array.isArray(state.communities) ? state.communities : seed.communities;
    state.communityMembers = Array.isArray(state.communityMembers) ? state.communityMembers : seed.communityMembers;
    state.communityJoinRequests = Array.isArray(state.communityJoinRequests) ? state.communityJoinRequests : seed.communityJoinRequests;
    state.communityEvents = Array.isArray(state.communityEvents) ? state.communityEvents : seed.communityEvents;
    state.eventReservations = Array.isArray(state.eventReservations) ? state.eventReservations : seed.eventReservations;
    state.communityInvitations = Array.isArray(state.communityInvitations) ? state.communityInvitations : seed.communityInvitations;
    state.communityPosts = Array.isArray(state.communityPosts) ? state.communityPosts : seed.communityPosts;
    [
      ["communities", "id"],
      ["communityMembers", "id"],
      ["communityJoinRequests", "id"],
      ["communityEvents", "id"],
      ["eventReservations", "id"],
      ["communityInvitations", "id"],
      ["communityPosts", "id"]
    ].forEach(([collection, key]) => {
      const existing = new Set(state[collection].map((item) => item[key]));
      seed[collection].forEach((item) => {
        if (!existing.has(item[key])) state[collection].push(item);
      });
    });
    const adminCommunity = state.communities.find((community) => community.id === COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID);
    if (adminCommunity) {
      adminCommunity.ownerId = COMMUNITY_ADMIN_ID;
      adminCommunity.accessType = adminCommunity.accessType || "open";
      adminCommunity.visibility = adminCommunity.visibility || "public";
      adminCommunity.allowMemberPosts = adminCommunity.allowMemberPosts !== false;
      adminCommunity.rules = adminCommunity.rules || "Mantener respeto, compartir experiencias de bienestar y evitar recomendaciones medicas sin supervision profesional.";
      adminCommunity.pinnedMessage = adminCommunity.pinnedMessage || "Bienvenidos a Respira y Avanza. Revisa los eventos de la semana y reserva tu lugar con anticipacion.";
    }
    const adminMember = state.communityMembers.find((member) => member.id === "member-yoga-admin") ||
      state.communityMembers.find((member) => member.communityId === COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID && member.userId === COMMUNITY_ADMIN_ID);
    if (adminMember) {
      adminMember.communityId = COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID;
      adminMember.userId = COMMUNITY_ADMIN_ID;
      adminMember.role = "owner";
      adminMember.status = "active";
      adminMember.joinedAt = adminMember.joinedAt || "2026-06-01T10:00:00-06:00";
    } else {
      state.communityMembers.push({
        id: "member-yoga-admin",
        communityId: COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID,
        userId: COMMUNITY_ADMIN_ID,
        role: "owner",
        status: "active",
        joinedAt: "2026-06-01T10:00:00-06:00"
      });
    }
    const paidMeditationEvent = state.communityEvents.find((event) => event.id === "event-yoga-meditation");
    if (paidMeditationEvent) paidMeditationEvent.cost = 300;
    state.communities.forEach((community) => {
      community.rules = community.rules || "Respeto entre miembros, informacion responsable y cero recomendaciones medicas sin supervision profesional.";
      community.pinnedMessage = community.pinnedMessage || "";
      community.allowMemberPosts = community.allowMemberPosts !== false;
      community.logoImage = community.logoImage || "";
      community.logoInitials = community.logoInitials || initialsText(community.name).slice(0, 3);
      community.backgroundImage = community.backgroundImage || "";
    });
  }

  function loadState() {
    const stored = parseStored(localStorage.getItem(storageKey()), null);
    const state = stored && stored.schemaVersion === 1 ? stored : seedWellnessState();
    state.patientId = patientId();
    state.patientName = currentPatient()?.fullName || state.patientName || "Paciente";
    state.walletCredits = Number.isFinite(Number(state.walletCredits)) ? Number(state.walletCredits) : 120;
    state.achievements = Array.isArray(state.achievements) ? state.achievements : achievementCatalog();
    state.patientAchievements = Array.isArray(state.patientAchievements) ? state.patientAchievements : [];
    state.goalProgress = Array.isArray(state.goalProgress) ? state.goalProgress : [];
    state.alerts = Array.isArray(state.alerts) ? state.alerts : [];
    ensureCommunityState(state);
    recalculateState(state);
    saveState(state);
    return state;
  }

  function saveState(state) {
    localStorage.setItem(storageKey(), JSON.stringify(state));
  }

  function periodRange(periodType = "weekly") {
    const now = new Date();
    const end = new Date(now);
    end.setHours(23, 59, 59, 999);
    const start = new Date(now);
    if (periodType === "daily") {
      start.setHours(0, 0, 0, 0);
    } else if (periodType === "weekly") {
      start.setDate(start.getDate() - 6);
      start.setHours(0, 0, 0, 0);
    } else if (periodType === "monthly") {
      start.setDate(1);
      start.setHours(0, 0, 0, 0);
    } else if (periodType === "annual") {
      start.setMonth(0, 1);
      start.setHours(0, 0, 0, 0);
    } else {
      start.setDate(start.getDate() - 30);
      start.setHours(0, 0, 0, 0);
    }
    return { start, end };
  }

  function metricValue(metric) {
    if (!metric) return 0;
    if (typeof metric.value === "number") return metric.value;
    const parsed = Number(String(metric.value).replace(/[^\d.-]/g, ""));
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function metricPassesGoal(metric, goal) {
    const value = metricValue(metric);
    const operator = goal.comparisonOperator || "greater_than_or_equal";
    if (operator === "number_of_records") return true;
    if (operator === "less_than_or_equal") return value <= Number(goal.targetValue || 0);
    if (operator === "equal") return value === Number(goal.targetValue || 0);
    if (operator === "within_range") return value >= Number(goal.validRangeMin ?? goal.targetValue ?? 0) && value <= Number(goal.validRangeMax ?? goal.targetValue ?? 0);
    if (operator === "percentage_of_compliance") return value >= Number(goal.minimumCompliancePercentage || goal.targetValue || 0);
    return value >= Number(goal.targetValue || 0);
  }

  function metricsForGoal(state, goal, range = periodRange(goal.periodType)) {
    return state.metrics.filter((metric) => {
      const date = new Date(metric.recordedAt);
      return metric.type === goal.metricType && date >= range.start && date <= range.end;
    });
  }

  function dailyBuckets(metrics) {
    const buckets = new Map();
    metrics.forEach((metric) => {
      const key = dateOnly(new Date(metric.recordedAt));
      if (!buckets.has(key)) buckets.set(key, []);
      buckets.get(key).push(metric);
    });
    return buckets;
  }

  function calculateStreak(goal, metrics) {
    const buckets = dailyBuckets(metrics);
    let streak = 0;
    for (let offset = 0; offset < 365; offset += 1) {
      const day = new Date();
      day.setDate(day.getDate() - offset);
      const key = dateOnly(day);
      const passed = (buckets.get(key) || []).some((metric) => metricPassesGoal(metric, goal));
      if (!passed) break;
      streak += 1;
    }
    return streak;
  }

  function recalculateGoalProgress(state, goal) {
    const range = periodRange(goal.periodType);
    const records = metricsForGoal(state, goal, range);
    const completedOccurrences = goal.comparisonOperator === "number_of_records"
      ? records.length
      : Array.from(dailyBuckets(records).values()).filter((items) => items.some((metric) => metricPassesGoal(metric, goal))).length;
    const requiredOccurrences = Number(goal.requiredOccurrences || goal.targetValue || 1);
    const currentValue = goal.comparisonOperator === "number_of_records"
      ? records.length
      : records.reduce((sum, metric) => sum + metricValue(metric), 0);
    const compliancePercentage = Math.max(0, Math.min(100, Math.round((completedOccurrences / Math.max(requiredOccurrences, 1)) * 100)));
    const currentStreak = calculateStreak(goal, state.metrics.filter((metric) => metric.type === goal.metricType));
    const status = compliancePercentage >= Number(goal.minimumCompliancePercentage || 100)
      ? "completed"
      : completedOccurrences > 0
        ? "in_progress"
        : "not_started";

    return {
      id: `progress-${goal.id}`,
      goalId: goal.id,
      patientId: state.patientId,
      periodStart: range.start.toISOString(),
      periodEnd: range.end.toISOString(),
      currentValue,
      targetValue: Number(goal.targetValue || requiredOccurrences || 1),
      compliancePercentage,
      completedOccurrences,
      requiredOccurrences,
      currentStreak,
      status,
      updatedAt: new Date().toISOString()
    };
  }

  function recalculateState(state) {
    state.goalProgress = state.goals.map((goal) => recalculateGoalProgress(state, goal));
    evaluateAchievements(state);
    state.healthScore = calculateHealthScore(state);
    state.summaries = {
      weekly: buildSummary(state, "weekly"),
      monthly: buildSummary(state, "monthly"),
      annual: buildSummary(state, "annual")
    };
  }

  function evaluateAchievements(state) {
    const progressByGoal = new Map(state.goalProgress.map((progress) => [progress.goalId, progress]));
    state.achievements.forEach((achievement) => {
      const criteria = achievement.criteria || {};
      let progress = 0;
      if (criteria.minRecords) {
        progress = Math.min(100, Math.round((state.metrics.length / criteria.minRecords) * 100));
      }
      if (criteria.goalId) {
        const goalProgress = progressByGoal.get(criteria.goalId);
        progress = Math.max(progress, goalProgress?.compliancePercentage || 0);
        if (criteria.streakRequired) {
          progress = Math.max(progress, Math.round(((goalProgress?.currentStreak || 0) / criteria.streakRequired) * 100));
        }
        if (criteria.completedOccurrences) {
          progress = Math.max(progress, Math.round(((goalProgress?.completedOccurrences || 0) / criteria.completedOccurrences) * 100));
        }
      }
      if (criteria.overallCompliance) {
        const average = averageGoalCompliance(state);
        progress = Math.max(progress, Math.round((average / criteria.overallCompliance) * 100));
      }
      progress = Math.max(0, Math.min(100, progress));
      const existing = state.patientAchievements.find((item) => item.achievementId === achievement.id);
      const unlocked = progress >= 100;
      if (existing) {
        existing.progress = progress;
        existing.status = unlocked ? "unlocked" : progress > 0 ? "in_progress" : "locked";
        if (unlocked && !existing.unlockedAt) existing.unlockedAt = new Date().toISOString();
      } else {
        state.patientAchievements.push({
          id: wellnessId("patient-achievement"),
          patientId: state.patientId,
          achievementId: achievement.id,
          goalId: criteria.goalId || "",
          unlockedAt: unlocked ? new Date().toISOString() : "",
          progress,
          status: unlocked ? "unlocked" : progress > 0 ? "in_progress" : "locked",
          sharedAt: ""
        });
      }
    });
  }

  function latestMetric(state, type) {
    return [...state.metrics]
      .filter((metric) => metric.type === type)
      .sort((a, b) => new Date(b.recordedAt) - new Date(a.recordedAt))[0] || null;
  }

  function todaysMetric(state, type) {
    const today = dateOnly();
    return [...state.metrics]
      .filter((metric) => metric.type === type && dateOnly(new Date(metric.recordedAt)) === today)
      .sort((a, b) => new Date(b.recordedAt) - new Date(a.recordedAt))[0] || latestMetric(state, type);
  }

  function metricScore(config, metric) {
    if (!metric) return 50;
    const value = metricValue(metric);
    if (config.min !== undefined && config.max !== undefined) {
      return value >= config.min && value <= config.max ? 100 : Math.max(35, 100 - Math.min(65, Math.abs(value - config.target) * 3));
    }
    if (config.inverse) {
      return Math.max(0, Math.min(100, Math.round((1 - value / Math.max(config.target * 2, 1)) * 100)));
    }
    return Math.max(0, Math.min(100, Math.round((value / Math.max(config.target || 1, 1)) * 100)));
  }

  function calculateHealthScore(state) {
    const tracked = ["water_intake", "sleep_hours", "activity_steps", "medications_taken", "stress_score", "blood_pressure_systolic", "glucose_mgdl", "oxygen_saturation", "mood_score"];
    const scores = tracked.map((type) => {
      const config = metricConfig(type);
      return metricScore(config, todaysMetric(state, type));
    });
    const goalAverage = averageGoalCompliance(state);
    const total = [...scores, goalAverage].reduce((sum, value) => sum + value, 0) / (scores.length + 1);
    return Math.round(Math.max(0, Math.min(100, total)));
  }

  function averageGoalCompliance(state) {
    if (!state.goalProgress.length) return 0;
    return Math.round(state.goalProgress.reduce((sum, item) => sum + item.compliancePercentage, 0) / state.goalProgress.length);
  }

  function healthScoreStatus(score) {
    if (score >= 88) return { label: "Excelente", tone: "excellent" };
    if (score >= 75) return { label: "Bueno", tone: "good" };
    if (score >= 60) return { label: "Estable", tone: "stable" };
    if (score >= 45) return { label: "Atencion", tone: "attention" };
    return { label: "Riesgo", tone: "risk" };
  }

  function buildSummary(state, periodType) {
    const range = periodRange(periodType);
    const records = state.metrics.filter((metric) => {
      const date = new Date(metric.recordedAt);
      return date >= range.start && date <= range.end;
    });
    const completed = state.goalProgress.filter((item) => item.status === "completed").length;
    const unlocked = state.patientAchievements.filter((item) => item.status === "unlocked").length;
    const bestGoal = [...state.goals].sort((a, b) => {
      const pa = state.goalProgress.find((item) => item.goalId === a.id)?.compliancePercentage || 0;
      const pb = state.goalProgress.find((item) => item.goalId === b.id)?.compliancePercentage || 0;
      return pb - pa;
    })[0];
    return {
      periodType,
      recordCount: records.length,
      activeGoals: state.goals.filter((goal) => goal.status === "active").length,
      completedGoals: completed,
      compliancePercentage: averageGoalCompliance(state),
      bestCategory: bestGoal?.category || "Sin datos",
      attentionCategory: "Presion arterial",
      longestStreak: Math.max(0, ...state.goalProgress.map((item) => item.currentStreak || 0)),
      unlockedAchievements: unlocked,
      comparison: "+8%",
      message: "Vas construyendo constancia. Revisa los indicadores que requieren seguimiento medico."
    };
  }

  function metricDisplay(metric) {
    if (!metric) return "Sin registro";
    const config = metricConfig(metric.type);
    if (metric.type === "blood_pressure_systolic") return `${metric.value}/${metric.metadata?.diastolic || "--"} ${metric.unit || config.unit}`;
    if (metric.type === "medications_taken") return `${metric.value}% confirmado`;
    if (metric.type === "personal_note") return safeText(metric.value);
    return `${safeText(metric.value)} ${safeText(metric.unit || config.unit || "")}`.trim();
  }

  function metricStatus(config, metric) {
    if (!metric) return { label: "Sin dato", tone: "empty" };
    const score = metricScore(config, metric);
    if (score >= 90) return { label: "En meta", tone: "good" };
    if (score >= 65) return { label: "En seguimiento", tone: "stable" };
    if (score >= 45) return { label: "Atencion", tone: "attention" };
    return { label: "Revisar", tone: "risk" };
  }

  function wellnessHeroCopy() {
    if (wellnessUi.section === "communities" && wellnessUi.communityView === "admin") {
      return ["Administrador de comunidad", "CRM, eventos, contenido, analitica y crecimiento de la comunidad"];
    }
    const copyBySection = {
      today: ["Mi salud hoy", "Registra tu estado, indicadores y seguimiento preventivo"],
      wellbeing: ["Mi salud hoy", "Registra tu estado, indicadores y seguimiento preventivo"],
      communities: ["Comunidades", "Conecta, comparte y mejora tu bienestar con personas que tienen intereses similares"],
      history: ["Historial", "Registros, alertas e integraciones de bienestar"]
    };
    return copyBySection[wellnessUi.section] || copyBySection.today;
  }

  function updateWellnessHeroCopy() {
    const [title, subtitle] = wellnessHeroCopy();
    const titleNode = document.getElementById("patientWellnessHeroTitle");
    const subtitleNode = document.getElementById("patientWellnessHeroSubtitle");
    if (titleNode) titleNode.textContent = title;
    if (subtitleNode) subtitleNode.textContent = subtitle;
  }

  function renderPatientWellness() {
    const root = document.getElementById("patientWellnessRoot");
    if (!root) return;
    const state = loadState();
    document.getElementById("wellnessView")?.classList.toggle("wellness-admin-mode", wellnessUi.section === "communities" && wellnessUi.communityView === "admin");
    updateWellnessHeroCopy();
    root.innerHTML = `
      ${renderWellnessSection(state)}
      ${renderWellnessBottomNav()}
      ${renderWellnessModal(state)}
    `;
    if (typeof setupPatientMenuResponsiveCriteria === "function") setupPatientMenuResponsiveCriteria(root);
    setupWellnessShareCarousel(root);
  }

  function renderWellnessTabs() {
    return `
      <div class="wellness-section-tabs" aria-label="Navegacion Wellness">
        ${sections.map((section) => `
          <button class="wellness-section-tab ${wellnessUi.section === section.id ? "active" : ""}" type="button" data-wellness-section="${section.id}">
            <span>${icon(section.icon)}</span>
            ${safeText(section.label)}
          </button>
        `).join("")}
        <button class="wellness-section-tab wellness-quick-tab" type="button" data-wellness-quick-entry>
          <span>${icon("plus")}</span>
          Registrar
        </button>
      </div>
    `;
  }

  function renderWellnessSection(state) {
    if (wellnessUi.section === "wellbeing") return renderWellbeing(state);
    if (wellnessUi.section === "goals") return renderWellbeing(state);
    if (wellnessUi.section === "communities") return renderCommunities(state);
    if (wellnessUi.section === "achievements") return renderWellbeing(state);
    if (wellnessUi.section === "history") return renderHistory(state);
    return renderToday(state);
  }

  function renderToday(state) {
    const patient = currentPatient();
    const score = state.healthScore || calculateHealthScore(state);
    const status = healthScoreStatus(score);
    const openAlerts = state.alerts.filter((alert) => alert.status === "open");
    return `
      <div class="wellness-today-grid">
        <article class="wellness-greeting-card">
          <div>
            <small>${safeText(formatLongDate(new Date()))}</small>
            <h3>Buenos dias, ${safeText(firstName(patient?.fullName))}</h3>
            <p>Como amaneciste hoy?</p>
          </div>
          <div class="wellness-greeting-actions">
            <button class="wellness-notification-button" type="button" data-wellness-section="history" aria-label="Alertas Wellness">
              ${icon("bell")}
              <span>${openAlerts.length}</span>
            </button>
            <span class="wellness-avatar">${safeText(initialsText(patient?.fullName))}</span>
          </div>
        </article>

        <div class="wellness-mood-row" role="group" aria-label="Estado de animo">
          ${moodOptions.map((mood) => `
            <button class="wellness-mood-option wellness-mood-${mood.accent} ${state.selectedMood === mood.label ? "active" : ""}" type="button" data-wellness-mood="${mood.value}" data-wellness-mood-label="${safeText(mood.label)}">
              <span>${icon(mood.icon)}</span>
              <strong>${safeText(mood.label)}</strong>
              ${state.selectedMood === mood.label ? `<small>${safeText(formatMetricTime(state.moodRecordedAt))}</small>` : ""}
            </button>
          `).join("")}
        </div>

        <article class="wellness-score-card wellness-score-${status.tone}">
          <div class="wellness-score-ring" style="--score:${score}">
            <strong>${score}</strong>
            <span>/100</span>
          </div>
          <div class="wellness-score-copy">
            <small>Health Score orientativo</small>
            <h3>${safeText(status.label)}</h3>
            <p>No es diagnostico medico. Se calcula con registros disponibles, objetivos y datos conectados.</p>
            <button class="wellness-link-button" type="button" data-wellness-modal="score">Ver como se calcula</button>
          </div>
          ${renderTrendSvg(state)}
        </article>

        <section class="wellness-metric-grid" aria-label="Indicadores diarios">
          ${metricCatalog.map((config) => renderDailyMetricCard(state, config)).join("")}
        </section>
      </div>
    `;
  }

  function renderTrendSvg(state) {
    const dailyScores = Array.from({ length: 7 }, (_, index) => {
      const day = new Date();
      day.setDate(day.getDate() - (6 - index));
      const key = dateOnly(day);
      const dayMetrics = state.metrics.filter((metric) => dateOnly(new Date(metric.recordedAt)) === key);
      if (!dayMetrics.length) return 58;
      const total = dayMetrics.reduce((sum, metric) => sum + metricScore(metricConfig(metric.type), metric), 0);
      return Math.round(total / dayMetrics.length);
    });
    const points = dailyScores.map((score, index) => `${index * 18},${72 - score * 0.58}`).join(" ");
    const previous = dailyScores[dailyScores.length - 2] || dailyScores[0];
    const current = dailyScores[dailyScores.length - 1] || state.healthScore;
    const delta = current - previous;
    return `
      <div class="wellness-score-trend">
        <svg viewBox="0 0 108 78" aria-hidden="true">
          <polyline points="${points}" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>
        </svg>
        <span>${delta >= 0 ? "+" : ""}${delta} vs ayer</span>
      </div>
    `;
  }

  function renderDailyMetricCard(state, config) {
    const metric = todaysMetric(state, config.type);
    const status = metricStatus(config, metric);
    const percent = metric ? metricScore(config, metric) : 0;
    const trend = percent >= 80 ? "Meta cerca" : percent >= 50 ? "En progreso" : "Pendiente";
    return `
      <article class="wellness-metric-card wellness-accent-${config.accent}">
        <div class="wellness-metric-top">
          <span class="wellness-metric-icon">${icon(config.icon)}</span>
          <button class="wellness-card-action" type="button" data-wellness-quick-entry="${config.type}">Registrar</button>
        </div>
        <h4>${safeText(config.label)}</h4>
        <strong>${metricDisplay(metric)}</strong>
        <div class="wellness-progress-line"><span style="width:${Math.min(percent, 100)}%"></span></div>
        <div class="wellness-metric-meta">
          <span>Meta: ${safeText(config.target)} ${safeText(config.unit)}</span>
          <span>${safeText(status.label)}</span>
        </div>
        <div class="wellness-source-row">
          <small>${safeText(config.source)}</small>
          <button type="button" data-wellness-metric-detail="${config.type}">Ver detalle</button>
        </div>
      </article>
    `;
  }

  function renderNextActionCard() {
    return `
      <article class="wellness-next-action">
        <span>${icon("bell")}</span>
        <div>
          <small>Proxima accion medica</small>
          <h4>Consulta de seguimiento</h4>
          <p>18/07/2026 08:00 con Dr. Carter Jimmy. Revisa tus registros antes de la cita.</p>
        </div>
        <button type="button" data-wellness-section="history">Ver seguimiento</button>
      </article>
    `;
  }

  function renderWellbeing(state) {
    return `
      <section class="wellness-subscreen wellness-today-streaks-screen">
        <div class="wellness-subscreen-heading">
          <div>
            <small>Mi salud hoy</small>
            <h3>Mi salud hoy</h3>
            <p>Registra tu estado, indicadores y seguimiento preventivo. Aqui tambien veras tus objetivos logrados, rachas y recompensas.</p>
          </div>
          ${renderPeriodSelector()}
        </div>
        ${renderToday(state)}
        ${renderAchievementHighlights(state)}
        ${renderGoalsInStreaksPanel(state)}
        <article class="wellness-summary-card">
          <h4>Resumen ${periodLabel(wellnessUi.period).toLowerCase()}</h4>
          ${renderSummaryList(state.summaries[summaryKeyFromPeriod(wellnessUi.period)])}
        </article>
      </section>
    `;
  }

  function renderGoalsInStreaksPanel(state) {
    const progressByGoal = new Map(state.goalProgress.map((progress) => [progress.goalId, progress]));
    const completed = state.goals.filter((goal) => progressByGoal.get(goal.id)?.status === "completed");
    const goals = (completed.length ? completed : state.goals)
      .slice()
      .sort((a, b) => (progressByGoal.get(b.id)?.compliancePercentage || 0) - (progressByGoal.get(a.id)?.compliancePercentage || 0));
    return `
      <section class="wellness-achievement-panel wellness-goal-achievement-panel">
        <div class="wellness-achievement-heading">
          <div>
            <small>Objetivos</small>
            <h4>${completed.length ? "Objetivos logrados" : "Objetivos en seguimiento"}</h4>
            <p>${completed.length ? "Metas cumplidas que suman a tus rachas." : "Aun no hay objetivos completados. Sigue registrando resultados para desbloquearlos."}</p>
          </div>
          <button class="wellness-primary-soft" type="button" data-wellness-create-goal>${icon("plus")} Crear</button>
        </div>
        <div class="wellness-goal-grid">
          ${goals.length ? goals.map((goal) => renderGoalCard(state, goal)).join("") : renderEmptyState("Sin objetivos", "Crea tu primer objetivo para dar seguimiento.")}
        </div>
      </section>
    `;
  }

  function renderAchievementHighlights(state) {
    const merged = state.achievements.map((achievement) => ({
      ...achievement,
      patient: state.patientAchievements.find((item) => item.achievementId === achievement.id) || { progress: 0, status: "locked" }
    }));
    const featured = merged.find((item) => item.patient.status === "unlocked") || merged[0];
    return `
      <section class="wellness-achievement-panel">
        <div class="wellness-achievement-heading">
          <div>
            <small>Rachas y constancias</small>
            <h4>Progreso que ya estas construyendo</h4>
          </div>
          <button class="wellness-primary-soft" type="button" data-wellness-share="achievement:${safeText(featured.id)}">${icon("share")} Compartir</button>
        </div>
        <div class="wellness-achievement-carousel" data-wellness-pan>
          ${merged.map((achievement) => renderAchievementBadge(achievement)).join("")}
        </div>
        <article class="wellness-featured-achievement">
          <span>${icon(featured.icon)}</span>
          <div>
            <small>Logro destacado - ${safeText(featured.level)}</small>
            <h4>${safeText(featured.title)}</h4>
            <p>${safeText(featured.description)}</p>
          </div>
          <button type="button" data-wellness-share="achievement:${safeText(featured.id)}">Compartir logro</button>
        </article>
      </section>
    `;
  }

  function renderPeriodSelector() {
    const periods = [
      { id: "week", label: "Semana" },
      { id: "month", label: "Mes" },
      { id: "year", label: "Ano" },
      { id: "custom", label: "Rango" }
    ];
    return `
      <div class="wellness-period-tabs" aria-label="Periodo">
        ${periods.map((period) => `
          <button class="${wellnessUi.period === period.id ? "active" : ""}" type="button" data-wellness-period="${period.id}">${safeText(period.label)}</button>
        `).join("")}
      </div>
    `;
  }

  function periodLabel(period) {
    return ({ week: "Semana", month: "Mes", year: "Ano", custom: "Rango personalizado" })[period] || "Semana";
  }

  function summaryKeyFromPeriod(period) {
    if (period === "month") return "monthly";
    if (period === "year") return "annual";
    return "weekly";
  }

  function renderWellbeingCard(state, config) {
    const metric = todaysMetric(state, config.type);
    const score = metricScore(config, metric);
    const status = metricStatus(config, metric);
    return `
      <article class="wellness-wellbeing-card wellness-accent-${config.accent}">
        <div>
          <span>${icon(config.icon)}</span>
          <small>${safeText(config.category)}</small>
          <h4>${safeText(config.label)}</h4>
        </div>
        <strong>${score}</strong>
        <p>${safeText(status.label)} · Meta ${safeText(config.target)} ${safeText(config.unit)}</p>
        <div class="wellness-mini-bars" aria-hidden="true">
          ${[42, 58, 66, 73, score].map((value) => `<i style="height:${value}%"></i>`).join("")}
        </div>
        <button type="button" data-wellness-metric-detail="${config.type}">Ver detalle</button>
      </article>
    `;
  }

  function renderSummaryList(summary) {
    return `
      <dl class="wellness-summary-list">
        <div><dt>Objetivos activos</dt><dd>${summary.activeGoals}</dd></div>
        <div><dt>Objetivos cumplidos</dt><dd>${summary.completedGoals}</dd></div>
        <div><dt>Cumplimiento total</dt><dd>${summary.compliancePercentage}%</dd></div>
        <div><dt>Mejor categoria</dt><dd>${safeText(summary.bestCategory)}</dd></div>
        <div><dt>Requiere atencion</dt><dd>${safeText(summary.attentionCategory)}</dd></div>
        <div><dt>Racha mas larga</dt><dd>${summary.longestStreak} dias</dd></div>
      </dl>
      <button class="wellness-primary-soft" type="button" data-wellness-share="summary:${summary.periodType}">Compartir resumen</button>
    `;
  }

  function renderGoals(state) {
    return `
      <section class="wellness-subscreen">
        <div class="wellness-subscreen-heading">
          <div>
            <small>Objetivos</small>
            <h3>Metas personales y clinicas</h3>
            <p>Los registros diarios actualizan estos avances de forma automatica.</p>
          </div>
          <button class="wellness-primary-action" type="button" data-wellness-create-goal>${icon("plus")} Crear objetivo</button>
        </div>
        <div class="wellness-goal-grid">
          ${state.goals.length ? state.goals.map((goal) => renderGoalCard(state, goal)).join("") : renderEmptyState("Sin objetivos", "Crea tu primer objetivo para dar seguimiento.")}
        </div>
      </section>
    `;
  }

  function renderGoalCard(state, goal) {
    const progress = state.goalProgress.find((item) => item.goalId === goal.id) || recalculateGoalProgress(state, goal);
    const config = metricConfig(goal.metricType);
    return `
      <article class="wellness-goal-card wellness-accent-${config.accent}">
        <div class="wellness-goal-ring" style="--goal:${progress.compliancePercentage}">
          <strong>${progress.compliancePercentage}%</strong>
        </div>
        <div class="wellness-goal-copy">
          <small>${safeText(goal.category)} · ${safeText(goal.periodType)}</small>
          <h4>${safeText(goal.title)}</h4>
          <p>${safeText(goal.description || "Objetivo activo")}</p>
          <div class="wellness-goal-meta">
            <span>${progress.completedOccurrences}/${progress.requiredOccurrences} cumplidos</span>
            <span>Racha ${progress.currentStreak} dias</span>
          </div>
        </div>
        <div class="wellness-goal-actions">
          <button type="button" data-wellness-goal-detail="${safeText(goal.id)}">Detalle</button>
          <button type="button" data-wellness-quick-entry="${safeText(goal.metricType)}">Registrar resultado</button>
          <button type="button" data-wellness-share="goal:${safeText(goal.id)}">Compartir avance</button>
          <button class="wellness-goal-delete-button" type="button" data-wellness-goal-delete="${safeText(goal.id)}">Eliminar</button>
        </div>
      </article>
    `;
  }

  function renderAchievements(state) {
    const merged = state.achievements.map((achievement) => ({
      ...achievement,
      patient: state.patientAchievements.find((item) => item.achievementId === achievement.id) || { progress: 0, status: "locked" }
    }));
    const featured = merged.find((item) => item.patient.status === "unlocked") || merged[0];
    return `
      <section class="wellness-subscreen">
        <div class="wellness-subscreen-heading">
          <div>
            <small>Mis logros</small>
            <h3>Rachas y recompensas</h3>
          </div>
          <button class="wellness-primary-soft" type="button" data-wellness-share="achievement:${safeText(featured.id)}">${icon("share")} Compartir</button>
        </div>
        <div class="wellness-achievement-carousel" data-wellness-pan>
          ${merged.map((achievement) => renderAchievementBadge(achievement)).join("")}
        </div>
        <article class="wellness-featured-achievement">
          <span>${icon(featured.icon)}</span>
          <div>
            <small>Logro destacado · ${safeText(featured.level)}</small>
            <h4>${safeText(featured.title)}</h4>
            <p>${safeText(featured.description)}</p>
          </div>
          <button type="button" data-wellness-share="achievement:${safeText(featured.id)}">Compartir logro</button>
        </article>
      </section>
    `;
  }

  function renderAchievementBadge(achievement) {
    return `
      <button class="wellness-achievement-badge wellness-achievement-${achievement.patient.status}" type="button" data-wellness-achievement-detail="${safeText(achievement.id)}">
        <span>${icon(achievement.icon)}</span>
        <strong>${safeText(achievement.title)}</strong>
        <small>${achievement.patient.progress}%</small>
      </button>
    `;
  }

  function communityById(state, communityId) {
    return state.communities.find((community) => community.id === communityId) || null;
  }

  function eventById(state, eventId) {
    return state.communityEvents.find((event) => event.id === eventId) || null;
  }

  function membershipFor(state, communityId, userId = state.patientId) {
    return state.communityMembers.find((member) => member.communityId === communityId && member.userId === userId && member.status === "active") || null;
  }

  function adminMembershipFor(state, communityId) {
    return membershipFor(state, communityId, currentCommunityAdmin().id);
  }

  function requestForCurrentUser(state, communityId) {
    return state.communityJoinRequests.find((request) => request.communityId === communityId && request.userId === state.patientId && request.status === "pending") || null;
  }

  function canManageCommunity(state, communityId) {
    const membership = adminMembershipFor(state, communityId);
    return ["owner", "admin"].includes(membership?.role);
  }

  function manageableCommunities(state) {
    return state.communities.filter((community) => canManageCommunity(state, community.id));
  }

  function selectedAdminCommunity(state) {
    const communities = manageableCommunities(state);
    if (!communities.length) return null;
    const selected = communities.find((community) => community.id === wellnessUi.adminCommunityId) || communities[0];
    wellnessUi.adminCommunityId = selected.id;
    return selected;
  }

  function communityPostsFor(state, communityId) {
    return [...(state.communityPosts || [])]
      .filter((post) => post.communityId === communityId)
      .sort((a, b) => Number(Boolean(b.pinned)) - Number(Boolean(a.pinned)) || new Date(b.createdAt) - new Date(a.createdAt));
  }

  function communityPublishedPosts(state, communityId) {
    return communityPostsFor(state, communityId).filter((post) => post.status === "published");
  }

  function memberDisplayName(state, member) {
    const admin = currentCommunityAdmin();
    if (member.userId === admin.id) return admin.fullName;
    if (member.userId === state.patientId) return state.patientName || "Tu cuenta";
    const requestName = state.communityJoinRequests.find((request) => request.userId === member.userId)?.userName;
    const knownMembers = {
      "100000088": "Ana Sofia Ruiz",
      "100000071": "Diego Ramirez",
      "100000119": "Mariana Lopez",
      "100000207": "Carlos Ruiz",
      "100000218": "Laura Hernandez"
    };
    return requestName || knownMembers[member.userId] || `Usuario ${String(member.userId || "").slice(-4)}`;
  }

  function communityStatusLabel(status) {
    const labels = {
      active: "Activa",
      paused: "Pausada",
      archived: "Archivada",
      published: "Publicado",
      draft: "Borrador",
      cancelled: "Cancelado",
      hidden: "Oculto"
    };
    return labels[status] || status || "Sin estado";
  }

  function eventReservationForUser(state, eventId) {
    return state.eventReservations.find((reservation) => reservation.eventId === eventId && reservation.userId === state.patientId && !["cancelled", "no_show"].includes(reservation.status)) || null;
  }

  function eventSeats(state, event) {
    const confirmed = state.eventReservations.filter((reservation) => reservation.eventId === event.id && reservation.status === "confirmed").length;
    const waitlisted = state.eventReservations.filter((reservation) => reservation.eventId === event.id && reservation.status === "waitlisted").length;
    return {
      confirmed,
      waitlisted,
      available: Math.max(0, Number(event.capacity || 0) - confirmed)
    };
  }

  function bookingStatus(state, event) {
    const reservation = eventReservationForUser(state, event.id);
    const seats = eventSeats(state, event);
    if (reservation?.status === "confirmed") return { label: "Reservation confirmed", tone: "confirmed" };
    if (reservation?.status === "waitlisted") return { label: "Lista de espera", tone: "waitlist" };
    if (event.status !== "published") return { label: "Not ticketed", tone: "neutral" };
    if (!seats.available) return { label: "Full", tone: "full" };
    if (seats.available <= 3) return { label: "Almost full", tone: "almost" };
    return { label: "Booking open", tone: "open" };
  }

  const COMMUNITY_EVENT_GROUP_LABELS = ["This week", "Next week", "Coming soon"];
  const COMMUNITY_EVENT_GROUP_PAGE_SIZE = 5;

  function startOfLocalDay(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  }

  function startOfWeek(date) {
    const start = startOfLocalDay(date);
    const day = start.getDay() || 7;
    start.setDate(start.getDate() - day + 1);
    return start;
  }

  function eventGroupLabel(event) {
    const eventDate = startOfLocalDay(new Date(event.startAt));
    const thisWeekStart = startOfWeek(new Date());
    const nextWeekStart = new Date(thisWeekStart);
    nextWeekStart.setDate(thisWeekStart.getDate() + 7);
    const comingSoonStart = new Date(thisWeekStart);
    comingSoonStart.setDate(thisWeekStart.getDate() + 14);
    if (eventDate < nextWeekStart) return "This week";
    if (eventDate < comingSoonStart) return "Next week";
    return "Coming soon";
  }

  function eventGroupsFor(events) {
    return COMMUNITY_EVENT_GROUP_LABELS.map((label) => ({
      label,
      events: events.filter((event) => eventGroupLabel(event) === label)
    }));
  }

  function eventGroupKey(label, options = {}) {
    return `${options.groupScope || wellnessUi.communityView || "communities"}:${label}`;
  }

  function visibleEventGroupCount(label, options = {}) {
    const key = eventGroupKey(label, options);
    return Math.max(COMMUNITY_EVENT_GROUP_PAGE_SIZE, Number(wellnessUi.eventGroupVisible?.[key]) || COMMUNITY_EVENT_GROUP_PAGE_SIZE);
  }

  function communityEventsForPatient(state) {
    const memberCommunityIds = new Set(state.communityMembers.filter((member) => member.userId === state.patientId && member.status === "active").map((member) => member.communityId));
    return [...state.communityEvents]
      .filter((event) => event.status === "published" && (memberCommunityIds.has(event.communityId) || communityById(state, event.communityId)?.visibility === "public"))
      .sort((a, b) => new Date(a.startAt) - new Date(b.startAt));
  }

  function communityMemberAvatars(community) {
    const letters = String(community.name || "KL").split(/\s+/).map((word) => word[0]).join("").slice(0, 3).toUpperCase();
    return Array.from({ length: 3 }, (_, index) => `<span>${safeText(letters[index] || "K")}</span>`).join("");
  }

  function renderCommunities(state) {
    const adminCommunities = manageableCommunities(state);
    if (wellnessUi.communityView === "landing") {
      return renderCommunitiesLanding(state);
    }
    if (wellnessUi.communityView === "admin" && adminCommunities.length) return renderCommunityAdmin(state);
    const views = [
      { id: "events", label: "Eventos", icon: "calendar" },
      { id: "explore", label: "Explorar", icon: "search" },
      { id: "mine", label: "Mis comunidades", icon: "users" },
      { id: "requests", label: "Solicitudes", icon: "user-plus" },
      { id: "reservations", label: "Mis reservas", icon: "ticket" },
      ...(adminCommunities.length ? [{ id: "admin", label: "Administrar", icon: "shield" }] : [])
    ];
    if (wellnessUi.communityView === "admin" && !adminCommunities.length) wellnessUi.communityView = "mine";
    return `
      <section class="wellness-subscreen wellness-communities">
        <div class="wellness-subscreen-heading wellness-communities-heading">
          <div>
            <small>Comunidades</small>
            <h3>Comunidades</h3>
            <p>Conecta, comparte y mejora tu bienestar con personas que tienen intereses similares.</p>
          </div>
          <button class="wellness-primary-action" type="button" data-wellness-modal="create-community">${icon("plus")} Crear comunidad</button>
        </div>
        <div class="wellness-community-nav" data-wellness-pan>
          ${views.map((view) => `
            <button class="${wellnessUi.communityView === view.id ? "active" : ""}" type="button" data-wellness-community-view="${view.id}">
              ${icon(view.icon)} ${safeText(view.label)}
              ${view.id === "requests" ? `<small>${pendingOwnerRequests(state).length}</small>` : ""}
            </button>
          `).join("")}
        </div>
        ${renderCommunityView(state)}
      </section>
    `;
  }

  function renderCommunitiesLanding(state) {
    const stories = [
      { initials: "TU", name: "Tu historia", action: true },
      { initials: "ML", name: "Mariana", image: "", accent: "mint" },
      { initials: "DR", name: "Diego", image: "", accent: "blue" },
      { initials: "AS", name: "Ana Sofia", image: "", accent: "rose" },
      { initials: "CR", name: "Carlos", image: "", accent: "gold" },
      { initials: "...", name: "Mas", image: "", accent: "soft" }
    ];
    const posts = [
      {
        author: "Mariana Lopez",
        time: "Hoy - 8:30 a.m.",
        text: "Iniciando el dia con gratitud y respiracion consciente. Pequenos pasos, grandes cambios.",
        visual: "breath",
        likes: 128,
        comments: 24,
        shares: 15
      },
      {
        author: "Diego Ramirez",
        time: "Ayer - 7:15 p.m.",
        text: "Nada como una caminata al aire libre para limpiar la mente y recargar energia.",
        visual: "trail",
        likes: 96,
        comments: 18,
        shares: 7
      },
      {
        author: "Ana Sofia Ruiz",
        time: "2 dias - 9:45 a.m.",
        text: "Mi smoothie favorito para empezar el dia con toda la energia.",
        visual: "nutrition",
        likes: 74,
        comments: 12,
        shares: 6
      }
    ];

    return `
      <section class="wellness-subscreen wellness-communities-entry">
        <button class="communities-entry-explore" type="button" data-wellness-community-view="home">
          <span>${icon("compass")} Explorar comunidades</span>
          ${icon("chevron-right")}
        </button>

        <div class="communities-entry-title">
          <small>Comunidades</small>
          <h3>Comunidades</h3>
          <p>Conecta, comparte e inspira.</p>
        </div>

        <div class="communities-story-strip" data-wellness-pan>
          ${stories.map((story) => `
            <button class="communities-story ${story.action ? "is-own" : ""} ${story.accent ? `is-${story.accent}` : ""}" type="button">
              <span class="communities-story-avatar">
                ${story.action ? icon("plus") : safeText(story.initials)}
              </span>
              <small>${safeText(story.name)}</small>
            </button>
          `).join("")}
        </div>

        <div class="communities-entry-feed">
          ${posts.map((post) => `
            <article class="community-feed-post">
              <div class="community-feed-author">
                <span class="community-feed-avatar">${safeText(post.author.split(" ").map((part) => part[0]).join("").slice(0, 2))}</span>
                <div>
                  <strong>${safeText(post.author)}</strong>
                  <small>${safeText(post.time)}</small>
                </div>
                <button type="button" aria-label="Mas opciones">${icon("more-horizontal")}</button>
              </div>
              <p>${safeText(post.text)}</p>
              <div class="community-feed-visual is-${safeText(post.visual)}"></div>
              <div class="community-feed-actions">
                <span>${icon("heart")} ${post.likes}</span>
                <span>${icon("message-circle")} ${post.comments}</span>
                <span>${icon("send")} ${post.shares}</span>
              </div>
            </article>
          `).join("")}
        </div>
      </section>
    `;
  }

  function renderCommunityView(state) {
    if (wellnessUi.communityView === "events") return renderCommunityEvents(state);
    if (wellnessUi.communityView === "explore") return renderExploreCommunities(state);
    if (wellnessUi.communityView === "mine") return renderMyCommunities(state);
    if (wellnessUi.communityView === "requests") return renderCommunityRequests(state);
    if (wellnessUi.communityView === "reservations") return renderMyReservations(state);
    if (wellnessUi.communityView === "admin") return renderCommunityAdmin(state);
    return renderCommunitiesHome(state);
  }

  function renderCommunitiesHome(state) {
    const grouped = eventGroupsFor(communityEventsForPatient(state));
    return `
      <div class="wellness-community-home">
        ${renderReservationsCarousel(state)}
        ${renderMemberCommunitiesCarousel(state)}
        <section>
          <div class="wellness-community-section-heading">
            <h4>Comunidades destacadas</h4>
          </div>
          <div class="wellness-community-carousel" data-wellness-pan data-wellness-pan-mode="horizontal" aria-label="Carrusel de comunidades destacadas">
            ${state.communities.map((community) => renderCommunityCard(state, community, true)).join("")}
          </div>
          <div class="wellness-community-see-all-row">
            <button type="button" class="wellness-community-see-all-button" data-wellness-community-view="explore">Ver todas</button>
          </div>
        </section>
        <div class="wellness-community-events-stack">
          ${grouped.map((group) => renderEventGroup(state, group.label, group.events, { groupScope: "community-home" })).join("")}
        </div>
      </div>
    `;
  }

  function patientEventReservations(state) {
    return (state.eventReservations || [])
      .filter((reservation) => reservation.userId === state.patientId && !["cancelled", "no_show"].includes(reservation.status))
      .map((reservation) => ({ reservation, event: eventById(state, reservation.eventId) }))
      .filter((item) => item.event)
      .sort((a, b) => new Date(a.event.startAt) - new Date(b.event.startAt));
  }

  function renderReservationsCarousel(state) {
    const reservations = patientEventReservations(state);
    if (!reservations.length) return "";
    return `
      <section class="wellness-reservations-section">
        <div class="wellness-community-section-heading">
          <div>
            <h4>Mis reservaciones</h4>
            <p>Eventos a los que confirmaste asistencia.</p>
          </div>
        </div>
        <div class="wellness-reservations-carousel" data-wellness-pan data-wellness-pan-mode="horizontal" aria-label="Carrusel de reservaciones">
          ${reservations.map(({ reservation, event }) => renderReservationTile(state, reservation, event)).join("")}
        </div>
        <div class="wellness-reservation-more-row">
          <button type="button" class="wellness-reservation-more-button" data-wellness-community-view="reservations">Ver mas</button>
        </div>
      </section>
    `;
  }

  function patientCommunities(state) {
    const activeMemberships = new Map(
      state.communityMembers
        .filter((member) => member.userId === state.patientId && member.status === "active")
        .map((member) => [member.communityId, member])
    );
    return state.communities
      .filter((community) => activeMemberships.has(community.id))
      .map((community) => ({ ...community, role: activeMemberships.get(community.id)?.role || "member" }));
  }

  function renderMemberCommunitiesCarousel(state) {
    const communities = patientCommunities(state);
    if (!communities.length) return "";
    return `
      <section class="wellness-member-communities-section">
        <div class="wellness-community-section-heading">
          <h4>Mis Comunidades</h4>
        </div>
        <div class="wellness-community-carousel" data-wellness-pan data-wellness-pan-mode="horizontal" aria-label="Carrusel de mis comunidades">
          ${communities.map((community) => renderCommunityCard(state, community, true)).join("")}
        </div>
        <div class="wellness-community-see-all-row">
          <button type="button" class="wellness-community-see-all-button" data-wellness-community-view="mine">Ver mas</button>
        </div>
      </section>
    `;
  }

  function renderReservationTile(state, reservation, event) {
    const community = communityById(state, event.communityId);
    const start = new Date(event.startAt);
    const reservationLabel = reservation.status === "waitlisted" ? "Lista de espera" : "Reservacion confirmada";
    return `
      <article class="wellness-reservation-tile">
        <button class="wellness-reservation-main" type="button" data-wellness-event-open="${safeText(event.id)}">
          <span class="wellness-reservation-date wellness-community-cover-${safeText(event.coverTone || community?.coverTone || "mint")}">
            <small>${safeText(start.toLocaleDateString("es-MX", { weekday: "short" }))}</small>
            <strong>${safeText(start.toLocaleDateString("es-MX", { day: "2-digit" }))}</strong>
            <em>${safeText(start.toLocaleDateString("es-MX", { month: "short" }))}</em>
          </span>
          <span class="wellness-reservation-copy">
            <small>${safeText(event.category)} - ${safeText(reservationLabel)}</small>
            <strong>${safeText(event.title)}</strong>
            <span>${safeText(start.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" }))} - ${safeText(community?.name || "Comunidad Klini")}</span>
            <span>${safeText(event.modality === "remote" ? "Remoto" : (event.locationName || "Por definir"))}</span>
          </span>
        </button>
        <button class="wellness-reservation-cancel" type="button" data-wellness-community-action="cancel-reservation" data-event-id="${safeText(event.id)}">Cancelar</button>
      </article>
    `;
  }

  function renderCommunityEvents(state) {
    const publishedEvents = communityEventsForPatient(state)
      .filter((event) => event.status === "published");
    const grouped = eventGroupsFor(publishedEvents);
    return `
      <section class="wellness-community-events-view">
        <div class="wellness-community-section-heading wellness-events-view-heading">
          <div>
            <h4>Eventos creados por lideres</h4>
            <p>Consulta actividades presenciales y remotas de tus comunidades y de comunidades publicas.</p>
          </div>
          <button type="button" data-wellness-modal="create-event">${icon("plus")} Crear evento</button>
        </div>
        <div class="wellness-event-list wellness-event-list-featured">
          ${publishedEvents.length ? publishedEvents.slice(0, 2).map((event) => renderEventCard(state, event, { showLeader: true })).join("") : renderEmptyState("No hay eventos disponibles", "Cuando los lideres creen eventos, apareceran en esta seccion.")}
        </div>
        ${grouped.map((group) => renderEventGroup(state, group.label, group.events, { showLeader: true, groupScope: "community-events" })).join("")}
      </section>
    `;
  }

  function renderExploreCommunities(state) {
    return `
      <div class="wellness-community-card-grid">
        ${state.communities.map((community) => renderCommunityCard(state, community)).join("")}
      </div>
    `;
  }

  function renderMyCommunities(state) {
    const mine = state.communityMembers
      .filter((member) => member.userId === state.patientId && member.status === "active")
      .map((member) => ({ ...communityById(state, member.communityId), role: member.role }))
      .filter(Boolean);
    const pending = state.communityJoinRequests.filter((request) => request.userId === state.patientId && request.status === "pending");
    return `
      <div class="wellness-community-card-grid">
        ${mine.length ? mine.map((community) => renderCommunityCard(state, community)).join("") : renderEmptyState("Aun no perteneces a comunidades", "Explora comunidades relacionadas con tus intereses y objetivos de bienestar.")}
        ${pending.length ? `<article class="wellness-community-pending-card"><h4>Solicitudes enviadas</h4>${pending.map((request) => `<p>${safeText(communityById(state, request.communityId)?.name)} - Solicitud pendiente</p>`).join("")}</article>` : ""}
      </div>
    `;
  }

  function pendingOwnerRequests(state) {
    const manageable = new Set(state.communities.filter((community) => canManageCommunity(state, community.id)).map((community) => community.id));
    return state.communityJoinRequests.filter((request) => manageable.has(request.communityId) && request.status === "pending");
  }

  function renderCommunityRequests(state) {
    const requests = pendingOwnerRequests(state);
    return `
      <div class="wellness-request-list">
        ${requests.length ? requests.map((request) => {
          const community = communityById(state, request.communityId);
          return `
            <article class="wellness-request-item">
              <span class="wellness-community-avatar">${safeText(request.userInitials || initialsText(request.userName))}</span>
              <div>
                <small>${safeText(formatMetricTime(request.requestedAt))}</small>
                <h4>${safeText(request.userName)}</h4>
                <p>${safeText(request.brief)}</p>
                <em>${safeText((request.sharedInterests || []).join(" · "))}</em>
                <strong>${safeText(community?.name || "Comunidad")}</strong>
              </div>
              <div>
                <button type="button" data-wellness-community-action="accept-request" data-request-id="${safeText(request.id)}">${icon("check")} Aceptar</button>
                <button type="button" data-wellness-community-action="reject-request" data-request-id="${safeText(request.id)}">${icon("x")} Rechazar</button>
              </div>
            </article>
          `;
        }).join("") : renderEmptyState("Sin solicitudes", "Cuando una comunidad cerrada reciba solicitudes, apareceran aqui.")}
      </div>
    `;
  }

  function renderMyReservations(state) {
    const reservations = patientEventReservations(state);
    return `
      <div class="wellness-event-list">
        ${reservations.length ? reservations.map(({ event }) => renderEventCard(state, event)).join("") : renderEmptyState("Sin reservaciones", "Reserva un lugar en eventos de tus comunidades.")}
      </div>
    `;
  }

  function roleLabel(role) {
    const labels = {
      owner: "Propietario",
      admin: "Administrador",
      moderator: "Moderador",
      creator: "Creadora",
      member: "Miembro"
    };
    return labels[role] || "Miembro";
  }

  function renderRoleOptions(role) {
    return ["member", "moderator", "admin", "creator", "owner"].map((value) => `<option value="${value}" ${role === value ? "selected" : ""}>${safeText(roleLabel(value))}</option>`).join("");
  }

  function renderStatusOptions(options, current) {
    return options.map(([value, label]) => `<option value="${safeText(value)}" ${current === value ? "selected" : ""}>${safeText(label)}</option>`).join("");
  }

  function adminStatusLabel(status) {
    const labels = {
      active: "Activo",
      invited: "Invitado",
      pending: "Pendiente",
      suspended: "Suspendido",
      inactive: "Inactivo",
      removed: "Removido"
    };
    return labels[status] || status || "Activo";
  }

  function renderAdminTrend(tone = "teal") {
    const points = tone === "orange"
      ? "0,30 12,29 24,33 36,20 48,24 60,16 72,23 84,22"
      : "0,32 12,30 24,28 36,29 48,22 60,20 72,16 84,12";
    return `
      <svg class="wellness-admin-trend wellness-admin-trend-${safeText(tone)}" viewBox="0 0 86 42" aria-hidden="true">
        <polyline points="${points}"></polyline>
      </svg>
    `;
  }

  function renderAdminMetricCard({ iconName, label, value, detail, tone = "teal" }) {
    return `
      <article class="wellness-admin-stat wellness-admin-stat-${safeText(tone)}">
        <span>${icon(iconName)}</span>
        <div>
          <small>${safeText(label)}</small>
          <strong>${safeText(value)}</strong>
          <p>${safeText(detail)}</p>
        </div>
        ${renderAdminTrend(tone)}
      </article>
    `;
  }

  function renderAdminMemberStack(state, members) {
    const visible = members.slice(0, 6);
    return `
      <div class="wellness-admin-member-stack">
        ${visible.map((member) => `<span>${safeText(initialsText(memberDisplayName(state, member)))}</span>`).join("")}
        <span>+${Math.max(0, Number(communityById(state, members[0]?.communityId)?.memberCount || members.length) - visible.length)}</span>
      </div>
    `;
  }

  function renderAdminEventTile(state, event) {
    const start = new Date(event.startAt);
    const seats = eventSeats(state, event);
    const percent = Math.min(100, Math.round((seats.confirmed / Math.max(1, Number(event.capacity || 1))) * 100));
    return `
      <article class="wellness-admin-event-tile">
        <div class="wellness-admin-event-art wellness-community-cover-${safeText(event.coverTone || "mint")}">
          <span>${icon(categoryIcon(event.category))}</span>
        </div>
        <div class="wellness-admin-event-date">
          <small>${safeText(start.toLocaleDateString("es-MX", { weekday: "short" }))}</small>
          <strong>${safeText(start.toLocaleDateString("es-MX", { day: "2-digit" }))}</strong>
          <span>${safeText(start.toLocaleDateString("es-MX", { month: "short" }))}</span>
        </div>
        <div class="wellness-admin-event-copy">
          <h5>${safeText(event.title)}</h5>
          <p>${safeText(start.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" }))} - ${safeText(new Date(event.endAt).toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" }))} · ${event.modality === "remote" ? "Online" : "Presencial"}</p>
          <small>Cupo: ${safeText(event.capacity)} · Reservados: ${seats.confirmed}</small>
          <div class="wellness-admin-progress"><span style="width:${percent}%"></span></div>
        </div>
        <div class="wellness-admin-event-actions">
          <button type="button" data-wellness-event-open="${safeText(event.id)}">Editar</button>
          <button type="button" data-wellness-community-action="admin-event-attendees" data-event-id="${safeText(event.id)}">Ver asistentes</button>
        </div>
      </article>
    `;
  }

  function renderAdminRequestRow(request) {
    return `
      <article class="wellness-admin-request-row">
        <span class="wellness-community-avatar">${safeText(request.userInitials || initialsText(request.userName))}</span>
        <div>
          <strong>${safeText(request.userName)}</strong>
          <p>${safeText(request.brief)}</p>
          <small>Solicitado ${safeText(formatMetricTime(request.requestedAt))}</small>
        </div>
        <button type="button" data-wellness-community-action="accept-request" data-request-id="${safeText(request.id)}">Aceptar</button>
        <button type="button" data-wellness-community-action="reject-request" data-request-id="${safeText(request.id)}">Rechazar</button>
        <button type="button" aria-label="Mas opciones">...</button>
      </article>
    `;
  }

  function renderAdminTool(community, iconName, title, detail, setting) {
    const isOn = setting === "open"
      ? community.accessType === "open"
      : setting === "visible"
        ? community.visibility !== "hidden"
        : setting === "posts"
          ? community.allowMemberPosts !== false
          : false;
    return `
      <article class="wellness-admin-tool-row">
        <span>${icon(iconName)}</span>
        <div>
          <strong>${safeText(title)}</strong>
          <small>${safeText(detail)}</small>
        </div>
        ${setting
          ? `<button class="wellness-admin-toggle ${isOn ? "active" : ""}" type="button" data-wellness-community-action="toggle-admin-setting" data-community-id="${safeText(community.id)}" data-setting="${safeText(setting)}" aria-label="${safeText(title)}"><i></i></button>`
          : `<button class="wellness-admin-next" type="button" data-wellness-community-action="admin-tool-info" data-community-id="${safeText(community.id)}">${icon("chevron-right")}</button>`}
      </article>
    `;
  }

  function safeCssUrl(value) {
    return String(value || "").replace(/["'()\\\n\r]/g, "").trim();
  }

  function communityBackgroundStyle(community) {
    const image = safeCssUrl(community.backgroundImage);
    if (!image) return "";
    return `style="background-image: linear-gradient(90deg, rgba(255,255,255,0.88), rgba(211,251,242,0.72)), url('${image}');"`;
  }

  function eventImageStyle(event) {
    const image = safeCssUrl(event?.imageUrl);
    if (!image) return "";
    return `style="background-image: linear-gradient(180deg, rgba(255,255,255,0.18), rgba(5,42,54,0.22)), url('${image}');"`;
  }

  function renderEventCoverContent(event, start = new Date()) {
    if (event?.imageUrl) return "";
    return `
      <span>${safeText(start.toLocaleDateString("es-MX", { weekday: "short" }))}</span>
      <strong>${safeText(start.toLocaleDateString("es-MX", { day: "2-digit", month: "short" }))}</strong>
    `;
  }

  function renderCommunityLogo(community, className = "wellness-admin-community-mark") {
    const logoImage = safeCssUrl(community.logoImage);
    const initials = safeText(community.logoInitials || initialsText(community.name).slice(0, 3));
    if (logoImage) {
      return `<div class="${safeText(className)} has-image"><img src="${safeText(logoImage)}" alt="Logotipo de ${safeText(community.name)}"></div>`;
    }
    return `<div class="${safeText(className)}"><strong>${initials}</strong>${icon(categoryIcon(community.category))}</div>`;
  }

  function renderAdminProfileAvatar(admin, className = "wellness-admin-profile-avatar") {
    const photoUrl = safeCssUrl(admin.photoUrl);
    if (photoUrl) {
      return `<span class="${safeText(className)} has-photo"><img src="${safeText(photoUrl)}" alt="Foto de perfil de ${safeText(admin.fullName)}"></span>`;
    }
    return `<span class="${safeText(className)}">${safeText(admin.initials)}</span>`;
  }

  function renderCommunityAdminLegacy(state) {
    const managed = manageableCommunities(state);
    const community = selectedAdminCommunity(state);
    if (!community) return renderEmptyState("Sin comunidades administrables", "El administrador independiente aun no tiene comunidades asignadas.");
    const admin = currentCommunityAdmin();
    const members = state.communityMembers.filter((member) => member.communityId === community.id && ["active", "invited"].includes(member.status));
    const activeMembers = members.filter((member) => member.status === "active");
    const pendingRequests = state.communityJoinRequests.filter((request) => request.communityId === community.id && request.status === "pending");
    const events = state.communityEvents.filter((event) => event.communityId === community.id).sort((a, b) => new Date(a.startAt) - new Date(b.startAt));
    const activeEvents = events.filter((event) => event.status === "published");
    const posts = communityPostsFor(state, community.id);
    const publishedPosts = communityPublishedPosts(state, community.id);
    const nextEvents = activeEvents.slice(0, 2);
    return `
      <section class="wellness-community-admin wellness-admin-dashboard">
        <div class="wellness-admin-topbar">
          <button type="button" class="wellness-admin-back" data-wellness-community-view="home">${icon("chevron-left")} Volver a comunidades</button>
          <div class="wellness-admin-profile-strip">
            <button type="button" aria-label="Notificaciones">${icon("bell")}<span>${pendingRequests.length}</span></button>
            <button type="button" aria-label="Mensajes">${icon("chat")}</button>
            ${renderAdminProfileAvatar(admin)}
            <div>
              <strong>${safeText(admin.fullName)}</strong>
              <small>${safeText(admin.role)}</small>
            </div>
          </div>
        </div>

        <header class="wellness-admin-page-title">
          <h3>Panel de administracion</h3>
          <p>Comunidades &gt; ${safeText(community.name)}</p>
        </header>

        <section class="wellness-admin-community-hero wellness-community-cover-${safeText(community.coverTone || "mint")}">
          <button type="button" class="wellness-admin-edit-community" data-wellness-community-action="admin-edit-community" data-community-id="${safeText(community.id)}">${icon("edit")} Editar comunidad</button>
          <div class="wellness-admin-community-mark">
            ${icon(categoryIcon(community.category))}
          </div>
          <div class="wellness-admin-community-copy">
            <small>${safeText(community.category)} · ${community.accessType === "open" ? "Abierta" : "Cerrada"}</small>
            <h4>${safeText(community.name)}</h4>
            <div class="wellness-admin-community-meta">
              <span>${icon("users")} ${safeText(community.memberCount || activeMembers.length)} miembros</span>
              <span>${icon("map")} ${safeText(community.city || "Online")}</span>
            </div>
            ${renderAdminMemberStack(state, activeMembers)}
          </div>
          <div class="wellness-admin-hero-actions">
            <button type="button" class="wellness-primary-action" data-wellness-modal="create-event">${icon("calendar")} Crear evento</button>
            <button type="button" class="wellness-secondary-action" data-wellness-admin-focus-post>${icon("edit")} Nueva publicacion</button>
            <button type="button" class="wellness-secondary-action" data-wellness-community-action="admin-invite-members" data-community-id="${safeText(community.id)}">${icon("user-plus")} Invitar miembros</button>
          </div>
        </section>

        <div class="wellness-admin-kpi-grid">
          ${renderAdminMetricCard({ iconName: "users", label: "Miembros", value: community.memberCount || activeMembers.length, detail: "+12 este mes", tone: "teal" })}
          ${renderAdminMetricCard({ iconName: "calendar", label: "Eventos activos", value: activeEvents.length, detail: `${Math.min(2, activeEvents.length)} proximos`, tone: "purple" })}
          ${renderAdminMetricCard({ iconName: "user-plus", label: "Solicitudes pendientes", value: pendingRequests.length, detail: "Requieren revision", tone: "orange" })}
          ${renderAdminMetricCard({ iconName: "chat", label: "Publicaciones este mes", value: publishedPosts.length + 16, detail: "+6 vs. mes anterior", tone: "teal" })}
        </div>

        <div class="wellness-admin-dashboard-grid">
          <section class="wellness-admin-panel wellness-admin-events-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Proximos eventos</h4>
              <button type="button" data-wellness-community-view="events">Ver calendario</button>
            </div>
            <div class="wellness-admin-event-list">
              ${nextEvents.length ? nextEvents.map((event) => renderAdminEventTile(state, event)).join("") : renderEmptyState("Sin eventos proximos", "Crea un evento para activar el calendario de la comunidad.")}
            </div>
            <button type="button" class="wellness-admin-panel-link" data-wellness-community-view="events">Ver todos los eventos</button>
          </section>

          <section class="wellness-admin-panel wellness-admin-requests-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Solicitudes de ingreso</h4>
              <button type="button" data-wellness-community-view="requests">Ver todas (${pendingRequests.length})</button>
            </div>
            <div class="wellness-admin-request-list">
              ${pendingRequests.length ? pendingRequests.slice(0, 3).map(renderAdminRequestRow).join("") : renderEmptyState("Sin solicitudes", "Las solicitudes pendientes apareceran aqui.")}
            </div>
            <button type="button" class="wellness-admin-panel-link" data-wellness-community-view="requests">Ver todas las solicitudes</button>
          </section>

          <section class="wellness-admin-panel wellness-admin-tools-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Herramientas rapidas</h4>
            </div>
            <div class="wellness-admin-tool-list">
              ${renderAdminTool(community, "users", "Comunidad abierta", community.accessType === "open" ? "Cualquiera puede unirse" : "Requiere aprobacion", "open")}
              ${renderAdminTool(community, "search", "Visibilidad en busqueda", community.visibility === "hidden" ? "Oculta para explorar" : "Visible para pacientes", "visible")}
              ${renderAdminTool(community, "chat", "Permitir publicaciones", community.allowMemberPosts === false ? "Solo administradores" : "Los miembros pueden publicar", "posts")}
              ${renderAdminTool(community, "shield", "Reglas de la comunidad", "7 reglas configuradas", "")}
              ${renderAdminTool(community, "alert", "Reportar contenido", "Revisa reportes pendientes", "")}
            </div>
            <button type="button" class="wellness-admin-panel-link" data-wellness-community-action="admin-edit-community" data-community-id="${safeText(community.id)}">Ver configuracion completa</button>
          </section>

          <section class="wellness-admin-panel wellness-admin-members-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Administrar miembros</h4>
              <button type="button">Exportar</button>
            </div>
            <div class="wellness-admin-member-controls">
              <input type="search" placeholder="Buscar miembros..." aria-label="Buscar miembros">
              <select><option>Todos los roles</option><option>Administrador</option><option>Moderador</option></select>
              <select><option>Todos los estados</option><option>Activo</option><option>Invitado</option></select>
            </div>
            <div class="wellness-admin-member-table">
              <div class="wellness-admin-member-table-head"><span>Miembro</span><span>Rol</span><span>Estado</span><span>Fecha de union</span><span>Acciones</span></div>
              ${members.slice(0, 5).map((member) => `
                <article class="wellness-admin-member-row">
                  <div><span class="wellness-community-avatar">${safeText(initialsText(memberDisplayName(state, member)))}</span><strong>${safeText(memberDisplayName(state, member))}<small>@${safeText(String(memberDisplayName(state, member)).toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/(^_|_$)/g, ""))}</small></strong></div>
                  <select data-wellness-admin-member-role="${safeText(member.id)}" ${member.role === "owner" ? "disabled" : ""}>${renderRoleOptions(member.role)}</select>
                  <span class="wellness-admin-status wellness-admin-status-${safeText(member.status)}">${safeText(adminStatusLabel(member.status))}</span>
                  <small>${safeText(new Date(member.joinedAt).toLocaleDateString("es-MX", { day: "2-digit", month: "short", year: "numeric" }))}</small>
                  <button type="button" ${member.role === "owner" ? "disabled" : ""} data-wellness-community-action="remove-member" data-member-id="${safeText(member.id)}">...</button>
                </article>
              `).join("")}
            </div>
            <button type="button" class="wellness-admin-panel-link">Ver todos los miembros (${safeText(community.memberCount || members.length)})</button>
          </section>

          <section class="wellness-admin-panel wellness-admin-content-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Contenido de la comunidad</h4>
              <button type="button" class="wellness-primary-soft" data-wellness-admin-focus-post>${icon("plus")} Nueva publicacion</button>
            </div>
            <div class="wellness-admin-content-tabs">
              <button type="button" class="active">Publicaciones</button>
              <button type="button">Anuncios fijados</button>
              <button type="button">Borradores</button>
            </div>
            <form class="wellness-admin-post-form" data-wellness-community-post-form>
              <input type="hidden" name="communityId" value="${safeText(community.id)}">
              <input name="title" placeholder="Titulo de publicacion" required>
              <textarea name="body" rows="2" placeholder="Escribe una publicacion para los miembros" required></textarea>
              <label><input name="pinned" type="checkbox" value="true"> Fijar publicacion</label>
              <button type="submit" class="wellness-primary-action">${icon("plus")} Publicar</button>
            </form>
            <div class="wellness-admin-content-list">
              ${posts.length ? posts.slice(0, 2).map((post) => `
                <article class="wellness-admin-content-item">
                  <span class="wellness-community-avatar">${safeText(initialsText(post.authorName))}</span>
                  <div>
                    <strong>${safeText(post.authorName)} <small>${safeText(communityStatusLabel(post.status))}</small></strong>
                    <p>${safeText(post.body)}</p>
                    <footer><span>${icon("heart")} ${post.pinned ? "24" : "18"}</span><span>${icon("chat")} ${post.pinned ? "6" : "3"}</span><button type="button" data-wellness-community-action="toggle-post-pin" data-post-id="${safeText(post.id)}">${post.pinned ? "Desfijar" : "Fijar"}</button></footer>
                  </div>
                  <button type="button" aria-label="Mas opciones">...</button>
                </article>
              `).join("") : renderEmptyState("Sin publicaciones", "Publica avisos, reglas o recordatorios para la comunidad.")}
            </div>
            <button type="button" class="wellness-admin-panel-link">Ver todas las publicaciones</button>
          </section>

          <section class="wellness-admin-panel wellness-admin-activity-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Actividad de la comunidad</h4>
              <select aria-label="Periodo de actividad"><option>Ultimos 30 dias</option><option>Ultimos 7 dias</option></select>
            </div>
            <div class="wellness-admin-activity-list">
              ${[
                ["Publicaciones", publishedPosts.length + 16, "+6"],
                ["Comentarios", 42, "+12"],
                ["Reacciones", 156, "+20"],
                ["Nuevos miembros", Math.max(12, pendingRequests.length + 9), "+5"]
              ].map(([label, value, delta]) => `
                <div class="wellness-admin-activity-row">
                  <span>${safeText(label)}</span>
                  <div><i style="width:${Math.min(100, Number(value))}%"></i></div>
                  <strong>${safeText(value)}</strong>
                  <small>${safeText(delta)} vs. mes anterior</small>
                </div>
              `).join("")}
            </div>
            <div class="wellness-admin-activity-chart" aria-hidden="true">
              ${[18, 42, 35, 48, 30, 56, 45, 64, 52, 72, 58, 76, 68, 88].map((value) => `<i style="height:${value}px"></i>`).join("")}
            </div>
          </section>
        </div>
      </section>
    `;
  }

  function adminModuleItems() {
    return [
      { id: "dashboard", label: "Inicio", icon: "spark" },
      { id: "members", label: "Miembros", icon: "users" },
      { id: "requests", label: "Solicitudes", icon: "user-plus" },
      { id: "events", label: "Eventos", icon: "calendar" },
      { id: "posts", label: "Publicaciones", icon: "chat" },
      { id: "calendar", label: "Calendario", icon: "clock" },
      { id: "analytics", label: "Analytics", icon: "target" },
      { id: "settings", label: "Configuracion", icon: "shield" },
      { id: "automations", label: "Automatizaciones", icon: "bolt" },
      { id: "gamification", label: "Gamificacion", icon: "medal" }
    ];
  }

  function adminCommunityContext(state, community) {
    const communityEventIds = new Set(state.communityEvents.filter((event) => event.communityId === community.id).map((event) => event.id));
    const members = state.communityMembers
      .filter((member) => member.communityId === community.id && ["active", "invited", "suspended"].includes(member.status))
      .sort((a, b) => new Date(a.joinedAt || 0) - new Date(b.joinedAt || 0));
    const activeMembers = members.filter((member) => member.status === "active");
    const requests = state.communityJoinRequests
      .filter((request) => request.communityId === community.id && request.status === "pending")
      .sort((a, b) => new Date(b.requestedAt) - new Date(a.requestedAt));
    const events = state.communityEvents
      .filter((event) => event.communityId === community.id)
      .sort((a, b) => new Date(a.startAt) - new Date(b.startAt));
    const now = new Date();
    const upcomingEvents = events.filter((event) => event.status === "published" && new Date(event.startAt) >= now);
    const pastEvents = events.filter((event) => new Date(event.startAt) < now);
    const posts = communityPostsFor(state, community.id);
    const publishedPosts = posts.filter((post) => post.status === "published");
    const reservations = state.eventReservations.filter((reservation) => communityEventIds.has(reservation.eventId));
    const invitations = state.communityInvitations.filter((invite) => communityEventIds.has(invite.eventId));
    const averageOccupancy = Math.round(events.reduce((total, event) => {
      const seats = eventSeats(state, event);
      return total + ((seats.confirmed / Math.max(1, Number(event.capacity || 1))) * 100);
    }, 0) / Math.max(1, events.length));
    const weekStart = new Date(now);
    weekStart.setDate(now.getDate() - 7);
    return {
      members,
      activeMembers,
      requests,
      events,
      upcomingEvents,
      pastEvents,
      posts,
      publishedPosts,
      reservations,
      invitations,
      newMembersWeek: activeMembers.filter((member) => new Date(member.joinedAt || 0) >= weekStart).length,
      postsWeek: posts.filter((post) => new Date(post.createdAt || 0) >= weekStart).length,
      comments: publishedPosts.length * 7 + 28,
      reactions: publishedPosts.length * 31 + 94,
      averageOccupancy,
      participation: Math.min(96, Math.max(42, 58 + activeMembers.length + requests.length * 2))
    };
  }

  function adminMemberProfile(state, member, index = 0) {
    const names = ["Ana Sofia Ruiz", "Diego Ramirez", "Mariana Lopez", "Carlos Ruiz", "Laura Hernandez", "Valeria Torres", "Roberto Salinas"];
    const cities = ["CDMX", "Guadalajara", "Monterrey", "Puebla", "Queretaro", "Merida"];
    const interests = [
      ["Yoga", "Respiracion", "Meditacion"],
      ["Running", "Cardio", "Habitos"],
      ["Nutricion", "Recetas", "Apego"],
      ["Salud mental", "Mindfulness", "Sueno"]
    ];
    const displayName = memberDisplayName(state, member) || names[index % names.length];
    const participation = Math.min(98, 52 + (index * 9) + (member.role === "owner" ? 22 : member.role === "admin" ? 18 : 0));
    return {
      name: displayName,
      username: String(displayName).toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/(^_|_$)/g, ""),
      city: cities[index % cities.length],
      age: 27 + ((index * 5) % 24),
      wellnessLevel: ["Bronce", "Plata", "Oro", "Balance Pro"][index % 4],
      participation,
      events: 2 + (index % 8),
      posts: index % 5,
      lastActivity: ["Hace 12 min", "Hoy 08:45", "Ayer 19:20", "Hace 3 dias"][index % 4],
      interests: interests[index % interests.length]
    };
  }

  function adminMetricValue(value) {
    return typeof value === "number" ? value.toLocaleString("es-MX") : value;
  }

  function renderAdminKpi({ iconName, label, value, detail, tone = "teal" }) {
    return `
      <article class="wellness-admin-kpi wellness-admin-kpi-${safeText(tone)}">
        <span>${icon(iconName)}</span>
        <div>
          <small>${safeText(label)}</small>
          <strong>${safeText(adminMetricValue(value))}</strong>
          <p>${safeText(detail)}</p>
        </div>
      </article>
    `;
  }

  function renderAdminSidebar(state, community, managed) {
    const modules = adminModuleItems();
    return `
      <aside class="wellness-admin-sidebar">
        <button type="button" class="wellness-admin-back" data-wellness-community-view="home">${icon("chevron-left")} Comunidades</button>
        <div class="wellness-admin-brand">
          <span>${icon("shield")}</span>
          <div>
            <strong>Klini Wellness</strong>
            <small>Admin de comunidad</small>
          </div>
        </div>
        <label class="wellness-admin-community-switch">
          <span>Comunidad activa</span>
          <select data-wellness-admin-community-select>
            ${managed.map((item) => `<option value="${safeText(item.id)}" ${item.id === community.id ? "selected" : ""}>${safeText(item.name)}</option>`).join("")}
          </select>
        </label>
        <nav class="wellness-admin-module-nav" aria-label="Modulos administrativos">
          ${modules.map((item) => `
            <button class="${wellnessUi.adminModule === item.id ? "active" : ""}" type="button" data-wellness-admin-module="${safeText(item.id)}">
              ${icon(item.icon)}
              <span>${safeText(item.label)}</span>
            </button>
          `).join("")}
        </nav>
        <a class="wellness-admin-standalone-link" href="comunidad-publica.html?community=${safeText(community.id)}">${icon("globe")} Vista publica</a>
        <a class="wellness-admin-standalone-link" href="comunidad-admin.html">${icon("globe")} Acceso diferente</a>
      </aside>
    `;
  }

  function adminRequestRelativeTime(value) {
    const sent = new Date(value);
    if (Number.isNaN(sent.getTime())) return "";
    const minutes = Math.max(1, Math.round((Date.now() - sent.getTime()) / 60000));
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.round(minutes / 60);
    if (hours < 24) return `${hours} h`;
    return `${Math.round(hours / 24)} d`;
  }

  function adminRequestGroupLabel(value) {
    const sent = new Date(value);
    if (Number.isNaN(sent.getTime())) return "Recientes";
    const today = dateOnly(new Date());
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    const sentDay = dateOnly(sent);
    if (sentDay === today) return "Hoy";
    if (sentDay === dateOnly(yesterday)) return "Ayer";
    return "Ultimos 7 dias";
  }

  function renderAdminNotificationRow(community, request, index = 0) {
    const profile = adminMemberProfile({ communityJoinRequests: [] }, { userId: request.userId }, index);
    const interests = (request.sharedInterests || profile.interests || []).slice(0, 2);
    return `
      <article class="wellness-admin-notification-row">
        <span class="wellness-admin-notification-avatar">${safeText(request.userInitials || initialsText(request.userName))}</span>
        <div class="wellness-admin-notification-copy">
          <p><strong>${safeText(request.userName)}</strong> solicito unirse a <strong>${safeText(community.name)}</strong>.</p>
          <small>${safeText(adminRequestRelativeTime(request.requestedAt))} - ${safeText(profile.city)} - ${profile.age} anos</small>
          ${interests.length ? `<em>${interests.map(safeText).join(" - ")}</em>` : ""}
          <div class="wellness-admin-notification-actions">
            <button type="button" data-wellness-community-action="accept-request" data-request-id="${safeText(request.id)}">Aceptar</button>
            <button type="button" data-wellness-community-action="reject-request" data-request-id="${safeText(request.id)}">Rechazar</button>
          </div>
        </div>
        <button type="button" class="wellness-admin-notification-chevron" data-wellness-community-action="open-admin-request-flow" data-request-id="${safeText(request.id)}" aria-label="Ver perfil">${icon("chevron-right")}</button>
      </article>
    `;
  }

  function renderAdminRequestFlowDetail(community, request) {
    const profile = adminMemberProfile({ communityJoinRequests: [] }, { userId: request.userId }, 1);
    const interests = request.sharedInterests || profile.interests || [];
    return `
      <aside class="wellness-admin-notification-dropdown wellness-admin-request-flow-dropdown" aria-label="Detalle de solicitud">
        <header class="wellness-admin-request-flow-header">
          <button type="button" class="wellness-admin-message-back" data-wellness-community-action="back-admin-notifications" aria-label="Regresar a solicitudes">${icon("chevron-left")}</button>
          <span class="wellness-admin-notification-avatar">${safeText(request.userInitials || initialsText(request.userName))}</span>
          <div>
            <strong>${safeText(request.userName)}</strong>
            <small>${safeText(community.name)}</small>
          </div>
          <button type="button" class="wellness-admin-message-close" data-wellness-community-action="close-admin-notifications" aria-label="Cerrar solicitudes">${icon("x")}</button>
        </header>
        <section class="wellness-admin-request-flow-body">
          <p>${safeText(request.brief || "Solicitud para unirse a la comunidad.")}</p>
          <div class="wellness-admin-request-flow-meta">
            <span><strong>${safeText(profile.city)}</strong><small>Ciudad</small></span>
            <span><strong>${profile.age}</strong><small>Edad</small></span>
            <span><strong>${profile.participation}%</strong><small>Participacion</small></span>
          </div>
          <div class="wellness-admin-chip-row">${interests.map((item) => `<span>${safeText(item)}</span>`).join("")}</div>
          <dl>
            <div><dt>Comunidades en comun</dt><dd>2</dd></div>
            <div><dt>Miembros en comun</dt><dd>7</dd></div>
            <div><dt>Solicitud enviada</dt><dd>${safeText(adminRequestRelativeTime(request.requestedAt))}</dd></div>
          </dl>
        </section>
        <footer class="wellness-admin-request-flow-actions">
          <button type="button" data-wellness-community-action="reject-request" data-request-id="${safeText(request.id)}">${icon("x")} Rechazar</button>
          <button type="button" data-wellness-community-action="accept-request" data-request-id="${safeText(request.id)}">${icon("check")} Aceptar</button>
        </footer>
      </aside>
    `;
  }

  function renderAdminNotificationDropdown(community, ctx) {
    const requests = ctx.requests || [];
    const firstRequest = requests[0];
    const activeRequest = requests.find((request) => request.id === wellnessUi.activeAdminRequestId);
    if (activeRequest) return renderAdminRequestFlowDetail(community, activeRequest);
    const grouped = requests.slice(0, 7).reduce((acc, request, index) => {
      const label = adminRequestGroupLabel(request.requestedAt);
      acc[label] = acc[label] || [];
      acc[label].push({ request, index });
      return acc;
    }, {});
    const sections = ["Hoy", "Ayer", "Ultimos 7 dias", "Recientes"]
      .filter((label) => grouped[label]?.length)
      .map((label) => `
        <section class="wellness-admin-notification-section">
          <h5>${safeText(label)}</h5>
          ${grouped[label].map((item) => renderAdminNotificationRow(community, item.request, item.index)).join("")}
        </section>
      `)
      .join("");
    const summaryName = firstRequest ? `${safeText(firstRequest.userName)}${requests.length > 1 ? ` + ${requests.length - 1} mas` : ""}` : "Sin solicitudes nuevas";
    return `
      <aside class="wellness-admin-notification-dropdown" aria-label="Solicitudes pendientes">
        <header>
          <div>
            <strong>Solicitudes pendientes</strong>
            <small>${safeText(community.name)}</small>
          </div>
          <div class="wellness-admin-notification-header-actions">
            <span>${requests.length}</span>
            <button type="button" class="wellness-admin-message-close" data-wellness-community-action="close-admin-notifications" aria-label="Cerrar solicitudes">${icon("x")}</button>
          </div>
        </header>
        ${requests.length ? `
          <button type="button" class="wellness-admin-notification-summary" data-wellness-admin-module="requests">
            <span class="wellness-admin-notification-stack">
              ${requests.slice(0, 3).map((request) => `<i>${safeText(request.userInitials || initialsText(request.userName))}</i>`).join("")}
            </span>
            <span>
              <strong>Solicitudes de miembros</strong>
              <small>${summaryName}</small>
            </span>
            <b aria-hidden="true"></b>
            ${icon("chevron-right")}
          </button>
          ${sections}
          <footer>
            <button type="button" data-wellness-admin-module="requests">Ver todas las solicitudes</button>
          </footer>
        ` : `
          <div class="wellness-admin-notification-empty">
            <span>${icon("check")}</span>
            <strong>Todo al dia</strong>
            <p>No hay solicitudes pendientes para esta comunidad.</p>
          </div>
        `}
      </aside>
    `;
  }

  function adminCommunityMessages(community) {
    const communityName = community?.name || "la comunidad";
    return [
      { id: "message-carmen", name: "Carmen Csn", initials: "CC", preview: "Le gusto un mensaje", time: "5 h", unread: true, note: "Pregunta lo que quieras a tus miembros..." },
      { id: "message-denisse", name: "Denisse", initials: "DE", preview: "2 mensajes nuevos", time: "10 h", unread: true, note: "Quiere confirmar su asistencia" },
      { id: "message-grety", name: "Grety Herzig", initials: "GH", preview: "Enviado ayer", time: "Ayer", unread: false, note: "Gracias por el evento" },
      { id: "message-pelayo", name: "Pelayo", initials: "PE", preview: `Pregunta sobre ${communityName}`, time: "2 d", unread: false, note: "Nueva duda de comunidad" },
      { id: "message-yannina", name: "Yannina Romano", initials: "YR", preview: "Enviado el domingo", time: "Dom", unread: false, note: "Seguimiento pendiente" }
    ];
  }

  function renderAdminMessageRow(message) {
    return `
      <button type="button" class="wellness-admin-message-row" data-wellness-community-action="open-admin-message" data-message-id="${safeText(message.id)}">
        <span class="wellness-admin-notification-avatar">${safeText(message.initials)}</span>
        <span class="wellness-admin-message-copy">
          <strong>${safeText(message.name)}</strong>
          <small>${safeText(message.preview)} - ${safeText(message.time)}</small>
        </span>
        ${message.unread ? `<i aria-label="No leido"></i>` : ""}
      </button>
    `;
  }

  function adminMessageThreadDetails(message, community) {
    const username = String(message.name || "miembro").toLowerCase().replace(/[^a-z0-9]+/g, "").slice(0, 18) || "miembro";
    const details = {
      "message-carmen": {
        outgoing: "Gracias por participar en la comunidad.",
        inbound: "Me gusto mucho el mensaje de bienvenida.",
        cardTitle: "Bienvenida a Respira y Avanza",
        cardMeta: "Guia para nuevos miembros"
      },
      "message-denisse": {
        outgoing: "Jajajajajajajaja",
        inbound: "Confirmo asistencia y puedo compartirlo con dos amigas.",
        cardTitle: "Caminata Wellness",
        cardMeta: "Evento recomendado"
      },
      "message-grety": {
        outgoing: "Te envie la informacion del proximo evento.",
        inbound: "Perfecto, manana reviso los horarios.",
        cardTitle: "Agenda semanal",
        cardMeta: "Resumen de comunidad"
      },
      "message-pelayo": {
        outgoing: "Claro, te comparto la respuesta aqui.",
        inbound: message.note || "Tengo una pregunta sobre la comunidad.",
        cardTitle: community.name,
        cardMeta: "Pregunta de miembro"
      },
      "message-yannina": {
        outgoing: "Gracias por escribirnos.",
        inbound: "Me interesa participar en el siguiente reto.",
        cardTitle: "Reto Wellness",
        cardMeta: "Participacion comunitaria"
      }
    };
    return {
      username,
      ...(details[message.id] || {
        outgoing: "Gracias por escribir.",
        inbound: message.note || message.preview,
        cardTitle: community.name,
        cardMeta: "Mensaje de comunidad"
      })
    };
  }

  function renderAdminMessageThread(community, message) {
    const details = adminMessageThreadDetails(message, community);
    return `
      <aside class="wellness-admin-notification-dropdown wellness-admin-message-dropdown wellness-admin-message-thread-dropdown" aria-label="Conversacion con ${safeText(message.name)}">
        <header class="wellness-admin-message-thread-header">
          <button type="button" class="wellness-admin-message-back" data-wellness-community-action="back-admin-messages" aria-label="Regresar a mensajes">${icon("chevron-left")}</button>
          <span class="wellness-admin-notification-avatar">${safeText(message.initials)}</span>
          <div>
            <strong>${safeText(message.name)}</strong>
            <small>${safeText(details.username)}</small>
          </div>
          <div class="wellness-admin-message-thread-actions">
            <button type="button" data-wellness-community-action="admin-message-call" aria-label="Llamar">${icon("phone")}</button>
            <button type="button" data-wellness-community-action="admin-message-video" aria-label="Videollamada">${icon("video")}</button>
            <button type="button" class="wellness-admin-message-close" data-wellness-community-action="close-admin-messages" aria-label="Cerrar mensajes">${icon("x")}</button>
          </div>
        </header>
        <section class="wellness-admin-message-thread-body">
          <time>Ayer a las 2:48 p.m.</time>
          <p class="wellness-admin-chat-bubble outgoing">${safeText(details.outgoing)}</p>
          <div class="wellness-admin-message-divider"><span>Nuevos mensajes</span></div>
          <time>1:31 a.m.</time>
          <article class="wellness-admin-message-post-preview">
            <div>
              <span>${icon("spark")}</span>
              <strong>Klini Wellness</strong>
            </div>
            <section>
              <strong>${safeText(details.cardTitle)}</strong>
              <p>${safeText(details.cardMeta)} para ${safeText(community.name)}.</p>
            </section>
          </article>
          <p class="wellness-admin-chat-bubble incoming">${safeText(details.inbound)}</p>
          <p class="wellness-admin-chat-bubble incoming small">Gracias, quedo pendiente.</p>
        </section>
        <div class="wellness-admin-message-composer">
          <button type="button" data-wellness-community-action="admin-message-camera" aria-label="Camara">${icon("camera")}</button>
          <input type="text" placeholder="Mensaje..." aria-label="Mensaje" />
          <button type="button" data-wellness-community-action="admin-message-audio" aria-label="Audio">${icon("mic")}</button>
          <button type="button" data-wellness-community-action="admin-message-media" aria-label="Imagen">${icon("image")}</button>
          <button type="button" data-wellness-community-action="compose-admin-message" aria-label="Enviar">${icon("send")}</button>
        </div>
      </aside>
    `;
  }

  function renderAdminMessagesDropdown(community) {
    const messages = adminCommunityMessages(community);
    const unreadCount = messages.filter((message) => message.unread).length;
    const activeMessage = messages.find((message) => message.id === wellnessUi.activeAdminMessageId);
    if (activeMessage) return renderAdminMessageThread(community, activeMessage);
    return `
      <aside class="wellness-admin-notification-dropdown wellness-admin-message-dropdown" aria-label="Mensajes recibidos">
        <header>
          <div>
            <strong>${safeText(currentCommunityAdmin().fullName)}</strong>
            <small>${safeText(community.name)}</small>
          </div>
          <button type="button" class="wellness-admin-message-close" data-wellness-community-action="close-admin-messages" aria-label="Cerrar mensajes">${icon("x")}</button>
        </header>
        <div class="wellness-admin-message-search">
          ${icon("search")}
          <span>Buscar mensajes o miembros</span>
        </div>
        <div class="wellness-admin-message-notes" aria-label="Conversaciones destacadas">
          ${messages.slice(0, 4).map((message, index) => `
            <button type="button" data-wellness-community-action="open-admin-message" data-message-id="${safeText(message.id)}">
              <span class="wellness-admin-notification-avatar">${safeText(message.initials)}</span>
              ${message.unread ? `<i aria-hidden="true"></i>` : ""}
              <small>${index === 0 ? "Tu nota" : safeText(message.name.split(" ")[0])}</small>
            </button>
          `).join("")}
        </div>
        <div class="wellness-admin-message-tab-row">
          <strong>Mensajes</strong>
          <button type="button" data-wellness-community-action="admin-message-requests">Solicitudes (1)</button>
        </div>
        <section class="wellness-admin-message-list">
          ${messages.map(renderAdminMessageRow).join("")}
        </section>
        <footer>
          <button type="button" data-wellness-community-action="compose-admin-message">${icon("send")} Nuevo mensaje</button>
        </footer>
      </aside>
    `;
  }

  function renderAdminTopbar(admin, community, ctx) {
    const adminMessages = adminCommunityMessages(community);
    const unreadMessages = adminMessages.filter((message) => message.unread).length;
    return `
      <div class="wellness-admin-console-topbar wellness-admin-console-topbar-actions-only">
        <div class="wellness-admin-top-actions">
          <button type="button" data-wellness-modal="create-event">${icon("calendar")} Crear evento</button>
          <button type="button" data-wellness-admin-focus-post>${icon("edit")} Publicar</button>
          <button type="button" data-wellness-community-action="admin-invite-members" data-community-id="${safeText(community.id)}">${icon("user-plus")} Invitar</button>
          <div class="wellness-admin-notification-wrap wellness-admin-message-wrap">
            <button type="button" class="wellness-admin-notification-button wellness-admin-message-button ${wellnessUi.messagesOpen ? "active" : ""}" data-wellness-community-action="toggle-admin-messages" aria-label="Mensajes recibidos" aria-expanded="${wellnessUi.messagesOpen ? "true" : "false"}">
              ${icon("send")}
              <strong>Mensajes</strong>
              ${unreadMessages ? `<span>${unreadMessages}</span>` : ""}
            </button>
            ${wellnessUi.messagesOpen ? renderAdminMessagesDropdown(community) : ""}
          </div>
          <div class="wellness-admin-notification-wrap">
            <button type="button" class="wellness-admin-notification-button ${wellnessUi.notificationsOpen ? "active" : ""}" data-wellness-community-action="toggle-admin-notifications" aria-label="Solicitudes pendientes" aria-expanded="${wellnessUi.notificationsOpen ? "true" : "false"}">
              ${icon("bell")}
              <strong>Solicitudes</strong>
              ${ctx.requests.length ? `<span>${ctx.requests.length}</span>` : ""}
            </button>
            ${wellnessUi.notificationsOpen ? renderAdminNotificationDropdown(community, ctx) : ""}
          </div>
          <button type="button" class="wellness-admin-creator-button" data-wellness-community-action="creator-profile" data-community-id="${safeText(community.id)}" aria-label="Perfil del creador de la comunidad">
            ${renderAdminProfileAvatar(admin)}
            <span>
              <strong>${safeText(admin.fullName)}</strong>
              <small>${safeText(admin.title || admin.role)}</small>
            </span>
          </button>
        </div>
      </div>
    `;
  }

  function renderAdminCommunityHero(state, community, ctx) {
    return `
      <section class="wellness-admin-console-hero wellness-community-cover-${safeText(community.coverTone || "mint")}" ${communityBackgroundStyle(community)}>
        <div class="wellness-admin-media-actions">
          <button type="button" data-wellness-community-action="admin-edit-community" data-community-id="${safeText(community.id)}">${icon("camera")} Fondo</button>
          <button type="button" data-wellness-community-action="admin-edit-community" data-community-id="${safeText(community.id)}">${icon("edit")} Logotipo</button>
        </div>
        ${renderCommunityLogo(community)}
        <div>
          <small>${safeText(community.category)} - ${community.accessType === "open" ? "Abierta" : "Privada"} - ${safeText(community.city || "Online")}</small>
          <h4>${safeText(community.name)}</h4>
          <p>${safeText(community.shortDescription || community.description)}</p>
          <div class="wellness-admin-community-meta">
            <span>${icon("users")} ${safeText(community.memberCount || ctx.activeMembers.length)} miembros</span>
            <span>${icon("target")} ${ctx.participation}% participacion</span>
            <span>${icon("calendar")} ${ctx.upcomingEvents.length} eventos proximos</span>
          </div>
        </div>
        <div class="wellness-admin-health-score">
          <span>Salud de comunidad</span>
          <strong>${Math.min(99, ctx.participation + 8)}</strong>
          <small>Crecimiento, retencion y actividad</small>
        </div>
      </section>
    `;
  }

  function renderAdminKpiGrid(ctx, community) {
    const totalMembers = Number(community.memberCount || ctx.activeMembers.length);
    const sentInvitations = ctx.invitations.length + 18;
    const acceptedInvitations = Math.max(0, Math.round(sentInvitations * 0.62));
    const kpis = [
      { iconName: "users", label: "Total de miembros", value: totalMembers, detail: `${ctx.newMembersWeek} nuevos esta semana`, tone: "teal" },
      { iconName: "user-plus", label: "Nuevos miembros", value: ctx.newMembersWeek, detail: "Ultimos 7 dias", tone: "blue" },
      { iconName: "spark", label: "Miembros activos", value: ctx.activeMembers.length, detail: `${ctx.participation}% de participacion`, tone: "green" },
      { iconName: "bell", label: "Solicitudes pendientes", value: ctx.requests.length, detail: "Por revisar", tone: "orange" },
      { iconName: "calendar", label: "Eventos proximos", value: ctx.upcomingEvents.length, detail: "Publicados", tone: "purple" },
      { iconName: "check", label: "Eventos realizados", value: ctx.pastEvents.length, detail: "Historico reciente", tone: "teal" },
      { iconName: "ticket", label: "Ocupacion promedio", value: `${ctx.averageOccupancy}%`, detail: "Eventos publicados", tone: "green" },
      { iconName: "edit", label: "Publicaciones semana", value: ctx.postsWeek, detail: `${ctx.publishedPosts.length} publicadas`, tone: "blue" },
      { iconName: "chat", label: "Comentarios", value: ctx.comments, detail: "+12 vs. semana anterior", tone: "purple" },
      { iconName: "heart", label: "Reacciones", value: ctx.reactions, detail: "Interacciones totales", tone: "red" },
      { iconName: "share", label: "Invitaciones enviadas", value: sentInvitations, detail: `${acceptedInvitations} aceptadas`, tone: "orange" },
      { iconName: "target", label: "Participacion", value: `${ctx.participation}%`, detail: "Indice comunitario", tone: "teal" }
    ];
    return `<div class="wellness-admin-kpi-grid wellness-admin-kpi-grid-large">${kpis.map(renderAdminKpi).join("")}</div>`;
  }

  function renderAdminChart(title, subtitle, values, tone = "teal") {
    return `
      <article class="wellness-admin-chart-card wellness-admin-chart-${safeText(tone)}">
        <div>
          <strong>${safeText(title)}</strong>
          <small>${safeText(subtitle)}</small>
        </div>
        <div class="wellness-admin-chart-bars" aria-hidden="true">
          ${values.map((value) => `<i style="height:${Math.max(16, Math.min(96, value))}%"></i>`).join("")}
        </div>
      </article>
    `;
  }

  function renderAdminDashboard(state, community, ctx) {
    const nextEvents = ctx.upcomingEvents.slice(0, 2);
    return `
      <div class="wellness-admin-module-content">
        ${renderAdminCommunityHero(state, community, ctx)}
        ${renderAdminKpiGrid(ctx, community)}
        <div class="wellness-admin-chart-grid">
          ${renderAdminChart("Crecimiento de miembros", "Altas por semana", [34, 44, 51, 49, 62, 71, 86], "teal")}
          ${renderAdminChart("Participacion semanal", "Publicaciones, comentarios y reservas", [46, 58, 52, 70, 66, 82, 76], "purple")}
          ${renderAdminChart("Asistencia a eventos", "Reservas confirmadas", [28, 36, 42, 64, 58, 72, 80], "orange")}
          ${renderAdminChart("Actividad por dia", "Usuarios activos", [35, 42, 61, 49, 76, 88, 68], "blue")}
          ${renderAdminChart("Nuevos registros", "Ultimos 30 dias", [18, 24, 32, 35, 48, 54, 67], "green")}
        </div>
        <div class="wellness-admin-dashboard-panels">
          <section class="wellness-admin-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Proximos eventos</h4>
              <button type="button" data-wellness-admin-module="events">Ver eventos</button>
            </div>
            <div class="wellness-admin-event-list">
              ${nextEvents.length ? nextEvents.map((event) => renderAdminEventManagerCard(state, event, true)).join("") : renderEmptyState("Sin eventos proximos", "Crea un evento para activar reservas.")}
            </div>
          </section>
          <section class="wellness-admin-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Solicitudes recientes</h4>
              <button type="button" data-wellness-admin-module="requests">Ver solicitudes</button>
            </div>
            <div class="wellness-admin-request-card-list">
              ${ctx.requests.length ? ctx.requests.slice(0, 3).map((request, index) => renderAdminRequestCard(request, index, true)).join("") : renderEmptyState("Sin solicitudes", "Las solicitudes pendientes apareceran aqui.")}
            </div>
          </section>
          <section class="wellness-admin-panel wellness-admin-premium-panel">
            <div class="wellness-admin-panel-heading">
              <h4>Funciones premium Klini</h4>
              <button type="button" data-wellness-community-action="admin-ai">IA</button>
            </div>
            ${[
              ["IA para publicaciones", "Genera anuncios, retos y mensajes de bienvenida."],
              ["IA para eventos", "Crea titulo, agenda, imagen y recordatorios desde una idea."],
              ["IA moderadora", "Detecta spam, lenguaje ofensivo o contenido inapropiado."],
              ["Segmentacion inteligente", "Campanas por actividad, intereses, ciudad e historial."]
            ].map(([title, detail]) => `<article><strong>${safeText(title)}</strong><p>${safeText(detail)}</p></article>`).join("")}
          </section>
        </div>
      </div>
    `;
  }

  function renderAdminMembers(state, community, ctx) {
    return `
      <section class="wellness-admin-panel wellness-admin-members-console">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Administracion de miembros</h4>
            <p>CRM de comunidad con roles, notas, invitaciones y acciones de moderacion.</p>
          </div>
          <button type="button" data-wellness-community-action="admin-export" data-community-id="${safeText(community.id)}">Exportar</button>
        </div>
        <div class="wellness-admin-member-controls">
          <input type="search" placeholder="Buscar por nombre, usuario, correo, ciudad o intereses" aria-label="Buscar miembros">
          <select><option>Activos</option><option>Inactivos</option><option>Suspendidos</option><option>Administradores</option><option>Moderadores</option><option>Nuevos</option><option>Pendientes</option></select>
          <select><option>Todos los roles</option><option>Administrador</option><option>Moderador</option><option>Miembro</option></select>
          <select><option>Participacion</option><option>Mayor asistencia</option><option>Ultima actividad</option></select>
        </div>
        <div class="wellness-admin-member-table wellness-admin-member-table-wide">
          <div class="wellness-admin-member-table-head">
            <span>Miembro</span><span>Ciudad / edad</span><span>Nivel</span><span>Participacion</span><span>Eventos</span><span>Publicaciones</span><span>Rol</span><span>Acciones</span>
          </div>
          ${ctx.members.map((member, index) => {
            const profile = adminMemberProfile(state, member, index);
            return `
              <article class="wellness-admin-member-row wellness-admin-member-row-wide">
                <div>
                  <span class="wellness-community-avatar">${safeText(initialsText(profile.name))}</span>
                  <strong>${safeText(profile.name)}<small>@${safeText(profile.username)} - ${safeText(profile.lastActivity)}</small></strong>
                </div>
                <span>${safeText(profile.city)}<small>${profile.age} anos</small></span>
                <span>${safeText(profile.wellnessLevel)}</span>
                <span><i class="wellness-admin-meter"><b style="width:${profile.participation}%"></b></i>${profile.participation}%</span>
                <span>${profile.events}</span>
                <span>${profile.posts}</span>
                <select data-wellness-admin-member-role="${safeText(member.id)}" ${member.role === "owner" ? "disabled" : ""}>${renderRoleOptions(member.role)}</select>
                <div class="wellness-admin-row-actions">
                  <button type="button" data-wellness-community-action="admin-profile-view" data-member-id="${safeText(member.id)}">Perfil</button>
                  <button type="button" data-wellness-community-action="member-message" data-member-id="${safeText(member.id)}">Mensaje</button>
                  <button type="button" data-wellness-community-action="member-invite-event" data-member-id="${safeText(member.id)}">Evento</button>
                  <button type="button" data-wellness-community-action="member-note" data-member-id="${safeText(member.id)}">Notas</button>
                  <button type="button" data-wellness-community-action="member-suspend" data-member-id="${safeText(member.id)}" ${member.role === "owner" ? "disabled" : ""}>Suspender</button>
                  <button type="button" data-wellness-community-action="remove-member" data-member-id="${safeText(member.id)}" ${member.role === "owner" ? "disabled" : ""}>Expulsar</button>
                </div>
              </article>
            `;
          }).join("")}
        </div>
      </section>
    `;
  }

  function renderAdminRequestCard(request, index = 0, compact = false) {
    const profile = adminMemberProfile({ communityJoinRequests: [] }, { userId: request.userId }, index);
    const interests = request.sharedInterests || profile.interests;
    return `
      <article class="wellness-admin-request-card ${compact ? "compact" : ""}">
        <span class="wellness-community-avatar">${safeText(request.userInitials || initialsText(request.userName))}</span>
        <div>
          <small>${safeText(formatMetricTime(request.requestedAt))}</small>
          <h4>${safeText(request.userName)}</h4>
          <p>${safeText(profile.city)} - ${profile.age} anos</p>
          <div class="wellness-admin-chip-row">${interests.map((item) => `<span>${safeText(item)}</span>`).join("")}</div>
          <dl>
            <div><dt>Comunidades en comun</dt><dd>${1 + (index % 3)}</dd></div>
            <div><dt>Miembros en comun</dt><dd>${3 + (index * 2)}</dd></div>
          </dl>
        </div>
        <footer>
          <button type="button" data-wellness-community-action="accept-request" data-request-id="${safeText(request.id)}">${icon("check")} Aceptar</button>
          <button type="button" data-wellness-community-action="reject-request" data-request-id="${safeText(request.id)}">${icon("x")} Rechazar</button>
          <button type="button" data-wellness-community-action="admin-profile-view" data-request-id="${safeText(request.id)}">Ver perfil</button>
        </footer>
      </article>
    `;
  }

  function renderAdminRequests(community, ctx) {
    return `
      <section class="wellness-admin-panel">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Solicitudes</h4>
            <p>Revision tipo red social con aprobacion individual o masiva.</p>
          </div>
          <button type="button" data-wellness-community-action="accept-all-requests" data-community-id="${safeText(community.id)}">Aceptar varias</button>
        </div>
        <div class="wellness-admin-request-grid">
          ${ctx.requests.length ? ctx.requests.map((request, index) => renderAdminRequestCard(request, index)).join("") : renderEmptyState("Sin solicitudes pendientes", "Cuando alguien solicite acceso, aparecera aqui.")}
        </div>
      </section>
    `;
  }

  function adminEventModalityLabel(event) {
    if (event.modality === "remote") return "Virtual";
    if (event.modality === "mixed") return "Mixto";
    return "Presencial";
  }

  function renderAdminEventManagerCard(state, event, compact = false) {
    const start = new Date(event.startAt);
    const seats = eventSeats(state, event);
    const percent = Math.min(100, Math.round((seats.confirmed / Math.max(1, Number(event.capacity || 1))) * 100));
    return `
      <article class="wellness-admin-event-manager ${compact ? "compact" : ""}">
        <div class="wellness-admin-event-art wellness-community-cover-${safeText(event.coverTone || "mint")}"><span>${icon(categoryIcon(event.category))}</span></div>
        <div class="wellness-admin-event-copy">
          <small>${safeText(event.category)} - ${safeText(adminEventModalityLabel(event))}</small>
          <h4>${safeText(event.title)}</h4>
          <p>${safeText(start.toLocaleDateString("es-MX", { weekday: "short", day: "2-digit", month: "short" }))} ${safeText(start.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" }))} - ${safeText(event.locationName || event.remoteUrl || "Por definir")}</p>
          <div class="wellness-admin-event-meta">
            <span>Cupo ${safeText(event.capacity)}</span>
            <span>Reservados ${seats.confirmed}</span>
            <span>Espera ${seats.waitlisted}</span>
            <span>${safeText(communityStatusLabel(event.status))}</span>
          </div>
          <div class="wellness-admin-progress"><span style="width:${percent}%"></span></div>
        </div>
        <select data-wellness-admin-event-status="${safeText(event.id)}">${renderStatusOptions([["published", "Publicado"], ["draft", "Borrador"], ["cancelled", "Cancelado"]], event.status)}</select>
        <div class="wellness-admin-event-actions">
          <button type="button" data-wellness-event-open="${safeText(event.id)}">Editar</button>
          <button type="button" data-wellness-community-action="duplicate-event" data-event-id="${safeText(event.id)}">Duplicar</button>
          <button type="button" data-wellness-community-action="cancel-event" data-event-id="${safeText(event.id)}">Cancelar</button>
          <button type="button" data-wellness-community-action="delete-event" data-event-id="${safeText(event.id)}">Eliminar</button>
          <button type="button" data-wellness-community-action="admin-event-attendees" data-event-id="${safeText(event.id)}">Asistentes</button>
          <button type="button" data-wellness-community-action="send-event-reminder" data-event-id="${safeText(event.id)}">Recordatorio</button>
          <button type="button" data-wellness-community-action="close-reservations" data-event-id="${safeText(event.id)}">Cerrar reservas</button>
        </div>
      </article>
    `;
  }

  function renderAdminEvents(state, community, ctx) {
    return `
      <section class="wellness-admin-panel">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Eventos</h4>
            <p>Gestor completo para presenciales, virtuales y mixtos.</p>
          </div>
          <button type="button" data-wellness-modal="create-event">${icon("plus")} Crear evento</button>
        </div>
        <div class="wellness-admin-filter-row">
          ${["Proximos", "Finalizados", "Cancelados", "Borradores"].map((filter, index) => `<button class="${index === 0 ? "active" : ""}" type="button">${safeText(filter)}</button>`).join("")}
        </div>
        <div class="wellness-admin-event-manager-list">
          ${ctx.events.length ? ctx.events.map((event) => renderAdminEventManagerCard(state, event)).join("") : renderEmptyState("Sin eventos", "Crea un evento para comenzar.")}
        </div>
      </section>
    `;
  }

  function renderAdminPosts(community, ctx) {
    return `
      <section class="wellness-admin-panel">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Publicaciones</h4>
            <p>Contenido tipo grupo social: texto, media, encuestas, documentos y enlaces.</p>
          </div>
          <button type="button" data-wellness-community-action="admin-ai">Generar con IA</button>
        </div>
        <form class="wellness-admin-post-form wellness-admin-post-form-rich" data-wellness-community-post-form>
          <input type="hidden" name="communityId" value="${safeText(community.id)}">
          <select name="postType"><option>Texto</option><option>Imagen</option><option>Video</option><option>Encuesta</option><option>Documento</option><option>PDF</option><option>Audio</option><option>Link</option></select>
          <input name="title" placeholder="Titulo de publicacion" required>
          <textarea name="body" rows="3" placeholder="Escribe una publicacion para los miembros" required></textarea>
          <label><span>Programar</span><input name="scheduleAt" type="datetime-local"></label>
          <label><span>Comentarios</span><select name="commentsMode"><option value="open">Permitir</option><option value="limited">Limitar</option><option value="closed">Bloquear</option></select></label>
          <label><span>Reacciones</span><select name="reactionsEnabled"><option value="true">Activadas</option><option value="false">Desactivadas</option></select></label>
          <label><input name="pinned" type="checkbox" value="true"> Fijar</label>
          <label><input name="highlighted" type="checkbox" value="true"> Destacar</label>
          <button type="submit" class="wellness-primary-action">${icon("plus")} Publicar</button>
        </form>
        <div class="wellness-admin-content-list wellness-admin-content-list-rich">
          ${ctx.posts.length ? ctx.posts.map((post) => `
            <article class="wellness-admin-content-item">
              <span class="wellness-community-avatar">${safeText(initialsText(post.authorName))}</span>
              <div>
                <strong>${safeText(post.title || post.authorName)} <small>${safeText(communityStatusLabel(post.status))}</small></strong>
                <p>${safeText(post.body)}</p>
                <footer><span>${icon("heart")} ${post.pinned ? "34" : "18"}</span><span>${icon("chat")} ${post.pinned ? "9" : "3"}</span><button type="button" data-wellness-community-action="toggle-post-pin" data-post-id="${safeText(post.id)}">${post.pinned ? "Desfijar" : "Fijar"}</button><button type="button" data-wellness-community-action="delete-post" data-post-id="${safeText(post.id)}">Eliminar</button></footer>
              </div>
              <select data-wellness-admin-post-status="${safeText(post.id)}">${renderStatusOptions([["published", "Publicado"], ["draft", "Borrador"], ["hidden", "Oculto"]], post.status)}</select>
            </article>
          `).join("") : renderEmptyState("Sin publicaciones", "Publica avisos, documentos o encuestas para la comunidad.")}
        </div>
      </section>
    `;
  }

  function renderAdminCalendar(ctx) {
    const labels = ["Lun", "Mar", "Mie", "Jue", "Vie", "Sab", "Dom"];
    const calendarItems = [
      ...ctx.upcomingEvents.slice(0, 5).map((event, index) => ({ label: event.title, type: "Evento", day: index % 7 })),
      { label: "Publicacion programada", type: "Post", day: 2 },
      { label: "Cumpleanos de miembros", type: "Cumpleanos", day: 3 },
      { label: "Reto Wellness semanal", type: "Reto", day: 4 },
      { label: "Encuesta de satisfaccion", type: "Encuesta", day: 5 },
      { label: "Sesion virtual", type: "Sesion", day: 6 }
    ];
    return `
      <section class="wellness-admin-panel">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Calendario</h4>
            <p>Vista tipo agenda para eventos, publicaciones, cumpleanos, retos, encuestas y sesiones.</p>
          </div>
          <button type="button" data-wellness-modal="create-event">Nuevo</button>
        </div>
        <div class="wellness-admin-calendar-grid">
          ${labels.map((label, day) => `
            <article>
              <strong>${safeText(label)}</strong>
              ${calendarItems.filter((item) => item.day === day).map((item) => `
                <button draggable="true" type="button" data-wellness-community-action="admin-calendar-move">
                  <small>${safeText(item.type)}</small>
                  <span>${safeText(item.label)}</span>
                </button>
              `).join("")}
            </article>
          `).join("")}
        </div>
        <div class="wellness-admin-calendar-actions">
          <button type="button" data-wellness-community-action="admin-calendar-duplicate">Duplicar</button>
          <button type="button" data-wellness-community-action="admin-calendar-move">Cambiar fecha</button>
          <button type="button" data-wellness-community-action="admin-calendar-move">Cambiar horario</button>
        </div>
      </section>
    `;
  }

  function renderAdminAnalytics(ctx) {
    const heatValues = Array.from({ length: 35 }, (_, index) => 18 + ((index * 13) % 76));
    const funnel = [["Invitados", 120], ["Registrados", 88], ["Activos", ctx.activeMembers.length], ["Reservan", Math.max(8, ctx.reservations.length)], ["Asisten", Math.max(4, Math.round(ctx.reservations.length * 0.72))]];
    return `
      <section class="wellness-admin-panel">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Analytics</h4>
            <p>Retencion, participacion, asistencia, horarios pico y contenido mas exitoso.</p>
          </div>
          <select aria-label="Periodo"><option>Ultimos 30 dias</option><option>Ultimos 7 dias</option><option>Este trimestre</option></select>
        </div>
        <div class="wellness-admin-analytics-grid">
          ${renderAdminChart("Miembros activos", "Usuarios con actividad reciente", [42, 56, 62, 64, 70, 76, 81], "teal")}
          ${renderAdminChart("Retencion", "Miembros que regresan", [66, 68, 71, 73, 76, 79, 82], "green")}
          ${renderAdminChart("Eventos llenos", "Ocupacion por evento", [38, 55, 72, 91, 64, 84, 78], "orange")}
          ${renderAdminChart("Horas pico", "Actividad por hora", [18, 22, 36, 58, 74, 88, 61], "purple")}
          <article class="wellness-admin-heatmap">
            <strong>Heatmap de actividad</strong>
            <div>${heatValues.map((value) => `<i style="opacity:${value / 100}"></i>`).join("")}</div>
          </article>
          <article class="wellness-admin-funnel">
            <strong>Embudo de participacion</strong>
            ${funnel.map(([label, value]) => `<div><span>${safeText(label)}</span><i style="width:${Math.min(100, Number(value))}%"></i><b>${safeText(value)}</b></div>`).join("")}
          </article>
        </div>
      </section>
    `;
  }

  function renderAdminSettings(community) {
    return `
      <section class="wellness-admin-panel">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Configuracion</h4>
            <p>Datos generales, privacidad, solicitudes, reglas, moderacion, notificaciones y permisos.</p>
          </div>
          <button type="button" data-wellness-community-action="admin-edit-community" data-community-id="${safeText(community.id)}">Editar general</button>
        </div>
        <div class="wellness-admin-settings-grid">
          <article class="wellness-admin-create-user-card">
            <h5>Alta de administrador</h5>
            <p>Usuario separado del acceso de pacientes y medicos.</p>
            <label><span>Nombre</span><input placeholder="Nombre del administrador"></label>
            <label><span>Correo</span><input type="email" placeholder="correo@empresa.com"></label>
            <label><span>Rol</span><select><option>Administrador</option><option>Moderador</option><option>Creador</option></select></label>
            <button type="button" data-wellness-community-action="admin-create-user">Crear acceso</button>
          </article>
          <article>
            <h5>General</h5>
            <p>${safeText(community.name)}</p>
            <small>${safeText(community.description)}</small>
          </article>
          ${renderAdminTool(community, "users", "Comunidad abierta", community.accessType === "open" ? "Ingreso automatico" : "Ingreso manual", "open")}
          ${renderAdminTool(community, "search", "Visible en busqueda", community.visibility === "hidden" ? "Oculta" : "Visible", "visible")}
          ${renderAdminTool(community, "chat", "Publicaciones de miembros", community.allowMemberPosts === false ? "Solo staff" : "Permitidas", "posts")}
          ${[
            ["Preguntas para ingresar", "3 preguntas activas"],
            ["Reglas", "7 reglas publicadas"],
            ["Moderacion", "Revision preventiva activa"],
            ["Notificaciones", "Eventos, retos y reportes"],
            ["Permisos", "Admins, moderadores y creadores"]
          ].map(([title, detail]) => `<article class="wellness-admin-setting-row"><strong>${safeText(title)}</strong><small>${safeText(detail)}</small><button type="button" data-wellness-community-action="admin-tool-info" data-community-id="${safeText(community.id)}">${icon("chevron-right")}</button></article>`).join("")}
        </div>
      </section>
    `;
  }

  function renderAdminAutomations() {
    const automations = [
      ["Bienvenida", "Enviar mensaje cuando un usuario entra."],
      ["Cumpleanos", "Enviar felicitacion y puntos Wellness."],
      ["Encuesta", "Enviar encuesta despues de eventos."],
      ["Reto semanal", "Publicar reto los lunes."],
      ["Resumen mensual", "Enviar reporte de comunidad."],
      ["Recordatorio de evento", "24 h, 1 h y 15 min antes."],
      ["Reactivacion", "Mensaje si no entra hace 15 dias."],
      ["Insignias y puntos", "Otorgar medallas por misiones."]
    ];
    return `
      <section class="wellness-admin-panel">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Automatizaciones</h4>
            <p>Flujos automaticos para retencion, eventos, retos e insignias.</p>
          </div>
          <button type="button" data-wellness-community-action="admin-ai">Crear con IA</button>
        </div>
        <div class="wellness-admin-automation-grid">
          ${automations.map(([title, detail], index) => `
            <article>
              <span>${icon(index % 2 ? "bell" : "bolt")}</span>
              <strong>${safeText(title)}</strong>
              <p>${safeText(detail)}</p>
              <button class="wellness-admin-toggle ${index < 5 ? "active" : ""}" type="button" data-wellness-community-action="toggle-automation" aria-label="${safeText(title)}"><i></i></button>
            </article>
          `).join("")}
        </div>
      </section>
    `;
  }

  function renderAdminGamification(ctx) {
    const missions = [
      ["Participar en 5 eventos", 72],
      ["Completar 20 dias de ejercicio", 54],
      ["Publicar 10 veces", 38],
      ["Invitar amigos", 64],
      ["Asistir a una meditacion", 88],
      ["10 000 pasos por una semana", 46]
    ];
    return `
      <section class="wellness-admin-panel">
        <div class="wellness-admin-panel-heading">
          <div>
            <h4>Gamificacion</h4>
            <p>Puntos, niveles, insignias, retos, ranking, misiones y medallas.</p>
          </div>
          <button type="button" data-wellness-community-action="toggle-gamification">Configurar</button>
        </div>
        <div class="wellness-admin-game-grid">
          <article><span>${icon("gift")}</span><strong>Puntos Wellness</strong><p>${ctx.reactions + 420} puntos entregados este mes</p></article>
          <article><span>${icon("medal")}</span><strong>Niveles</strong><p>Bronce, Plata, Oro y Balance Pro</p></article>
          <article><span>${icon("target")}</span><strong>Ranking</strong><p>Top 10 semanal visible para miembros</p></article>
        </div>
        <div class="wellness-admin-mission-list">
          ${missions.map(([title, progress]) => `
            <article>
              <strong>${safeText(title)}</strong>
              <div class="wellness-admin-progress"><span style="width:${progress}%"></span></div>
              <small>${progress}% de avance comunitario</small>
            </article>
          `).join("")}
        </div>
      </section>
    `;
  }

  function renderAdminRealtimePanel(state, community, ctx) {
    const nextEvent = ctx.upcomingEvents[0];
    const items = [
      ["Nuevos miembros conectados", `${Math.max(3, ctx.newMembersWeek + 2)} activos ahora`, "users"],
      ["Solicitudes recientes", `${ctx.requests.length} pendientes`, "user-plus"],
      ["Proximo evento", nextEvent ? nextEvent.title : "Sin evento programado", "calendar"],
      ["Recordatorios pendientes", "3 recordatorios por enviar", "bell"],
      ["Publicaciones programadas", "2 listas para esta semana", "edit"],
      ["Alertas de moderacion", "1 reporte requiere revision", "alert"],
      ["Eventos casi llenos", `${ctx.events.filter((event) => eventSeats(state, event).available <= 3).length} detectados`, "ticket"],
      ["Usuarios en riesgo", "6 miembros sin entrar 15 dias", "history"]
    ];
    return `
      <aside class="wellness-admin-realtime">
        <div class="wellness-admin-realtime-heading">
          <strong>Actividad en tiempo real</strong>
          <small>${safeText(community.name)}</small>
        </div>
        ${items.map(([title, detail, iconName]) => `
          <article>
            <span>${icon(iconName)}</span>
            <div>
              <strong>${safeText(title)}</strong>
              <p>${safeText(detail)}</p>
            </div>
          </article>
        `).join("")}
      </aside>
    `;
  }

  function renderAdminSelectedModule(state, community, ctx) {
    if (wellnessUi.adminModule === "members") return renderAdminMembers(state, community, ctx);
    if (wellnessUi.adminModule === "requests") return renderAdminRequests(community, ctx);
    if (wellnessUi.adminModule === "events") return renderAdminEvents(state, community, ctx);
    if (wellnessUi.adminModule === "posts") return renderAdminPosts(community, ctx);
    if (wellnessUi.adminModule === "calendar") return renderAdminCalendar(ctx);
    if (wellnessUi.adminModule === "analytics") return renderAdminAnalytics(ctx);
    if (wellnessUi.adminModule === "settings") return renderAdminSettings(community);
    if (wellnessUi.adminModule === "automations") return renderAdminAutomations();
    if (wellnessUi.adminModule === "gamification") return renderAdminGamification(ctx);
    return renderAdminDashboard(state, community, ctx);
  }

  function renderCommunityAdmin(state) {
    const managed = manageableCommunities(state);
    const community = selectedAdminCommunity(state);
    if (!community) return renderEmptyState("Sin comunidades administrables", "El administrador independiente aun no tiene comunidades asignadas.");
    if (!adminModuleItems().some((item) => item.id === wellnessUi.adminModule)) wellnessUi.adminModule = "dashboard";
    const admin = currentCommunityAdmin();
    const ctx = adminCommunityContext(state, community);
    return `
      <section class="wellness-community-admin wellness-admin-console">
        <div class="wellness-admin-access-strip">
          <span>${icon("shield")}</span>
          <div>
            <strong>Acceso independiente para administradores de comunidades</strong>
            <p>Este panel esta conectado a las comunidades del paciente, pero opera con usuario administrador separado de pacientes y medicos.</p>
          </div>
          <a href="comunidad-publica.html?community=${safeText(community.id)}">Ver vista publica</a>
          <a href="comunidad-admin.html">Abrir acceso dedicado</a>
        </div>
        <div class="wellness-admin-console-layout">
          ${renderAdminSidebar(state, community, managed)}
          <main class="wellness-admin-console-main">
            ${renderAdminTopbar(admin, community, ctx)}
            ${renderAdminSelectedModule(state, community, ctx)}
          </main>
          ${renderAdminRealtimePanel(state, community, ctx)}
        </div>
      </section>
    `;
  }

  function renderCommunityCard(state, community, compact = false) {
    const membership = membershipFor(state, community.id);
    const request = requestForCurrentUser(state, community.id);
    const adminManaged = canManageCommunity(state, community.id);
    const action = adminManaged
      ? { label: "Administrar", type: "admin-open" }
      : membership
        ? { label: "Ya eres miembro", type: "already-member" }
      : request
        ? { label: "Solicitud enviada", type: "pending" }
        : community.accessType === "open"
          ? { label: "Unirme", type: "join" }
          : { label: "Solicitar acceso", type: "request" };
    return `
      <article class="wellness-community-card ${compact ? "compact" : ""}">
        <div class="wellness-community-cover wellness-community-cover-${safeText(community.coverTone || "mint")}" ${communityBackgroundStyle(community)}>
          ${renderCommunityLogo(community, "wellness-community-card-logo")}
        </div>
        <div class="wellness-community-copy">
          <small>${safeText(community.category)} · ${community.accessType === "open" ? "Abierta" : "Cerrada"}</small>
          <h4>${safeText(community.name)}</h4>
          <p>${safeText(community.shortDescription)}</p>
          <div class="wellness-community-meta">
            <span>${icon("users")} ${community.memberCount} miembros</span>
            <span>${safeText(community.city || "Online")}</span>
          </div>
          <div class="wellness-community-members">${communityMemberAvatars(community)}</div>
        </div>
        <div class="wellness-community-actions">
          <button type="button" data-wellness-community-open="${safeText(community.id)}">Ver comunidad</button>
          <button type="button" ${action.type === "pending" ? "disabled" : ""} data-wellness-community-action="${safeText(action.type)}" data-community-id="${safeText(community.id)}">${safeText(action.label)}</button>
        </div>
      </article>
    `;
  }

  function renderEventGroup(state, label, events, options = {}) {
    if (!events.length) return "";
    const groupKey = eventGroupKey(label, options);
    const visibleCount = visibleEventGroupCount(label, options);
    const visibleEvents = events.slice(0, visibleCount);
    const moreButtonAttributes = `data-wellness-event-group-more="${safeText(groupKey)}"`;
    return `
      <section class="wellness-event-group">
        <div class="wellness-community-section-heading">
          <h4>${safeText(label)}</h4>
        </div>
        <div class="wellness-event-list">
          ${visibleEvents.map((event) => renderEventCard(state, event, options)).join("")}
        </div>
        ${visibleEvents.length ? `
          <div class="wellness-event-group-more-row">
            <button class="wellness-event-group-more" type="button" ${moreButtonAttributes} aria-label="Ver mas eventos de ${safeText(label)}">Ver mas</button>
          </div>
        ` : ""}
      </section>
    `;
  }

  function renderEventCard(state, event, options = {}) {
    if (!event) return "";
    const community = communityById(state, event.communityId);
    const seats = eventSeats(state, event);
    const status = bookingStatus(state, event);
    const start = new Date(event.startAt);
    const leaderLabel = event.creatorId === state.patientId ? "Creado por ti" : "Lider de comunidad";
    return `
      <article class="wellness-event-card">
        <div class="wellness-event-cover wellness-community-cover-${safeText(event.coverTone || community?.coverTone || "mint")} ${event.imageUrl ? "has-image" : ""}" ${eventImageStyle(event)}>
          ${renderEventCoverContent(event, start)}
        </div>
        <div class="wellness-event-copy">
          <small>${safeText(event.category)}</small>
          <h4>${safeText(event.title)}</h4>
          <p>${safeText(start.toLocaleDateString("es-MX", { weekday: "long", day: "2-digit", month: "long" }))}, ${safeText(start.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" }))}</p>
          <p>${safeText(community?.name || "Comunidad Klini")}</p>
          ${options.showLeader ? `<p><strong>${safeText(leaderLabel)}:</strong> ${safeText(community?.name || "Comunidad Klini")}</p>` : ""}
          <div class="wellness-event-meta">
            <span>${icon(event.modality === "remote" ? "globe" : "map")} ${safeText(event.modality === "remote" ? "Remoto" : event.locationName)}</span>
            <span class="wellness-booking-status wellness-booking-${status.tone}">${safeText(status.label)}</span>
          </div>
          <small>${seats.available} lugares disponibles de ${event.capacity}</small>
        </div>
        <button type="button" data-wellness-event-open="${safeText(event.id)}">Ver detalle</button>
      </article>
    `;
  }

  function categoryIcon(category) {
    const key = String(category || "").toLowerCase();
    if (key.includes("nutric")) return "leaf";
    if (key.includes("running")) return "walk";
    if (key.includes("mental") || key.includes("medit")) return "brain";
    return "heart";
  }

  function renderHistory(state) {
    const sorted = [...state.metrics].sort((a, b) => new Date(b.recordedAt) - new Date(a.recordedAt)).slice(0, 40);
    return `
      <section class="wellness-subscreen wellness-history-layout">
        <div class="wellness-subscreen-heading">
          <div>
            <small>Historial</small>
            <h3>Registros, alertas e integraciones</h3>
            <p>Consulta lo capturado manualmente y lo sincronizado por dispositivos.</p>
          </div>
          <button class="wellness-primary-action" type="button" data-wellness-quick-entry>${icon("plus")} Registrar</button>
        </div>
        <div class="wellness-history-grid">
          <article class="wellness-history-card">
            <h4>Registros recientes</h4>
            ${sorted.map(renderHistoryItem).join("")}
          </article>
          <article class="wellness-history-card">
            <h4>Alertas y recomendaciones</h4>
            ${state.alerts.length ? state.alerts.map(renderHistoryAlert).join("") : renderEmptyState("Sin alertas", "No hay resultados pendientes de seguimiento.")}
          </article>
          <article class="wellness-history-card">
            <h4>Integraciones con dispositivos</h4>
            ${state.integrations.map(renderIntegrationItem).join("")}
          </article>
          <article class="wellness-history-card">
            <h4>Privacidad para compartir</h4>
            ${renderPrivacyControls(state)}
          </article>
        </div>
      </section>
    `;
  }

  function renderHistoryItem(metric) {
    const config = metricConfig(metric.type);
    return `
      <div class="wellness-history-item">
        <span class="wellness-history-icon wellness-accent-${config.accent}">${icon(config.icon)}</span>
        <div>
          <strong>${safeText(config.label)}</strong>
          <small>${safeText(formatMetricTime(metric.recordedAt))} · ${safeText(config.source)}</small>
        </div>
        <b>${metricDisplay(metric)}</b>
      </div>
    `;
  }

  function renderHistoryAlert(alert) {
    return `
      <div class="wellness-history-alert wellness-alert-${safeText(alert.severity)}">
        <span>${icon("alert")}</span>
        <div>
          <strong>${safeText(alert.title)}</strong>
          <p>${safeText(alert.message)}</p>
        </div>
        <button type="button" data-wellness-dismiss-alert="${safeText(alert.id)}">Revisada</button>
      </div>
    `;
  }

  function renderIntegrationItem(item) {
    return `
      <div class="wellness-integration-item">
        <span>${icon("shield")}</span>
        <div>
          <strong>${safeText(item.name)}</strong>
          <small>${safeText(item.status)}${item.lastSync ? ` · ${safeText(formatMetricTime(item.lastSync))}` : ""}</small>
        </div>
        <button type="button" data-wellness-toggle-integration="${safeText(item.id)}">${item.status === "Conectado" ? "Pausar" : "Conectar"}</button>
      </div>
    `;
  }

  function renderEmptyState(title, body) {
    return `
      <div class="wellness-empty-state">
        <span>${icon("spark")}</span>
        <strong>${safeText(title)}</strong>
        <p>${safeText(body)}</p>
      </div>
    `;
  }

  function renderWellnessBottomNav() {
    return `
      <nav class="wellness-bottom-nav" aria-label="Menu Wellness movil">
        <button type="button" data-wellness-home>${icon("home")}<span>Home</span></button>
        <button type="button" data-wellness-open-devices>${icon("test")}<span>Dispositivos</span></button>
        <button type="button" class="wellness-bottom-plus" data-wellness-quick-entry>${icon("plus")}<span>Registro</span></button>
        <button type="button" class="${wellnessUi.section === "wellbeing" ? "active" : ""}" data-wellness-section="wellbeing">${icon("medal")}<span>Rachas</span></button>
        <button type="button" class="${wellnessUi.section === "communities" ? "active" : ""}" data-wellness-section="communities">${icon("users")}<span>Comunidades</span></button>
      </nav>
    `;
  }

  function renderWellnessModal(state) {
    if (wellnessUi.modal === "quick") return renderQuickEntrySheet(state);
    if (wellnessUi.modal === "goal") return renderGoalFormSheet();
    if (wellnessUi.modal === "score") return renderScoreModal(state);
    if (wellnessUi.modal === "detail") return renderMetricDetailModal(state);
    if (wellnessUi.modal === "goal-detail") return renderGoalDetailModal(state);
    if (wellnessUi.modal === "achievement") return renderAchievementDetailModal(state);
    if (wellnessUi.modal === "share") return renderShareModal(state);
    if (wellnessUi.modal === "community-detail") return renderCommunityDetailModal(state);
    if (wellnessUi.modal === "event-detail") return renderEventDetailModal(state);
    if (wellnessUi.modal === "create-community") return renderCreateCommunityModal();
    if (wellnessUi.modal === "edit-community") return renderEditCommunityModal(state);
    if (wellnessUi.modal === "creator-profile") return renderCreatorProfileModal(state);
    if (wellnessUi.modal === "admin-request-profile") return renderAdminRequestProfileModal(state);
    if (wellnessUi.modal === "create-event") return renderCreateEventModal(state);
    return "";
  }

  function adminProfileUsername(name) {
    return String(name || "usuario")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, ".")
      .replace(/(^\.|\.$)/g, "")
      .slice(0, 28) || "usuario";
  }

  function adminRequestProfileData(state, request) {
    const communityRequests = state.communityJoinRequests
      .filter((item) => item.communityId === request.communityId && item.status === "pending")
      .sort((a, b) => new Date(b.requestedAt) - new Date(a.requestedAt));
    const index = Math.max(0, communityRequests.findIndex((item) => item.id === request.id));
    const profile = adminMemberProfile({ communityJoinRequests: [] }, { userId: request.userId }, index);
    const instagram = adminProfileUsername(request.userName);
    return {
      ...profile,
      name: request.userName || profile.name,
      initials: request.userInitials || initialsText(request.userName),
      interests: request.sharedInterests || profile.interests,
      instagram,
      instagramUrl: `https://www.instagram.com/${instagram}/`
    };
  }

  function renderAdminRequestProfileModal(state) {
    const request = state.communityJoinRequests.find((item) => item.id === wellnessUi.adminProfileRequestId);
    if (!request) return "";
    const community = communityById(state, request.communityId);
    const profile = adminRequestProfileData(state, request);
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessAdminRequestProfileTitle">
        <section class="wellness-sheet wellness-admin-request-profile-sheet">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <div class="wellness-admin-request-profile-cover">
            <span class="wellness-admin-request-profile-photo">${safeText(profile.initials)}</span>
          </div>
          <small>Perfil del solicitante</small>
          <h3 id="wellnessAdminRequestProfileTitle">${safeText(profile.name)}</h3>
          <p class="wellness-modal-copy">${profile.age} anos - ${safeText(profile.city)} - ${safeText(community?.name || "Comunidad")}</p>
          <a class="wellness-admin-instagram-link" href="${safeText(profile.instagramUrl)}" target="_blank" rel="noopener noreferrer" aria-label="Abrir perfil de Instagram de ${safeText(profile.name)}">
            <span>${shareChannelIcon("instagram")}</span>
            <strong>Instagram</strong>
            <small>@${safeText(profile.instagram)}</small>
          </a>
          <dl class="wellness-summary-list">
            <div><dt>Nombre completo</dt><dd>${safeText(profile.name)}</dd></div>
            <div><dt>Edad</dt><dd>${profile.age} anos</dd></div>
            <div><dt>Ciudad</dt><dd>${safeText(profile.city)}</dd></div>
            <div><dt>Nivel Wellness</dt><dd>${safeText(profile.wellnessLevel)}</dd></div>
            <div><dt>Intereses</dt><dd>${profile.interests.map(safeText).join(", ")}</dd></div>
            <div><dt>Solicitud</dt><dd>${safeText(adminRequestRelativeTime(request.requestedAt))}</dd></div>
          </dl>
        </section>
      </div>
    `;
  }

  function renderCreatorProfileModal(state) {
    const admin = currentCommunityAdmin();
    const community = communityById(state, wellnessUi.adminCommunityId) || selectedAdminCommunity(state);
    const managed = manageableCommunities(state);
    const ctx = community ? adminCommunityContext(state, community) : null;
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessCreatorProfileTitle">
        <section class="wellness-sheet wellness-creator-profile-sheet">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Perfil del creador</small>
          <h3 id="wellnessCreatorProfileTitle">${safeText(admin.fullName)}</h3>
          <div class="wellness-creator-profile-card">
            <div class="wellness-creator-photo-editor">
              ${renderAdminProfileAvatar(admin, "wellness-admin-profile-avatar wellness-creator-profile-edit-avatar")}
              <label class="wellness-creator-photo-upload">
                ${icon("camera")} Editar foto
                <input type="file" accept="image/*" data-wellness-admin-profile-photo />
              </label>
              ${admin.photoUrl ? `<button type="button" class="wellness-creator-photo-remove" data-wellness-community-action="remove-admin-profile-photo">Quitar foto</button>` : ""}
            </div>
            <div>
              <strong>${safeText(admin.title || admin.role)}</strong>
              <p>${safeText(admin.email)}</p>
              <small>${safeText(admin.city)} - Plan ${safeText(admin.plan)}</small>
            </div>
          </div>
          <dl class="wellness-summary-list">
            <div><dt>Comunidad actual</dt><dd>${safeText(community?.name || "Sin comunidad")}</dd></div>
            <div><dt>Comunidades administradas</dt><dd>${managed.length}</dd></div>
            <div><dt>Miembros gestionados</dt><dd>${safeText(managed.reduce((total, item) => total + Number(item.memberCount || 0), 0))}</dd></div>
            <div><dt>Participacion actual</dt><dd>${ctx ? `${ctx.participation}%` : "Sin dato"}</dd></div>
            <div><dt>Alta del creador</dt><dd>${safeText(new Date(admin.joinedAt).toLocaleDateString("es-MX", { day: "2-digit", month: "long", year: "numeric" }))}</dd></div>
            <div><dt>Tipo de acceso</dt><dd>Administrador independiente</dd></div>
          </dl>
          <div class="wellness-creator-community-list">
            ${managed.map((item) => `
              <button type="button" data-wellness-community-action="admin-open" data-community-id="${safeText(item.id)}">
                ${renderCommunityLogo(item, "wellness-creator-community-logo")}
                <span><strong>${safeText(item.name)}</strong><small>${safeText(item.category)} - ${safeText(item.memberCount || 0)} miembros</small></span>
              </button>
            `).join("")}
          </div>
        </section>
      </div>
    `;
  }

  function renderCommunityDetailModal(state) {
    const community = communityById(state, wellnessUi.communityId);
    if (!community) return "";
    const membership = membershipFor(state, community.id);
    const events = state.communityEvents.filter((event) => event.communityId === community.id).sort((a, b) => new Date(a.startAt) - new Date(b.startAt));
    const posts = communityPublishedPosts(state, community.id).slice(0, 4);
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessCommunityTitle">
        <section class="wellness-sheet wellness-sheet-wide wellness-community-detail-sheet">
          <button class="wellness-community-back-button" type="button" data-wellness-close-modal>${icon("chevron-left")} Volver a comunidades</button>
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <div class="wellness-community-detail-cover wellness-community-cover-${safeText(community.coverTone || "mint")}" ${communityBackgroundStyle(community)}>
            ${renderCommunityLogo(community, "wellness-community-detail-logo")}
          </div>
          <small>${safeText(community.category)} - ${community.accessType === "open" ? "Comunidad abierta" : "Comunidad cerrada"}</small>
          <h3 id="wellnessCommunityTitle">${safeText(community.name)}</h3>
          <p class="wellness-modal-copy">${safeText(community.description)}</p>
          <div class="wellness-community-detail-stats">
            <div><small>Miembros</small><strong>${community.memberCount}</strong></div>
            <div><small>Ciudad</small><strong>${safeText(community.city || "Online")}</strong></div>
            <div><small>Tu rol</small><strong>${safeText(membership?.role || "Visitante")}</strong></div>
          </div>
          <div class="wellness-goal-tabs">
            ${["Inicio", "Eventos", "Miembros", "Publicaciones", "Informacion"].map((label, index) => `<button class="${index === 0 ? "active" : ""}" type="button">${safeText(label)}</button>`).join("")}
          </div>
          ${community.pinnedMessage ? `<article class="wellness-recommendation"><strong>Aviso de la comunidad</strong><p>${safeText(community.pinnedMessage)}</p></article>` : ""}
          ${renderCommunityDetailEvents(state, community.id)}
          <div class="wellness-admin-public-posts">
            <h4>Publicaciones recientes</h4>
            ${posts.length ? posts.map((post) => `
              <article class="wellness-admin-public-post">
                <strong>${safeText(post.title)}</strong>
                <small>${post.pinned ? "Fijada - " : ""}${safeText(formatMetricTime(post.createdAt))}</small>
                <p>${safeText(post.body)}</p>
              </article>
            `).join("") : renderEmptyState("Sin publicaciones", "Aun no hay publicaciones visibles para esta comunidad.")}
          </div>
          <div class="wellness-sheet-actions">
            ${canManageCommunity(state, community.id) ? `<button type="button" class="wellness-secondary-action" data-wellness-community-action="admin-open" data-community-id="${safeText(community.id)}">${icon("shield")} Administrar pagina</button>` : ""}
            <button type="button" class="wellness-primary-action" data-wellness-community-action="${membership ? "leave" : community.accessType === "open" ? "join" : "request"}" data-community-id="${safeText(community.id)}">
              ${membership ? "Salir de comunidad" : community.accessType === "open" ? "Unirme a la comunidad" : "Solicitar acceso"}
            </button>
          </div>
        </section>
      </div>
    `;
  }

  function renderCommunityDetailEvents(state, communityId) {
    const events = state.communityEvents
      .filter((event) => event.communityId === communityId && event.status === "published")
      .sort((a, b) => new Date(a.startAt) - new Date(b.startAt));
    const groups = eventGroupsFor(events)
      .filter((group) => group.events.length);
    return `
      <section class="wellness-community-detail-events">
        <div class="wellness-community-section-heading">
          <div>
            <h4>Eventos</h4>
            <p>Los eventos de esta comunidad tambien aparecen en This week, Next week o Coming soon.</p>
          </div>
        </div>
        ${groups.length ? groups.map((group) => renderEventGroup(state, group.label, group.events, { groupScope: `community-detail-${communityId}` })).join("") : renderEmptyState("Sin eventos", "Esta comunidad aun no tiene eventos publicados.")}
      </section>
    `;
  }

  function renderEventDetailModal(state) {
    const event = eventById(state, wellnessUi.eventId);
    if (!event) return "";
    const community = communityById(state, event.communityId);
    const seats = eventSeats(state, event);
    const reservation = eventReservationForUser(state, event.id);
    const status = bookingStatus(state, event);
    const start = new Date(event.startAt);
    const end = new Date(event.endAt);
    const primaryAction = reservation?.status === "confirmed"
      ? { type: "cancel-reservation", label: "Cancelar reserva" }
      : !seats.available
        ? { type: event.allowWaitlist ? "waitlist" : "full", label: event.allowWaitlist ? "Unirme a lista de espera" : "Evento lleno" }
        : { type: "reserve", label: "Reservar espacio" };
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessEventTitle">
        <section class="wellness-sheet wellness-sheet-wide wellness-event-detail-sheet">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <div class="wellness-community-detail-cover wellness-community-cover-${safeText(event.coverTone || community?.coverTone || "mint")}">
            <span>${icon(event.modality === "remote" ? "globe" : "map")}</span>
          </div>
          <small>${safeText(community?.name || "Comunidad Klini")}</small>
          <h3 id="wellnessEventTitle">${safeText(event.title)}</h3>
          <p class="wellness-modal-copy">${safeText(event.description)}</p>
          <dl class="wellness-summary-list">
            <div><dt>Fecha</dt><dd>${safeText(start.toLocaleDateString("es-MX", { weekday: "long", day: "2-digit", month: "long", year: "numeric" }))}</dd></div>
            <div><dt>Horario</dt><dd>${safeText(start.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" }))} - ${safeText(end.toLocaleTimeString("es-MX", { hour: "2-digit", minute: "2-digit" }))}</dd></div>
            <div><dt>Modalidad</dt><dd>${event.modality === "remote" ? "Remoto" : "Presencial"}</dd></div>
            <div><dt>Ubicacion</dt><dd>${safeText(event.modality === "remote" ? event.remoteUrl : `${event.locationName} - ${event.address}`)}</dd></div>
            <div><dt>Reservacion</dt><dd>${safeText(status.label)}</dd></div>
            <div><dt>Cupo</dt><dd>${seats.confirmed}/${event.capacity} ocupados</dd></div>
            <div><dt>Disponibles</dt><dd>${seats.available}</dd></div>
            <div><dt>Lista de espera</dt><dd>${event.allowWaitlist ? `${seats.waitlisted} personas` : "No habilitada"}</dd></div>
          </dl>
          <article class="wellness-recommendation">
            <strong>Reglas del evento</strong>
            <p>Reserva solo si puedes asistir. Las cancelaciones liberan lugares para otros miembros. Si presentas sintomas, consulta a tu profesional de salud antes de participar.</p>
          </article>
          <div class="wellness-event-reservation-bar">
            <span>${safeText(status.label)}</span>
            <button type="button" ${primaryAction.type === "full" ? "disabled" : ""} data-wellness-community-action="${safeText(primaryAction.type)}" data-event-id="${safeText(event.id)}">${safeText(primaryAction.label)}</button>
          </div>
        </section>
      </div>
    `;
  }

  function renderEditCommunityModal(state) {
    const community = communityById(state, wellnessUi.adminCommunityId) || selectedAdminCommunity(state);
    if (!community || !canManageCommunity(state, community.id)) return "";
    const selectedAccess = community.accessType || "open";
    const selectedVisibility = community.visibility || "public";
    const selectedStatus = community.status || "active";
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessEditCommunityTitle">
        <section class="wellness-sheet wellness-sheet-wide">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Administracion de comunidad</small>
          <h3 id="wellnessEditCommunityTitle">Editar comunidad</h3>
          <form class="wellness-form wellness-goal-form wellness-admin-edit-form" data-wellness-community-admin-form>
            <input type="hidden" name="communityId" value="${safeText(community.id)}">
            <div class="wellness-admin-brand-editor wellness-admin-full">
              <div class="wellness-admin-brand-preview wellness-community-cover-${safeText(community.coverTone || "mint")}" ${communityBackgroundStyle(community)}>
                ${renderCommunityLogo(community)}
                <div>
                  <small>Vista previa de portada</small>
                  <strong>${safeText(community.name)}</strong>
                  <span>${safeText(community.category)} - ${safeText(community.city || "Online")}</span>
                </div>
              </div>
              <label><span>Imagen de fondo</span><input name="backgroundImage" value="${safeText(community.backgroundImage || "")}" placeholder="URL de imagen de portada"></label>
              <label><span>Cargar fondo</span><input type="file" accept="image/*" data-wellness-brand-file="backgroundImage"></label>
              <label><span>Logotipo</span><input name="logoImage" value="${safeText(community.logoImage || "")}" placeholder="URL del logotipo"></label>
              <label><span>Cargar logotipo</span><input type="file" accept="image/*" data-wellness-brand-file="logoImage"></label>
              <label><span>Iniciales del logotipo</span><input name="logoInitials" maxlength="3" value="${safeText(community.logoInitials || initialsText(community.name).slice(0, 3))}" placeholder="RA"></label>
            </div>
            <label><span>Nombre de la pagina</span><input name="name" value="${safeText(community.name)}" required></label>
            <label><span>Categoria</span><select name="category">${["Yoga", "Running", "Nutricion", "Meditacion", "Salud mental", "Habitos saludables"].map((category) => `<option ${community.category === category ? "selected" : ""}>${safeText(category)}</option>`).join("")}</select></label>
            <label class="wellness-admin-full"><span>Descripcion corta</span><input name="shortDescription" value="${safeText(community.shortDescription)}" required></label>
            <label class="wellness-admin-full"><span>Descripcion completa</span><textarea name="description" rows="4" required>${safeText(community.description)}</textarea></label>
            <label><span>Ciudad o region</span><input name="city" value="${safeText(community.city || "")}"></label>
            <label><span>Idioma</span><select name="language">${["Espanol", "Ingles"].map((language) => `<option ${community.language === language ? "selected" : ""}>${safeText(language)}</option>`).join("")}</select></label>
            <label><span>Tipo de acceso</span><select name="accessType">${renderStatusOptions([["open", "Abierta"], ["closed", "Cerrada"]], selectedAccess)}</select></label>
            <label><span>Visibilidad</span><select name="visibility">${renderStatusOptions([["public", "Visible en busqueda"], ["hidden", "Oculta en busqueda"]], selectedVisibility)}</select></label>
            <label><span>Estado</span><select name="status">${renderStatusOptions([["active", "Activa"], ["paused", "Pausada"], ["archived", "Archivada"]], selectedStatus)}</select></label>
            <div class="wellness-admin-full wellness-admin-cover-field">
              <span>Tono de portada</span>
              <div class="wellness-admin-cover-swatches">
                ${["mint", "aqua", "gold", "purple"].map((tone) => `
                  <label class="wellness-admin-swatch wellness-community-cover-${tone} ${community.coverTone === tone ? "active" : ""}" title="${safeText(tone)}">
                    <input type="radio" name="coverTone" value="${tone}" ${community.coverTone === tone ? "checked" : ""}>
                    <span>${safeText(tone)}</span>
                  </label>
                `).join("")}
              </div>
            </div>
            <label class="wellness-admin-full"><span>Reglas de convivencia</span><textarea name="rules" rows="3">${safeText(community.rules || "")}</textarea></label>
            <label class="wellness-admin-full"><span>Mensaje fijado</span><textarea name="pinnedMessage" rows="2" placeholder="Aviso destacado para los miembros">${safeText(community.pinnedMessage || "")}</textarea></label>
            <div class="wellness-sheet-actions">
              <button type="button" class="wellness-secondary-action" data-wellness-close-modal>Cancelar</button>
              <button type="submit" class="wellness-primary-action">Guardar cambios</button>
            </div>
          </form>
        </section>
      </div>
    `;
  }

  function renderCreateCommunityModal() {
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessCreateCommunityTitle">
        <section class="wellness-sheet wellness-sheet-wide">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Nueva comunidad</small>
          <h3 id="wellnessCreateCommunityTitle">Crear comunidad</h3>
          <form class="wellness-form wellness-goal-form" data-wellness-community-form>
            <label><span>Nombre</span><input name="name" required placeholder="Ej. Habitos saludables CDMX"></label>
            <label><span>Descripcion corta</span><input name="shortDescription" required placeholder="Resumen para la tarjeta"></label>
            <label><span>Descripcion completa</span><textarea name="description" rows="3" required></textarea></label>
            <label><span>Categoria</span><select name="category"><option>Yoga</option><option>Running</option><option>Nutricion</option><option>Meditacion</option><option>Salud mental</option><option>Habitos saludables</option></select></label>
            <label><span>Ciudad o region</span><input name="city" value="Ciudad de Mexico"></label>
            <label><span>Idioma</span><select name="language"><option>Espanol</option><option>Ingles</option></select></label>
            <label><span>Tipo de acceso</span><select name="accessType"><option value="open">Comunidad abierta</option><option value="closed">Comunidad cerrada</option></select></label>
            <label><span>Visibilidad</span><select name="visibility"><option value="public">Visible publicamente</option><option value="hidden">Oculta en busqueda</option></select></label>
            <label><span>Reglas</span><textarea name="rules" rows="3" placeholder="Politica de comportamiento y requisitos"></textarea></label>
            <div class="wellness-sheet-actions">
              <button type="button" class="wellness-secondary-action" data-wellness-close-modal>Cancelar</button>
              <button type="submit" class="wellness-primary-action">Crear comunidad</button>
            </div>
          </form>
        </section>
      </div>
    `;
  }

  function renderCreateEventModal(state) {
    const manageable = state.communities.filter((community) => canManageCommunity(state, community.id));
    const selectedCommunity = manageable.find((community) => community.id === wellnessUi.adminCommunityId) || manageable[0] || {};
    const previewDate = new Date("2026-07-30T18:00:00");
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessCreateEventTitle">
        <section class="wellness-sheet wellness-sheet-wide wellness-event-create-sheet">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <header class="wellness-event-create-header">
            <span>${icon("calendar")}</span>
            <div>
              <small>Nuevo evento</small>
              <h3 id="wellnessCreateEventTitle">Crear evento</h3>
              <p>Completa la informacion para crear tu evento.</p>
            </div>
          </header>
          <form class="wellness-event-create-form" data-wellness-event-form>
            <div class="wellness-event-create-grid">
              <label class="wellness-event-field">
                <span>Comunidad organizadora</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("users")}</i>
                  <select name="communityId">${manageable.map((community) => `<option value="${safeText(community.id)}" ${community.id === wellnessUi.adminCommunityId ? "selected" : ""}>${safeText(community.name)}</option>`).join("")}</select>
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Nombre del evento</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("spark")}</i>
                  <input name="title" required placeholder="Ej. Caminata preventiva">
                </div>
              </label>

              <div class="wellness-event-image-field wellness-event-field-full">
                <span>Imagen del evento</span>
                <div class="wellness-event-image-editor">
                  <div class="wellness-event-image-preview wellness-event-cover wellness-community-cover-${safeText(selectedCommunity.coverTone || "mint")}" data-wellness-event-image-preview>
                    ${renderEventCoverContent({}, previewDate)}
                  </div>
                  <div class="wellness-event-image-copy">
                    <strong>Sube una imagen o pega una URL</strong>
                    <small>Formatos recomendados: JPG, PNG. Tamano max. 5MB.</small>
                  </div>
                  <div class="wellness-event-image-actions">
                    <input name="imageUrl" data-wellness-event-image-url placeholder="URL o referencia de imagen">
                    <label class="wellness-event-upload-button">
                      ${icon("camera")} Subir imagen
                      <input type="file" accept="image/*" data-wellness-event-image-file>
                    </label>
                  </div>
                  <small class="wellness-event-image-note">Previsualizacion con la misma proporcion de la tarjeta del evento.</small>
                </div>
              </div>

              <label class="wellness-event-field">
                <span>Descripcion corta</span>
                <div class="wellness-event-control wellness-event-textarea-control">
                  <i aria-hidden="true">${icon("chat")}</i>
                  <textarea name="shortDescription" rows="3" maxlength="120" required placeholder="Describe tu evento en pocas palabras..."></textarea>
                </div>
                <small>Max. 120 caracteres</small>
              </label>
              <label class="wellness-event-field">
                <span>Descripcion completa</span>
                <div class="wellness-event-control wellness-event-textarea-control">
                  <i aria-hidden="true">${icon("edit")}</i>
                  <textarea name="description" rows="3" maxlength="1000" required placeholder="Cuenta mas detalles sobre tu evento..."></textarea>
                </div>
                <small>Max. 1000 caracteres</small>
              </label>
              <label class="wellness-event-field">
                <span>Categoria</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("target")}</i>
                  <select name="category"><option>Wellbeing</option><option>Yoga</option><option>Running</option><option>Nutricion</option><option>Meditacion</option></select>
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Fecha</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("calendar")}</i>
                  <input name="date" type="date" value="2026-07-30" required>
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Inicio</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("clock")}</i>
                  <input name="startTime" type="time" value="18:00" required>
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Finalizacion</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("clock")}</i>
                  <input name="endTime" type="time" value="19:00" required>
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Zona horaria</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("globe")}</i>
                  <select name="timezone"><option>America/Mexico_City</option><option>America/Bogota</option><option>America/New_York</option><option>America/Los_Angeles</option></select>
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Modalidad</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("user-plus")}</i>
                  <select name="modality"><option value="in_person">Presencial</option><option value="remote">Virtual</option><option value="mixed">Mixto</option></select>
                </div>
              </label>

              <section class="wellness-event-location-panel wellness-event-field-full">
                <label class="wellness-event-field">
                  <span>Ubicacion</span>
                  <div class="wellness-event-control">
                    <i aria-hidden="true">${icon("map")}</i>
                    <input name="location" required placeholder="Direccion fisica o enlace remoto">
                  </div>
                </label>
                <label class="wellness-event-field">
                  <span>Google Maps</span>
                  <div class="wellness-event-control">
                    <i aria-hidden="true">${icon("map")}</i>
                    <input name="mapsUrl" placeholder="Liga de Google Maps">
                  </div>
                </label>
                <label class="wellness-event-field">
                  <span>Zoom</span>
                  <div class="wellness-event-control">
                    <i aria-hidden="true">${icon("video")}</i>
                    <input name="zoomUrl" placeholder="Liga Zoom">
                  </div>
                </label>
                <label class="wellness-event-field">
                  <span>Google Meet</span>
                  <div class="wellness-event-control">
                    <i aria-hidden="true">${icon("video")}</i>
                    <input name="meetUrl" placeholder="Liga Meet">
                  </div>
                </label>
                <label class="wellness-event-field">
                  <span>Microsoft Teams</span>
                  <div class="wellness-event-control">
                    <i aria-hidden="true">${icon("video")}</i>
                    <input name="teamsUrl" placeholder="Liga Microsoft Teams">
                  </div>
                </label>
              </section>

              <label class="wellness-event-field">
                <span>Cupo maximo</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("users")}</i>
                  <input name="capacity" type="number" min="1" value="20" required placeholder="Ej. 30 personas">
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Lista de espera</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("ticket")}</i>
                  <select name="allowWaitlist"><option value="true">Habilitar lista de espera</option><option value="false">No habilitada</option></select>
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Costo opcional</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("gift")}</i>
                  <input name="cost" type="number" min="0" step="1" placeholder="0">
                </div>
              </label>
              <label class="wellness-event-field">
                <span>Invitaciones</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("share")}</i>
                  <select name="inviteMode"><option value="all">Todos</option><option value="admins">Administradores</option><option value="moderators">Moderadores</option><option value="group">Grupo seleccionado</option><option value="selected">Usuarios seleccionados</option><option value="none">No enviar invitacion</option></select>
                </div>
              </label>
              <label class="wellness-event-field wellness-event-field-full">
                <span>Recordatorios</span>
                <div class="wellness-event-control">
                  <i aria-hidden="true">${icon("bell")}</i>
                  <select name="reminders"><option value="24h,1h,15m">24 h, 1 h y 15 min antes</option><option value="24h">24 horas antes</option><option value="1h">1 hora antes</option><option value="15m">15 minutos antes</option></select>
                </div>
              </label>
            </div>
            <div class="wellness-event-form-actions">
              <button type="button" class="wellness-secondary-action" data-wellness-close-modal>Cancelar</button>
              <button type="submit" class="wellness-primary-action">Crear evento ${icon("spark")}</button>
            </div>
          </form>
        </section>
      </div>
    `;
  }

  function renderQuickEntrySheet() {
    const config = metricConfig(wellnessUi.quickMetric);
    const isNote = wellnessUi.quickMetric === "personal_note";
    const nowValue = dateTimeValue(new Date());
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessQuickTitle">
        <section class="wellness-sheet">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Registro rapido</small>
          <h3 id="wellnessQuickTitle">${isNote ? "Nota personal" : safeText(config.label)}</h3>
          <div class="wellness-quick-options" data-wellness-pan>
            ${quickEntryOptions.map((type) => {
              const option = type === "personal_note" ? { type, label: "Nota personal", icon: "chat" } : metricConfig(type);
              return `<button class="${wellnessUi.quickMetric === type ? "active" : ""}" type="button" data-wellness-select-quick="${type}">${icon(option.icon)} ${safeText(option.label)}</button>`;
            }).join("")}
          </div>
          <form class="wellness-form" data-wellness-quick-form>
            <input type="hidden" name="metricType" value="${safeText(wellnessUi.quickMetric)}" />
            <label>
              <span>${isNote ? "Nota" : "Valor"}</span>
              ${isNote
                ? `<textarea name="value" rows="4" placeholder="Escribe una nota personal"></textarea>`
                : `<input name="value" type="number" step="0.1" placeholder="Meta sugerida: ${safeText(config.target || "")} ${safeText(config.unit || "")}" required />`}
            </label>
            <label>
              <span>Fecha y hora</span>
              <input name="recordedAt" type="datetime-local" value="${safeText(nowValue)}" required />
            </label>
            <label>
              <span>Comentario opcional</span>
              <textarea name="notes" rows="3" placeholder="Agrega contexto si lo necesitas"></textarea>
            </label>
            <div class="wellness-attachment-field">
              <span>Fotografia o documento</span>
              <div class="wellness-attachment-control">
                <button class="wellness-attachment-plus" type="button" data-wellness-attachment-menu aria-expanded="false" aria-label="Agregar fotografia o documento">
                  ${icon("plus")}
                </button>
                <div class="wellness-attachment-copy">
                  <strong>Agregar evidencia</strong>
                  <small data-wellness-attachment-name>Sin archivo seleccionado</small>
                </div>
                <input class="wellness-hidden-file-input" name="attachment" type="file" accept="image/*" data-wellness-attachment-input />
                <div class="wellness-attachment-menu" data-wellness-attachment-options>
                  <button type="button" data-wellness-attachment-pick="camera">
                    ${icon("camera")}
                    <span>Tomar foto</span>
                  </button>
                  <button type="button" data-wellness-attachment-pick="gallery">
                    ${icon("camera")}
                    <span>Subir archivo</span>
                    <small>Rollo fotografico</small>
                  </button>
                </div>
              </div>
            </div>
            <div class="wellness-sheet-actions">
              <button type="button" class="wellness-secondary-action" data-wellness-close-modal>Cancelar</button>
              <button type="submit" class="wellness-primary-action">Guardar registro</button>
            </div>
          </form>
        </section>
      </div>
    `;
  }

  function renderGoalFormSheet() {
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessGoalTitle">
        <section class="wellness-sheet wellness-sheet-wide">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Nuevo objetivo</small>
          <h3 id="wellnessGoalTitle">Crear objetivo de bienestar</h3>
          <form class="wellness-form wellness-goal-form" data-wellness-goal-form>
            <label><span>Nombre del objetivo</span><input name="title" required placeholder="Ej. Tomar 2 litros de agua"></label>
            <label><span>Categoria</span><select name="category">${metricCatalog.map((item) => `<option>${safeText(item.category)}</option>`).join("")}<option>Habito personalizado</option></select></label>
            <label><span>Descripcion</span><textarea name="description" rows="3"></textarea></label>
            <label><span>Indicador relacionado</span><select name="metricType">${metricCatalog.map((item) => `<option value="${item.type}">${safeText(item.label)}</option>`).join("")}</select></label>
            <label><span>Valor meta</span><input name="targetValue" type="number" step="0.1" required></label>
            <label><span>Unidad</span><input name="targetUnit" placeholder="L, pasos, %, mmHg"></label>
            <label><span>Frecuencia</span><select name="periodType"><option value="daily">Diario</option><option value="weekly" selected>Semanal</option><option value="monthly">Mensual</option><option value="annual">Anual</option><option value="custom">Personalizado</option></select></label>
            <label><span>Ocurrencias requeridas</span><input name="requiredOccurrences" type="number" min="1" value="5"></label>
            <label><span>Operador</span><select name="comparisonOperator"><option value="greater_than_or_equal">Mayor o igual</option><option value="less_than_or_equal">Menor o igual</option><option value="equal">Igual</option><option value="within_range">Dentro de un rango</option><option value="number_of_records">Numero de registros</option><option value="percentage_of_compliance">Porcentaje de cumplimiento</option></select></label>
            <label><span>Rango minimo</span><input name="validRangeMin" type="number" step="0.1"></label>
            <label><span>Rango maximo</span><input name="validRangeMax" type="number" step="0.1"></label>
            <label><span>Fecha de inicio</span><input name="startDate" type="date" value="${safeText(dateOnly())}"></label>
            <label><span>Fecha final</span><input name="endDate" type="date"></label>
            <label><span>Dias aplicables</span><input name="days" placeholder="Lun, Mar, Mie, Jue, Vie"></label>
            <label><span>Hora recomendada</span><input name="recommendedTime" type="time" value="08:30"></label>
            <label><span>Recordatorios</span><select name="reminders"><option value="true">Activados</option><option value="false">Desactivados</option></select></label>
            <label><span>Privacidad</span><select name="privacyLevel"><option value="private">Privado</option><option value="care_team">Equipo medico</option><option value="shareable">Compartible</option></select></label>
            <label><span>Medico relacionado</span><input name="doctor" placeholder="Opcional"></label>
            <label><span>Programa medico</span><input name="program" placeholder="Opcional"></label>
            <div class="wellness-sheet-actions">
              <button type="button" class="wellness-secondary-action" data-wellness-close-modal>Cancelar</button>
              <button type="submit" class="wellness-primary-action">Guardar objetivo</button>
            </div>
          </form>
        </section>
      </div>
    `;
  }

  function renderScoreModal(state) {
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessScoreTitle">
        <section class="wellness-sheet">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Health Score</small>
          <h3 id="wellnessScoreTitle">Como se calcula</h3>
          <p class="wellness-modal-copy">El puntaje combina indicadores diarios disponibles, progreso de objetivos, adherencia a medicamentos, registros de dispositivos y alertas abiertas. No sustituye una valoracion medica.</p>
          <div class="wellness-score-breakdown">
            ${["Registros diarios", "Objetivos activos", "Adherencia", "Alertas abiertas"].map((label, index) => `
              <div>
                <span>${safeText(label)}</span>
                <strong>${[78, averageGoalCompliance(state), 94, Math.max(60, 100 - state.alerts.filter((alert) => alert.status === "open").length * 12)][index]}%</strong>
              </div>
            `).join("")}
          </div>
        </section>
      </div>
    `;
  }

  function renderMetricDetailModal(state) {
    const config = metricConfig(wellnessUi.detailMetric);
    const records = [...state.metrics].filter((metric) => metric.type === config.type).sort((a, b) => new Date(a.recordedAt) - new Date(b.recordedAt));
    const values = records.slice(-12).map((metric) => metricScore(config, metric));
    const average = records.length ? Math.round(records.reduce((sum, metric) => sum + metricValue(metric), 0) / records.length) : 0;
    const best = records.length ? Math.max(...records.map(metricValue)) : 0;
    const low = records.length ? Math.min(...records.map(metricValue)) : 0;
    const relatedGoals = state.goals.filter((goal) => goal.metricType === config.type);
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessDetailTitle">
        <section class="wellness-sheet wellness-sheet-wide">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Detalle de indicador</small>
          <h3 id="wellnessDetailTitle">${safeText(config.label)}</h3>
          <div class="wellness-detail-chart">${renderBars(values)}</div>
          <dl class="wellness-summary-list">
            <div><dt>Promedio</dt><dd>${average} ${safeText(config.unit)}</dd></div>
            <div><dt>Mejor resultado</dt><dd>${best} ${safeText(config.unit)}</dd></div>
            <div><dt>Resultado mas bajo</dt><dd>${low} ${safeText(config.unit)}</dd></div>
            <div><dt>Dias con registro</dt><dd>${new Set(records.map((metric) => dateOnly(new Date(metric.recordedAt)))).size}</dd></div>
            <div><dt>Dias sin registro</dt><dd>${Math.max(0, 7 - new Set(records.map((metric) => dateOnly(new Date(metric.recordedAt)))).size)}</dd></div>
            <div><dt>Objetivos relacionados</dt><dd>${relatedGoals.length}</dd></div>
          </dl>
          <article class="wellness-recommendation">
            <strong>Recomendacion general</strong>
            <p>Revisa tendencias y comparte datos fuera de rango con tu profesional de salud. Si presentas sintomas graves, solicita atencion inmediata.</p>
          </article>
          <button class="wellness-primary-soft" type="button" data-wellness-share="metric:${safeText(config.type)}">Compartir progreso</button>
        </section>
      </div>
    `;
  }

  function renderGoalDetailModal(state) {
    const goal = state.goals.find((item) => item.id === wellnessUi.goalId);
    if (!goal) return "";
    const progress = state.goalProgress.find((item) => item.goalId === goal.id) || recalculateGoalProgress(state, goal);
    const relatedRecords = state.metrics.filter((metric) => metric.type === goal.metricType).slice(-8);
    const unlocked = state.patientAchievements.filter((item) => item.goalId === goal.id && item.status === "unlocked");
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessGoalDetailTitle">
        <section class="wellness-sheet wellness-sheet-wide">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Detalle de objetivo</small>
          <h3 id="wellnessGoalDetailTitle">${safeText(goal.title)}</h3>
          <div class="wellness-goal-tabs">
            ${["Resumen", "Registros", "Tendencias", "Logros", "Configuracion"].map((label, index) => `<button class="${index === 0 ? "active" : ""}" type="button">${safeText(label)}</button>`).join("")}
          </div>
          <dl class="wellness-summary-list">
            <div><dt>Periodo</dt><dd>${safeText(goal.periodType)}</dd></div>
            <div><dt>Avance actual</dt><dd>${progress.completedOccurrences}/${progress.requiredOccurrences}</dd></div>
            <div><dt>Porcentaje</dt><dd>${progress.compliancePercentage}%</dd></div>
            <div><dt>Dias cumplidos</dt><dd>${progress.completedOccurrences}</dd></div>
            <div><dt>Dias pendientes</dt><dd>${Math.max(0, progress.requiredOccurrences - progress.completedOccurrences)}</dd></div>
            <div><dt>Racha actual</dt><dd>${progress.currentStreak} dias</dd></div>
            <div><dt>Mejor racha</dt><dd>${Math.max(progress.currentStreak, 7)} dias</dd></div>
            <div><dt>Logros desbloqueados</dt><dd>${unlocked.length}</dd></div>
          </dl>
          <div class="wellness-detail-records">
            ${relatedRecords.map(renderHistoryItem).join("")}
          </div>
          <div class="wellness-sheet-actions">
            <button type="button" class="wellness-secondary-action" data-wellness-quick-entry="${safeText(goal.metricType)}">Registrar resultado</button>
            <button type="button" class="wellness-secondary-action" data-wellness-create-goal>Editar objetivo</button>
            <button type="button" class="wellness-primary-action" data-wellness-share="goal:${safeText(goal.id)}">Compartir avance</button>
          </div>
        </section>
      </div>
    `;
  }

  function renderAchievementDetailModal(state) {
    const achievement = state.achievements.find((item) => item.id === wellnessUi.goalId);
    const patientAchievement = state.patientAchievements.find((item) => item.achievementId === achievement?.id);
    if (!achievement) return "";
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessAchievementTitle">
        <section class="wellness-sheet">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <div class="wellness-achievement-detail-medal">${icon(achievement.icon)}</div>
          <small>${safeText(achievement.category)} · ${safeText(achievement.level)}</small>
          <h3 id="wellnessAchievementTitle">${safeText(achievement.title)}</h3>
          <p>${safeText(achievement.description)}</p>
          <div class="wellness-progress-line"><span style="width:${patientAchievement?.progress || 0}%"></span></div>
          <div class="wellness-sheet-actions">
            <button class="wellness-secondary-action" type="button" data-wellness-close-modal>Cerrar</button>
            <button class="wellness-primary-action" type="button" data-wellness-share="achievement:${safeText(achievement.id)}">Compartir</button>
          </div>
        </section>
      </div>
    `;
  }

  function renderShareModal(state) {
    const label = shareTitle(state);
    return `
      <div class="wellness-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="wellnessShareTitle">
        <section class="wellness-sheet wellness-share-sheet">
          <button class="wellness-modal-close" type="button" data-wellness-close-modal aria-label="Cerrar">x</button>
          <small>Previsualizacion para compartir</small>
          <h3 id="wellnessShareTitle">${safeText(label)}</h3>
          <div class="wellness-social-preview" id="wellnessSocialPreview">
            <span>${icon("medal")}</span>
            <strong>${safeText(label)}</strong>
            <p>Avance personal de bienestar en Klini Dr Sam.</p>
          </div>
          <div class="wellness-share-format-tabs" aria-label="Formato social">
            <button class="active" type="button">Historia 1080x1920</button>
            <button type="button">Cuadrada 1080x1080</button>
            <button type="button">Vertical 1080x1350</button>
            <button type="button">Video corto</button>
          </div>
          <div class="wellness-share-channel-shell" aria-label="Opciones para compartir">
            <button class="wellness-share-channel-arrow" type="button" data-wellness-share-channel-scroll="left" aria-label="Mover opciones a la izquierda">${icon("chevron-left")}</button>
            <div class="wellness-share-channel-track" data-wellness-share-pan>
              ${renderShareChannelButton("instagram", "Instagram", "web", "instagram")}
              ${renderShareChannelButton("tiktok", "TikTok", "web", "tiktok")}
              ${renderShareChannelButton("facebook", "Facebook", "web", "facebook")}
              ${renderShareChannelButton("whatsapp", "WhatsApp", "web", "whatsapp")}
              ${renderShareChannelButton("stories", "Historias", "web", "historias")}
              ${renderShareChannelButton("messages", "Mensajes", "web", "mensajes")}
              ${renderShareChannelButton("messenger", "Messenger", "web", "messenger")}
              ${renderShareChannelButton("telegram", "Telegram", "web", "telegram")}
              ${renderShareChannelButton("download", "Descargar", "download")}
              ${renderShareChannelButton("copy", "Copiar enlace", "copy")}
              ${renderShareChannelButton("device", "Compartir por", "device")}
            </div>
            <button class="wellness-share-channel-arrow" type="button" data-wellness-share-channel-scroll="right" aria-label="Mover opciones a la derecha">${icon("chevron-right")}</button>
          </div>
        </section>
      </div>
    `;
  }

  function renderShareChannelButton(type, label, action, network = "") {
    const actionAttribute = action === "download"
      ? "data-wellness-download-share"
      : action === "copy"
        ? "data-wellness-copy-share"
        : action === "device"
          ? "data-wellness-device-share"
          : `data-wellness-web-share="${safeText(network)}"`;
    return `
      <button class="wellness-share-channel wellness-share-${safeText(type)}" type="button" ${actionAttribute}>
        <span class="wellness-share-channel-icon">${shareChannelIcon(type)}</span>
        <strong>${safeText(label)}</strong>
      </button>
    `;
  }

  function shareChannelIcon(type) {
    const paths = {
      instagram: '<rect x="5" y="5" width="14" height="14" rx="4"></rect><circle cx="12" cy="12" r="3.2"></circle><path d="M16.8 7.2h.01"></path>',
      tiktok: '<path d="M14 4v10.2a4 4 0 1 1-4-4"></path><path d="M14 4c1 3 2.8 4.7 6 5"></path>',
      facebook: '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v5h4v-5h3l1-4h-4V8z"></path>',
      whatsapp: '<path d="M4.5 20l1.1-3.7a8 8 0 1 1 3.1 2.7L4.5 20z"></path><path d="M9.5 8.8c.4 2.5 2.2 4.3 4.7 5"></path><path d="M9.5 8.8l1.2-.7 1 1.8-.8.6"></path><path d="M14.2 13.8l.6-.8 1.8 1-.7 1.2"></path>',
      stories: '<circle cx="12" cy="12" r="8" stroke-dasharray="3 2"></circle><path d="M12 8v8"></path><path d="M8 12h8"></path>',
      messages: '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path>',
      messenger: '<path d="M4 13a8 8 0 1 1 3 6.2L4 20l.8-3A7.8 7.8 0 0 1 4 13z"></path><path d="M8 13l3-3 3 3 3-3"></path>',
      telegram: '<path d="M21 4L3 11l7 3 3 7 8-17z"></path><path d="M10 14l4-4"></path>',
      download: '<path d="M12 3v12"></path><path d="M7 10l5 5 5-5"></path><path d="M5 20h14"></path>',
      copy: '<rect x="8" y="8" width="11" height="11" rx="2"></rect><path d="M5 16H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>',
      device: '<circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><path d="M8.6 10.6l6.8-4.2"></path><path d="M8.6 13.4l6.8 4.2"></path>'
    };
    return `<svg viewBox="0 0 24 24" aria-hidden="true">${paths[type] || paths.device}</svg>`;
  }

  function setupWellnessShareCarousel(root) {
    root.querySelectorAll("[data-wellness-share-pan], [data-wellness-pan]").forEach((track) => {
      if (track.dataset.wellnessPanReady === "true") return;
      track.dataset.wellnessPanReady = "true";
      track.classList.add("is-pan-enabled");
      let dragging = false;
      let moved = false;
      let startX = 0;
      let startY = 0;
      let startScroll = 0;
      const hasHorizontalOverflow = () => track.scrollWidth > track.clientWidth + 2;

      track.addEventListener("pointerdown", (event) => {
        if (event.button !== undefined && event.button !== 0) return;
        if (!hasHorizontalOverflow()) return;
        dragging = true;
        moved = false;
        startX = event.clientX;
        startY = event.clientY;
        startScroll = track.scrollLeft;
        track.classList.add("is-panning");
        track.setPointerCapture?.(event.pointerId);
      });

      track.addEventListener("pointermove", (event) => {
        if (!dragging) return;
        const delta = event.clientX - startX;
        const verticalDelta = event.clientY - startY;
        const horizontalIntent = Math.abs(delta) >= Math.abs(verticalDelta);
        if (!horizontalIntent && Math.abs(verticalDelta) > 8) return;
        if (!horizontalIntent || Math.abs(delta) <= 2) return;
        moved = true;
        event.preventDefault();
        track.scrollLeft = startScroll - delta;
      }, { passive: false });

      const stopDragging = (event) => {
        dragging = false;
        track.classList.remove("is-panning");
        track.releasePointerCapture?.(event.pointerId);
      };

      track.addEventListener("pointerup", stopDragging);
      track.addEventListener("pointercancel", stopDragging);
      track.addEventListener("dragstart", (event) => event.preventDefault());
      track.addEventListener("wheel", (event) => {
        if (!hasHorizontalOverflow()) return;
        if (Math.abs(event.deltaY) <= Math.abs(event.deltaX)) return;
        event.preventDefault();
        track.scrollLeft += event.deltaY;
      }, { passive: false });
      track.addEventListener("click", (event) => {
        if (!moved) return;
        event.preventDefault();
        event.stopPropagation();
        moved = false;
      }, true);
    });
  }

  function shareTitle(state) {
    const context = wellnessUi.shareContext || { type: "summary", id: "weekly" };
    if (context.type === "achievement") return state.achievements.find((item) => item.id === context.id)?.title || "Logro Wellness";
    if (context.type === "goal") return state.goals.find((item) => item.id === context.id)?.title || "Avance de objetivo";
    if (context.type === "metric") return metricConfig(context.id).label;
    return `Resumen ${safeText(context.id || "semanal")}`;
  }

  function renderBars(values) {
    if (!values.length) return renderEmptyState("Sin datos", "Registra resultados para visualizar tendencias.");
    return `
      <div class="wellness-bars" aria-hidden="true">
        ${values.map((value) => `<i style="height:${Math.max(12, Math.min(100, value))}%"></i>`).join("")}
      </div>
    `;
  }

  function handleCommunityAction(button) {
    const state = loadState();
    const action = button.dataset.wellnessCommunityAction;
    const communityId = button.dataset.communityId;
    const eventId = button.dataset.eventId;
    const requestId = button.dataset.requestId;
    if (action === "toggle-admin-notifications") {
      wellnessUi.notificationsOpen = !wellnessUi.notificationsOpen;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "toggle-admin-messages") {
      wellnessUi.messagesOpen = !wellnessUi.messagesOpen;
      wellnessUi.notificationsOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "open-admin-message") {
      wellnessUi.messagesOpen = true;
      wellnessUi.notificationsOpen = false;
      wellnessUi.activeAdminMessageId = button.dataset.messageId || "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "back-admin-messages") {
      wellnessUi.messagesOpen = true;
      wellnessUi.notificationsOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "close-admin-messages") {
      wellnessUi.messagesOpen = false;
      wellnessUi.notificationsOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "open-admin-request-flow") {
      wellnessUi.notificationsOpen = true;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = button.dataset.requestId || "";
      renderPatientWellness();
      return;
    }
    if (action === "back-admin-notifications") {
      wellnessUi.notificationsOpen = true;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "close-admin-notifications") {
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "view") {
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      wellnessUi.communityId = communityId;
      wellnessUi.modal = "community-detail";
      renderPatientWellness();
      return;
    }
    if (action === "already-member" && communityId) {
      notify("Ya perteneces a esta comunidad.");
      return;
    }
    if (action === "admin-open" && communityId) {
      if (canManageCommunity(state, communityId)) {
        wellnessUi.adminCommunityId = communityId;
        wellnessUi.communityView = "admin";
        wellnessUi.modal = "";
        wellnessUi.notificationsOpen = false;
        wellnessUi.messagesOpen = false;
        wellnessUi.activeAdminMessageId = "";
        wellnessUi.activeAdminRequestId = "";
        renderPatientWellness();
      }
      return;
    }
    if (action === "admin-edit-community" && communityId) {
      if (canManageCommunity(state, communityId)) {
        wellnessUi.adminCommunityId = communityId;
        wellnessUi.modal = "edit-community";
        wellnessUi.notificationsOpen = false;
        wellnessUi.messagesOpen = false;
        wellnessUi.activeAdminMessageId = "";
        wellnessUi.activeAdminRequestId = "";
        renderPatientWellness();
      }
      return;
    }
    if (action === "admin-invite-members" && communityId) {
      wellnessUi.messagesOpen = false;
      wellnessUi.notificationsOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      notify("Se prepararia una invitacion para nuevos miembros de la comunidad.");
      renderPatientWellness();
      return;
    }
    if (action === "admin-event-attendees" && eventId) {
      const eventItem = eventById(state, eventId);
      const seats = eventItem ? eventSeats(state, eventItem) : { confirmed: 0 };
      notify(`Asistentes confirmados: ${seats.confirmed}.`);
      return;
    }
    if (action === "admin-tool-info" && communityId) {
      if (canManageCommunity(state, communityId)) {
        wellnessUi.adminCommunityId = communityId;
        wellnessUi.modal = "edit-community";
        wellnessUi.notificationsOpen = false;
        wellnessUi.messagesOpen = false;
        wellnessUi.activeAdminMessageId = "";
        wellnessUi.activeAdminRequestId = "";
        renderPatientWellness();
      }
      return;
    }
    if (action === "creator-profile") {
      if (communityId) wellnessUi.adminCommunityId = communityId;
      wellnessUi.modal = "creator-profile";
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "remove-admin-profile-photo") {
      saveCommunityAdminProfilePatch({ photoUrl: null });
      notify("Foto de perfil eliminada.");
      renderPatientWellness();
      return;
    }
    if (action === "admin-profile-view" && requestId) {
      wellnessUi.adminProfileRequestId = requestId;
      wellnessUi.modal = "admin-request-profile";
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }
    if (action === "accept-all-requests" && communityId) {
      wellnessUi.activeAdminRequestId = "";
      const requests = state.communityJoinRequests.filter((request) => request.communityId === communityId && request.status === "pending");
      requests.forEach((request) => {
        request.status = "accepted";
        request.resolvedAt = new Date().toISOString();
        request.resolvedBy = currentCommunityAdmin().id;
        if (!state.communityMembers.some((member) => member.communityId === request.communityId && member.userId === request.userId)) {
          state.communityMembers.push({
            id: wellnessId("member"),
            communityId: request.communityId,
            userId: request.userId,
            role: "member",
            status: "active",
            joinedAt: new Date().toISOString()
          });
        }
      });
      const community = communityById(state, communityId);
      if (community) community.memberCount = Number(community.memberCount || 0) + requests.length;
      notify(`${requests.length} solicitudes aceptadas.`);
      saveState(state);
      renderPatientWellness();
      return;
    }
    if (action === "duplicate-event" && eventId) {
      const eventItem = eventById(state, eventId);
      if (eventItem && canManageCommunity(state, eventItem.communityId)) {
        const start = new Date(eventItem.startAt);
        const end = new Date(eventItem.endAt);
        start.setDate(start.getDate() + 7);
        end.setDate(end.getDate() + 7);
        state.communityEvents.unshift({
          ...eventItem,
          id: wellnessId("event"),
          title: `${eventItem.title} (copia)`,
          startAt: start.toISOString(),
          endAt: end.toISOString(),
          status: "draft",
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        });
        notify("Evento duplicado como borrador.");
      }
    }
    if (action === "cancel-event" && eventId) {
      const eventItem = eventById(state, eventId);
      if (eventItem && canManageCommunity(state, eventItem.communityId)) {
        eventItem.status = "cancelled";
        eventItem.updatedAt = new Date().toISOString();
        notify("Evento cancelado.");
      }
    }
    if (action === "delete-event" && eventId) {
      const eventItem = eventById(state, eventId);
      if (eventItem && canManageCommunity(state, eventItem.communityId)) {
        state.communityEvents = state.communityEvents.filter((item) => item.id !== eventId);
        state.eventReservations = state.eventReservations.filter((reservation) => reservation.eventId !== eventId);
        notify("Evento eliminado.");
      }
    }
    if (action === "send-event-reminder" && eventId) {
      notify("Recordatorio preparado para asistentes e invitados.");
      return;
    }
    if (action === "close-reservations" && eventId) {
      const eventItem = eventById(state, eventId);
      if (eventItem && canManageCommunity(state, eventItem.communityId)) {
        eventItem.bookingClosed = true;
        eventItem.bookingDeadline = new Date().toISOString();
        eventItem.updatedAt = new Date().toISOString();
        notify("Reservaciones cerradas.");
      }
    }
    if (["admin-profile-view", "member-message", "member-invite-event", "member-note", "member-block"].includes(action)) {
      const labels = {
        "admin-profile-view": "Perfil administrativo abierto.",
        "member-message": "Mensaje listo para enviar al miembro.",
        "member-invite-event": "Invitacion a evento preparada.",
        "member-note": "Notas del miembro abiertas.",
        "member-block": "Miembro bloqueado para revision."
      };
      notify(labels[action]);
      return;
    }
    if (action === "member-suspend") {
      const member = state.communityMembers.find((item) => item.id === button.dataset.memberId);
      if (member && member.role !== "owner" && canManageCommunity(state, member.communityId)) {
        member.status = "suspended";
        member.updatedAt = new Date().toISOString();
        notify("Miembro suspendido.");
      }
    }
    if (["admin-export", "admin-ai", "admin-calendar-move", "admin-calendar-duplicate", "toggle-automation", "toggle-gamification", "admin-create-user", "compose-admin-message", "admin-message-requests", "admin-message-call", "admin-message-video", "admin-message-camera", "admin-message-audio", "admin-message-media"].includes(action)) {
      const labels = {
        "admin-export": "Exportacion de miembros preparada.",
        "admin-ai": "Asistente IA listo para generar contenido o eventos.",
        "admin-calendar-move": "Movimiento de calendario preparado.",
        "admin-calendar-duplicate": "Elemento del calendario duplicado.",
        "toggle-automation": "Automatizacion actualizada.",
        "toggle-gamification": "Configuracion de gamificacion abierta.",
        "admin-create-user": "Acceso de administrador preparado para alta independiente.",
        "compose-admin-message": "Nuevo mensaje listo para redactar.",
        "admin-message-requests": "Solicitudes de mensajes abiertas.",
        "admin-message-call": "Llamada preparada.",
        "admin-message-video": "Videollamada preparada.",
        "admin-message-camera": "Camara lista.",
        "admin-message-audio": "Audio listo.",
        "admin-message-media": "Selector de imagen preparado."
      };
      notify(labels[action]);
      return;
    }
    if (action === "toggle-admin-setting" && communityId) {
      const community = communityById(state, communityId);
      const setting = button.dataset.setting;
      if (community && canManageCommunity(state, communityId)) {
        if (setting === "open") community.accessType = community.accessType === "open" ? "closed" : "open";
        if (setting === "visible") community.visibility = community.visibility === "hidden" ? "public" : "hidden";
        if (setting === "posts") community.allowMemberPosts = community.allowMemberPosts === false;
        community.updatedAt = new Date().toISOString();
        saveState(state);
        notify("Configuracion actualizada.");
        renderPatientWellness();
      }
      return;
    }
    if (action === "join" && communityId) {
      if (!membershipFor(state, communityId)) {
        state.communityMembers.push({
          id: wellnessId("member"),
          communityId,
          userId: state.patientId,
          role: "member",
          status: "active",
          joinedAt: new Date().toISOString()
        });
        const community = communityById(state, communityId);
        if (community) community.memberCount = Number(community.memberCount || 0) + 1;
        notify("Te uniste a la comunidad.");
      } else {
        notify("Ya perteneces a esta comunidad.");
      }
    }
    if (action === "request" && communityId) {
      if (!requestForCurrentUser(state, communityId) && !membershipFor(state, communityId)) {
        state.communityJoinRequests.push({
          id: wellnessId("request"),
          communityId,
          userId: state.patientId,
          userName: state.patientName,
          userInitials: initialsText(state.patientName),
          brief: "Solicitud enviada desde Wellness.",
          sharedInterests: ["Bienestar", "Prevencion"],
          status: "pending",
          requestedAt: new Date().toISOString()
        });
        notify("Solicitud enviada.");
      }
    }
    if (action === "leave" && communityId) {
      const membership = membershipFor(state, communityId);
      if (membership && membership.role !== "owner") {
        membership.status = "removed";
        const community = communityById(state, communityId);
        if (community) community.memberCount = Math.max(0, Number(community.memberCount || 0) - 1);
        notify("Saliste de la comunidad.");
      } else {
        notify("El propietario no puede salir sin transferir la comunidad.");
      }
    }
    if (action === "reserve" && eventId) {
      const eventItem = eventById(state, eventId);
      const seats = eventItem ? eventSeats(state, eventItem) : { available: 0 };
      if (!eventReservationForUser(state, eventId) && eventItem && seats.available > 0) {
        state.eventReservations.push({
          id: wellnessId("reservation"),
          eventId,
          userId: state.patientId,
          status: "confirmed",
          reservedAt: new Date().toISOString()
        });
        notify("Reservacion confirmada.");
      }
    }
    if (action === "waitlist" && eventId) {
      const eventItem = eventById(state, eventId);
      const seats = eventItem ? eventSeats(state, eventItem) : { waitlisted: 0 };
      if (!eventReservationForUser(state, eventId) && eventItem?.allowWaitlist) {
        state.eventReservations.push({
          id: wellnessId("reservation"),
          eventId,
          userId: state.patientId,
          status: "waitlisted",
          reservedAt: new Date().toISOString(),
          waitlistPosition: seats.waitlisted + 1
        });
        notify("Te agregamos a la lista de espera.");
      }
    }
    if (action === "cancel-reservation" && eventId) {
      const reservation = eventReservationForUser(state, eventId);
      if (reservation) {
        reservation.status = "cancelled";
        reservation.cancelledAt = new Date().toISOString();
        promoteWaitlistUser(state, eventId);
        notify("Reservacion cancelada.");
      }
    }
    if (action === "accept-request" && requestId) {
      const request = state.communityJoinRequests.find((item) => item.id === requestId);
      if (request && request.status === "pending") {
        wellnessUi.activeAdminRequestId = "";
        request.status = "accepted";
        request.resolvedAt = new Date().toISOString();
        request.resolvedBy = currentCommunityAdmin().id;
        state.communityMembers.push({
          id: wellnessId("member"),
          communityId: request.communityId,
          userId: request.userId,
          role: "member",
          status: "active",
          joinedAt: new Date().toISOString()
        });
        const community = communityById(state, request.communityId);
        if (community) community.memberCount = Number(community.memberCount || 0) + 1;
        notify("Solicitud aceptada.");
      }
    }
    if (action === "reject-request" && requestId) {
      const request = state.communityJoinRequests.find((item) => item.id === requestId);
      if (request && request.status === "pending") {
        wellnessUi.activeAdminRequestId = "";
        request.status = "rejected";
        request.resolvedAt = new Date().toISOString();
        request.resolvedBy = currentCommunityAdmin().id;
        notify("Solicitud rechazada.");
      }
    }
    if (action === "remove-member") {
      const memberId = button.dataset.memberId;
      const member = state.communityMembers.find((item) => item.id === memberId);
      if (member && member.role !== "owner" && canManageCommunity(state, member.communityId)) {
        member.status = "removed";
        member.removedAt = new Date().toISOString();
        member.removedBy = currentCommunityAdmin().id;
        const community = communityById(state, member.communityId);
        if (community) community.memberCount = Math.max(0, Number(community.memberCount || 0) - 1);
        notify("Miembro removido de la comunidad.");
      }
    }
    if (action === "toggle-post-pin") {
      const post = (state.communityPosts || []).find((item) => item.id === button.dataset.postId);
      if (post && canManageCommunity(state, post.communityId)) {
        post.pinned = !post.pinned;
        post.updatedAt = new Date().toISOString();
        notify(post.pinned ? "Publicacion fijada." : "Publicacion sin fijar.");
      }
    }
    if (action === "delete-post") {
      const post = (state.communityPosts || []).find((item) => item.id === button.dataset.postId);
      if (post && canManageCommunity(state, post.communityId)) {
        state.communityPosts = state.communityPosts.filter((item) => item.id !== post.id);
        notify("Publicacion eliminada.");
      }
    }
    saveState(state);
    renderPatientWellness();
  }

  function promoteWaitlistUser(state, eventId) {
    const next = state.eventReservations
      .filter((reservation) => reservation.eventId === eventId && reservation.status === "waitlisted")
      .sort((a, b) => Number(a.waitlistPosition || 99) - Number(b.waitlistPosition || 99))[0];
    if (next) {
      next.status = "confirmed";
      next.waitlistPosition = 0;
      next.promotedAt = new Date().toISOString();
    }
  }

  function handlePatientWellnessAction(event) {
    const clickedOutsideAdminFloatingPanel = (wellnessUi.messagesOpen || wellnessUi.notificationsOpen)
      && !event.target.closest(".wellness-admin-notification-dropdown")
      && !event.target.closest('[data-wellness-community-action="toggle-admin-messages"]')
      && !event.target.closest('[data-wellness-community-action="toggle-admin-notifications"]');
    if (clickedOutsideAdminFloatingPanel) {
      wellnessUi.messagesOpen = false;
      wellnessUi.notificationsOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
    }

    const homeButton = event.target.closest("[data-wellness-home]");
    if (homeButton) {
      closePatientWellnessModal(false);
      if (typeof globalThis.returnToPatientHome === "function") {
        globalThis.returnToPatientHome();
      } else if (typeof globalThis.showView === "function") {
        globalThis.showView("homeView");
      }
      return;
    }

    const devicesButton = event.target.closest("[data-wellness-open-devices]");
    if (devicesButton) {
      closePatientWellnessModal(false);
      if (typeof globalThis.showView === "function") {
        globalThis.showView("medicalDevicesView");
      }
      globalThis.updatePatientGlobalBottomNav?.("medicalDevicesView");
      return;
    }

    const sectionButton = event.target.closest("[data-wellness-section]");
    if (sectionButton) {
      wellnessUi.section = sectionButton.dataset.wellnessSection;
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminRequestId = "";
      if (wellnessUi.section === "communities") {
        wellnessUi.communityView = "landing";
      }
      localStorage.setItem(WELLNESS_SECTION_KEY, wellnessUi.section);
      closePatientWellnessModal(false);
      renderPatientWellness();
      globalThis.updatePatientGlobalBottomNav?.(wellnessUi.section);
      return;
    }

    const eventGroupMoreButton = event.target.closest("[data-wellness-event-group-more]");
    if (eventGroupMoreButton) {
      const key = eventGroupMoreButton.dataset.wellnessEventGroupMore;
      if (key) {
        wellnessUi.eventGroupVisible[key] = (Number(wellnessUi.eventGroupVisible?.[key]) || COMMUNITY_EVENT_GROUP_PAGE_SIZE) + COMMUNITY_EVENT_GROUP_PAGE_SIZE;
      }
      renderPatientWellness();
      return;
    }

    const communityViewButton = event.target.closest("[data-wellness-community-view]");
    if (communityViewButton) {
      wellnessUi.communityView = communityViewButton.dataset.wellnessCommunityView || "landing";
      if (wellnessUi.communityView === "admin") wellnessUi.adminModule = "dashboard";
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }

    const adminModuleButton = event.target.closest("[data-wellness-admin-module]");
    if (adminModuleButton) {
      wellnessUi.communityView = "admin";
      wellnessUi.adminModule = adminModuleButton.dataset.wellnessAdminModule || "dashboard";
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }

    const communityOpenButton = event.target.closest("[data-wellness-community-open]");
    if (communityOpenButton) {
      wellnessUi.communityId = communityOpenButton.dataset.wellnessCommunityOpen;
      wellnessUi.modal = "community-detail";
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }

    const eventOpenButton = event.target.closest("[data-wellness-event-open]");
    if (eventOpenButton) {
      wellnessUi.eventId = eventOpenButton.dataset.wellnessEventOpen;
      wellnessUi.modal = "event-detail";
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }

    const communityAction = event.target.closest("[data-wellness-community-action]");
    if (communityAction) {
      handleCommunityAction(communityAction);
      return;
    }

    const periodButton = event.target.closest("[data-wellness-period]");
    if (periodButton) {
      wellnessUi.period = periodButton.dataset.wellnessPeriod;
      renderPatientWellness();
      return;
    }

    const moodButton = event.target.closest("[data-wellness-mood]");
    if (moodButton) {
      const state = loadState();
      state.selectedMood = moodButton.dataset.wellnessMoodLabel;
      state.moodRecordedAt = new Date().toISOString();
      state.metrics.push({
        id: wellnessId("metric"),
        patientId: state.patientId,
        type: "mood_score",
        value: Number(moodButton.dataset.wellnessMood),
        unit: "/5",
        recordedAt: new Date().toISOString(),
        source: "manual",
        notes: `Estado registrado: ${moodButton.dataset.wellnessMoodLabel}`,
        metadata: {}
      });
      recalculateState(state);
      saveState(state);
      notify(`Registraste tu estado: ${moodButton.dataset.wellnessMoodLabel}.`);
      renderPatientWellness();
      return;
    }

    const quickButton = event.target.closest("[data-wellness-quick-entry]");
    if (quickButton) {
      wellnessUi.quickMetric = quickButton.dataset.wellnessQuickEntry || wellnessUi.quickMetric || "water_intake";
      wellnessUi.modal = "quick";
      renderPatientWellness();
      globalThis.updatePatientGlobalBottomNav?.("today");
      return;
    }

    const quickSelect = event.target.closest("[data-wellness-select-quick]");
    if (quickSelect) {
      wellnessUi.quickMetric = quickSelect.dataset.wellnessSelectQuick;
      wellnessUi.modal = "quick";
      renderPatientWellness();
      return;
    }

    const attachmentMenuButton = event.target.closest("[data-wellness-attachment-menu]");
    if (attachmentMenuButton) {
      const control = attachmentMenuButton.closest(".wellness-attachment-control");
      const isOpen = control?.classList.toggle("open");
      attachmentMenuButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
      return;
    }

    const attachmentPick = event.target.closest("[data-wellness-attachment-pick]");
    if (attachmentPick) {
      const control = attachmentPick.closest(".wellness-attachment-control");
      const input = control?.querySelector("[data-wellness-attachment-input]");
      if (input) {
        input.accept = "image/*";
        if (attachmentPick.dataset.wellnessAttachmentPick === "camera") {
          input.setAttribute("capture", "environment");
        } else {
          input.removeAttribute("capture");
        }
        control?.classList.remove("open");
        control?.querySelector("[data-wellness-attachment-menu]")?.setAttribute("aria-expanded", "false");
        input.click();
      }
      return;
    }

    const adminPostFocus = event.target.closest("[data-wellness-admin-focus-post]");
    if (adminPostFocus) {
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminMessageId = "";
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      const form = document.querySelector("#wellnessView [data-wellness-community-post-form]");
      form?.scrollIntoView({ behavior: "smooth", block: "center" });
      form?.querySelector("input[name='title']")?.focus();
      return;
    }

    const modalButton = event.target.closest("[data-wellness-modal]");
    if (modalButton) {
      wellnessUi.modal = modalButton.dataset.wellnessModal;
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
      return;
    }

    const detailButton = event.target.closest("[data-wellness-metric-detail]");
    if (detailButton) {
      wellnessUi.detailMetric = detailButton.dataset.wellnessMetricDetail;
      wellnessUi.modal = "detail";
      renderPatientWellness();
      return;
    }

    const goalButton = event.target.closest("[data-wellness-goal-detail]");
    if (goalButton) {
      wellnessUi.goalId = goalButton.dataset.wellnessGoalDetail;
      wellnessUi.modal = "goal-detail";
      renderPatientWellness();
      return;
    }

    if (event.target.closest("[data-wellness-create-goal]")) {
      wellnessUi.modal = "goal";
      renderPatientWellness();
      return;
    }

    const deleteGoalButton = event.target.closest("[data-wellness-goal-delete]");
    if (deleteGoalButton) {
      deleteWellnessGoal(deleteGoalButton.dataset.wellnessGoalDelete);
      return;
    }

    const achievementButton = event.target.closest("[data-wellness-achievement-detail]");
    if (achievementButton) {
      wellnessUi.goalId = achievementButton.dataset.wellnessAchievementDetail;
      wellnessUi.modal = "achievement";
      renderPatientWellness();
      return;
    }

    const shareButton = event.target.closest("[data-wellness-share]");
    if (shareButton) {
      const [type, id] = String(shareButton.dataset.wellnessShare || "summary:weekly").split(":");
      wellnessUi.shareContext = { type, id };
      wellnessUi.modal = "share";
      renderPatientWellness();
      return;
    }

    const dismissAlert = event.target.closest("[data-wellness-dismiss-alert]");
    if (dismissAlert) {
      const state = loadState();
      const alert = state.alerts.find((item) => item.id === dismissAlert.dataset.wellnessDismissAlert);
      if (alert) alert.status = "reviewed";
      saveState(state);
      notify("Alerta marcada como revisada.");
      renderPatientWellness();
      return;
    }

    const integrationButton = event.target.closest("[data-wellness-toggle-integration]");
    if (integrationButton) {
      const state = loadState();
      const item = state.integrations.find((integration) => integration.id === integrationButton.dataset.wellnessToggleIntegration);
      if (item) {
        item.status = item.status === "Conectado" ? "Pausado" : "Conectado";
        item.lastSync = item.status === "Conectado" ? new Date().toISOString() : item.lastSync;
      }
      saveState(state);
      renderPatientWellness();
      return;
    }

    if (event.target.closest("[data-wellness-contact-doctor]")) {
      notify("Se prepararia contacto con el medico o agenda de consulta.");
      return;
    }

    const shareScrollButton = event.target.closest("[data-wellness-share-channel-scroll]");
    if (shareScrollButton) {
      scrollWellnessShareChannels(shareScrollButton.dataset.wellnessShareChannelScroll);
      return;
    }

    if (event.target.closest("[data-wellness-download-share]")) {
      downloadShareCard(loadState());
      return;
    }

    if (event.target.closest("[data-wellness-copy-share]")) {
      const text = `${shareTitle(loadState())} - Avance Wellness en Klini Dr Sam`;
      navigator.clipboard?.writeText(text);
      notify("Texto copiado para compartir.");
      return;
    }

    if (event.target.closest("[data-wellness-device-share]")) {
      shareWithDevice(loadState(), "dispositivo");
      return;
    }

    const webShareButton = event.target.closest("[data-wellness-web-share]");
    if (webShareButton) {
      shareWithDevice(loadState(), webShareButton.dataset.wellnessWebShare);
      return;
    }

    if (event.target.closest("[data-wellness-close-modal]") || event.target.classList.contains("wellness-modal-backdrop")) {
      closePatientWellnessModal();
      return;
    }

    if (clickedOutsideAdminFloatingPanel) {
      wellnessUi.activeAdminRequestId = "";
      renderPatientWellness();
    }
  }

  function handlePatientWellnessSubmit(event) {
    const quickForm = event.target.closest("[data-wellness-quick-form]");
    if (quickForm) {
      event.preventDefault();
      saveQuickMetric(new FormData(quickForm));
      return;
    }
    const goalForm = event.target.closest("[data-wellness-goal-form]");
    if (goalForm) {
      event.preventDefault();
      saveNewGoal(new FormData(goalForm));
      return;
    }
    const communityForm = event.target.closest("[data-wellness-community-form]");
    if (communityForm) {
      event.preventDefault();
      saveNewCommunity(new FormData(communityForm));
      return;
    }
    const communityAdminForm = event.target.closest("[data-wellness-community-admin-form]");
    if (communityAdminForm) {
      event.preventDefault();
      saveCommunityAdminPage(new FormData(communityAdminForm));
      return;
    }
    const communityPostForm = event.target.closest("[data-wellness-community-post-form]");
    if (communityPostForm) {
      event.preventDefault();
      saveCommunityPost(new FormData(communityPostForm));
      return;
    }
    const eventForm = event.target.closest("[data-wellness-event-form]");
    if (eventForm) {
      event.preventDefault();
      saveNewCommunityEvent(new FormData(eventForm));
    }
  }

  function handlePatientWellnessChange(event) {
    const adminCommunitySelect = event.target.closest("[data-wellness-admin-community-select]");
    if (adminCommunitySelect) {
      wellnessUi.adminCommunityId = adminCommunitySelect.value;
      wellnessUi.communityView = "admin";
      wellnessUi.notificationsOpen = false;
      wellnessUi.messagesOpen = false;
      renderPatientWellness();
      return;
    }
    const memberRoleSelect = event.target.closest("[data-wellness-admin-member-role]");
    if (memberRoleSelect) {
      updateCommunityMemberRole(memberRoleSelect.dataset.wellnessAdminMemberRole, memberRoleSelect.value);
      return;
    }
    const eventStatusSelect = event.target.closest("[data-wellness-admin-event-status]");
    if (eventStatusSelect) {
      updateCommunityEventStatus(eventStatusSelect.dataset.wellnessAdminEventStatus, eventStatusSelect.value);
      return;
    }
    const postStatusSelect = event.target.closest("[data-wellness-admin-post-status]");
    if (postStatusSelect) {
      updateCommunityPostStatus(postStatusSelect.dataset.wellnessAdminPostStatus, postStatusSelect.value);
      return;
    }
    const adminProfilePhotoInput = event.target.closest("[data-wellness-admin-profile-photo]");
    if (adminProfilePhotoInput) {
      const file = adminProfilePhotoInput.files?.[0];
      if (!file || typeof FileReader !== "function") return;
      const reader = new FileReader();
      reader.addEventListener("load", () => {
        saveCommunityAdminProfilePatch({ photoUrl: String(reader.result || "") });
        notify("Foto de perfil actualizada.");
        renderPatientWellness();
      });
      reader.readAsDataURL(file);
      return;
    }
    const brandFileInput = event.target.closest("[data-wellness-brand-file]");
    if (brandFileInput) {
      const file = brandFileInput.files?.[0];
      const targetName = brandFileInput.dataset.wellnessBrandFile;
      const form = brandFileInput.closest("[data-wellness-community-admin-form]");
      const targetInput = form?.querySelector(`input[name="${targetName}"]`);
      if (!file || !targetInput || typeof FileReader !== "function") return;
      const reader = new FileReader();
      reader.addEventListener("load", () => {
        targetInput.value = String(reader.result || "");
        notify(targetName === "logoImage" ? "Logotipo listo para guardar." : "Imagen de fondo lista para guardar.");
      });
      reader.readAsDataURL(file);
      return;
    }
    const eventImageFileInput = event.target.closest("[data-wellness-event-image-file]");
    if (eventImageFileInput) {
      const file = eventImageFileInput.files?.[0];
      const form = eventImageFileInput.closest("[data-wellness-event-form]");
      const urlInput = form?.querySelector("[data-wellness-event-image-url]");
      if (!file || !urlInput || typeof FileReader !== "function") return;
      const reader = new FileReader();
      reader.addEventListener("load", () => {
        urlInput.value = String(reader.result || "");
        updateEventImagePreview(form);
        notify("Imagen del evento lista para publicar.");
      });
      reader.readAsDataURL(file);
      return;
    }
    const attachmentInput = event.target.closest("[data-wellness-attachment-input]");
    if (!attachmentInput) return;
    const control = attachmentInput.closest(".wellness-attachment-control");
    const label = control?.querySelector("[data-wellness-attachment-name]");
    if (label) {
      label.textContent = attachmentInput.files?.[0]?.name || "Sin archivo seleccionado";
    }
  }

  function handlePatientWellnessInput(event) {
    const eventImageUrl = event?.target?.closest?.("[data-wellness-event-image-url]");
    if (eventImageUrl) {
      updateEventImagePreview(eventImageUrl.closest("[data-wellness-event-form]"));
      return;
    }
  }

  function updateEventImagePreview(form) {
    if (!form) return;
    const preview = form.querySelector("[data-wellness-event-image-preview]");
    const urlInput = form.querySelector("[data-wellness-event-image-url]");
    if (!preview || !urlInput) return;
    const image = safeCssUrl(urlInput.value);
    preview.classList.toggle("has-image", Boolean(image));
    if (image) {
      preview.style.backgroundImage = `linear-gradient(180deg, rgba(255,255,255,0.18), rgba(5,42,54,0.22)), url('${image}')`;
      preview.innerHTML = "";
    } else {
      preview.style.backgroundImage = "";
      preview.innerHTML = renderEventCoverContent({}, new Date("2026-07-30T18:00:00"));
    }
  }

  function saveQuickMetric(formData) {
    const state = loadState();
    const type = String(formData.get("metricType") || wellnessUi.quickMetric);
    const config = metricConfig(type);
    const isNote = type === "personal_note";
    const rawValue = String(formData.get("value") || "").trim();
    const value = isNote ? rawValue : Number(rawValue);
    if (!isNote && !Number.isFinite(value)) {
      notify("Ingresa un valor valido.");
      return;
    }
    const file = formData.get("attachment");
    const metric = {
      id: wellnessId("metric"),
      patientId: state.patientId,
      type,
      value,
      unit: isNote ? "" : config.unit,
      recordedAt: new Date(formData.get("recordedAt") || new Date()).toISOString(),
      source: "manual",
      notes: String(formData.get("notes") || ""),
      metadata: file && file.name ? { attachmentName: file.name, attachmentType: file.type || "archivo" } : {}
    };
    state.metrics.push(metric);
    if (type === "blood_pressure_systolic") metric.metadata.diastolic = 80;
    validateClinicalAlert(state, metric);
    recalculateState(state);
    saveState(state);
    closePatientWellnessModal(false);
    const goal = state.goals.find((item) => item.metricType === type);
    const progress = goal ? state.goalProgress.find((item) => item.goalId === goal.id) : null;
    const targetText = goal ? ` Avance del objetivo: ${progress?.compliancePercentage || 0}%.` : "";
    notify(`Registraste ${metricDisplay(metric)}.${targetText}`);
    renderPatientWellness();
  }

  function validateClinicalAlert(state, metric) {
    const config = metricConfig(metric.type);
    if (config.min === undefined || config.max === undefined) return;
    const value = metricValue(metric);
    if (value >= config.min && value <= config.max) return;
    state.alerts.unshift({
      id: wellnessId("alert"),
      patientId: state.patientId,
      type: metric.type,
      severity: value > config.max ? "attention" : "review",
      title: "Resultado fuera del rango configurado",
      message: `El registro de ${config.label.toLowerCase()} esta fuera del rango configurado. Considera comunicarte con tu profesional de salud.`,
      createdAt: new Date().toISOString(),
      status: "open"
    });
  }

  function saveNewGoal(formData) {
    const state = loadState();
    const metricType = String(formData.get("metricType") || "water_intake");
    const goal = {
      id: wellnessId("goal"),
      patientId: state.patientId,
      title: String(formData.get("title") || "Objetivo Wellness"),
      description: String(formData.get("description") || ""),
      category: String(formData.get("category") || metricConfig(metricType).category),
      metricType,
      targetValue: Number(formData.get("targetValue") || 1),
      targetUnit: String(formData.get("targetUnit") || metricConfig(metricType).unit || ""),
      comparisonOperator: String(formData.get("comparisonOperator") || "greater_than_or_equal"),
      periodType: String(formData.get("periodType") || "weekly"),
      requiredOccurrences: Number(formData.get("requiredOccurrences") || 1),
      minimumCompliancePercentage: 100,
      streakRequired: 0,
      validRangeMin: formData.get("validRangeMin") ? Number(formData.get("validRangeMin")) : undefined,
      validRangeMax: formData.get("validRangeMax") ? Number(formData.get("validRangeMax")) : undefined,
      startDate: new Date(formData.get("startDate") || new Date()).toISOString(),
      endDate: formData.get("endDate") ? new Date(formData.get("endDate")).toISOString() : "",
      daysApplicable: String(formData.get("days") || ""),
      recommendedTime: String(formData.get("recommendedTime") || ""),
      reminders: String(formData.get("reminders") || "true") === "true",
      privacyLevel: String(formData.get("privacyLevel") || "private"),
      relatedDoctor: String(formData.get("doctor") || ""),
      relatedProgram: String(formData.get("program") || ""),
      status: "active",
      createdBy: "patient"
    };
    state.goals.unshift(goal);
    recalculateState(state);
    saveState(state);
    closePatientWellnessModal(false);
    wellnessUi.section = "wellbeing";
    notify("Objetivo creado y enlazado a tus registros diarios.");
    renderPatientWellness();
  }

  function deleteWellnessGoal(goalId) {
    const state = loadState();
    const goal = state.goals.find((item) => item.id === goalId);
    if (!goal) return;
    const confirmed = globalThis.confirm
      ? globalThis.confirm(`Eliminar el objetivo "${goal.title}"?`)
      : true;
    if (!confirmed) return;
    state.goals = state.goals.filter((item) => item.id !== goalId);
    state.goalProgress = state.goalProgress.filter((item) => item.goalId !== goalId);
    state.patientAchievements = state.patientAchievements.filter((item) => item.goalId !== goalId);
    if (wellnessUi.goalId === goalId) {
      wellnessUi.goalId = "";
      closePatientWellnessModal(false);
    }
    recalculateState(state);
    saveState(state);
    notify("Objetivo eliminado.");
    renderPatientWellness();
  }

  function saveCommunityAdminPage(formData) {
    const state = loadState();
    const communityId = String(formData.get("communityId") || "");
    const community = communityById(state, communityId);
    if (!community || !canManageCommunity(state, communityId)) {
      notify("No tienes permisos para administrar esta comunidad.");
      return;
    }
    const name = String(formData.get("name") || community.name).trim();
    community.name = name || community.name;
    community.slug = community.name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "") || community.id;
    community.shortDescription = String(formData.get("shortDescription") || "").trim();
    community.description = String(formData.get("description") || "").trim();
    community.category = String(formData.get("category") || community.category);
    community.city = String(formData.get("city") || "").trim() || "Online";
    community.region = community.city;
    community.language = String(formData.get("language") || "Espanol");
    community.accessType = String(formData.get("accessType") || "open");
    community.visibility = String(formData.get("visibility") || "public");
    community.status = String(formData.get("status") || "active");
    community.coverTone = String(formData.get("coverTone") || community.coverTone || "mint");
    community.backgroundImage = String(formData.get("backgroundImage") || "").trim();
    community.logoImage = String(formData.get("logoImage") || "").trim();
    community.logoInitials = String(formData.get("logoInitials") || initialsText(community.name).slice(0, 3)).trim().slice(0, 3).toUpperCase();
    community.rules = String(formData.get("rules") || "").trim();
    community.pinnedMessage = String(formData.get("pinnedMessage") || "").trim();
    community.updatedAt = new Date().toISOString();
    saveState(state);
    wellnessUi.adminCommunityId = community.id;
    wellnessUi.communityView = "admin";
    wellnessUi.modal = "";
    notify("Pagina de comunidad actualizada.");
    renderPatientWellness();
  }

  function saveCommunityPost(formData) {
    const state = loadState();
    const admin = currentCommunityAdmin();
    const communityId = String(formData.get("communityId") || "");
    if (!communityId || !canManageCommunity(state, communityId)) {
      notify("Selecciona una comunidad que puedas administrar.");
      return;
    }
    const title = String(formData.get("title") || "").trim();
    const body = String(formData.get("body") || "").trim();
    if (!title || !body) {
      notify("Completa titulo y contenido de la publicacion.");
      return;
    }
    state.communityPosts = Array.isArray(state.communityPosts) ? state.communityPosts : [];
    state.communityPosts.unshift({
      id: wellnessId("post"),
      communityId,
      authorId: admin.id,
      authorName: admin.fullName,
      title,
      body,
      postType: String(formData.get("postType") || "Texto"),
      status: String(formData.get("scheduleAt") || "") ? "draft" : "published",
      pinned: String(formData.get("pinned") || "false") === "true",
      highlighted: String(formData.get("highlighted") || "false") === "true",
      commentsMode: String(formData.get("commentsMode") || "open"),
      reactionsEnabled: String(formData.get("reactionsEnabled") || "true") === "true",
      scheduledAt: String(formData.get("scheduleAt") || ""),
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    });
    const community = communityById(state, communityId);
    if (community) community.updatedAt = new Date().toISOString();
    saveState(state);
    wellnessUi.adminCommunityId = communityId;
    wellnessUi.communityView = "admin";
    notify("Publicacion creada.");
    renderPatientWellness();
  }

  function updateCommunityMemberRole(memberId, role) {
    const state = loadState();
    const member = state.communityMembers.find((item) => item.id === memberId);
    if (!member || member.role === "owner" || !canManageCommunity(state, member.communityId)) return;
    member.role = ["admin", "moderator", "creator"].includes(role) ? role : "member";
    member.updatedAt = new Date().toISOString();
    saveState(state);
    notify("Rol de miembro actualizado.");
    renderPatientWellness();
  }

  function updateCommunityEventStatus(eventId, status) {
    const state = loadState();
    const eventItem = eventById(state, eventId);
    if (!eventItem || !canManageCommunity(state, eventItem.communityId)) return;
    eventItem.status = ["published", "draft", "cancelled"].includes(status) ? status : "draft";
    eventItem.updatedAt = new Date().toISOString();
    saveState(state);
    notify("Estado del evento actualizado.");
    renderPatientWellness();
  }

  function updateCommunityPostStatus(postId, status) {
    const state = loadState();
    const post = (state.communityPosts || []).find((item) => item.id === postId);
    if (!post || !canManageCommunity(state, post.communityId)) return;
    post.status = ["published", "draft", "hidden"].includes(status) ? status : "draft";
    post.updatedAt = new Date().toISOString();
    saveState(state);
    notify("Estado de publicacion actualizado.");
    renderPatientWellness();
  }

  function saveNewCommunity(formData) {
    const state = loadState();
    const admin = currentCommunityAdmin();
    const name = String(formData.get("name") || "Comunidad Wellness").trim();
    const category = String(formData.get("category") || "Habitos saludables");
    const communityId = wellnessId("community");
    const community = {
      id: communityId,
      ownerId: admin.id,
      name,
      slug: name.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "") || communityId,
      shortDescription: String(formData.get("shortDescription") || ""),
      description: String(formData.get("description") || ""),
      category,
      coverTone: category.toLowerCase().includes("nutric") ? "gold" : category.toLowerCase().includes("mental") ? "purple" : "mint",
      logoImage: "",
      logoInitials: initialsText(name).slice(0, 3),
      backgroundImage: "",
      accessType: String(formData.get("accessType") || "open"),
      visibility: String(formData.get("visibility") || "public"),
      city: String(formData.get("city") || "Ciudad de Mexico"),
      region: String(formData.get("city") || "Ciudad de Mexico"),
      language: String(formData.get("language") || "Espanol"),
      memberCount: 1,
      status: "active",
      rules: String(formData.get("rules") || ""),
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };
    state.communities.unshift(community);
    state.communityMembers.push({
      id: wellnessId("member"),
      communityId,
      userId: admin.id,
      role: "owner",
      status: "active",
      joinedAt: new Date().toISOString()
    });
    saveState(state);
    closePatientWellnessModal(false);
    wellnessUi.section = "communities";
    wellnessUi.communityView = "admin";
    wellnessUi.adminCommunityId = communityId;
    notify("Comunidad creada.");
    renderPatientWellness();
  }

  function saveNewCommunityEvent(formData) {
    const state = loadState();
    const admin = currentCommunityAdmin();
    const communityId = String(formData.get("communityId") || "");
    if (!communityId || !canManageCommunity(state, communityId)) {
      notify("Selecciona una comunidad que puedas administrar.");
      return;
    }
    const community = communityById(state, communityId);
    const date = String(formData.get("date") || dateOnly());
    const startTime = String(formData.get("startTime") || "18:00");
    const endTime = String(formData.get("endTime") || "19:00");
    const modality = String(formData.get("modality") || "in_person");
    const location = String(formData.get("location") || "");
    const zoomUrl = String(formData.get("zoomUrl") || "");
    const meetUrl = String(formData.get("meetUrl") || "");
    const teamsUrl = String(formData.get("teamsUrl") || "");
    const remoteUrl = zoomUrl || meetUrl || teamsUrl || (modality !== "in_person" ? location : "");
    const eventItem = {
      id: wellnessId("event"),
      communityId,
      creatorId: admin.id,
      title: String(formData.get("title") || "Evento Wellness"),
      imageUrl: String(formData.get("imageUrl") || ""),
      shortDescription: String(formData.get("shortDescription") || ""),
      description: String(formData.get("description") || ""),
      category: String(formData.get("category") || community?.category || "Wellbeing"),
      coverTone: community?.coverTone || "mint",
      modality,
      locationName: modality === "remote" ? "Sala virtual Klini" : location,
      address: modality === "remote" ? "" : location,
      mapsUrl: String(formData.get("mapsUrl") || ""),
      zoomUrl,
      meetUrl,
      teamsUrl,
      remoteUrl,
      startAt: new Date(`${date}T${startTime}:00`).toISOString(),
      endAt: new Date(`${date}T${endTime}:00`).toISOString(),
      timezone: String(formData.get("timezone") || "America/Mexico_City"),
      capacity: Number(formData.get("capacity") || 20),
      allowWaitlist: String(formData.get("allowWaitlist") || "true") === "true",
      cost: Number(formData.get("cost") || 0),
      reminders: String(formData.get("reminders") || "24h,1h,15m").split(","),
      bookingDeadline: new Date(`${date}T${startTime}:00`).toISOString(),
      status: "published",
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };
    state.communityEvents.unshift(eventItem);
    const inviteMode = String(formData.get("inviteMode") || "all");
    state.communityInvitations.push({
      id: wellnessId("invite"),
      eventId: eventItem.id,
      userId: inviteMode === "all" ? "all-members" : inviteMode,
      status: "sent",
      sentAt: new Date().toISOString()
    });
    saveState(state);
    closePatientWellnessModal(false);
    wellnessUi.section = "communities";
    wellnessUi.communityView = "admin";
    wellnessUi.adminCommunityId = communityId;
    notify("Evento publicado e invitaciones registradas.");
    renderPatientWellness();
  }

  function closePatientWellnessModal(shouldRender = true) {
    wellnessUi.modal = "";
    wellnessUi.detailMetric = "";
    wellnessUi.goalId = "";
    wellnessUi.communityId = "";
    wellnessUi.eventId = "";
    wellnessUi.shareContext = null;
    wellnessUi.notificationsOpen = false;
    wellnessUi.messagesOpen = false;
    wellnessUi.activeAdminMessageId = "";
    wellnessUi.activeAdminRequestId = "";
    wellnessUi.adminProfileRequestId = "";
    if (shouldRender) renderPatientWellness();
  }

  function openPatientWellnessSection(section = "today") {
    const normalizedSection = section === "goals" ? "wellbeing" : section;
    const nextSection = sections.some((item) => item.id === normalizedSection) ? normalizedSection : "today";
    wellnessUi.section = nextSection;
    if (nextSection === "communities") wellnessUi.communityView = "landing";
    localStorage.setItem(WELLNESS_SECTION_KEY, wellnessUi.section);
    closePatientWellnessModal(false);
    renderPatientWellness();
    globalThis.updatePatientGlobalBottomNav?.(nextSection);
  }

  function openCommunityAdminPanel(communityId = "") {
    wellnessUi.section = "communities";
    wellnessUi.communityView = "admin";
    wellnessUi.adminModule = "dashboard";
    if (communityId) wellnessUi.adminCommunityId = communityId;
    localStorage.setItem(WELLNESS_SECTION_KEY, wellnessUi.section);
    closePatientWellnessModal(false);
    renderPatientWellness();
    globalThis.updatePatientGlobalBottomNav?.("communities");
  }

  function openPatientWellnessQuickEntry(metricType = "") {
    wellnessUi.section = "today";
    localStorage.setItem(WELLNESS_SECTION_KEY, wellnessUi.section);
    wellnessUi.quickMetric = metricType || wellnessUi.quickMetric || "water_intake";
    wellnessUi.modal = "quick";
    renderPatientWellness();
    globalThis.updatePatientGlobalBottomNav?.("today");
  }

  function clonePublicData(value) {
    return JSON.parse(JSON.stringify(value ?? null));
  }

  function publicCommunityPayload(communityId = COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID) {
    const state = loadState();
    const requestedId = String(communityId || COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID);
    const community = communityById(state, requestedId)
      || state.communities.find((item) => item.slug === requestedId)
      || communityById(state, COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID)
      || state.communities.find((item) => item.visibility !== "hidden" && item.status !== "archived")
      || state.communities[0]
      || null;
    if (!community) return null;

    const activeMembers = state.communityMembers
      .filter((member) => member.communityId === community.id && member.status === "active")
      .map((member, index) => ({
        ...member,
        displayName: memberDisplayName(state, member),
        initials: initialsText(memberDisplayName(state, member)),
        participation: Math.min(98, 54 + (index * 7))
      }));
    const events = state.communityEvents
      .filter((event) => event.communityId === community.id && event.status === "published")
      .sort((a, b) => new Date(a.startAt) - new Date(b.startAt))
      .map((event) => ({
        ...event,
        seats: eventSeats(state, event),
        reservation: eventReservationForUser(state, event.id),
        booking: bookingStatus(state, event)
      }));
    const posts = communityPublishedPosts(state, community.id).map((post, index) => ({
      ...post,
      reactions: Number(post.reactions || (post.pinned ? 24 : 12 + index * 3)),
      comments: Number(post.comments || (post.pinned ? 6 : 2 + index))
    }));
    const pendingRequests = state.communityJoinRequests.filter((request) => request.communityId === community.id && request.status === "pending");
    const relatedCommunities = state.communities
      .filter((item) => item.id !== community.id && item.visibility !== "hidden" && item.status !== "archived")
      .slice(0, 3);

    return clonePublicData({
      patient: {
        id: state.patientId,
        name: state.patientName,
        initials: initialsText(state.patientName),
        walletCredits: Number(state.walletCredits || 0)
      },
      admin: currentCommunityAdmin(),
      community,
      activeMembers,
      events,
      posts,
      pendingRequests,
      relatedCommunities,
      walletCredits: Number(state.walletCredits || 0),
      membership: membershipFor(state, community.id),
      request: requestForCurrentUser(state, community.id),
      canManage: canManageCommunity(state, community.id),
      generatedAt: new Date().toISOString()
    });
  }

  function publicCommunityAction(action, payload = {}) {
    const state = loadState();
    const communityId = String(payload.communityId || COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID);
    const community = communityById(state, communityId);
    let message = "";
    if (!community) return { message: "No encontramos esta comunidad.", payload: publicCommunityPayload(communityId) };

    if (action === "join") {
      if (!membershipFor(state, communityId)) {
        if (community.accessType === "open") {
          state.communityMembers.push({
            id: wellnessId("member"),
            communityId,
            userId: state.patientId,
            role: "member",
            status: "active",
            joinedAt: new Date().toISOString()
          });
          community.memberCount = Number(community.memberCount || 0) + 1;
          community.updatedAt = new Date().toISOString();
          message = "Te uniste a la comunidad.";
        } else if (!requestForCurrentUser(state, communityId)) {
          state.communityJoinRequests.push({
            id: wellnessId("request"),
            communityId,
            userId: state.patientId,
            userName: state.patientName,
            userInitials: initialsText(state.patientName),
            brief: "Solicitud enviada desde la vista publica.",
            sharedInterests: ["Bienestar", community.category || "Comunidad"],
            status: "pending",
            requestedAt: new Date().toISOString()
          });
          message = "Solicitud enviada al administrador.";
        } else {
          message = "Tu solicitud ya esta pendiente.";
        }
      } else {
        message = "Ya perteneces a esta comunidad.";
      }
    }

    if (action === "leave") {
      const membership = membershipFor(state, communityId);
      if (membership && membership.role !== "owner") {
        membership.status = "removed";
        membership.updatedAt = new Date().toISOString();
        community.memberCount = Math.max(0, Number(community.memberCount || 0) - 1);
        community.updatedAt = new Date().toISOString();
        message = "Saliste de la comunidad.";
      } else {
        message = membership?.role === "owner" ? "El propietario no puede salir desde la vista publica." : "Aun no perteneces a esta comunidad.";
      }
    }

    if (action === "reserve") {
      const eventItem = eventById(state, String(payload.eventId || ""));
      const seats = eventItem ? eventSeats(state, eventItem) : { available: 0 };
      const cost = Math.max(0, Number(eventItem?.cost || eventItem?.price || eventItem?.creditCost || 0));
      const wallet = Math.max(0, Number(state.walletCredits || 0));
      if (eventItem && eventItem.communityId === communityId && !eventReservationForUser(state, eventItem.id)) {
        if (cost > wallet) {
          message = "No tienes suficientes creditos para reservar este evento.";
        } else {
          const status = seats.available > 0 ? "confirmed" : eventItem.allowWaitlist ? "waitlisted" : "cancelled";
          state.eventReservations.push({
            id: wellnessId("reservation"),
            eventId: eventItem.id,
            userId: state.patientId,
            status,
            reservedAt: new Date().toISOString()
          });
          if (status === "confirmed" && cost > 0) state.walletCredits = wallet - cost;
          eventItem.updatedAt = new Date().toISOString();
          message = status === "confirmed"
            ? cost > 0 ? `Lugar reservado. Se descontaron ${cost} creditos.` : "Lugar reservado."
            : status === "waitlisted" ? "Te agregamos a la lista de espera." : "El evento esta lleno.";
        }
      } else {
        message = eventItem ? "Ya tienes una reserva para este evento." : "No encontramos este evento.";
      }
    }

    if (action === "cancel-reservation") {
      const reservation = eventReservationForUser(state, String(payload.eventId || ""));
      if (reservation) {
        reservation.status = "cancelled";
        reservation.updatedAt = new Date().toISOString();
        message = "Reserva cancelada.";
      } else {
        message = "No encontramos una reserva activa.";
      }
    }

    if (action === "create-post") {
      const membership = membershipFor(state, communityId);
      const body = String(payload.body || "").trim();
      if (!membership && !canManageCommunity(state, communityId)) {
        message = "Unete a la comunidad para publicar.";
      } else if (community.allowMemberPosts === false && !canManageCommunity(state, communityId)) {
        message = "Solo administradores pueden publicar por ahora.";
      } else if (body) {
        state.communityPosts = Array.isArray(state.communityPosts) ? state.communityPosts : [];
        state.communityPosts.unshift({
          id: wellnessId("post"),
          communityId,
          authorId: state.patientId,
          authorName: state.patientName || "Paciente",
          title: String(payload.title || body.split(".")[0] || "Publicacion de comunidad").trim().slice(0, 80),
          body,
          status: "published",
          pinned: false,
          createdAt: new Date().toISOString(),
          updatedAt: new Date().toISOString()
        });
        community.updatedAt = new Date().toISOString();
        message = "Publicacion compartida.";
      } else {
        message = "Escribe algo para compartir.";
      }
    }

    saveState(state);
    return { message, payload: publicCommunityPayload(communityId) };
  }

  function shareWithDevice(state, network) {
    const title = shareTitle(state);
    const text = `${title}. Avance Wellness en Klini Dr Sam.`;
    if (navigator.share) {
      navigator.share({ title, text }).catch(() => notify("Comparte cuando estes listo."));
    } else {
      navigator.clipboard?.writeText(text);
      notify(`Texto listo para ${network}.`);
    }
  }

  function scrollWellnessShareChannels(direction) {
    const track = document.querySelector("#wellnessView [data-wellness-share-pan]");
    if (!track) return;
    const distance = Math.max(130, Math.round(track.clientWidth * 0.75));
    track.scrollBy({ left: direction === "left" ? -distance : distance, behavior: "smooth" });
  }

  function downloadShareCard(state) {
    const title = shareTitle(state);
    const canvas = document.createElement("canvas");
    canvas.width = 1080;
    canvas.height = 1920;
    const ctx = canvas.getContext("2d");
    const gradient = ctx.createLinearGradient(0, 0, 1080, 1920);
    gradient.addColorStop(0, "#e8fff9");
    gradient.addColorStop(1, "#ffffff");
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, 1080, 1920);
    ctx.fillStyle = "#13bba9";
    ctx.beginPath();
    ctx.arc(540, 480, 170, 0, Math.PI * 2);
    ctx.fill();
    ctx.fillStyle = "#ffffff";
    ctx.font = "bold 110px Arial";
    ctx.textAlign = "center";
    ctx.fillText("Klini", 540, 500);
    ctx.fillStyle = "#0c1d3a";
    ctx.font = "bold 78px Arial";
    wrapCanvasText(ctx, title, 540, 760, 860, 92);
    ctx.font = "42px Arial";
    ctx.fillStyle = "#52677f";
    wrapCanvasText(ctx, "Avance personal de bienestar en Klini Dr Sam.", 540, 1050, 820, 58);
    ctx.fillStyle = "#0aa595";
    ctx.font = "bold 52px Arial";
    ctx.fillText("Klini Dr Sam", 540, 1640);
    const link = document.createElement("a");
    link.download = "klini-wellness-logro.png";
    link.href = canvas.toDataURL("image/png");
    link.click();
    notify("Imagen descargada.");
  }

  function wrapCanvasText(ctx, text, x, y, maxWidth, lineHeight) {
    const words = String(text).split(" ");
    let line = "";
    words.forEach((word) => {
      const testLine = `${line}${word} `;
      if (ctx.measureText(testLine).width > maxWidth && line) {
        ctx.fillText(line.trim(), x, y);
        line = `${word} `;
        y += lineHeight;
      } else {
        line = testLine;
      }
    });
    ctx.fillText(line.trim(), x, y);
  }

  document.addEventListener?.("click", (event) => {
    if (!wellnessUi.messagesOpen && !wellnessUi.notificationsOpen) return;
    const wellnessView = document.getElementById("wellnessView");
    const path = event.composedPath?.() || [];
    if (path.includes(wellnessView) || wellnessView?.contains?.(event.target)) return;
    wellnessUi.messagesOpen = false;
    wellnessUi.notificationsOpen = false;
    wellnessUi.activeAdminMessageId = "";
    renderPatientWellness();
  });

  window.renderPatientWellness = renderPatientWellness;
  window.handlePatientWellnessAction = handlePatientWellnessAction;
  window.handlePatientWellnessSubmit = handlePatientWellnessSubmit;
  window.handlePatientWellnessChange = handlePatientWellnessChange;
  window.handlePatientWellnessInput = handlePatientWellnessInput;
  window.closePatientWellnessModal = closePatientWellnessModal;
  window.openPatientWellnessSection = openPatientWellnessSection;
  window.openPatientWellnessQuickEntry = openPatientWellnessQuickEntry;
  window.openCommunityAdminPanel = openCommunityAdminPanel;
  window.KliniWellnessCommunity = {
    defaultCommunityId: COMMUNITY_ADMIN_DEFAULT_COMMUNITY_ID,
    getPublicPayload: publicCommunityPayload,
    performPublicAction: publicCommunityAction
  };
})();
