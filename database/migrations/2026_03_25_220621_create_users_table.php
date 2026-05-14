<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // create_users_table
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('role_id')->nullable()->constrained();
        $table->string('first_name');
        $table->string('last_name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('phone')->nullable();
        $table->string('avatar')->nullable();
        $table->string('face_template')->nullable();
        $table->enum('user_type', ['owner', 'admin', 'employee']);
        $table->enum('employment_status', ['active', 'inactive', 'suspended'])->default('active');
        $table->string('employee_id')->nullable();
        $table->string('department')->nullable();
        $table->string('position')->nullable();
        $table->date('joined_date')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
