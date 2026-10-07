@extends('layouts.app', ['title' => 'Klini - Acceso de usuarios'])

@section('body_class', 'formal-login-body')

@section('content')
  @php
    $moduleLabels = [
      'superadmin' => 'Superadministrador',
      'institution' => 'Institución',
      'unit' => 'Unidad',
      'operational' => 'Área operativa',
      'digitalPharmacy' => 'Farmacia digital',
      'externalPharmacy' => 'Farmacia externa',
      'doctor' => 'Médico',
      'patient' => 'Paciente',
      'insurance' => 'Seguros GMM',
      'insuranceAdvisor' => 'Asesor de seguros',
      'messenger' => 'Mensajero',
      'provider' => 'Proveedor',
    ];
    $groupedUsers = $users->groupBy(fn ($user) => $user['module'] ?? $user->module);
    $demoPassword = 'Demo2026';
  @endphp

  <div class="formal-login-screen">
    <header class="formal-login-header">
      <a class="formal-login-brand" href="{{ route('home') }}" aria-label="Klini inicio">
        <x-klini-brand label="Tu salud, en un solo lugar." />
      </a>

      <div class="formal-login-profile">
        <span class="formal-login-avatar">K</span>
        <div>
          <strong>Acceso de usuarios</strong>
          <small>Sesión segura</small>
        </div>
      </div>
    </header>

    <main class="formal-login-stage">
      <section class="formal-login-welcome" aria-labelledby="login-title">
        <p class="eyebrow">Ingreso unificado</p>
        <h1 id="login-title">¿A qué módulo quieres ingresar hoy?</h1>
        <p>Usa tus credenciales y Klini abrirá automáticamente el módulo autorizado: institución, unidad, operación, mensajero, proveedor, médico, paciente o seguros.</p>
        <div class="formal-login-modules" aria-label="Rutas disponibles">
          @foreach (['Institución', 'Unidad', 'Área operativa', 'Médico', 'Paciente', 'Seguros GMM', 'Mensajero', 'Proveedor'] as $module)
            <span>{{ $module }}</span>
          @endforeach
        </div>
      </section>
    </main>

    <section class="formal-login-composer" aria-labelledby="access-panel-title">
      <span class="formal-login-menu" aria-hidden="true">⋮</span>
      <div class="formal-login-composer-content">
        <div class="formal-login-composer-title">
          <h2 id="access-panel-title">Panel de ingreso</h2>
          <span>El usuario entra al módulo que tiene autorizado.</span>
        </div>

        @if (request()->boolean('session_expired'))
          <div class="formal-login-session-notice" role="status">
            La sesión anterior venció. Ya generamos un acceso nuevo; puedes ingresar nuevamente.
          </div>
        @endif

        <form method="post" action="{{ route('demo-login.store') }}" class="formal-login-form" id="demo-login-form">
          @csrf
          <label>
            <span>Usuario</span>
            <select name="username" id="demo-username" autocomplete="username" required>
              @foreach ($groupedUsers as $module => $moduleUsers)
                <optgroup label="Módulo {{ strtolower($moduleLabels[$module] ?? $module) }}">
                  @foreach ($moduleUsers as $user)
                    @php
                      $username = $user['username'] ?? $user->username;
                      $name = $user['name'] ?? $user->name;
                    @endphp
                    <option
                      value="{{ $username }}"
                      data-name="{{ $name }}"
                      data-module="{{ $moduleLabels[$module] ?? ucfirst($module) }}"
                      @selected(old('username') === $username)
                    >{{ $name }} ({{ $username }})</option>
                  @endforeach
                </optgroup>
              @endforeach
            </select>
          </label>

          <label>
            <span>Contraseña</span>
            <input name="password" id="demo-password" type="password" value="{{ $demoPassword }}" autocomplete="current-password" @required(!$passwordless)>
          </label>

          <button class="formal-login-submit" type="submit">Ingresar</button>

          <div class="formal-login-register" aria-label="Opciones de registro">
            <button type="button" title="Flujo disponible próximamente">Registrarme, soy paciente</button>
            <button type="button" title="Flujo disponible próximamente">Registrarme, soy médico</button>
          </div>

          @if ($errors->any())
            <p class="formal-login-error" role="alert">{{ $errors->first() }}</p>
          @endif
        </form>

      </div>
    </section>
  </div>

  <script>
    (() => {
      const select = document.querySelector('#demo-username');
      const password = document.querySelector('#demo-password');
      const syncCredentials = () => {
        const option = select?.selectedOptions[0];
        if (!option) return;
        password.value = @json($demoPassword);
      };

      select?.addEventListener('change', syncCredentials);
      syncCredentials();
    })();
  </script>
@endsection
