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
        Schema::create('daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('trade_date');
            $table->string('category', 20); // '52WH' or 'VG'
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('new_count')->default(0);
            $table->unsignedInteger('repeated_count')->default(0);
            $table->unsignedInteger('dropped_count')->default(0);
            $table->string('top_stock', 50)->nullable();
            $table->decimal('top_score', 6, 2)->nullable();
            $table->timestamps();

            $table->unique(['trade_date', 'category']);
            $table->index('trade_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_summaries');
    }
};
