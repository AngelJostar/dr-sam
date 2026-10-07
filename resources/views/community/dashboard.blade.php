<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $adminMode ? 'Administrador de Comunidades' : 'Comunidades' }} | Klini</title>
  <meta name="theme-color" content="#edf8f3">
  <link rel="icon" type="image/svg+xml" href="{{ asset('brand/klini/favicon.svg') }}">
  <link rel="stylesheet" href="{{ asset('vendor/klini-community/base.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/klini-community/wellness.css') }}">
  <link rel="stylesheet" href="{{ asset('css/klini-brand.css') }}">
  <link rel="stylesheet" href="{{ asset('css/klini-workspace.css') }}">
  <link rel="stylesheet" href="{{ asset('css/klini-community.css') }}?v={{ filemtime(public_path('css/klini-community.css')) }}">
</head>
<body class="native-shell klini-community-body" data-patient-view="wellnessView" data-community-mode="{{ $adminMode ? 'admin' : 'explore' }}" data-community-storage="{{ auth()->id() }}" data-community-name="{{ auth()->user()->name }}" data-community-admin-url="{{ route('community.dashboard') }}" data-community-explore-url="{{ route('community.explore') }}">
  <header class="klini-community-header">
    <a class="klini-community-brand" href="{{ route('dashboard') }}"><x-klini-brand label="Wellness & comunidad" /></a>
    <span class="klini-community-header-label">{{ $adminMode ? 'Administrador de Comunidades' : 'Tu comunidad. Tu bienestar.' }}</span>
    <div class="klini-community-session"><x-klini-session-menu /></div>
  </header>
  <main class="klini-community-shell">
    <div class="klini-community-intro">
      <div><p class="klini-community-eyebrow">KLINI WELLNESS</p><h1>{{ $adminMode ? 'Conecta. Inspira. Haz comunidad.' : 'El bienestar se vive en compañía.' }}</h1><p>{{ $adminMode ? 'Un espacio para cuidar a tu comunidad y verla crecer, a su ritmo.' : 'Encuentra personas, experiencias y pequeños hábitos que te hacen bien.' }}</p></div>
      <a class="klini-community-back" href="{{ $adminMode ? (auth()->user()->role === 'superadmin' ? route('superadmin.dashboard') : route('dashboard')) : route('patient.dashboard') }}">{{ $adminMode && auth()->user()->role === 'superadmin' ? '← Superadministrador' : '← Volver a mi espacio' }}</a>
    </div>
    <p class="klini-community-demo" role="note"><span aria-hidden="true">◌</span> Espacio de demostración · Datos ficticios. Los cambios se guardan en este navegador; los mensajes e invitaciones son simulados.</p>
    <section id="wellnessView" aria-label="{{ $adminMode ? 'Administración de comunidades' : 'Comunidades de bienestar' }}">
      <div class="wellness-dashboard-panel"><div id="patientWellnessRoot" class="wellness-root"></div></div>
    </section>
    <noscript>Activa JavaScript para consultar y administrar las comunidades.</noscript>
  </main>
  <div id="toast" class="klini-community-toast" role="status" aria-live="polite"></div>
  <script src="{{ asset('vendor/klini-community/wellness.js') }}?v={{ filemtime(public_path('vendor/klini-community/wellness.js')) }}" defer></script>
  <script src="{{ asset('js/klini-community.js') }}?v={{ filemtime(public_path('js/klini-community.js')) }}" defer></script>
</body>
</html>
