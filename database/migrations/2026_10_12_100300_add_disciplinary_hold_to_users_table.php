<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('disciplinary_hold_at')->nullable()->index();
            $table->string('disciplinary_hold_reason', 500)->nullable();
            $table->foreignId('disciplinary_hold_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('disciplinary_hold_by');
            $table->dropIndex(['disciplinary_hold_at']);
            $table->dropColumn(['disciplinary_hold_at', 'disciplinary_hold_reason']);
        });
    }
};
