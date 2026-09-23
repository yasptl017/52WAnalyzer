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
        Schema::create('trade_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->date('trade_date');
            $table->string('entry_style', 20)->default('Moderate'); // Aggressive, Moderate, Conservative
            $table->decimal('entry_price', 12, 2);
            $table->decimal('stop_loss', 12, 2);
            $table->string('stop_method', 50)->default('ATR_1.5x');
            $table->decimal('target_1r', 12, 2)->nullable();
            $table->decimal('target_2r', 12, 2)->nullable();
            $table->decimal('target_3r', 12, 2)->nullable();
            $table->decimal('prev_resistance', 12, 2)->nullable();
            $table->decimal('atr_projection', 12, 2)->nullable();
            $table->decimal('rr_ratio', 6, 2)->default(0);
            $table->unsignedInteger('suggested_qty')->default(0);
            $table->decimal('capital_used', 14, 2)->default(0);
            $table->string('status', 20)->default('CANDIDATE'); // CANDIDATE, REJECTED
            $table->string('rejection_reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['trade_date', 'status']);
            $table->index(['trade_date', 'rr_ratio']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_setups');
    }
};
