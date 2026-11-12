<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medication_catalog_items', function (Blueprint $table): void {
            $table->boolean('digital_pharmacy_enabled')->default(false)->index();
        });
        Schema::table('pharmacy_products', function (Blueprint $table): void {
            $table->foreignId('medication_catalog_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->unique(['provider_id', 'medication_catalog_item_id'], 'pharmacy_provider_catalog_unique');
        });
        Schema::table('providers', function (Blueprint $table): void {
            $table->string('pharmacy_address', 500)->nullable();
            $table->decimal('pharmacy_latitude', 10, 7)->nullable();
            $table->decimal('pharmacy_longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_products', function (Blueprint $table): void {
            $table->dropUnique('pharmacy_provider_catalog_unique');
            $table->dropConstrainedForeignId('medication_catalog_item_id');
        });
        Schema::table('medication_catalog_items', fn (Blueprint $table) => $table->dropColumn('digital_pharmacy_enabled'));
        Schema::table('providers', fn (Blueprint $table) => $table->dropColumn(['pharmacy_address', 'pharmacy_latitude', 'pharmacy_longitude']));
    }
};
