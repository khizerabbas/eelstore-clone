<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Category;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homepage_category_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete(); // if category deleted, section becomes empty
            $table->unsignedTinyInteger('position')->default(1); // 1,2,3
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed initial 3 sections using the first 3 categories (for now)
        $categories = Category::orderBy('id')->take(3)->get();
        $position = 1;

        foreach ($categories as $category) {
            DB::table('homepage_category_sections')->insert([
                'category_id' => $category->id,
                'position'    => $position++,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_category_sections');
    }
};
