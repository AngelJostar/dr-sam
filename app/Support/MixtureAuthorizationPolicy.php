<?php

namespace App\Support;

final class MixtureAuthorizationPolicy
{
    /** @return list<string> */
    public static function requirements(string $requestType): array
    {
        return $requestType === 'chemo'
            ? ['oncology', 'pharmacy']
            : ['nursing', 'pharmacy'];
    }

    public static function normalize(array $authorizations, string $requestType): array
    {
        if ($requestType === 'chemo') {
            $authorizations['oncology'] ??= 'pending';
        } else {
            // Compatibilidad con solicitudes anteriores que llamaban "operational" a Enfermeria.
            $authorizations['nursing'] ??= $authorizations['operational'] ?? 'pending';
        }

        $authorizations['pharmacy'] ??= 'pending';

        return $authorizations;
    }

    public static function status(array $authorizations, string $area, string $requestType): string
    {
        if ($area === 'nursing' && $requestType !== 'chemo') {
            return $authorizations['nursing'] ?? $authorizations['operational'] ?? 'pending';
        }

        return $authorizations[$area] ?? 'pending';
    }

    public static function allApproved(array $payload, string $requestType): bool
    {
        $authorizations = self::normalize($payload['authorizations'] ?? [], $requestType);

        return collect(self::requirements($requestType))
            ->every(fn (string $area): bool => self::status($authorizations, $area, $requestType) === 'approved');
    }

    public static function cancellationLockedByCbta(?string $remoteStatus): bool
    {
        return in_array($remoteStatus, ['authorized', 'dispensed', 'preparing', 'ready', 'in_route', 'delivered'], true);
    }
}
