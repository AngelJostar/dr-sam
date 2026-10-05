@extends('layouts.app', ['title' => 'Klini / Dr. Sam - Iniciar sesión'])

@section('body_class', 'formal-login-body')

@section('content')
  <div class="formal-login-screen">
    <header class="formal-login-header">
      <a class="formal-login-brand" href="{{ route('login') }}">
        <strong>Klini / Dr. Sam</strong>
        <small>Plataforma de salud</small>
      </a>
      <div class="formal-login-profile">
        <span class="formal-login-avatar">DS</span>
        <div><strong>Bienvenido</strong><small>Acceso seguro</small></div>
      </div>
    </header>
    <main class="formal-login-stage">
      <section class="formal-login-welcome" aria-labelledby="login-title">
        <p class="eyebrow">Ingreso a la plataforma</p>
        <h1 id="login-title">Bienvenido a Klini</h1>
        <p>Ingresa con tu usuario y contraseña para acceder a tu espacio de trabajo en Dr. Sam.</p>
      </section>
    </main>
    <section class="formal-login-composer" aria-labelledby="access-panel-title">
      <div class="formal-login-composer-content">
        <div class="formal-login-composer-title">
          <h2 id="access-panel-title">Iniciar sesión</h2>
          <span>Accede al panel autorizado para tu cuenta.</span>
        </div>
        @if (request()->boolean('session_expired'))
          <div class="formal-login-session-notice" role="status">Tu sesión venció. Ingresa nuevamente.</div>
        @endif
        <form method="post" action="{{ route('login.store') }}" class="formal-login-form">
          @csrf
          <label>
            <span>Usuario</span>
            <input name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus>
          </label>
          <label>
            <span>Contraseña</span>
            <input name="password" type="password" autocomplete="current-password" required>
          </label>
          <button class="formal-login-submit" type="submit">Ingresar</button>
          @if ($errors->any())
            <p class="formal-login-error" role="alert">{{ $errors->first() }}</p>
          @endif
        </form>
      </div>
    </section>
  </div>
@endsection
