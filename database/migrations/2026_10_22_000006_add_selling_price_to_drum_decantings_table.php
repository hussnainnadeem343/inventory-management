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
        Schema::table('drum_decantings', function (Blueprint $table) {
            $table->decimal('selling_price_per_liter', 12, 2)->default(0.00)->after('cost_per_liter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drum_decantings', function (Blueprint $table) {
            $table->dropColumn('selling_price_per_liter');
        });
    }
};
