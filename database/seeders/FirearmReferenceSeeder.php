<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class FirearmReferenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Importing firearm reference data...');

        Artisan::call('nrapa:import-firearm-reference', [
            '--force' => false,
        ]);

        $this->command->info(Artisan::output());
    }
}
