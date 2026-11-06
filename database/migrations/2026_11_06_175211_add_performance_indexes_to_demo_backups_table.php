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
            $table->index('restored_path');
            $table->index('is_automated');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demo_backups', function (Blueprint $table) {
            $table->dropIndex(['restored_path']);
            $table->dropIndex(['is_automated']);
            $table->dropIndex(['created_at']);
        });
    }
};
