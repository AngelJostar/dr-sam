<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class DemoAccessController extends Controller
{
    public function index(DashboardRegistry $registry): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->destination(Auth::user(), $registry, true);
        }

        return view('auth.login');
    }

    public function selector(): View
    {

        $users = User::query()
            ->select(['name', 'username', 'role', 'module', 'password', 'is_demo'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $demoPasswords = $users->mapWithKeys(fn (User $user) => [
            $user->username => $user->is_demo && $user->password && Hash::check('Demo2026', $user->password)
                ? 'Demo2026' : '',
        ]);

        return view('auth.demo-login', [
            'users' => $users,
            'demoPasswords' => $demoPasswords,
        ]);
    }

    public function store(Request $request, DashboardRegistry $registry): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', $validated['username'])
            ->where('status', 'active')
            ->first();

        if (! $user || ! $user->password || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['username' => 'Usuario o contraseña incorrectos.'])
                ->withInput($request->only('username'));
        }

        $destination = $this->destination($user, $registry, $request->routeIs('login.store'));

        Auth::login($user);
        $request->session()->regenerate();

        $request->session()->forget('url.intended');

        return $destination;
    }

    private function destination(User $user, DashboardRegistry $registry, bool $initial): RedirectResponse
    {
        $preferred = $registry->modulesFor($user)->firstWhere('key', data_get($user->metadata, 'home_module'));
        if ($preferred && $preferred['url'] !== '#' && !in_array($user->role, ['doctor', 'patient'], true)) {
            return redirect()->to($preferred['url']);
        }
        if ($user->role === 'superadmin') {
            return redirect()->route($initial ? 'demo-login.index' : 'superadmin.dashboard');
        }

        $aliases = [
            'digitalPharmacy' => 'digital_pharmacy',
            'externalPharmacy' => 'external_pharmacy',
            'insurance' => 'insurance_health',
            'insuranceAdvisor' => 'insurance_advisor',
            'provider' => 'provider_npt',
        ];
        $modules = $registry->modulesFor($user)->filter(fn (array $module) => $module['url'] !== '#');
        $module = $modules->firstWhere('key', $aliases[$user->module] ?? $user->module) ?? $modules->first();
        abort_unless($module, 403, 'No tienes un módulo autorizado disponible.');

        return redirect()->to($module['url']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

