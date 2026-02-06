<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loading_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('load_data_id')->constrained('load_data')->onDelete('cascade');
            $table->unsignedInteger('rounds_loaded');
            $table->date('session_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'load_data_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_sessions');
    }
};
