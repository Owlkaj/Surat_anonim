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
        Schema::create('replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('reply_threads')->onDelete('cascade');
            $table->foreignId('author_user_id')->constrained('users')->onDelete('cascade');
            $table->text('body');
            $table->string('moderation_status')->default('approved')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('purge_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('replies');
    }
};
