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
        // Permissions table
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('group'); // membership, documents, firearm, admin
            $table->text('description')->nullable();
            $table->boolean('is_high_risk')->default(false); // For UI warning badges
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('group');
        });

        // User permissions pivot table
        Schema::create('permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
        });

        // Admin action audit log - tracks every permission-based action
        Schema::create('admin_action_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Admin who performed action
            $table->string('role_at_action'); // Role at time of action
            $table->string('permission_used'); // Permission that allowed this action
            $table->string('action'); // approve_membership, reject_document, etc.
            $table->string('target_type'); // App\Models\Membership, App\Models\MemberDocument, etc.
            $table->unsignedBigInteger('target_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['target_type', 'target_id']);
            $table->index('permission_used');
        });

        // Update users table to have admin_type for super/standard admin distinction
        Schema::table('users', function (Blueprint $table) {
            $table->string('admin_type')->nullable()->after('role'); // 'super_admin' or 'standard_admin'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('admin_type');
        });

        Schema::dropIfExists('admin_action_logs');
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permissions');
    }
};
