<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Only admins have accounts: owner (everything) or staff (orders + catalog)
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staff')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('image');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable()->after('description');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('description');
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable();
        });

        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('youtube_url')->nullable()->after('whatsapp_url');
            $table->string('x_url')->nullable()->after('youtube_url');
            $table->text('footer_about')->nullable();
            $table->string('opening_hours')->nullable();
        });

        // Simple CMS pages (About, Delivery & COD, Returns, Privacy, Terms, FAQ …)
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');

        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['youtube_url', 'x_url', 'footer_about', 'opening_hours']);
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('logo');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['image', 'meta_title', 'meta_description']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gallery', 'meta_title', 'meta_description']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
