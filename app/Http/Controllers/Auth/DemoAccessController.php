<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Http\JsonResponse;
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
            return redirect()->route('dashboard');
        }

        $users = User::query()
            ->where('passwordless_review', true)
            ->select(['name', 'username', 'role', 'module'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        if ($users->isEmpty()) {
            $users = $registry->allDemoUsers();
        }

        return view('auth.demo-login', [
            'users' => $users,
            'passwordless' => config('drsam.review_passwordless'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => [config('drsam.review_passwordless') ? 'nullable' : 'required', 'nullable', 'string'],
        ]);

        $user = User::query()
            ->where(fn ($query) => $query->where('username', $validated['username'])->orWhere('email', $validated['username']))
            ->where('status', 'active')
            ->first();

        if (! $user) {
            return back()
                ->withErrors(['username' => 'El usuario no existe o esta inactivo.'])
                ->withInput($request->only('username'));
        }

        if (! (config('drsam.review_passwordless') && $user->passwordless_review)) {
            $password = (string) ($validated['password'] ?? '');

            if (! $user->password || ! Hash::check($password, $user->password)) {
                return back()
                    ->withErrors(['password' => 'Usuario o contrasena incorrectos.'])
                    ->withInput($request->only('username'));
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        if ($user->role === 'superadmin') {
            $request->session()->forget('url.intended');

            return redirect()->route('superadmin.dashboard');
        }

        if ($user->account_category === User::CATEGORY_KLINI && in_array($user->role, ['patient', 'doctor'], true)) {
            $request->session()->forget('url.intended');

            return redirect()->route($user->role.'.dashboard');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('home')]);
        }

        return redirect()->route('home');
    }
}

