<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Products with the same size group link to each other as "Other sizes" (e.g. group "7/29 wire", label "90 m")
            $table->string('size_group', 100)->nullable()->index()->after('sku');
            $table->string('size_label', 40)->nullable()->after('size_group');
            // Optional note on the product page, e.g. "Custom lengths available – mention the length in the order comment"
            $table->string('customer_note', 300)->nullable()->after('short_description');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['size_group']);
            $table->dropColumn(['size_group', 'size_label', 'customer_note']);
        });
    }
};
