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
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('business_name');
            $table->string('business_type')->nullable(); // e.g. Auto Lube Station, Retail Store, Wholesale, Multi-Shop
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->string('branches_count')->nullable();
            $table->text('message')->nullable();
            $table->string('preferred_plan')->nullable(); // e.g. Starter, Auto Lube Pro, Enterprise
            $table->string('status')->default('pending'); // pending, contacted, scheduled, closed
            $table->text('internal_notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
