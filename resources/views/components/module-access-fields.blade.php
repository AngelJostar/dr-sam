@if (!in_array($accessUser->role, ['superadmin', 'doctor', 'patient'], true))
<fieldset class="module-access-fields"><legend>Módulos autorizados</legend>
<input type="hidden" name="modules_submitted" value="1">
@foreach (config('drsam.modules') as $key => $module)
  @if (!in_array($key, ['superadmin', 'doctor', 'patient'], true))
  <label><input type="checkbox" name="assigned_modules[]" value="{{ $key }}" @checked(in_array($key, data_get($accessUser->metadata, 'assigned_modules', app(\App\Services\Platform\DashboardRegistry::class)->modulesFor($accessUser)->pluck('key')->all()), true))> {{ $module['label'] }}</label>
  @endif
@endforeach
</fieldset>
@endif
