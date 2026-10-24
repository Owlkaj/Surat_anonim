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
        Schema::create('delivery_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('letter_id')->constrained('letters')->onDelete('cascade');
            $table->string('channel')->default('email');
            $table->string('destination_hmac')->index();
            $table->text('destination_encrypted')->nullable();
            $table->string('status')->default('queued')->index(); // queued, sending, sent, failed_retryable, failed_permanent
            $table->integer('attempt_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->string('idempotency_key')->unique();
            $table->string('provider_message_id')->nullable();
            $table->string('last_error_code')->nullable();
            $table->timestamps();
            $table->timestamp('purge_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_jobs');
    }
};
