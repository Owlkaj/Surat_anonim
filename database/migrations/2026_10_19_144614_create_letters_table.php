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
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->onDelete('cascade');
            $table->string('target_contact_hmac')->nullable()->index();
            $table->text('target_email_encrypted')->nullable();
            $table->string('target_label')->nullable();
            $table->text('body');
            $table->string('music_url', 1000)->nullable();
            $table->string('visibility')->default('private')->index(); // private, unlisted, public_pending, public
            $table->string('moderation_status')->default('approved')->index(); // approved, pending, under_review, rejected, takedown
            $table->string('token_hash', 64)->unique();
            $table->integer('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letters');
    }
};
