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
        Schema::create('data_archives', function (Blueprint $table) {
            $table->id();
            $table->date('trade_date')->unique();
            $table->string('source', 50)->default('NSE');
            $table->unsignedInteger('total_52wh')->default(0);
            $table->unsignedInteger('total_vg')->default(0);
            $table->string('status', 20)->default('COMPLETE'); // COMPLETE, PARTIAL, FAILED
            $table->string('raw_file_52wh', 255)->nullable();
            $table->string('raw_file_vg', 255)->nullable();
            $table->string('raw_file_bhavcopy', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('trade_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_archives');
    }
};
