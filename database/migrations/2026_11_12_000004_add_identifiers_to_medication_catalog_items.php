<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_catalog_items', function (Blueprint $table): void {
            $table->string('sku', 100)->nullable()->unique();
            $table->string('barcode', 32)->nullable()->index();
            $table->string('barcode_type', 16)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('medication_catalog_items', function (Blueprint $table): void {
            $table->dropUnique(['sku']);
            $table->dropIndex(['barcode']);
            $table->dropColumn(['sku', 'barcode', 'barcode_type']);
        });
    }
};
