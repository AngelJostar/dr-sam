<?php

namespace App\Services;

use App\Models\MedicationCatalogItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class MedicationSkuSequence
{
    /** All SKU writers use this row lock inside their transaction. */
    public function lock(): object
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('La asignación de SKU requiere una transacción.');
        }

        return DB::table('medication_catalog_sequences')->where('name', 'sku')->lockForUpdate()->firstOrFail();
    }

    public function next(): string
    {
        $sequence = $this->lock();
        // Include manually entered SKUs; fixed nine-digit strings sort numerically.
        $last = MedicationCatalogItem::where('sku', 'regexp', '^1[0-9]{8}$')->orderByDesc('sku')->value('sku');
        $next = max((int) $sequence->last_value, (int) $last, 100000000) + 1;
        if ($next > 199999999) {
            throw ValidationException::withMessages(['name' => 'Se agotó la secuencia de SKU del catálogo.'])->errorBag('newMedication');
        }
        DB::table('medication_catalog_sequences')->where('name', 'sku')->update(['last_value' => $next]);

        return (string) $next;
    }
}
