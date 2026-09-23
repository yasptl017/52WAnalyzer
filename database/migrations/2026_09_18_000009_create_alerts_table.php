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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained('stocks')->onDelete('cascade');
            $table->date('trade_date');
            $table->string('rule_code', 50); // THIRD_52WH, VOLUME_DOUBLES, MOMENTUM_OVER_90, RR_OVER_3, INSTITUTIONAL_OVER_85
            $table->text('message');
            $table->boolean('is_acknowledged')->default(false);
            $table->timestamp('raised_at')->useCurrent();
            $table->timestamps();

            $table->index(['trade_date', 'rule_code']);
            $table->index(['is_acknowledged', 'raised_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
