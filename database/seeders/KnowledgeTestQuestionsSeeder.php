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
        if (!$test) {
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
            // Q5 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the Fundamental NRAPA Rules for Safe Gun Handling (6 rules):',
                'options' => null,
                'correct_answer' => '1. Know your target and what is beyond. 2. Know how to use the gun safely. 3. Be sure the gun is safe to operate. 4. Use only the correct ammunition for your gun. 5. Wear eye and ear protection as appropriate. 6. Never use alcohol or over-the-counter, prescription or other drugs before or while shooting.',
                'points' => 6,
            ],
            // Q6 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Disciplinary Action shall exist for (list 3 items):',
                'options' => null,
                'correct_answer' => '1. Contraventions of all laws pertaining to conservation, hunting, firearms and ammunition. 2. Breaches of this Code of Ethics. 3. Conduct which brings or is likely to bring the Association, hunting and the private possession of firearms and ammunition into disrepute.',
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
            // Q14 - Written/Matching (10 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the number to the definition: (10 marks)

1. A person is permitted to hold only one license of this kind.
2. Any natural person who is an occasional hunter or sports person is eligible for a maximum of four ten-year-term licenses.
3. The FCA imposes certain requirements for applicants in this category - must be a member of an accredited hunting association or sport-shooting organization.
4. The firearm must be one approved for collection by an accredited collectors association.
5. Applicants are required to submit a written motivation for their use of the firearm.
6. Violation or failure to comply with the provisions of the FCA or the terms of a license, permit, or authorization is an offense.
7. Means to kill by means of a firearm only and by no other means.
8. Proper storage of firearms and ammunition in a prescribed safe or strong room is a prerequisite.
9. An air gun, a tranquiliser firearm, a paintball gun, a flare gun, a deactivated firearm, an antique firearm, any captive bolt gun.
10. A complete object consisting of a cartridge case, primer, propellant and bullet.

Match with: License for self-defense, License for Occasional Hunting/Sport Shooting, License for Dedicated Hunting/Sport Shooting, License in Private Collection, Temporary Authorization, Offenses and Penalties, Shoot, Safekeeping, Devices not regarded as firearms, Cartridge',
                'options' => null,
                'correct_answer' => '1=License for self-defense, 2=License for Occasional Hunting/Sport Shooting, 3=License for Dedicated Hunting/Sport Shooting, 4=License in Private Collection, 5=Temporary Authorization, 6=Offenses and Penalties, 7=Shoot, 8=Safekeeping, 9=Devices not regarded as firearms, 10=Cartridge',
                'points' => 10,
            ],
            // Q15 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Complete the Period of validity of license or permit: (3 marks)
- Licence to possess a firearm for self-defense: ___ years
- Licence to possess a restricted firearm for self-defense: ___ years
- Licence to possess a firearm for occasional hunting/sport shooting: ___ years',
                'options' => null,
                'correct_answer' => 'Self-defense: Five years, Restricted self-defense: Two years, Occasional hunting/sport shooting: Ten years',
                'points' => 3,
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
            // Q18 - Multiple select (4 marks)
            [
                'question_type' => 'multiple_select',
                'question_text' => 'There are four standard bolt action rifle shooting positions: (Select 4 correct answers)',
                'options' => [
                    'A' => 'Standing',
                    'B' => 'Running',
                    'C' => 'Kneeling',
                    'D' => 'Recoil pad',
                    'E' => 'Prone',
                    'F' => 'Sling',
                    'G' => 'Sitting',
                    'H' => 'Cheek piece',
                    'I' => 'Swivel',
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
            // Q20 - Written (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carry techniques. (8 marks)',
                'options' => null,
                'correct_answer' => '1. Sling carry, 2. Cradle carry, 3. Elbow or side carry, 4. Shoulder carry, 5. Two Handed ready carry, 6. Safe carry in a group, 7. Walking side by side, 8. Walking in single file',
                'points' => 8,
            ],
            // Q21 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carrying fundamentals (4 marks)',
                'options' => null,
                'correct_answer' => '1. Keep the safety in the "on" position while carrying a firearm. 2. Only change the position of the safety to fire when you are ready to shoot. 3. Always keep your finger outside the trigger guard. 4. Keep muzzle pointed in a safe direction and the barrel under control.',
                'points' => 4,
            ],
            // Q22 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the shooting positions (4 marks)',
                'options' => null,
                'correct_answer' => '1. Standing position, 2. Kneeling, 3. Sitting, 4. Prone',
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
            // Q25 - Written/Matching (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the number to the definition: (8 marks)

