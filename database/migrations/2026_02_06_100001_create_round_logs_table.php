<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('round_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_firearm_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('rounds_fired');
            $table->date('logged_date');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['user_firearm_id', 'logged_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_logs');
    }
};
