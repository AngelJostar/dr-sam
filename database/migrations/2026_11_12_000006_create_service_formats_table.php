<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_formats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('name');
            $table->string('purpose', 32)->default('request');
            $table->text('description')->nullable();
            $table->string('source_view')->nullable();
            $table->timestamps();
            $table->unique(['service_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_formats');
    }
};
