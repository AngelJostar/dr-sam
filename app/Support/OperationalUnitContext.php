<?php

namespace App\Support;

use App\Models\MedicalUnit;
use App\Models\OperationalProfile;
use Illuminate\Http\Request;

final class OperationalUnitContext
{
    public static function resolve(Request $request): ?MedicalUnit
    {
        $user = $request->user();
        $profile = OperationalProfile::query()->where('user_id', $user?->id)->first();
        if ($request->filled('unit')) {
            $unit = MedicalUnit::query()->with('institution')->findOrFail($request->integer('unit'));
            $allowed = in_array($user?->role, ['superadmin', 'admin'], true)
                || ($user?->role === 'unit' && $unit->unit_username === $user->username)
                || ($user?->role === 'institution' && $user->institution && (int) $unit->institution_id === (int) $user->institution->id)
                || ($user?->role === 'operational' && $profile?->status === 'active' && (int) $profile->medical_unit_id === (int) $unit->id);
            abort_unless($allowed, 403, 'No puedes consultar otra unidad.');
            return $unit;
        }
        if ($user?->role === 'operational') {
            abort_unless($profile?->status === 'active' && $profile->medical_unit_id, 403, 'El perfil operativo necesita una unidad activa asignada.');
            return $profile->medicalUnit;
        }
        return match ($user?->role) {
            'unit' => MedicalUnit::query()->where('unit_username', $user->username)->firstOrFail(),
            'institution' => $user->institution?->medicalUnits()->orderBy('name')->first(),
            'superadmin', 'admin' => MedicalUnit::query()->with('institution')->orderBy('id')->first(),
            default => null,
        };
    }
}
