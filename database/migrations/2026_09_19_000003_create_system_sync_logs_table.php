<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->date('trade_date')->index();
            $table->timestamp('synced_at')->useCurrent();
            $table->string('triggered_by')->default('AUTO_FIRST_LOGIN'); // 'ADMIN', 'SCHEDULED_CRON', 'AUTO_FIRST_LOGIN'
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('SUCCESS'); // 'SUCCESS', 'IN_PROGRESS', 'FAILED', 'SKIPPED_HOLIDAY'
            $table->integer('records_52wh')->default(0);
            $table->integer('records_vg')->default(0);
            $table->integer('records_bhavcopy')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_sync_logs');
    }
};
