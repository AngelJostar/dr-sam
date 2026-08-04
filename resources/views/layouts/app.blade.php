<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dr. Sam' }}</title>
    <link rel="stylesheet" href="{{ asset('css/drsam.css') }}?v={{ filemtime(public_path('css/drsam.css')) }}">
    @stack('styles')
  </head>
  <body class="native-shell @yield('body_class')">
    <main class="shell">
      @yield('content')
    </main>
    @stack('scripts')
  </body>
</html>
