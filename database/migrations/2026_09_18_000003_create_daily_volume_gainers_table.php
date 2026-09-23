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
        Schema::create('daily_volume_gainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->date('trade_date');
            $table->unsignedBigInteger('volume')->nullable();
            $table->unsignedBigInteger('week1_avg_volume')->nullable();
            $table->decimal('week1_change', 10, 2)->nullable(); // e.g. 184.07x
            $table->unsignedBigInteger('week2_avg_volume')->nullable();
            $table->decimal('week2_change', 10, 2)->nullable();
            $table->decimal('ltp', 12, 2)->nullable();
            $table->decimal('p_change', 8, 2)->nullable();
            $table->decimal('turnover_lakhs', 14, 2)->nullable();
            $table->unsignedInteger('sessions_seen')->default(1);
            $table->string('confidence', 10)->default('★'); // ★, ★★, ★★★
            $table->timestamps();

            $table->unique(['stock_id', 'trade_date']);
            $table->index('trade_date');
            $table->index(['trade_date', 'week1_change']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_volume_gainers');
    }
};
