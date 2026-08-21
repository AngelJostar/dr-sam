<?php

namespace App\Console\Commands;

use App\Models\MixtureIntegration;
use App\Services\Integrations\Cbta\MixtureIntegrationSyncService;
use Illuminate\Console\Command;

class SyncCbtaMixtureRequests extends Command
{
    protected $signature = 'cbta:sync-mixtures {--id= : ID de una integracion concreta} {--limit=50 : Maximo por corrida}';
    protected $description = 'Crea o concilia solicitudes de mezclas pendientes en CBTA de forma idempotente';

    public function handle(MixtureIntegrationSyncService $sync): int
    {
        $query = MixtureIntegration::query()
            ->when($this->option('id'), fn ($query, $id) => $query->whereKey($id))
            ->when(! $this->option('id'), fn ($query) => $query
                ->where(function ($query): void {
                    $query->whereIn('sync_status', ['prevalidated', 'synced'])
                        ->orWhere(function ($query): void {
                            $query->where('sync_status', 'failed')
                                ->where('sync_attempts', '<', (int) config('cbta.max_sync_attempts', 10))
                                ->where(fn ($query) => $query->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now()));
                        });
                }))
            ->orderBy('id')
            ->limit((int) $this->option('limit'));
        $processed = 0;
        $failed = 0;

        $query->get()->each(function (MixtureIntegration $integration) use ($sync, &$processed, &$failed): void {
            $processed++;
            if (! $sync->sync($integration)) {
                $failed++;
            }
        });

        $this->info("Integraciones procesadas: {$processed}; fallidas: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
