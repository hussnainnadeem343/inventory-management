<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('code', 20);
            $table->string('description', 255)->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['shop_id', 'code']);
            $table->index(['shop_id', 'status']);
        });

        // Pre-seed standard common units
        $now = now();
        $standardUnits = [
            ['name' => 'Piece', 'code' => 'PCS', 'description' => 'Single item / unit piece'],
            ['name' => 'Kilogram', 'code' => 'KG', 'description' => 'Weight in kilograms'],
            ['name' => 'Gram', 'code' => 'GM', 'description' => 'Weight in grams'],
            ['name' => 'Litre', 'code' => 'LTR', 'description' => 'Liquid volume in litres'],
            ['name' => 'Millilitre', 'code' => 'ML', 'description' => 'Liquid volume in millilitres'],
            ['name' => 'Box', 'code' => 'BOX', 'description' => 'Carton or outer box packaging'],
            ['name' => 'Pack', 'code' => 'PACK', 'description' => 'Pre-packed bundled item'],
            ['name' => 'Dozen', 'code' => 'DOZ', 'description' => 'Twelve units (12 pcs)'],
            ['name' => 'Meter', 'code' => 'MTR', 'description' => 'Length in meters'],
            ['name' => 'Bag', 'code' => 'BAG', 'description' => 'Sack or bulk bori packaging'],
        ];

        foreach ($standardUnits as $unit) {
            DB::table('units')->insert([
                'shop_id' => null,
                'name' => $unit['name'],
                'code' => $unit['code'],
                'description' => $unit['description'],
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
