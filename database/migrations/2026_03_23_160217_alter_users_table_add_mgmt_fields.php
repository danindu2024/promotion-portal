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
            $table->string('province')->nullable()->after('password');
            $table->string('district')->nullable()->after('province');
            $table->string('ds_division')->nullable()->after('district');
            $table->dropColumn('role');
            $table->enum('access_level', ['data entry', 'validator', 'decision maker', 'admin'])->after('ds_division')->default('data entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['province', 'district', 'ds_division', 'access_level']);
            $table->enum('role', ['Agent', 'Validator', 'Admin'])->default('Agent');
        });
    }
};
