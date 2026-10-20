<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterKliniRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KliniRegistrationController extends Controller
{
    public function store(RegisterKliniRequest $request, PlatformAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $user = DB::transaction(function () use ($data, $request, $audit): User {
            $role = $data['registration_type'];
            $user = User::query()->create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'username' => $data['username'], 'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $role, 'module' => $role,
                'status' => $role === 'doctor' ? 'inactive' : 'active',
                'account_category' => User::CATEGORY_KLINI,
                'is_demo' => false, 'passwordless_review' => false,
                'metadata' => ['created_from' => 'klini_home_registration'],
            ]);
            $user->assignKliniMemberId();

            if ($role === 'patient') {
                Patient::query()->create([
                    'user_id' => $user->id, 'full_name' => $user->name,
                    'first_name' => $data['first_name'], 'last_name' => $data['last_name'],
                    'email' => $user->email, 'status' => 'active',
                    'metadata' => ['created_from' => 'klini_home_registration'],
                ]);
            } else {
                Doctor::query()->create([
                    'user_id' => $user->id, 'full_name' => $user->name,
                    'professional_license' => $data['professional_license'], 'specialty' => $data['specialty'],
                    'status' => 'inactive',
                    'metadata' => ['created_from' => 'klini_home_registration'],
                ]);
            }

            $audit->record($request, 'klini.member.registered', $user, $role, [
                'account_category' => User::CATEGORY_KLINI, 'klini_member_id' => $user->klini_member_id,
            ]);

            return $user;
        });

        return redirect()->route('home')->with('klini_registration', [
            'member_id' => $user->klini_member_id, 'role' => $user->role,
        ]);
    }
}
