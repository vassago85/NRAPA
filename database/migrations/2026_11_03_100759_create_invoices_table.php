<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NRAPA invoice ledger.
 *
 * One row per invoiced transaction. The number is minted once by
 * App\Services\Billing\InvoiceNumberSequence and never changes. The payload
 * and line_items columns freeze a snapshot of what was invoiced so later
 * downloads reprint the original document even if the member's address or
 * NRAPA's bank details change.
 *
 * source_type / source_id is a polymorphic pointer back to the business
 * record (currently always App\Models\Membership; future phases will add
 * shop orders and event registrations without a schema change). The unique
 * index on that pair enforces "one invoice per transaction".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('number')->unique();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Polymorphic owner. Not using morphs() because we want our own index name.
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');

            $table->timestamp('issued_at');

            $table->char('currency', 3)->default('ZAR');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            // Member's NRAPA-… bank reference at issue time (nullable because
            // non-membership invoices in later phases may not have one).
            $table->string('reference')->nullable();

            $table->json('line_items');
            $table->json('payload');

            // Private-disk path to the cached PDF. Nullable: filled on first render.
            $table->string('pdf_path')->nullable();

            // The admin who first materialised the invoice. Null for backfill /
            // system-created invoices so the audit trail is honest about who clicked.
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['source_type', 'source_id'], 'invoices_source_unique');
            $table->index('issued_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
