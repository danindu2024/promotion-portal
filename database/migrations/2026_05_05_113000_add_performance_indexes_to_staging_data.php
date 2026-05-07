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
        Schema::table('staging_data', function (Blueprint $table) {
            $table->index(['target_record_id', 'validation_status'], 'idx_staging_target_status');
            $table->index(['uploaded_by', 'validation_status'], 'idx_staging_user_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staging_data', function (Blueprint $table) {
            $table->dropIndex('idx_staging_target_status');
            $table->dropIndex('idx_staging_user_status');
        });
    }
};
