<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log for the "we have submitted this year's activities to SAPS" event.
 *
 * One active row per activity year. When an admin clicks "Mark 2026 as
 * submitted to SAPS", a row is written here. From that moment the compliance
 * count for 2026 ignores any activity whose verified_at is after
 * submitted_at — late submissions are still visible on the member's record
 * but do not change the SAPS compliance total.
 *
 * Unlock is a soft delete (deleted_at), so if a mistake is made the audit
 * trail stays intact. The unique index is on (year, deleted_at IS NULL) via
 * a conditional unique at the application level — MySQL does not support
 * partial indexes, so we rely on activeLockFor() + a plain index on year.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saps_year_submissions', function (Blueprint $table) {
            $table->id();
            // The compliance / activity year being locked (e.g. 2026).
            $table->unsignedSmallInteger('year');
            $table->timestamp('submitted_at');
            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('note')->nullable();
            // Snapshot of the approved activity count across all members at
            // the moment of lock, for operational audit.
            $table->unsignedInteger('locked_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('year');
            $table->index('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saps_year_submissions');
    }
};
