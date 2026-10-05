<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dr. Sam' }}</title>
    <link rel="stylesheet" href="{{ asset('css/drsam.css') }}?v={{ filemtime(public_path('css/drsam.css')) }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/buttons.css') }}?v={{ filemtime(public_path('css/buttons.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/module-switcher.css') }}?v={{ filemtime(public_path('css/module-switcher.css')) }}">
  </head>
  <body class="native-shell @yield('body_class')">
    <main class="shell">
      @yield('content')
    </main>
    @auth
      @if (!in_array(auth()->user()->role, ['doctor', 'patient'], true) && !request()->routeIs('doctor.*', 'patient.*', 'demo-login.*'))
        @include('components.module-switcher')
      @endif
    @endauth
    @stack('scripts')
    <script src="{{ asset('js/drsam-table-filters.js') }}?v={{ filemtime(public_path('js/drsam-table-filters.js')) }}" defer></script>
  </body>
</html>
