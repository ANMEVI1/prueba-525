<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The anonymous page view counter was never displayed; the audit log replaces it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('page_views');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('page_views', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();

            $table->index('created_at');
        });
    }
};
