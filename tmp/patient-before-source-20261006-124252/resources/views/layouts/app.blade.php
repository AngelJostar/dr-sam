<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? (str_contains($title, 'Klini') ? $title : $title.' | Klini') : 'Klini' }}</title>
    <meta name="theme-color" content="#082441">
    <link rel="icon" type="image/svg+xml" href="{{ asset('brand/klini/favicon.svg') }}">
    <link rel="preload" href="{{ asset('brand/klini/dejavu.woff') }}" as="font" type="font/woff" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/drsam.css') }}?v={{ filemtime(public_path('css/drsam.css')) }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/klini-brand.css') }}?v={{ filemtime(public_path('css/klini-brand.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/klini-workspace.css') }}?v={{ filemtime(public_path('css/klini-workspace.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/klini-system.css') }}?v={{ filemtime(public_path('css/klini-system.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/klini-carousels.css') }}?v={{ filemtime(public_path('css/klini-carousels.css')) }}">
  </head>
  <body class="native-shell @yield('body_class')">
    <main class="shell">
      @yield('content')
    </main>
    @stack('scripts')
    <script src="{{ asset('js/drsam-table-filters.js') }}?v={{ filemtime(public_path('js/drsam-table-filters.js')) }}" defer></script>
    <script src="{{ asset('js/klini-carousels.js') }}?v={{ filemtime(public_path('js/klini-carousels.js')) }}" defer></script>
  </body>
</html>
