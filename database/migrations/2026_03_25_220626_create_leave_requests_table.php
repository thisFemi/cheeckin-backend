<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // create_leave_requests_table
public function up(): void
{
    Schema::create('leave_requests', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
        $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->date('start_date');
        $table->date('end_date');
        $table->integer('total_days');
        $table->text('reason');
        $table->string('document')->nullable();
        $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
        $table->text('rejection_reason')->nullable();
        $table->timestamp('actioned_at')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
