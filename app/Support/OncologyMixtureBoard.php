<?php

namespace App\Support;

use App\Models\ProviderRequest;

final class OncologyMixtureBoard
{
    public const CLOSED = ['delivered', 'completed', 'cancelled', 'rejected'];

    public static function category(ProviderRequest $record): string
    {
        return match ($record->status) {
            'dispensed', 'preparing', 'ready' => 'preparing',
            'in_route' => 'route',
            'delivered', 'completed' => 'delivered',
            'cancelled', 'rejected' => 'history',
            default => 'pending',
        };
    }

    public static function statusLabel(ProviderRequest $record): string
    {
        return match ($record->status) {
            'draft' => 'Borrador',
            'accepted', 'authorized', 'approved' => 'Aceptada',
            'dispensed' => 'Dispensada',
            'preparing' => 'En preparación',
            'ready' => 'Inspeccionada',
            'in_route' => 'En ruta',
            'delivered', 'completed' => 'Entregada',
            'cancelled' => 'Cancelada',
            'rejected' => 'Rechazada',
            'materialization_failed' => 'Error de integración',
            default => 'Pendiente',
        };
    }

    public static function approval(ProviderRequest $record): array
    {
        $explicit = strtolower((string) (data_get($record->payload, 'approval_status') ?? data_get($record->payload, 'approval.status')));
        $authorizations = collect(data_get($record->payload, 'authorizations', []))->filter();
        if (in_array($explicit, ['rejected', 'denied', 'rechazada'], true)
            || $authorizations->contains('rejected') || in_array($record->status, ['rejected', 'cancelled'], true)) {
            return ['Rechazada', 'history'];
        }
        if (in_array($explicit, ['approved', 'authorized', 'aprobada'], true)
            || MixtureAuthorizationPolicy::allApproved($record->payload ?? [], 'chemo')) {
            return ['Aprobada', 'delivered'];
        }

        return ['Pendiente', 'pending'];
    }
}
