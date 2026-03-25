<?php

namespace Database\Seeders;

use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestQuestion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KnowledgeTestQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedSportShooterQuestions();
        $this->seedHunterQuestions();
        $this->seedCombinedQuestions();
    }

    /**
     * Force delete all questions for a test, bypassing foreign key constraints if needed
     */
    protected function clearTestQuestions(KnowledgeTest $test): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete all questions for this test
        KnowledgeTestQuestion::where('knowledge_test_id', $test->id)->delete();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Seed Dedicated Sport Shooter test questions (45 questions, 171 marks)
     * Based on NRAPA SPORT TEST ANSWER SHEET COMBINED.pdf
     */
    protected function seedSportShooterQuestions(): void
    {
        $test = KnowledgeTest::where('slug', 'dedicated-sport-shooter')->first();
        if (! $test) {
            $this->command->error('Sport Shooter test not found. Run KnowledgeTestSeeder first.');

            return;
        }

        // Clear existing questions (force delete)
        $this->clearTestQuestions($test);

        $questions = [
            // Q1 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Complete the sentence: NRAPA Promotes active participation in _________ shooting.',
                'options' => ['A' => 'Pin', 'B' => 'Three-gun', 'C' => 'Practical', 'D' => 'Postal'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q2 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Complete the sentence: NRAPA Promotes - To obey all _________, _________, _________ and practices pertaining to _________ and the private _________ of _________ and ammunition. (Select 6 correct answers)',
                'options' => [
                    'A' => 'Laws',
                    'B' => 'Cases',
                    'C' => 'Primers',
                    'D' => 'Arms',
                    'E' => 'Bullets',
                    'F' => 'Powder',
                    'G' => 'Hunting',
                    'H' => 'Possession',
                    'I' => 'Regulations',
                    'J' => 'Codes of conduct',
                ],
                'correct_answers' => ['A', 'D', 'G', 'H', 'I', 'J'], // Laws, Arms, Hunting, Possession, Regulations, Codes of conduct
                'points' => 6,
            ],
            // Q3 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Sport shooting education is important because it:',
                'options' => [
                    'A' => 'Provides more funding for sport shooting clubs',
                    'B' => 'Discourages less interested people from sport shooting',
                    'C' => 'Takes lots of time to complete',
                    'D' => 'Improves sport shooting skills',
                ],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q4 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The purpose of sport shooter education is to produce safe, responsible, knowledgeable and involved sport shooters.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q5 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the Fundamental NRAPA Rules for Safe Gun Handling: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Know your target and what is beyond',
                    'B' => 'Know how to use the gun safely',
                    'C' => 'Always keep the safety on',
                    'D' => 'Be sure the gun is safe to operate',
                    'E' => 'Use only the correct ammunition for your gun',
                    'F' => 'Only shoot in daylight',
                    'G' => 'Wear eye and ear protection as appropriate',
                    'H' => 'Always wear gloves',
                    'I' => 'Never use alcohol or drugs before or while shooting',
                    'J' => 'Keep the gun loaded at all times',
                ],
                'correct_answers' => ['A', 'B', 'D', 'E', 'G', 'I'],
                'points' => 6,
            ],
            // Q6 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Disciplinary Action shall exist for: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Contraventions of all laws pertaining to conservation, hunting, firearms and ammunition',
                    'B' => 'Failure to pay annual membership fees',
                    'C' => 'Breaches of the Code of Ethics',
                    'D' => 'Not attending monthly meetings',
                    'E' => 'Conduct which brings or is likely to bring the Association into disrepute',
                    'F' => 'Using non-NRAPA branded equipment',
                ],
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q7.1 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm in an approved safe/strong room on your premises provided that you are a holder of a legally licensed firearm/s:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q7.2 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm provided that you are a police officer:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q7.3 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm provided that you have a letter from the owner countersigned by the local DFO stating the period of storage (SAPS 539):',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q8 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Unless you have "dedicated status" you are restricted to _______ rounds of ammunition per licensed firearm, and a maximum of 2400 primers, unless you have written permission from the Registrar.',
                'options' => ['A' => '100', 'B' => '150', 'C' => '99', 'D' => '200'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q9 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The FCA adopts a broad definition of "firearm," which includes:',
                'options' => [
                    'A' => 'Any device that can "propel a bullet or projectile through a barrel or cylinder by means of burning propellant, at a muzzle energy exceeding 8 joules (6 ft-lbs)"',
                    'B' => 'A spear',
                    'C' => 'Bow and arrow',
                    'D' => 'Slingshot',
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q10 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The FCA excludes various devices that would otherwise be considered firearms under this definition:',
                'options' => [
                    'A' => 'Shotgun',
                    'B' => 'Rifle',
                    'C' => 'Explosive-powered tools designed for industrial application for splitting rocks or concrete',
                ],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q11 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: In South Africa, the right to possess firearms is guaranteed by law.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q12 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Certain firearms are categorized as prohibited firearms and cannot ordinarily be possessed or licensed under the FCA. These include any: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Semi-automatic firearm',
                    'B' => 'Projectile or rocket manufactured to be discharged from a cannon, recoilless gun or mortar, or rocket launcher',
                    'C' => 'Gun, cannon, recoilless gun, mortar, light mortar or launcher manufactured to fire a rocket, grenade, self-propelled grenade, bomb, or explosive device',
                    'D' => 'Manual operated rifle or carbine',
                    'E' => 'Altered firearm',
                    'F' => '12 gauge pump action shotgun',
                    'G' => 'Fully automatic firearm',
                ],
                'correct_answers' => ['B', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q13 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: A competency certificate to possess a firearm, trade in firearms, manufacture firearms, or open a gunsmith business is valid for as long as the license to which it relates remains valid, unless the certificate is terminated or renewed.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q14 - Matching (10 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each definition to the correct term: (10 marks)',
                'options' => [
                    'A' => 'A person is permitted to hold only one license of this kind.',
                    'B' => 'Any natural person who is an occasional hunter or sports person is eligible for a maximum of four ten-year-term licenses.',
                    'C' => 'The FCA imposes certain requirements for applicants in this category - must be a member of an accredited hunting association or sport-shooting organization.',
                    'D' => 'The firearm must be one approved for collection by an accredited collectors association.',
                    'E' => 'Applicants are required to submit a written motivation for their use of the firearm.',
                    'F' => 'Violation or failure to comply with the provisions of the FCA or the terms of a license, permit, or authorization is an offense.',
                    'G' => 'Means to kill by means of a firearm only and by no other means.',
                    'H' => 'Proper storage of firearms and ammunition in a prescribed safe or strong room is a prerequisite.',
                    'I' => 'An air gun, a tranquiliser firearm, a paintball gun, a flare gun, a deactivated firearm, an antique firearm, any captive bolt gun.',
                    'J' => 'A complete object consisting of a cartridge case, primer, propellant and bullet.',
                ],
                'correct_answers' => [
                    'A' => 'License for self-defense',
                    'B' => 'License for Occasional Hunting/Sport Shooting',
                    'C' => 'License for Dedicated Hunting/Sport Shooting',
                    'D' => 'License in Private Collection',
                    'E' => 'Temporary Authorization',
                    'F' => 'Offenses and Penalties',
                    'G' => 'Shoot',
                    'H' => 'Safekeeping',
                    'I' => 'Devices not regarded as firearms',
                    'J' => 'Cartridge',
                    '_distractors' => ['Competency Certificate', 'Firearm Dealer License', 'Import Permit'],
                ],
                'points' => 10,
            ],
            // Q15a - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Licence to possess a firearm for self-defense is valid for how many years?',
                'options' => ['A' => 'Two', 'B' => 'Five', 'C' => 'Ten', 'D' => 'Fifteen'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q15b - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Licence to possess a restricted firearm for self-defense is valid for how many years?',
                'options' => ['A' => 'Two', 'B' => 'Five', 'C' => 'Ten', 'D' => 'Fifteen'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q15c - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Licence to possess a firearm for occasional hunting/sport shooting is valid for how many years?',
                'options' => ['A' => 'Two', 'B' => 'Five', 'C' => 'Ten', 'D' => 'Fifteen'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q16 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List four main types of shooting related incidents: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Walking fast with a firearm',
                    'B' => 'Lack of control of the firearm',
                    'C' => 'Human error and or judgment mistakes',
                    'D' => 'Safety rule violations',
                    'E' => 'Be sure the gun is safe to operate',
                    'F' => 'Equipment or ammunition failure',
                    'G' => 'Know your target and what is beyond',
                    'H' => 'When holding a gun, rest your finger on the trigger guard or along the side of the gun',
                ],
                'correct_answers' => ['B', 'C', 'D', 'F'],
                'points' => 4,
            ],
            // Q17 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'The fundamental NRAPA rules for safe gun handling are: (Select 4 correct answers)',
                'options' => [
                    'A' => 'ALWAYS make sure the safety is engaged',
                    'B' => 'Human error and or judgment mistakes',
                    'C' => 'ALWAYS keep the gun pointed in a safe direction',
                    'D' => 'Equipment or ammunition failure',
                    'E' => 'ALWAYS keep the gun unloaded until ready to use',
                    'F' => 'When holding a gun, rest',
                    'G' => 'ALWAYS keep your finger off the trigger until ready to shoot',
                    'H' => 'Safety rule violations',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q19 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing a Fence – Recommended action to be taken:',
                'options' => [
                    'A' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence. Check barrel for debris. If necessary reload and continue with stalk.',
                    'B' => 'Place the rifle through the fence holding the grip. The rifle must be pointed towards yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence. Check barrel for debris. If necessary reload and continue with stalk.',
                    'C' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Climb through the fence with the rifle still in your hand. Check barrel for debris. If necessary reload and continue with stalk.',
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q20 - Multiple select (8 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the rifle carry techniques: (Select 8 correct answers)',
                'options' => [
                    'A' => 'Sling carry',
                    'B' => 'Cradle carry',
                    'C' => 'Hip carry',
                    'D' => 'Elbow or side carry',
                    'E' => 'Shoulder carry',
                    'F' => 'Two Handed ready carry',
                    'G' => 'Overhead carry',
                    'H' => 'Safe carry in a group',
                    'I' => 'Barrel-first carry',
                    'J' => 'Walking side by side',
                    'K' => 'Walking in single file',
                    'L' => 'Muzzle-down drag',
                ],
                'correct_answers' => ['A', 'B', 'D', 'E', 'F', 'H', 'J', 'K'],
                'points' => 8,
            ],
            // Q21 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the rifle carrying fundamentals: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Keep the safety in the "on" position while carrying',
                    'B' => 'Always carry the firearm unloaded',
                    'C' => 'Only change safety to fire when ready to shoot',
                    'D' => 'Keep the rifle in a case when walking',
                    'E' => 'Always keep your finger outside the trigger guard',
                    'F' => 'Rest the rifle on your shoulder at all times',
                    'G' => 'Keep muzzle pointed in a safe direction and barrel under control',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q22 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the shooting positions: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Standing',
                    'B' => 'Running',
                    'C' => 'Kneeling',
                    'D' => 'Crouching',
                    'E' => 'Sitting',
                    'F' => 'Lying on side',
                    'G' => 'Prone',
                    'H' => 'Hanging',
                    'I' => 'Squatting',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q23 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List the three MAIN parts of a firearm: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Butt plate',
                    'B' => 'Scope',
                    'C' => 'Stock',
                    'D' => 'Recoil pad',
                    'E' => 'Action',
                    'F' => 'Sling',
                    'G' => 'Barrel',
                    'H' => 'Cheek piece',
                    'I' => 'Swivel',
                ],
                'correct_answers' => ['C', 'E', 'G'],
                'points' => 3,
            ],
            // Q24 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'The following are all types of actions: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Canon',
                    'B' => 'Lever',
                    'C' => 'Break or hinge',
                    'D' => 'Bolt',
                    'E' => 'Pump',
                    'F' => 'Spear',
                    'G' => 'Barrel',
                    'H' => 'Sling shot',
                    'I' => 'Semi-Auto',
                ],
                'correct_answers' => ['B', 'C', 'D', 'E', 'I'],
                'points' => 5,
            ],
            // Q25 - Matching (8 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each definition to the correct firearm component: (8 marks)',
                'options' => [
                    'A' => 'A gun barrel is the tube, usually metal, through which a controlled explosion or rapid expansion of gases are released in order to propel a projectile out of the end at a high velocity.',
                    'B' => 'Loads and fires ammunition',
                    'C' => 'Serves as a platform for supporting the action and barrel',
                    'D' => 'A trigger is a mechanism that actuates the firing of firearms.',
                    'E' => 'In firearms, a safety or safety catch is a mechanism used to help prevent the accidental discharge of a firearm, helping to ensure safer handling',
                    'F' => 'Part of the barrel from which the projectile emerges',
                    'G' => 'Rifling is often described by its twist rate',
                    'H' => 'A trigger guard is a loop surrounding the trigger of a firearm and protecting it from accidental discharge',
                ],
                'correct_answers' => [
                    'A' => 'Barrel',
                    'B' => 'Action',
                    'C' => 'Stock',
                    'D' => 'Trigger',
                    'E' => 'Safety',
                    'F' => 'Muzzle',
                    'G' => 'Rifling',
                    'H' => 'Trigger guard',
                    '_distractors' => ['Magazine', 'Hammer', 'Bolt'],
                ],
                'points' => 8,
            ],
            // Q26.1 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The rifle barrel is long and has thick walls with spiralling grooves cut into the bore. The grooved pattern is called rifling.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q26.2 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The shotgun barrel is long and made of fairly thin steel that is very smooth on the inside to allow the shot and wad to glide down the barrel without friction.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q26.3 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The handgun barrel is much shorter than a rifle or shotgun barrel because the gun is designed to be shot while being held with one or two hands.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q27 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Name the four types of safeties: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Bottom safety',
                    'B' => 'Cross-Bolt Safety',
                    'C' => 'Pivot Safety',
                    'D' => 'Stock standard safety',
                    'E' => 'Manual safety',
                    'F' => 'Slide or Tang Safety',
                    'G' => 'Carry the firearm pointing upwards',
                    'H' => 'Half-Cock or Hammer Safety',
                    'I' => 'Pull the trigger before you clean the firearm',
                ],
                'correct_answers' => ['B', 'C', 'F', 'H'],
                'points' => 4,
            ],
            // Q28 - Matching (9 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each description to the correct firearm component: (9 marks)',
                'options' => [
                    'A' => 'The portion of a firearm that wraps around the trigger to provide both protection and safety.',
                    'B' => 'The area of the firearm that contains the rear end of the barrel, where the cartridge is inserted.',
                    'C' => 'The front end of the barrel where the projectile exits the firearm.',
                    'D' => 'The part of a revolver that holds cartridges in separate chambers.',
                    'E' => 'The lever that\'s pulled or squeezed to initiate the firing process.',
                    'F' => 'The part that strikes the firing pin or the cartridge primer directly.',
                    'G' => 'A spring-operated container that holds cartridges for a repeating firearm.',
                    'H' => 'The portion of a handgun that\'s used to hold the firearm.',
                    'I' => 'The inside of the gun\'s barrel through which the projectile travels when fired.',
                ],
                'correct_answers' => [
                    'A' => 'Trigger Guard',
                    'B' => 'Breech',
                    'C' => 'Muzzle',
                    'D' => 'Cylinder',
                    'E' => 'Trigger',
                    'F' => 'Hammer',
                    'G' => 'Magazine',
                    'H' => 'Grip',
                    'I' => 'Bore',
                    '_distractors' => ['Stock', 'Barrel', 'Safety'],
                ],
                'points' => 9,
            ],
            // Q29 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The following is a description of what action - A type of firearm which, utilizing some of the recoil or some of the expanding-gas energy from the firing cartridge, cycles the action to eject the spent shell, to chamber a fresh one from a magazine and to cock the mainspring, placing the gun in position for another shot.',
                'options' => [
                    'A' => 'Bolt',
                    'B' => 'Pump',
                    'C' => 'Lever',
                    'D' => 'Semi-Auto',
                ],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q30 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the major parts of a shotgun: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Action (lock)',
                    'B' => 'Scope',
                    'C' => 'Stock',
                    'D' => 'Magazine',
                    'E' => 'Barrel',
                    'F' => 'Silencer',
                    'G' => 'Bipod',
                ],
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q31 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the different actions found in shotguns: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Pump-action',
                    'B' => 'Lever action',
                    'C' => 'Semi-automatic',
                    'D' => 'Revolver action',
                    'E' => 'Bolt action',
                    'F' => 'Gas-delayed blowback',
                    'G' => 'Hinge/break action',
                    'H' => 'Falling block',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q32 - Multiple select (2 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the two very common safeties in shotguns: (Select 2 correct answers)',
                'options' => [
                    'A' => 'The Tang',
                    'B' => 'Magazine safety',
                    'C' => 'Crossbolt',
                    'D' => 'Trigger lock',
                    'E' => 'Pivot safety',
                    'F' => 'Grip safety',
                ],
                'correct_answers' => ['A', 'C'],
                'points' => 2,
            ],
            // Q33 - Multiple select (2 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the two common types of actions used in sport shooting handguns: (Select 2 correct answers)',
                'options' => [
                    'A' => 'Single action',
                    'B' => 'Bolt action',
                    'C' => 'Double action',
                    'D' => 'Pump action',
                    'E' => 'Lever action',
                    'F' => 'Gas action',
                ],
                'correct_answers' => ['A', 'C'],
                'points' => 2,
            ],
            // Q34 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the typical cartridge malfunctions: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Misfire',
                    'B' => 'Overfire',
                    'C' => 'Hangfire',
                    'D' => 'Backfire',
                    'E' => 'Squib Load',
                    'F' => 'Flashfire',
                    'G' => 'Barrel burst',
                    'H' => 'Double feed',
                ],
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q35 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List the steps for cleaning a firearm: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Re-load the firearm',
                    'B' => 'Safely unload the firearm',
                    'C' => 'Remove all ammunition from the cleaning area',
                    'D' => 'Store in a clean safe place',
                    'E' => 'Always keep your safe locked',
                    'F' => 'Use cloth and gun cleaning solvents to remove dirt, powder residue, skin oils and moisture from all metal parts of the firearm, including the action',
                    'G' => 'Carry the firearm pointing upwards',
                    'H' => 'Use cleaning rods, brushes, patches and solvent to clean the bore',
                    'I' => 'Pull the trigger before you clean the firearm',
                    'J' => 'Disassemble the firearm for more thorough cleaning',
                    'K' => 'Apply a coating of gun oil to protect the firearm from rust',
                    'L' => 'Place the firearm upright',
                ],
                'correct_answers' => ['B', 'C', 'F', 'H', 'J', 'K'],
                'points' => 6,
            ],
            // Q36 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Rifle and Pistol Cartridge consist of four components: (Select 4 correct answers)',
                'options' => [
                    'A' => 'The primer',
                    'B' => 'Lever',
                    'C' => 'The projectile (bullet)',
                    'D' => 'Spear',
                    'E' => 'Pump',
                    'F' => 'The case or shell',
                    'G' => 'The powder (black powder replaced later by smokeless black powder)',
                ],
                'correct_answers' => ['A', 'C', 'F', 'G'],
                'points' => 4,
            ],
            // Q37 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'A Shotgun shell consists of: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Hull',
                    'B' => 'Bolt action',
                    'C' => 'Primer',
                    'D' => 'Barrel',
                    'E' => 'The powder',
                    'F' => 'Extractor',
                    'G' => 'Wad',
                    'H' => 'Shot',
                    'I' => 'The projectile (bullet)',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G', 'H'],
                'points' => 5,
            ],
            // Q38 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Common shotgun gauges are: (Select 5 correct answers)',
                'options' => [
                    'A' => '10G',
                    'B' => '24G',
                    'C' => '16G',
                    'D' => '5G',
                    'E' => '12G',
                    'F' => '31G',
                    'G' => '20G',
                    'H' => '28G',
                    'I' => '18G',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G', 'H'],
                'points' => 5,
            ],
            // Q39 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the basic parts of a bullet: (Select 4 correct answers)',
                'options' => [
                    'A' => 'The Base',
                    'B' => 'The Primer',
                    'C' => 'The Shank',
                    'D' => 'The Cannelure',
                    'E' => 'The Ogive',
                    'F' => 'The Jacket',
                    'G' => 'The Meplat',
                    'H' => 'The Rim',
                    'I' => 'The Casing',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q40 - Matching (5 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each definition to the correct ballistics term: (5 marks)',
                'options' => [
                    'A' => 'An object set in motion by an exterior force and continuing under its own inertia.',
                    'B' => 'The study of the path of projectiles, particularly those shot from artillery or firearms.',
                    'C' => 'The distance a bullet travels in the barrel while making one revolution.',
                    'D' => 'Without air resistance, a projectile would not change velocity until it hit something.',
                    'E' => 'The curve a projectile describes in space.',
                ],
                'correct_answers' => [
                    'A' => 'Projectile',
                    'B' => 'Ballistics',
                    'C' => 'Twist',
                    'D' => 'Air Resistance',
                    'E' => 'Trajectory',
                    '_distractors' => ['Recoil', 'Muzzle Velocity', 'Gravity'],
                ],
                'points' => 5,
            ],
            // Q41 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'There are five different general shapes of hunting bullets: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Flat Point',
                    'B' => 'Rimfire',
                    'C' => 'Boat-Tail Spitzer',
                    'D' => 'Lead point',
                    'E' => 'Semi-Spitzer',
                    'F' => 'Pellet',
                    'G' => 'Round Nose',
                    'H' => 'Spitzer',
                    'I' => 'The projectile (bullet)',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G', 'H'],
                'points' => 5,
            ],
            // Q42 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Name the common handgun bullets: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Wadcutter',
                    'B' => 'Rimfire',
                    'C' => 'Lead hollow point',
                    'D' => 'Lead point',
                    'E' => 'Full metal Jacket',
                    'F' => 'Partition',
                    'G' => 'Soft point',
                    'H' => 'Hollow point',
                    'I' => 'The projectile (bullet)',
                ],
                'correct_answers' => ['A', 'C', 'D', 'E', 'G', 'H'],
                'points' => 6,
            ],
            // Q43 - Matching (6 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each description to the correct term: (6 marks)',
                'options' => [
                    'A' => 'The curve a projectile describes in space.',
                    'B' => 'The study of the path of projectiles, particularly those shot from artillery or firearms.',
                    'C' => 'An object set in motion by an exterior force and continuing under its own inertia.',
                    'D' => 'Without gravity, a projectile would travel in a straight line until it hit something.',
                    'E' => 'Without air resistance, a projectile would not change velocity until it hit something.',
                    'F' => 'The distance a bullet travels in the barrel while making one revolution.',
                ],
                'correct_answers' => [
                    'A' => 'Trajectory',
                    'B' => 'Ballistics',
                    'C' => 'Projectile',
                    'D' => 'Gravity',
                    'E' => 'Air Resistance',
                    'F' => 'Twist',
                    '_distractors' => ['Recoil', 'Muzzle Velocity'],
                ],
                'points' => 6,
            ],
            // Q44 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the statements about prohibited firearms and ammunition: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Tracer ammunition may not be used',
                    'B' => 'Hollow-point ammunition is banned from ranges',
                    'C' => 'Fully automatic firearms may not be fired on full automatic',
                    'D' => 'Black powder firearms are prohibited',
                    'E' => 'Any gun, cannon, mortar or launcher for rockets, grenades or bombs may not be fired on the range',
                    'F' => 'Shotguns may not be used on rifle ranges',
                ],
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q45 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'There are three basic categories of shooting ranges: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Underground',
                    'B' => 'Indoor ranges',
                    'C' => 'Trajectory',
                    'D' => 'Outdoor no danger area ranges',
                    'E' => 'Outdoor danger area ranges',
                    'F' => 'Mine dumps',
                    'G' => 'Open fields',
                    'H' => 'River beds',
                ],
                'correct_answers' => ['B', 'D', 'E'],
                'points' => 3,
            ],
        ];

        $sortOrder = 1;
        $totalPoints = 0;
        foreach ($questions as $q) {
            KnowledgeTestQuestion::create([
                'knowledge_test_id' => $test->id,
                'question_type' => $q['question_type'],
                'question_text' => $q['question_text'],
                'options' => $q['options'] ?? null,
                'correct_answer' => $q['correct_answer'] ?? null,
                'correct_answers' => $q['correct_answers'] ?? null,
                'points' => $q['points'],
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
            $totalPoints += $q['points'];
        }

        $this->command->info('Seeded '.count($questions)." questions ({$totalPoints} total points) for Sport Shooter test.");
    }

    /**
     * Seed Dedicated Hunter test questions
     * TODO: Update from Hunter PDF when provided
     */
    /**
     * Seed Dedicated Hunter test questions
     * Based on hunter-specific questions from NRAPA SPORT HUNTING TEST ANSWER SHEET.pdf
     */
    protected function seedHunterQuestions(): void
    {
        $test = KnowledgeTest::where('slug', 'dedicated-hunter')->first();
        if (! $test) {
            $this->command->error('Hunter test not found. Run KnowledgeTestSeeder first.');

            return;
        }

        // Clear existing questions (force delete)
        $this->clearTestQuestions($test);

        $questions = [
            // Core firearm knowledge (shared with sport shooter)
            // Q1 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the six Fundamental NRAPA Rules for Safe Gun Handling:',
                'options' => [
                    'A' => 'Know your target and what is beyond',
                    'B' => 'Always keep the safety on',
                    'C' => 'Know how to use the gun safely',
                    'D' => 'Be sure the gun is safe to operate',
                    'E' => 'Only shoot in daylight',
                    'F' => 'Use only the correct ammunition for your gun',
                    'G' => 'Always wear gloves',
                    'H' => 'Wear eye and ear protection as appropriate',
                    'I' => 'Keep the gun loaded at all times',
                    'J' => 'Never use alcohol or over-the-counter, prescription or other drugs before or while shooting',
                ],
                'correct_answers' => ['A', 'C', 'D', 'F', 'H', 'J'],
                'points' => 6,
            ],
            // Q2 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Disciplinary Action shall exist for: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Contraventions of all laws pertaining to conservation, hunting, firearms and ammunition',
                    'B' => 'Failure to pay annual membership fees',
                    'C' => 'Breaches of the Code of Ethics',
                    'D' => 'Not attending monthly meetings',
                    'E' => 'Conduct which brings or is likely to bring the Association, hunting and the private possession of firearms and ammunition into disrepute',
                    'F' => 'Using non-NRAPA branded equipment',
                ],
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q3 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List the three MAIN parts of a firearm: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Butt plate', 'B' => 'Scope', 'C' => 'Stock',
                    'D' => 'Recoil pad', 'E' => 'Action', 'F' => 'Sling',
                    'G' => 'Barrel', 'H' => 'Cheek piece', 'I' => 'Swivel',
                ],
                'correct_answers' => ['C', 'E', 'G'],
                'points' => 3,
            ],
            // Q4 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'The following are all types of actions: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Canon', 'B' => 'Lever', 'C' => 'Break or hinge',
                    'D' => 'Bolt', 'E' => 'Pump', 'F' => 'Speer',
                    'G' => 'Barrel', 'H' => 'Sling shot', 'I' => 'Semi auto',
                ],
                'correct_answers' => ['B', 'C', 'D', 'E', 'I'],
                'points' => 5,
            ],
            // Q5 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List the steps to safely cleaning a firearm: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Re-load the firearm',
                    'B' => 'Safely unload the firearm',
                    'C' => 'Remove all ammunition from the cleaning area',
                    'D' => 'Store in a clean safe place',
                    'E' => 'Always keep your safe locked',
                    'F' => 'Use cloth and gun cleaning solvents to remove dirt, powder residue, skin oils and moisture from all metal parts',
                    'G' => 'Carry the firearm pointing upwards',
                    'H' => 'Use cleaning rods, brushes, patches and solvent to clean the bore',
                    'I' => 'Pull the trigger before you clean the firearm',
                    'J' => 'Disassemble the firearm for more thorough cleaning',
                    'K' => 'Apply a coating of gun oil to protect the firearm from rust',
                    'L' => 'Place the firearm upright',
                ],
                'correct_answers' => ['B', 'C', 'F', 'H', 'J', 'K'],
                'points' => 6,
            ],
            // HUNTER SPECIFIC QUESTIONS
            // Q6 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Complete the sentence: NRAPA Promotes at all times to honor the ethic of "_________" to ensure the humane harvesting of game.',
                'options' => [
                    'A' => 'Multiple shot humane kill',
                    'B' => 'Single shot inhumane kill',
                    'C' => 'Single shot humane kill',
                ],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q7 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Hunter education is important because it:',
                'options' => [
                    'A' => 'Provides more funding for wildlife agencies',
                    'B' => 'Discourages less interested people from going hunting',
                    'C' => 'Takes lots of time to complete',
                    'D' => 'Improves hunter behaviour and makes hunters safer',
                ],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q8 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'As an ethical hunter, I will: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Actively support legal, safe and ethical hunting',
                    'B' => 'Discourages fellow sport shooters from going hunting',
                    'C' => 'Participate in sport shooting events',
                    'D' => 'Show respect for all wildlife and the environment that sustains them',
                    'E' => 'Only hunt with factory ammunition',
                    'F' => 'Take responsibility for my actions',
                    'G' => 'Frequently visit the shooting range',
                    'H' => 'Report vandalism, hunting violations or poaching to the local authorities',
                    'I' => 'Show respect for myself and other people, including landowners, fellow hunters and non-hunters',
                    'J' => 'Know and obey the laws and regulations for hunting',
                ],
                'correct_answers' => ['A', 'D', 'F', 'H', 'I', 'J'],
                'points' => 6,
            ],
            // Q9 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The purpose of hunter education is to produce safe, responsible, knowledgeable and involved hunters.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q10 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Complete the sentence: NRAPA Promotes the sustainable utilisation of wildlife as a _________ tool and promotes _________, _________ hunting. (Select 3 correct answers)',
                'options' => [
                    'A' => 'Conservation', 'B' => 'Wild life', 'C' => 'Hunting',
                    'D' => 'Ethical', 'E' => 'Shooting', 'F' => 'Responsible',
                ],
                'correct_answers' => ['A', 'D', 'F'],
                'points' => 3,
            ],
            // Q11 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Complete the sentence: ______ chase balances the skills and equipment of the hunter with the abilities of the animal to escape.',
                'options' => ['A' => 'Unfair', 'B' => 'Responsible', 'C' => 'Fair', 'D' => 'Controlled'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q12 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List the Protected or endangered species categories: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Critically Endangered Species', 'B' => 'Critically Dangerous Species',
                    'C' => 'Water Species', 'D' => 'Endangered Species',
                    'E' => 'Vulnerable Species', 'F' => 'Sub Species',
                    'G' => 'Protected Species', 'H' => 'Unprotected Species',
                    'I' => 'Dangerous Water species', 'J' => 'Conservation status of huntable species',
                ],
                'correct_answers' => ['A', 'D', 'E', 'G', 'J'],
                'points' => 5,
            ],
            // Q13 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'What does CITES stand for?',
                'options' => [
                    'A' => 'Convention on Local Trade in Endangered Species of Wild Fauna and Flora',
                    'B' => 'Convention on International Trade in Endangered Species of Wild Fauna and Flora',
                    'C' => 'Convention on International Trade in Dangerous Species of Wild Fauna and Flora',
                ],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q14 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Blue Swallow a Critically Endangered Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q15 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Mountain Zebra an Endangered Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q16 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Cheetah a Vulnerable Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q17 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The Elephant is a Protected Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q18 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Black Wildebeest has Conservation status of huntable species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q19 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'No _______ hunting of listed large predators, white rhino, black rhino, crocodile or elephant.',
                'options' => ['A' => 'Rifle', 'B' => 'Bow'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q20 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: No use of flood or spot lights, except for controlling damage causing animals - leopards and hyenas.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q21 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The hunting of captive-bred "listed large predators" is prohibited if the animal has not been released from captivity and been self-sustainable for at least 24 months.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q22 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The hunting of captive-bred "listed large predators" is prohibited by use of a gin (leghold) trap.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q23 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: No darting, except by a vet or person authorized by the vet for veterinary, scientific, management or transport purposes.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q24 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: For any hunting of any nature, even animals classified as "problem animals", by anyone other than the landowner and his immediate family no written permission of the landowner is required.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q25 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The use of semi-automatic or self-loading rifles to hunt ordinary or protected game is permitted.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q26 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The director of _________ is empowered to issue special permits to make hunting legal under a variety of unusual circumstances.',
                'options' => ['A' => 'Finance', 'B' => 'Security', 'C' => 'Human resources', 'D' => 'Nature Conservation'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q27 - Multiple select (2 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Complete the sentence: The use of semi-automatic or self-loading rifles to hunt _________ or _________ game is prohibited. (Select 2 correct answers)',
                'options' => ['A' => 'Unprotected', 'B' => 'Common', 'C' => 'Ordinary', 'D' => 'Protected'],
                'correct_answers' => ['C', 'D'],
                'points' => 2,
            ],
            // Q28 - Multiple select (2 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Complete the sentence: The use of semi-automatic or self-loading rifles may be used to hunt _________ and _________. (Select 2 correct answers)',
                'options' => ['A' => 'Wild animals which is not game', 'B' => 'Ordinary', 'C' => 'Protected', 'D' => 'Problem animals'],
                'correct_answers' => ['A', 'D'],
                'points' => 2,
            ],
            // Q29 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List Four main types of hunting related shooting incidents: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Walking fast with a firearm',
                    'B' => 'Lack of control of the firearm',
                    'C' => 'Human error and or judgment mistakes',
                    'D' => 'Safety rule violations',
                    'E' => 'Be sure the gun is safe to operate',
                    'F' => 'Equipment or ammunition failure',
                    'G' => 'Know your target and what is beyond',
                    'H' => 'When holding a gun, rest your finger on the trigger guard',
                ],
                'correct_answers' => ['B', 'C', 'D', 'F'],
                'points' => 4,
            ],
            // Q30 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing a Fence – Recommended action to be taken:',
                'options' => [
                    'A' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence. Check barrel for debris. If necessary reload and continue with stalk.',
                    'B' => 'Place the rifle through the fence holding the grip. The rifle must be pointed towards yourself and others. Place on the other side of the fence. Climb through the fence. Check barrel for debris.',
                    'C' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Climb through the fence with the rifle still in your hand.',
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q31 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Rifle carrying techniques: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Elbow or side carry', 'B' => 'Hanging loose', 'C' => 'Sling carry',
                    'D' => 'Butt carry', 'E' => 'Cradle carry', 'F' => 'Shoulder carry',
                    'G' => 'Barrel carry', 'H' => 'Two Handed ready carry',
                ],
                'correct_answers' => ['A', 'C', 'E', 'F', 'H'],
                'points' => 5,
            ],
            // Q32 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Types of shots: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Frontal', 'B' => 'In the rumen', 'C' => 'Broad side',
                    'D' => 'Behind', 'E' => 'Quartering forward', 'F' => 'Neck',
                    'G' => 'Quartering away', 'H' => 'Head', 'I' => 'Back',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q33 - Track A identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track A: Identify the animal from this track (round paw, 4 toes, no claw marks):',
                'options' => ['A' => 'Dog', 'B' => 'Leopard', 'C' => 'Hyena', 'D' => 'Sitatunga'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q34 - Track B identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track B: Identify the animal from this track (large print, 4 round toes spread wide):',
                'options' => ['A' => 'Rhino', 'B' => 'Elephant', 'C' => 'Hippo', 'D' => 'Buffalo'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q35 - Track C identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track C: Identify the animal from this track (massive 3-toed print):',
                'options' => ['A' => 'Hippo', 'B' => 'Elephant', 'C' => 'Buffalo', 'D' => 'Rhino'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q36 - Track D identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track D: Identify the animal from this track (single oval hoof):',
                'options' => ['A' => 'Mountain Zebra', 'B' => 'Burchell\'s Zebra', 'C' => 'Warthog', 'D' => 'Eland'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q37 - Track E identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track E: Identify the animal from this track (2 pointed hooves with dew claws):',
                'options' => ['A' => 'Impala', 'B' => 'Blesbuck', 'C' => 'Warthog', 'D' => 'Bushpig'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q38 - Track F identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track F: Identify the animal from this track (elongated splayed hooves):',
                'options' => ['A' => 'Gemsbuck', 'B' => 'Nyala', 'C' => 'Lechwe', 'D' => 'Sitatunga'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q39 - Direction of travel (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Which direction is the animal walking based on the tracks?',
                'options' => ['A' => 'Left to right', 'B' => 'Right to left'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q35 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'The first three survival priorities are: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Eat a lot of berries',
                    'B' => 'Find water',
                    'C' => 'Take shelter',
                    'D' => 'Swim to cool down',
                    'E' => 'To keep warm (or cool)',
                ],
                'correct_answers' => ['B', 'C', 'E'],
                'points' => 3,
            ],
            // Q36 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'A fire making kit should consist of: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Water', 'B' => 'Lighter', 'C' => 'Ammunition', 'D' => 'Matches',
                    'E' => 'Knife', 'F' => 'Rope', 'G' => 'Steel wool/battery', 'H' => 'Insect repellant',
                    'I' => 'Magnifying glass', 'J' => 'Magnesium bar',
                ],
                'correct_answers' => ['B', 'D', 'G', 'I', 'J'],
                'points' => 5,
            ],
            // Q37 - Matching (7 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each first-aid term to its correct definition:',
                'options' => [
                    'A' => 'A medical condition where the body\'s vital organs do not receive enough blood flow, causing weakness, rapid pulse, and pale skin.',
                    'B' => 'A temporary loss of consciousness caused by a drop in blood pressure, often due to pain, emotional stress, or overheating.',
                    'C' => 'Blood loss from a wound visible on the body surface; apply direct pressure and elevate the injured area.',
                    'D' => 'The application of a strip of material to a wound to hold a dressing in place, reduce bleeding, or support an injured limb.',
                    'E' => 'Tissue damage caused by heat, chemicals, electricity, or radiation; cool with running water for at least 20 minutes.',
                    'F' => 'A viral disease transmitted through the bite of an infected animal; always seek immediate medical attention after an animal bite.',
                    'G' => 'Small parasites that attach to the skin and feed on blood; remove carefully by grasping close to the skin and pulling steadily.',
                ],
                'correct_answers' => [
                    'A' => 'Shock',
                    'B' => 'Fainting',
                    'C' => 'External Bleeding',
                    'D' => 'Bandaging',
                    'E' => 'Burn',
                    'F' => 'Rabies',
                    'G' => 'Ticks',
                    '_distractors' => ['Heatstroke', 'Fracture', 'Dehydration'],
                ],
                'points' => 7,
            ],
            // Q38 - Multiple select (8 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select all the rifle carry techniques: (Select 8 correct answers)',
                'options' => [
                    'A' => 'Sling carry',
                    'B' => 'Hip carry',
                    'C' => 'Cradle carry',
                    'D' => 'Elbow or side carry',
                    'E' => 'Overhead carry',
                    'F' => 'Shoulder carry',
                    'G' => 'Two Handed ready carry',
                    'H' => 'Barrel-first carry',
                    'I' => 'Safe carry in a group',
                    'J' => 'Walking side by side',
                    'K' => 'Muzzle-down drag',
                    'L' => 'Walking in single file',
                ],
                'correct_answers' => ['A', 'C', 'D', 'F', 'G', 'I', 'J', 'L'],
                'points' => 8,
            ],
            // Q39 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the rifle carrying fundamentals: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Keep the safety in the "on" position while carrying a firearm',
                    'B' => 'Always carry the firearm unloaded',
                    'C' => 'Only change the position of the safety to fire when you are ready to shoot',
                    'D' => 'Keep the rifle in a case when walking',
                    'E' => 'Always keep your finger outside the trigger guard',
                    'F' => 'Rest the rifle on your shoulder at all times',
                    'G' => 'Keep muzzle pointed in a safe direction and the barrel under control',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
        ];

        $sortOrder = 1;
        $totalPoints = 0;
        foreach ($questions as $q) {
            KnowledgeTestQuestion::create([
                'knowledge_test_id' => $test->id,
                'question_type' => $q['question_type'],
                'question_text' => $q['question_text'],
                'options' => $q['options'] ?? null,
                'correct_answer' => $q['correct_answer'] ?? null,
                'correct_answers' => $q['correct_answers'] ?? null,
                'points' => $q['points'],
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
            $totalPoints += $q['points'];
        }

        $this->command->info('Seeded '.count($questions)." questions ({$totalPoints} total points) for Dedicated Hunter test.");
    }

    /**
     * Seed Combined Hunter & Sport Shooter test questions (76 questions, 239 marks)
     * Based on NRAPA SPORT HUNTING TEST ANSWER SHEET.pdf
     */
    protected function seedCombinedQuestions(): void
    {
        $test = KnowledgeTest::where('slug', 'dedicated-both')->first();
        if (! $test) {
            $this->command->error('Combined test not found. Run KnowledgeTestSeeder first.');

            return;
        }

        // Clear existing questions (force delete)
        $this->clearTestQuestions($test);

        $questions = [
            // Q1 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Complete the sentence: NRAPA Promotes active participation in _________ shooting.',
                'options' => ['A' => 'Pin', 'B' => 'Three-gun', 'C' => 'Practical', 'D' => 'Postal'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q2 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Complete the sentence: NRAPA Promotes - To obey all _________, _________, _________ and practices pertaining to _________ and the private _________ of _________ and ammunition. (Select 6 correct answers)',
                'options' => [
                    'A' => 'Laws', 'B' => 'Cases', 'C' => 'Primers', 'D' => 'Arms',
                    'E' => 'Bullets', 'F' => 'Powder', 'G' => 'Rifle', 'H' => 'Shotgun',
                    'I' => 'Hunting', 'J' => 'Own', 'K' => 'Rifle scope', 'L' => 'Possession',
                    'M' => 'Regulations', 'N' => 'Codes of conduct',
                ],
                'correct_answers' => ['A', 'D', 'I', 'L', 'M', 'N'],
                'points' => 6,
            ],
            // Q3 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the Fundamental NRAPA Rules for Safe Gun Handling: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Know your target and what is beyond',
                    'B' => 'Always keep the safety on',
                    'C' => 'Know how to use the gun safely',
                    'D' => 'Only shoot in daylight',
                    'E' => 'Be sure the gun is safe to operate',
                    'F' => 'Always wear gloves',
                    'G' => 'Use only the correct ammunition for your gun',
                    'H' => 'Keep the gun loaded at all times',
                    'I' => 'Wear eye and ear protection as appropriate',
                    'J' => 'Never use alcohol or over-the-counter, prescription or other drugs before or while shooting',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E', 'G', 'I', 'J'],
                'points' => 6,
            ],
            // Q4 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Disciplinary Action shall exist for: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Contraventions of all laws pertaining to conservation, hunting, firearms and ammunition',
                    'B' => 'Failure to pay annual membership fees',
                    'C' => 'Breaches of this Code of Ethics',
                    'D' => 'Not attending monthly meetings',
                    'E' => 'Conduct which brings or is likely to bring the Association, hunting and the private possession of firearms and ammunition into disrepute',
                    'F' => 'Using non-NRAPA branded equipment',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q5.1 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm in an approved safe/strong room on your premises provided that you are a holder of a legally licensed firearm/s:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q5.2 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm provided that you are a police officer:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q5.3 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm provided that you have a letter from the owner countersigned by the local DFO stating the period of storage (SAPS 539):',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q6 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Unless you have "dedicated status" you are restricted to _______ rounds of ammunition per licensed firearm, and a maximum of 2400 primers, unless you have written permission from the Registrar.',
                'options' => ['A' => '100', 'B' => '150', 'C' => '99', 'D' => '200'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q7 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The FCA adopts a broad definition of "firearm," which includes:',
                'options' => [
                    'A' => 'Any device that can "propel a bullet or projectile through a barrel or cylinder by means of burning propellant, at a muzzle energy exceeding 8 joules (6 ft-lbs)"',
                    'B' => 'A spear',
                    'C' => 'Bow and arrow',
                    'D' => 'Slingshot',
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q8 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The FCA excludes various devices that would otherwise be considered firearms under this definition:',
                'options' => [
                    'A' => 'Shotgun',
                    'B' => 'Rifle',
                    'C' => 'Explosive-powered tools designed for industrial application for splitting rocks or concrete',
                ],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q9 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: In South Africa, the right to possess firearms is guaranteed by law.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q10 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Certain firearms are categorized as prohibited firearms and cannot ordinarily be possessed or licensed under the FCA. These include any: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Semi-automatic firearm',
                    'B' => 'Projectile or rocket manufactured to be discharged from a cannon, recoilless gun or mortar, or rocket launcher',
                    'C' => 'Gun, cannon, recoilless gun, mortar, light mortar or launcher manufactured to fire a rocket, grenade, self-propelled grenade, bomb, or explosive device',
                    'D' => 'Manual operated rifle or carbine',
                    'E' => 'Altered firearm',
                    'F' => '12 gauge pump action shotgun',
                    'G' => 'Fully automatic firearm',
                ],
                'correct_answers' => ['B', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q11 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: A competency certificate to possess a firearm, trade in firearms, manufacture firearms, or open a gunsmith business is valid for as long as the license to which it relates remains valid, unless the certificate is terminated or renewed.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q12 - Matching (10 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each definition to the correct term:',
                'options' => [
                    'A' => 'A person is permitted to hold only one license of this kind.',
                    'B' => 'Any natural person who is an occasional hunter or sports person is eligible for a maximum of four ten-year-term licenses.',
                    'C' => 'The FCA imposes certain requirements for applicants in this category - must be a member of an accredited hunting association or sport-shooting organization.',
                    'D' => 'The firearm must be one approved for collection by an accredited collectors association.',
                    'E' => 'Applicants are required to submit a written motivation for their use of the firearm.',
                    'F' => 'Violation or failure to comply with the provisions of the FCA or the terms of a license, permit, or authorization is an offense.',
                    'G' => 'Means to kill by means of a firearm only and by no other means.',
                    'H' => 'Proper storage of firearms and ammunition in a prescribed safe or strong room is a prerequisite.',
                    'I' => 'An air gun, a tranquiliser firearm, a paintball gun, a flare gun, a deactivated firearm, an antique firearm, any captive bolt gun.',
                    'J' => 'A complete object consisting of a cartridge case, primer, propellant and bullet.',
                ],
                'correct_answer' => null,
                'correct_answers' => [
                    'A' => 'License for self-defense',
                    'B' => 'License for Occasional Hunting/Sport Shooting',
                    'C' => 'License for Dedicated Hunting/Sport Shooting',
                    'D' => 'License in Private Collection',
                    'E' => 'Temporary Authorization',
                    'F' => 'Offenses and Penalties',
                    'G' => 'Shoot',
                    'H' => 'Safekeeping',
                    'I' => 'Devices not regarded as firearms',
                    'J' => 'Cartridge',
                    '_distractors' => ['Competency Certificate', 'Firearm Dealer License', 'Import Permit'],
                ],
                'points' => 10,
            ],
            // Q13 - Matching (7 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each definition to the correct hunter type:',
                'options' => [
                    'A' => 'A person who provides services for the facilitation, organization, or management of hunts for clients.',
                    'B' => 'A person who holds a valid Professional Hunter\'s license and guides hunters in the field.',
                    'C' => 'An animal hunted primarily as a specimen for display or record, typically selected for exceptional physical attributes.',
                    'D' => 'A person recognized by an accredited sport-shooting organization who actively and regularly participates in sport-shooting activities.',
                    'E' => 'A person recognized by an accredited hunting association who actively and regularly participates in hunting activities.',
                    'F' => 'A person who hunts on an occasional basis and does not hold dedicated status.',
                    'G' => 'A person who hunts lawfully and in good faith.',
                ],
                'correct_answer' => null,
                'correct_answers' => [
                    'A' => 'Hunting operator',
                    'B' => 'Professional Hunter',
                    'C' => 'Trophy',
                    'D' => 'Dedicated Sports Person',
                    'E' => 'Dedicated Hunter',
                    'F' => 'Occasional Hunter',
                    'G' => 'Bona-fide hunter',
                    '_distractors' => ['Game Ranger', 'Conservation Officer', 'Taxidermist'],
                ],
                'points' => 7,
            ],
            // Q14 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Name the four types of safeties: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Bottom safety',
                    'B' => 'Cross-Bolt Safety',
                    'C' => 'Pivot Safety',
                    'D' => 'Stock standard safety',
                    'E' => 'Manual safety',
                    'F' => 'Slide or Tang Safety',
                    'G' => 'Carry the firearm pointing upwards',
                    'H' => 'Half-Cock or Hammer Safety',
                    'I' => 'Pull the trigger before you clean the firearm',
                ],
                'correct_answers' => ['B', 'C', 'F', 'H'],
                'points' => 4,
            ],
            // Q15 - Matching (9 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each description to the correct firearm component:',
                'options' => [
                    'A' => 'The portion of a firearm that wraps around the trigger to provide both protection and safety.',
                    'B' => 'The area of the firearm that contains the rear end of the barrel, where the cartridge is inserted.',
                    'C' => 'The front end of the barrel where the projectile exits the firearm.',
                    'D' => 'The part of a revolver that holds cartridges in separate chambers.',
                    'E' => 'The lever that is pulled or squeezed to initiate the firing process.',
                    'F' => 'The part that strikes the firing pin or the cartridge primer directly.',
                    'G' => 'A spring-operated container that holds cartridges for a repeating firearm.',
                    'H' => 'The portion of a handgun that is used to hold the firearm.',
                    'I' => 'The inside of the gun\'s barrel through which the projectile travels when fired.',
                ],
                'correct_answer' => null,
                'correct_answers' => [
                    'A' => 'Trigger Guard',
                    'B' => 'Breech',
                    'C' => 'Muzzle',
                    'D' => 'Cylinder',
                    'E' => 'Trigger',
                    'F' => 'Hammer',
                    'G' => 'Magazine',
                    'H' => 'Grip',
                    'I' => 'Bore',
                    '_distractors' => ['Stock', 'Barrel', 'Safety'],
                ],
                'points' => 9,
            ],
            // Q16a - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'A licence to possess a firearm for self-defence is valid for how many years?',
                'options' => ['A' => 'Two', 'B' => 'Five', 'C' => 'Ten', 'D' => 'Fifteen'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q16b - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'A licence to possess a restricted firearm for self-defence is valid for how many years?',
                'options' => ['A' => 'Two', 'B' => 'Five', 'C' => 'Ten', 'D' => 'Fifteen'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q16c - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'A licence to possess a firearm for occasional hunting/sport shooting is valid for how many years?',
                'options' => ['A' => 'Two', 'B' => 'Five', 'C' => 'Ten', 'D' => 'Fifteen'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q17 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List the three MAIN parts of a firearm: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Butt plate', 'B' => 'Scope', 'C' => 'Stock',
                    'D' => 'Recoil pad', 'E' => 'Action', 'F' => 'Sling',
                    'G' => 'Barrel', 'H' => 'Cheek piece', 'I' => 'Swivel',
                ],
                'correct_answers' => ['C', 'E', 'G'],
                'points' => 3,
            ],
            // Q18 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'The following are all types of actions: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Canon', 'B' => 'Lever', 'C' => 'Break or hinge',
                    'D' => 'Bolt', 'E' => 'Pump', 'F' => 'Speer',
                    'G' => 'Barrel', 'H' => 'Sling shot', 'I' => 'Semi auto',
                ],
                'correct_answers' => ['B', 'C', 'D', 'E', 'I'],
                'points' => 5,
            ],
            // Q19 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List the steps to safely cleaning a firearm: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Re-load the firearm',
                    'B' => 'Safely unload the firearm',
                    'C' => 'Remove all ammunition from the cleaning area',
                    'D' => 'Store in a clean safe place',
                    'E' => 'Always keep your safe locked',
                    'F' => 'Use cloth and gun cleaning solvents to remove dirt, powder residue, skin oils and moisture from all metal parts',
                    'G' => 'Carry the firearm pointing upwards',
                    'H' => 'Use cleaning rods, brushes, patches and solvent to clean the bore',
                    'I' => 'Pull the trigger before you clean the firearm',
                    'J' => 'Disassemble the firearm for more thorough cleaning',
                    'K' => 'Apply a coating of gun oil to protect the firearm from rust',
                    'L' => 'Place the firearm upright',
                ],
                'correct_answers' => ['B', 'C', 'F', 'H', 'J', 'K'],
                'points' => 6,
            ],
            // Q20 - Matching (8 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each definition to the correct firearm part:',
                'options' => [
                    'A' => 'A gun barrel is the tube through which gases propel a projectile at high velocity.',
                    'B' => 'Loads and fires ammunition.',
                    'C' => 'Serves as a platform for supporting the action and barrel.',
                    'D' => 'A mechanism that actuates the firing of firearms.',
                    'E' => 'A mechanism used to help prevent accidental discharge, ensuring safer handling.',
                    'F' => 'Part of the barrel from which the projectile emerges.',
                    'G' => 'Often described by its twist rate — the spiral grooves inside a barrel.',
                    'H' => 'A loop surrounding the trigger, protecting it from accidental discharge.',
                ],
                'correct_answer' => null,
                'correct_answers' => [
                    'A' => 'Barrel',
                    'B' => 'Action',
                    'C' => 'Stock',
                    'D' => 'Trigger',
                    'E' => 'Safety',
                    'F' => 'Muzzle',
                    'G' => 'Rifling',
                    'H' => 'Trigger guard',
                    '_distractors' => ['Magazine', 'Hammer', 'Bolt'],
                ],
                'points' => 8,
            ],
            // Q21 - Matching (6 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each description to the correct term:',
                'options' => [
                    'A' => 'The curve a projectile describes in space.',
                    'B' => 'The study of the path of projectiles, particularly those shot from artillery or firearms.',
                    'C' => 'An object set in motion by an exterior force and continuing under its own inertia.',
                    'D' => 'Without gravity, a projectile would travel in a straight line until it hit something.',
                    'E' => 'Without air resistance, a projectile would not change velocity until it hit something.',
                    'F' => 'The distance a bullet travels in the barrel while making one revolution.',
                ],
                'correct_answer' => null,
                'correct_answers' => [
                    'A' => 'Trajectory',
                    'B' => 'Ballistics',
                    'C' => 'Projectile',
                    'D' => 'Gravity',
                    'E' => 'Air Resistance',
                    'F' => 'Twist',
                    '_distractors' => ['Recoil', 'Muzzle Velocity'],
                ],
                'points' => 6,
            ],
            // Q22 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the prohibited firearms and ammunition rules: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Tracer ammunition may not be used',
                    'B' => 'Hollow-point ammunition is banned from ranges',
                    'C' => 'Fully automatic firearms may not be fired on full automatic',
                    'D' => 'Black powder firearms are prohibited',
                    'E' => 'Any gun, cannon, recoilless gun, mortar, light mortar or launcher manufactured to fire a rocket, grenade, self-propelled grenade, bomb or explosive device may not be fired on the range',
                    'F' => 'Shotguns may not be used on rifle ranges',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q23 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Rifle and Pistol Cartridge consist of four components: (Select 4 correct answers)',
                'options' => [
                    'A' => 'The primer', 'B' => 'Lever', 'C' => 'The projectile (bullet)',
                    'D' => 'Speer', 'E' => 'Pump', 'F' => 'The case or shell',
                    'G' => 'The powder (black powder replaced later by smokeless black powder)',
                ],
                'correct_answers' => ['A', 'C', 'F', 'G'],
                'points' => 4,
            ],
            // Q24 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the major parts of a shotgun: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Action (lock)',
                    'B' => 'Scope',
                    'C' => 'Stock',
                    'D' => 'Magazine',
                    'E' => 'Barrel',
                    'F' => 'Silencer',
                    'G' => 'Bipod',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q25 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the different actions found in shotguns: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Pump-action',
                    'B' => 'Lever action',
                    'C' => 'Semi-automatic',
                    'D' => 'Revolver action',
                    'E' => 'Bolt action',
                    'F' => 'Gas-delayed blowback',
                    'G' => 'Hinge/break action',
                    'H' => 'Falling block',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q26 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'A Shotgun shell consists of: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Hull', 'B' => 'Bolt action', 'C' => 'Primer',
                    'D' => 'Barrel', 'E' => 'The powder', 'F' => 'Extractor',
                    'G' => 'Wad', 'H' => 'Shot', 'I' => 'The projectile (bullet)',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G', 'H'],
                'points' => 5,
            ],
            // Q27 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Common shotgun gauges are: (Select 5 correct answers)',
                'options' => [
                    'A' => '10', 'B' => '24', 'C' => '16',
                    'D' => '5', 'E' => '12', 'F' => '31',
                    'G' => '20', 'H' => '28', 'I' => '18',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G', 'H'],
                'points' => 5,
            ],
            // Q28 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Differences Between Rifles, Shotguns, and Handguns - The main differences are:',
                'options' => [
                    'A' => 'Their scopes and the type of sights used',
                    'B' => 'Their barrels and the type of ammunition used',
                    'C' => 'Their weight and the type of stock used',
                ],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q29 - Matching (5 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each definition to the correct term:',
                'options' => [
                    'A' => 'An object set in motion by an exterior force and continuing under its own inertia.',
                    'B' => 'The study of the path of projectiles, particularly those shot from artillery or firearms.',
                    'C' => 'The distance a bullet travels in the barrel while making one revolution.',
                    'D' => 'Without air resistance, a projectile would not change velocity until it hit something.',
                    'E' => 'The curve a projectile describes in space.',
                ],
                'correct_answer' => null,
                'correct_answers' => [
                    'A' => 'Projectile',
                    'B' => 'Ballistics',
                    'C' => 'Twist',
                    'D' => 'Air Resistance',
                    'E' => 'Trajectory',
                    '_distractors' => ['Recoil', 'Muzzle Velocity', 'Gravity'],
                ],
                'points' => 5,
            ],
            // Q30 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the typical cartridge malfunctions: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Misfire',
                    'B' => 'Overfire',
                    'C' => 'Hangfire',
                    'D' => 'Backfire',
                    'E' => 'Squib Load',
                    'F' => 'Flashfire',
                    'G' => 'Barrel burst',
                    'H' => 'Double feed',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E'],
                'points' => 3,
            ],
            // Q31 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the basic parts of a bullet: (Select 4 correct answers)',
                'options' => [
                    'A' => 'The Base',
                    'B' => 'The Primer',
                    'C' => 'The Shank',
                    'D' => 'The Cannelure',
                    'E' => 'The Ogive',
                    'F' => 'The Jacket',
                    'G' => 'The Meplat',
                    'H' => 'The Rim',
                    'I' => 'The Casing',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q32 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'There are five different general shapes of hunting bullets: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Flat Point', 'B' => 'Rimfire', 'C' => 'Boat-Tail Spitzer',
                    'D' => 'Lead point', 'E' => 'Semi-Spitzer', 'F' => 'Pellet',
                    'G' => 'Round Nose', 'H' => 'Spitzer', 'I' => 'The projectile (bullet)',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G', 'H'],
                'points' => 5,
            ],
            // Q33 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Name the common handgun bullets: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Wadcutter', 'B' => 'Rimfire', 'C' => 'Lead hollow point',
                    'D' => 'Lead point', 'E' => 'Full metal Jacket', 'F' => 'Partition',
                    'G' => 'Soft point', 'H' => 'Hollow point', 'I' => 'The projectile (bullet)',
                ],
                'correct_answers' => ['A', 'C', 'D', 'E', 'G', 'H'],
                'points' => 6,
            ],
            // Q34 - Multiple choice (1 mark) - HUNTER SPECIFIC
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Complete the sentence: NRAPA Promotes at all times to honor the ethic of "_________" to ensure the humane harvesting of game.',
                'options' => [
                    'A' => 'Multiple shot humane kill',
                    'B' => 'Single shot inhumane kill',
                    'C' => 'Single shot humane kill',
                ],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q35 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Hunter education is important because it:',
                'options' => [
                    'A' => 'Provides more funding for wildlife agencies',
                    'B' => 'Discourages less interested people from going hunting',
                    'C' => 'Takes lots of time to complete',
                    'D' => 'Improves hunter behaviour and makes hunters safer',
                ],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q36 - Multiple select (6 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'As an ethical hunter, I will: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Actively support legal, safe and ethical hunting',
                    'B' => 'Discourages fellow sport shooters from going hunting',
                    'C' => 'Participate in sport shooting events',
                    'D' => 'Show respect for all wildlife and the environment that sustains them',
                    'E' => 'Only hunt with factory ammunition',
                    'F' => 'Take responsibility for my actions',
                    'G' => 'Frequently visit the shooting range',
                    'H' => 'Report vandalism, hunting violations or poaching to the local authorities',
                    'I' => 'Show respect for myself and other people, including landowners, fellow hunters and non-hunters',
                    'J' => 'Know and obey the laws and regulations for hunting',
                ],
                'correct_answers' => ['A', 'D', 'F', 'H', 'I', 'J'],
                'points' => 6,
            ],
            // Q37 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The purpose of hunter education is to produce safe, responsible, knowledgeable and involved hunters.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q38 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Complete the sentence: NRAPA Promotes the sustainable utilisation of wildlife as a _________ tool and promotes _________, _________ hunting. (Select 3 correct answers)',
                'options' => [
                    'A' => 'Conservation', 'B' => 'Wild life', 'C' => 'Hunting',
                    'D' => 'Ethical', 'E' => 'Shooting', 'F' => 'Responsible',
                ],
                'correct_answers' => ['A', 'D', 'F'],
                'points' => 3,
            ],
            // Q39 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Sport shooting education is important because it:',
                'options' => [
                    'A' => 'Provides more funding for sport shooting clubs',
                    'B' => 'Discourages less interested people from sport shooting',
                    'C' => 'Takes lots of time to complete',
                    'D' => 'Improves sport shooting skills',
                ],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q40 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Complete the sentence: ______ chase balances the skills and equipment of the hunter with the abilities of the animal to escape.',
                'options' => ['A' => 'Unfair', 'B' => 'Responsible', 'C' => 'Fair', 'D' => 'Controlled'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q41 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List the Protected or endangered species categories: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Critically Endangered Species', 'B' => 'Critically Dangerous Species',
                    'C' => 'Water Species', 'D' => 'Endangered Species',
                    'E' => 'Vulnerable Species', 'F' => 'Sub Species',
                    'G' => 'Protected Species', 'H' => 'Unprotected Species',
                    'I' => 'Dangerous Water species', 'J' => 'Conservation status of huntable species',
                ],
                'correct_answers' => ['A', 'D', 'E', 'G', 'J'],
                'points' => 5,
            ],
            // Q42 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'What does CITES stand for?',
                'options' => [
                    'A' => 'Convention on Local Trade in Endangered Species of Wild Fauna and Flora',
                    'B' => 'Convention on International Trade in Endangered Species of Wild Fauna and Flora',
                    'C' => 'Convention on International Trade in Dangerous Species of Wild Fauna and Flora',
                ],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q43 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Blue Swallow a Critically Endangered Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q44 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Mountain Zebra an Endangered Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q45 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Cheetah a Vulnerable Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q46 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The Elephant is a Protected Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q47 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Black Wildebeest has Conservation status of huntable species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q48 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'No _______ hunting of listed large predators, white rhino, black rhino, crocodile or elephant.',
                'options' => ['A' => 'Rifle', 'B' => 'Bow'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q49 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: No use of flood or spot lights, except for controlling damage causing animals - leopards and hyenas.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q50 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The hunting of captive-bred "listed large predators" is prohibited if the animal has not been released from captivity and been self-sustainable for at least 24 months.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q51 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The hunting of captive-bred "listed large predators" is prohibited by use of a gin (leghold) trap.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q52 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: No darting, except by a vet or person authorized by the vet for veterinary, scientific, management or transport purposes.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q53 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: For any hunting of any nature, even animals classified as "problem animals", by anyone other than the landowner and his immediate family no written permission of the landowner is required.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q54 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The use of semi-automatic or self-loading rifles to hunt ordinary or protected game is permitted.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q55 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The director of _________ is empowered to issue special permits to make hunting legal under a variety of unusual circumstances.',
                'options' => ['A' => 'Finance', 'B' => 'Security', 'C' => 'Human resources', 'D' => 'Nature Conservation'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q56 - Multiple select (2 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Complete the sentence: The use of semi-automatic or self-loading rifles to hunt _________ or _________ game is prohibited. (Select 2 correct answers)',
                'options' => ['A' => 'Unprotected', 'B' => 'Common', 'C' => 'Ordinary', 'D' => 'Protected'],
                'correct_answers' => ['C', 'D'],
                'points' => 2,
            ],
            // Q57 - Multiple select (2 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Complete the sentence: The use of semi-automatic or self-loading rifles may be used to hunt _________ and _________. (Select 2 correct answers)',
                'options' => ['A' => 'Wild animals which is not game', 'B' => 'Ordinary', 'C' => 'Protected', 'D' => 'Problem animals'],
                'correct_answers' => ['A', 'D'],
                'points' => 2,
            ],
            // Q58 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List Four main types of hunting related shooting incidents: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Walking fast with a firearm',
                    'B' => 'Lack of control of the firearm',
                    'C' => 'Human error and or judgment mistakes',
                    'D' => 'Safety rule violations',
                    'E' => 'Be sure the gun is safe to operate',
                    'F' => 'Equipment or ammunition failure',
                    'G' => 'Know your target and what is beyond',
                    'H' => 'When holding a gun, rest your finger on the trigger guard',
                ],
                'correct_answers' => ['B', 'C', 'D', 'F'],
                'points' => 4,
            ],
            // Q59 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing a Fence – Recommended action to be taken:',
                'options' => [
                    'A' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence. Check barrel for debris. If necessary reload and continue with stalk.',
                    'B' => 'Place the rifle through the fence holding the grip. The rifle must be pointed towards yourself and others. Place on the other side of the fence. Climb through the fence. Check barrel for debris.',
                    'C' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Climb through the fence with the rifle still in your hand.',
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q60 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Rifle carrying techniques: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Elbow or side carry', 'B' => 'Hanging loose', 'C' => 'Sling carry',
                    'D' => 'Butt carry', 'E' => 'Cradle carry', 'F' => 'Shoulder carry',
                    'G' => 'Barrel carry', 'H' => 'Two Handed ready carry',
                ],
                'correct_answers' => ['A', 'C', 'E', 'F', 'H'],
                'points' => 5,
            ],
            // Q61 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Types of shots: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Frontal', 'B' => 'In the rumen', 'C' => 'Broad side',
                    'D' => 'Behind', 'E' => 'Quartering forward', 'F' => 'Neck',
                    'G' => 'Quartering away', 'H' => 'Head', 'I' => 'Back',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q62 - Track A identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track A: Identify the animal from this track (round paw, 4 toes, no claw marks):',
                'options' => ['A' => 'Dog', 'B' => 'Leopard', 'C' => 'Hyena', 'D' => 'Sitatunga'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q63 - Track B identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track B: Identify the animal from this track (large print, 4 round toes spread wide):',
                'options' => ['A' => 'Rhino', 'B' => 'Elephant', 'C' => 'Hippo', 'D' => 'Buffalo'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q64 - Track C identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track C: Identify the animal from this track (massive 3-toed print):',
                'options' => ['A' => 'Hippo', 'B' => 'Elephant', 'C' => 'Buffalo', 'D' => 'Rhino'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q65 - Track D identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track D: Identify the animal from this track (single oval hoof):',
                'options' => ['A' => 'Mountain Zebra', 'B' => 'Burchell\'s Zebra', 'C' => 'Warthog', 'D' => 'Eland'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q66 - Track E identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track E: Identify the animal from this track (2 pointed hooves with dew claws):',
                'options' => ['A' => 'Impala', 'B' => 'Blesbuck', 'C' => 'Warthog', 'D' => 'Bushpig'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q67 - Track F identification (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Track F: Identify the animal from this track (elongated splayed hooves):',
                'options' => ['A' => 'Gemsbuck', 'B' => 'Nyala', 'C' => 'Lechwe', 'D' => 'Sitatunga'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q68 - Direction of travel (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Which direction is the animal walking based on the tracks?',
                'options' => ['A' => 'Left to right', 'B' => 'Right to left'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q64 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'The first three survival priorities are: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Eat a lot of berries',
                    'B' => 'Find water',
                    'C' => 'Take shelter',
                    'D' => 'Swim to cool down',
                    'E' => 'To keep warm (or cool)',
                ],
                'correct_answers' => ['B', 'C', 'E'],
                'points' => 3,
            ],
            // Q65 - Multiple select (5 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'A fire making kit should consist of: (Select 5 correct answers)',
                'options' => [
                    'A' => 'Water', 'B' => 'Lighter', 'C' => 'Ammunition', 'D' => 'Matches',
                    'E' => 'Knife', 'F' => 'Rope', 'G' => 'Steel wool/battery', 'H' => 'Insect repellant',
                    'I' => 'Magnifying glass', 'J' => 'Magnesium bar',
                ],
                'correct_answers' => ['B', 'D', 'G', 'I', 'J'],
                'points' => 5,
            ],
            // Q66 - Matching (7 marks)
            [
                'question_type' => 'matching',
                'question_text' => 'Match each definition to the correct first aid term:',
                'options' => [
                    'A' => 'A medical condition where the body\'s vital organs do not receive enough blood flow, causing weakness, rapid pulse, and pale skin.',
                    'B' => 'A temporary loss of consciousness caused by a drop in blood pressure, often due to pain, emotional stress, or overheating.',
                    'C' => 'Blood loss from a wound visible on the body surface; apply direct pressure and elevate the injured area.',
                    'D' => 'The application of a strip of material to a wound to hold a dressing in place, reduce bleeding, or support an injured limb.',
                    'E' => 'Tissue damage caused by heat, chemicals, electricity, or radiation; cool with running water for at least 20 minutes.',
                    'F' => 'A viral disease transmitted through the bite of an infected animal; always seek immediate medical attention after an animal bite.',
                    'G' => 'Small parasites that attach to the skin and feed on blood; remove carefully by grasping close to the skin and pulling steadily.',
                ],
                'correct_answer' => null,
                'correct_answers' => [
                    'A' => 'Shock',
                    'B' => 'Fainting',
                    'C' => 'External Bleeding',
                    'D' => 'Bandaging',
                    'E' => 'Burn',
                    'F' => 'Rabies',
                    'G' => 'Ticks',
                    '_distractors' => ['Heatstroke', 'Fracture', 'Dehydration'],
                ],
                'points' => 7,
            ],
            // Q67 - Multiple select (8 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the rifle carry techniques: (Select 8 correct answers)',
                'options' => [
                    'A' => 'Sling carry',
                    'B' => 'Hip carry',
                    'C' => 'Cradle carry',
                    'D' => 'Overhead carry',
                    'E' => 'Elbow or side carry',
                    'F' => 'Barrel-first carry',
                    'G' => 'Shoulder carry',
                    'H' => 'Muzzle-down drag',
                    'I' => 'Two Handed ready carry',
                    'J' => 'Safe carry in a group',
                    'K' => 'Walking side by side',
                    'L' => 'Walking in single file',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E', 'G', 'I', 'J', 'K', 'L'],
                'points' => 8,
            ],
            // Q68 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the rifle carrying fundamentals: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Keep the safety in the "on" position while carrying a firearm',
                    'B' => 'Always carry the firearm unloaded',
                    'C' => 'Only change the position of the safety to fire when you are ready to shoot',
                    'D' => 'Keep the rifle in a case when walking',
                    'E' => 'Always keep your finger outside the trigger guard',
                    'F' => 'Rest the rifle on your shoulder at all times',
                    'G' => 'Keep muzzle pointed in a safe direction and the barrel under control',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q69 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The purpose of sport shooter education is to produce safe, responsible, knowledgeable and involved sport shooters.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q70 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'List Four main types of shooting related incidents: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Walking fast with a firearm',
                    'B' => 'Lack of control of the firearm',
                    'C' => 'Human error and or judgment mistakes',
                    'D' => 'Safety rule violations',
                    'E' => 'Be sure the gun is safe to operate',
                    'F' => 'Equipment or ammunition failure',
                    'G' => 'Know your target and what is beyond',
                    'H' => 'When holding a gun, rest your finger on the trigger guard',
                ],
                'correct_answers' => ['B', 'C', 'D', 'F'],
                'points' => 4,
            ],
            // Q71 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'There are four standard bolt action rifle shooting positions: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Standing', 'B' => 'Running', 'C' => 'Kneeling',
                    'D' => 'Recoil pad', 'E' => 'Prone', 'F' => 'Sling',
                    'G' => 'Sitting', 'H' => 'Cheek piece', 'I' => 'Swivel',
                ],
                'correct_answers' => ['A', 'C', 'E', 'G'],
                'points' => 4,
            ],
            // Q72.1 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The rifle barrel is long and has thick walls with spiralling grooves cut into the bore. The grooved pattern is called rifling.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q72.2 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The shotgun barrel is long and made of fairly thin steel that is very smooth on the inside to allow the shot and wad to glide down the barrel without friction.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q72.3 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The handgun barrel is much shorter than a rifle or shotgun barrel because the gun is designed to be shot while being held with one or two hands.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q73 - Multiple select (2 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the two very common safeties in shotguns: (Select 2 correct answers)',
                'options' => [
                    'A' => 'The Tang',
                    'B' => 'Magazine safety',
                    'C' => 'Crossbolt',
                    'D' => 'Trigger lock',
                    'E' => 'Pivot safety',
                    'F' => 'Grip safety',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C'],
                'points' => 2,
            ],
            // Q74 - Multiple select (2 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Select the two common types of actions used in sport shooting handguns: (Select 2 correct answers)',
                'options' => [
                    'A' => 'Single action',
                    'B' => 'Bolt action',
                    'C' => 'Double action',
                    'D' => 'Pump action',
                    'E' => 'Lever action',
                    'F' => 'Gas action',
                ],
                'correct_answer' => null,
                'correct_answers' => ['A', 'C'],
                'points' => 2,
            ],
            // Q75 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'A type of firearm which, utilizing some of the recoil or expanding-gas energy from the firing cartridge, cycles the action to eject the spent shell, chamber a fresh one and cock the mainspring. This describes:',
                'options' => ['A' => 'Bolt', 'B' => 'Pump', 'C' => 'Lever', 'D' => 'Semi-Auto'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q76 - Multiple select (3 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'There are three basic categories of shooting ranges: (Select 3 correct answers)',
                'options' => [
                    'A' => 'Underground', 'B' => 'Indoor ranges', 'C' => 'Trajectory',
                    'D' => 'Outdoor no danger area ranges', 'E' => 'Outdoor danger area ranges',
                    'F' => 'Mine dumps', 'G' => 'Open fields', 'H' => 'River beds',
                ],
                'correct_answers' => ['B', 'D', 'E'],
                'points' => 3,
            ],
        ];

        $sortOrder = 1;
        $totalPoints = 0;
        foreach ($questions as $q) {
            KnowledgeTestQuestion::create([
                'knowledge_test_id' => $test->id,
                'question_type' => $q['question_type'],
                'question_text' => $q['question_text'],
                'options' => $q['options'] ?? null,
                'correct_answer' => $q['correct_answer'] ?? null,
                'correct_answers' => $q['correct_answers'] ?? null,
                'points' => $q['points'],
                'sort_order' => $sortOrder++,
                'is_active' => true,
            ]);
            $totalPoints += $q['points'];
        }

        $this->command->info('Seeded '.count($questions)." questions ({$totalPoints} total points) for Combined Hunter & Sport Shooter test.");
    }
}
