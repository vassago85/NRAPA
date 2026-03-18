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
        Schema::create('terms_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('terms_version_id')->constrained()->cascadeOnDelete();
            $table->timestamp('accepted_at')->useCurrent();
            $table->string('accepted_ip', 45)->nullable();
            $table->text('accepted_user_agent')->nullable();
            $table->timestamps();

            // A user can only accept each version once
            $table->unique(['user_id', 'terms_version_id']);

            // Indexes for lookups
            $table->index('user_id');
            $table->index('terms_version_id');
            $table->index('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_acceptances');
    }
};
