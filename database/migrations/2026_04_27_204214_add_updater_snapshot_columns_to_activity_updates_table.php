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
        Schema::table('activity_updates', function (Blueprint $table) {
            $table->string('updater_name')->nullable()->after('user_id');
            $table->string('updater_department')->nullable()->after('updater_name');
            $table->string('updater_job_title')->nullable()->after('updater_department');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_updates', function (Blueprint $table) {
            $table->dropColumn(['updater_name', 'updater_department', 'updater_job_title']);
        });
    }
};
