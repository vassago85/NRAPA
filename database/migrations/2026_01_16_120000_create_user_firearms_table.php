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
        Schema::create('user_firearms', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('firearm_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calibre_id')->nullable()->constrained()->nullOnDelete();

            // Firearm details
            $table->string('make')->nullable(); // e.g., "Howa", "Tikka", "CZ"
            $table->string('model')->nullable(); // e.g., "1500", "T3x", "455"
            $table->string('serial_number')->nullable();
            $table->string('nickname')->nullable(); // User's name for the firearm

            // Barrel details (for rifles)
            $table->string('barrel_length')->nullable(); // e.g., "24 inches"
            $table->string('barrel_twist')->nullable(); // e.g., "1:10"
            $table->string('barrel_profile')->nullable(); // e.g., "Heavy", "Sporter"

            // Stock/Chassis
            $table->string('stock_type')->nullable(); // e.g., "Synthetic", "Wood", "Chassis"
            $table->string('stock_make')->nullable();

            // Optics
            $table->string('scope_make')->nullable();
            $table->string('scope_model')->nullable();
            $table->string('scope_magnification')->nullable(); // e.g., "4-16x44"

            // License details
            $table->string('license_number')->nullable();
            $table->date('license_issue_date')->nullable();
            $table->date('license_expiry_date')->nullable();
            $table->enum('license_type', ['self_defence', 'occasional_sport', 'dedicated_sport', 'dedicated_hunting', 'business', 'private_collection'])->nullable();
            $table->enum('license_status', ['valid', 'expired', 'renewal_pending', 'revoked'])->default('valid');

            // Notifications
            $table->boolean('expiry_notification_sent_90')->default(false);
            $table->boolean('expiry_notification_sent_60')->default(false);
            $table->boolean('expiry_notification_sent_30')->default(false);
            $table->boolean('expiry_notification_sent_7')->default(false);

            // Additional info
            $table->text('notes')->nullable();
            $table->string('image_path')->nullable(); // Photo of firearm
            $table->string('license_document_path')->nullable(); // Scan of license

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'is_active']);
            $table->index('license_expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_firearms');
    }
};
