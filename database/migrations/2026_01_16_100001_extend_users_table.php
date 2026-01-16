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
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->after('id');
            $table->string('id_number')->nullable()->unique()->after('email');
            $table->string('phone')->nullable()->after('id_number');
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->text('physical_address')->nullable()->after('date_of_birth');
            $table->text('postal_address')->nullable()->after('physical_address');
            $table->boolean('is_admin')->default(false)->after('remember_token');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'id_number',
                'phone',
                'date_of_birth',
                'physical_address',
                'postal_address',
                'is_admin',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
