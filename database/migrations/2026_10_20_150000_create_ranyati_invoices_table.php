<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ranyati_invoices')) {
            return;
        }

        Schema::create('ranyati_invoices', function (Blueprint $table) {
            $table->id();
            $table->char('period', 7)->unique();
            $table->decimal('hosting_fee', 10, 2);
            $table->decimal('member_fee', 10, 2);
            $table->unsignedInteger('new_members');
            $table->unsignedInteger('renewals');
            $table->decimal('total', 12, 2);
            $table->string('invoice_ninja_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ranyati_invoices');
    }
};
