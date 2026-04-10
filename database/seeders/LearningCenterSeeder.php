<?php

namespace Database\Seeders;

use App\Models\LearningArticle;
use App\Models\LearningArticlePage;
use App\Models\LearningCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LearningCenterSeeder extends Seeder
{
    public function run(): void
    {
        // Set all existing learning articles to NRAPA (null author)
        LearningArticle::query()->update(['author_id' => null]);

        $this->seedSharedContent();
        $this->seedDedicatedHunterContent();
        $this->seedDedicatedSportShooterContent();
        $this->seedDedicatedBothContent();

        $this->command->info('Learning Center seeded successfully.');
    }

    protected function ensureCategory(string $name, string $slug, ?string $dedicatedType, string $description, int $sortOrder): LearningCategory
    {
        return LearningCategory::updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $description,
                'sort_order' => $sortOrder,
                'is_active' => true,
                'dedicated_type' => $dedicatedType,
            ]
        );
    }

    protected function ensureArticle(LearningCategory $category, array $data, ?string $dedicatedType = null): LearningArticle
    {
        $slug = $data['slug'] ?? Str::slug($data['title']);

        return LearningArticle::updateOrCreate(
            ['slug' => $slug],
            [
                'learning_category_id' => $category->id,
                'author_id' => null, // NRAPA as author (displayed via getAuthorNameAttribute)
                'title' => $data['title'],
                'excerpt' => $data['excerpt'] ?? Str::limit(strip_tags($data['content'] ?? ''), 200),
                'content' => $data['content'],
                'featured_image' => $data['featured_image'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'is_published' => true,
                'is_featured' => $data['featured'] ?? false,
                'published_at' => now(),
                'dedicated_type' => $dedicatedType ?? $category->dedicated_type,
            ]
        );
    }

    /**
     * Seed pages for an article. Each entry: ['title' => ..., 'content' => ..., 'image_path' => ..., 'image_caption' => ...]
     */
    protected function ensurePages(LearningArticle $article, array $pages): void
    {
        foreach ($pages as $index => $page) {
            LearningArticlePage::updateOrCreate(
                [
                    'learning_article_id' => $article->id,
                    'page_number' => $index + 1,
                ],
                [
                    'title' => $page['title'],
                    'content' => $page['content'],
                    'image_path' => $page['image_path'] ?? null,
                    'image_caption' => $page['image_caption'] ?? null,
                ]
            );
        }

        LearningArticlePage::where('learning_article_id', $article->id)
            ->where('page_number', '>', count($pages))
            ->delete();
    }

    /**
     * Copy NRAPA logo to learning center storage and return the path.
     */
    protected function getLearningCenterLogoPath(): ?string
    {
        $logoPath = public_path('nrapa-logo.png');
        if (! file_exists($logoPath)) {
            return null;
        }
        $destPath = 'learning/articles/nrapa-logo.png';
        Storage::disk('public')->put($destPath, file_get_contents($logoPath));

        return $destPath;
    }

    /**
     * Copy a question-image into the learning center storage and return
     * the storage path (relative to public disk). Returns null if source missing.
     */
    protected function copyQuestionImageToLearning(string $sourceFilename, string $destFilename): ?string
    {
        $sourcePath = storage_path('app/public/question-images/'.$sourceFilename);
        if (! file_exists($sourcePath)) {
            return null;
        }
        $destPath = 'learning/articles/'.$destFilename;
        Storage::disk('public')->put($destPath, file_get_contents($sourcePath));

        return $destPath;
    }

    /**
     * Get the public URL for a learning article image path.
     */
    protected function learningImageUrl(string $storagePath): string
    {
        return '/storage/'.$storagePath;
    }

    protected function seedSharedContent(): void
    {
        $cat = $this->ensureCategory(
            'Firearm Safety & Legal Basics',
            'firearm-safety-legal-basics',
            null,
            'Core safety rules and legal requirements for all members.',
            10
        );

        $this->ensureArticle($cat, [
            'title' => 'Fundamental NRAPA Rules for Safe Gun Handling',
            'slug' => 'fundamental-nrapa-rules-safe-gun-handling',
            'excerpt' => 'The six fundamental rules every member must follow for safe firearm handling.',
            'content' => $this->contentFundamentalRules(),
            'sort_order' => 1,
            'featured' => true,
        ]);

        $this->ensureArticle($cat, [
            'title' => 'Disciplinary Action and Code of Ethics',
            'slug' => 'disciplinary-action-code-ethics',
            'excerpt' => 'When and why NRAPA may take disciplinary action against members.',
            'content' => $this->contentDisciplinaryAction(),
            'sort_order' => 2,
        ]);

        $this->ensureArticle($cat, [
            'title' => 'Three Main Parts of a Firearm',
            'slug' => 'three-main-parts-firearm',
            'excerpt' => 'Stock, action, and barrel—the building blocks of every firearm.',
            'content' => $this->contentThreeMainParts(),
            'sort_order' => 3,
            'featured_image' => $this->getLearningCenterLogoPath(),
        ]);

        $this->ensureArticle($cat, [
            'title' => 'Types of Firearm Actions',
            'slug' => 'types-firearm-actions',
            'excerpt' => 'Lever, break/hinge, bolt, pump, and semi-automatic actions explained.',
            'content' => $this->contentTypesOfActions(),
            'sort_order' => 4,
        ]);

        $this->ensureArticle($cat, [
            'title' => 'Steps to Safely Clean a Firearm',
            'slug' => 'steps-safely-clean-firearm',
            'excerpt' => 'Correct sequence for cleaning your firearm safely.',
            'content' => $this->contentCleaningSteps(),
            'sort_order' => 5,
        ]);
    }

    protected function seedDedicatedHunterContent(): void
    {
        $dedicatedType = 'hunter';

        $cat1 = $this->ensureCategory(
            'Hunter Ethics and Conservation',
            'hunter-ethics-conservation',
            $dedicatedType,
            'Ethical hunting, fair chase, and wildlife conservation.',
            20
        );

        $this->ensureArticle($cat1, [
            'title' => 'Single Shot Humane Kill and Fair Chase',
            'slug' => 'single-shot-humane-kill-fair-chase',
            'excerpt' => 'Why NRAPA promotes a single-shot humane kill and what fair chase means.',
            'content' => $this->contentSingleShotFairChase(),
            'sort_order' => 1,
            'featured' => true,
        ], $dedicatedType);

        $this->ensureArticle($cat1, [
            'title' => 'Ethical Hunter Commitment',
            'slug' => 'ethical-hunter-commitment',
            'excerpt' => 'Six commitments every ethical hunter makes.',
            'content' => $this->contentEthicalHunterCommitment(),
            'sort_order' => 2,
        ], $dedicatedType);

        $this->ensureArticle($cat1, [
            'title' => 'Sustainable Use and Responsible Hunting',
            'slug' => 'sustainable-use-responsible-hunting',
            'excerpt' => 'Wildlife as a conservation tool and ethical, responsible hunting.',
            'content' => $this->contentSustainableUse(),
            'sort_order' => 3,
        ], $dedicatedType);

        $this->ensureArticle($cat1, [
            'title' => 'Dedicated Hunter or Sport Shooter Status',
            'slug' => 'dedicated-hunter-sport-shooter-status',
            'excerpt' => 'Requirements for dedicated status, activities, and annual reporting.',
            'content' => $this->contentDedicatedHunterStatus(),
            'sort_order' => 4,
        ], $dedicatedType);

        $cat2 = $this->ensureCategory(
            'Protected and Endangered Species',
            'protected-endangered-species',
            $dedicatedType,
            'CITES, species categories, and conservation status in South Africa.',
            30
        );

        $this->ensureArticle($cat2, [
            'title' => 'Protected and Endangered Species Categories',
            'slug' => 'protected-endangered-species-categories',
            'excerpt' => 'Critically Endangered, Endangered, Vulnerable, Protected, and huntable species.',
            'content' => $this->contentSpeciesCategories(),
            'sort_order' => 1,
        ], $dedicatedType);

        $this->ensureArticle($cat2, [
            'title' => 'CITES and International Trade',
            'slug' => 'cites-international-trade',
            'excerpt' => 'What CITES stands for and why it matters for hunters.',
            'content' => $this->contentCITES(),
            'sort_order' => 2,
        ], $dedicatedType);

        $cat3 = $this->ensureCategory(
            'Hunting Regulations and Firearms',
            'hunting-regulations-firearms',
            $dedicatedType,
            'Lights, bow hunting, semi-automatics, landowner permission, and more.',
            40
        );

        $this->ensureArticle($cat3, [
            'title' => 'Key Hunting Regulations',
            'slug' => 'key-hunting-regulations',
            'excerpt' => 'Lights, captive-bred predators, darting, landowner permission, semi-automatic rifles.',
            'content' => $this->contentKeyHuntingRegulations(),
            'sort_order' => 1,
        ], $dedicatedType);

        $this->ensureArticle($cat3, [
            'title' => 'Rifle Carrying Techniques and Fundamentals',
            'slug' => 'rifle-carrying-techniques-fundamentals',
            'excerpt' => 'Eight rifle carry techniques and four carrying fundamentals.',
            'content' => $this->contentRifleCarrying(),
            'sort_order' => 2,
        ], $dedicatedType);

        $this->ensureArticle($cat3, [
            'title' => 'Types of Shots and Crossing a Fence',
            'slug' => 'types-shots-crossing-fence',
            'excerpt' => 'Correct shot placement and safe procedure for crossing a fence with a rifle.',
            'content' => $this->contentTypesOfShotsFence(),
            'sort_order' => 3,
        ], $dedicatedType);

        $this->ensureArticle($cat3, [
            'title' => 'Hunting Rifle Ballistics',
            'slug' => 'hunting-rifle-ballistics',
            'excerpt' => 'Trajectory, ballistic coefficient, sighting-in, and shooting uphill or downhill.',
            'content' => $this->contentHuntingRifleBallistics(),
            'sort_order' => 4,
        ], $dedicatedType);

        $this->ensureArticle($cat3, [
            'title' => 'Caliber Selection',
            'slug' => 'caliber-selection',
            'excerpt' => 'Using an adequate caliber for the animal and legal restrictions on caliber.',
            'content' => $this->contentCaliberSelection(),
            'sort_order' => 5,
        ], $dedicatedType);

        $cat4 = $this->ensureCategory(
            'Animal Identification and Fieldcraft',
            'animal-identification-fieldcraft',
            $dedicatedType,
            'Planning a hunt, track identification, tracking, survival priorities, and first aid.',
            50
        );

        $this->ensureArticle($cat4, [
            'title' => 'Planning a Hunt',
            'slug' => 'planning-a-hunt',
            'excerpt' => 'When, what, where, how and why—licences, permits, documentation and ethics.',
            'content' => $this->contentPlanningAHunt(),
            'sort_order' => 0,
        ], $dedicatedType);

        $tracksImage = $this->copyQuestionImageToLearning('animal-tracks-test.png', 'animal-tracks-test.png');
        $directionImage = $this->copyQuestionImageToLearning('animal-tracks-direction.png', 'animal-tracks-direction.png');

        $tracksArticle = $this->ensureArticle($cat4, [
            'title' => 'African Animal Tracks (Spoor) Identification',
            'slug' => 'african-animal-tracks-spoor-identification',
            'excerpt' => 'Identify Leopard, Hippo, Rhino, Burchell\'s Zebra, Warthog, Sitatunga and others from tracks.',
            'content' => $this->contentAnimalTracksIntro($tracksImage),
            'sort_order' => 1,
            'featured' => true,
            'featured_image' => $tracksImage,
        ], $dedicatedType);

        $this->ensurePages($tracksArticle, [
            ['title' => 'Track A — Leopard', 'content' => $this->contentTrackLeopard()],
            ['title' => 'Track B — Hippo', 'content' => $this->contentTrackHippo()],
            ['title' => 'Track C — Rhino', 'content' => $this->contentTrackRhino()],
            ['title' => 'Track D — Burchell\'s Zebra', 'content' => $this->contentTrackZebra()],
            ['title' => 'Track E — Warthog', 'content' => $this->contentTrackWarthog()],
            ['title' => 'Track F — Sitatunga', 'content' => $this->contentTrackSitatunga()],
        ]);

        $this->ensureArticle($cat4, [
            'title' => 'Direction of Travel from Tracks',
            'slug' => 'direction-travel-tracks',
            'excerpt' => 'How to read which way an animal was walking from its tracks.',
            'content' => $this->contentDirectionOfTravel($directionImage),
            'sort_order' => 2,
            'featured_image' => $directionImage,
        ], $dedicatedType);

        $this->ensureArticle($cat4, [
            'title' => 'Tracking (Spoor and Sign)',
            'slug' => 'tracking-spoor-and-sign',
            'excerpt' => 'The science and art of observing tracks and signs to understand the animal and landscape.',
            'content' => $this->contentTrackingSpoorSign(),
            'sort_order' => 3,
        ], $dedicatedType);

        $this->ensureArticle($cat4, [
            'title' => 'Survival Priorities and Fire-Making Kit',
            'slug' => 'survival-priorities-fire-making-kit',
            'excerpt' => 'First three survival priorities and what to include in a fire-making kit.',
            'content' => $this->contentSurvivalFireKit(),
            'sort_order' => 4,
        ], $dedicatedType);

        $this->ensureArticle($cat4, [
            'title' => 'First Aid Terms: Shock, Fainting, Bleeding, Burns, Rabies, Ticks',
            'slug' => 'first-aid-terms-field',
            'excerpt' => 'Definitions of common first aid terms relevant to hunting and the field.',
            'content' => $this->contentFirstAidTerms(),
            'sort_order' => 5,
        ], $dedicatedType);
    }

    protected function seedDedicatedSportShooterContent(): void
    {
        $dedicatedType = 'sport';

        $cat1 = $this->ensureCategory(
            'NRAPA Sport Shooting and Education',
            'nrapa-sport-shooting-education',
            $dedicatedType,
            'What NRAPA promotes and why sport shooting education matters.',
            60
        );

        $this->ensureArticle($cat1, [
            'title' => 'NRAPA Promotes Lawful Sport Shooting and Compliance',
            'slug' => 'nrapa-promotes-postal-compliance',
            'excerpt' => 'Active participation in lawful sport shooting and obeying laws and regulations.',
            'content' => $this->contentNRAPAPostalCompliance(),
            'sort_order' => 1,
        ], $dedicatedType);

        $this->ensureArticle($cat1, [
            'title' => 'Purpose of Sport Shooter Education',
            'slug' => 'purpose-sport-shooter-education',
            'excerpt' => 'Producing safe, responsible, knowledgeable and involved sport shooters.',
            'content' => $this->contentPurposeSportEducation(),
            'sort_order' => 2,
        ], $dedicatedType);

        $this->ensureArticle($cat1, [
            'title' => 'Dedicated Sport Shooter Status',
            'slug' => 'dedicated-sport-shooter-status',
            'excerpt' => 'Requirements for dedicated status, activities, and annual reporting.',
            'content' => $this->contentDedicatedSportShooterStatus(),
            'sort_order' => 3,
        ], $dedicatedType);

        $this->ensureArticle($cat1, [
            'title' => 'Postal-style shooting (reference)',
            'slug' => 'postal-shooting',
            'excerpt' => 'What postal-style shooting means in the sport, and logging qualifying activities in the portal.',
            'content' => $this->contentPostalShooting(),
            'sort_order' => 4,
        ], $dedicatedType);

        $cat2 = $this->ensureCategory(
            'FCA and Licensing',
            'fca-licensing-sport',
            $dedicatedType,
            'Firearms Control Act, license types, ammunition limits, and validity.',
            70
        );

        $this->ensureArticle($cat2, [
            'title' => 'Ammunition Limits and Dedicated Status',
            'slug' => 'ammunition-limits-dedicated-status',
            'excerpt' => '200 rounds per firearm and 2400 primers unless you have dedicated status.',
            'content' => $this->contentAmmunitionLimits(),
            'sort_order' => 1,
        ], $dedicatedType);

        $this->ensureArticle($cat2, [
            'title' => 'License Types and Definitions',
            'slug' => 'license-types-definitions',
            'excerpt' => 'Self-defense, occasional, dedicated, collection, temporary authorization, and more.',
            'content' => $this->contentLicenseTypes(),
            'sort_order' => 2,
        ], $dedicatedType);

        $this->ensureArticle($cat2, [
            'title' => 'Period of Validity of Licenses',
            'slug' => 'period-validity-licenses',
            'excerpt' => 'How long self-defense, restricted, and occasional licenses last.',
            'content' => $this->contentPeriodValidity(),
            'sort_order' => 3,
        ], $dedicatedType);

        $cat3 = $this->ensureCategory(
            'Firearm Components and Safeties',
            'firearm-components-safeties',
            $dedicatedType,
            'Barrel, action, stock, trigger, safety types, and shotgun/handgun basics.',
            80
        );

        $this->ensureArticle($cat3, [
            'title' => 'Four Types of Safeties',
            'slug' => 'four-types-safeties',
            'excerpt' => 'Cross-bolt, pivot, slide/tang, and half-cock/hammer safeties.',
            'content' => $this->contentFourTypesSafeties(),
            'sort_order' => 1,
        ], $dedicatedType);

        $this->ensureArticle($cat3, [
            'title' => 'Firearm Components: Bore, Muzzle, Breech, Trigger, Hammer, Grip',
            'slug' => 'firearm-components-bore-muzzle',
            'excerpt' => 'Definitions of key components for rifles and handguns.',
            'content' => $this->contentFirearmComponents(),
            'sort_order' => 2,
        ], $dedicatedType);

        $this->ensureArticle($cat3, [
            'title' => 'Shotgun Parts, Actions, and Safeties',
            'slug' => 'shotgun-parts-actions-safeties',
            'excerpt' => 'Major parts of a shotgun, action types, and common safeties.',
            'content' => $this->contentShotgunParts(),
            'sort_order' => 3,
        ], $dedicatedType);

        $this->ensureArticle($cat3, [
            'title' => 'Handgun Action Types: Single and Double Action',
            'slug' => 'handgun-action-types',
            'excerpt' => 'Single action vs double action in sport shooting handguns.',
            'content' => $this->contentHandgunActions(),
            'sort_order' => 4,
        ], $dedicatedType);

        $cat4 = $this->ensureCategory(
            'Ammunition and Ballistics',
            'ammunition-ballistics-sport',
            $dedicatedType,
            'Cartridge malfunctions, bullet parts, trajectory, and prohibited use on range.',
            90
        );

        $this->ensureArticle($cat4, [
            'title' => 'Cartridge Malfunctions: Misfire, Hangfire, Squib Load',
            'slug' => 'cartridge-malfunctions',
            'excerpt' => 'What they are and how to respond safely.',
            'content' => $this->contentCartridgeMalfunctions(),
            'sort_order' => 1,
        ], $dedicatedType);

        $this->ensureArticle($cat4, [
            'title' => 'Basic Parts of a Bullet',
            'slug' => 'basic-parts-bullet',
            'excerpt' => 'Base, shank, ogive, and meplat.',
            'content' => $this->contentBasicPartsBullet(),
            'sort_order' => 2,
        ], $dedicatedType);

        $this->ensureArticle($cat4, [
            'title' => 'Ballistics Terms: Trajectory, Projectile, Gravity, Air Resistance, Twist',
            'slug' => 'ballistics-terms',
            'excerpt' => 'Definitions of key ballistics terms.',
            'content' => $this->contentBallisticsTerms(),
            'sort_order' => 3,
        ], $dedicatedType);

        $this->ensureArticle($cat4, [
            'title' => 'Prohibited Firearms and Ammunition on the Range',
            'slug' => 'prohibited-range',
            'excerpt' => 'Tracer, full auto, and explosive devices may not be used.',
            'content' => $this->contentProhibitedRange(),
            'sort_order' => 4,
        ], $dedicatedType);
    }

    protected function seedDedicatedBothContent(): void
    {
        $cat = $this->ensureCategory(
            'Combined: Hunter & Sport Shooter',
            'combined-hunter-sport-shooter',
            'both',
            'Additional topics for members seeking both Dedicated Hunter and Dedicated Sport Shooter status.',
            100
        );

        $this->ensureArticle($cat, [
            'title' => 'Hunter and Sport Shooter Definitions',
            'slug' => 'hunter-sport-shooter-definitions',
            'excerpt' => 'Hunting operator, professional hunter, trophy, dedicated sports person, dedicated hunter, occasional hunter, bona-fide hunter.',
            'content' => $this->contentHunterSportDefinitions(),
            'sort_order' => 1,
        ], 'both');

        $this->ensureArticle($cat, [
            'title' => 'Shooting Positions and Incident Types',
            'slug' => 'shooting-positions-incident-types',
            'excerpt' => 'Four standard bolt-action positions and four main types of shooting incidents.',
            'content' => $this->contentShootingPositionsIncidents(),
            'sort_order' => 2,
        ], 'both');
    }

    // --------------- Shared content bodies ---------------
    protected function contentFundamentalRules(): string
    {
        return <<<'HTML'
<p>NRAPA requires all members to know and apply these six fundamental rules for safe gun handling. Each rule exists to prevent negligent injury or death and must be treated as absolute.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">The Six Fundamental Rules</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Know your target and what is beyond</h3>
<p>Before you pull the trigger, be absolutely certain of your target identification. Observe your entire area of fire — not just the target itself, but the area behind and around it. A bullet can travel far beyond the target and strike an unintended person, animal, or object. Never fire in conditions where people could be present in your line of fire, and never fire at movement, colour, sound, or shape alone. Positive identification of the target is a non-negotiable requirement before any shot.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Know how to use the gun safely</h3>
<p>Before handling any firearm, learn how it operates. Know the basic parts, understand how to open and close the action, and know how to safely remove ammunition. Read the owner's manual and, if necessary, seek instruction from a qualified person. Remember that a mechanical safety device is never foolproof — it is a mechanical device that can fail. The safety is a supplement to safe gun handling, not a substitute for it.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Be sure the gun is safe to operate</h3>
<p>Regular maintenance and cleaning are essential to keeping a firearm in safe working order. Inspect the firearm before use and ensure it is stored properly when not in use. If there is any doubt about the condition or safety of a firearm — whether through age, damage, or unusual operation — have a qualified gunsmith inspect it before firing. A firearm that is not safe to operate should never be loaded or used.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">4. Use only the correct ammunition</h3>
<p>Every firearm is designed for a specific calibre or gauge of ammunition, which is stamped on the barrel. Always check the markings on the barrel and compare them with the markings on the ammunition box and on each cartridge before loading. Using incorrect ammunition can destroy a firearm and cause serious injury or death. Never shoot a firearm unless you are certain you have the correct ammunition for it.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">5. Wear eye and ear protection as appropriate</h3>
<p>Firearms are loud — exposure to the noise of gunfire can cause permanent, irreversible hearing damage. Firearms also emit debris, hot gas, and occasionally particles from the casing or action. Both shooters and spectators should wear adequate hearing protection (earplugs or earmuffs) and eye protection (shooting glasses or safety glasses) whenever firearms are being discharged. This applies at ranges, in the field, and during any practice or competition.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">6. Never use alcohol or drugs before or while shooting</h3>
<p>Alcohol, over-the-counter medication, prescription drugs, and any other substances that impair judgment, coordination, or reaction time must never be used before or during shooting. Even small amounts of alcohol or sedating medication can critically reduce a shooter's ability to handle a firearm safely. Firearms should also be stored securely and away from persons who are under the influence or otherwise not authorised to access them.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">NRAPA Code of Conduct — Three Cardinal Rules</h2>
<p>In addition to the six fundamental rules above, the NRAPA Code of Conduct imposes three cardinal rules that apply at all times when handling any firearm:</p>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li><strong>ALWAYS keep the gun pointed in a safe direction.</strong> This is the most fundamental safety rule. A "safe direction" means that even if the firearm were to discharge, it would not cause injury or damage.</li>
<li><strong>ALWAYS keep your finger off the trigger until you are ready to shoot.</strong> Rest your finger alongside the frame or action, outside the trigger guard, until you have made the deliberate decision to fire.</li>
<li><strong>ALWAYS keep the firearm unloaded until you are ready to use it.</strong> Firearms should only be loaded when you are in the field or on the range and ready to shoot. When not in active use, the action should be open and the firearm unloaded.</li>
</ol>
<p class="mt-4">These three rules, combined with the six fundamental rules, form the complete foundation of safe firearm handling as taught by NRAPA.</p>
HTML;
    }

    protected function contentDisciplinaryAction(): string
    {
        return <<<'HTML'
<h2 class="mt-4 mb-3 text-xl font-bold">Grounds for Disciplinary Action</h2>
<p>Disciplinary action shall exist for:</p>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li><strong>Contraventions of all laws</strong> pertaining to conservation, hunting, firearms and ammunition.</li>
<li><strong>Breaches of the NRAPA Code of Ethics.</strong></li>
<li><strong>Conduct which brings or is likely to bring</strong> the Association, hunting and the private possession of firearms and ammunition into disrepute.</li>
</ol>

<h2 class="mt-8 mb-3 text-xl font-bold">Categories of Transgressions</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">Category 1 — Immediate Termination of Membership</h3>
<p>The following transgressions result in immediate termination of NRAPA membership:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Deliberately or accidentally committing a <strong>gross safety violation</strong> that endangers human life.</li>
<li>A <strong>court conviction involving violence</strong> or a contravention of the Firearms Control Act (FCA).</li>
<li><strong>Deliberate disregard of provincial hunting regulations</strong>, including hunting without valid permits or outside of prescribed seasons.</li>
<li><strong>Repetition of a Category 2 transgression</strong> within 18 months of a prior Category 2 offence.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Category 2 — Compulsory Suspension with Written Warning</h3>
<p>The following transgressions result in compulsory suspension and a formal written warning:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Transgression of the <strong>Safety Code</strong> at a range, in the field, or during any NRAPA-sanctioned activity.</li>
<li>A <strong>gross violation of the Code of Conduct</strong> that does not rise to the level of a Category 1 offence.</li>
<li><strong>Contravening provincial conservation regulations</strong> (e.g. exceeding bag limits, hunting protected species without a permit).</li>
<li><strong>Failing to report a Category 1 transgression</strong> committed by another member when the member had knowledge of the offence.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Category 3 — Discretionary Action</h3>
<p>The following transgressions are subject to discretionary disciplinary action by the NRAPA disciplinary committee:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Gross disregard of the codes</strong> of ethics or conduct that does not fit squarely into Category 1 or 2.</li>
<li><strong>Three or more complaints</strong> received from different landowners, fellow shooters, or members of the public regarding the member's behaviour.</li>
<li><strong>Actions against the spirit of the codes</strong> that, while not explicitly listed, undermine the reputation or objectives of the Association.</li>
</ul>

<h2 class="mt-8 mb-3 text-xl font-bold">Misconduct Complaint Process</h2>
<p>Any person — whether an NRAPA member or not — may file a misconduct complaint against an NRAPA member. The process is as follows:</p>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li>The complainant obtains a <strong>complaints form</strong> from the NRAPA office (available on the NRAPA website or by request).</li>
<li>The completed form is submitted to the NRAPA office with any supporting evidence or documentation.</li>
<li>The accused member receives <strong>written notification</strong> of the complaint and the specific allegations, and is given an opportunity to respond.</li>
<li>The NRAPA disciplinary committee reviews the complaint, the response, and any evidence before determining the appropriate category and sanction.</li>
</ol>

<h2 class="mt-8 mb-3 text-xl font-bold">Sanctions</h2>
<p>The following sanctions may be imposed, depending on the severity of the transgression:</p>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Sanction</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Description</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Probation</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">A written reprimand is issued and the member is placed on probation for a designated period. Any further transgression during this period may result in a more severe sanction.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Suspension</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">The member's NRAPA membership and all associated privileges are suspended for a definite period. During suspension the member may not participate in NRAPA activities or use NRAPA accreditation.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Expulsion</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Permanent removal from the Association. An expelled member may only re-apply for membership after a waiting period of at least <strong>5 years</strong> from the date of expulsion.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Interim Suspension</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Where an allegation is serious and an immediate risk may exist, the member may be placed on interim suspension pending the outcome of the disciplinary process.</td>
</tr>
</tbody>
</table>
</div>
<p class="mt-4">Members are expected to comply with the law and the NRAPA Code of Ethics at all times. Ignorance of the rules is not a defence.</p>
HTML;
    }

    protected function contentThreeMainParts(): string
    {
        return <<<'HTML'
<p>Every firearm, regardless of type, consists of three main parts: the <strong>stock</strong>, the <strong>action</strong>, and the <strong>barrel</strong>. Understanding these components is fundamental to safe and effective firearm use.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">1. The Stock</h2>
<p>The stock serves as the platform for supporting the action and barrel. It is the part of the firearm you hold, and it allows the shooter to control, aim, and fire the weapon. Stocks are made from <strong>wood</strong> (traditional) or <strong>synthetic material</strong> (modern, weather-resistant).</p>
<p class="mt-3">Key parts of the stock include:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Butt plate or recoil pad</strong> — The rear end of the stock that rests against the shooter's shoulder. A recoil pad absorbs some of the rearward force when the firearm is discharged.</li>
<li><strong>Cheek piece</strong> — A raised section on the side of the stock where the shooter rests their cheek to achieve a consistent sight picture.</li>
<li><strong>Grip</strong> — The area of the stock where the shooting hand holds the firearm and accesses the trigger. On handguns, the grip is a separate component.</li>
<li><strong>Forend (forearm)</strong> — The forward portion of the stock, beneath the barrel, used by the support hand to steady and guide the firearm.</li>
</ul>
<p class="mt-3">The stock helps the shooter control the firearm, absorb recoil, and maintain a stable aiming position.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">2. The Action</h2>
<p>The action is the heart of the firearm. It is the mechanism that <strong>loads, fires, and unloads ammunition</strong>. The action contains the firing mechanism (trigger, hammer or striker, firing pin) and usually houses the safety device.</p>
<p class="mt-3">There are five main types of firearm actions:</p>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Action Type</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Brief Description</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Lever action</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Operated by a lever behind the trigger guard; cycling the lever ejects the spent case and chambers a new round.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Break / Hinge action</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">The barrel hinges open at the breech for loading and unloading. Simple, reliable, and easy to visually confirm if loaded.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Bolt action</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Operated by lifting and pulling back a bolt handle to eject the spent case, then pushing forward to chamber a new round.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Pump action</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Operated by sliding the forend back and forth to cycle the action. Common in shotguns.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Semi-automatic</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Uses recoil or gas energy from firing to automatically cycle the action; fires one round per trigger pull.</td>
</tr>
</tbody>
</table>
</div>

<h2 class="mt-8 mb-3 text-xl font-bold">3. The Barrel</h2>
<p>The barrel is the tube through which a controlled explosion propels a projectile at high velocity towards the target. Barrel design varies significantly between firearm types:</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Rifle Barrel</h3>
<p>A rifle barrel is long with thick walls. The inside of the bore has <strong>spiralling grooves</strong> (known as <strong>rifling</strong>) cut into it. This grooved pattern spins the bullet as it travels through the barrel, imparting gyroscopic stability that dramatically improves accuracy over long distances. Rifles are designed for precision shooting at extended ranges.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Shotgun Barrel</h3>
<p>A shotgun barrel is long with fairly thin steel walls. The bore is <strong>very smooth inside</strong>, allowing the shot pellets and wad to glide through without friction. Shotgun barrels may have a choke (constriction) at the muzzle to control the spread of the shot pattern. Shotguns are designed for shooting at moving targets (birds, clay targets) or at close range.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Handgun Barrel</h3>
<p>A handgun barrel is <strong>much shorter</strong> than a rifle or shotgun barrel. It is designed so that the firearm can be shot with one or two hands. Despite the shorter length, handgun barrels are rifled (like rifle barrels) to spin the bullet for accuracy. The shorter barrel results in lower muzzle velocity and a shorter effective range compared to rifles.</p>

<p class="mt-6">Accessories such as scopes, slings, or bipods are not part of the three main components, though they may be attached to one of them.</p>
HTML;
    }

    protected function contentTypesOfActions(): string
    {
        return <<<'HTML'
<p>The action is the mechanism that loads, fires, and ejects ammunition. There are five main types of firearm actions that every shooter should understand. Each type has distinct characteristics that affect reliability, speed of operation, and suitability for different purposes.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">The Five Main Action Types</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Lever Action</h3>
<p>The lever action is operated by a <strong>lever located behind the trigger guard</strong>. To cycle the action, the shooter pulls the lever downward and rearward, which ejects the spent cartridge case and cocks the hammer. Pushing the lever back to its closed position chambers a fresh round from the tubular or box magazine. Lever actions were historically very popular in North America and remain in use for hunting and sport shooting. They allow reasonably fast follow-up shots while keeping the shooter's cheek on the stock.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Break / Hinge Action</h3>
<p>In a break or hinge action, the <strong>barrel hinges open at the breech</strong> for loading and unloading. This is one of the simplest and most reliable action designs. Break actions may be single-shot (one barrel) or double-barrel (side-by-side or over-under). Because the breech is fully exposed when open, it is very easy to visually confirm whether the firearm is loaded or unloaded. Break actions are common in shotguns and some single-shot rifles. Their simplicity makes them an excellent choice for beginners and for situations where safety verification must be immediate and obvious.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Bolt Action</h3>
<p>The bolt action is operated by <strong>lifting and pulling back a bolt handle</strong>, which unlocks the action, extracts and ejects the spent cartridge case, and cocks the firing mechanism. Pushing the bolt forward strips a fresh round from the magazine and chambers it; turning the handle down locks the bolt in place. Bolt actions are widely regarded as the <strong>most accurate</strong> action type for hunting rifles, because the bolt locks up solidly and consistently. They are the most common action type for precision hunting and long-range target shooting.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">4. Pump Action</h3>
<p>The pump action (also called slide action) is operated by <strong>sliding the forend rearward and then forward</strong>. Pulling the forend back unlocks the action, extracts and ejects the spent shell, and cocks the hammer. Pushing the forend forward chambers a new round and locks the action. Pump actions are extremely <strong>reliable and fast</strong>, and are most commonly found in shotguns. They are favoured for their mechanical simplicity and the positive control the shooter has over the cycling process.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">5. Semi-Automatic</h3>
<p>A semi-automatic action uses <strong>recoil energy or expanding-gas energy</strong> from the firing cartridge to automatically cycle the action. When a round is fired, the action ejects the spent shell, chambers a fresh round from the magazine, and cocks the mainspring — all without manual intervention from the shooter. The firearm is then in position for another shot. A semi-automatic fires <strong>one round per trigger pull</strong>; it does not fire continuously like a fully automatic firearm.</p>
<p class="mt-3">The formal description: <em>"A type of firearm which, utilising some of the recoil or some of the expanding-gas energy from the firing cartridge, cycles the action to eject the spent shell, to chamber a fresh one from a magazine and to cock the mainspring, placing the gun in position for another shot."</em></p>

<h2 class="mt-8 mb-3 text-xl font-bold">Summary Comparison</h2>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Action</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Operation</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Common Use</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Lever</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Lever behind trigger guard</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Rifles (hunting, sport)</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Break / Hinge</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Barrel pivots open at breech</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Shotguns, single-shot rifles</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Bolt</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Bolt handle lifted and cycled</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Hunting rifles, precision shooting</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Pump</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Forend slides back and forward</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Shotguns</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Semi-automatic</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Gas or recoil energy auto-cycles</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Rifles, shotguns, handguns</td>
</tr>
</tbody>
</table>
</div>

<p class="mt-4">Other mechanisms (e.g. cannon, sling shot, spear) are not standard firearm action types and are not covered in the NRAPA syllabus.</p>
HTML;
    }

    protected function contentCleaningSteps(): string
    {
        return <<<'HTML'
<h2 class="mt-4 mb-3 text-xl font-bold">Why Cleaning Is Important</h2>
<p>Regular cleaning is essential for the <strong>proper function</strong>, <strong>safety</strong>, and <strong>longevity</strong> of any firearm. Fouling from powder residue, copper deposits, moisture, and skin oils accumulate with use and can cause malfunctions, corrosion, and reduced accuracy. A well-maintained firearm is safer to operate, more reliable in the field, and retains its value over time. Neglecting cleaning can lead to dangerous conditions such as bore obstructions or action failures.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">When to Clean</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>After every use</strong> — Clean your firearm as soon as practical after shooting. Powder residue and moisture begin corroding metal surfaces immediately.</li>
<li><strong>Before shooting after prolonged storage</strong> — A firearm that has been stored for an extended period may have accumulated moisture, dust, dirt, or solidified grease and oil inside the action and bore. These contaminants can prevent proper operation and must be removed before the firearm is loaded and fired.</li>
<li><strong>Periodically during storage</strong> — Even if not in use, inspect and lightly oil firearms in storage to prevent rust, especially in humid climates.</li>
</ul>

<h2 class="mt-8 mb-3 text-xl font-bold">Safety First</h2>
<p>Before cleaning any firearm, you must make <strong>ABSOLUTELY sure</strong> that it is completely unloaded. Verify visually and physically that the chamber and magazine are empty. The action should remain <strong>open</strong> throughout the entire cleaning process. There must be <strong>NO ammunition</strong> anywhere in the cleaning area — remove all cartridges, loaded magazines, and loose rounds before you begin. Accidents during cleaning are entirely preventable by following this rule without exception.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">The Six-Step Cleaning Process</h2>
<ol class="list-decimal pl-6 my-4 space-y-4">
<li>
<strong>Safely unload the firearm.</strong>
<p class="mt-1">Remove the magazine (if applicable), open the action, and visually and physically confirm that the chamber is empty. Keep the action open.</p>
</li>
<li>
<strong>Remove all ammunition from the cleaning area.</strong>
<p class="mt-1">Take all cartridges, loaded magazines, and loose rounds out of the room or area where you will be cleaning. This eliminates any possibility of accidentally loading a round during the process.</p>
</li>
<li>
<strong>Disassemble the firearm for more thorough cleaning.</strong>
<p class="mt-1">Follow the manufacturer's instructions to field-strip or partially disassemble the firearm. This provides access to internal surfaces, the bore, and the action components that cannot be reached when the firearm is fully assembled. Do not disassemble beyond what the manufacturer recommends unless you are trained to do so.</p>
</li>
<li>
<strong>Clean the bore using cleaning rods, brushes, patches, and solvent.</strong>
<p class="mt-1">Attach a bore brush to the cleaning rod, apply solvent, and push it through the barrel from breech to muzzle (where possible) several times to loosen fouling. Follow with clean patches until they come out without discolouration. A clean bore is essential for accuracy and safety.</p>
</li>
<li>
<strong>Clean all metal parts including the action.</strong>
<p class="mt-1">Use a cloth and gun cleaning solvents to remove dirt, powder residue, skin oils, and moisture from all external and accessible internal metal surfaces. Pay particular attention to the bolt face, feed ramp, extractor, and any areas where residue accumulates. A toothbrush or nylon brush can help reach tight areas.</p>
</li>
<li>
<strong>Apply a coating of gun oil.</strong>
<p class="mt-1">Once all parts are clean and dry, apply a thin, even coating of gun oil to all metal surfaces to protect the firearm from rust and corrosion. Avoid over-oiling — excess oil can attract dust and gum up the action. Lightly oil moving parts and the bore, then wipe away any excess with a clean cloth.</p>
</li>
</ol>

<h2 class="mt-8 mb-3 text-xl font-bold">Storage After Cleaning</h2>
<p>After cleaning, store the firearm in a <strong>clean, dry, and safe place</strong> — ideally in a prescribed safe or strong room as required by the Firearms Control Act. Proper storage is an integral part of firearm upkeep: a clean firearm stored in poor conditions will quickly deteriorate. Ensure the storage environment is free from excessive humidity, and consider using silicone-treated gun socks or dehumidifiers in your safe.</p>
<p class="mt-4">Never re-load the firearm until you are ready to use it. Never pull the trigger during the cleaning process. Treat cleaning with the same discipline and respect you apply when handling a loaded firearm.</p>
HTML;
    }

    // --------------- Hunter content bodies ---------------
    protected function contentSingleShotFairChase(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">Wildlife as a Renewable Resource</h2>
<p>Wildlife is a renewable natural resource. As hunters we must respect all wildlife harvested and conduct ourselves ethically at all times. Hunting is a privilege, not a right, and it depends on public acceptance and responsible behaviour by every participant.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Hunting Laws vs Hunter Ethics</h2>
<p>While <strong>hunting laws</strong> preserve wildlife populations, <strong>ethics</strong> preserve the hunter's <strong>opportunity to hunt</strong>. Laws set the minimum standard of behaviour; ethics go further, covering fairness, respect, and responsibility in areas not explicitly governed by legislation. Hunter ethics is a personal code of behaviour that each hunter develops over a lifetime of experience and reflection.</p>
<p class="mt-4">The two key words at the heart of ethical hunting are <strong>"respect"</strong> and <strong>"responsibility"</strong>. These guide every decision a hunter makes in the field — from the choice of quarry and method, to conduct towards landowners, fellow hunters, and the general public.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Single Shot Humane Kill</h2>
<p>NRAPA promotes at all times the ethic of <strong>"single shot humane kill"</strong> to ensure the humane harvesting of game. This means:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>One well-placed shot should be sufficient to harvest the animal.</li>
<li>Hunters must be proficient with their firearm and use the appropriate calibre and bullet for the species being hunted.</li>
<li>Shot placement must target the vital organs to ensure a quick, clean kill with minimal suffering.</li>
<li>It is the hunter's responsibility to determine whether a shot is ethical or unethical before pulling the trigger — considering distance, angle, wind, the animal's posture, and potential obstructions.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Fair Chase</h2>
<p><strong>Fair chase</strong> is defined as the <strong>ethical, sportsmanlike, and lawful pursuit of free-ranging wild game animals in a manner which does not give the hunter an improper or unfair advantage</strong> over the animal. It balances the skills and equipment of the hunter with the animal's natural ability to detect danger and escape.</p>
<p class="mt-4">Fair chase is central to ethical hunting. It ensures that hunting remains a genuine test of the hunter's fieldcraft, patience, and marksmanship, not a foregone conclusion achieved through technology or unsporting methods.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Prohibited Hunting Methods</h2>
<p>The following methods are <strong>prohibited for the hunting of listed threatened or protected species</strong> (except where specified for damage-causing animals or under special permit):</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Bow Hunting</h3>
<p>No bow hunting of listed large predators (cheetah, leopard, spotted hyena, brown hyena, wild dog), white or black rhino, crocodile, or elephant.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Firearms</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>No automatic weapons.</li>
<li>No .22 rimfire calibre or smaller.</li>
<li>No shotguns, except for the hunting of birds.</li>
<li>No airguns.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Lights</h3>
<p>No use of flood lights or spot lights, except for controlling damage-causing animals, culling operations, and the hunting of leopards and hyenas.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Vehicles</h3>
<p>No hunting from motorised vehicles, except for tracking game over long distances, controlling damage-causing animals, culling operations, and by disabled or elderly persons (over 65 years of age).</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Other Prohibited Methods</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>No hunting of tranquilised or trapped animals.</li>
<li>No darting of animals, except by a veterinarian or a person authorised by a vet for veterinary, scientific, management, or transport purposes.</li>
<li>No use of dogs, except for tracking wounded animals and for flushing, pointing, or retrieving birds.</li>
<li>No use of bait, except dead bait for leopards and hyenas, and bait for aquatic species.</li>
<li>No luring of animals by means of sounds or smells.</li>
<li>No use of poison, traps, snares, or spears, except for controlling damage-causing animals.</li>
<li>No hunting from aircraft, except for tracking game, culling operations, and controlling damage-causing animals.</li>
</ul>
HTML;
    }

    protected function contentEthicalHunterCommitment(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">NRAPA Code of Conduct — Hunting</h2>
<p>The NRAPA Code of Conduct requires that all members who hunt must:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Respect all wildlife and the natural environment.</li>
<li>Obey all laws, regulations, and codes of conduct pertaining to hunting, firearms, and conservation.</li>
<li>Honour the principle of <strong>"Single Shot Humane Kill"</strong> — ensuring the quick, clean, and ethical harvesting of game.</li>
<li>Abide by the NRAPA code for right and wrong in all hunting activities.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Fair Chase and Ethical Hunting</h2>
<p>Ethical hunting is fundamentally about <strong>fair chase</strong> — balancing the skills and equipment of the hunter against the animal's natural ability to detect danger and escape. A hunt conducted under fair chase principles is one where the outcome is never guaranteed, and the animal has a genuine opportunity to evade the hunter.</p>
<p class="mt-4">The role of hunter ethics in society is critical: ethical behaviour by hunters ensures that <strong>public perception of hunting remains positive and approving</strong>. Irresponsible or unethical conduct by even a single hunter can damage the reputation of all hunters and threaten the future of hunting as a lawful activity. The best way to uphold the hunting tradition is to <strong>hunt responsibly</strong> at all times.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">The Six Commitments of an Ethical Hunter</h2>
<p>As an ethical hunter, I commit to the following:</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Know and obey the laws and regulations for hunting</h3>
<p>Every hunter has a duty to be informed about and comply with all national, provincial, and local laws governing hunting seasons, bag limits, species restrictions, firearm use, and permit requirements. Ignorance of the law is no defence. Staying current with regulations is part of being a responsible hunter.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Show respect for myself and other people</h3>
<p>This includes respect for landowners who grant access to their property, fellow hunters in the field, and non-hunters in the community. Hunters must seek proper permission, behave courteously, leave property as they found it, and represent the hunting community with dignity. Non-hunters and anti-hunters form opinions based on the behaviour they observe.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Show respect for all wildlife and the environment</h3>
<p>Ethical hunters view wildlife with reverence and gratitude. They do not waste game, they pursue only species they intend to use, and they take care not to damage habitat. Respect for the environment means leaving the veld cleaner than you found it, avoiding unnecessary disturbance, and supporting conservation efforts that sustain wildlife populations.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">4. Take responsibility for my actions</h3>
<p>A responsible hunter accepts full accountability for every shot fired, every animal pursued, and every decision made in the field. This includes acknowledging mistakes, making every effort to recover wounded game, and never blaming equipment or circumstances for poor decisions.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">5. Report vandalism, hunting violations or poaching to local authorities</h3>
<p>Ethical hunters do not tolerate illegal or irresponsible behaviour by others. Witnessing poaching, illegal hunting methods, or destruction of property and remaining silent makes one complicit. Reporting violations to local conservation officers, the police, or the relevant authority is a duty every ethical hunter must uphold.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">6. Actively support legal, safe and ethical hunting</h3>
<p>Supporting hunting means participating in conservation programmes, contributing to habitat management, educating new hunters, and promoting the values of safe and ethical hunting to the broader public. The future of hunting depends on active, positive engagement by the hunting community.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Purpose of Hunter Education</h2>
<p>Hunter education aims to produce <strong>safe, responsible, knowledgeable, and involved hunters</strong> who will preserve the hunting tradition for future generations through exemplary conduct in the field and in society.</p>
HTML;
    }

    protected function contentSustainableUse(): string
    {
        return <<<'HTML'
<p>NRAPA promotes the <strong>sustainable utilisation of wildlife as a conservation tool</strong> and promotes <strong>ethical, responsible hunting</strong>.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Wildlife as a Renewable Resource</h2>
<p>Wildlife is a <strong>renewable natural resource</strong> — it replenishes itself naturally as long as the population is not completely depleted and habitats remain intact. This characteristic is what makes sustainable utilisation possible: when harvesting is kept within the limits of natural recovery, wildlife populations can be maintained indefinitely.</p>
<p class="mt-4">Hunters must <strong>respect all wildlife harvested</strong> and conduct themselves ethically at all times. Ethical hunting goes beyond simply following the law. While <strong>hunting laws preserve wildlife</strong>, it is <strong>ethics that preserve the hunter's opportunity to hunt</strong>.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Public Opinion and Ethical Behaviour</h2>
<p>Public perception of hunting is directly influenced by the behaviour of individual hunters. Ethical behaviour in the field ensures that hunters remain <strong>welcome in communities</strong> and that <strong>hunting areas stay open</strong>. Irresponsible or unethical conduct — even by a minority — damages the reputation of all hunters and can lead to loss of access, stricter regulations, or outright bans.</p>
<p class="mt-4">Every hunter is an ambassador for the hunting community. How you conduct yourself in the field, how you treat landowners and their property, and how you handle harvested animals all shape public opinion.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">The Hunter's Conservation Role</h2>
<p>Hunters have a <strong>duty to get involved in conservation practices</strong> to ensure that wildlife and wild places are preserved for future generations. This includes supporting habitat management, anti-poaching efforts, wildlife monitoring, and the funding of conservation initiatives through hunting fees and levies.</p>
<p class="mt-4">Hunting, when properly managed, is one of the most effective conservation tools available. Revenue from hunting funds the management and protection of vast areas of natural habitat that would otherwise be converted to agriculture or development.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Definitions</h2>
<p><strong>Sustainable use</strong> is defined as using wildlife resources at a rate that <strong>does not exceed the capacity of the population to recover naturally</strong>. It requires that harvest levels are set based on scientific data about population size, reproduction rates, and habitat carrying capacity.</p>
<p class="mt-4"><strong>Responsible hunting</strong> means following all legal requirements, adhering to ethical standards, and actively contributing to conservation. A responsible hunter:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Obeys all laws, regulations, and permit conditions</li>
<li>Respects bag limits and quotas</li>
<li>Uses adequate caliber and equipment for a quick, humane kill</li>
<li>Respects the landowner, the environment, and other hunters</li>
<li>Reports violations and supports anti-poaching efforts</li>
<li>Contributes to conservation through participation and funding</li>
<li>Educates the next generation about sustainable practices</li>
</ul>
HTML;
    }

    protected function contentDedicatedHunterStatus(): string
    {
        return <<<'HTML'
<p>South Africa's Firearms Control Act (FCA) requires applicants for a licence in the dedicated category to be members of an accredited hunting or sport-shooting organisation and to have completed a training course and assessment. NRAPA is accredited by the SAPS to allocate Dedicated Hunter and Sport Shooter status.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">FCA Obligations of the Accredited Association</h2>
<p>The FCA places specific obligations on accredited associations such as NRAPA:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>The association must <strong>regularly evaluate</strong> its dedicated members' hunting and sport-shooting activities to confirm ongoing participation.</li>
<li>The association must <strong>keep records</strong> of all members' hunting and sport-shooting participation throughout the year.</li>
<li>The association must <strong>annually, before year-end</strong>, submit a written report to the Registrar of Firearms containing all dedicated members' details.</li>
<li>The association must <strong>furnish particulars</strong> of any members whose dedicated membership has lapsed, together with the reasons for the lapse.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">The Dedicated Licence (Section 16)</h2>
<p>A dedicated licence under Section 16 of the FCA allows the holder to possess a handgun, rifle, or shotgun (not fully automatic). Semi-automatic shotguns are permitted but are limited to a <strong>maximum capacity of 5 shots</strong>. The licence is valid for <strong>10 years</strong>.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Requirements to Obtain Dedicated Status</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Pass NRAPA's prescribed course successfully, achieving at least <strong>70% in the written/theoretical test</strong>.</li>
<li>Pass NRAPA's practical <strong>shooting test</strong> for dedicated hunter.</li>
<li><strong>Exception:</strong> Members who already hold a dedicated hunter certificate from another accredited association, a CHASA certificate, or a Professional Hunter certificate do not need to complete the course to apply for dedicated status with NRAPA.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Ongoing Activity Requirements</h2>
<p>A Dedicated Hunter must take part in at least <strong>two dedicated activities on at least two separate occasions</strong> during the year. Qualifying activities include:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>A complete course of fire at a <strong>recognised range</strong> or in a <strong>sanctioned postal-style or club competition</strong>, with the scorecard signed by a Range Officer or responsible person.</li>
<li><strong>Written confirmation of a hunt</strong> from an outfitter, Professional Hunter, or fellow member.</li>
<li>Participation in a <strong>sport shooting event</strong> of any other SAPS-accredited association.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Annual Reporting</h2>
<p>All dedicated members must submit their <strong>Dedicated Activities Report</strong> to the NRAPA office before <strong>31 October</strong> annually. The report form is available on www.NRAPA.co.za.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Not-Active Report</h3>
<p>If a member is unable to participate in dedicated activities during the year, they must provide <strong>reasonable grounds</strong> in a Not-Active Report. Valid reasons may include illness, injury, travel, or other circumstances that genuinely prevented participation. The Not-Active Report must also be submitted before the annual deadline.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Consequences of Non-Compliance</h2>
<p><strong>Failure to submit any report (Activities Report or Not-Active Report) will INVALIDATE the member's dedicated status.</strong> This is not discretionary — the FCA requires it. Loss of dedicated status may result in the revocation of Section 16 licences and associated privileges. Members must treat the reporting deadline with the same seriousness as a licence renewal.</p>
HTML;
    }

    protected function contentSpeciesCategories(): string
    {
        return <<<'HTML'
<p>In South Africa, species are classified into these main categories:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Critically Endangered Species</strong> — e.g. Blue Swallow. At very high risk of extinction.</li>
<li><strong>Endangered Species</strong> — e.g. Mountain Zebra. At high risk of extinction.</li>
<li><strong>Vulnerable Species</strong> — e.g. Cheetah. At risk of becoming endangered.</li>
<li><strong>Protected Species</strong> — e.g. Elephant. Protected by law; hunting or trade is restricted.</li>
<li><strong>Conservation status of huntable species</strong> — e.g. Black Wildebeest. Species that may be legally hunted under regulation.</li>
</ul>
<p class="mt-4">It is essential to know which species fall into which category before hunting or trading.</p>

<h3 class="mt-6 mb-3 text-lg font-semibold">Conservation Status of Huntable Species</h3>
<p>The table below lists common huntable species and their conservation status as per the NRAPA study material:</p>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Species</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Conservation Status</th>
</tr>
</thead>
<tbody>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Black Rhino</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1 font-semibold text-red-700 dark:text-red-400">Critical Endangered Species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Bontebuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Vulnerable to extinction</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Blue Duiker</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Vulnerable to extinction</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Lion</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Vulnerable to extinction</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Mountain Zebra</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Vulnerable to extinction</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Soeni</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Vulnerable to extinction</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Oribi</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Lechwe</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Lower risk</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Puku</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Lower risk</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Sitatunga</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Lower risk</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Black Wildebeest</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Blesbuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Blue Wildebeest</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Burchell's Zebra</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Bushbuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Bushpig</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Buffalo</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Cape Hartebeest</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Cape Grysbok</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Damara dik-dik</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Eland</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Elephant</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Giraffe</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Grey Duiker</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Hippopotamus</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Impala</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Klipspringer</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Lichtenstein's Hartebeest</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Nyala</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Oryx</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Reedbuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Red Duiker</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Roan Antelope</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Sable</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Sharp's Grysbok</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Close to Endangered</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Southern Kudu</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Southern Mountain Reedbuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Springbuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Steenbuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Tsessebe</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1 font-semibold text-red-700 dark:text-red-400">Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Vaal Rehbuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Waterbuck</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Warthog</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">White Rhino</td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Least Endangered species</td></tr>
</tbody>
</table>
</div>
HTML;
    }

    protected function contentCITES(): string
    {
        return <<<'HTML'
<p><strong>CITES</strong> stands for the <strong>Convention on International Trade in Endangered Species of Wild Fauna and Flora</strong>.</p>
<p class="mt-4">It is an international agreement that regulates trade in endangered and protected species (and their parts) across borders. South Africa is a signatory. Hunters and traders must comply with CITES when moving trophies or specimens internationally.</p>
<p class="mt-4">A hunter wanting to hunt a CITES-listed species must apply for the necessary CITES permit at the relevant provincial nature conservation office.</p>

<h3 class="mt-6 mb-3 text-lg font-semibold">CITES Schedules for Endangered Species</h3>
<p>Species are listed under three schedules based on the level of protection required:</p>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Schedule 1</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Schedule 2</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Schedule 3</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Black Rhino</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">All Primates</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Tsessebe</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Cheetah</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Aardvark</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Cape Mountain Zebra</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Ant-eater</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Elephant</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Blue Duiker</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Leopard</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Bontebok</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Small spotted cat</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Hippopotamus</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Hartman's Zebra</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Lion</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Lynx/Caracal</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Nile Crocodile</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Otters</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Python</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Serval</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">White Rhino</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"></td>
</tr>
</tbody>
</table>
</div>

<h3 class="mt-6 mb-3 text-lg font-semibold">Schedule Descriptions</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Schedule 1</strong> — Species threatened with extinction. Trade is only permitted in exceptional circumstances.</li>
<li><strong>Schedule 2</strong> — Species not necessarily threatened with extinction but trade must be controlled to avoid utilisation incompatible with their survival.</li>
<li><strong>Schedule 3</strong> — Species protected in at least one country which has asked other CITES parties for assistance in controlling the trade.</li>
</ul>
HTML;
    }

    protected function contentKeyHuntingRegulations(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">Special Restrictions on Lion and Rhino Hunting</h2>
<p>Captive-bred listed large predators (cheetah, spotted hyena, brown hyena, wild dog, leopard) and white or black rhino are <strong>prohibited from being hunted</strong> if any of the following conditions apply:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>The animal has <strong>not been released</strong> from a captive or controlled environment and been <strong>self-sustainable for at least 24 months</strong>.</li>
<li>The animal is kept in a <strong>controlled environment</strong> (e.g. enclosed camp, pen, or fenced area that restricts natural movement).</li>
<li>The animal is under the <strong>influence of a tranquiliser</strong>.</li>
<li>The animal is <strong>adjacent to a holding facility</strong> (boma, pen, or similar structure).</li>
<li>The animal is hunted by means of a <strong>gin (leghold) trap</strong>.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Prohibited Hunting Methods</h2>
<p>The following methods are <strong>prohibited for the hunting of listed threatened or protected species</strong>, except where specifically allowed for damage-causing animals, culling operations, or under special permit:</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Bow Hunting</h3>
<p>No bow hunting of listed large predators (cheetah, leopard, spotted hyena, brown hyena, wild dog), white or black rhino, crocodile, or elephant.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Firearms Restrictions</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>No <strong>automatic weapons</strong>.</li>
<li>No <strong>.22 rimfire calibre or smaller</strong>.</li>
<li>No <strong>shotguns</strong>, except for the hunting of birds.</li>
<li>No <strong>airguns</strong>.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Use of Lights</h3>
<p>No use of <strong>flood lights or spot lights</strong>, except for:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Controlling damage-causing animals.</li>
<li>Culling operations.</li>
<li>Hunting of leopards and hyenas.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Hunting from Vehicles</h3>
<p>No hunting from <strong>motorised vehicles</strong>, except for:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Tracking game over long distances (i.e. using a vehicle for transport to the hunting area, not shooting from the vehicle).</li>
<li>Controlling damage-causing animals.</li>
<li>Culling operations.</li>
<li>Disabled or elderly persons (over 65 years of age).</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Tranquilised and Trapped Animals</h3>
<p>No hunting of animals that are <strong>tranquilised or trapped</strong>. No darting of animals except by a <strong>veterinarian</strong> or a person authorised by a vet for veterinary, scientific, management, or transport purposes.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Use of Dogs</h3>
<p>No use of dogs for hunting, except for:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Tracking wounded animals.</li>
<li>Flushing, pointing, or retrieving birds.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Baiting and Luring</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>No use of <strong>bait</strong>, except dead bait for leopards and hyenas, and bait for aquatic species.</li>
<li>No <strong>luring of animals by means of sounds or smells</strong>.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Poison, Traps, Snares and Spears</h3>
<p>No use of <strong>poison, traps, snares, or spears</strong>, except for controlling damage-causing animals under permit.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Hunting from Aircraft</h3>
<p>No hunting from <strong>aircraft</strong>, except for tracking game, culling operations, and controlling damage-causing animals.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Calibre and Firearm Requirements</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>No <strong>.22 rimfire</strong> calibre may be used for hunting game (other than birds or hares).</li>
<li>No <strong>shotgun</strong> may be used for hunting game animals (except birds).</li>
<li><strong>Semi-automatic or self-loading rifles</strong> may not be used to hunt ordinary or protected game. They may be used for wild animals that are not classified as game and for problem animals.</li>
</ul>
<p class="mt-4">The director of Nature Conservation is empowered to issue special permits for unusual circumstances, including the use of otherwise prohibited methods or firearms.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Permission to Hunt</h2>
<p>A hunter must <strong>always</strong> have <strong>written permission from the landowner</strong> before hunting on any property. This applies even for the hunting of "problem animals." The only exception is the landowner and his or her immediate family hunting on their own land.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Contents of Written Permission</h3>
<p>The written permission letter must contain the following details:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Full names and addresses</strong> of both the landowner and the hunter.</li>
<li><strong>Date</strong> on which the permission is granted.</li>
<li><strong>Farm description</strong> — name and number of the property.</li>
<li><strong>Game particulars</strong> — the number of animals, species, and gender (male/female) to be hunted.</li>
<li><strong>Date(s) of the hunt</strong> — when the hunting will take place.</li>
<li><strong>Signatures of both parties</strong> — the landowner and the hunter.</li>
</ul>
<p class="mt-4">The hunter must <strong>carry the written permission letter at all times</strong> while hunting. Failure to produce it on demand by a conservation officer or police official is an offence.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Hunting Permits and Licences</h2>
<p>Hunting permits are issued by <strong>provincial authorities</strong> (the relevant provincial nature conservation department). Requirements vary by province and may differ for residents and non-residents.</p>
<p class="mt-4"><strong>Non-South African hunters</strong> must be accompanied by a registered <strong>Professional Hunter (PH)</strong> and the hunt must be arranged through a licensed <strong>hunting outfitter</strong>. The outfitter is responsible for obtaining the necessary permits.</p>
HTML;
    }

    protected function contentRifleCarrying(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">Eight Rifle Carry Techniques</h2>
<p>Choosing the correct carry technique depends on terrain, vegetation, the position of other hunters, and how quickly you may need to bring the rifle to a shooting position. Every technique must keep the muzzle pointed in a safe direction at all times.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Sling Carry</h3>
<p>The rifle sling is placed over the shoulder with your hand on the sling to prevent it from slipping. This is a comfortable carry for <strong>long walks in open cover</strong> where quick shots are unlikely. The disadvantage is that the barrel can catch in brush and overhead branches, and the rifle is slow to bring into a shooting position.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Cradle Carry</h3>
<p>The gun rests across your forearm and the crook of your elbow, with your hand securing the rifle by grasping the stock or fore-end. This is a <strong>comfortable carry that reduces arm fatigue</strong> on longer hunts. The muzzle points to one side and the action is easily accessible.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Elbow or Side Carry</h3>
<p>The elbow or "side" carry is a very comfortable carry for <strong>break-action firearms</strong>. The pivot of the open action rests easily in the crook of your elbow and <strong>down over your forearm</strong>. In this manner the barrel naturally points down. Others can easily see that your action is <strong>safe and open</strong>.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">4. Shoulder Carry</h3>
<p>The rifle is balanced on the shoulder with your hand on the grip and your <strong>finger off the trigger</strong>. This is a good carry when walking <strong>beside or behind others</strong>, as the muzzle points upward and behind. It is <strong>not a good carry if others are behind you</strong>, as the muzzle would be directed toward them.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">5. Two-Handed Ready Carry</h3>
<p>The gun is held by <strong>both hands in front of the body</strong> with the barrel pointing upward at an angle. The trigger finger must <strong>always remain outside the trigger guard</strong>. This carry provides the <strong>best muzzle control</strong>, especially in thick brush, and is the <strong>quickest carry from which to aim and fire</strong>. It is the preferred carry when game may be encountered at close range.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">6. Safe Carry in a Group</h3>
<p>When hunting in a group, <strong>strict muzzle control is the overriding priority</strong>. Each hunter must be aware of the position of every other member of the group at all times. You may need to adjust your carry technique for the terrain, pace, and formation of the group. Communication about direction of movement and intended shooting lanes is essential.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">7. Walking Side by Side</h3>
<p>When three or more hunters walk abreast, the <strong>hunters on each side</strong> keep their muzzles pointed to the <strong>side or front</strong> (away from the centre). The <strong>hunter in the middle</strong> keeps the muzzle pointed <strong>back and up</strong>, or straight up/forward, ensuring that no muzzle sweeps across any other hunter at any time.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">8. Walking in Single File</h3>
<p>When hunters walk in a line:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>The <strong>hunter in front</strong> keeps the muzzle pointed to the <strong>side or upward</strong>.</li>
<li>The <strong>hunter in the middle</strong> uses a cradle carry with the muzzle pointed to the <strong>side</strong>.</li>
<li>The <strong>hunter at the rear</strong> keeps the muzzle pointed <strong>back or to the side</strong>.</li>
</ul>
<p>No hunter should ever have their muzzle pointing toward the person ahead of or behind them.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Four Fundamentals of Carrying a Firearm</h2>
<p>Regardless of which carry technique you use, these four fundamentals must be observed at all times:</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Keep the safety in the "on" position while carrying</h3>
<p>The mechanical safety is your secondary defence against accidental discharge. While no safety is foolproof, keeping it engaged while carrying adds an important layer of protection, especially when moving over uneven terrain where a stumble or fall could cause an unintended trigger pull.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Only change the safety to fire when you are ready to shoot</h3>
<p>The safety should only be moved to the "fire" position at the moment you have identified your target, confirmed what is beyond it, and are about to take the shot. Disengaging the safety prematurely — such as while still walking or scanning for game — is a dangerous habit.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Always keep your finger outside the trigger guard</h3>
<p>Your trigger finger must rest alongside the frame, action, or trigger guard — never on the trigger itself — until you have made the deliberate decision to fire. This is the single most effective habit for preventing negligent discharges.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">4. Keep the muzzle pointed in a safe direction and the barrel under control</h3>
<p>This is the most fundamental safety rule. A safe direction means that even if the firearm were to accidentally discharge, the bullet would not strike a person or cause unintended damage. The barrel must be under your physical control at all times — not swinging freely or pointing where you cannot see.</p>
HTML;
    }

    protected function contentTypesOfShotsFence(): string
    {
        return <<<'HTML'
<p>There are <strong>four recognised shots for humane kill placement</strong>: Full Frontal, Broadside, Quartering Away, and Quartering Forward. Shots into the rumen, from behind, or at the head/neck should be avoided unless specifically appropriate and the hunter is highly skilled.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Full Frontal Shot</h2>
<p>The full frontal shot presents a <strong>higher risk</strong> with a <strong>smaller target area</strong>. The projectile must pass through thick skin, chest muscle, and bone to reach the vital organs. A shot placed slightly left or right of centre may pass between the shoulder and the lung cavity, causing little damage and only wounding the animal. This shot requires a <strong>deep-penetrating bullet design</strong> to be effective. It should only be attempted when no other angle is available and the hunter is confident of precise placement.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Broadside Shot</h2>
<p>The broadside shot is <strong>the most recommended shot</strong> for hunting. The animal is standing perpendicular to the hunter, presenting the largest possible target area. The heart shot is placed <strong>just below the centre horizontal line, directly behind the front leg</strong>. This angle offers the <strong>largest kill zone</strong>, encompassing both the heart and lungs.</p>
<p class="mt-4">The <strong>double-lung shot</strong> is the best option for most hunting situations. It provides the greatest margin for error while still ensuring a quick, humane kill. Even a shot placed slightly high or back from the heart will pass through both lungs, resulting in rapid incapacitation.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Quartering Away Shot</h2>
<p>In the quartering away shot, the animal is <strong>facing away from the hunter at an angle</strong>. This angle can be misleading — there is a natural tendency to aim too far forward. The hunter must aim so that the projectile <strong>reaches the vital organs through the torso</strong>, angling forward through the body cavity. The point of aim is typically behind the last rib on the near side, directing the bullet diagonally through the chest to exit near the off-side shoulder.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Quartering Forward Shot</h2>
<p>In the quartering forward shot, the animal is <strong>facing toward the hunter at an angle</strong>. The aiming point is the <strong>chest area just inside the shoulder blade of the closer front leg</strong>. This directs the bullet into the vital chest cavity. The angle requires careful judgment to ensure the projectile reaches the heart-lung area and does not deflect off the heavy shoulder bone.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Shot Placement Principles</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Practice on paper targets first.</strong> Know your rifle and your own limitations before hunting live game.</li>
<li><strong>Know where your bullet impacts at various distances.</strong> Sight-in at the range and verify at different yardages.</li>
<li><strong>Know what lies behind the target.</strong> A bullet that passes through an animal or misses can travel a dangerous distance.</li>
<li><strong>Positively identify the target</strong> before placing your finger on the trigger. Never shoot at movement, sound, or shape alone.</li>
<li><strong>Approach the animal from behind</strong> where possible, using the wind to your advantage.</li>
<li><strong>Shoot at a target inside the animal, not on it.</strong> Visualise the position of the vital organs within the body and aim for them, not at the skin surface.</li>
<li><strong>Use an adequate caliber</strong> for the species you are hunting. An underpowered rifle leads to wounded, suffering animals.</li>
<li><strong>Be prepared for a follow-up shot.</strong> Keep the rifle loaded and on target after the first shot. Watch the animal's reaction to determine if a second shot is needed.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Crossing a Fence with a Rifle</h2>
<p>Crossing obstacles while carrying a firearm is one of the most dangerous moments during a hunt. Strict safety procedures must be followed at all times.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Single Person Crossing a Fence</h3>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li><strong>Unload the rifle completely</strong> — remove the magazine and ensure the chamber is empty.</li>
<li>Place the rifle through the fence, holding it by the grip. The muzzle must be pointed <strong>away from yourself and others</strong> at all times.</li>
<li>Lay it on the other side of the fence <strong>without getting debris into the barrel</strong>.</li>
<li>Climb through the fence carefully.</li>
<li><strong>Check the barrel for debris.</strong> If necessary, clear it.</li>
<li>Reload if necessary and continue with the stalk.</li>
</ol>

<h3 class="mt-5 mb-2 text-lg font-semibold">Two People Crossing a Fence</h3>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li><strong>Both hunters unload their rifles completely.</strong></li>
<li>One person holds both rifles while the other climbs through the fence.</li>
<li>The rifles are passed through the fence one at a time, <strong>muzzles pointed away from both people</strong>.</li>
<li>The second person then climbs through the fence.</li>
</ol>

<h3 class="mt-5 mb-2 text-lg font-semibold">Crossing a Ditch or River</h3>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li><strong>Unload the rifle completely.</strong></li>
<li>Sling the rifle over your shoulder so that <strong>both hands are free</strong> for balance and safety.</li>
<li>Cross the obstacle safely, maintaining your footing.</li>
</ol>

<h3 class="mt-5 mb-2 text-lg font-semibold">Climbing a Tree</h3>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li><strong>Unload the rifle completely</strong> and lay it on the ground. If hunting with a guide, the bolt may be left open and the rifle handed to the guide.</li>
<li>Climb the tree with both hands free.</li>
<li>Have the rifle raised to you by rope, or climb down to retrieve it.</li>
<li>Never attempt to climb while holding a rifle.</li>
</ol>
HTML;
    }

    protected function contentAnimalTracksIntro(?string $imagePath = null): string
    {
        $imageHtml = $imagePath
            ? '<div class="my-6"><img src="'.$this->learningImageUrl($imagePath).'" alt="Six animal tracks for identification" class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700"><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 text-center">Animal tracks as shown in the knowledge test — identify each track labelled A–F.</p></div>'
            : '';

        return <<<HTML
<p>Being able to identify animal tracks (spoor) is essential for hunting and fieldcraft. In the knowledge test you will be shown six tracks labelled A–F and asked to identify each one.</p>
{$imageHtml}
<p>Use the pages below to study each track individually. Key identification features include track shape, number of toes, presence or absence of claw marks, and overall size.</p>
<p class="mt-4">Other species such as dog, hyena, mountain zebra, blesbuck, impala and gemsbuck have distinct tracks. Learning to tell them apart from the six above is part of the test.</p>
HTML;
    }

    protected function contentTrackLeopard(): string
    {
        return <<<'HTML'
<h2>Track A — Leopard</h2>
<p>The leopard track is a <strong>round paw print</strong> with <strong>four toe pads</strong> arranged in a semi-circle ahead of a large rear pad. Because leopards have <strong>retractable claws</strong>, there are <strong>no claw marks</strong> visible in the track — this is the key feature that distinguishes cat tracks from dog/hyena tracks.</p>
<h3>Key identification features</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Shape:</strong> Round and compact</li>
<li><strong>Toes:</strong> Four toe pads, no claw impressions</li>
<li><strong>Rear pad:</strong> Large, tri-lobed heel pad</li>
<li><strong>Size:</strong> Approximately 5–12 cm long</li>
<li><strong>Distinguishing trait:</strong> No claw marks (retractable claws)</li>
</ul>
<h3>Comparison</h3>
<p>A <strong>dog</strong> or <strong>hyena</strong> track also shows four toes, but their claws are <strong>non-retractable</strong> and always leave marks in front of the toe pads. The overall shape of a dog track is more elongated, while the leopard's is rounder.</p>
HTML;
    }

    protected function contentTrackHippo(): string
    {
        return <<<'HTML'
<h2>Track B — Hippo</h2>
<p>The hippopotamus leaves a <strong>very large print</strong> with <strong>four round toes spread wide</strong>. It is one of the largest tracks you will encounter in African bushveld.</p>
<h3>Key identification features</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Shape:</strong> Four large, rounded toe impressions splayed outward</li>
<li><strong>Toes:</strong> Four toes of roughly equal size</li>
<li><strong>Size:</strong> Approximately 28 cm across</li>
<li><strong>Distinguishing trait:</strong> Massive size with four evenly spaced, round toe marks</li>
</ul>
<h3>Comparison</h3>
<p>A <strong>rhino</strong> track is also very large but has only <strong>three toes</strong>. The hippo's four distinct round toe impressions make it unmistakable once you know what to look for.</p>
HTML;
    }

    protected function contentTrackRhino(): string
    {
        return <<<'HTML'
<h2>Track C — Rhino</h2>
<p>The rhino produces a <strong>massive three-toed print</strong> that is one of the most recognisable tracks in Africa. Both white and black rhino leave a characteristic three-lobed impression.</p>
<h3>Key identification features</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Shape:</strong> Large, rounded print with three distinct toe lobes</li>
<li><strong>Toes:</strong> Three toes forming a clover-like shape</li>
<li><strong>Size:</strong> Approximately 25 cm across</li>
<li><strong>Distinguishing trait:</strong> Three-toed print — the only large African mammal with three toes</li>
</ul>
<h3>Comparison</h3>
<p>The <strong>hippo</strong> track is a similar size but has <strong>four toes</strong>. An <strong>elephant</strong> track is larger and more circular with no distinct toe separation.</p>
HTML;
    }

    protected function contentTrackZebra(): string
    {
        return <<<'HTML'
<h2>Track D — Burchell's Zebra</h2>
<p>Burchell's zebra, like all equines, walks on a <strong>single oval hoof</strong>. The track is essentially a large, rounded hoof print with no toe separation.</p>
<h3>Key identification features</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Shape:</strong> Single oval hoof impression</li>
<li><strong>Toes:</strong> One (single hoof, no cloven toes)</li>
<li><strong>Size:</strong> Approximately 10 cm long</li>
<li><strong>Distinguishing trait:</strong> Single, solid hoof — the only common African game animal with one toe</li>
</ul>
<h3>Comparison</h3>
<p>A <strong>mountain zebra</strong> track is narrower and more V-shaped compared to the rounder Burchell's zebra hoof. No antelope or other game species leaves a single-hoofed track, making zebra tracks unique.</p>
HTML;
    }

    protected function contentTrackWarthog(): string
    {
        return <<<'HTML'
<h2>Track E — Warthog</h2>
<p>The warthog track shows <strong>two pointed central hooves</strong> with <strong>two smaller dew claws</strong> behind them. It is a relatively small, cloven-hoofed track.</p>
<h3>Key identification features</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Shape:</strong> Two pointed front hooves with two smaller rounded marks behind</li>
<li><strong>Toes:</strong> Two main hooves plus two dew claws</li>
<li><strong>Size:</strong> Approximately 5–6 cm long (main hooves only ~2.5 cm)</li>
<li><strong>Distinguishing trait:</strong> Small, pointed hooves with prominent dew claw impressions</li>
</ul>
<h3>Comparison</h3>
<p>A <strong>bushpig</strong> track is similar but usually larger and rounder. Antelope tracks like <strong>impala</strong> or <strong>blesbuck</strong> have two pointed hooves but generally lack visible dew claw marks in normal walking gait. Warthog dew claws register more frequently because of the animal's heavier, lower build.</p>
HTML;
    }

    protected function contentTrackSitatunga(): string
    {
        return <<<'HTML'
<h2>Track F — Sitatunga</h2>
<p>The sitatunga is a <strong>semi-aquatic antelope</strong> with distinctively <strong>elongated, splayed hooves</strong> adapted for walking on soft, marshy ground. The track is unlike any other antelope's.</p>
<h3>Key identification features</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Shape:</strong> Long, banana-shaped hooves that splay wide apart</li>
<li><strong>Toes:</strong> Two elongated hooves that spread significantly</li>
<li><strong>Size:</strong> Approximately 9 cm long</li>
<li><strong>Distinguishing trait:</strong> Extremely elongated and splayed hooves — adapted for marshy habitat</li>
</ul>
<h3>Comparison</h3>
<p>Most antelope tracks (e.g. <strong>impala</strong>, <strong>gemsbuck</strong>) are more compact and pointed. The <strong>lechwe</strong>, another wetland antelope, also has elongated hooves but they are not as extremely splayed as the sitatunga's. The sitatunga's splayed shape is its most distinctive identifier.</p>
HTML;
    }

    protected function contentDirectionOfTravel(?string $imagePath = null): string
    {
        $imageHtml = $imagePath
            ? '<div class="my-6"><img src="'.$this->learningImageUrl($imagePath).'" alt="Direction of travel from animal tracks" class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700"><p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 text-center">Antelope spoor has an arrow shape indicating the direction of movement.</p></div>'
            : '';

        return <<<HTML
<p>Determining the direction an animal was travelling is one of the most fundamental tracking skills. By reading the tracks correctly, a hunter can anticipate where the animal is heading and position themselves strategically ahead of the quarry rather than simply following behind.</p>
{$imageHtml}

<h2 class="mt-6 mb-3 text-xl font-bold">Reading Direction from Tracks</h2>
<ul class="list-disc pl-6 my-4 space-y-3">
<li><strong>Track orientation</strong> — Footprints typically show a forward-facing pattern. The front (toe) of the track points in the direction the animal was moving. For hoofed animals, the pointed or narrow end of the hoofprint generally indicates the direction of travel.</li>
<li><strong>Antelope tracks</strong> — Antelope spoor often has a distinctive arrow or V shape. The pointed end of the V indicates the forward direction of movement. This is one of the easiest and most reliable indicators of direction.</li>
<li><strong>Drag marks</strong> — Some animals create small drag marks from their hooves when walking. These marks trail behind the direction of travel, appearing as shallow grooves or scuffs behind the main track impression.</li>
<li><strong>Displaced earth and sand</strong> — Soil is pushed backward from the direction of travel as the animal pushes off with each step. Deeper impressions at the front (toe) of the track indicate the forward push-off, while the rear of the track may show a slight ridge of displaced material.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Track Pattern and Gait</h2>
<p>Animals leave a regular pattern of footfalls when walking. In a normal walking gait, the front feet land slightly ahead of the hind feet. As the animal continues, the hind feet often land on or near the impression left by the front feet. By following this sequence of impressions, the tracker can confirm the direction of travel even when individual track details are unclear.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Assessing Track Freshness</h2>
<p>Knowing not only the direction but also how recently the animal passed is critical:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Fresh tracks</strong> have sharp, well-defined edges. The soil inside the track appears moist and undisturbed by wind or rain. Fine detail such as the texture of the hoof pad may be visible.</li>
<li><strong>Older tracks</strong> show weathering — edges crumble, soil dries out, small insects may have walked across them, and wind-blown debris or fallen leaves may partially fill the impression.</li>
<li>The rate of aging depends on soil type, moisture, sun exposure, and wind. Trackers develop a feel for these factors through experience in their local terrain.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Why Direction of Travel Matters</h2>
<p>Knowing the direction of travel allows the hunter to anticipate where the animal is heading — towards water, feeding areas, bedding sites, or escape cover. This enables the hunter to plan an approach route that keeps them downwind and positions them ahead of the animal, greatly increasing the chance of a successful and ethical shot.</p>
<p class="mt-4">In the knowledge test, a diagram shows tracks and you must identify whether the animal was walking left-to-right or right-to-left based on the track orientation.</p>
HTML;
    }

    protected function contentTrackingSpoorSign(): string
    {
        return <<<'HTML'
<p>Tracking in hunting and ecology is the <strong>science and art of observing animal tracks and other signs</strong>, with the goal of gaining understanding of the landscape and the animal being tracked (quarry). A further goal is deeper understanding of the systems and patterns that make up the environment. Skilled tracking combines observation, patience, and knowledge of animal behaviour.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Components of Spoor</h2>
<p>Trackers must be able to recognise and follow animals through their <strong>tracks, signs, and trails</strong>, collectively known as <strong>spoor</strong>. Spoor includes a wide range of evidence:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Tracks (footprints)</strong> — Impressions left in soil, sand, or mud. The shape, size, depth, and spacing of tracks indicate the species, size, speed, and direction of travel of the animal.</li>
<li><strong>Scat and droppings</strong> — The shape, size, composition, and freshness of droppings indicate the species, diet, and how recently the animal passed. Fresh droppings are moist and may have an odour; older droppings are dry and crumbly.</li>
<li><strong>Trails</strong> — Regularly used pathways through vegetation, worn down by repeated use. Trails may be used by multiple species.</li>
<li><strong>Feeding signs</strong> — Broken branches, stripped bark, chewed vegetation, uprooted plants, and partially eaten fruits or seeds indicate where and what an animal has been feeding on.</li>
<li><strong>Resting areas and beds</strong> — Flattened vegetation where an animal has lain down. The size and shape of the depression indicates the species and body size.</li>
<li><strong>Hair and fur</strong> — Caught on thorns, branches, bark, or fencing. The colour, length, and texture help identify the species.</li>
<li><strong>Scent marks and territorial markings</strong> — Urine, dung middens, rubbing posts, and secretions from scent glands left on trees, rocks, or the ground. These indicate territory boundaries and the presence of specific individuals.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Sign Tracking</h2>
<p><strong>Sign tracking</strong> is reading indirect evidence — anything besides an actual footprint that indicates the presence or passage of an animal. Approximately half of practical tracking is sign tracking and half is working with actual tracks. Signs include:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Disturbed vegetation — bent or broken grass, displaced leaves, trampled undergrowth.</li>
<li>Broken spider webs — a fresh break in a web across a trail indicates recent passage.</li>
<li>Turned or displaced stones — stones knocked from their resting place, exposing damp or lighter-coloured earth underneath.</li>
<li>Scuff marks on rocks or logs — where an animal has climbed over or scraped against a surface.</li>
<li>Rubbing marks on trees — bark worn smooth or stripped by animals rubbing horns, antlers, or bodies.</li>
<li>Kills and carcass remains — feathers, bones, and blood indicate predator activity.</li>
<li>Behaviour of other animals — alarm calls from birds or primates, fleeing prey species, or circling vultures all indicate the presence of a predator or disturbance.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Travel Routes</h2>
<ul class="list-disc pl-6 my-4 space-y-3">
<li><strong>Trails</strong> — Species non-specific "superhighways" that are regularly used by many different animals. Trails are typically wide, well-worn, and easy to follow. They often connect water sources to feeding and bedding areas.</li>
<li><strong>Runs</strong> — Frequently used narrow paths through thick bush, more specific to a particular animal or small group. Runs connect important resources such as watering points, bedding areas, and feeding grounds.</li>
<li><strong>Escape routes</strong> — Paths used by animals when fleeing from danger. These include <em>pushdowns</em> (used once, where an animal crashes through brush in panic) and <em>established escape routes</em> (used repeatedly, often leading to a hide or dense cover).</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Resting Areas</h2>
<ul class="list-disc pl-6 my-4 space-y-3">
<li><strong>Beds</strong> — Consistent overnight sleeping places, often located in thick brush or other protected areas that provide security from predators and shelter from weather.</li>
<li><strong>Transit beds</strong> — Used periodically during the day for temporary rest between activity periods. Animals may have several transit beds within their home range.</li>
<li><strong>Lays</strong> — Temporary resting spots used once or twice, often for activities such as chewing cud or digesting a meal. Recognised by broken and crushed vegetation in a body-sized area.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Aging Tracks</h2>
<p>Determining how old a track is (aging) is one of the most difficult and important tracking skills:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Fresh tracks</strong> have crisp, well-defined edges and moist soil. Fine details such as hoof texture and claw marks are clearly visible. Fresh tracks often appear darker because the exposed soil retains moisture.</li>
<li><strong>As time passes</strong>, edges crumble and collapse, soil dries out and lightens in colour, small insects walk across the track leaving their own tiny prints, wind-blown debris and fallen leaves partially fill the impression, and rain smooths or obliterates detail.</li>
<li>Key factors affecting aging include <strong>weather conditions</strong> (wind, rain, sun, humidity), <strong>soil type</strong> (sand ages differently from clay or loam), and <strong>gravity</strong> (loose soil crumbles into the track over time).</li>
<li>Experienced trackers calibrate their aging assessment by making their own tracks nearby and observing how quickly they change under the current conditions.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Practical Tracking Tips</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Track slowly and carefully — rushing leads to lost tracks and missed signs.</li>
<li>Do not step on the tracks you are following — preserve the spoor for re-examination if needed.</li>
<li>Look ahead to where the animal is heading, not just down at where it has been. Anticipate the animal's likely route based on terrain, vegetation, and known resources.</li>
<li>Use wind direction to stay downwind of the quarry. If the animal catches your scent, it will flee or change direction.</li>
<li>When tracking in a group, designate one person as the lead tracker to avoid trampling the spoor.</li>
</ul>
HTML;
    }

    protected function contentSurvivalFireKit(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">When Survival Situations Arise</h2>
<p>A survival situation can happen on any hunt — a vehicle breakdown in a remote area, becoming separated from the hunting party, getting lost in unfamiliar terrain, or sustaining an injury far from help. Every hunter should be mentally and physically prepared for the possibility that they may need to survive in the bush for an extended period with limited resources.</p>
<p class="mt-4">The ordinary person can survive approximately <strong>three minutes without air</strong>, <strong>three hours without protection in extreme conditions</strong>, <strong>three days without water</strong>, and <strong>three weeks without food</strong>. Understanding these timelines helps you prioritise correctly in an emergency.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">The Three Survival Priorities</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Keep Warm (or Cool)</h3>
<p><strong>Hypothermia</strong> (dangerously low body temperature) and <strong>heatstroke</strong> (dangerously high body temperature) are <strong>immediate threats</strong> that can kill faster than thirst or hunger. Regulating your body temperature is the first and most critical survival priority.</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>In cold environments: stay dry at all costs, insulate yourself from the ground, wear layers, generate heat through fire or activity, and avoid wind exposure.</li>
<li>In hot environments: seek shade, minimise physical exertion during the heat of the day, protect exposed skin from direct sunlight, and conserve body fluids.</li>
<li>Wet clothing accelerates heat loss dramatically. If you become wet in cold conditions, remove or wring out wet clothing and replace with dry layers if available.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Find Water</h3>
<p>Humans can survive approximately <strong>three days without water</strong>, but performance and judgment degrade rapidly after the first day. Dehydration leads to confusion, weakness, and inability to make good decisions — all of which compound the survival problem.</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Minimise physical movement to conserve body water.</li>
<li>Avoid fluids that increase urination (coffee, alcohol, caffeine-containing substances).</li>
<li>Look for natural water sources: streams, rivers, rock pools, dew on vegetation, and water-collecting plants.</li>
<li>Purify water before drinking whenever possible — boiling is the most reliable field method. Even clear-looking water may contain harmful organisms.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Take Shelter</h3>
<p>Protection from the elements — rain, wind, direct sun, and cold night temperatures — is essential. Shelter improves body temperature regulation and conserves energy.</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Use natural features: rock overhangs, dense bush, fallen trees, or caves.</li>
<li>Construct a basic lean-to or windbreak using branches, leaves, and available materials.</li>
<li>Insulate the ground beneath you to prevent heat loss — use grass, leaves, branches, or a backpack.</li>
<li>Only after addressing warmth, water, and shelter should you begin thinking about food.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">The Fire-Making Kit</h2>
<p>Every hunter should carry a fire-making kit as part of their standard field equipment. A well-prepared kit contains multiple methods of ignition, because any single method can fail:</p>

<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Item</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">How It Works</th>
</tr>
</thead>
<tbody>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"><strong>Lighter</strong></td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Easiest and fastest ignition source. Keep dry and protected.</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"><strong>Matches</strong></td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Waterproof or strike-anywhere matches preferred. Store in a waterproof container.</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"><strong>Magnesium bar (metal match)</strong></td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Scrape shavings from the bar into a pile, then strike the flint strip to create sparks. Works even when wet.</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"><strong>Magnifying glass</strong></td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Focuses sunlight to ignite tinder. A lens from binoculars or a rifle scope can serve as a substitute in an emergency.</td></tr>
<tr><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1"><strong>Steel wool and battery</strong></td><td class="border border-zinc-300 dark:border-zinc-600 px-3 py-1">Touching fine steel wool across the terminals of a battery causes it to heat and ignite. An emergency method that works reliably.</td></tr>
</tbody>
</table>
</div>

<h2 class="mt-6 mb-3 text-xl font-bold">Uses of Fire in a Survival Situation</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Warmth</strong> — Prevents hypothermia and maintains core body temperature through cold nights.</li>
<li><strong>Cooking</strong> — Makes food safer and more digestible; kills parasites and bacteria in meat.</li>
<li><strong>Signalling</strong> — Smoke during the day and flame at night are visible from great distances and alert rescuers to your location.</li>
<li><strong>Purifying water</strong> — Boiling water kills harmful organisms and makes it safe to drink.</li>
<li><strong>Keeping predators away</strong> — Most wild animals avoid fire and the smell of smoke.</li>
<li><strong>Morale</strong> — The psychological comfort of a fire in a survival situation should not be underestimated. It provides a sense of security and normalcy.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Extinguishing a Fire</h2>
<p>It is <strong>critical</strong> to properly extinguish any fire before leaving the site. An unattended fire in the bush can cause catastrophic veld fires that destroy habitat, wildlife, and property.</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Drown</strong> the fire with water — pour water over all embers, coals, and surrounding ground.</li>
<li><strong>Stir</strong> the ashes thoroughly to expose hidden embers, then drown again.</li>
<li><strong>Feel for heat</strong> — hold your hand near (not on) the ashes to check for remaining warmth. If warm, repeat the process.</li>
<li>If water is scarce, bury the fire with <strong>at least one foot of dirt</strong> and place heavy stones on top.</li>
<li>Never leave a fire site until you are certain it is completely extinguished.</li>
</ul>
HTML;
    }

    protected function contentHuntingRifleBallistics(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">How a Rifle Fires</h2>
<p>Understanding the sequence of events from trigger pull to bullet impact is fundamental to understanding ballistics:</p>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li>The <strong>firing pin strikes the primer</strong> at the base of the cartridge.</li>
<li>The primer ignites, sending a flash into the powder charge.</li>
<li>The <strong>powder burns rapidly</strong>, creating hot expanding gases.</li>
<li>Increasing gas pressure <strong>propels the bullet through the barrel</strong>.</li>
<li><strong>Rifling</strong> (spiral grooves cut into the barrel) engages the bullet and imparts spin, stabilising it in flight.</li>
<li>The bullet <strong>exits the muzzle</strong> and travels downrange under its own inertia.</li>
<li><strong>Air resistance</strong> (drag) gradually slows the bullet from the moment it leaves the barrel.</li>
<li><strong>Gravity</strong> pulls the bullet downward from the instant it exits the muzzle, causing bullet drop.</li>
<li>The rifle is <strong>sighted-in</strong> to compensate: the barrel is angled very slightly upward relative to the line of sight, so the bullet's arcing trajectory intersects the line of sight at the desired range.</li>
</ol>

<h2 class="mt-6 mb-3 text-xl font-bold">Trajectory and Line of Sight</h2>
<p>Because the barrel is angled slightly upward relative to the scope or sights, the bullet <strong>rises above the line of sight</strong> after leaving the muzzle. It reaches its <strong>maximum height above the line of sight</strong> at approximately the midpoint of its flight — this is called the <strong>mid-range trajectory</strong>, typically 2 to 4 inches above the line of sight for most hunting cartridges sighted-in at 200 yards.</p>
<p class="mt-4">After reaching maximum height, the bullet begins to fall. It <strong>crosses the line of sight again at the sight-in range</strong> — this is where bullet impact and point of aim coincide. Beyond this range, the bullet drops increasingly below the line of sight. If the quarry is closer than the sight-in range, the bullet will strike slightly high; if substantially farther, it will strike low.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Uphill and Downhill Shooting</h2>
<p>The key principle of angled shooting is that bullet trajectory depends on the <strong>horizontal (level) range</strong> to the target, not the <strong>line-of-sight (slant) range</strong>. When shooting steeply uphill or downhill, the horizontal distance to the target is always less than the line-of-sight distance.</p>
<p class="mt-4">This means you should <strong>hold lower than normal</strong> when shooting at steep angles, whether up or down. The bullet will drop less than expected because gravity acts over the shorter horizontal distance, not the longer slant distance.</p>
<p class="mt-4"><strong>Example:</strong> At a 40-degree angle, a target that is 400 yards away along the line of sight is only approximately 335 yards in horizontal range. Your hold and bullet drop should be calculated for 335 yards, not 400.</p>
<p class="mt-4">At the extremes: if you were shooting <strong>straight down</strong> (90 degrees), the bullet would have virtually no curved trajectory — it falls straight. If shooting <strong>straight up</strong>, the same applies. In both cases, the horizontal range is effectively zero.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Ballistic Coefficient (BC)</h2>
<p>The <strong>Ballistic Coefficient</strong> is a measure of how streamlined a bullet is — how efficiently it cuts through the air. Technically, it is the ratio of a bullet's <strong>sectional density</strong> to its <strong>coefficient of form</strong> (a measure of aerodynamic shape).</p>
<p class="mt-4">A <strong>higher BC</strong> means less aerodynamic drag, better velocity retention, flatter trajectory, and less wind drift. Factors that increase BC include:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Spitzer (pointed) shape</strong> — more aerodynamically efficient than a round nose</li>
<li><strong>Boat-tail base</strong> — a tapered base that reduces drag at the rear of the bullet</li>
<li><strong>Higher sectional density</strong> — heavier bullets of a given caliber tend to have higher BC</li>
</ul>
<p class="mt-4">BC is one of the most important factors determining both trajectory and wind drift at longer ranges.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Sighting-In</h2>
<p><strong>Sighting-in</strong> is the process of adjusting the line of sight (scope or iron sights) so that at a given distance, the bullet trajectory and the line of sight intersect — meaning the bullet strikes where the crosshairs are aimed.</p>
<p class="mt-4">You must <strong>sight-in before every hunt</strong> with the specific ammunition you plan to use. A rifle that was sighted-in previously could have been knocked out of alignment by a single jolt during transport, a bump to the scope, or even changes in temperature and altitude. Never assume a rifle is still sighted-in without verifying at the range.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Bullet Energy</h2>
<p>The energy a bullet delivers to the target is what causes tissue damage and ensures a humane kill. Bullet energy is calculated using the formula:</p>
<p class="mt-4 font-mono text-center"><strong>E = W x V&sup2; / 450,450</strong></p>
<p class="mt-2 text-sm text-center">Where W = bullet weight in grains, V = velocity in feet per second, E = energy in foot-pounds.</p>
<p class="mt-4">Two key principles follow from this formula:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Bullet weight is constant</strong> throughout flight, but <strong>velocity decreases</strong> continuously due to air resistance. Therefore energy decreases with range.</li>
<li><strong>Heavier bullets carry more energy</strong> at any given velocity.</li>
<li>Because velocity is <strong>squared</strong> in the formula, increasing velocity has a proportionally much greater effect on energy than increasing weight. A small increase in velocity produces a large increase in energy.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Bullet Selection</h2>
<p>The bullet must achieve two objectives: it must <strong>penetrate sufficiently</strong> to reach the vital organs, and it must <strong>expand or fragment enough</strong> to cause significant internal bleeding and tissue damage for a quick kill.</p>
<p class="mt-4">Different bullet types are suited to different quarry:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Frangible (varmint) bullets</strong> — designed for small game under approximately 30 lbs (14 kg). They break apart rapidly on impact, transferring all energy quickly. Not suitable for larger game as they lack penetration.</li>
<li><strong>Expanding (soft-point, hollow-point) bullets</strong> — the standard choice for big game hunting. They mushroom on impact, creating a wide wound channel while still penetrating deeply enough to reach vital organs.</li>
<li><strong>Non-expanding (full metal jacket) bullets</strong> — designed for maximum penetration on very large or thick-skinned game such as buffalo and elephant, where deep straight-line penetration to the vitals is essential. Not suitable for thinner-skinned game as they may pass through without transferring adequate energy.</li>
</ul>
<p class="mt-4">Always <strong>match the bullet to the quarry</strong>. Using the wrong bullet type can result in either insufficient penetration or insufficient energy transfer, both leading to wounded animals.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Key Definitions</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Projectile</strong> — an object set in motion by an exterior force and continuing under its own inertia.</li>
<li><strong>Trajectory</strong> — the curved path a projectile describes through space under the influence of gravity and air resistance.</li>
<li><strong>Ballistics</strong> — the study of the motion and behaviour of projectiles.</li>
<li><strong>Gravity</strong> — the force that pulls the bullet downward from the moment it leaves the barrel, causing bullet drop.</li>
<li><strong>Air resistance (drag)</strong> — the force that opposes the bullet's forward motion, progressively slowing it and reducing energy.</li>
<li><strong>Twist rate</strong> — the rate of rifling spin in the barrel, expressed as one full rotation per a given number of inches (e.g. 1:10 means one rotation every 10 inches).</li>
<li><strong>Sighting-in</strong> — adjusting the sights so that bullet impact coincides with point of aim at a specified range.</li>
</ul>
HTML;
    }

    protected function contentCaliberSelection(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">What is Caliber?</h2>
<p><strong>Caliber</strong> describes the bore size of a firearm and the cartridge it fires. It is measured as the <strong>bore diameter from land to land</strong> (the raised portions of the rifling), expressed in hundredths or thousandths of an inch (e.g. .308) or in millimetres (e.g. 7.62mm).</p>
<p class="mt-4"><strong>Why are there so many calibers?</strong> Throughout firearms history, tinkerers and gunsmiths have modified existing brass cases and rifle chambers to create new cartridges optimised for specific purposes. Successful experimental cartridges — known as "wildcats" — eventually gain enough popularity to be adopted by ammunition manufacturers and become commercially available standard calibers.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Caliber Categories</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">Small Calibers (.22 and under)</h3>
<p>Used primarily for target shooting, small game, and varmint hunting. Common examples include:</p>
<ul class="list-disc pl-6 my-4 space-y-1">
<li>.17 Hornady Magnum Rimfire (HMR)</li>
<li>.22 Short, .22 Long Rifle (LR), .22 Magnum (rimfire)</li>
<li>.22 Hornet, .220 Swift, .22-250 Remington (centrefire)</li>
<li>.222 Remington, .223 Remington</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Medium Calibers (100gr to 300gr bullets)</h3>
<p>The most versatile category, covering everything from small antelope to large plains game. Common examples include:</p>
<ul class="list-disc pl-6 my-4 space-y-1">
<li>.243 Winchester, .25-06 Remington</li>
<li>6.5 Creedmoor, .270 Winchester</li>
<li>7mm Remington Magnum</li>
<li>.308 Winchester, .30-06 Springfield</li>
<li>.300 Winchester Magnum</li>
<li>.375 Holland &amp; Holland Magnum</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Medium-Heavy Calibers (300gr to 500gr bullets)</h3>
<p>Intended for heavy and dangerous game. Common examples include:</p>
<ul class="list-disc pl-6 my-4 space-y-1">
<li>.45-70 Government</li>
<li>.404 Jeffery</li>
<li>.416 Rigby / .416 Remington Magnum</li>
<li>.458 Winchester Magnum, .458 Lott</li>
<li>.470 Nitro Express</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Heavy Calibers (.50 and above)</h3>
<p>Specialised for the largest and most dangerous game:</p>
<ul class="list-disc pl-6 my-4 space-y-1">
<li>.505 Gibbs</li>
<li>.577 Nitro Express</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Ultra-Heavy Calibers (525gr to 1000gr bullets)</h3>
<p>Extreme stopping power for the most dangerous situations:</p>
<ul class="list-disc pl-6 my-4 space-y-1">
<li>.600 Nitro Express</li>
<li>.700 Nitro Express</li>
<li>.585 Nyati</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Regulations</h2>
<p>South African provincial ordinances impose the following key caliber restrictions:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>No .22 rimfire</strong> may be used for hunting game animals (except birds or hares).</li>
<li><strong>No shotgun</strong> may be used for hunting game animals (except game birds).</li>
<li><strong>No semi-automatic or self-loading rifle</strong> may be used to hunt ordinary or protected game. Semi-automatics may be used for problem animals and wild animals that are not classified as game.</li>
<li>On <strong>P3-exempted farms</strong>, hunting with almost any weapon may be allowed under the specific permit conditions.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Adequate Caliber</h2>
<p>Selecting an adequate caliber is the <strong>hunter's responsibility</strong>. A professional hunter will not allow hunting of any animal with a weapon that has insufficient killing power. Factors that determine adequate caliber include:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Animal size and toughness</strong> — larger animals require more energy and deeper penetration</li>
<li><strong>Shot placement</strong> — even a large caliber cannot compensate for a poorly placed shot</li>
<li><strong>Bullet type</strong> — the bullet must be matched to the quarry for proper penetration and expansion</li>
<li><strong>Distance</strong> — bullets lose energy over range; adequate caliber at 100 yards may be marginal at 300</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Ammunition Safety</h2>
<p>Always <strong>match your ammunition exactly to the data stamp on the barrel</strong> of your rifle. The data stamp indicates the specific cartridge the rifle is chambered for. Never use the wrong ammunition — even a cartridge that is close in size can chamber partially and cause a <strong>dangerous obstruction or catastrophic failure</strong> when fired. If in doubt, have a qualified gunsmith verify the correct ammunition for your firearm.</p>
HTML;
    }

    protected function contentPlanningAHunt(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">Hunting Seasons</h2>
<p>Hunting in South Africa is regulated with <strong>clearly defined seasons</strong> that respect the breeding cycles of wildlife. Traditionally, the hunting season runs from <strong>May to August</strong> (the winter months), though this varies by province and district. Some species may have different open and closed periods depending on local regulations.</p>
<p class="mt-4">The responsibility lies with the hunter to be informed about dates, species, and area-specific regulations before undertaking any hunt.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">The 5 W Questions</h2>
<p>A well-planned hunt begins with answering five key questions in order:</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">When?</h3>
<p>Have a specific date or date range in mind before contacting an outfitter. This allows the operator to confirm availability and ensures your hunt falls within the legal hunting season for the species you intend to pursue.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">What?</h3>
<p>Decide which animals you want to hunt. Be realistic — your wish list may not make practical sense for a single trip due to logistics, habitat differences, and geographic distances between areas where different species are found. Discuss your list with the outfitter to create a realistic plan.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Where?</h3>
<p>The hunting destination is largely determined by what you want to hunt. Different species inhabit different regions, habitats, and terrain types. Your outfitter will recommend areas best suited to your target species.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">How?</h3>
<p>Before paying any deposit, get the details of the hunt in the form of a <strong>written itinerary</strong>. This should cover daily schedules, hunting methods, accommodation, meals, transport, and what is included or excluded from the quoted price.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Why?</h3>
<p>Communicate your objectives and expectations clearly to the outfitter. Is this a family trip where everyone needs to be accommodated? Are you focused on trophy quality? Do you want exclusivity on the property? Do you need specific amenities? Clear communication prevents misunderstandings and ensures both parties have aligned expectations.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Factors to Consider</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Place</strong> — Assess the quality of the hunting area and accommodation. Ask for references from previous clients and check reviews.</li>
<li><strong>Product</strong> — Are the animals in their natural habitat, or are they behind high fences on a small property? Quality of the hunting experience depends on the naturalness of the environment.</li>
<li><strong>Communication</strong> — Is the outfitter responsive and professional? Poor communication before the hunt usually means poor service during the hunt.</li>
<li><strong>Price</strong> — Understand exactly what is included and excluded. Clarify day fees, trophy fees, taxidermy, transport, accommodation, meals, and any other costs. Get it in writing.</li>
<li><strong>Permits</strong> — Some species (particularly TOPS-listed animals) require special permits. Confirm that the farm has the necessary permits for the species you want to hunt.</li>
<li><strong>Slaughter Facilities</strong> — Verify that proper cold room and slaughter facilities are available for handling harvested animals hygienically.</li>
<li><strong>Wellness</strong> — Ensure you have an adequate supply of any personal medication. Confirm your medical aid coverage for the area. Carry a well-stocked first aid kit on every hunting trip.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Documentation Checklist</h2>
<p>Ensure all documentation is in order before departing for a hunt. Missing documents can result in prosecution, confiscation of firearms, and loss of firearm rights.</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Firearm licence</strong> — carry the original licence for every firearm you are transporting.</li>
<li><strong>Valid hunting licence</strong> — requirements differ for ordinary game, protected game, and game birds. Some provinces require separate licences.</li>
<li><strong>Import/export permit</strong> — required when transporting firearms across provincial or national borders.</li>
<li><strong>Vehicle and trailer licences</strong> — ensure all vehicle documentation is current.</li>
<li><strong>Written hunting permit</strong> — must bear the signatures of both the landowner and the hunter. On exempted farms, the permit must include the farm number.</li>
<li><strong>Statement of donation or sale of carcasses</strong> — required when game meat or trophies change hands.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Hunting Techniques</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">Stalking</h3>
<p>Stalking involves slow, silent movements to close the distance between the hunter and the quarry. The hunter uses terrain, vegetation, and wind direction as cover. Stalk near areas where animals are likely to be found — near water, feeding areas, or resting spots. Patience and stealth are essential.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Still Hunting</h3>
<p>Still hunting is a method of <strong>slow, deliberate walking</strong> through the hunting area, pausing frequently to observe and listen. The rule of thumb is <strong>90% watching and listening, 10% moving</strong>. The hunter moves a few steps, stops, scans the surroundings thoroughly, then moves again. This is an active technique that requires keen observation skills.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Driven Hunt</h3>
<p>In a driven hunt, <strong>shooters are positioned in ambush</strong> at strategic points while <strong>drivers move through the area</strong> to stir game toward the waiting hunters. Safety is critical during driven hunts — each shooter must know the exact positions of all other participants, and strict rules about shooting angles and zones must be observed. Never shoot toward other hunters or drivers.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Stand/Post Hunting</h3>
<p>The hunter <strong>remains still at a fixed position</strong> near known animal trails, water points, or feeding areas, waiting for game to come within range. This method requires patience but can be highly effective, especially during early morning and late afternoon when animals are most active.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Blind Hunting</h3>
<p>A <strong>temporary or permanent structure</strong> is used to conceal the hunter. The blind should be positioned <strong>downwind</strong> of the expected approach route of the game, and placed <strong>out of direct sunlight</strong> where possible to avoid overheating and to prevent silhouettes being visible from outside. The hunter waits inside the blind for game to approach within effective range.</p>
HTML;
    }

    protected function contentFirstAidTerms(): string
    {
        return <<<'HTML'
<p>Understanding basic first aid is essential for any hunter. Injuries and medical emergencies can occur far from professional medical help, and prompt, correct action can save a life. Below are the key first aid conditions every hunter should know how to recognise and treat.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Shock</h2>
<p><strong>What it is:</strong> Shock is a life-threatening condition in which the body's vital organs do not receive enough blood flow. It can result from severe bleeding, trauma, burns, allergic reactions, or emotional distress.</p>
<p class="mt-4"><strong>Signs and symptoms:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Weakness and dizziness</li>
<li>Rapid, weak pulse</li>
<li>Pale, cold, and clammy skin</li>
<li>Shallow, rapid breathing</li>
<li>Nausea or vomiting</li>
<li>Confusion or anxiety</li>
</ul>
<p class="mt-4"><strong>Treatment:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Lay the person down on their back.</li>
<li>Elevate the legs approximately 30 cm (12 inches) above heart level, unless a leg injury prevents this.</li>
<li>Keep the person warm — cover with a blanket, jacket, or any available insulation.</li>
<li>Reassure the person calmly and keep them still.</li>
<li>Do not give food or drink.</li>
<li>Seek medical help as soon as possible — shock can be fatal if untreated.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Fainting</h2>
<p><strong>What it is:</strong> A temporary loss of consciousness caused by a sudden drop in blood pressure, which reduces blood flow to the brain.</p>
<p class="mt-4"><strong>Common causes:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Pain or emotional stress (e.g. seeing blood)</li>
<li>Overheating or dehydration</li>
<li>Prolonged standing, especially in hot conditions</li>
<li>Low blood sugar</li>
</ul>
<p class="mt-4"><strong>Treatment:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Lay the person down flat on their back.</li>
<li>Elevate the legs to restore blood flow to the brain.</li>
<li>Loosen tight clothing (collar, belt, chest straps).</li>
<li>Apply cool water to the face and neck.</li>
<li>When the person regains consciousness, keep them lying down for several minutes before allowing them to sit up slowly.</li>
<li>If the person does not regain consciousness within one minute, place them in the recovery position and seek medical help.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">External Bleeding</h2>
<p><strong>What it is:</strong> Blood loss from a wound visible on the outside of the body. External bleeding can range from minor cuts to life-threatening haemorrhage.</p>
<p class="mt-4"><strong>Treatment:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Apply <strong>firm, direct pressure</strong> to the wound with a clean cloth, gauze pad, or any available clean material.</li>
<li><strong>Elevate</strong> the injured area above heart level where possible to reduce blood flow to the wound.</li>
<li>Maintain <strong>continuous pressure for at least 10 minutes</strong> without lifting the cloth to check — this allows clotting to begin.</li>
<li>If blood soaks through, add more material on top — do not remove the original dressing.</li>
<li>Once bleeding slows, apply a firm <strong>bandage</strong> to hold the dressing in place.</li>
<li>For severe or uncontrollable bleeding, apply a tourniquet above the wound if trained to do so, and seek emergency medical help immediately.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Bandaging</h2>
<p><strong>What it is:</strong> The application of a strip of material to hold a dressing in place, reduce bleeding, support an injured limb, or immobilise a fracture.</p>
<p class="mt-4"><strong>Types of bandages:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Roller bandage</strong> — a strip of material wound around the injured area in overlapping layers.</li>
<li><strong>Triangular bandage</strong> — a versatile bandage that can be used as a sling, head covering, or folded into a broad or narrow bandage.</li>
<li><strong>Pressure bandage</strong> — a bandage applied firmly over a wound dressing to control bleeding through direct pressure.</li>
</ul>
<p class="mt-4"><strong>Rules for bandaging:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Not too tight — check circulation beyond the bandage (fingers or toes should remain warm and pink). Loosen immediately if numbness, tingling, or blue discolouration occurs.</li>
<li>Use clean materials wherever possible to reduce infection risk.</li>
<li>Immobilise the area if a fracture is suspected — do not attempt to straighten a broken bone.</li>
<li>Secure the bandage firmly so it does not unravel, but ensure the patient can still breathe and move comfortably.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Burns</h2>
<p><strong>What it is:</strong> Tissue damage caused by heat, chemicals, electricity, or radiation. Burns are classified by depth:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>First degree (superficial)</strong> — redness and pain, affecting only the outer skin layer. Similar to mild sunburn.</li>
<li><strong>Second degree (partial thickness)</strong> — blistering, severe pain, redness, and swelling. Affects deeper skin layers.</li>
<li><strong>Third degree (full thickness)</strong> — charred or white/waxy skin, may be painless due to nerve destruction. Affects all layers of skin and possibly underlying tissue.</li>
</ul>
<p class="mt-4"><strong>Treatment:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Cool the burn with <strong>running water for at least 20 minutes</strong>. This is the single most effective first aid measure for burns.</li>
<li>Remove clothing and jewellery from the burned area unless stuck to the wound.</li>
<li>Cover the burn with a <strong>clean, non-stick dressing</strong> (cling film works well as a temporary cover).</li>
<li>Do <strong>NOT</strong> apply butter, oil, toothpaste, egg white, or any home remedy — these trap heat and increase infection risk.</li>
<li>Do <strong>NOT</strong> burst blisters — they protect the wound from infection.</li>
<li>Seek medical help for all large burns, burns to the face/hands/feet/genitals, third-degree burns, or burns that encircle a limb.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Rabies</h2>
<p><strong>What it is:</strong> A viral disease transmitted through the bite (or scratch) of an infected animal. Rabies affects the central nervous system and is <strong>almost always fatal</strong> once symptoms appear. Common carriers include jackals, mongooses, wild dogs, bats, and domestic dogs or cats that have not been vaccinated.</p>
<p class="mt-4"><strong>Signs to watch for:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Any bite or scratch from a wild animal or an animal behaving abnormally.</li>
<li>Animals showing unusual aggression, loss of fear of humans, excessive drooling, or paralysis.</li>
</ul>
<p class="mt-4"><strong>Immediate action:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Wash the wound immediately</strong> with soap and running water for at least <strong>15 minutes</strong>. This is critical and significantly reduces the risk of infection.</li>
<li>Apply an antiseptic (e.g. iodine or alcohol-based solution) after washing.</li>
<li>Seek <strong>medical attention urgently</strong> — anti-rabies treatment (post-exposure prophylaxis) must start as soon as possible.</li>
<li>Do not attempt to catch or kill the animal, but note its appearance and behaviour for the medical team.</li>
</ul>
<p class="mt-4"><strong>All animal bites sustained in the bush should be treated as potential rabies exposures</strong> until proven otherwise. Do not wait for symptoms before seeking treatment.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Ticks</h2>
<p><strong>What they are:</strong> Small parasites that attach to the skin and feed on blood. Ticks are common in the South African bush and can transmit serious diseases, most notably <strong>tick bite fever</strong> (African tick bite fever, caused by Rickettsia africae).</p>
<p class="mt-4"><strong>Tick removal:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Grasp the tick as close to the skin surface as possible using <strong>fine-tipped tweezers</strong>.</li>
<li>Pull <strong>steadily upward</strong> with even pressure. Do <strong>NOT twist or jerk</strong>, as this may cause the mouthparts to break off and remain in the skin.</li>
<li>Do <strong>NOT crush</strong> the tick with your fingers — this can release infectious material.</li>
<li>Do <strong>NOT</strong> apply petroleum jelly, nail polish, a hot match, or other substances to try to make the tick "back out." These methods are ineffective and may cause the tick to regurgitate infectious material into the wound.</li>
<li>After removal, clean the bite area thoroughly with <strong>antiseptic</strong>.</li>
<li>Monitor for symptoms of tick bite fever over the following 1-2 weeks: fever, headache, muscle pain, and a dark scab (eschar) at the bite site with a rash.</li>
</ul>
<p class="mt-4"><strong>Prevention:</strong></p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Wear long trousers tucked into socks or gaiters, and long-sleeved shirts.</li>
<li>Use insect repellent containing DEET on exposed skin and permethrin on clothing.</li>
<li>Check your entire body thoroughly for ticks after every outing — paying special attention to the hairline, behind the ears, armpits, groin, and behind the knees.</li>
<li>Remove ticks as soon as they are found — the sooner a tick is removed, the lower the risk of disease transmission.</li>
</ul>
HTML;
    }

    // --------------- Sport shooter content bodies ---------------
    protected function contentNRAPAPostalCompliance(): string
    {
        return <<<'HTML'
<p>The <strong>National Rifle and Pistol Association (NRAPA)</strong> is accredited by the South African Police Services (SAPS) with the designated powers to allocate <strong>Dedicated Hunter and Dedicated Sport Shooter status</strong> to its members.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">NRAPA Commitments</h2>
<p>The Association is committed to:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Promoting the <strong>sustainable utilisation of wildlife</strong> as a conservation tool and promoting ethical, responsible hunting as well as sport shooting activities in a controlled environment.</li>
<li>Liaising with authorities and participating in consultative and decision-making processes to promote members' interests.</li>
<li>Assisting and cooperating with authorities and organisations with similar objectives in the conservation, distribution and sustainable utilisation of wildlife.</li>
<li>Obeying all <strong>laws, regulations, codes of conduct</strong> and practices pertaining to hunting and the private possession of arms and ammunition.</li>
<li>Conducting <strong>training and educational courses</strong> for its members — Dedicated Hunter and Sport Shooter.</li>
<li>Promoting at all times the ethic of <strong>"Single Shot Humane Kill"</strong> to ensure the humane harvesting of game.</li>
<li>Promoting <strong>active participation in lawful sport shooting</strong> (range practice, sanctioned events, and club activities).</li>
<li>Pursuing these objectives without political or sectarian bias.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Compliance Keywords</h2>
<p>NRAPA promotes obeying all <strong>laws</strong>, <strong>regulations</strong>, <strong>codes of conduct</strong> and practices pertaining to <strong>arms</strong> (firearms), <strong>hunting</strong>, and the private <strong>possession</strong> of firearms and ammunition. The six key compliance words are:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Laws</strong> — all national and provincial legislation governing firearms and hunting</li>
<li><strong>Arms</strong> — responsible handling and storage of all firearms</li>
<li><strong>Hunting</strong> — ethical and lawful hunting practices</li>
<li><strong>Possession</strong> — legal possession under the Firearms Control Act</li>
<li><strong>Regulations</strong> — compliance with FCA Regulations and provincial ordinances</li>
<li><strong>Codes of conduct</strong> — adherence to NRAPA and industry ethical codes</li>
</ul>
HTML;
    }

    protected function contentPurposeSportEducation(): string
    {
        return <<<'HTML'
<p>The purpose of sport shooter education is to train sport shooters to become:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Safe</strong> — by following all firearm and shooting safety rules at all times, on and off the range.</li>
<li><strong>Responsible</strong> — about sport shooting, wildlife conservation, and compliance with all applicable laws and regulations.</li>
<li><strong>Knowledgeable</strong> — by knowing and demonstrating acceptable behaviour and attitudes while shooting, and understanding the firearms they use.</li>
<li><strong>Involved</strong> — in joining and actively participating in sport shooting organisations, sanctioned competitions, and dedicated shooting activities.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Outcome of the Programme</h2>
<p>Sport shooter education is important because it:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Helps <strong>prevent shooting accidents</strong> by instilling safe handling habits and knowledge.</li>
<li>Improves <strong>sport shooting skills</strong> through structured training and ongoing practice.</li>
<li>Promotes <strong>compliance with the Firearms Control Act</strong> and all applicable regulations.</li>
<li>Improves shooter behaviour, ensuring <strong>public acceptance of sport shooting</strong> as a responsible activity.</li>
<li>Encourages <strong>regular participation</strong> in lawful sport shooting and dedicated activities, which are required to maintain dedicated status.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">NRAPA Code of Conduct — Firearm Safety</h2>
<p>The NRAPA Code of Conduct for Firearm Safety is built on three cardinal rules:</p>
<ol class="list-decimal pl-6 my-4 space-y-2">
<li><strong>ALWAYS keep the gun pointed in a safe direction.</strong> This is the primary rule of gun safety. A safe direction means that even if the gun were to go off, it would not cause injury or damage.</li>
<li><strong>ALWAYS keep your finger off the trigger until ready to shoot.</strong> Rest your finger on the trigger guard or along the side of the gun. Do not touch the trigger until you are actually ready to fire.</li>
<li><strong>ALWAYS keep the gun unloaded until ready to use.</strong> Whenever you pick up a gun, engage the safety if possible, remove the magazine if present, open the action, and check that the chamber is clear.</li>
</ol>
HTML;
    }

    protected function contentDedicatedSportShooterStatus(): string
    {
        return <<<'HTML'
<p>South Africa's Firearms Control Act (FCA) requires applicants for a licence in the dedicated category to be members of an accredited sport-shooting (or hunting) organisation and to have completed a training course and assessment. NRAPA is accredited by the SAPS to allocate Dedicated Sport Shooter status.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">FCA Obligations of the Accredited Association</h2>
<p>The FCA places specific obligations on accredited associations such as NRAPA:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>The association must <strong>regularly evaluate</strong> its dedicated members' sport-shooting activities to confirm ongoing participation.</li>
<li>The association must <strong>keep records</strong> of all members' sport-shooting participation throughout the year.</li>
<li>The association must <strong>annually, before year-end</strong>, submit a written report to the Registrar of Firearms containing all dedicated members' details.</li>
<li>The association must <strong>furnish particulars</strong> of any members whose dedicated membership has lapsed, together with the reasons for the lapse.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">The Dedicated Licence (Section 16)</h2>
<p>A dedicated licence under Section 16 of the FCA allows the holder to possess a handgun, rifle, or shotgun (not fully automatic). Semi-automatic shotguns are permitted but are limited to a <strong>maximum capacity of 5 shots</strong>. The licence is valid for <strong>10 years</strong>.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Requirements to Obtain Dedicated Status</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Pass NRAPA's prescribed dedicated award scheme successfully, achieving at least <strong>70% in the written/theoretical test</strong>.</li>
<li>Pass NRAPA's practical <strong>shooting test</strong> for dedicated sport shooter.</li>
<li><strong>Exception:</strong> Members who already hold a dedicated hunter certificate from another accredited association, a CHASA certificate, or a Professional Hunter certificate do not need to complete the course to apply for dedicated status with NRAPA.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Ongoing Activity Requirements</h2>
<p>A Dedicated Sport Shooter must take part in at least <strong>two dedicated activities on at least two separate occasions</strong> during the year. Typical qualifying activities include range practice, club or postal-style leagues, and sanctioned matches—recorded through the <strong>member portal</strong>:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Complete a course of fire at an <strong>official shooting range</strong> or recognised event; attach a signed scorecard, range receipt, or other evidence the portal requests.</li>
<li>Where a competition format allows only one official score per day per discipline, follow that rule when logging.</li>
<li><strong>NRAPA does not supply postal targets or scorecard packs.</strong> Use targets and courses provided by your range or club; upload proof through the portal.</li>
<li>Participation in a <strong>sport shooting event</strong> of any other SAPS-accredited association also qualifies when logged with appropriate evidence.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Annual Reporting</h2>
<p>All dedicated members must submit their <strong>Dedicated Activities Report</strong> to the NRAPA office before <strong>31 October</strong> annually. The report form is available on www.NRAPA.co.za.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Not-Active Report</h3>
<p>If a member is unable to participate in dedicated activities during the year, they must provide <strong>reasonable grounds</strong> in a Not-Active Report. Valid reasons may include illness, injury, travel, or other circumstances that genuinely prevented participation. The Not-Active Report must also be submitted before the annual deadline.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Consequences of Non-Compliance</h2>
<p><strong>Failure to submit any report (Activities Report or Not-Active Report) will INVALIDATE the member's dedicated status.</strong> This is not discretionary — the FCA requires it. Loss of dedicated status may result in the revocation of Section 16 licences and associated privileges. Members must treat the reporting deadline with the same seriousness as a licence renewal.</p>
HTML;
    }

    protected function contentPostalShooting(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">What Is Postal-style Shooting?</h2>
<p><strong>Postal-style shooting</strong> is a competition format where shooters fire a defined course of fire at their local range using targets supplied by the <strong>range or club</strong>, then compare results through a league or association. The name comes from the historical habit of mailing scorecards to a central scorer. <strong>NRAPA does not issue postal target sheets, PDFs, or printed packs for sale or download.</strong> If you take part in a club or range postal league, use that organiser's targets and rules.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">How Qualifying Activities Are Recorded</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Complete the course of fire at an <strong>official shooting range</strong> or as directed by a sanctioned event.</li>
<li>Keep evidence such as a <strong>signed scorecard</strong>, range receipt, match results, or range officer declaration, as the member portal prompts.</li>
<li>Log the outing as a <strong>dedicated activity in the NRAPA member portal</strong> and attach the requested proof.</li>
<li>Follow any one-score-per-day-per-discipline rules set by the competition you entered.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Common Discipline Names (reference)</h2>
<p>The sport uses many named disciplines (examples below). Paper sizes and courses vary by organiser—this list is for orientation only:</p>
<ul class="list-disc pl-6 my-4 space-y-1">
<li>Dedicated Rifle</li>
<li>Dedicated Handgun</li>
<li>Air Rifle</li>
<li>Rimfire Rifle</li>
<li>Gallery Rifle</li>
<li>Centre-Fire Rifle</li>
<li>Pistol Precision</li>
<li>Pistol Sporting</li>
<li>Revolver Precision</li>
<li>Revolver Sporting</li>
</ul>
<p class="mt-4">Eye and ear protection are required for all live-fire disciplines. Firearms must be unloaded when moving between positions; actions must be open and magazines removed where applicable.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Postal-style Shooting and Dedicated Status</h2>
<p>A qualifying range day or sanctioned postal-style match can count as a <strong>dedicated activity</strong> under the FCA when you log it with proper evidence in the portal. The important part is <strong>lawful participation</strong> and <strong>documentation</strong>, not whether NRAPA supplied the paper target.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Why the Format Helps Shooters</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Structured practice</strong> — Formal courses of fire build accuracy and consistency.</li>
<li><strong>Fits dedicated activity reporting</strong> — Each qualifying outing logged in the portal supports your annual participation record.</li>
<li><strong>Often local</strong> — Many leagues let you shoot at a nearby range on your own schedule.</li>
<li><strong>Reinforces safety</strong> — Regular formal shooting reinforces range etiquette and safe gun handling.</li>
</ul>
HTML;
    }

    protected function contentAmmunitionLimits(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">Ammunition Possession Limits</h2>
<p>Under the Firearms Control Act, ammunition is defined as any complete centre-fire, rim-fire, or pin-fire cartridge, or primer. You may only be in possession of ammunition that is capable of being fired in your licensed firearm.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Standard Limits (Without Dedicated Status)</h3>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>200 rounds</strong> of ammunition per licensed firearm</li>
<li><strong>2,400 primers</strong> maximum, unless you have written permission from the Registrar</li>
<li><strong>2.4 kg (total)</strong> of nitrocellulose propellant for reloading purposes (per the Explosives Act)</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Dedicated Status Benefits</h3>
<p>Members with <strong>Dedicated Hunter or Sport Shooter status</strong> from an accredited association such as NRAPA are permitted higher ammunition limits to support regular practice, sport shooting, and handloading activities.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Reloading Restrictions</h3>
<p><strong>No person may reload ammunition for another person.</strong> Reloading (handloading) is permitted only for your own personal use with your own licensed firearms.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Loaning a Firearm</h3>
<p>You may let another person use a firearm licensed to you, but <strong>only while under your direct supervision</strong>, and only if:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>They are not prohibited by law from using firearms.</li>
<li>You have no reason to believe they may not safely handle firearms (e.g. intoxicated, drugs, mental health concerns).</li>
<li>The firearm is being used in and for a safe and lawful manner and purpose.</li>
</ul>

<h3 class="mt-5 mb-2 text-lg font-semibold">Safe Custody of Ammunition</h3>
<p>Store ammunition separately from firearms in a cool, dry place to prevent corrosion. Corroded ammunition can cause jamming, misfires, and other safety problems. Keep all ammunition away from flammable materials.</p>
HTML;
    }

    protected function contentLicenseTypes(): string
    {
        return <<<'HTML'
<p>The Firearms Control Act (FCA) establishes several categories of firearm licences, each with specific requirements, restrictions, and validity periods. Every sport shooter must understand the licence types, their conditions, and the legal obligations that come with firearm ownership.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Licence Types</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">Licence for Self-Defence (Section 13)</h3>
<p>A person may hold a licence for self-defence for a shotgun (not fully automatic or semi-automatic) or a handgun (not fully automatic). The applicant must demonstrate a <strong>need</strong> for self-defence and an inability to achieve adequate protection by other reasonable means. Only <strong>one</strong> self-defence licence is permitted per person. Valid for <strong>5 years</strong>.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Licence for Occasional Hunting / Sport Shooting (Section 15)</h3>
<p>Available for a handgun (not fully automatic), rifle, or shotgun (not semi-automatic or fully automatic). Any natural person who is an occasional hunter or sport shooter may hold a maximum of <strong>four</strong> licences in this category. Valid for <strong>10 years</strong>. The number of Section 15 licences permitted is reduced if the holder also holds licences in other categories.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Licence for Dedicated Hunting / Sport Shooting (Section 16)</h3>
<p>Available for a handgun, rifle, or shotgun (not fully automatic). Semi-automatic shotguns are permitted but limited to a maximum capacity of <strong>5 shots</strong>. The applicant must be a member of an <strong>accredited hunting or sport-shooting association</strong> (such as NRAPA) and must have completed the prescribed training course and assessment. Valid for <strong>10 years</strong>.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Licence in Private Collection (Section 17)</h3>
<p>Any firearm, including prohibited firearms, may be held in a private collection. The firearm must be approved by an <strong>accredited collectors association</strong>. The firearm must be at least <strong>50 years old</strong> and possess historical, cultural, artistic, or scientific value. Valid for <strong>10 years</strong>. Rigorous display and storage requirements apply — the collector must demonstrate adequate security measures for the collection.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Temporary Authorization</h3>
<p>A temporary authorization may be issued to any person, including aliens (non-South African citizens). The applicant must submit a <strong>written motivation</strong> for the intended use. The authorization is valid for a specific firearm, a specific period, and a specific use only.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Offences and Penalties</h2>
<p>Violation of the FCA or the terms of any licence is an offence. Penalties include fines or imprisonment ranging from <strong>2 to 25 years</strong> depending on the severity of the offence. Specific offences include:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Failure to lock a firearm in a prescribed safe is an offence.</li>
<li>Failure to report the loss or theft of a firearm is an offence.</li>
<li>Possession of an unlicensed firearm is an offence.</li>
<li>Supplying a firearm to an unlicensed person is an offence.</li>
</ul>

<h2 class="mt-8 mb-3 text-xl font-bold">Key Definitions</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Shoot</strong> — means to kill by means of a firearm only.</li>
<li><strong>Safekeeping</strong> — proper storage in a prescribed safe or strong room as required by the FCA.</li>
<li><strong>Devices not regarded as firearms</strong> — air gun, tranquiliser firearm, paintball gun, flare gun, deactivated firearm, antique firearm (manufactured before 1900 and not designed to fire modern ammunition), and captive bolt gun.</li>
<li><strong>Cartridge</strong> — a complete object consisting of a case, primer, propellant (powder charge), and bullet or shot. It is the complete round of ammunition loaded into a firearm.</li>
</ul>

<h2 class="mt-8 mb-3 text-xl font-bold">Period of Validity</h2>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Licence Type</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Validity Period</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Self-defence (Section 13)</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">5 years</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Restricted self-defence</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">2 years</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Occasional hunting / sport shooting (Section 15)</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">10 years</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Dedicated hunting / sport shooting (Section 16)</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">10 years</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Private collection (Section 17)</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">10 years</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Public collection</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">10 years</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Business — hunting</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">5 years</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Business — other</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">5 years</td>
</tr>
</tbody>
</table>
</div>

<h2 class="mt-8 mb-3 text-xl font-bold">Safe Custody Requirements</h2>
<p>All firearms must be stored in a <strong>SABS-compliant safe</strong> that meets the standards SANS 953-1 and SANS 953-2. The safe must be constructed of steel at least <strong>2 mm thick</strong>, have an integral locking mechanism, and be <strong>bolted to a wall or floor</strong> to prevent removal. The safe must be kept locked at all times when firearms are stored inside. Failure to comply with safe custody requirements is a criminal offence under the FCA.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Ammunition Limits</h2>
<p>Unless the holder has dedicated status or written permission from the Registrar, the following limits apply:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Maximum of <strong>200 rounds of ammunition per licensed firearm</strong>.</li>
<li>Maximum of <strong>2400 primers</strong> without dedicated status or Registrar permission.</li>
<li>Maximum of <strong>2.4 kg nitrocellulose propellant</strong> (smokeless powder) for reloading.</li>
<li>No person may reload ammunition for another person without the appropriate authorisation.</li>
</ul>
<p class="mt-3">Dedicated status holders are permitted higher ammunition limits for the purposes of sport shooting practice, competition, and handloading.</p>
HTML;
    }

    protected function contentPeriodValidity(): string
    {
        return <<<'HTML'
<p>Period of validity of license or permit:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Licence to possess a firearm for self-defense:</strong> Five years.</li>
<li><strong>Licence to possess a restricted firearm for self-defense:</strong> Two years.</li>
<li><strong>Licence to possess a firearm for occasional hunting/sport shooting:</strong> Ten years.</li>
</ul>
HTML;
    }

    protected function contentFourTypesSafeties(): string
    {
        return <<<'HTML'
<p>Four types of safeties are commonly found on firearms. Each operates differently, and every shooter must understand how the safety on their particular firearm works before handling it.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">1. Cross-Bolt Safety</h2>
<p>A <strong>cross-bolt safety</strong> is a button that slides from side to side, blocking the trigger mechanism when engaged. It is found on many pump-action and semi-automatic firearms. The cross-bolt is usually located behind the trigger in the trigger guard area. The shooter pushes the button to one side for "safe" and to the other side for "fire". The safe position is often indicated by a coloured band (typically red) that is hidden when the safety is engaged.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">2. Pivot Safety</h2>
<p>A <strong>pivot safety</strong> is a lever that pivots (rotates) to engage or disengage. It is commonly found on bolt-action rifles. The pivot safety is often located on the rear of the bolt or near the trigger. The shooter rotates it to the safe position to block the firing mechanism, and rotates it to the fire position when ready to shoot. Some pivot safeties have two or three positions (safe, fire, and bolt-lock).</p>

<h2 class="mt-6 mb-3 text-xl font-bold">3. Slide or Tang Safety</h2>
<p>A <strong>slide or tang safety</strong> is a sliding button usually located on the tang (the top rear) of the receiver, behind the action. It is common on shotguns, particularly pump-action and semi-automatic models. The shooter slides the button forward to fire and back to safe. Because it sits on top of the receiver, it is easy to operate with the thumb of the shooting hand without shifting grip.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">4. Half-Cock or Hammer Safety</h2>
<p>A <strong>half-cock or hammer safety</strong> holds the hammer in a half-cocked position, preventing it from striking the firing pin. This type of safety is found on older firearms and some lever-action rifles. It provides an intermediate position between fully cocked and at rest. Pulling the trigger while the hammer is at half-cock should not release the hammer, although this depends on the mechanical condition of the firearm.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Important Warning</h2>
<p>A safety is a <strong>mechanical device</strong> and, like any mechanical device, it can fail. A safety is <strong>not a substitute for safe gun handling</strong>. Never rely solely on a safety mechanism to prevent a discharge. Always follow the fundamental rules of firearm safety: keep the muzzle pointed in a safe direction, keep your finger off the trigger until ready to shoot, and keep the firearm unloaded until ready to use.</p>
HTML;
    }

    protected function contentFirearmComponents(): string
    {
        return <<<'HTML'
<p>Understanding the key components of a firearm is essential for safe handling, maintenance, and communication. The following are the most important parts every sport shooter must know.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Trigger Guard</h2>
<p>The <strong>trigger guard</strong> is the portion of a firearm that wraps around the trigger, providing both protection from accidental contact and safety. It prevents objects and fingers from inadvertently touching the trigger. The shooter's finger should remain outside the trigger guard at all times until the decision to fire has been made.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Breech</h2>
<p>The <strong>breech</strong> is the area containing the rear end of the barrel where the cartridge is inserted. This is where the cartridge is loaded and the firing mechanism engages. When the action is closed, the breech seals the rear of the barrel so that the expanding gases from the burning propellant are directed forward, propelling the bullet down the bore.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Muzzle</h2>
<p>The <strong>muzzle</strong> is the front end of the barrel where the projectile exits. The muzzle must always be pointed in a safe direction. Muzzle awareness — knowing where the front of the barrel is pointed at all times — is the foundation of safe gun handling. A safe direction is one where, even if an unintended discharge occurred, no person or property would be harmed.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Cylinder</h2>
<p>The <strong>cylinder</strong> is the part of a revolver that holds cartridges in separate chambers arranged in a circle. The cylinder rotates to align each chamber with the barrel for firing. Most revolver cylinders hold five or six rounds. When loading or unloading a revolver, the cylinder swings out or the loading gate is opened to access the chambers.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Trigger</h2>
<p>The <strong>trigger</strong> is the lever pulled or squeezed to initiate the firing process. The shooter's finger should <strong>always</strong> remain outside the trigger guard until they are ready to fire. Proper trigger control — a smooth, steady squeeze — is one of the most important fundamentals of accurate shooting.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Hammer</h2>
<p>The <strong>hammer</strong> is the part that strikes the firing pin or cartridge primer directly, initiating the firing sequence. The hammer can be external (visible on the outside of the firearm) or internal (concealed within the action). On some firearms, pulling the trigger both cocks and releases the hammer (double action), while on others the hammer must be cocked manually or by the action cycling (single action).</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Magazine</h2>
<p>A <strong>magazine</strong> is a spring-operated container that holds cartridges for a repeating firearm. Magazines can be detachable (removed from the firearm for loading) or fixed (integral to the firearm, loaded from the top via stripper clips or individual rounds). A magazine is different from a "clip" — a clip is a device that holds cartridges together to facilitate loading into a magazine, not a magazine itself.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Grip</h2>
<p>The <strong>grip</strong> is the portion of a handgun used to hold the firearm. It should fit the shooter's hand comfortably for proper control and consistent shot placement. On rifles and shotguns, this area is called the "wrist" of the stock. Proper grip is fundamental to recoil management and accuracy.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Bore</h2>
<p>The <strong>bore</strong> is the inside of the gun's barrel through which the projectile travels when fired. In rifles and handguns, the bore has spiral grooves called <strong>rifling</strong> that spin the bullet for gyroscopic stability and accuracy. In shotguns, the bore is smooth (no rifling). The calibre or gauge of a firearm is measured from the bore diameter. Keeping the bore clean and free of obstructions is critical for safety and accuracy.</p>
HTML;
    }

    protected function contentShotgunParts(): string
    {
        return <<<'HTML'
<h2 class="mt-4 mb-3 text-xl font-bold">Three Major Parts of a Shotgun</h2>
<p>Like all firearms, a shotgun consists of three major parts:</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Action (Lock)</h3>
<p>The action is the mechanism that loads, fires, and ejects cartridges. It houses the firing mechanism including the trigger, hammer or striker, firing pin, and safety device. The action is the operational heart of the shotgun.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Stock</h3>
<p>The stock supports the action and barrel, and provides the mounting surface against the shooter's shoulder. It allows the shooter to aim, control recoil, and fire the shotgun accurately. Stocks may be made of wood (traditional) or synthetic material (modern, weather-resistant). The stock includes the butt (shoulder end), the wrist (grip area), and the forend (forward section used by the support hand).</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Barrel</h3>
<p>The barrel is the tube through which shot travels. Shotgun barrels are <strong>smooth inside</strong> (no rifling), allowing the shot charge and wad to glide through without friction. The barrel may have a <strong>choke</strong> at the muzzle end — a constriction that controls the spread of the shot pattern. A tighter choke produces a narrower spread for longer range; a more open choke produces a wider spread for closer targets.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Four Shotgun Action Types</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">Pump-Action</h3>
<p>Operated by sliding the forend back and forth to cycle the action — ejecting the spent shell and chambering a new one. Pump-action shotguns are reliable, versatile, and commonly used for hunting, sport shooting, and home defence. They require manual operation for each shot.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Semi-Automatic</h3>
<p>Uses gas pressure or recoil energy from the fired shell to automatically cycle the action. After each shot, the action opens, ejects the spent shell, and chambers a fresh round without manual operation. Semi-automatics offer reduced felt recoil and faster follow-up shots compared to pump-action shotguns.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Bolt Action</h3>
<p>Operated by lifting and pulling back the bolt handle to eject the spent case, then pushing it forward to chamber a new round. Bolt-action shotguns are less common than other types, but are used in some slug guns and specialised applications where accuracy at longer range is required.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">Hinge / Break Action</h3>
<p>The barrel hinges open at the breech for loading and unloading. Break-action shotguns are available as single-barrel, side-by-side (two barrels next to each other), or over-under (two barrels stacked vertically). They are simple in design, reliable, and easy to verify whether the firearm is loaded or unloaded — simply open the action and visually inspect the chamber(s).</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Differences Between Rifles, Shotguns, and Handguns</h2>
<p>The main differences between rifles, shotguns, and handguns are their barrels and the type of ammunition they use:</p>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Feature</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Rifle</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Shotgun</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Handgun</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Barrel</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Long, rifled (spiral grooves)</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Long, smooth bore</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Short, rifled</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Ammunition</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Single projectile (bullet)</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Multiple projectiles (shot) or single slug</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Single projectile (bullet), various calibres</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Effective Range</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Long range, high accuracy</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Shorter range</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Short to medium range</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Handling</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Shoulder-fired, two hands</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Shoulder-fired, two hands</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Designed for one or two hands</td>
</tr>
</tbody>
</table>
</div>

<h2 class="mt-8 mb-3 text-xl font-bold">Two Common Shotgun Safeties</h2>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Tang Safety</strong> — A sliding button located on the top rear of the receiver (the tang). Slide forward to fire, back to safe. Easy to operate with the thumb of the shooting hand.</li>
<li><strong>Crossbolt Safety</strong> — A button located behind the trigger guard that slides from side to side. Push to one side for safe, the other for fire. Common on pump-action and semi-automatic shotguns.</li>
</ul>
HTML;
    }

    protected function contentHandgunActions(): string
    {
        return <<<'HTML'
<p>Handgun actions are classified by how the trigger interacts with the hammer or striker. Understanding the difference is essential for safe handling and effective shooting.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Single Action (SA)</h2>
<p>In a <strong>single-action</strong> handgun, the trigger performs only <strong>one action</strong> — releasing the hammer (or striker). The hammer must be manually cocked before each shot. In single-action revolvers, the shooter thumbs the hammer back before each shot. In single-action semi-automatic pistols (such as the 1911), the slide cocks the hammer when it cycles after the first shot.</p>
<p class="mt-3">Single-action triggers typically have a <strong>lighter, shorter trigger pull</strong> because the trigger only needs to release the already-cocked hammer. This can contribute to better accuracy. Found in: 1911-style pistols, single-action revolvers (e.g. Ruger Vaquero).</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Double Action (DA)</h2>
<p>In a <strong>double-action</strong> handgun, the trigger performs <strong>two actions</strong> — cocking the hammer <strong>and</strong> releasing it in one continuous pull. This results in a longer, heavier trigger pull since the trigger is doing more mechanical work. The advantage is that the shooter can fire simply by pulling the trigger without first manually cocking the hammer.</p>
<p class="mt-3">Found in: many modern revolvers (e.g. Smith &amp; Wesson), double-action-only (DAO) semi-automatic pistols. Double-action revolvers can also be fired in single-action mode by manually cocking the hammer first.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">DA/SA (Double Action / Single Action)</h2>
<p>A <strong>DA/SA</strong> handgun combines both mechanisms. The <strong>first shot is double action</strong> — the trigger cocks and releases the hammer in a long, heavy pull. After the first shot, the slide cycles and cocks the hammer, so all <strong>subsequent shots are single action</strong> — a shorter, lighter trigger pull.</p>
<p class="mt-3">This is common in many modern semi-automatic pistols (e.g. Beretta 92, SIG Sauer P226). The shooter must be comfortable transitioning between the heavier first pull and the lighter subsequent pulls.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Summary</h2>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Type</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Trigger Action</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Trigger Pull</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Examples</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Single Action</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Releases hammer only</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Light, short</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">1911, SA revolvers</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Double Action</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Cocks and releases hammer</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Heavy, long</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">DA revolvers, DAO pistols</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">DA/SA</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">First shot DA, subsequent SA</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Heavy first, light after</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Beretta 92, SIG P226</td>
</tr>
</tbody>
</table>
</div>
HTML;
    }

    protected function contentCartridgeMalfunctions(): string
    {
        return <<<'HTML'
<p>Three typical cartridge malfunctions can occur when firing a firearm. Each requires a specific, disciplined response to prevent injury. Understanding these malfunctions and the correct safety procedures is critical for every shooter.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">1. Misfire</h2>
<p>A <strong>misfire</strong> occurs when a cartridge does not fire when the trigger is pulled and the firing pin strikes the primer. The cartridge simply fails to discharge.</p>
<p class="mt-3"><strong>Common causes:</strong> defective or contaminated primer, contaminated or degraded propellant powder, improper cartridge seating in the chamber, weak firing pin strike, or ammunition that has been exposed to moisture or oil.</p>
<p class="mt-3"><strong>Safety procedure:</strong></p>
<ol class="list-decimal pl-6 my-3 space-y-1">
<li>Keep the firearm pointed in a safe direction (downrange).</li>
<li>Wait at least <strong>30 seconds</strong> before taking any further action. This waiting period is critical because what appears to be a misfire could actually be a hangfire (delayed ignition) — the round may still fire.</li>
<li>Do <strong>not</strong> immediately open the action.</li>
<li>After waiting, carefully unload the firearm and safely dispose of the defective cartridge.</li>
</ol>

<h2 class="mt-6 mb-3 text-xl font-bold">2. Hangfire</h2>
<p>A <strong>hangfire</strong> is a noticeable delay between pulling the trigger and the cartridge firing. The firing pin strikes the primer, but there is a perceptible pause — which can range from a fraction of a second to several seconds — before the propellant ignites and the round discharges.</p>
<p class="mt-3">Hangfires are <strong>very dangerous</strong> because the shooter may believe the round is a misfire and begin to open the action or lower the firearm, only to have the round fire unexpectedly.</p>
<p class="mt-3"><strong>Safety procedure:</strong></p>
<ol class="list-decimal pl-6 my-3 space-y-1">
<li>Keep the firearm pointed downrange in a safe direction.</li>
<li>Do <strong>not</strong> open the action, do <strong>not</strong> look down the barrel, and do <strong>not</strong> move the firearm from its firing position.</li>
<li>Wait at least <strong>30 seconds</strong> (the round may still fire during this period).</li>
<li>After waiting, carefully unload the firearm and dispose of the cartridge safely.</li>
</ol>

<h2 class="mt-6 mb-3 text-xl font-bold">3. Squib Load</h2>
<p>A <strong>squib load</strong> (also called a squib round) occurs when a cartridge fires with significantly less power than normal, often only pushing the bullet partway down the barrel. The bullet becomes lodged (obstructing the bore) instead of exiting the muzzle.</p>
<p class="mt-3"><strong>Signs of a squib load:</strong> noticeably reduced recoil, an unusual or quieter-than-normal sound (a "pop" instead of a "bang"), and the sensation that something is not right with the shot.</p>
<p class="mt-3"><strong>Safety procedure:</strong></p>
<ol class="list-decimal pl-6 my-3 space-y-1">
<li><strong>STOP SHOOTING IMMEDIATELY.</strong> Do not fire another round.</li>
<li>If a subsequent round is fired with a bullet lodged in the barrel, the barrel can burst — causing <strong>catastrophic failure</strong> that can result in serious injury or death.</li>
<li>Unload the firearm completely.</li>
<li>Check the barrel for an obstruction before continuing to shoot. Use a cleaning rod from the breech end to verify the barrel is clear.</li>
<li>If a bullet is lodged in the barrel, have it removed by a competent person or gunsmith.</li>
</ol>

<h2 class="mt-8 mb-3 text-xl font-bold">General Rule for All Malfunctions</h2>
<p>In every malfunction scenario, the first priority is to keep the firearm pointed in a safe direction. Never rush to diagnose or clear a malfunction — patience prevents accidents. When in doubt, keep the muzzle pointed downrange, wait, and then carefully unload.</p>
HTML;
    }

    protected function contentBasicPartsBullet(): string
    {
        return <<<'HTML'
<p>A bullet (the projectile component of a cartridge) has four basic parts. Understanding these parts helps the shooter select the right bullet for the intended purpose and understand how bullet design affects performance.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Four Basic Parts of a Bullet</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. The Base</h3>
<p>The <strong>base</strong> is the bottom (rear) of the bullet. It can be flat or have a slight concavity known as a <strong>boat-tail base</strong>. The base design affects aerodynamics and gas seal. A boat-tail base tapers inward, reducing aerodynamic drag at the rear of the bullet and improving long-range performance. A flat base provides a better gas seal and can be more accurate at shorter distances.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. The Shank</h3>
<p>The <strong>shank</strong> is the main cylindrical body of the bullet between the base and the ogive. The shank engages the rifling grooves in the barrel, which spin the bullet for gyroscopic stability in flight. The diameter of the shank determines the calibre of the bullet and must match the bore diameter precisely for safe and accurate performance.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. The Ogive</h3>
<p>The <strong>ogive</strong> (pronounced "oh-jive") is the curved portion of the bullet from the end of the shank to the tip (meplat). The shape of the ogive affects aerodynamic drag and the ballistic coefficient (BC) of the bullet. A more streamlined, elongated ogive reduces drag and improves long-range performance. A shorter, blunter ogive increases drag but may improve terminal performance at shorter ranges.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">4. The Meplat</h3>
<p>The <strong>meplat</strong> is the tip or point of the bullet. It can be pointed (spitzer), flat, or have a hollow point. The meplat shape affects both aerodynamics and terminal performance — how the bullet behaves on impact. A pointed meplat is more aerodynamic; a hollow-point meplat is designed to expand on impact for maximum energy transfer.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Five General Shapes of Hunting Bullets</h2>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Shape</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Characteristics</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Flat Point</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Flat tip. Good for use in dense brush. Feeds reliably in tubular magazines (lever-action rifles). Limited long-range performance due to higher drag.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Semi-Spitzer</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Slightly rounded point. A compromise between aerodynamics and terminal performance. Versatile for medium-range hunting.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Spitzer</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Pointed tip. Best aerodynamics for long-range shooting. Maintains velocity and energy better over distance. The most common hunting bullet shape for open-country hunting.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Boat-Tail Spitzer</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Pointed tip with a tapered base. Reduces base drag for the best long-range accuracy and flattest trajectory. Preferred for precision and long-range hunting.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Round Nose</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Rounded tip. Reliable feeding in all action types. Good brush performance (less deflection by twigs and branches). Higher drag limits long-range effectiveness.</td>
</tr>
</tbody>
</table>
</div>

<h2 class="mt-8 mb-3 text-xl font-bold">Common Handgun Bullet Types</h2>
<div class="my-4 overflow-x-auto">
<table class="w-full text-sm border-collapse border border-zinc-300 dark:border-zinc-600">
<thead>
<tr class="bg-zinc-100 dark:bg-zinc-700">
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Type</th>
<th class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 text-left font-bold">Description and Use</th>
</tr>
</thead>
<tbody>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Wadcutter</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Flat-faced, cylindrical bullet. Primarily used for target shooting because it cuts clean, round holes in paper targets for easy scoring.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Lead Hollow Point</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Exposed lead bullet with a hollow cavity in the tip. Designed to expand on impact for increased energy transfer.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Lead Point</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">General-purpose lead bullet with an exposed lead tip. Used for practice and general shooting.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Full Metal Jacket (FMJ)</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Lead core fully enclosed in a harder metal (copper or brass). Used for practice and training. Does not expand on impact. Feeds reliably in semi-automatic pistols.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Soft Point</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Lead core with a partial metal jacket exposing the lead tip. Designed for controlled expansion on impact.</td>
</tr>
<tr>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2 font-semibold">Hollow Point</td>
<td class="border border-zinc-300 dark:border-zinc-600 px-3 py-2">Jacketed bullet with a hollow cavity in the tip. Designed for maximum expansion on impact for self-defence applications. Transfers energy rapidly and reduces over-penetration risk.</td>
</tr>
</tbody>
</table>
</div>
HTML;
    }

    protected function contentBallisticsTerms(): string
    {
        return <<<'HTML'
<p>Ballistics is fundamental to understanding how a bullet behaves from the moment it leaves the barrel until it reaches (or misses) the target. The following terms are essential knowledge for every sport shooter.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Trajectory</h2>
<p><strong>Trajectory</strong> is the curve a projectile describes in space. A bullet does <strong>not</strong> travel in a straight line — it follows a parabolic arc, influenced by gravity pulling it downward and air resistance slowing it down from the moment it exits the muzzle. The trajectory determines where the bullet will strike relative to the line of sight at any given distance. Understanding trajectory is essential for accurate shot placement at varying distances.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Ballistics</h2>
<p><strong>Ballistics</strong> is the study of the path of projectiles, particularly those shot from firearms. There are three branches of ballistics:</p>
<ul class="list-disc pl-6 my-3 space-y-1">
<li><strong>Internal ballistics</strong> — what happens inside the barrel (ignition, pressure, bullet acceleration, engagement with rifling).</li>
<li><strong>External ballistics</strong> — what happens during the bullet's flight through the air (trajectory, drag, wind drift, gravity drop).</li>
<li><strong>Terminal ballistics</strong> — what happens when the bullet strikes the target (penetration, expansion, energy transfer).</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Projectile</h2>
<p>A <strong>projectile</strong> is an object set in motion by an exterior force and continuing under its own inertia. In firearms terminology, the bullet is the projectile — it is the component of the cartridge that is propelled down the barrel and travels to the target. Once the bullet leaves the muzzle, it is subject only to gravity, air resistance, and its own rotational stability.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Gravity</h2>
<p><strong>Gravity</strong> is the force pulling the bullet downward toward the earth. Without gravity, a bullet would travel in a straight line indefinitely until it struck something. Gravity begins affecting the bullet the instant it leaves the barrel — the bullet starts to drop from the moment of exit. This is why firearm sights are adjusted so that the barrel actually points slightly upward relative to the line of sight, allowing the bullet's arc to intersect the point of aim at a specific distance (the sight-in range).</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Air Resistance (Drag)</h2>
<p><strong>Air resistance</strong> (also called drag) is the force opposing the bullet's motion through the air. Without air resistance, a bullet would not lose velocity during flight. Air resistance slows the bullet progressively, reducing its energy and causing the trajectory to steepen as the bullet decelerates. Streamlined bullet shapes (spitzer, boat-tail) reduce drag, maintaining velocity and flattening trajectory over longer distances. The ballistic coefficient (BC) is a measure of how well a bullet overcomes air resistance.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Twist</h2>
<p><strong>Twist</strong> refers to the distance a bullet travels in the barrel while making one complete revolution (360-degree spin). It is expressed as a ratio — for example, 1:10 means the bullet completes one full revolution for every 10 inches of barrel length. The spiral grooves (<strong>rifling</strong>) cut into the bore of the barrel impart this spin to the bullet.</p>
<p class="mt-3">The twist rate must match the bullet's weight and length for proper stabilisation. Heavier or longer bullets generally require a faster twist rate (e.g. 1:8) to achieve stability, while lighter or shorter bullets can be stabilised by a slower twist rate (e.g. 1:12). An improperly stabilised bullet will tumble in flight, resulting in poor accuracy and unpredictable performance.</p>
HTML;
    }

    protected function contentProhibitedRange(): string
    {
        return <<<'HTML'
<p>Certain firearms, ammunition, and devices are prohibited from use on shooting ranges. Every shooter must know and comply with these restrictions.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Prohibited Firearms and Ammunition</h2>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Tracer Ammunition</h3>
<p><strong>Tracer ammunition</strong> produces a visible trail of light, allowing the shooter to see the bullet's path in flight. Tracer rounds contain a pyrotechnic compound in the base that ignites when fired. Tracer ammunition is banned from shooting ranges because it presents a serious <strong>fire hazard</strong> (the burning compound can ignite dry grass, backstop material, or structures), it is a <strong>distraction to other shooters</strong>, and it can <strong>damage range equipment</strong> and infrastructure.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Fully Automatic Firearms</h3>
<p><strong>Fully automatic firearms</strong> may not be fired on full automatic on the range. Semi-automatic fire only is permitted. The reason is safety — fully automatic fire significantly reduces the shooter's control over the firearm, increasing the risk of rounds leaving the designated range area or striking unintended targets. Controlled, aimed semi-automatic fire is the only acceptable mode on a shooting range.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Explosive Devices</h3>
<p>Any gun, cannon, recoilless gun, mortar, light mortar, or launcher manufactured to fire a rocket, grenade, self-propelled grenade, bomb, or explosive device may <strong>not</strong> be fired on the range. These devices produce blast effects, fragmentation, and overpressure that are incompatible with the safety design of standard shooting ranges.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Three Basic Categories of Shooting Ranges</h2>
<p>Shooting ranges in South Africa fall into three basic categories, each with different design and safety requirements:</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Indoor Ranges</h3>
<p>Fully enclosed ranges, typically designed for handguns and rimfire rifles. Indoor ranges are climate-controlled and contain noise within the structure. They require adequate ventilation systems to remove lead particles and propellant fumes from the air. Backstops are engineered to capture all projectiles safely.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Outdoor No-Danger-Area Ranges</h3>
<p>Outdoor ranges that have an adequate backstop (such as an earthen berm or hill) to capture all projectiles. There is no area beyond the backstop that could be endangered by firing. These ranges are designed so that all rounds are contained within the range boundaries regardless of trajectory.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Outdoor Danger-Area Ranges</h3>
<p>Outdoor ranges that have a defined danger area downrange where no public access is permitted during firing. These ranges may not have a complete backstop, so a safety zone (the danger area) must be established and controlled to ensure no persons enter the area while the range is active. Access control and range flags or signals are used to indicate when the range is live.</p>
HTML;
    }

    protected function contentHunterSportDefinitions(): string
    {
        return <<<'HTML'
<p>The following definitions are used in South African hunting and sport-shooting legislation and industry practice. Understanding these terms is essential for the knowledge test and for legal compliance.</p>

<h2 class="mt-6 mb-3 text-xl font-bold">Hunting Industry Roles</h2>
<ul class="list-disc pl-6 my-4 space-y-3">
<li><strong>Hunting Operator</strong> — A person who offers or organises the hunting of a wild animal or exotic animal for a fee. The operator is responsible for the logistics of the hunt, including accommodation, transport, and access to hunting areas.</li>
<li><strong>Professional Hunter (PH)</strong> — A suitably qualified and licensed person who may guide others (clients) for the purpose of hunting game, usually foreign hunters. To qualify as a PH, a person must be a South African citizen, be over 21 years of age, attend an accredited hunting school for a minimum of 10 days, and pass an examination set by nature conservation officials. One PH may guide a maximum of 2 clients at a time. If there are 3 or 4 hunters, 2 PHs are required. These requirements do not apply to bird hunting parties.</li>
<li><strong>Outfitter</strong> — A suitably licensed person who may arrange hunts for clients, usually for foreign hunters. An outfitter must hold an outfitter's permit issued by the relevant provincial authority. The outfitter coordinates the overall hunting experience but does not necessarily guide the client in the field personally.</li>
<li><strong>Client</strong> — A person not normally resident in the Republic of South Africa who pays or rewards any other person for, or in connection with, the hunting of game. It is <strong>illegal</strong> for anyone who is not a licensed Professional Hunter to guide a client, and it is <strong>illegal</strong> for anyone who is not a licensed outfitter to arrange a hunt for a client.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Trophy and Hunting Terms</h2>
<ul class="list-disc pl-6 my-4 space-y-3">
<li><strong>Trophy</strong> — Any mounted head or mounted skin of any game used or intended for private display or museum purposes, or any skin or portion thereof used in a processed or manufactured article.</li>
<li><strong>Exempted Farm</strong> — Land so fenced that certain species of game cannot enter or escape. Only animals listed on the exemption permit may be hunted without a hunting licence on such a farm. Hunting on exempted farms is governed by the conditions of the exemption permit.</li>
<li><strong>Night</strong> — The period from half an hour after sundown to half an hour before sunrise.</li>
<li><strong>Day</strong> — The period from half an hour before sunrise to half an hour after sunset.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">FCA Status Categories</h2>
<ul class="list-disc pl-6 my-4 space-y-3">
<li><strong>Dedicated Sports Person</strong> — A person who actively participates in sport-shooting and is a member of an accredited sports-shooting organisation. Dedicated status grants access to Section 16 licences and higher ammunition limits, but requires ongoing compliance with activity and reporting requirements.</li>
<li><strong>Dedicated Hunter</strong> — A person who actively participates in hunting activities and is a member of an accredited hunting association. The same benefits and obligations as a Dedicated Sports Person apply, but in the context of hunting.</li>
<li><strong>Occasional Hunter</strong> — A person who from time to time participates in hunting activities but who is <strong>not</strong> a member of an accredited hunting association. Occasional hunters are limited to Section 15 licences (maximum of four) and standard ammunition limits.</li>
<li><strong>Occasional Sports Person</strong> — A person who from time to time participates in sport-shooting but who is <strong>not</strong> a member of an accredited sports-shooting organisation. The same limitations as an Occasional Hunter apply, in the context of sport shooting.</li>
<li><strong>Bona-Fide Hunter</strong> — A category that existed under the old Arms and Ammunition Act of 1969. Under that Act, a person applied directly to the SAPS for bona-fide hunter status. Once obtained, nothing further was required to maintain it. This category has been superseded by the current Firearms Control Act categories (Dedicated Hunter and Occasional Hunter). Persons who held bona-fide hunter status may need to convert to one of the current categories.</li>
</ul>

<h2 class="mt-6 mb-3 text-xl font-bold">Can You Lose Dedicated Status?</h2>
<p><strong>Yes.</strong> A person can lose their dedicated status in the following ways:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li>Failure to comply with the association's activity requirements (e.g. not completing the minimum dedicated activities per year).</li>
<li>Not maintaining membership of the accredited association.</li>
<li>The association revoking dedicated status through its internal disciplinary measures.</li>
<li>Failure to submit the annual Dedicated Activities Report before the deadline.</li>
</ul>
<p class="mt-4">Loss of dedicated status has serious consequences: Section 16 licences held under dedicated status may be revoked, and the member loses the associated ammunition and firearm privileges.</p>
HTML;
    }

    protected function contentShootingPositionsIncidents(): string
    {
        return <<<'HTML'
<h2 class="mt-6 mb-3 text-xl font-bold">Four Types of Shooting Incidents</h2>
<p>Shooting incidents can be grouped into four broad categories. Understanding these categories helps shooters and hunters identify risks and take preventive measures.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Human Error and/or Judgment Mistakes</h3>
<p>These incidents result from poor decision-making in the field. Common examples include failing to check the foreground and background before taking a shot, misjudging the distance to the target, shooting at movement or sound instead of positively identifying the target, and failing to account for other people in the area. Many hunting accidents occur because the shooter did not take the time to properly assess the situation before firing.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Safety Rule Violations</h3>
<p>These incidents occur when established safety rules are deliberately or negligently ignored. Examples include pointing the muzzle in an unsafe direction, carrying loaded firearms on vehicles, ignoring proper fence-crossing procedures (firearms must always be unloaded before crossing a fence or obstacle), transporting loaded firearms in vehicles or camps, and handling firearms while under the influence of alcohol or medication. Strict adherence to the fundamental safety rules prevents almost all incidents in this category.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Lack of Control of the Firearm</h3>
<p>These incidents lead to accidental or negligent discharges. They occur when a shooter stumbles or trips while carrying a loaded firearm, drops a firearm, handles a firearm carelessly (e.g. catching clothing or twigs on the trigger), or fails to keep a finger off the trigger until ready to fire. Proper carry techniques and muzzle awareness are essential to prevent these incidents.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">4. Equipment or Ammunition Failure</h3>
<p>These incidents are caused by mechanical malfunction or improper ammunition. Examples include an obstructed barrel (a bullet or debris lodged in the bore that causes a catastrophic failure when another round is fired), using improper ammunition (wrong calibre or damaged cartridges), a malfunctioning safety mechanism, and worn or damaged firearm components. Regular maintenance, inspection, and using only the correct ammunition for the firearm prevent most equipment-related incidents.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Four Standard Shooting Positions</h2>
<p>The four standard bolt-action rifle shooting positions, from least steady to most steady, are:</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">1. Standing (Offhand)</h3>
<p>The <strong>least steady</strong> of all four positions. The body and extended arm are positioned at approximately 90 degrees diagonally to the target. The feet are placed 30-40 cm apart, flat on the ground, with weight evenly distributed. The extended (support) arm holds the rifle from directly underneath the forestock — not from the side. The support arm should be braced against the body if possible, with the elbow resting against the hip or ribcage. The shooting hand grips the pistol grip firmly, the butt is pressed firmly into the shoulder pocket, and the cheek is pressed firmly against the comb of the butt for a consistent cheek weld. This position is used when there is no time or opportunity to assume a lower position.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">2. Kneeling</h3>
<p>With only one arm braced (the elbow resting on the forward knee), the kneeling position is <strong>less steady than prone and sitting</strong> but more steady than standing. It is a good choice when the shooter needs to get above low obstacles such as grass, scrub, or a low fence. The shooter kneels on one knee and sits back on the heel of the rear foot, placing the elbow of the support arm on the forward knee for stability.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">3. Sitting</h3>
<p>Both arms are supported by the legs, making the sitting position the <strong>next steadiest after prone</strong>. There are several variations: crossed legs (ankles crossed), crossed ankles (legs extended and crossed at the ankles), and open legs (legs spread apart with elbows resting inside the knees). In all variations, both elbows are braced against the inner sides of the knees or legs, creating a stable two-point support. This position is excellent for shooting across open terrain where prone is not practical.</p>

<h3 class="mt-5 mb-2 text-lg font-semibold">4. Prone</h3>
<p>The <strong>steadiest of all four positions</strong> and the easiest position in which to hold a rifle still. The shooter lies flat on the ground, facing the target, with the body angled slightly to the shooting side. Both elbows rest firmly on the ground. Because almost the entire body is supported by the ground, movement is minimised. The prone position is best for mastering the four fundamentals of marksmanship: aiming, breath control, trigger squeeze, and follow through.</p>

<h2 class="mt-8 mb-3 text-xl font-bold">Shooting Fundamentals</h2>
<p>Regardless of position, every accurate shot depends on these fundamentals:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Aiming</strong> — Pick a precise spot to aim at, not just the general area of the target. A vague aiming point produces vague results.</li>
<li><strong>Breath control</strong> — Breathe normally, then hold your breath at the natural respiratory pause (the moment between exhaling and inhaling) while taking the shot. Do not hold your breath for more than a few seconds, as the body begins to tremble.</li>
<li><strong>Trigger squeeze</strong> — Squeeze the trigger slowly, steadily, and smoothly straight to the rear. Do not slap, jerk, or snatch the trigger. The shot should "surprise" you slightly — if you anticipate the exact moment of firing, you are likely to flinch.</li>
<li><strong>Follow through</strong> — Continue squeezing the trigger after the shot breaks and keep your eyes on the target. Do not lift your head or move immediately after firing. Proper follow through ensures the rifle is still aligned with the target as the bullet leaves the barrel.</li>
</ul>

<h2 class="mt-8 mb-3 text-xl font-bold">Using Natural and Artificial Supports</h2>
<p>Whenever possible, use available supports to steady the rifle and improve accuracy:</p>
<ul class="list-disc pl-6 my-4 space-y-2">
<li><strong>Trees</strong> — Lean against a tree trunk or rest the forestock on a branch. Place your hand between the rifle and the hard surface to dampen vibration.</li>
<li><strong>Mounds, stumps, and rocks</strong> — Rest the forestock on any solid, stable surface. Never rest the barrel directly on a hard surface, as this changes the point of impact.</li>
<li><strong>Backpacks and daypacks</strong> — A filled backpack makes an excellent field rest, especially in the prone or sitting position.</li>
<li><strong>Shooting sticks</strong> — Purpose-built bipod or tripod sticks that support the rifle in standing or kneeling positions. Widely used in African hunting.</li>
<li><strong>Sling technique</strong> — Loop the arm through the sling behind the elbow, wrap the sling around the forearm, and grip the forestock. The sling tension locks the support arm to the rifle, significantly improving stability in the standing, kneeling, and sitting positions.</li>
</ul>
HTML;
    }
}
