<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds soft-delete support to the users table.
     * Hard-deleting users would violate foreign key constraints in:
     *   - staging_data (uploaded_by, reviewed_by)
     *   - main_registry (approved_by, deleted_by)
     *   - audit_logs (user_id)
     *
     * Instead, users are deactivated (is_active = false) and kept in the DB
     * so all historical data references remain intact.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Soft delete: mark user as inactive instead of removing the row
            $table->boolean('is_active')->default(true)->after('access_level');
            $table->softDeletes(); // adds deleted_at timestamp column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'deleted_at']);
        });
    }
};
