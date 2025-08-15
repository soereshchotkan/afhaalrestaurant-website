<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->unique()->after('name')->nullable();
        });
        
        // Update existing categories with slugs
        $categories = \App\Models\Category::all();
        foreach ($categories as $category) {
            $category->slug = \Illuminate\Support\Str::slug($category->name);
            $category->save();
        }
        
        // Make slug not nullable after updating existing records
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};