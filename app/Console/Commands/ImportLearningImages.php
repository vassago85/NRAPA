<?php

namespace App\Console\Commands;

use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestQuestion;
use App\Models\LearningArticle;
use App\Models\LearningArticlePage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ImportLearningImages extends Command
{
    protected $signature = 'nrapa:import-learning-images {--dry-run : Preview changes without writing}';

    protected $description = 'Import images from public/learning_images/ into articles and quiz questions';

    protected int $updated = 0;

    protected int $skipped = 0;

    protected array $articleAliasMap = [
        'tracking-spoor-and-sign' => [
            'african-animal-tracks-spoor-identification',
        ],
    ];

    protected array $trackPageMap = [
        'q-track-leopard' => 'Leopard',
        'q-track-hippo' => 'Hippo',
        'q-track-rhino' => 'Rhino',
        'q-track-zebra' => 'Zebra',
        'q-track-warthog' => 'Warthog',
        'q-track-sitatunga' => 'Sitatunga',
    ];

    protected array $quizImageMap = [
        'q-crossing-fence' => 'Crossing a Fence',
        'q-shooting-positions' => 'shooting positions',
        'q-track-leopard' => 'Track A:',
        'q-track-hippo' => 'Track B:',
        'q-track-rhino' => 'Track C:',
        'q-track-zebra' => 'Track D:',
        'q-track-warthog' => 'Track E:',
        'q-track-sitatunga' => 'Track F:',
        'q-track-direction' => 'direction is the animal walking',
        'q-rifle-carry-techniques' => 'rifle carry techniques',
        'q-rifle-carry-fundamentals' => 'rifle carrying fundamentals',
        'q-firearm-components-match' => 'definition to the correct firearm component',
        'q-shotgun-handgun-components' => 'description to the correct firearm component',
        'q-types-of-shots' => 'Types of shots',
        'q-three-main-parts' => 'three MAIN parts of a firearm',
        'q-firearm-actions' => 'types of actions',
        'q-cleaning-firearm' => 'cleaning a firearm',
        'q-four-safeties' => 'types of safeties',
        'q-shotgun-parts' => 'major parts of a shotgun',
        'q-shotgun-actions' => 'actions found in shotguns',
        'q-handgun-actions' => 'types of actions used in sport shooting handguns',
        'q-cartridge-malfunctions' => 'cartridge malfunctions',
        'q-bullet-parts' => 'basic parts of a bullet',
        'q-bullet-shapes' => 'shapes of hunting bullets',
        'q-handgun-bullets' => 'common handgun bullets',
        'q-cartridge-components' => 'Cartridge consist of four',
        'q-shotgun-shell' => 'Shotgun shell consists',
        'q-shotgun-safeties' => 'common safeties in shotguns',
        'q-semi-auto-action' => 'recoil or',
        'q-shooting-ranges' => 'categories of shooting ranges',
        'q-survival-priorities' => 'survival priorities',
        'q-fire-making-kit' => 'fire making kit',
        'q-first-aid-terms' => 'first-aid term',
        'q-endangered-species' => 'endangered species categories',
    ];

    public function handle(): int
    {
        $sourcePath = public_path('learning_images');
        $dryRun = $this->option('dry-run');

        if (! File::isDirectory($sourcePath)) {
            $this->error('Directory public/learning_images/ does not exist.');

            return 1;
        }

        $files = collect(File::files($sourcePath))
            ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif']));

        $this->info("Found {$files->count()} image files. ".($dryRun ? '(DRY RUN)' : ''));
        $this->newLine();

        foreach ($files as $file) {
            $slug = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $extension = $file->getExtension();

            if (str_starts_with($slug, 'q-')) {
                $this->processQuizImage($file, $slug, $extension, $dryRun);
            } else {
                $this->processArticleImage($file, $slug, $extension, $dryRun);
            }
        }

        $this->newLine();
        $this->info("Done. Updated: {$this->updated}, Skipped: {$this->skipped}");

        return 0;
    }

    protected function processArticleImage($file, string $slug, string $ext, bool $dryRun): void
    {
        $article = LearningArticle::where('slug', $slug)->first();

        if (! $article) {
            $this->warn("  SKIP article slug not found: {$slug}");
            $this->skipped++;

            return;
        }

        $storagePath = "learning/articles/{$slug}.{$ext}";

        if ($dryRun) {
            $this->line("  [DRY] Would set featured_image on article '{$article->title}' -> {$storagePath}");
        } else {
            Storage::disk('public')->put($storagePath, File::get($file));
            $article->update(['featured_image' => $storagePath]);
            $this->info("  OK article '{$article->title}' -> {$storagePath}");
        }
        $this->updated++;

        if (isset($this->articleAliasMap[$slug])) {
            foreach ($this->articleAliasMap[$slug] as $aliasSlug) {
                $aliasArticle = LearningArticle::where('slug', $aliasSlug)->first();
                if (! $aliasArticle) {
                    $this->warn("  SKIP alias slug not found: {$aliasSlug}");
                    continue;
                }
                if ($dryRun) {
                    $this->line("  [DRY] Would also set featured_image on '{$aliasArticle->title}' -> {$storagePath}");
                } else {
                    $aliasArticle->update(['featured_image' => $storagePath]);
                    $this->info("  OK alias '{$aliasArticle->title}' -> {$storagePath}");
                }
                $this->updated++;
            }
        }
    }

    protected function processQuizImage($file, string $slug, string $ext, bool $dryRun): void
    {
        $matched = false;

        // Article sub-pages (track images)
        if (isset($this->trackPageMap[$slug])) {
            $matched = $this->assignTrackPageImage($file, $slug, $ext, $dryRun) || $matched;
        }

        // Track direction also goes as featured_image on the direction article
        if ($slug === 'q-track-direction') {
            $matched = $this->assignDirectionArticleImage($file, $ext, $dryRun) || $matched;
        }

        // Quiz questions
        if (isset($this->quizImageMap[$slug])) {
            $matched = $this->assignQuizQuestionImages($file, $slug, $ext, $dryRun) || $matched;
        }

        if (! $matched) {
            $this->warn("  SKIP no mapping for: {$slug}");
            $this->skipped++;
        }
    }

    protected function assignTrackPageImage($file, string $slug, string $ext, bool $dryRun): bool
    {
        $animalName = $this->trackPageMap[$slug];
        $article = LearningArticle::where('slug', 'african-animal-tracks-spoor-identification')->first();

        if (! $article) {
            $this->warn("  SKIP tracks article not found");

            return false;
        }

        $page = LearningArticlePage::where('learning_article_id', $article->id)
            ->where('title', 'LIKE', "%{$animalName}%")
            ->first();

        if (! $page) {
            $this->warn("  SKIP track page for '{$animalName}' not found");

            return false;
        }

        $storagePath = "learning/articles/{$slug}.{$ext}";

        if ($dryRun) {
            $this->line("  [DRY] Would set image on page '{$page->title}' -> {$storagePath}");
            $this->updated++;

            return true;
        }

        Storage::disk('public')->put($storagePath, File::get($file));
        $page->update(['image_path' => $storagePath]);
        $this->info("  OK page '{$page->title}' -> {$storagePath}");
        $this->updated++;

        return true;
    }

    protected function assignDirectionArticleImage($file, string $ext, bool $dryRun): bool
    {
        $article = LearningArticle::where('slug', 'direction-travel-tracks')->first();

        if (! $article) {
            return false;
        }

        $storagePath = "learning/articles/q-track-direction.{$ext}";

        if ($dryRun) {
            $this->line("  [DRY] Would set featured_image on '{$article->title}' -> {$storagePath}");
            $this->updated++;

            return true;
        }

        Storage::disk('public')->put($storagePath, File::get($file));
        $article->update(['featured_image' => $storagePath]);
        $this->info("  OK article '{$article->title}' featured -> {$storagePath}");
        $this->updated++;

        return true;
    }

    protected function assignQuizQuestionImages($file, string $slug, string $ext, bool $dryRun): bool
    {
        $pattern = $this->quizImageMap[$slug];
        $storagePath = "knowledge-test-images/{$slug}.{$ext}";

        $questions = KnowledgeTestQuestion::where('question_text', 'LIKE', "%{$pattern}%")->get();

        if ($questions->isEmpty()) {
            $this->warn("  SKIP no quiz questions match pattern: {$pattern}");

            return false;
        }

        if (! $dryRun) {
            Storage::disk('public')->put($storagePath, File::get($file));
        }

        foreach ($questions as $q) {
            $testName = $q->knowledgeTest?->title ?? 'Unknown';
            if ($dryRun) {
                $this->line("  [DRY] Would set image on Q{$q->sort_order} '{$testName}' -> {$storagePath}");
            } else {
                $q->update(['image_path' => $storagePath]);
                $this->info("  OK Q{$q->sort_order} '{$testName}' -> {$storagePath}");
            }
            $this->updated++;
        }

        return true;
    }
}
