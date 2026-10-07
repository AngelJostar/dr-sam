@php
  $sessionUser = auth()->user();
  $sessionModules = $sessionUser ? app(\App\Services\Platform\DashboardRegistry::class)->modulesFor($sessionUser) : collect();
@endphp
<details class="klini-session-menu">
  <summary aria-label="Opciones de sesión y módulos">
    <span class="klini-session-avatar" aria-hidden="true">{{ mb_substr($sessionUser?->name ?? 'K', 0, 1) }}</span>
    <span>Cambiar módulo<small class="klini-session-user">{{ $sessionUser?->name ?? 'Mi sesión' }}</small></span><span aria-hidden="true">⌄</span>
  </summary>
  <div class="klini-session-dropdown">
    <div class="klini-session-heading">Cambiar de módulo<small>Accesos autorizados para tu sesión</small></div>
    <nav aria-label="Módulos autorizados">
      @foreach($sessionModules as $sessionModule)
        <a href="{{ $sessionModule['url'] }}" @if(url()->current() === $sessionModule['url']) aria-current="page" @endif><x-klini-icon name="{{ $sessionModule['key'] }}" /><span>{{ $sessionModule['label'] }}</span><span aria-hidden="true">↗</span></a>
      @endforeach
    </nav>
    <form method="post" action="{{ route('logout') }}">
      @csrf
      <button type="submit">Cerrar sesión <span aria-hidden="true">→</span></button>
    </form>
  </div>
</details>
<form class="klini-session-logout" method="post" action="{{ route('logout') }}">
  @csrf
  <button type="submit">Cerrar sesión <span aria-hidden="true">→</span></button>
</form>
