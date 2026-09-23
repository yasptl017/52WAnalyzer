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
        Schema::create('daily_52w_highs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->date('trade_date');
            $table->decimal('ltp', 12, 2)->nullable();
            $table->decimal('high_price', 12, 2)->nullable();
            $table->decimal('prev_close', 12, 2)->nullable();
            $table->decimal('change_val', 12, 2)->nullable();
            $table->decimal('p_change', 8, 2)->nullable();
            $table->decimal('prev_52w_high', 12, 2)->nullable();
            $table->string('prev_52w_date', 30)->nullable();
            $table->unsignedInteger('consecutive_days')->default(1);
            $table->unsignedInteger('total_appearances_30d')->default(1);
            $table->boolean('is_new')->default(false);
            $table->timestamps();

            $table->unique(['stock_id', 'trade_date']);
            $table->index('trade_date');
            $table->index(['trade_date', 'consecutive_days']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_52w_highs');
    }
};
