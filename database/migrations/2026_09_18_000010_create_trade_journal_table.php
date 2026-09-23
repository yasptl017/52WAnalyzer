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
        Schema::create('trade_journal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->date('entry_date');
            $table->decimal('entry_price', 12, 2);
            $table->decimal('stop_loss', 12, 2)->nullable();
            $table->decimal('target_price', 12, 2)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('status', 20)->default('OPEN'); // OPEN, CLOSED, CANCELLED
            $table->date('exit_date')->nullable();
            $table->decimal('exit_price', 12, 2)->nullable();
            $table->decimal('pnl', 14, 2)->nullable();
            $table->decimal('pnl_pct', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'entry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_journal');
    }
};
