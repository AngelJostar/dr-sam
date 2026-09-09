@extends('layouts.app', ['title' => 'Usuarios aseguradora'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._topbar')
    @include('insurance._sidebar', ['abilities' => ['admin_users' => true]])

    <main class="insurance-health-native-workspace">
      <section class="page-heading insurance-section-hero insurance-admin-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Administracion</p>
          <h1>Usuarios, roles y permisos</h1>
          <p>Catalogo de roles operativos del modulo aseguradora y permisos asignados.</p>
        </div>
      </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-admin-panel">
      <div class="section-title"><h2>Usuarios</h2><span>{{ $users->count() }}</span></div>
      <div class="compact-list">
        @foreach($users as $user)
          <div>
            <strong>{{ $user->name }}</strong>
            <span>{{ $user->username }} / {{ $user->role }} / {{ $user->status }}</span>
          </div>
        @endforeach
      </div>
    </article>

    <article class="insurance-panel insurance-admin-panel">
      <div class="section-title"><h2>Roles</h2><span>{{ $roles->count() }}</span></div>
      <div class="compact-list">
        @foreach($roles as $role)
          <div>
            <strong>{{ $role->name }}</strong>
            <span>{{ $role->permissions->pluck('key')->implode(', ') }}</span>
          </div>
        @endforeach
      </div>
    </article>
  </section>

  <section class="insurance-panel insurance-admin-panel insurance-permissions-panel">
    <div class="section-title"><h2>Permisos disponibles</h2><span>{{ $permissions->count() }}</span></div>
    <div class="permission-grid">
      @foreach($permissions as $permission)
        <span>{{ $permission->key }} - {{ $permission->name }}</span>
      @endforeach
    </div>
      </section>
    </main>
  </div>
@endsection
