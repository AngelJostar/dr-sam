<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\MobileDeviceRegistration;
use App\Models\PlatformModule;
use App\Models\User;
use App\Services\Platform\MobileAppConfiguration;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MobileAppController extends Controller
{
    public function index(Request $request, MobileAppConfiguration $configuration): View
    {
        $sections = [
            'overview' => 'Resumen', 'navigation' => 'Navegación', 'texts' => 'Botones y textos',
            'forms' => 'Formatos', 'users' => 'Usuarios', 'catalogs' => 'Catálogos',
            'communities' => 'Comunidades', 'subscriptions' => 'Billetera y planes',
            'advertising' => 'Publicidad', 'notifications' => 'Notificaciones',
            'health' => 'Salud y archivo', 'native' => 'Pantallas nativas',
        ];
        $section = $request->string('section', 'overview')->toString();
        abort_unless(array_key_exists($section, $sections), 404);
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $users = null;
        $devices = null;
        if ($section === 'users') {
            $users = User::query()->where('account_category', User::CATEGORY_KLINI)
                ->when($request->filled('q'), function ($query) use ($request) {
                    $term = '%'.$request->string('q')->toString().'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('username', 'like', $term)->orWhere('klini_member_id', 'like', $term));
                })->orderBy('name')->paginate(20)->withQueryString();
        }
        if ($section === 'notifications') {
            $devices = MobileDeviceRegistration::query()->select(['id', 'user_id', 'platform', 'device_name', 'enabled', 'last_seen_at'])
                ->with('user:id,name')->latest('last_seen_at')->paginate(20)->withQueryString();
        }

        return view('superadmin.mobile-app', [
            'sections' => $sections, 'section' => $section, 'configuration' => $configuration->read(),
            'users' => $users, 'devices' => $devices,
            'metrics' => [
                'Usuarios Klini' => User::query()->where('account_category', User::CATEGORY_KLINI)->count(),
                'Médicos activos' => Doctor::query()->where('status', 'active')->count(),
                'Dispositivos registrados' => MobileDeviceRegistration::query()->count(),
                'Textos configurables' => count(config('klini-mobile.texts')) + count(config('klini-mobile.forms')),
            ],
            'areas' => config('klini-mobile-areas'),
        ]);
    }

    public function update(Request $request, string $group, PlatformAuditService $audit): RedirectResponse
    {
        abort_unless(in_array($group, ['navigation', 'texts', 'forms'], true), 404);
        $definitions = config('klini-mobile.'.$group);
        $rules = ['revision' => ['required', 'integer', 'min:0'], 'values' => ['required', 'array:'.implode(',', array_keys($definitions))]];
        foreach ($definitions as $key => $field) {
            if ($group === 'navigation') {
                $rules['values.'.$key] = ['required', 'array:label,visible,order'];
                $rules['values.'.$key.'.label'] = ['required', 'string', 'max:40', 'not_regex:/[<>]/u'];
                $rules['values.'.$key.'.visible'] = ['required', 'boolean'];
                $rules['values.'.$key.'.order'] = ['required', 'integer', 'between:1,20'];
            } else {
                $rules['values.'.$key] = ['required', 'string', 'max:160', 'not_regex:/[<>]/u'];
            }
        }
        $data = $request->validate($rules);
        if ($group === 'navigation') {
            if (! $data['values']['home']['visible']) {
                throw ValidationException::withMessages(['values.home.visible' => 'El acceso a Inicio debe permanecer visible.']);
            }
            foreach ($data['values'] as &$item) {
                $item['visible'] = (bool) $item['visible'];
                $item['order'] = (int) $item['order'];
            }
            unset($item);
        }

        DB::transaction(function () use ($request, $group, $data, $audit): void {
            PlatformModule::query()->firstOrCreate(['key' => 'patient'], [
                'label' => 'Portal del paciente', 'target' => 'patient.dashboard',
                'enabled' => true, 'roles' => ['patient', 'superadmin', 'admin'], 'settings' => [],
            ]);
            $module = PlatformModule::query()->where('key', 'patient')->lockForUpdate()->firstOrFail();
            $settings = $module->settings ?? [];
            if ((int) ($settings['mobile_ui_revision'] ?? 0) !== (int) $data['revision']) {
                throw ValidationException::withMessages(['revision' => 'La configuración cambió en otra ventana. Actualiza esta página antes de guardar.']);
            }
            $settings['mobile_ui'][$group] = $data['values'];
            $settings['mobile_ui_revision'] = (int) $data['revision'] + 1;
            $settings['mobile_ui_updated_at'] = now()->toIso8601String();
            $module->update(['settings' => $settings]);
            $audit->record($request, 'superadmin.mobile.configuration.updated', $module, 'superadmin', ['group' => $group]);
        });

        return redirect()->route('superadmin.mobile-app', ['section' => $group])
            ->with('status', 'Cambios guardados. Se aplicarán al volver a abrir o recargar el portal de Klini móvil.');
    }
}
