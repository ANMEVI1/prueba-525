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
        Schema::create('demo_backups', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->index();
            $table->string('artifact_path')->nullable();
            $table->string('artifact_sha256', 64)->nullable();
            $table->string('source_sha256', 64)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('backup_duration_ms')->nullable();
            $table->string('restored_path')->nullable();
            $table->unsignedInteger('restore_duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_backups');
    }
};
