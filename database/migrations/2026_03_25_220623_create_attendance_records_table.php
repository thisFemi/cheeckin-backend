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
    Schema::create('attendance_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
        $table->foreignId('attendance_policy_id')->nullable()->constrained()->nullOnDelete();
        $table->date('date');
        $table->timestamp('check_in_at')->nullable();
        $table->timestamp('check_out_at')->nullable();
        $table->decimal('check_in_latitude', 10, 7)->nullable();
        $table->decimal('check_in_longitude', 10, 7)->nullable();
        $table->decimal('check_out_latitude', 10, 7)->nullable();
        $table->decimal('check_out_longitude', 10, 7)->nullable();
        $table->string('check_in_face_image')->nullable();
        $table->string('check_out_face_image')->nullable();
        $table->boolean('check_in_face_verified')->default(false);
        $table->boolean('check_out_face_verified')->default(false);
        $table->enum('status', ['present', 'absent', 'late', 'half_day', 'on_leave'])->default('absent');
        $table->integer('working_minutes')->nullable();
        $table->text('admin_note')->nullable();
        $table->boolean('is_overridden')->default(false);
        $table->timestamps();
        $table->unique(['user_id', 'date']);
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
