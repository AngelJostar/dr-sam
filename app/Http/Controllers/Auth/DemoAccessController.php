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
            return redirect()->route('dashboard');
        }

        $users = User::query()
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
            ->where('username', $validated['username'])
            ->where('status', 'active')
            ->first();

        if (! $user) {
            return back()
                ->withErrors(['username' => 'El usuario no existe o esta inactivo.'])
                ->withInput();
        }

        if (! config('drsam.review_passwordless')) {
            $password = (string) ($validated['password'] ?? '');

            if (! $user->password || ! Hash::check($password, $user->password)) {
                return back()
                    ->withErrors(['password' => 'Usuario o contrasena incorrectos.'])
                    ->withInput();
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

