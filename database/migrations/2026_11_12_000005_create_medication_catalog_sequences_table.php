<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medication_catalog_sequences', function (Blueprint $table): void {
            $table->string('name', 40)->primary();
            $table->unsignedBigInteger('last_value')->default(100000000);
        });
        DB::table('medication_catalog_sequences')->insert(['name' => 'sku', 'last_value' => 100000000]);
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_catalog_sequences');
    }
};
