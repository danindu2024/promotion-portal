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
        Schema::table('bank_deposits', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_deposits', function (Blueprint $table) {
            $table->string('bank_name')->after('deposit_date');
            $table->string('status')->nullable()->after('slip_path');
        });
    }
};
