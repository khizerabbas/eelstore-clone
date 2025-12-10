<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Usman Electronics');

            // Contact info
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('store_address')->nullable();

            // Social links
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('whatsapp_url')->nullable();

            // Map
            $table->text('map_embed_url')->nullable();

            // Contact form recipient
            $table->string('contact_recipient_email')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
