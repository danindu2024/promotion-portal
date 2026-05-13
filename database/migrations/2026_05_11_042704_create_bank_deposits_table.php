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
        Schema::create('bank_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('enrollment_number')->index();
            $table->decimal('amount', 15, 2);
            $table->date('deposit_date');
            $table->string('bank_name');
            $table->string('branch');
            $table->string('receipt_reference_number');
            $table->string('slip_path')->nullable();
            $table->string('status')->nullable()->default(null);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->constrained('users', 'user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_deposits');
    }
};
