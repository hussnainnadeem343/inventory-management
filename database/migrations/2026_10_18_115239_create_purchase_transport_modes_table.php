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
        Schema::create('purchase_transport_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $defaultModes = [
            ['name' => 'Road / Truck', 'description' => 'Heavy transport and inter-city trailers'],
            ['name' => 'Suzuki / Pickup', 'description' => 'Local intra-city delivery pickup'],
            ['name' => 'Courier / Cargo', 'description' => 'TCS, Leopard, Post, or specialized cargo service'],
            ['name' => 'By Hand', 'description' => 'Direct hand delivery or staff purchase pick up'],
            ['name' => 'Air Cargo', 'description' => 'Urgent airport air freight cargo'],
        ];

        foreach ($defaultModes as $m) {
            DB::table('purchase_transport_modes')->insert([
                'shop_id' => null,
                'name' => $m['name'],
                'description' => $m['description'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_transport_modes');
    }
};
