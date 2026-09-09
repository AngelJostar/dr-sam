<?php

namespace App\Support;

final class MixtureIntegrationStatus
{
    public const LABELS = [
        'pending' => 'Pendiente',
        'received' => 'Pendiente',
        'materialized' => 'Pendiente',
        'requested' => 'Pendiente',
        'authorized' => 'Aprobada',
        'accepted' => 'Aprobada',
        'approved' => 'Aprobada',
        'dispensed' => 'Dispensada',
        'preparing' => 'Preparada',
        'ready' => 'Inspeccionada',
        'in_route' => 'En ruta',
        'delivered' => 'Entregada',
        'rejected' => 'No aprobada',
        'cancelled' => 'Cancelada',
        'materialization_failed' => 'Error de integración',
    ];

    public static function label(?string $status): string
    {
        return self::LABELS[$status ?? ''] ?? 'Pendiente';
    }
}
