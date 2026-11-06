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
        Schema::table('demo_backups', function (Blueprint $table) {
            $table->string('database_name', 100)->nullable()->default('iesp_continuidad_demo')->after('status');
            $table->string('database_type', 20)->nullable()->default('mysql')->after('database_name');
            $table->string('secondary_path')->nullable()->after('artifact_sha256');
            $table->boolean('replicated')->default(false)->after('secondary_path');
            $table->boolean('is_automated')->default(false)->after('replicated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demo_backups', function (Blueprint $table) {
            $table->dropColumn([
                'database_name',
                'database_type',
                'secondary_path',
                'replicated',
                'is_automated',
            ]);
        });
    }
};
