<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Editable storefront content (texts, links, images, colours) – keys like "home.hero_title".
        // Defaults live in config/site_content.php; only values changed in the admin are stored here.
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        // Carry over texts that were already editable in Store settings
        $settings = DB::table('site_settings')->first();

        if ($settings) {
            $carry = [
                'home.hero_title'    => $settings->home_hero_title ?? null,
                'home.hero_subtitle' => $settings->home_hero_subtitle ?? null,
                'footer.about_text'  => $settings->footer_about ?? null,
            ];

            foreach (array_filter($carry, fn ($v) => filled($v)) as $key => $value) {
                DB::table('site_contents')->insert([
                    'key'        => $key,
                    'value'      => json_encode($value),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
