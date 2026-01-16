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
        Schema::table('shooting_activities', function (Blueprint $table) {
            // Drop old columns that we're replacing
            $table->dropColumn('activity_type');
            $table->dropColumn('venue');

            // Add new relationship columns
            $table->foreignId('activity_type_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('event_category_id')->nullable()->after('activity_type_id')->constrained()->nullOnDelete();
            $table->foreignId('event_type_id')->nullable()->after('event_category_id')->constrained()->nullOnDelete();
            $table->foreignId('firearm_type_id')->nullable()->after('description')->constrained()->nullOnDelete();
            $table->foreignId('calibre_id')->nullable()->after('firearm_type_id')->constrained()->nullOnDelete();

            // Location fields
            $table->string('location')->nullable()->after('calibre_id');
            $table->foreignId('country_id')->nullable()->after('location')->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->after('country_id')->constrained()->nullOnDelete();
            $table->string('closest_town_city')->nullable()->after('province_id');

            // Additional document
            $table->foreignId('additional_document_id')->nullable()->after('evidence_document_id')->constrained('member_documents')->nullOnDelete();

            // Status for approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('additional_document_id');
            $table->text('rejection_reason')->nullable()->after('status');

            // Activity period tracking
            $table->unsignedTinyInteger('activity_year_month_start')->nullable()->after('activity_date');
        });

        // Add activity period start month to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('activity_period_start_month')->default(10)->after('is_admin'); // Default October
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('activity_period_start_month');
        });

        Schema::table('shooting_activities', function (Blueprint $table) {
            $table->dropColumn([
                'activity_type_id',
                'event_category_id',
                'event_type_id',
                'firearm_type_id',
                'calibre_id',
                'location',
                'country_id',
                'province_id',
                'closest_town_city',
                'additional_document_id',
                'status',
                'rejection_reason',
                'activity_year_month_start',
            ]);

            // Re-add old columns
            $table->string('activity_type')->nullable()->after('activity_date');
            $table->string('venue')->nullable()->after('activity_type');
        });
    }
};
