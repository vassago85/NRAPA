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
        // Enhance firearm_types table
        Schema::table('firearm_types', function (Blueprint $table) {
            $table->enum('category', ['handgun', 'rifle', 'shotgun'])->default('rifle')->after('name');
            $table->enum('ignition_type', ['rimfire', 'centerfire', 'both'])->nullable()->after('category');
            $table->enum('action_type', [
                'single_shot',
                'revolver',
                'semi_auto',
                'bolt_action',
                'lever_action',
                'pump_action',
                'break_action',
                'other',
            ])->nullable()->after('ignition_type');
            $table->text('description')->nullable()->after('action_type');
        });

        // Enhance calibres table
        Schema::table('calibres', function (Blueprint $table) {
            $table->enum('ignition_type', ['rimfire', 'centerfire'])->default('centerfire')->after('category');
            $table->json('aliases')->nullable()->after('ignition_type')->comment('Alternative names/formats for this calibre');
            $table->boolean('is_common')->default(false)->after('is_active')->comment('Commonly used calibre for quick selection');
            $table->boolean('is_obsolete')->default(false)->after('is_common')->comment('Historical/obsolete calibre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('firearm_types', function (Blueprint $table) {
            $table->dropColumn(['category', 'ignition_type', 'action_type', 'description']);
        });

        Schema::table('calibres', function (Blueprint $table) {
            $table->dropColumn(['ignition_type', 'aliases', 'is_common', 'is_obsolete']);
        });
    }
};