1. A gun barrel is the tube, usually metal, through which a controlled explosion or rapid expansion of gases are released in order to propel a projectile out of the end at a high velocity.
2. Loads and fires ammunition
3. Serves as a platform for supporting the action and barrel
4. A trigger is a mechanism that actuates the firing of firearms.
5. In firearms, a safety or safety catch is a mechanism used to help prevent the accidental discharge of a firearm, helping to ensure safer handling
6. Part of the barrel from which the projectile emerges
7. Rifling is often described by its twist rate
8. A trigger guard is a loop surrounding the trigger of a firearm and protecting it from accidental discharge

Match with: Action, Barrel, Stock, Trigger, Safety, Muzzle, Rifling, Trigger guard',
                'options' => null,
                'correct_answer' => '1=Barrel, 2=Action, 3=Stock, 4=Trigger, 5=Safety, 6=Muzzle, 7=Rifling, 8=Trigger guard',
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
            // Q28 - Written/Matching (9 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Choose the correct description for the following components: (9 marks)

1. The portion of a firearm that wraps around the trigger to provide both protection and safety.
2. The area of the firearm that contains the rear end of the barrel, where the cartridge is inserted.
3. The front end of the barrel where the projectile exits the firearm.
4. The part of a revolver that holds cartridges in separate chambers.
5. The lever that\'s pulled or squeezed to initiate the firing process.
6. The part that strikes the firing pin or the cartridge primer directly.
7. A spring-operated container that holds cartridges for a repeating firearm.
8. The portion of a handgun that\'s used to hold the firearm.
9. The inside of the gun\'s barrel through which the projectile travels when fired.

Match with: Bore, Breech, Muzzle, Cylinder, Trigger, Hammer, Magazine, Grip, Trigger Guard',
                'options' => null,
                'correct_answer' => '1=Trigger Guard, 2=Breech, 3=Muzzle, 4=Cylinder, 5=Trigger, 6=Hammer, 7=Magazine, 8=Grip, 9=Bore',
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
            // Q30 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the major parts of a shotgun (3 marks)',
                'options' => null,
                'correct_answer' => '1. Action (lock), 2. Stock, 3. Barrel',
                'points' => 3,
            ],
            // Q31 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the different actions in shotguns (4 marks)',
                'options' => null,
                'correct_answer' => '1. Pump-action, 2. Semi-automatic, 3. Bolt action, 4. Hinge/break action',
                'points' => 4,
            ],
            // Q32 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Two very common safeties in shotguns are: (2 marks)',
                'options' => null,
                'correct_answer' => '1. The Tang, 2. Crossbolt',
                'points' => 2,
            ],
            // Q33 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the two common types of actions used in sport shooting – handguns (2 marks)',
                'options' => null,
                'correct_answer' => '1. Single action, 2. Double action',
                'points' => 2,
            ],
            // Q34 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the typical cartridge malfunctions. (3 marks)',
                'options' => null,
                'correct_answer' => '1. Misfire, 2. Hangfire, 3. Squib Load',
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
            // Q39 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Basic parts of a bullet are (4 marks)',
                'options' => null,
                'correct_answer' => '1. The Base, 2. The Shank, 3. The Ogive, 4. The Meplat',
                'points' => 4,
            ],
            // Q40 - Written/Matching (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the definitions: (5 marks)

1. An object set in motion by an exterior force and continuing under its own inertia.
2. The study of the path of projectiles, particularly those shot from artillery or firearms.
3. The distance a bullet travels in the barrel while making one revolution.
4. Without air resistance, a projectile would not change velocity until it hit something.
5. The curve a projectile describes in space.

