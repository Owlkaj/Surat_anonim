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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('content_type')->index(); // letter, reply
            $table->unsignedBigInteger('content_id')->index();
            $table->foreignId('reporter_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_hmac')->nullable()->index();
            $table->string('category')->index(); // threat, harassment, doxxing, defamation, spam, fraud, unwanted_sexual, other
            $table->text('details')->nullable();
            $table->string('status')->default('pending')->index(); // pending, under_review, resolved_takedown, resolved_dismissed
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->timestamp('purge_at')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
