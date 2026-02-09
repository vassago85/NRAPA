<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('dismissable_type');  // e.g., App\Models\MemberDocument, App\Models\ShootingActivity
            $table->unsignedBigInteger('dismissable_id');
            $table->timestamp('dismissed_at')->useCurrent();

            $table->unique(['user_id', 'dismissable_type', 'dismissable_id'], 'notification_dismissal_unique');
            $table->index(['user_id', 'dismissable_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_dismissals');
    }
};
