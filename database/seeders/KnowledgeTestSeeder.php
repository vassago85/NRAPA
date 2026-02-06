<?php

namespace Database\Seeders;

use App\Models\KnowledgeTest;
use Illuminate\Database\Seeder;

class KnowledgeTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tests = [
            [
                'slug' => 'member-induction',
                'name' => 'Member Induction Test',
                'description' => 'Basic membership knowledge test covering association rules, responsibilities, and safe firearm handling fundamentals. Required for all new members.',
                'passing_score' => 70,
                'time_limit_minutes' => 30,
                'max_attempts' => 3,
                'is_active' => true,
                'dedicated_type' => null, // General test for all members
            ],
            [
                'slug' => 'dedicated-hunter',
                'name' => 'Dedicated Hunter Knowledge Test',
                'description' => 'Comprehensive test covering hunting regulations, ethics, wildlife conservation, and dedicated hunter responsibilities in South Africa.',
                'passing_score' => 75,
                'time_limit_minutes' => 45,
                'max_attempts' => 3,
                'is_active' => true,
                'dedicated_type' => 'hunter',
            ],
            [
                'slug' => 'dedicated-sport-shooter',
                'name' => 'Dedicated Sport Shooter Knowledge Test',
                'description' => 'Comprehensive test covering sport shooting disciplines, competition rules, range safety, and dedicated sport shooter responsibilities.',
                'passing_score' => 75,
                'time_limit_minutes' => 45,
                'max_attempts' => 3,
                'is_active' => true,
                'dedicated_type' => 'sport',
            ],
            [
                'slug' => 'firearm-safety',
                'name' => 'Firearm Safety Refresher',
                'description' => 'Periodic safety refresher test covering safe handling practices, storage requirements, and legal obligations for firearm owners.',
                'passing_score' => 80,
                'time_limit_minutes' => 20,
                'max_attempts' => 5,
                'is_active' => true,
                'dedicated_type' => null,
            ],
        ];

        $count = 0;
        foreach ($tests as $testData) {
            KnowledgeTest::updateOrCreate(
                ['slug' => $testData['slug']],
                $testData
            );
            $count++;
        }

        $this->command->info("Seeded {$count} knowledge tests.");
    }
}
