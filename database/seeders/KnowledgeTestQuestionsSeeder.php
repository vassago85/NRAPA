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
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q2 - Written (6 marks) - Fill in blanks
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA Promotes - To obey all _________, _________, _________ and practices pertaining to _________ and the private _________ of _________ and ammunition. (Fill in the 6 blanks)',
                'options' => null,
                'correct_answer' => 'Laws, Regulations, Codes of conduct, Arms, Possession, Firearms',
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
                'correct_answer' => '1. ALWAYS keep the gun pointed in a safe direction. 2. ALWAYS keep the gun unloaded until ready to use. 3. ALWAYS keep your finger off the trigger until ready to shoot. 4. ALWAYS make sure the safety is engaged. 5. Know your target and what is beyond. 6. Be sure the gun is safe to operate.',
                'points' => 6,
            ],
            // Q6 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Disciplinary Action shall exist for: (List 3 items)',
                'options' => null,
                'correct_answer' => '1. Unsafe handling of firearms. 2. Violation of range rules. 3. Unsportsmanlike conduct.',
                'points' => 3,
            ],
            // Q7 - Multiple True/False (3 marks)
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
                'correct_answer' => 'A',
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
            // Q12 - Multiple choice (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Certain firearms are categorized as prohibited firearms and cannot ordinarily be possessed or licensed under the FCA. Select 4 that apply: Semi-automatic firearm, Projectile or rocket from cannon/mortar/rocket launcher, Gun/cannon/mortar/launcher for rockets/grenades/bombs, Manual operated rifle or carbine, Altered firearm, 12 gauge pump action shotgun, Fully automatic firearm',
                'options' => null,
                'correct_answer' => 'Projectile or rocket from cannon/mortar/rocket launcher, Gun/cannon/mortar/launcher for rockets/grenades/bombs, Altered firearm, Fully automatic firearm',
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
            // Q14 - Matching (10 marks) - Written
            [
                'question_type' => 'written',
                'question_text' => 'Match the following definitions to their terms: License to Possess a Firearm for Occasional Hunting and Sport Shooting, Devices not regarded as firearms, License to Possess a Firearm in a Private Collection, License for self-defense, Safekeeping, Temporary Authorization to Possess a Firearm, Cartridge, License to Possess Firearm for Dedicated Hunting and Dedicated Sports Shooting, Offenses and Penalties, Shoot',
                'options' => null,
                'correct_answer' => '1-License for self-defense, 2-License for Occasional Hunting and Sport Shooting, 3-License for Dedicated Hunting and Dedicated Sports Shooting, 4-License in Private Collection, 5-Temporary Authorization, 6-Offenses and Penalties, 7-Shoot, 8-Safekeeping, 9-Devices not regarded as firearms, 10-Cartridge',
                'points' => 10,
            ],
            // Q15 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Complete the Period of validity: Section 13 (self-defense), Section 16 (dedicated hunting/sport shooting), Section 20 (business other than hunting)',
                'options' => null,
                'correct_answer' => 'Section 13: Five years, Section 16: Ten years, Section 20 (non-hunting): Two years',
                'points' => 3,
            ],
            // Q16 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List Four main types of shooting related shooting incidents:',
                'options' => null,
                'correct_answer' => '1. Lack of control of the firearm, 2. Human error and/or judgment mistakes, 3. Safety rule violations, 4. Equipment or ammunition failure',
                'points' => 4,
            ],
            // Q17 - Multiple choice (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The fundamental NRAPA rules for safe gun handling are (select 4):',
                'options' => null,
                'correct_answer' => '1. ALWAYS keep the gun pointed in a safe direction, 2. ALWAYS keep the gun unloaded until ready to use, 3. ALWAYS keep your finger off the trigger until ready to shoot, 4. ALWAYS make sure the safety is engaged',
                'points' => 4,
            ],
            // Q18 - Multiple choice (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'There are four standard bolt action rifle shooting positions (select 4):',
                'options' => null,
                'correct_answer' => 'Standing, Kneeling, Prone, Sitting',
                'points' => 4,
            ],
            // Q19 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing a Fence – Recommended action to be taken:',
                'options' => [
                    'A' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence. Check barrel for debris. If necessary reload and continue with stalk.',
                    'B' => 'Place the rifle through the fence holding the grip. The rifle must be pointed towards yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence.',
                    'C' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Climb through the fence with the rifle still in your hand.'
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q20 - Written (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carry techniques (8 techniques):',
                'options' => null,
                'correct_answer' => 'Elbow or side carry, Sling carry, Shoulder carry, Cradle carry, Two Handed ready carry, Trail carry, Ready carry, Port arms',
                'points' => 8,
            ],
            // Q21 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carrying fundamentals (4 items):',
                'options' => null,
                'correct_answer' => '1. Muzzle control, 2. Trigger finger discipline, 3. Safe direction awareness, 4. Proper grip',
                'points' => 4,
            ],
            // Q22 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the shooting positions (4 positions):',
                'options' => null,
                'correct_answer' => 'Standing, Kneeling, Sitting, Prone',
                'points' => 4,
            ],
            // Q23 - Multiple choice (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the three MAIN parts of a firearm:',
                'options' => null,
                'correct_answer' => 'Stock, Action, Barrel',
                'points' => 3,
            ],
            // Q24 - Multiple choice (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The following are all types of actions (select 5):',
                'options' => null,
                'correct_answer' => 'Lever, Break or hinge, Bolt, Pump, Semi auto',
                'points' => 5,
            ],
            // Q25 - Matching (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the definitions to: Action, Trigger, Trigger guard, Barrel, Safety, Stock, Muzzle, Rifling',
                'options' => null,
                'correct_answer' => 'Action-Loads and fires ammunition, Trigger-Mechanism that actuates firing, Trigger guard-Loop protecting trigger, Barrel-Tube through which projectile travels, Safety-Mechanism preventing accidental discharge, Stock-Platform supporting action and barrel, Muzzle-End where projectile emerges, Rifling-Spiral grooves in barrel',
                'points' => 8,
            ],
            // Q26 - True/False (3 marks)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The rifle barrel is long and has thick walls with spiralling grooves cut into the bore. The grooved pattern is called rifling.',
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
                'question_text' => 'True or False: The handgun barrel is much shorter than a rifle or shotgun barrel because the gun is designed to be shot while being held with one or two hands.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q27 - Multiple choice (4 marks)
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
                'question_text' => 'Match the descriptions to: Bore, Muzzle, Cylinder, Breech, Magazine, Hammer, Trigger, Grip, Trigger Guard',
                'options' => null,
                'correct_answer' => 'Trigger Guard-wraps around trigger for protection, Breech-rear end of barrel where cartridge inserted, Muzzle-front end where projectile exits, Cylinder-part of revolver holding cartridges, Trigger-lever pulled to initiate firing, Hammer-strikes firing pin, Magazine-spring-operated container for cartridges, Grip-portion used to hold firearm, Bore-inside of barrel',
                'points' => 9,
            ],
            // Q29 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'A type of firearm which, utilizing some of the recoil or expanding-gas energy from the firing cartridge, cycles the action to eject the spent shell, chamber a fresh one and cock the mainspring. This describes what action?',
                'options' => ['A' => 'Bolt', 'B' => 'Pump', 'C' => 'Lever', 'D' => 'Semi-Auto'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q30 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the major parts of a shotgun:',
                'options' => null,
                'correct_answer' => 'Stock, Action, Barrel',
                'points' => 3,
            ],
            // Q31 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the different actions in shotguns:',
                'options' => null,
                'correct_answer' => 'Break or hinge, Pump, Semi-auto, Bolt',
                'points' => 4,
            ],
            // Q32 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Two very common safeties in shotguns are:',
                'options' => null,
                'correct_answer' => 'Cross-bolt safety, Tang safety',
                'points' => 2,
            ],
            // Q33 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the two common types of actions used in sport shooting – handguns:',
                'options' => null,
                'correct_answer' => 'Semi-automatic, Revolver',
                'points' => 2,
            ],
            // Q34 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the typical cartridge malfunctions:',
                'options' => null,
                'correct_answer' => 'Misfire, Hangfire, Squib load',
                'points' => 3,
            ],
            // Q35 - Multiple choice (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the steps for cleaning a firearm (6 steps):',
                'options' => null,
                'correct_answer' => '1. Safely unload the firearm, 2. Remove all ammunition from the cleaning area, 3. Dissemble the firearm for more thorough cleaning, 4. Use cloth and gun cleaning solvents to remove dirt, 5. Use cleaning rods, brushes, patches and solvent to clean the bore, 6. Apply a coating of gun oil to protect from rust',
                'points' => 6,
            ],
            // Q36 - Multiple choice (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle and Pistol Cartridge consist of four components:',
                'options' => null,
                'correct_answer' => 'The primer, The projectile (bullet), The case or shell, The powder',
                'points' => 4,
            ],
            // Q37 - Multiple choice (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'A Shotgun shell consists of (5 components):',
                'options' => null,
                'correct_answer' => 'Hull, Primer, Powder, Wad, Shot',
                'points' => 5,
            ],
            // Q38 - Multiple choice (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Common shotgun gauges are (5):',
                'options' => null,
                'correct_answer' => '10, 12, 16, 20, 28',
                'points' => 5,
            ],
            // Q39 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Basic parts of a bullet are:',
                'options' => null,
                'correct_answer' => 'Core, Jacket, Base, Tip/Nose',
                'points' => 4,
            ],
            // Q40 - Matching (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match definitions to: Ballistics, Twist, Trajectory, Air Resistance, Projectile',
                'options' => null,
                'correct_answer' => 'Projectile-Object set in motion by exterior force, Ballistics-Study of path of projectiles, Twist-Distance bullet travels for one revolution, Air Resistance-Without it projectile would not change velocity, Trajectory-Curve projectile describes in space',
                'points' => 5,
            ],
            // Q41 - Multiple choice (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'There are five different general shapes of hunting bullets:',
                'options' => null,
                'correct_answer' => 'Flat Point, Boat-Tail Spitzer, Semi-Spitzer, Round Nose, Spitzer',
                'points' => 5,
            ],
            // Q42 - Multiple choice (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the common handgun bullets (6):',
                'options' => null,
                'correct_answer' => 'Wadcutter, Lead hollow point, Full metal Jacket, Soft point, Hollow point, Lead round nose',
                'points' => 6,
            ],
            // Q43 - Matching (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the correct descriptions to: Projectile, Ballistics, Trajectory, Air Resistance, Gravity, Twist',
                'options' => null,
                'correct_answer' => 'Trajectory-Curve in space, Ballistics-Study of projectile path, Projectile-Object set in motion, Gravity-Without it projectile travels straight, Air Resistance-Without it velocity unchanged, Twist-Distance for one revolution',
                'points' => 6,
            ],
            // Q44 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Prohibited firearms and ammunition are:',
                'options' => null,
                'correct_answer' => 'Fully automatic firearms, Explosive/incendiary ammunition, Firearms disguised as other objects',
                'points' => 3,
            ],
            // Q45 - Multiple choice (3 marks)
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
                'question_text' => 'NRAPA Promotes the sustainable utilisation of wildlife as a __________ tool and promotes_______, __________hunting as well as sport shooting activities in a controlled environment.',
                'options' => null,
                'correct_answer' => 'Conservation, Ethical, Responsible',
                'points' => 3,
            ],
            // Q4 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA Promotes - To obey all_____, ________, _______of conduct and practices pertaining to ________and the private ________ of ____and ammunition.',
                'options' => null,
                'correct_answer' => 'Laws, Regulations, Codes, Arms, Possession, Firearms',
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
                'question_text' => 'As an ethical hunter, I will (select 6):',
                'options' => null,
                'correct_answer' => 'Actively support legal, safe and ethical hunting; Show respect for all wildlife and the environment; Take responsibility for my actions; Report vandalism, hunting violations or poaching; Show respect for myself and other people; Know and obey the laws and regulations for hunting',
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
            // Q12-16 - True/False (1 mark each)
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
            // Q18-23 - True/False (1 mark each)
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
                'question_text' => 'True or False: The hunting of captive-bred "listed large predators", white rhinos or black rhinos is prohibited by use of a gin (leghold) trap.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: No darting, except by a vet or person authorized by the vet for veterinary; scientific; management or transport purposes.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: For any hunting of any nature, even animals classified as "problem animals", by anyone other than the landowner and his immediate family no written permission of the landowner is required.',
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
                'question_text' => 'The director of _______________is empowered to issue special permits to make hunting legal under a variety of unusual circumstances.',
                'options' => ['A' => 'Finance', 'B' => 'Security', 'C' => 'Human resources', 'D' => 'Nature Conservation'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q25 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The use of semi-automatic or self-loading rifles to hunt ______or ________ game is prohibited.',
                'options' => null,
                'correct_answer' => 'Ordinary, Protected',
                'points' => 2,
            ],
            // Q26 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The use of semi-automatic or self-loading rifles may be used to hunt "______________" and "_______________"',
                'options' => null,
                'correct_answer' => 'Wild animals which is not game, Problem animals',
                'points' => 2,
            ],
            // Q27 - True/False (4 marks)
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
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'You may store another person\'s legally licensed firearm provided that you have a letter from the owner countersigned by the local DFO stating the period of storage (SAPS 539):',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q28 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Unless you have "dedicated status" you are restricted to _____ rounds of ammunition per licensed firearm, and a maximum of 2400 primers:',
                'options' => ['A' => '100', 'B' => '150', 'C' => '99', 'D' => '200'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q29 - Multiple choice (1 mark)
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
            // Q30 - Multiple choice (1 mark)
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
            // Q31 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: In South Africa, the right to possess firearms is guaranteed by law.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q32 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Certain firearms are categorized as prohibited firearms and cannot ordinarily be possessed or licensed under the FCA. List 4:',
                'options' => null,
                'correct_answer' => 'Projectile or rocket from cannon/mortar/rocket launcher, Gun/cannon/mortar/launcher for rockets/grenades/bombs, Altered firearm, Fully automatic firearm',
                'points' => 4,
            ],
            // Q33 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: A competency certificate to possess a firearm is valid for as long as the license to which it relates remains valid, unless the certificate is terminated or renewed.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q34 - Matching (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the definitions to: Dedicated Hunter, Hunting operator, Trophy, Dedicated Sports Person, Professional Hunter, Bona-fide hunter, Occasional Hunter',
                'options' => null,
                'correct_answer' => 'Hunting operator-Person who offers/organises hunting for fee, Professional Hunter-Licensed person who guides clients, Trophy-Mounted head/skin for display, Dedicated Sports Person-Active sports-shooting member, Dedicated Hunter-Active hunting association member, Occasional Hunter-From time to time hunts without membership, Bona-fide hunter-Old Arms Act category',
                'points' => 7,
            ],
            // Q35 - Matching (10 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the definitions to: License for Occasional Hunting/Sport Shooting, Devices not regarded as firearms, License for Private Collection, License for self-defense, Safekeeping, Temporary Authorization, Cartridge, License for Dedicated Hunting/Sport Shooting, Offenses and Penalties, Shoot',
                'options' => null,
                'correct_answer' => 'Self-defense-Only one license, Occasional-Maximum four 10-year licenses, Dedicated-Member of accredited association, Private Collection-Approved by collectors association, Temporary Authorization-Written motivation required, Offenses-Violation is offense, Shoot-Kill by firearm only, Safekeeping-Proper storage in safe, Devices not firearms-Air gun/paintball/etc, Cartridge-Case/primer/propellant/bullet',
                'points' => 10,
            ],
            // Q36 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Complete the Period of validity: Section 13 (self-defense), Section 16 (dedicated), Section 20 (business other than hunting)',
                'options' => null,
                'correct_answer' => 'Section 13: Five years, Section 16: Ten years, Section 20 (non-hunting): Two years',
                'points' => 3,
            ],
            // Q37 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List Four main types of hunting related shooting incidents:',
                'options' => null,
                'correct_answer' => 'Lack of control of the firearm, Human error and/or judgment mistakes, Safety rule violations, Equipment or ammunition failure',
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
                'question_text' => 'The fundamental NRAPA rules for safe gun handling are (4 rules):',
                'options' => null,
                'correct_answer' => 'ALWAYS keep the gun pointed in a safe direction, ALWAYS keep the gun unloaded until ready to use, ALWAYS keep your finger off the trigger until ready to shoot, ALWAYS make sure the safety is engaged',
                'points' => 4,
            ],
            // Q40 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The following are all types of actions (5):',
                'options' => null,
                'correct_answer' => 'Lever, Break or hinge, Bolt, Pump, Semi auto',
                'points' => 5,
            ],
            // Q41 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the steps for cleaning a firearm (6 steps):',
                'options' => null,
                'correct_answer' => 'Safely unload the firearm, Remove all ammunition from cleaning area, Dissemble for thorough cleaning, Use cloth and solvents to remove dirt, Use cleaning rods/brushes/patches for bore, Apply gun oil to protect from rust',
                'points' => 6,
            ],
            // Q42 - Matching (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the definitions to: Action, Trigger, Trigger guard, Barrel, Safety, Stock, Muzzle, Rifling',
                'options' => null,
                'correct_answer' => 'Barrel-Tube for projectile, Action-Loads and fires, Stock-Platform supporting action/barrel, Trigger-Actuates firing, Safety-Prevents accidental discharge, Muzzle-Projectile emerges, Rifling-Twist rate, Trigger guard-Loop protecting trigger',
                'points' => 8,
            ],
            // Q43 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle and Pistol Cartridge consist of four components:',
                'options' => null,
                'correct_answer' => 'The primer, The projectile (bullet), The case or shell, The powder',
                'points' => 4,
            ],
            // Q44 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing a Fence – Recommended action:',
                'options' => [
                    'A' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence. Check barrel for debris.',
                    'B' => 'Place the rifle through the fence holding the grip. The rifle must be pointed towards yourself and others. Place on the other side of the fence.',
                    'C' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Climb through the fence with the rifle still in your hand.'
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q45 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle carrying techniques (5):',
                'options' => null,
                'correct_answer' => 'Elbow or side carry, Sling carry, Shoulder carry, Cradle carry, Two Handed ready carry',
                'points' => 5,
            ],
            // Q46 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Differences Between Rifles, Shotguns, and Handguns:',
                'options' => [
                    'A' => 'The main differences are their scopes and the type of sights used',
                    'B' => 'The main differences are their barrels and the type of ammunition used',
                    'C' => 'The main differences are their weight and the type of stock used'
                ],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q47 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'A rifle or pistol cartridge consist of four components:',
                'options' => null,
                'correct_answer' => 'The case or shell, The projectile (bullet), The powder, The primer',
                'points' => 4,
            ],
            // Q48 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'A Shotgun shell consists of (5 components):',
                'options' => null,
                'correct_answer' => 'Hull, Primer, Powder, Wad, Shot',
                'points' => 5,
            ],
            // Q49 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Common shotgun gauges are (5):',
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
                'question_text' => 'Match definitions to: Ballistics, Twist, Trajectory, Air Resistance, Projectile',
                'options' => null,
                'correct_answer' => 'Projectile-Object set in motion, Ballistics-Study of projectile path, Twist-Distance for one revolution, Air Resistance-Without it velocity unchanged, Trajectory-Curve in space',
                'points' => 5,
            ],
            // Q52 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the steps to safely clean a firearm (6 steps):',
                'options' => null,
                'correct_answer' => 'Safely unload, Remove all ammunition, Use cloth and solvents, Use cleaning rods/brushes/patches, Dissemble for thorough cleaning, Apply gun oil',
                'points' => 6,
            ],
            // Q53 - Written/Image (6 marks) - Animal identification
            [
                'question_type' => 'written',
                'question_text' => 'Animal Identification: Identify the animals from their tracks (6 animals). Note: This question requires images to be displayed.',
                'options' => null,
                'correct_answer' => 'Leopard, Rhino, Mountain Zebra, Burchells Zebra, Warthog, Duiker',
                'points' => 6,
            ],
            // Q54 - Written/Image (1 mark)
            [
                'question_type' => 'written',
                'question_text' => 'Which direction is the animal walking based on the track? Note: This question requires an image to be displayed.',
                'options' => null,
                'correct_answer' => 'Right to left',
                'points' => 1,
            ],
            // Q55 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The first three survival priorities are:',
                'options' => null,
                'correct_answer' => 'Find water, Take shelter, To keep warm (or cool)',
                'points' => 3,
            ],
            // Q56 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'A fire making kit should consist of (5 items):',
                'options' => null,
                'correct_answer' => 'Lighter, Matches, Steel wool/battery, Magnifying glass, Magnesium bar',
                'points' => 5,
            ],
            // Q57 - Matching (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match definitions to: External Bleeding, Fainting, Bandaging, Burn, Shock, Rabies, Ticks',
                'options' => null,
                'correct_answer' => 'Shock-Poor circulation to vital organs, Fainting-Temporary condition similar to shock, External Bleeding-Blood escaping from cut vessels, Bandaging-Control severe bleeding, Burn-Damage from heat, Rabies-Virus from warm blooded animals, Ticks-Insect-like bugs in woods',
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
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q2 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA Promotes - To obey all ____, _________, _______ and practices pertaining to _________and the private ________ of _______ and ammunition.',
                'options' => null,
                'correct_answer' => 'Laws, Regulations, Codes of conduct, Arms, Possession, Firearms',
                'points' => 6,
            ],
            // Q3 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The Fundamental NRAPA Rules for Safe Gun Handling are (6 rules):',
                'options' => null,
                'correct_answer' => 'ALWAYS keep gun pointed in safe direction, ALWAYS keep gun unloaded until ready, ALWAYS keep finger off trigger, ALWAYS make sure safety engaged, Know your target and beyond, Be sure gun is safe to operate',
                'points' => 6,
            ],
            // Q4 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Disciplinary Action shall exist for (3 items):',
                'options' => null,
                'correct_answer' => 'Unsafe handling of firearms, Violation of range rules, Unsportsmanlike conduct',
                'points' => 3,
            ],
            // Q5 - True/False (3 marks)
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
                'correct_answer' => 'A',
                'points' => 1,
            ],
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
                'question_text' => 'Unless you have "dedicated status" you are restricted to _______ rounds of ammunition per licensed firearm:',
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
                    'D' => 'Slingshot'
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
                    'C' => 'Explosive-powered tools designed for industrial application for splitting rocks or concrete'
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
            // Q10 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Certain firearms are categorized as prohibited firearms and cannot ordinarily be possessed or licensed under the FCA. List 4:',
                'options' => null,
                'correct_answer' => 'Projectile or rocket from cannon/mortar/rocket launcher, Gun/cannon/mortar/launcher for rockets/grenades/bombs, Altered firearm, Fully automatic firearm',
                'points' => 4,
            ],
            // Q11 - True/False (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: A competency certificate to possess a firearm is valid for as long as the license to which it relates remains valid.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q12 - Matching (10 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the definitions to: License for Occasional Hunting/Sport Shooting, Devices not regarded as firearms, License for Private Collection, License for self-defense, Safekeeping, Temporary Authorization, Cartridge, License for Dedicated Hunting/Sport Shooting, Offenses and Penalties, Shoot',
                'options' => null,
                'correct_answer' => 'Self-defense-Only one license, Occasional-Maximum four 10-year licenses, Dedicated-Member of accredited association, Private Collection-Approved by collectors association, Temporary Authorization-Written motivation required, Offenses-Violation is offense, Shoot-Kill by firearm only, Safekeeping-Proper storage in safe, Devices not firearms-Air gun/paintball/etc, Cartridge-Case/primer/propellant/bullet',
                'points' => 10,
            ],
            // Q13 - Matching (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the definitions to: Dedicated Hunter, Hunting operator, Trophy, Dedicated Sports Person, Professional Hunter, Bona-fide hunter, Occasional Hunter',
                'options' => null,
                'correct_answer' => 'Hunting operator-Person who offers/organises hunting for fee, Professional Hunter-Licensed person who guides clients, Trophy-Mounted head/skin for display, Dedicated Sports Person-Active sports-shooting member, Dedicated Hunter-Active hunting association member, Occasional Hunter-From time to time hunts without membership, Bona-fide hunter-Old Arms Act category',
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
                'question_text' => 'Match the descriptions to: Bore, Muzzle, Cylinder, Breech, Magazine, Hammer, Trigger, Grip, Trigger Guard',
                'options' => null,
                'correct_answer' => 'Trigger Guard-wraps around trigger, Breech-rear end of barrel, Muzzle-front end where projectile exits, Cylinder-revolver part holding cartridges, Trigger-lever initiating firing, Hammer-strikes firing pin, Magazine-container for cartridges, Grip-portion to hold firearm, Bore-inside of barrel',
                'points' => 9,
            ],
            // Q16 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Complete the Period of validity: Section 13 (self-defense), Section 16 (dedicated), Section 20 (business other than hunting)',
                'options' => null,
                'correct_answer' => 'Section 13: Five years, Section 16: Ten years, Section 20 (non-hunting): Two years',
                'points' => 3,
            ],
            // Q17 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the three MAIN parts of a firearm:',
                'options' => null,
                'correct_answer' => 'Stock, Action, Barrel',
                'points' => 3,
            ],
            // Q18 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The following are all types of actions (5):',
                'options' => null,
                'correct_answer' => 'Lever, Break or hinge, Bolt, Pump, Semi auto',
                'points' => 5,
            ],
            // Q19 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the steps to safely cleaning a firearm (6 steps):',
                'options' => null,
                'correct_answer' => 'Safely unload, Remove all ammunition, Dissemble for cleaning, Use cloth and solvents, Use cleaning rods/brushes/patches, Apply gun oil',
                'points' => 6,
            ],
            // Q20 - Matching (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the definitions to: Action, Trigger, Trigger guard, Barrel, Safety, Stock, Muzzle, Rifling',
                'options' => null,
                'correct_answer' => 'Barrel-Tube for projectile, Action-Loads and fires, Stock-Platform, Trigger-Actuates firing, Safety-Prevents discharge, Muzzle-Projectile emerges, Rifling-Twist rate, Trigger guard-Loop protecting trigger',
                'points' => 8,
            ],
            // Q21 - Matching (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match the descriptions to: Projectile, Ballistics, Trajectory, Air Resistance, Gravity, Twist',
                'options' => null,
                'correct_answer' => 'Trajectory-Curve in space, Ballistics-Study of projectile path, Projectile-Object set in motion, Gravity-Without it travels straight, Air Resistance-Without it velocity unchanged, Twist-Distance for one revolution',
                'points' => 6,
            ],
            // Q22 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Prohibited firearms and ammunition are (3):',
                'options' => null,
                'correct_answer' => 'Fully automatic firearms, Explosive/incendiary ammunition, Firearms disguised as other objects',
                'points' => 3,
            ],
            // Q23 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle and Pistol Cartridge consist of four components:',
                'options' => null,
                'correct_answer' => 'The primer, The projectile (bullet), The case or shell, The powder',
                'points' => 4,
            ],
            // Q24 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the major parts of a shotgun:',
                'options' => null,
                'correct_answer' => 'Stock, Action, Barrel',
                'points' => 3,
            ],
            // Q25 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the different actions in shotguns:',
                'options' => null,
                'correct_answer' => 'Break or hinge, Pump, Semi-auto, Bolt',
                'points' => 4,
            ],
            // Q26 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'A Shotgun shell consists of (5 components):',
                'options' => null,
                'correct_answer' => 'Hull, Primer, Powder, Wad, Shot',
                'points' => 5,
            ],
            // Q27 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Common shotgun gauges are (5):',
                'options' => null,
                'correct_answer' => '10, 12, 16, 20, 28',
                'points' => 5,
            ],
            // Q28 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Differences Between Rifles, Shotguns, and Handguns:',
                'options' => [
                    'A' => 'The main differences are their scopes and the type of sights used',
                    'B' => 'The main differences are their barrels and the type of ammunition used',
                    'C' => 'The main differences are their weight and the type of stock used'
                ],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q29 - Matching (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match definitions to: Ballistics, Twist, Trajectory, Air Resistance, Projectile',
                'options' => null,
                'correct_answer' => 'Projectile-Object set in motion, Ballistics-Study of projectile path, Twist-Distance for one revolution, Air Resistance-Without it velocity unchanged, Trajectory-Curve in space',
                'points' => 5,
            ],
            // Q30 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the typical cartridge malfunctions:',
                'options' => null,
                'correct_answer' => 'Misfire, Hangfire, Squib load',
                'points' => 3,
            ],
            // Q31 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Basic parts of a bullet are:',
                'options' => null,
                'correct_answer' => 'Core, Jacket, Base, Tip/Nose',
                'points' => 4,
            ],
            // Q32 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'There are five different general shapes of hunting bullets:',
                'options' => null,
                'correct_answer' => 'Flat Point, Boat-Tail Spitzer, Semi-Spitzer, Round Nose, Spitzer',
                'points' => 5,
            ],
            // Q33 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the common handgun bullets (6):',
                'options' => null,
                'correct_answer' => 'Wadcutter, Lead hollow point, Full metal Jacket, Soft point, Hollow point, Lead round nose',
                'points' => 6,
            ],
            // Q34 - Multiple choice (1 mark)
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
            // Q35 - Multiple choice (1 mark)
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
            // Q36 - Written (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'As an ethical hunter, I will (select 6):',
                'options' => null,
                'correct_answer' => 'Actively support legal, safe and ethical hunting; Show respect for all wildlife; Take responsibility for my actions; Report violations or poaching; Show respect for people; Know and obey hunting laws',
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
            // Q38 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'NRAPA Promotes the sustainable utilisation of wildlife as a __________ tool and promotes_______, __________hunting.',
                'options' => null,
                'correct_answer' => 'Conservation, Ethical, Responsible',
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
                    'D' => 'Improves sport shooting skills'
                ],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q40 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => '______chase balances the skills and equipment of the hunter with the abilities of the animal to escape:',
                'options' => ['A' => 'Unfair', 'B' => 'Responsible', 'C' => 'Fair', 'D' => 'Controlled'],
                'correct_answer' => 'C',
                'points' => 1,
            ],
            // Q41 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List the Protected or endangered species categories (5):',
                'options' => null,
                'correct_answer' => 'Critically Endangered Species, Endangered Species, Vulnerable Species, Protected Species, Conservation status of huntable species',
                'points' => 5,
            ],
            // Q42 - Multiple choice (1 mark)
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
            // Q43-47 - True/False (1 mark each)
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
            // Q48 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'No _______hunting of listed large predators, white rhino, black rhino, crocodile or elephant:',
                'options' => ['A' => 'Rifle', 'B' => 'Bow'],
                'correct_answer' => 'B',
                'points' => 1,
            ],
            // Q49-54 - True/False (1 mark each)
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
                'question_text' => 'True or False: The hunting of captive-bred "listed large predators", white rhinos or black rhinos is prohibited by use of a gin (leghold) trap.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: No darting, except by a vet or person authorized by the vet for veterinary; scientific; management or transport purposes.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: For any hunting of any nature, even animals classified as "problem animals", by anyone other than the landowner and his immediate family no written permission of the landowner is required.',
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
            // Q55 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'The director of _______________is empowered to issue special permits to make hunting legal under a variety of unusual circumstances.',
                'options' => ['A' => 'Finance', 'B' => 'Security', 'C' => 'Human resources', 'D' => 'Nature Conservation'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q56 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The use of semi-automatic or self-loading rifles to hunt ______or ________ game is prohibited.',
                'options' => null,
                'correct_answer' => 'Ordinary, Protected',
                'points' => 2,
            ],
            // Q57 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The use of semi-automatic or self-loading rifles may be used to hunt "______________" and "_______________"',
                'options' => null,
                'correct_answer' => 'Wild animals which is not game, Problem animals',
                'points' => 2,
            ],
            // Q58 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List Four main types of hunting related shooting incidents:',
                'options' => null,
                'correct_answer' => 'Lack of control of the firearm, Human error and/or judgment mistakes, Safety rule violations, Equipment or ammunition failure',
                'points' => 4,
            ],
            // Q59 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'Crossing a Fence – Recommended action:',
                'options' => [
                    'A' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Place on the other side of the fence without getting debris into the barrel. Climb through the fence. Check barrel for debris.',
                    'B' => 'Place the rifle through the fence holding the grip. The rifle must be pointed towards yourself and others.',
                    'C' => 'Place the rifle through the fence holding the grip. The rifle must be pointed away from yourself and others. Climb through the fence with the rifle still in your hand.'
                ],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q60 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Rifle carrying techniques (5):',
                'options' => null,
                'correct_answer' => 'Elbow or side carry, Sling carry, Shoulder carry, Cradle carry, Two Handed ready carry',
                'points' => 5,
            ],
            // Q61 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Types of shots (4):',
                'options' => null,
                'correct_answer' => 'Frontal, Broadside, Quartering forward, Quartering away',
                'points' => 4,
            ],
            // Q62 - Written/Image (6 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Animal Identification: Identify the animals from their tracks (6 animals). Note: This question requires images to be displayed.',
                'options' => null,
                'correct_answer' => 'Leopard, Rhino, Mountain Zebra, Burchells Zebra, Warthog, Duiker',
                'points' => 6,
            ],
            // Q63 - Written/Image (1 mark)
            [
                'question_type' => 'written',
                'question_text' => 'Which direction is the animal walking based on the track? Note: This question requires an image.',
                'options' => null,
                'correct_answer' => 'Right to left',
                'points' => 1,
            ],
            // Q64 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'The first three survival priorities are:',
                'options' => null,
                'correct_answer' => 'Find water, Take shelter, To keep warm (or cool)',
                'points' => 3,
            ],
            // Q65 - Written (5 marks)
            [
                'question_type' => 'written',
                'question_text' => 'A fire making kit should consist of (5 items):',
                'options' => null,
                'correct_answer' => 'Lighter, Matches, Steel wool/battery, Magnifying glass, Magnesium bar',
                'points' => 5,
            ],
            // Q66 - Matching (7 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Match definitions to: External Bleeding, Fainting, Bandaging, Burn, Shock, Rabies, Ticks',
                'options' => null,
                'correct_answer' => 'Shock-Poor circulation to vital organs, Fainting-Temporary condition, External Bleeding-Blood escaping, Bandaging-Control severe bleeding, Burn-Damage from heat, Rabies-Virus from warm blooded animals, Ticks-Insect-like bugs in woods',
                'points' => 7,
            ],
            // Q67 - Written (8 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carry techniques (8):',
                'options' => null,
                'correct_answer' => 'Elbow or side carry, Sling carry, Shoulder carry, Cradle carry, Two Handed ready carry, Trail carry, Ready carry, Port arms',
                'points' => 8,
            ],
            // Q68 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the rifle carrying fundamentals (4):',
                'options' => null,
                'correct_answer' => 'Muzzle control, Trigger finger discipline, Safe direction awareness, Proper grip',
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
            // Q70 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'List Four main types of shooting related shooting incidents:',
                'options' => null,
                'correct_answer' => 'Lack of control of the firearm, Human error and/or judgment mistakes, Safety rule violations, Equipment or ammunition failure',
                'points' => 4,
            ],
            // Q71 - Written (4 marks)
            [
                'question_type' => 'written',
                'question_text' => 'There are four standard bolt action rifle shooting positions:',
                'options' => null,
                'correct_answer' => 'Standing, Kneeling, Prone, Sitting',
                'points' => 4,
            ],
            // Q72 - True/False (3 marks)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'True or False: The rifle barrel is long and has thick walls with spiralling grooves cut into the bore. The grooved pattern is called rifling.',
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
                'question_text' => 'True or False: The handgun barrel is much shorter than a rifle or shotgun barrel because the gun is designed to be shot while being held with one or two hands.',
                'options' => ['A' => 'True', 'B' => 'False'],
                'correct_answer' => 'A',
                'points' => 1,
            ],
            // Q73 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Two very common safeties in shotguns are:',
                'options' => null,
                'correct_answer' => 'Cross-bolt safety, Tang safety',
                'points' => 2,
            ],
            // Q74 - Written (2 marks)
            [
                'question_type' => 'written',
                'question_text' => 'Name the two common types of actions used in sport shooting – handguns:',
                'options' => null,
                'correct_answer' => 'Semi-automatic, Revolver',
                'points' => 2,
            ],
            // Q75 - Multiple choice (1 mark)
            [
                'question_type' => 'multiple_choice',
                'question_text' => 'A type of firearm which, utilizing some of the recoil or expanding-gas energy from the firing cartridge, cycles the action to eject the spent shell, chamber a fresh one and cock the mainspring. This describes what action?',
                'options' => ['A' => 'Bolt', 'B' => 'Pump', 'C' => 'Lever', 'D' => 'Semi-Auto'],
                'correct_answer' => 'D',
                'points' => 1,
            ],
            // Q76 - Written (3 marks)
            [
                'question_type' => 'written',
                'question_text' => 'There are three basic categories of shooting ranges:',
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
