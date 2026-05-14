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
    Schema::create('user_leave_entitlements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
        $table->integer('year');
        $table->integer('entitled_days');
        $table->timestamps();
        $table->unique(['user_id', 'leave_type_id', 'year']);
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_leave_entitlements');
    }
};
