<?php

namespace App\Services\Platform;

use Illuminate\Validation\ValidationException;

class DomainStateTransitionService
{
    public function assertAllowed(string $workflow, ?string $current, string $next): void
    {
        $transitions = config("drsam_workflows.{$workflow}", []);
        $allowed = $transitions[$current ?? ''] ?? [];

        if (! in_array($next, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "No se permite cambiar de {$current} a {$next}.",
            ]);
        }
    }
}
