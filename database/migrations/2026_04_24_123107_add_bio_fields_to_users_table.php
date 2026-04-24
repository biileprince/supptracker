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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member')->after('password');
            $table->string('employee_id')->nullable()->after('role');
            $table->string('job_title')->nullable()->after('employee_id');
            $table->string('department')->nullable()->after('job_title');
            $table->string('phone')->nullable()->after('department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'employee_id', 'job_title', 'department', 'phone']);
        });
    }
};
