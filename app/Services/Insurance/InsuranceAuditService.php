<?php

namespace App\Services\Insurance;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class InsuranceAuditService
{
    public function record(Request $request, string $event, Model $model, array $context = []): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'ip_address' => $request->ip(),
            'payload' => [
                'module' => 'insurance_health',
                'changed_fields' => array_keys($model->getChanges()),
                'context' => $context,
            ],
        ]);
    }
}
