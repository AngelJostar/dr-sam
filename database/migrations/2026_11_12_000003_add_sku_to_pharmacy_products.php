<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table): void {
            $table->string('sku', 100)->nullable();
            $table->unique(['provider_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table): void {
            $table->dropUnique(['provider_id', 'sku']);
            $table->dropColumn('sku');
        });
    }
};
