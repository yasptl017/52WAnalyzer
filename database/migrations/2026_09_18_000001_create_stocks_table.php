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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 30)->unique();
            $table->string('company_name', 255)->nullable();
            $table->string('series', 10)->default('EQ');
            $table->string('asset_class', 20)->default('EQUITY'); // EQUITY, ETF, etc.
            $table->string('sector', 100)->nullable();
            $table->decimal('market_cap_cr', 14, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('symbol');
            $table->index('sector');
            $table->index('asset_class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
