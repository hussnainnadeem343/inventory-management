<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Categories table: drop global unique on name and add composite unique on [shop_id, name]
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_name_unique');
            $table->unique(['shop_id', 'name'], 'categories_shop_name_unique');
        });

        // 2. Brands table: drop global unique on name and add composite unique on [shop_id, name]
        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique('brands_name_unique');
            $table->unique(['shop_id', 'name'], 'brands_shop_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_shop_name_unique');
            $table->unique('name', 'categories_name_unique');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropUnique('brands_shop_name_unique');
            $table->unique('name', 'brands_name_unique');
        });
    }
};
