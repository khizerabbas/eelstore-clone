<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Bulk / trade quote requests from electricians and contractors (/bulk-quote)
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->nullable()->unique();   // QR-100001
            $table->string('name');
            $table->string('phone', 20)->index();                     // +923XXXXXXXXX
            $table->string('email')->nullable();
            $table->string('business_name')->nullable();
            $table->string('city')->nullable();
            $table->text('items');                                    // free text list from the customer
            $table->json('cart_items')->nullable();                   // snapshot of their cart, if they ticked it
            $table->string('attachment_path')->nullable();            // private disk (storage/app/private/quotes)
            $table->string('attachment_name')->nullable();
            $table->string('status', 20)->default('new')->index();    // new, contacted, quoted, won, lost
            $table->text('admin_note')->nullable();
            $table->timestamp('owner_whatsapp_sent_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_requests');
    }
};
