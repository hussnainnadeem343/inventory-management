<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('closing_date');
            $table->dateTime('closed_at');
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->decimal('cash_sales', 12, 2)->default(0);
            $table->decimal('cash_expenses', 12, 2)->default(0);
            $table->decimal('expected_cash', 12, 2)->default(0);
            $table->decimal('actual_cash', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            $table->decimal('card_total', 12, 2)->default(0);
            $table->decimal('bank_total', 12, 2)->default(0);
            $table->decimal('credit_total', 12, 2)->default(0);
            $table->decimal('total_net_sales', 12, 2)->default(0);
            $table->integer('total_invoices')->default(0);
            $table->string('status', 30)->default('closed');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'closing_date']);
            $table->index(['shop_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_closings');
    }
};