Match with: Projectile, Ballistics, Twist, Air Resistance, Trajectory',
                'options' => null,
                'correct_answer' => '1=Projectile, 2=Ballistics, 3=Twist, 4=Air Resistance, 5=Trajectory',
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
            // Q43 - Written/Matching (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the correct descriptions: (6 marks)

1. The curve a projectile describes in space.
2. The study of the path of projectiles, particularly those shot from artillery or firearms.
3. An object set in motion by an exterior force and continuing under its own inertia.
4. Without gravity, a projectile would travel in a straight line until it hit something.
5. Without air resistance, a projectile would not change velocity until it hit something.
6. The distance a bullet travels in the barrel while making one revolution.

Match with: Projectile, Ballistics, Trajectory, Air Resistance, Gravity, Twist',
                'options' => null,
                'correct_answer' => '1=Trajectory, 2=Ballistics, 3=Projectile, 4=Gravity, 5=Air Resistance, 6=Twist',
                'points' => 6,
            ],
            // Q44 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Prohibited firearms and ammunition are: (3 marks)',
                'options' => null,
                'correct_answer' => '1. Tracer ammunition may not be used. 2. Fully automatic firearms may not be fired on full automatic. 3. Any gun, cannon, recoilless gun, mortar, light mortar or launcher manufactured to fire a rocket, grenade, self-propelled grenade, bomb or explosive device may not be fired on the range.',
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

        $this->command->info("Seeded " . count($questions) . " questions ({$totalPoints} total points) for Sport Shooter test.");
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
        if (!$test) {
            $this->command->error('Hunter test not found. Run KnowledgeTestSeeder first.');
            return;
        }

        // Clear existing questions (force delete)
        $this->clearTestQuestions($test);

        $questions = [
            // Core firearm knowledge (shared with sport shooter)
            // Q1 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the Fundamental NRAPA Rules for Safe Gun Handling (6 rules):',
                'options' => null,
                'correct_answer' => '1. Know your target and what is beyond. 2. Know how to use the gun safely. 3. Be sure the gun is safe to operate. 4. Use only the correct ammunition for your gun. 5. Wear eye and ear protection as appropriate. 6. Never use alcohol or over-the-counter, prescription or other drugs before or while shooting.',
                'points' => 6,
            ],
            // Q2 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Disciplinary Action shall exist for (list 3 items):',
                'options' => null,
                'correct_answer' => '1. Contraventions of all laws pertaining to conservation, hunting, firearms and ammunition. 2. Breaches of this Code of Ethics. 3. Conduct which brings or is likely to bring the Association, hunting and the private possession of firearms and ammunition into disrepute.',
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
            // Q33 - Multiple select (6 marks) - Animal Identification
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Animal Identification - Identify the animals from the tracks/images: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Leopard', 'B' => 'Dog', 'C' => 'Hyena',
                    'D' => 'Hippo', 'E' => 'Rhino', 'F' => 'Mountain Zebra',
                    'G' => 'Burchell\'s Zebra', 'H' => 'Warthog', 'I' => 'Blesbuck',
                    'J' => 'Sitatunga', 'K' => 'Impala', 'L' => 'Gemsbuck',
                ],
                'correct_answers' => ['A', 'D', 'E', 'G', 'H', 'J'],
                'points' => 6,
            ],
            // Q34 - Multiple choice (1 mark)
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
            // Q37 - Written/Matching (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the definitions (7 marks): External Bleeding, Fainting, Bandaging, Burn, Shock, Rabies, Ticks.',
                'options' => null,
                'correct_answer' => '1=Shock, 2=Fainting, 3=External Bleeding, 4=Bandaging, 5=Burn, 6=Rabies, 7=Ticks',
                'points' => 7,
            ],
            // Q38 - Written (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carry techniques. (8 marks)',
                'options' => null,
                'correct_answer' => '1. Sling carry, 2. Cradle carry, 3. Elbow or side carry, 4. Shoulder carry, 5. Two Handed ready carry, 6. Safe carry in a group, 7. Walking side by side, 8. Walking in single file',
                'points' => 8,
            ],
            // Q39 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carrying fundamentals (4 marks)',
                'options' => null,
                'correct_answer' => '1. Keep the safety in the "on" position while carrying a firearm. 2. Only change the position of the safety to fire when you are ready to shoot. 3. Always keep your finger outside the trigger guard. 4. Keep muzzle pointed in a safe direction and the barrel under control.',
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

        $this->command->info("Seeded " . count($questions) . " questions ({$totalPoints} total points) for Dedicated Hunter test.");
    }

    /**
     * Seed Combined Hunter & Sport Shooter test questions (76 questions, 239 marks)
     * Based on NRAPA SPORT HUNTING TEST ANSWER SHEET.pdf
     */
    protected function seedCombinedQuestions(): void
    {
        $test = KnowledgeTest::where('slug', 'dedicated-both')->first();
        if (!$test) {
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
            // Q3 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the Fundamental NRAPA Rules for Safe Gun Handling (6 rules):',
                'options' => null,
                'correct_answer' => '1. Know your target and what is beyond. 2. Know how to use the gun safely. 3. Be sure the gun is safe to operate. 4. Use only the correct ammunition for your gun. 5. Wear eye and ear protection as appropriate. 6. Never use alcohol or over-the-counter, prescription or other drugs before or while shooting.',
                'points' => 6,
            ],
            // Q4 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Disciplinary Action shall exist for (list 3 items):',
                'options' => null,
                'correct_answer' => '1. Contraventions of all laws pertaining to conservation, hunting, firearms and ammunition. 2. Breaches of this Code of Ethics. 3. Conduct which brings or is likely to bring the Association, hunting and the private possession of firearms and ammunition into disrepute.',
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
            // Q12 - Written/Matching (10 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the number to the definition (10 marks): Match License types and definitions.',
                'options' => null,
                'correct_answer' => '1=License for self-defense, 2=License for Occasional Hunting/Sport Shooting, 3=License for Dedicated Hunting/Sport Shooting, 4=License in Private Collection, 5=Temporary Authorization, 6=Offenses and Penalties, 7=Shoot, 8=Safekeeping, 9=Devices not regarded as firearms, 10=Cartridge',
                'points' => 10,
            ],
            // Q13 - Written/Matching (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the number to the definition (7 marks): Match Hunter types - Dedicated Hunter, Hunting operator, Trophy, Dedicated Sports Person, Professional Hunter, Bona-fide hunter, Occasional Hunter.',
                'options' => null,
                'correct_answer' => '1=Hunting operator, 2=Professional Hunter, 3=Trophy, 4=Dedicated Sports Person, 5=Dedicated Hunter, 6=Occasional Hunter, 7=Bona-fide hunter',
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
            // Q15 - Written/Matching (9 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Choose the correct description for the following components (9 marks): Bore, Muzzle, Cylinder, Breech, Magazine, Hammer, Trigger, Grip, Trigger Guard.',
                'options' => null,
                'correct_answer' => '1=Trigger Guard, 2=Breech, 3=Muzzle, 4=Cylinder, 5=Trigger, 6=Hammer, 7=Magazine, 8=Grip, 9=Bore',
                'points' => 9,
            ],
            // Q16 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Complete the Period of validity of license or permit (3 marks): Self-defense, Restricted self-defense, Occasional hunting/sport shooting.',
                'options' => null,
                'correct_answer' => 'Self-defense: Five years, Restricted self-defense: Two years, Occasional hunting/sport shooting: Ten years',
                'points' => 3,
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
            // Q20 - Written/Matching (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the number to the definition (8 marks): Action, Trigger, Trigger guard, Barrel, Safety, Stock, Muzzle, Rifling.',
                'options' => null,
                'correct_answer' => '1=Barrel, 2=Action, 3=Stock, 4=Trigger, 5=Safety, 6=Muzzle, 7=Rifling, 8=Trigger guard',
                'points' => 8,
            ],
            // Q21 - Written/Matching (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the correct descriptions (6 marks): Projectile, Ballistics, Trajectory, Air Resistance, Gravity, Twist.',
                'options' => null,
                'correct_answer' => '1=Trajectory, 2=Ballistics, 3=Projectile, 4=Gravity, 5=Air Resistance, 6=Twist',
                'points' => 6,
            ],
            // Q22 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Prohibited firearms and ammunition are: (3 marks)',
                'options' => null,
                'correct_answer' => '1. Tracer ammunition may not be used. 2. Fully automatic firearms may not be fired on full automatic. 3. Any gun, cannon, recoilless gun, mortar, light mortar or launcher manufactured to fire a rocket, grenade, self-propelled grenade, bomb or explosive device may not be fired on the range.',
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
            // Q24 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the major parts of a shotgun (3 marks)',
                'options' => null,
                'correct_answer' => '1. Action (lock), 2. Stock, 3. Barrel',
                'points' => 3,
            ],
            // Q25 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the different actions in shotguns (4 marks)',
                'options' => null,
                'correct_answer' => '1. Pump-action, 2. Semi-automatic, 3. Bolt action, 4. Hinge/break action',
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
            // Q29 - Written/Matching (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the definitions (5 marks): Ballistics, Twist, Trajectory, Air Resistance, Projectile.',
                'options' => null,
                'correct_answer' => '1=Projectile, 2=Ballistics, 3=Twist, 4=Air Resistance, 5=Trajectory',
                'points' => 5,
            ],
            // Q30 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the typical cartridge malfunctions. (3 marks)',
                'options' => null,
                'correct_answer' => '1. Misfire, 2. Hangfire, 3. Squib Load',
                'points' => 3,
            ],
            // Q31 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Basic parts of a bullet are (4 marks)',
                'options' => null,
                'correct_answer' => '1. The Base, 2. The Shank, 3. The Ogive, 4. The Meplat',
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
            // Q62 - Multiple select (6 marks) - Animal Identification
            [
                'question_type' => 'multiple_select',
                'question_text' => 'Animal Identification - Identify the animals from the tracks/images: (Select 6 correct answers)',
                'options' => [
                    'A' => 'Leopard', 'B' => 'Dog', 'C' => 'Hyena',
                    'D' => 'Hippo', 'E' => 'Rhino', 'F' => 'Mountain Zebra',
                    'G' => 'Burchell\'s Zebra', 'H' => 'Warthog', 'I' => 'Blesbuck',
                    'J' => 'Sitatunga', 'K' => 'Impala', 'L' => 'Gemsbuck',
                ],
                'correct_answers' => ['A', 'D', 'E', 'G', 'H', 'J'],
                'points' => 6,
            ],
            // Q63 - Multiple choice (1 mark)
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
            // Q66 - Written/Matching (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Pair the definitions (7 marks): External Bleeding, Fainting, Bandaging, Burn, Shock, Rabies, Ticks.',
                'options' => null,
                'correct_answer' => '1=Shock, 2=Fainting, 3=External Bleeding, 4=Bandaging, 5=Burn, 6=Rabies, 7=Ticks',
                'points' => 7,
            ],
            // Q67 - Written (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carry techniques. (8 marks)',
                'options' => null,
                'correct_answer' => '1. Sling carry, 2. Cradle carry, 3. Elbow or side carry, 4. Shoulder carry, 5. Two Handed ready carry, 6. Safe carry in a group, 7. Walking side by side, 8. Walking in single file',
                'points' => 8,
            ],
            // Q68 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carrying fundamentals (4 marks)',
                'options' => null,
                'correct_answer' => '1. Keep the safety in the "on" position while carrying a firearm. 2. Only change the position of the safety to fire when you are ready to shoot. 3. Always keep your finger outside the trigger guard. 4. Keep muzzle pointed in a safe direction and the barrel under control.',
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
            // Q73 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Two very common safeties in shotguns are: (2 marks)',
                'options' => null,
                'correct_answer' => '1. The Tang, 2. Crossbolt',
                'points' => 2,
            ],
            // Q74 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the two common types of actions used in sport shooting – handguns (2 marks)',
                'options' => null,
                'correct_answer' => '1. Single action, 2. Double action',
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

        $this->command->info("Seeded " . count($questions) . " questions ({$totalPoints} total points) for Combined Hunter & Sport Shooter test.");
    }
}
