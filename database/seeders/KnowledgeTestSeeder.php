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
                'description' => 'Comprehensive test (57 questions, 169 marks) covering hunting regulations, ethics, wildlife conservation, endangered species, and dedicated hunter responsibilities in South Africa.',
                'passing_score' => 75,
                'time_limit_minutes' => 90, // 1.5 hours as per official test
                'max_attempts' => 3,
                'is_active' => true,
                'dedicated_type' => 'hunter',
            ],
            [
                'slug' => 'dedicated-sport-shooter',
                'name' => 'Dedicated Sport Shooter Knowledge Test',
                'description' => 'Comprehensive test (45 questions, 171 marks) covering sport shooting disciplines, firearm components, ammunition, safe handling, and FCA regulations.',
                'passing_score' => 75,
                'time_limit_minutes' => 90, // 1.5 hours as per official test
                'max_attempts' => 3,
                'is_active' => true,
                'dedicated_type' => 'sport',
            ],
            [
                'slug' => 'dedicated-both',
                'name' => 'Dedicated Hunter & Sport Shooter Knowledge Test',
                'description' => 'Combined comprehensive test (76 questions, 239 marks) for members seeking both Dedicated Hunter and Dedicated Sport Shooter status. Covers all topics from both individual tests.',
                'passing_score' => 75,
                'time_limit_minutes' => 120, // 2 hours as per official test
                'max_attempts' => 3,
                'is_active' => true,
                'dedicated_type' => 'both',
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
