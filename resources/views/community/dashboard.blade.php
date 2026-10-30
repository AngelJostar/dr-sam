<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $adminMode ? 'Administrador de Comunidades' : 'Comunidades' }} | Klini</title>
  <meta name="theme-color" content="{{ $adminMode ? '#f6f9fc' : '#edf8f3' }}">
  <link rel="icon" type="image/svg+xml" href="{{ asset('brand/klini/favicon.svg') }}">
  <link rel="stylesheet" href="{{ asset('vendor/klini-community/base.css') }}">
  <link rel="stylesheet" href="{{ asset('vendor/klini-community/wellness.css') }}">
  <link rel="stylesheet" href="{{ asset('css/klini-brand.css') }}">
  <link rel="stylesheet" href="{{ asset('css/klini-workspace.css') }}">
  <link rel="stylesheet" href="{{ asset('css/klini-community.css') }}?v={{ filemtime(public_path('css/klini-community.css')) }}">
  @if ($adminMode)
    <link rel="stylesheet" href="{{ asset('css/klini-community-admin.css') }}?v={{ filemtime(public_path('css/klini-community-admin.css')) }}">
  @endif
  <link rel="stylesheet" href="{{ asset('css/platform-density.css') }}?v={{ filemtime(public_path('css/platform-density.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/community-activities.css') }}?v={{ filemtime(public_path('css/community-activities.css')) }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="native-shell klini-community-body" data-patient-view="wellnessView" data-community-mode="{{ $adminMode ? 'admin' : 'explore' }}" data-community-storage="{{ auth()->id() }}" data-community-name="{{ auth()->user()->name }}" data-community-admin-url="{{ route('community.dashboard') }}" data-community-explore-url="{{ route('community.explore') }}">
  <header class="klini-community-header">
    <a class="klini-community-brand" href="{{ route('dashboard') }}"><x-klini-brand label="Wellness & comunidad" /></a>
    <span class="klini-community-header-label">{{ $adminMode ? 'Administrador de Comunidades' : 'Tu comunidad. Tu bienestar.' }}</span>
    <div class="klini-community-session"><x-klini-session-menu /></div>
  </header>
  <main class="klini-community-shell">
    @unless ($adminMode)
      <div class="klini-community-intro">
        <div><p class="klini-community-eyebrow">KLINI WELLNESS</p><h1>El bienestar se vive en compañía.</h1><p>Encuentra personas, experiencias y pequeños hábitos que te hacen bien.</p></div>
        <a class="klini-community-back" href="{{ route('patient.dashboard') }}">← Volver a mi espacio</a>
      </div>
      <p class="klini-community-demo" role="note"><span aria-hidden="true">◌</span> Espacio de demostración · Datos ficticios. Los cambios se guardan en este navegador; los mensajes e invitaciones son simulados.</p>
    @endunless
    <section id="wellnessView" aria-label="{{ $adminMode ? 'Administración de comunidades' : 'Comunidades de bienestar' }}">
      <div class="wellness-dashboard-panel"><div id="patientWellnessRoot" class="wellness-root"></div></div>
    </section>
    <noscript>Activa JavaScript para consultar y administrar las comunidades.</noscript>
  </main>
  <div id="toast" class="klini-community-toast" role="status" aria-live="polite"></div>
  <script src="{{ asset('vendor/klini-community/wellness.js') }}?v={{ filemtime(public_path('vendor/klini-community/wellness.js')) }}" defer></script>
  @if ($adminMode)
    @php
      $previewStyles = collect(['css/patient-portal-source.css', 'css/community-activities.css', 'css/communities.css', 'css/patient-stories.css', 'css/patient-bottom-nav-mobile.css', 'css/patient-social.css', 'css/community-event-preview.css'])
          ->map(fn ($path) => asset($path).'?v='.filemtime(public_path($path)));
    @endphp
    <script type="application/json" id="communityEventPreviewAssets">@json($previewStyles)</script>
    <script src="{{ asset('js/communities.js') }}?v={{ filemtime(public_path('js/communities.js')) }}" defer></script>
    <script src="{{ asset('js/klini-community-composer.js') }}?v={{ filemtime(public_path('js/klini-community-composer.js')) }}" defer></script>
  @endif
  <script src="{{ asset('js/klini-community.js') }}?v={{ filemtime(public_path('js/klini-community.js')) }}" defer></script>
<script src="{{ asset('js/community-activities.js') }}?v={{ filemtime(public_path('js/community-activities.js')) }}"></script>
</body>
</html>
