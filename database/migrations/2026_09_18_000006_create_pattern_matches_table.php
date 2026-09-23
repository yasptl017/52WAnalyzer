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
        Schema::create('pattern_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->date('trade_date');
            $table->string('pattern_code', 20); // P1..P23
            $table->string('pattern_name', 150);
            $table->decimal('confidence', 5, 2)->default(0);
            $table->text('evidence')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['trade_date', 'pattern_code']);
            $table->index(['stock_id', 'trade_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pattern_matches');
    }
};
