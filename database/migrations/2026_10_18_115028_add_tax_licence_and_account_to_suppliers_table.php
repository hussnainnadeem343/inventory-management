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
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('ntn', 50)->nullable()->after('email');
            $table->string('strn', 50)->nullable()->after('ntn');
            $table->string('license_number', 100)->nullable()->after('strn');
            $table->foreignId('liability_account_id')->nullable()->after('license_number')->constrained('accounts')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropForeign(['liability_account_id']);
            $table->dropColumn(['ntn', 'strn', 'license_number', 'liability_account_id']);
        });
    }
};
