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
        Schema::create('momentum_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->date('trade_date');
            $table->decimal('momentum_score', 6, 2)->default(0);
            $table->decimal('institutional_score', 6, 2)->default(0);
            $table->decimal('trade_quality_score', 6, 2)->default(0);
            $table->string('rank_tier', 10)->default('B'); // AAA, AA, A, B, Avoid
            $table->decimal('confidence_pct', 5, 2)->default(0);
            $table->json('reasons')->nullable();
            $table->timestamps();

            $table->unique(['stock_id', 'trade_date']);
            $table->index(['trade_date', 'momentum_score']);
            $table->index(['trade_date', 'rank_tier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('momentum_scores');
    }
};
