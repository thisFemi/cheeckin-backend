<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// create_attendance_policies_table
public function up(): void
{
    Schema::create('attendance_policies', function (Blueprint $table) {
        $table->id();
        $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
        $table->string('name');
        $table->time('work_start_time');
        $table->time('work_end_time');
        $table->integer('late_threshold_minutes')->default(15);
        $table->integer('early_checkout_threshold_minutes')->default(15);
        $table->boolean('allow_remote')->default(false);
        $table->decimal('office_latitude', 10, 7)->nullable();
        $table->decimal('office_longitude', 10, 7)->nullable();
        $table->integer('location_radius_meters')->default(100);
        $table->boolean('require_face_capture')->default(true);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_policies');
    }
};
