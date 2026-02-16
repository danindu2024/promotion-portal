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
        // MAIN REGISTRY
        Schema::create('main_registry', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['Self-Employed', 'Trade']);
            
            // Common Fields
            $table->string('full_name');
            $table->text('address');
            $table->string('district')->index(); // Index for filtering
            $table->string('ds_division');
            $table->string('gn_division');
            $table->string('contact_number')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('email')->nullable();

            // Self-Employed Specific (Nullable)
            $table->integer('age')->nullable()->index('idx_age');
            $table->enum('field_of_work', [
                'Agriculture & Fishing',
                'Textile & Garments',
                'Construction',
                'IT & Modern Services',
                'Food & Beverages',
                'Manufacturing',
                'Tourism & Hospitality',
                'Transportation',
                'Retail & Wholesale',
                'Other Services'
            ]);
            $table->integer('employees_count')->nullable();

            // Trade Specific (Nullable)
            $table->string('contact_person')->nullable();
            $table->integer('members_count')->nullable();

            // Soft Deletes & Approval Audit
            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('approved_by');
            $table->timestamp('approved_at');
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->text('deletion_reason')->nullable();

            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('approved_by')->references('user_id')->on('users');
            $table->foreign('deleted_by')->references('user_id')->on('users');

            // Composite Indexes (from SDD optimization strategy)
            // Database indexes follow the "Leftmost Prefix" rule. Covers queries for:
            // - WHERE category = ?
            // - WHERE category = ? AND district = ?
            // - WHERE category = ? AND district = ? AND field_of_work = ?
            $table->index(['category', 'district', 'field_of_work'], 'idx_category_district_field');
        });

        // STAGING DATA
        Schema::create('staging_data', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->index(); // To group Excel uploads
            $table->json('data_payload'); // Stores raw data
            $table->enum('validation_status', ['Pending', 'Valid', 'Error', 'Rejected', 'Approved'])->default('Pending');
            $table->enum('submission_type', ['NEW', 'UPDATE']);
            
            $table->unsignedBigInteger('target_record_id')->nullable(); // Only for Updates
            $table->text('error_message')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->unsignedBigInteger('uploaded_by');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            
            $table->timestamps(); // includes uploaded_at (created_at)

            // Constraints
            $table->foreign('uploaded_by')->references('user_id')->on('users');
            $table->foreign('reviewed_by')->references('user_id')->on('users');
            $table->foreign('target_record_id')->references('id')->on('main_registry');
        });

        // AUDIT LOGS (Security Camera) [cite: 179]
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable for failed logins
            $table->string('event_type'); // 'AUTH_FAILURE', 'SEARCH_QUERY', etc. 
            $table->string('action');
            $table->string('target_module')->nullable();
            $table->string('ip_address');
            $table->text('details')->nullable(); // Snapshot of old vs new values
            $table->json('metadata')->nullable(); // Search filters, record counts
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            // Foreign Key Constraint
            $table->foreign('user_id')->references('user_id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('staging_data');
        Schema::dropIfExists('main_registry');

    }
};
