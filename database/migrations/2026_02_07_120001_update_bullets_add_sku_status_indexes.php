<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bullets', function (Blueprint $table) {
            $table->string('sku_or_part_no', 64)->nullable()->after('twist_note');
            $table->string('status', 16)->default('active')->after('source_url');

            $table->index('manufacturer');
            $table->index('brand_line');
            $table->index('caliber_label');
            $table->index('weight_gr');
            $table->index('construction');
            $table->index('intended_use');
            $table->index('sku_or_part_no');
            $table->index('status');
        });

        // Drop old unique and recreate with sku_or_part_no
        Schema::table('bullets', function (Blueprint $table) {
            $table->dropUnique('uniq_bullet');
            $table->unique(
                ['manufacturer', 'brand_line', 'caliber_label', 'weight_gr', 'sku_or_part_no', 'twist_note', 'bc_reference'],
                'uniq_bullet'
            );
        });
    }

    public function down(): void
    {
        Schema::table('bullets', function (Blueprint $table) {
            $table->dropUnique('uniq_bullet');
            $table->unique(
                ['manufacturer', 'brand_line', 'caliber_label', 'weight_gr', 'twist_note', 'bc_reference'],
                'uniq_bullet'
            );
            $table->dropIndex(['manufacturer']);
            $table->dropIndex(['brand_line']);
            $table->dropIndex(['caliber_label']);
            $table->dropIndex(['weight_gr']);
            $table->dropIndex(['construction']);
            $table->dropIndex(['intended_use']);
            $table->dropIndex(['sku_or_part_no']);
            $table->dropIndex(['status']);
            $table->dropColumn(['sku_or_part_no', 'status']);
        });
    }
};
