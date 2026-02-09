<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_failures', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->unsignedInteger('row_number');
            $table->json('row_data');
            $table->string('error_message', 500);
            $table->boolean('resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('imported_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['batch_id', 'resolved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_failures');
    }
};
