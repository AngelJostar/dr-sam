@php
$sessionUser = auth()->user();
$sessionModules = app(\App\Services\Platform\DashboardRegistry::class)->modulesFor($sessionUser)->filter(fn ($module) => $module['url'] !== '#' && !in_array($module['key'], ['doctor', 'patient'], true));
$currentModule = $sessionModules->first(fn ($module) => request()->routeIs(config('drsam.modules.'.$module['key'].'.route', '')));
$initials = mb_strtoupper(mb_substr($sessionUser->name, 0, 1));
@endphp
<div class="account-module-switcher" data-module-switcher>
<button type="button" class="account-avatar" data-module-toggle aria-expanded="false" aria-controls="account-module-menu" aria-label="Perfil de {{ $sessionUser->name }}. Cambiar módulo">{{ $initials }}</button>
<div id="account-module-menu" class="account-module-menu" hidden>
<header><span class="account-avatar-static">{{ $initials }}</span><div><strong>{{ $sessionUser->name }}</strong><small>{{ $sessionUser->email ?? $sessionUser->username }}</small></div></header>
<h3>Seleccionar módulo</h3>
@foreach ($sessionModules as $module)
<div class="account-module-row"><span>{{ $module['label'] }} @if ($currentModule && $currentModule['key'] === $module['key'])<small>Actual</small>@endif @if (data_get($sessionUser->metadata, 'home_module') === $module['key'])<small>★ Inicio</small>@endif</span><a href="{{ $module['url'] }}">Entrar</a></div>
@endforeach
@if ($sessionModules->isEmpty())<p>No hay módulos autorizados disponibles.</p>@endif
<button type="button" data-module-manage>Cambiar módulo / Definir inicio</button>
</div></div>
<dialog class="account-module-dialog" data-module-dialog aria-labelledby="module-dialog-title">
<header><div><h2 id="module-dialog-title">Cambiar módulo</h2><p>Selecciona el módulo con el que deseas trabajar.</p></div><button type="button" data-module-close aria-label="Cerrar">×</button></header>
<div class="account-module-grid">
@foreach ($sessionModules as $module)
<article><span class="account-module-icon" aria-hidden="true">▦</span><h3>{{ $module['label'] }}</h3><a href="{{ $module['url'] }}">Abrir módulo</a><button type="button" data-module-home="{{ $module['key'] }}" data-module-label="{{ $module['label'] }}">★ Establecer como inicio</button></article>
@endforeach
</div><footer><button type="button" data-module-close>Cancelar</button></footer>
</dialog>
<dialog class="account-module-confirm" data-module-confirm aria-labelledby="module-confirm-title">
<form method="post" action="{{ route('account.home-module') }}">@csrf
<h2 id="module-confirm-title">Definir módulo de inicio</h2><p>Cada vez que inicies sesión entrarás primero a <strong data-module-chosen></strong>.</p>
<input type="hidden" name="module" data-module-input><label><input type="checkbox" name="open_now" value="1" checked> Abrir también ahora</label><footer><button type="button" data-module-cancel>Cancelar</button><button type="submit">Confirmar</button></footer>
</form></dialog>
@if (session('module_saved'))<div class="account-module-toast" role="status">✓ Módulo de inicio guardado: {{ session('module_saved') }}</div>@endif
<script>
(() => {
 const root = document.querySelector('[data-module-switcher]'), toggle = root.querySelector('[data-module-toggle]'), menu = root.querySelector('.account-module-menu');
 const importAccount = document.querySelector('.import-account-actions');
 if (importAccount) {
  const identity = importAccount.querySelector(':scope > div');
  if (identity) { identity.replaceWith(root); root.classList.add('is-inline'); const name = document.createElement('span'); name.className = 'account-session-name'; name.textContent = @json($sessionUser->name); toggle.append(name); }
 }
 const dialog = document.querySelector('[data-module-dialog]'), confirmation = document.querySelector('[data-module-confirm]');
 const closeMenu = () => { menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); };
 toggle.addEventListener('click', () => { menu.hidden = !menu.hidden; toggle.setAttribute('aria-expanded', String(!menu.hidden)); });
 document.addEventListener('click', event => { if (!root.contains(event.target)) closeMenu(); });
 document.addEventListener('keydown', event => { if (event.key === 'Escape') closeMenu(); });
 root.querySelector('[data-module-manage]').addEventListener('click', () => { closeMenu(); dialog.showModal(); });
 dialog.querySelectorAll('[data-module-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
 dialog.querySelectorAll('[data-module-home]').forEach(button => button.addEventListener('click', () => { confirmation.querySelector('[data-module-input]').value = button.dataset.moduleHome; confirmation.querySelector('[data-module-chosen]').textContent = button.dataset.moduleLabel; confirmation.showModal(); }));
 confirmation.querySelector('[data-module-cancel]').addEventListener('click', () => confirmation.close());
})();
</script>
