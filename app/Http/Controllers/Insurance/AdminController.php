<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function users(Request $request, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'admin_users');

        return view('insurance.admin.users', [
            'users' => User::query()
                ->whereIn('role', [
                    'insurance_admin',
                    'medical_auditor',
                    'patient_coordinator',
                    'delivery_coordinator',
                    'hospital_coordinator',
                    'billing',
                    'read_only',
                    'insurance_advisor',
                ])
                ->orderBy('role')
                ->orderBy('name')
                ->get(),
            'roles' => Role::query()->where('module', 'insurance_health')->with('permissions')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('module', 'insurance_health')->orderBy('name')->get(),
        ]);
    }
}
