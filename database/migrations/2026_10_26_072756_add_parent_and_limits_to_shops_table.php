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
        Schema::table('shops', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('shops')
                ->nullOnDelete();

            $table->unsignedInteger('max_branches')
                ->default(1)
                ->after('address')
                ->comment('Maximum allowed branches for this business');

            $table->unsignedInteger('max_users')
                ->default(5)
                ->after('max_branches')
                ->comment('Maximum allowed users/staff across all branches');

            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'max_branches', 'max_users']);
        });
    }
};
