<?php

namespace Database\Seeders;

use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestQuestion;
use Illuminate\Database\Seeder;

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
     * Seed Dedicated Sport Shooter test questions (45 questions, 171 marks)
     */
    protected function seedSportShooterQuestions(): void
    {
        $test = KnowledgeTest::where('slug', 'dedicated-sport-shooter')->first();
        if (!$test) {
            $this->command->error('Sport Shooter test not found. Run KnowledgeTestSeeder first.');
            return;
        }

        $questions = [
            // Q1 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'NRAPA Promotes active participation in _________ shooting.',
                'options' => ['A' => 'Pin', 'B' => 'Three-gun', 'C' => 'Practical', 'D' => 'Postal'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q2 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA Promotes - To obey all _________, _________, _________ and practices pertaining to _________ and the private _________ of _________ and ammunition. (Fill in 6 blanks)',
                'options' => null,
                'correct_answer' => 'Laws, Regulations, Codes of conduct, Hunting, Possession, Arms',
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
                    'D' => 'Improves sport shooting skills'
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
            // Q7 - True/False storage questions (3 marks total)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm in an approved safe/strong room on your premises provided that you are a holder of a legally licensed firearm/s:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm provided that you are a police officer:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
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
                    'D' => 'Slingshot'
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
                    'C' => 'Explosive-powered tools designed for industrial application for splitting rocks or concrete'
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
            // Q12 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Certain firearms are categorized as prohibited firearms and cannot ordinarily be possessed or licensed under the FCA. List 4:',
                'options' => null,
                'correct_answer' => '1. Projectile or rocket manufactured to be discharged from a cannon, recoilless gun or mortar, or rocket launcher. 2. Gun, cannon, recoilless gun, mortar, light mortar or launcher manufactured to fire a rocket, grenade, self-propelled grenade, bomb, or explosive device. 3. Altered firearm. 4. Fully automatic firearm.',
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
                'question_type' => 'written',
                'question_text' => 'Match the definitions: License for Occasional Hunting/Sport Shooting, Devices not regarded as firearms, License for Private Collection, License for self-defense, Safekeeping, Temporary Authorization, Cartridge, License for Dedicated Hunting/Sport Shooting, Offenses and Penalties, Shoot',
                'options' => null,
                'correct_answer' => '1-License for self-defense (one license only), 2-License for Occasional (max 4 ten-year), 3-License for Dedicated (accredited member required), 4-License for Private Collection (collectors association), 5-Temporary Authorization (written motivation), 6-Offenses and Penalties (violation is offense), 7-Shoot (kill by firearm only), 8-Safekeeping (proper storage), 9-Devices not firearms (air gun, paintball, etc), 10-Cartridge (case, primer, propellant, bullet)',
                'points' => 10,
            ],
            // Q15 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Complete the Period of validity: Section 13 (self-defense), Section 16 (dedicated), Section 20 (business other than hunting)',
                'options' => null,
                'correct_answer' => 'Section 13: Five years, Section 16: Ten years, Section 20 (business other than hunting): Five years',
                'points' => 3,
            ],
            // Q16 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List Four main types of shooting related incidents:',
                'options' => null,
                'correct_answer' => '1. Lack of control of the firearm. 2. Human error and/or judgment mistakes. 3. Safety rule violations. 4. Equipment or ammunition failure.',
                'points' => 4,
            ],
            // Q17 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The fundamental NRAPA rules for safe gun handling are (4 rules):',
                'options' => null,
                'correct_answer' => '1. ALWAYS make sure the safety is engaged. 2. ALWAYS keep the gun pointed in a safe direction. 3. ALWAYS keep the gun unloaded until ready to use. 4. ALWAYS keep your finger off the trigger until ready to shoot.',
                'points' => 4,
            ],
            // Q18 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'There are four standard bolt action rifle shooting positions:',
                'options' => null,
                'correct_answer' => 'Standing, Kneeling, Prone, Sitting',
                'points' => 4,
            ],
            // Q19 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing a Fence – Recommended action:',
                'options' => [
                    'A' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence. Check barrel for debris. If necessary reload and continue with stalk.',
                    'B' => 'Place the rifle through the fence holding the grip. The rifle must be pointed towards yourself and others.',
                    'C' => 'Place the rifle through the fence holding the grip. Climb through the fence with the rifle still in your hand.'
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q20 - Written (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carry techniques (8):',
                'options' => null,
                'correct_answer' => '1. Sling carry. 2. Cradle carry. 3. Elbow or side carry. 4. Shoulder carry. 5. Two Handed ready carry. 6. Safe carry in a group. 7. Walking side by side. 8. Walking in single file.',
                'points' => 8,
            ],
            // Q21 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carrying fundamentals (4):',
                'options' => null,
                'correct_answer' => '1. Keep the safety in the "on" position while carrying a firearm. 2. Only change the position of the safety to fire when you are ready to shoot. 3. Always keep your finger outside the trigger guard. 4. Keep muzzle pointed in a safe direction and the barrel under control.',
                'points' => 4,
            ],
            // Q22 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the shooting positions (4):',
                'options' => null,
                'correct_answer' => '1. Standing position. 2. Kneeling. 3. Sitting. 4. Prone.',
                'points' => 4,
            ],
            // Q23 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the three MAIN parts of a firearm:',
                'options' => null,
                'correct_answer' => 'Stock, Action, Barrel',
                'points' => 3,
            ],
            // Q24 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The following are all types of actions (5):',
                'options' => null,
                'correct_answer' => 'Lever, Break or hinge, Bolt, Pump, Semi-Auto',
                'points' => 5,
            ],
            // Q25 - Matching (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match definitions to: Action, Trigger, Trigger guard, Barrel, Safety, Stock, Muzzle, Rifling',
                'options' => null,
                'correct_answer' => 'Barrel=1 (tube for projectile), Action=2 (loads and fires), Stock=3 (platform), Trigger=4 (actuates firing), Safety=5 (prevents discharge), Muzzle=6 (projectile emerges), Rifling=7 (twist rate), Trigger guard=8 (loop protecting trigger)',
                'points' => 8,
            ],
            // Q26 - True/False (3 marks)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The rifle barrel is long and has thick walls with spiralling grooves cut into the bore (rifling).',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The shotgun barrel is long and made of fairly thin steel that is very smooth on the inside.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The handgun barrel is much shorter than a rifle or shotgun barrel.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q27 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the four types of safeties:',
                'options' => null,
                'correct_answer' => 'Cross-Bolt Safety, Pivot Safety, Slide or Tang Safety, Half-Cock or Hammer Safety',
                'points' => 4,
            ],
            // Q28 - Matching (9 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match descriptions to: Bore, Muzzle, Cylinder, Breech, Magazine, Hammer, Trigger, Grip, Trigger Guard',
                'options' => null,
                'correct_answer' => 'Trigger Guard=1, Breech=2, Muzzle=3, Cylinder=4, Trigger=5, Hammer=6, Magazine=7, Grip=8, Bore=9',
                'points' => 9,
            ],
            // Q29 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'A type of firearm which, utilizing recoil or expanding-gas energy, cycles the action to eject the spent shell, chamber a fresh one and cock the mainspring. This is:',
                'options' => ['A' => 'Bolt', 'B' => 'Pump', 'C' => 'Lever', 'D' => 'Semi-Auto'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q30 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the major parts of a shotgun:',
                'options' => null,
                'correct_answer' => '1. Action (lock). 2. Stock. 3. Barrel.',
                'points' => 3,
            ],
            // Q31 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the different actions in shotguns:',
                'options' => null,
                'correct_answer' => '1. Pump-action. 2. Semi-automatic. 3. Bolt action. 4. Hinge/break action.',
                'points' => 4,
            ],
            // Q32 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Two very common safeties in shotguns are:',
                'options' => null,
                'correct_answer' => '1. The Tang. 2. Crossbolt.',
                'points' => 2,
            ],
            // Q33 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the two common types of actions used in sport shooting – handguns:',
                'options' => null,
                'correct_answer' => '1. Single action. 2. Double action.',
                'points' => 2,
            ],
            // Q34 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the typical cartridge malfunctions:',
                'options' => null,
                'correct_answer' => '1. Misfire. 2. Hangfire. 3. Squib Load.',
                'points' => 3,
            ],
            // Q35 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the steps for cleaning a firearm (6 steps):',
                'options' => null,
                'correct_answer' => '1. Safely unload the firearm. 2. Remove all ammunition from the cleaning area. 3. Use cloth and gun cleaning solvents to remove dirt, powder residue, skin oils and moisture from all metal parts. 4. Use cleaning rods, brushes, patches and solvent to clean the bore. 5. Disassemble the firearm for more thorough cleaning. 6. Apply a coating of gun oil to protect the firearm from rust.',
                'points' => 6,
            ],
            // Q36 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle and Pistol Cartridge consist of four components:',
                'options' => null,
                'correct_answer' => 'The primer, The projectile (bullet), The case or shell, The powder',
                'points' => 4,
            ],
            // Q37 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'A Shotgun shell consists of (5 components):',
                'options' => null,
                'correct_answer' => 'Hull, Primer, Powder, Wad, Shot',
                'points' => 5,
            ],
            // Q38 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Common shotgun gauges are (5):',
                'options' => null,
                'correct_answer' => '10G, 12G, 16G, 20G, 28G',
                'points' => 5,
            ],
            // Q39 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Basic parts of a bullet are (4):',
                'options' => null,
                'correct_answer' => '1. The Base. 2. The Shank. 3. The Ogive. 4. The Meplat.',
                'points' => 4,
            ],
            // Q40 - Matching (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match definitions: Ballistics, Twist, Trajectory, Air Resistance, Projectile',
                'options' => null,
                'correct_answer' => 'Projectile=1 (object in motion), Ballistics=2 (study of projectile path), Twist=3 (distance for one revolution), Air Resistance=4 (without it velocity unchanged), Trajectory=5 (curve in space)',
                'points' => 5,
            ],
            // Q41 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'There are five different general shapes of hunting bullets:',
                'options' => null,
                'correct_answer' => 'Flat Point, Boat-Tail Spitzer, Semi-Spitzer, Round Nose, Spitzer',
                'points' => 5,
            ],
            // Q42 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the common handgun bullets (6):',
                'options' => null,
                'correct_answer' => 'Wadcutter, Lead hollow point, Lead point, Full metal Jacket, Soft point, Hollow point',
                'points' => 6,
            ],
            // Q43 - Matching (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match descriptions: Projectile, Ballistics, Trajectory, Air Resistance, Gravity, Twist',
                'options' => null,
                'correct_answer' => 'Trajectory=1 (curve in space), Ballistics=2 (study of projectile path), Projectile=3 (object in motion), Gravity=4 (without it travels straight), Air Resistance=5 (without it velocity unchanged), Twist=6 (distance for one revolution)',
                'points' => 6,
            ],
            // Q44 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Prohibited firearms and ammunition are (3):',
                'options' => null,
                'correct_answer' => '1. Tracer ammunition may not be used. 2. Fully automatic firearms may not be fired on full automatic. 3. Any gun, cannon, recoilless gun, mortar, light mortar or launcher manufactured to fire a rocket, grenade, self-propelled grenade, bomb or explosive device may not be fired on the range.',
                'points' => 3,
            ],
            // Q45 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'There are three basic categories of shooting ranges:',
                'options' => null,
                'correct_answer' => 'Indoor ranges, Outdoor no danger area ranges, Outdoor danger area ranges',
                'points' => 3,
            ],
        ];

        $this->seedQuestions($test, $questions, 'Sport Shooter');
    }

    /**
     * Seed Dedicated Hunter test questions (57 questions, 169 marks)
     */
    protected function seedHunterQuestions(): void
    {
        $test = KnowledgeTest::where('slug', 'dedicated-hunter')->first();
        if (!$test) {
            $this->command->error('Hunter test not found. Run KnowledgeTestSeeder first.');
            return;
        }

        $questions = [
            // Q1 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'NRAPA Promotes at all times to honor the ethic of "_________" to ensure the humane harvesting of game:',
                'options' => [
                    'A' => 'Multiple shot humane kill',
                    'B' => 'Single shot inhumane kill',
                    'C' => 'Single shot humane kill'
                ],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q2 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'NRAPA Promotes active participation in _________ shooting:',
                'options' => ['A' => 'Pin', 'B' => 'Three-gun', 'C' => 'Practical', 'D' => 'Postal'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q3 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA Promotes the sustainable utilisation of wildlife as a __________ tool and promotes_______, __________hunting.',
                'options' => null,
                'correct_answer' => 'Conservation, Ethical, Responsible',
                'points' => 3,
            ],
            // Q4 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA Promotes - To obey all_____, ________, _______of conduct and practices pertaining to ________and the private ________ of ____and ammunition.',
                'options' => null,
                'correct_answer' => 'Laws, Regulations, Codes, Hunting, Possession, Arms',
                'points' => 6,
            ],
            // Q5 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Hunter education is important because it:',
                'options' => [
                    'A' => 'Provides more funding for wildlife agencies',
                    'B' => 'Discourages less interested people from going hunting',
                    'C' => 'Takes lots of time to complete',
                    'D' => 'Improves hunter behaviour and makes hunters safer'
                ],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q6 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'As an ethical hunter, I will (list 6):',
                'options' => null,
                'correct_answer' => '1. Actively support legal, safe and ethical hunting. 2. Show respect for all wildlife and the environment that sustains them. 3. Take responsibility for my actions. 4. Report vandalism, hunting violations or poaching to the local authorities. 5. Show respect for myself and other people, including landowners, fellow hunters and non-hunters. 6. Know and obey the laws and regulations for hunting.',
                'points' => 6,
            ],
            // Q7 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The purpose of hunter education is to produce safe, responsible, knowledgeable and involved hunters.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q8 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Ethics generally cover _________that has to do with issues of fairness, respect, and responsibility not covered by laws.',
                'options' => ['A' => 'Roles', 'B' => 'Responsibilities', 'C' => 'Identity', 'D' => 'Behaviour'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q9 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => '______chase balances the skills and equipment of the hunter with the abilities of the animal to escape:',
                'options' => ['A' => 'Unfair', 'B' => 'Responsible', 'C' => 'Fair', 'D' => 'Controlled'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q10 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the Protected or endangered species categories (5):',
                'options' => null,
                'correct_answer' => 'Critically Endangered Species, Endangered Species, Vulnerable Species, Protected Species, Conservation status of huntable species',
                'points' => 5,
            ],
            // Q11 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'What does CITES stand for?',
                'options' => [
                    'A' => 'Convention on Local Trade in Endangered Species of Wild Fauna and Flora',
                    'B' => 'Convention on International Trade in Endangered Species of Wild Fauna and Flora',
                    'C' => 'Convention on International Trade in Dangerous Species of Wild Fauna and Flora'
                ],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q12-16 True/False species questions (1 mark each)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Blue Swallow a Critically Endangered Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Mountain Zebra an Endangered Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Is the Cheetah a Vulnerable Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The Elephant is a Protected Species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Black Wildebeest has Conservation status of huntable species?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q17 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'No _______hunting of listed large predators, white rhino, black rhino, crocodile or elephant:',
                'options' => ['A' => 'Rifle', 'B' => 'Bow'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q18-23 True/False hunting regulations (1 mark each)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: No use of flood or spot lights, except for controlling damage causing animals - leopards and hyenas.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The hunting of captive-bred "listed large predators", white rhinos or black rhinos is prohibited if the animal has not been released from captivity and been self-sustainable for at least 24 months.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The hunting of captive-bred "listed large predators" is prohibited by use of a gin (leghold) trap.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: No darting, except by a vet or person authorized by the vet.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: For hunting by anyone other than the landowner and his immediate family, no written permission of the landowner is required.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The use of semi-automatic or self-loading rifles to hunt ordinary or protected game is permitted.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q24 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The director of _______________is empowered to issue special permits.',
                'options' => ['A' => 'Finance', 'B' => 'Security', 'C' => 'Human resources', 'D' => 'Nature Conservation'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q25 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The use of semi-automatic rifles to hunt ______or ________ game is prohibited.',
                'options' => null,
                'correct_answer' => 'Ordinary, Protected',
                'points' => 2,
            ],
            // Q26 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Semi-automatic rifles may be used to hunt "___" and "___"',
                'options' => null,
                'correct_answer' => 'Problem animals, Wild animals which is not game',
                'points' => 2,
            ],
            // Q27 - True/False storage (3 marks)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s firearm if you are a holder of a legally licensed firearm/s:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s firearm if you are a police officer:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s firearm if you have a letter from the owner countersigned by the local DFO (SAPS 539):',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q28 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Without dedicated status, you are restricted to _____ rounds of ammunition per licensed firearm:',
                'options' => ['A' => '100', 'B' => '150', 'C' => '99', 'D' => '200'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q29-33 FCA questions
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The FCA definition of firearm includes:',
                'options' => [
                    'A' => 'Device propelling bullet >8 joules',
                    'B' => 'A spear',
                    'C' => 'Bow and arrow',
                    'D' => 'Slingshot'
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The FCA excludes:',
                'options' => [
                    'A' => 'Shotgun',
                    'B' => 'Rifle',
                    'C' => 'Explosive-powered industrial tools'
                ],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: In South Africa, the right to possess firearms is guaranteed by law.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'List 4 prohibited firearms under the FCA:',
                'options' => null,
                'correct_answer' => '1. Projectile/rocket from cannon/mortar/launcher. 2. Gun/cannon/mortar for rockets/grenades/bombs. 3. Altered firearm. 4. Fully automatic firearm.',
                'points' => 4,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: A competency certificate is valid as long as the license remains valid.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q34 - Matching hunter definitions (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match: Dedicated Hunter, Hunting operator, Trophy, Dedicated Sports Person, Professional Hunter, Bona-fide hunter, Occasional Hunter',
                'options' => null,
                'correct_answer' => 'Hunting operator=1 (organises hunting for fee), Professional Hunter=2 (guides clients), Trophy=3 (mounted head/skin), Dedicated Sports Person=4 (sports-shooting member), Dedicated Hunter=5 (hunting association member), Occasional Hunter=6 (hunts sometimes, not member), Bona-fide hunter=7 (old Act category)',
                'points' => 7,
            ],
            // Q35 - Matching license definitions (10 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match license types to definitions',
                'options' => null,
                'correct_answer' => 'Self-defense=1, Occasional=2, Dedicated=3, Private Collection=4, Temporary Auth=5, Offenses=6, Shoot=7, Safekeeping=8, Devices not firearms=9, Cartridge=10',
                'points' => 10,
            ],
            // Q36 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Complete license validity: Section 13 (self-defense), Section 16 (dedicated), Section 20 (business non-hunting)',
                'options' => null,
                'correct_answer' => 'Section 13: Five Years, Section 16: Ten Years, Section 20 (non-hunting): Five Years',
                'points' => 3,
            ],
            // Q37 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List Four main types of hunting related shooting incidents:',
                'options' => null,
                'correct_answer' => '1. Lack of control of the firearm. 2. Human error and/or judgment mistakes. 3. Safety rule violations. 4. Equipment or ammunition failure.',
                'points' => 4,
            ],
            // Q38 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the three MAIN parts of a firearm:',
                'options' => null,
                'correct_answer' => 'Stock, Action, Barrel',
                'points' => 3,
            ],
            // Q39 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The fundamental NRAPA rules for safe gun handling (4):',
                'options' => null,
                'correct_answer' => '1. ALWAYS make sure the safety is engaged. 2. ALWAYS keep the gun pointed in a safe direction. 3. ALWAYS keep the gun unloaded until ready to use. 4. ALWAYS keep your finger off the trigger until ready to shoot.',
                'points' => 4,
            ],
            // Q40 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Types of actions (5):',
                'options' => null,
                'correct_answer' => 'Lever, Break or hinge, Bolt, Pump, Semi auto',
                'points' => 5,
            ],
            // Q41 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Steps for cleaning a firearm (6):',
                'options' => null,
                'correct_answer' => '1. Safely unload. 2. Remove all ammunition from cleaning area. 3. Use cloth and solvents for metal parts. 4. Use cleaning rods/brushes for bore. 5. Disassemble for thorough cleaning. 6. Apply gun oil.',
                'points' => 6,
            ],
            // Q42 - Matching (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match: Action, Trigger, Trigger guard, Barrel, Safety, Stock, Muzzle, Rifling',
                'options' => null,
                'correct_answer' => 'Barrel=1, Action=2, Stock=3, Trigger=4, Safety=5, Muzzle=6, Rifling=7, Trigger guard=8',
                'points' => 8,
            ],
            // Q43 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle/Pistol Cartridge components (4):',
                'options' => null,
                'correct_answer' => 'The primer, The projectile (bullet), The case or shell, The powder',
                'points' => 4,
            ],
            // Q44 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing a Fence – Recommended action:',
                'options' => [
                    'A' => 'Rifle pointed away, place through fence, climb through, check barrel',
                    'B' => 'Rifle pointed towards yourself',
                    'C' => 'Climb with rifle in hand'
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q45 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle carrying techniques (5):',
                'options' => null,
                'correct_answer' => 'Elbow or side carry, Sling carry, Cradle carry, Shoulder carry, Two Handed ready carry',
                'points' => 5,
            ],
            // Q46 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Differences Between Rifles, Shotguns, and Handguns:',
                'options' => [
                    'A' => 'Scopes and sights',
                    'B' => 'Barrels and ammunition',
                    'C' => 'Weight and stock'
                ],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q47 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle/pistol cartridge components (4):',
                'options' => null,
                'correct_answer' => 'The case or shell, The projectile (bullet), The powder, The primer',
                'points' => 4,
            ],
            // Q48 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Shotgun shell consists of (5):',
                'options' => null,
                'correct_answer' => 'Hull, Primer, Powder, Wad, Shot',
                'points' => 5,
            ],
            // Q49 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Common shotgun gauges (5):',
                'options' => null,
                'correct_answer' => '10, 12, 16, 20, 28',
                'points' => 5,
            ],
            // Q50 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Types of shots (4):',
                'options' => null,
                'correct_answer' => 'Frontal, Broadside, Quartering forward, Quartering away',
                'points' => 4,
            ],
            // Q51 - Matching (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match: Ballistics, Twist, Trajectory, Air Resistance, Projectile',
                'options' => null,
                'correct_answer' => 'Projectile=1, Ballistics=2, Twist=3, Air Resistance=4, Trajectory=5',
                'points' => 5,
            ],
            // Q52 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Steps to safely clean a firearm (6):',
                'options' => null,
                'correct_answer' => '1. Safely unload. 2. Remove all ammunition. 3. Use cloth and solvents. 4. Use cleaning rods/brushes/patches. 5. Disassemble for thorough cleaning. 6. Apply gun oil.',
                'points' => 6,
            ],
            // Q53 - Written (6 marks) - Animal identification
            [
                'question_type' => 'written',
                'question_text' => 'Animal Identification from tracks (6 animals):',
                'options' => null,
                'correct_answer' => 'Leopard, Hippo, Rhino, Burchell\'s Zebra, Warthog, Sitatunga',
                'points' => 6,
            ],
            // Q54 - Written (1 mark)
            [
                'question_type' => 'written',
                'question_text' => 'Which direction is the animal walking?',
                'options' => null,
                'correct_answer' => 'Right to left',
                'points' => 1,
            ],
            // Q55 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The first three survival priorities:',
                'options' => null,
                'correct_answer' => 'Find water, Take shelter, Keep warm (or cool)',
                'points' => 3,
            ],
            // Q56 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Fire making kit should consist of (5):',
                'options' => null,
                'correct_answer' => 'Lighter, Matches, Steel wool/battery, Magnifying glass, Magnesium bar',
                'points' => 5,
            ],
            // Q57 - Matching (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match: External Bleeding, Fainting, Bandaging, Burn, Shock, Rabies, Ticks',
                'options' => null,
                'correct_answer' => 'Shock=1 (poor circulation), Fainting=2 (temporary), External Bleeding=3 (blood escaping), Bandaging=4 (control bleeding), Burn=5 (heat damage), Rabies=6 (virus from animals), Ticks=7 (insect-like bugs)',
                'points' => 7,
            ],
        ];

        $this->seedQuestions($test, $questions, 'Hunter');
    }

    /**
     * Seed Combined Hunter & Sport Shooter test questions (76 questions, 239 marks)
     */
    protected function seedCombinedQuestions(): void
    {
        $test = KnowledgeTest::where('slug', 'dedicated-both')->first();
        if (!$test) {
            $this->command->error('Combined test not found. Run KnowledgeTestSeeder first.');
            return;
        }

        $questions = [
            // Q1 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'NRAPA Promotes active participation in _________ shooting.',
                'options' => ['A' => 'Pin', 'B' => 'Three-gun', 'C' => 'Practical', 'D' => 'Postal'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q2 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA Promotes - To obey all laws, regulations, codes of conduct and practices pertaining to hunting and the private possession of arms and ammunition. (Fill in 6 blanks)',
                'options' => null,
                'correct_answer' => 'Laws, Regulations, Codes of conduct, Hunting, Possession, Arms',
                'points' => 6,
            ],
            // Q3 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The Fundamental NRAPA Rules for Safe Gun Handling are (6):',
                'options' => null,
                'correct_answer' => '1. Know your target and what is beyond. 2. Know how to use the gun safely. 3. Be sure the gun is safe to operate. 4. Use only the correct ammunition for your gun. 5. Wear eye and ear protection as appropriate. 6. Never use alcohol or drugs before or while shooting.',
                'points' => 6,
            ],
            // Q4 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Disciplinary Action shall exist for (3):',
                'options' => null,
                'correct_answer' => '1. Contraventions of all laws pertaining to conservation, hunting, firearms and ammunition. 2. Breaches of this Code of Ethics. 3. Conduct which brings the Association into disrepute.',
                'points' => 3,
            ],
            // Q5 - True/False storage (3 marks)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s firearm if you are a holder of a legally licensed firearm/s:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s firearm if you are a police officer:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s firearm if you have SAPS 539 letter:',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q6 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Without dedicated status, ammunition limit per firearm:',
                'options' => ['A' => '100', 'B' => '150', 'C' => '99', 'D' => '200'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q7-11 FCA questions
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The FCA definition of firearm includes:',
                'options' => [
                    'A' => 'Device propelling bullet >8 joules',
                    'B' => 'A spear',
                    'C' => 'Bow and arrow',
                    'D' => 'Slingshot'
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The FCA excludes:',
                'options' => ['A' => 'Shotgun', 'B' => 'Rifle', 'C' => 'Explosive-powered industrial tools'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: In SA, right to possess firearms is guaranteed by law.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'List 4 prohibited firearms under the FCA:',
                'options' => null,
                'correct_answer' => '1. Rockets/projectiles from cannon. 2. Guns for rockets/grenades. 3. Altered firearm. 4. Fully automatic firearm.',
                'points' => 4,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Competency certificate valid as long as license valid.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q12-13 Matching (17 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match license types (10 definitions)',
                'options' => null,
                'correct_answer' => 'Self-defense=1, Occasional=2, Dedicated=3, Private Collection=4, Temporary Auth=5, Offenses=6, Shoot=7, Safekeeping=8, Devices not firearms=9, Cartridge=10',
                'points' => 10,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Match hunter types (7 definitions)',
                'options' => null,
                'correct_answer' => 'Hunting operator=1, Professional Hunter=2, Trophy=3, Dedicated Sports Person=4, Dedicated Hunter=5, Occasional Hunter=6, Bona-fide hunter=7',
                'points' => 7,
            ],
            // Q14 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the four types of safeties:',
                'options' => null,
                'correct_answer' => 'Cross-Bolt Safety, Pivot Safety, Slide or Tang Safety, Half-Cock or Hammer Safety',
                'points' => 4,
            ],
            // Q15 - Matching (9 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match firearm components (9)',
                'options' => null,
                'correct_answer' => 'Trigger Guard=1, Breech=2, Muzzle=3, Cylinder=4, Trigger=5, Hammer=6, Magazine=7, Grip=8, Bore=9',
                'points' => 9,
            ],
            // Q16 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'License validity: Section 13, Section 16, Section 20 (non-hunting)',
                'options' => null,
                'correct_answer' => 'Section 13: Five Years, Section 16: Ten Years, Section 20 (non-hunting): Five Years',
                'points' => 3,
            ],
            // Q17-18 Firearm parts
            [
                'question_type' => 'written',
                'question_text' => 'Three MAIN parts of a firearm:',
                'options' => null,
                'correct_answer' => 'Stock, Action, Barrel',
                'points' => 3,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Types of actions (5):',
                'options' => null,
                'correct_answer' => 'Lever, Break or hinge, Bolt, Pump, Semi auto',
                'points' => 5,
            ],
            // Q19-20 Cleaning and components
            [
                'question_type' => 'written',
                'question_text' => 'Steps to safely clean a firearm (6):',
                'options' => null,
                'correct_answer' => '1. Safely unload. 2. Remove all ammunition. 3. Use cloth and solvents. 4. Use cleaning rods/brushes. 5. Disassemble. 6. Apply gun oil.',
                'points' => 6,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Match definitions: Action, Trigger, Trigger guard, Barrel, Safety, Stock, Muzzle, Rifling (8)',
                'options' => null,
                'correct_answer' => 'Barrel=1, Action=2, Stock=3, Trigger=4, Safety=5, Muzzle=6, Rifling=7, Trigger guard=8',
                'points' => 8,
            ],
            // Q21 - Matching (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match: Projectile, Ballistics, Trajectory, Air Resistance, Gravity, Twist',
                'options' => null,
                'correct_answer' => 'Trajectory=1, Ballistics=2, Projectile=3, Gravity=4, Air Resistance=5, Twist=6',
                'points' => 6,
            ],
            // Q22 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Prohibited firearms and ammunition (3):',
                'options' => null,
                'correct_answer' => '1. Tracer ammunition may not be used. 2. Fully automatic firearms may not be fired on full automatic. 3. Guns/cannons for rockets/grenades may not be fired on range.',
                'points' => 3,
            ],
            // Q23-27 Ammunition components
            [
                'question_type' => 'written',
                'question_text' => 'Rifle/Pistol Cartridge components (4):',
                'options' => null,
                'correct_answer' => 'The primer, The projectile (bullet), The case or shell, The powder',
                'points' => 4,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Major parts of a shotgun (3):',
                'options' => null,
                'correct_answer' => '1. Action (lock). 2. Stock. 3. Barrel.',
                'points' => 3,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Different actions in shotguns (4):',
                'options' => null,
                'correct_answer' => '1. Pump-action. 2. Semi-automatic. 3. Bolt action. 4. Hinge/break action.',
                'points' => 4,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Shotgun shell components (5):',
                'options' => null,
                'correct_answer' => 'Hull, Primer, Powder, Wad, Shot',
                'points' => 5,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Common shotgun gauges (5):',
                'options' => null,
                'correct_answer' => '10, 12, 16, 20, 28',
                'points' => 5,
            ],
            // Q28 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Main differences between Rifles, Shotguns, and Handguns:',
                'options' => [
                    'A' => 'Scopes and sights',
                    'B' => 'Barrels and ammunition',
                    'C' => 'Weight and stock'
                ],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q29-33 Ballistics and bullets
            [
                'question_type' => 'written',
                'question_text' => 'Match: Ballistics, Twist, Trajectory, Air Resistance, Projectile (5)',
                'options' => null,
                'correct_answer' => 'Projectile=1, Ballistics=2, Twist=3, Air Resistance=4, Trajectory=5',
                'points' => 5,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Typical cartridge malfunctions (3):',
                'options' => null,
                'correct_answer' => '1. Misfire. 2. Hangfire. 3. Squib Load.',
                'points' => 3,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Basic parts of a bullet (4):',
                'options' => null,
                'correct_answer' => '1. The Base. 2. The Shank. 3. The Ogive. 4. The Meplat.',
                'points' => 4,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Five general shapes of hunting bullets:',
                'options' => null,
                'correct_answer' => 'Flat Point, Boat-Tail Spitzer, Semi-Spitzer, Round Nose, Spitzer',
                'points' => 5,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Common handgun bullets (6):',
                'options' => null,
                'correct_answer' => 'Wadcutter, Lead hollow point, Lead point, Full metal Jacket, Soft point, Hollow point',
                'points' => 6,
            ],
            // Q34-40 Hunter ethics
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'NRAPA honors the ethic of "___" to ensure humane harvesting:',
                'options' => ['A' => 'Multiple shot humane kill', 'B' => 'Single shot inhumane kill', 'C' => 'Single shot humane kill'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Hunter education is important because it:',
                'options' => [
                    'A' => 'Provides funding',
                    'B' => 'Discourages people',
                    'C' => 'Takes time',
                    'D' => 'Improves hunter behaviour and safety'
                ],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'As an ethical hunter, I will (6):',
                'options' => null,
                'correct_answer' => '1. Support legal, safe, ethical hunting. 2. Show respect for wildlife. 3. Take responsibility. 4. Report violations. 5. Show respect for people. 6. Know and obey hunting laws.',
                'points' => 6,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: Purpose of hunter education is safe, responsible, knowledgeable hunters.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA promotes wildlife as ___ tool and ___, ___ hunting.',
                'options' => null,
                'correct_answer' => 'Conservation, Ethical, Responsible',
                'points' => 3,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Sport shooting education is important because it:',
                'options' => ['A' => 'Provides funding', 'B' => 'Discourages people', 'C' => 'Takes time', 'D' => 'Improves sport shooting skills'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => '___chase balances skills with animal escape:',
                'options' => ['A' => 'Unfair', 'B' => 'Responsible', 'C' => 'Fair', 'D' => 'Controlled'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q41-47 Conservation and hunting regulations
            [
                'question_type' => 'written',
                'question_text' => 'Protected/endangered species categories (5):',
                'options' => null,
                'correct_answer' => 'Critically Endangered Species, Endangered Species, Vulnerable Species, Protected Species, Conservation status of huntable species',
                'points' => 5,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'What does CITES stand for?',
                'options' => [
                    'A' => 'Convention on Local Trade',
                    'B' => 'Convention on International Trade in Endangered Species',
                    'C' => 'Convention on Dangerous Species'
                ],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Is the Blue Swallow Critically Endangered?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Is the Mountain Zebra Endangered?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Is the Cheetah Vulnerable?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Is the Elephant Protected?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Is Black Wildebeest huntable?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q48-57 Hunting regulations
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'No ___hunting of large predators, rhino, crocodile, elephant:',
                'options' => ['A' => 'Rifle', 'B' => 'Bow'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'No spotlights except for damage-causing animals?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Captive-bred predators must be self-sustainable 24 months?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Gin traps prohibited for large predators?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'No darting except by vet?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Written landowner permission NOT required for hunting?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Semi-auto permitted for ordinary/protected game?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Director of ___ issues special permits:',
                'options' => ['A' => 'Finance', 'B' => 'Security', 'C' => 'HR', 'D' => 'Nature Conservation'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Semi-auto prohibited for ___ or ___ game:',
                'options' => null,
                'correct_answer' => 'Ordinary, Protected',
                'points' => 2,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Semi-auto may hunt ___ and ___:',
                'options' => null,
                'correct_answer' => 'Problem animals, Wild animals which is not game',
                'points' => 2,
            ],
            // Q58-76 Practical skills
            [
                'question_type' => 'written',
                'question_text' => 'Four main types of hunting shooting incidents:',
                'options' => null,
                'correct_answer' => '1. Lack of control of firearm. 2. Human error/judgment mistakes. 3. Safety rule violations. 4. Equipment/ammunition failure.',
                'points' => 4,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing fence - recommended action:',
                'options' => [
                    'A' => 'Rifle pointed away, through fence, climb, check barrel',
                    'B' => 'Rifle pointed towards yourself',
                    'C' => 'Climb with rifle in hand'
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Rifle carrying techniques (5):',
                'options' => null,
                'correct_answer' => 'Elbow or side carry, Sling carry, Cradle carry, Shoulder carry, Two Handed ready carry',
                'points' => 5,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Types of shots (4):',
                'options' => null,
                'correct_answer' => 'Frontal, Broadside, Quartering forward, Quartering away',
                'points' => 4,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Animal Identification from tracks (6):',
                'options' => null,
                'correct_answer' => 'Leopard, Hippo, Rhino, Burchell\'s Zebra, Warthog, Sitatunga',
                'points' => 6,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Direction animal is walking:',
                'options' => null,
                'correct_answer' => 'Right to left',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'First three survival priorities:',
                'options' => null,
                'correct_answer' => 'Find water, Take shelter, Keep warm (or cool)',
                'points' => 3,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Fire making kit (5):',
                'options' => null,
                'correct_answer' => 'Lighter, Matches, Steel wool/battery, Magnifying glass, Magnesium bar',
                'points' => 5,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Match first aid: External Bleeding, Fainting, Bandaging, Burn, Shock, Rabies, Ticks (7)',
                'options' => null,
                'correct_answer' => 'Shock=1, Fainting=2, External Bleeding=3, Bandaging=4, Burn=5, Rabies=6, Ticks=7',
                'points' => 7,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Rifle carry techniques (8):',
                'options' => null,
                'correct_answer' => '1. Sling carry. 2. Cradle carry. 3. Elbow or side carry. 4. Shoulder carry. 5. Two Handed ready carry. 6. Safe carry in a group. 7. Walking side by side. 8. Walking in single file.',
                'points' => 8,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Rifle carrying fundamentals (4):',
                'options' => null,
                'correct_answer' => '1. Keep safety "on" while carrying. 2. Only change to fire when ready. 3. Finger outside trigger guard. 4. Muzzle in safe direction.',
                'points' => 4,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Purpose of sport shooter education is safe, responsible, knowledgeable shooters?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Four main types of shooting incidents:',
                'options' => null,
                'correct_answer' => '1. Lack of control of firearm. 2. Human error/judgment mistakes. 3. Safety rule violations. 4. Equipment/ammunition failure.',
                'points' => 4,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Four standard bolt action rifle positions:',
                'options' => null,
                'correct_answer' => 'Standing, Kneeling, Prone, Sitting',
                'points' => 4,
            ],
            // Q72 - True/False barrel differences (3 marks)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Rifle barrel has spiralling grooves (rifling)?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Shotgun barrel is smooth inside?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Handgun barrel is shorter than rifle/shotgun?',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q73-76 Final questions
            [
                'question_type' => 'written',
                'question_text' => 'Two common shotgun safeties:',
                'options' => null,
                'correct_answer' => '1. The Tang. 2. Crossbolt.',
                'points' => 2,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Two common handgun action types:',
                'options' => null,
                'correct_answer' => '1. Single action. 2. Double action.',
                'points' => 2,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Firearm using recoil/gas to cycle action is:',
                'options' => ['A' => 'Bolt', 'B' => 'Pump', 'C' => 'Lever', 'D' => 'Semi-Auto'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            [
                'question_type' => 'written',
                'question_text' => 'Three categories of shooting ranges:',
                'options' => null,
                'correct_answer' => 'Indoor ranges, Outdoor no danger area ranges, Outdoor danger area ranges',
                'points' => 3,
            ],
        ];

        $this->seedQuestions($test, $questions, 'Combined Hunter & Sport Shooter');
    }

    /**
     * Helper to seed questions for a test
     */
    protected function seedQuestions(KnowledgeTest $test, array $questions, string $testName): void
    {
        // Clear existing questions for this test
        KnowledgeTestQuestion::where('knowledge_test_id', $test->id)->delete();

        $count = 0;
        $totalPoints = 0;
        foreach ($questions as $index => $questionData) {
            KnowledgeTestQuestion::create([
                'knowledge_test_id' => $test->id,
                'question_type' => $questionData['question_type'],
                'question_text' => $questionData['question_text'],
                'options' => $questionData['options'],
                'correct_answer' => $questionData['correct_answer'],
                'points' => $questionData['points'],
                'sort_order' => $index + 1,
                'is_active' => true,
            ]);
            $count++;
            $totalPoints += $questionData['points'];
        }

        $this->command->info("Seeded {$count} questions ({$totalPoints} total points) for {$testName} test.");
    }
}
