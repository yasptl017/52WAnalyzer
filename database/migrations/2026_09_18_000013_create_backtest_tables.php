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
        Schema::create('backtest_runs', function (Blueprint $table) {
            $table->id();
            $table->string('strategy_name', 100)->default('52WH Buy & Hold');
            $table->date('from_date');
            $table->date('to_date');
            $table->decimal('sizing_per_stock', 12, 2)->default(10000.00);
            $table->unsignedInteger('total_trades')->default(0);
            $table->decimal('win_rate_pct', 6, 2)->default(0);
            $table->decimal('avg_gain_pct', 6, 2)->default(0);
            $table->decimal('avg_loss_pct', 6, 2)->default(0);
            $table->decimal('profit_factor', 6, 2)->default(0);
            $table->decimal('max_drawdown_pct', 6, 2)->default(0);
            $table->decimal('sharpe_ratio', 6, 2)->default(0);
            $table->json('parameters')->nullable();
            $table->json('hold_period_metrics')->nullable(); // 2W, 4W, 12W, 24W, 52W breakdown
            $table->timestamps();
        });

        Schema::create('backtest_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backtest_run_id')->constrained('backtest_runs')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->date('entry_date');
            $table->decimal('entry_price', 12, 2);
            $table->date('exit_date');
            $table->decimal('exit_price', 12, 2);
            $table->unsignedInteger('hold_weeks');
            $table->decimal('pnl', 14, 2);
            $table->decimal('pnl_pct', 8, 2);
            $table->timestamps();

            $table->index(['backtest_run_id', 'hold_weeks']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backtest_trades');
        Schema::dropIfExists('backtest_runs');
    }
};
