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
        Schema::table('inward_gate_pass_items', function (Blueprint $table) {
            $table->decimal('vendor_quantity', 14, 2)->default(0)->after('packages_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inward_gate_pass_items', function (Blueprint $table) {
            $table->dropColumn('vendor_quantity');
        });
    }
};
