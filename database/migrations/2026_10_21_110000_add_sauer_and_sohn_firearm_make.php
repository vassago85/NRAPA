<?php

use App\Models\FirearmMake;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('firearm_makes')) {
            return;
        }

        $name = 'Sauer & Sohn';
        $normalized = FirearmMake::normalize($name);

        $exists = FirearmMake::query()
            ->where('name', $name)
            ->orWhere('normalized_name', $normalized)
            ->exists();

        if ($exists) {
            return;
        }

        FirearmMake::create([
            'name' => $name,
            'normalized_name' => $normalized,
            'country' => 'Germany',
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('firearm_makes')) {
            return;
        }

        FirearmMake::query()->where('name', 'Sauer & Sohn')->delete();
    }
};
