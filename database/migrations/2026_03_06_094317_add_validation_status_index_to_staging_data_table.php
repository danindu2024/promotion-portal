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
            // Covers: WHERE validation_status = 'Pending' GROUP BY batch_id ORDER BY created_at
            // This is the core query for ReviewController::pending() — eliminates full table scan
            $table->index(['validation_status', 'created_at'], 'idx_staging_status_created');

            // Covers: WHERE validation_status = 'Pending' AND batch_id = ?
            // Used by batchDetails() and approveBatch()
            $table->index(['validation_status', 'batch_id'], 'idx_staging_status_batch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staging_data', function (Blueprint $table) {
            $table->dropIndex('idx_staging_status_created');
            $table->dropIndex('idx_staging_status_batch');
        });
    }
};
