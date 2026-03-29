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
        Schema::table('main_registry', function (Blueprint $table) {
            // Drop existing unique constraint
            $table->dropUnique(['contact_number']);
            
            // Add composite unique constraint
            $table->unique(['contact_number', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_registry', function (Blueprint $table) {
            $table->dropUnique(['contact_number', 'category']);
            $table->unique('contact_number');
        });
    }
};
