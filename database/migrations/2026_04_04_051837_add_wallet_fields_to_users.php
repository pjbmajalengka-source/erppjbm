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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('opening_salary_balance')->default(0)->after('email');
            $table->bigInteger('opening_bonus_balance')->default(0)->after('opening_salary_balance');
            $table->string('role')->default('employee')->after('opening_bonus_balance');
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['opening_salary_balance', 'opening_bonus_balance', 'role', 'is_active']);
        });
    }
};
