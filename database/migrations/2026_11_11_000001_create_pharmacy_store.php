<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', fn (Blueprint $t) => $t->boolean('digital_pharmacy_enabled')->default(false)->index());
        Schema::table('pharmacy_products', function (Blueprint $t): void {
            $t->foreignId('provider_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedInteger('stock')->default(0);
            $t->text('description')->nullable();
            $t->string('category')->nullable()->index();
            $t->string('image_url', 2048)->nullable();
            $t->boolean('featured')->default(false);
            $t->string('specialty')->nullable();
            $t->string('product_type')->nullable();
            $t->decimal('compare_at_price', 12, 2)->nullable();
        });
        Schema::create('pharmacy_store_settings', function (Blueprint $t): void {
            $t->id();
            $t->boolean('enabled')->default(false);
            $t->string('title')->default('Farmacia digital');
            $t->string('subtitle')->nullable();
            $t->string('banner_image_url', 2048)->nullable();
            $t->string('accent_color', 7)->default('#008c95');
            $t->decimal('shipping_fee', 10, 2)->default(0);
            $t->string('delivery_estimate')->nullable();
            $t->string('purchase_terms', 2048)->nullable();
            $t->timestamps();
        });
        Schema::create('pharmacy_store_orders', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('prescription_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('prescription_upload_id')->nullable()->index();
            $t->string('prescription_status')->nullable();
            $t->uuid('client_request_id');
            $t->string('status')->default('submitted')->index();
            $t->string('payment_status')->default('not_configured');
            $t->decimal('subtotal', 12, 2);
            $t->decimal('shipping_fee', 10, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->string('currency', 3)->default('MXN');
            $t->json('items');
            $t->json('address');
            $t->string('delivery_method')->default('delivery');
            $t->timestamp('terms_accepted_at');
            $t->string('purchase_terms', 2048)->nullable();
            $t->timestamps();
            $t->unique(['user_id', 'client_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_store_orders');
        Schema::dropIfExists('pharmacy_store_settings');
        Schema::table('pharmacy_products', function (Blueprint $t): void {
            $t->dropConstrainedForeignId('provider_id');
            $t->dropColumn(['stock', 'description', 'category', 'image_url', 'featured', 'specialty', 'product_type', 'compare_at_price']);
        });
        Schema::table('providers', fn (Blueprint $t) => $t->dropColumn('digital_pharmacy_enabled'));
    }
};
