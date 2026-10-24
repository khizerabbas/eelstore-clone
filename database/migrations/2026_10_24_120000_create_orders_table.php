<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // Human-friendly reference (UE-100042) and secret token for the public status page
            $table->string('reference', 20)->nullable()->unique();
            $table->string('public_token', 64)->unique();

            // Customer (no accounts – order requests are confirmed by phone/WhatsApp)
            $table->string('customer_name');
            $table->string('customer_phone', 20)->index(); // normalised +923XXXXXXXXX
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();
            $table->text('customer_comment')->nullable();

            // Totals (Cash on Delivery – no payment fields)
            $table->unsignedInteger('items_count')->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);

            // Status flow: new → contacted → confirmed → out_for_delivery → delivered (or cancelled)
            $table->string('status', 30)->default('new')->index();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('admin_note')->nullable();

            // Set once the owner WhatsApp alert went out (job is idempotent on retry)
            $table->timestamp('owner_whatsapp_sent_at')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot at the time of the request, so later product edits don't change old orders
            $table->string('product_name');
            $table->string('product_slug')->nullable();
            $table->string('sku')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
