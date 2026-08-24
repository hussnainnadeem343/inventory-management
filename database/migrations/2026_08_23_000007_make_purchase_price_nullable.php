<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('purchase_price', 14, 2)->nullable()->change();
            $table->decimal('selling_price', 14, 2)->nullable()->change();
        });
    }

    public function down(): void {}
};
