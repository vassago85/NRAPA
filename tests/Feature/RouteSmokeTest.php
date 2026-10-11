<?php

use App\Models\AffiliatedClub;
use App\Models\Bullet;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\DocumentType;
use App\Models\EndorsementFirearm;
use App\Models\EndorsementRequest;
use App\Models\KnowledgeTest;
use App\Models\KnowledgeTestAttempt;
use App\Models\KnowledgeTestQuestion;
use App\Models\LadderTest;
use App\Models\LearningArticle;
use App\Models\LearningArticlePage;
use App\Models\LearningCategory;
use App\Models\LoadData;
use App\Models\MemberDocument;
use App\Models\MemberMessage;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\ShootingActivity;
use App\Models\TermsVersion;
use App\Models\User;
use App\Models\UserFirearm;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Requests every GET page as each role against a seeded dataset and fails on any server error.
 */
const SMOKE_SKIPPED_URI_PREFIXES = ['livewire', 'storage/', 'dev/', 'email/verify/', 'email/confirm/', 'api/', '_'];

function smokeSeed(): array
{
    Storage::fake('local');
    Storage::fake('public');

    $annual = MembershipType::create([
        'slug' => 'smoke-annual',
        'name' => 'Smoke Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 500,
        'renewal_price' => 400,
        'is_active' => true,
    ]);
    $dedicated = MembershipType::create([
        'slug' => 'smoke-dedicated',
        'name' => 'Smoke Dedicated',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'initial_price' => 900,
        'renewal_price' => 800,
        'upgrade_price' => 300,
        'allows_dedicated_status' => true,
        'dedicated_type' => 'both',
        'is_active' => true,
    ]);

    $terms = TermsVersion::create([
        'version' => '1.0',
        'title' => 'Terms v1.0',
        'is_active' => true,
        'html_content' => '<p>Terms</p>',
        'published_at' => now()->subMonth(),
    ]);

    $makeUser = function (string $role, string $email) use ($terms) {
        $user = User::factory()->create(['role' => $role, 'email' => $email, 'email_verified_at' => now()]);
        $user->termsAcceptances()->create(['terms_version_id' => $terms->id, 'accepted_at' => now()]);

        return $user;
    };

    $member = $makeUser(User::ROLE_MEMBER, 'member@smoke.test');
    $admin = $makeUser(User::ROLE_ADMIN, 'admin@smoke.test');
    $owner = $makeUser(User::ROLE_OWNER, 'owner@smoke.test');
    $developer = $makeUser(User::ROLE_DEVELOPER, 'developer@smoke.test');

    $membership = Membership::create([
        'user_id' => $member->id,
        'membership_type_id' => $dedicated->id,
        'status' => 'active',
        'source' => 'web',
        'applied_at' => now()->subMonths(2),
        'approved_at' => now()->subMonths(2),
        'activated_at' => now()->subMonths(2),
        'payment_confirmed_at' => now()->subMonths(2),
        'expires_at' => now()->addMonths(10),
    ]);

    // Applicants covering every billable/non-billable branch on the approvals and dashboard pages.
    foreach ([
        ['web', 'applied', null],
        ['admin', 'applied', 'proof-of-payment/smoke.pdf'],
        ['import', 'applied', null],
        ['transfer', 'applied', null],
        ['web', 'pending_payment', null],
        ['web', 'expired', null],
    ] as $i => [$source, $status, $pop]) {
        $applicant = $makeUser(User::ROLE_MEMBER, "applicant{$i}@smoke.test");
        Membership::create([
            'user_id' => $applicant->id,
            'membership_type_id' => $annual->id,
            'status' => $status,
            'source' => $source,
            'applied_at' => now()->subDays(3),
            'expires_at' => $status === 'expired' ? now()->subMonth() : null,
            'proof_of_payment_path' => $pop,
        ]);
    }
    Storage::disk('local')->put('proof-of-payment/smoke.pdf', '%PDF-1.4 pop');

    $thread = MemberMessage::create([
        'user_id' => $member->id,
        'sent_by_user_id' => $member->id,
        'direction' => MemberMessage::DIRECTION_MEMBER_TO_ADMIN,
        'subject' => 'Smoke question',
        'body' => 'Unread by admins',
    ]);
    MemberMessage::create([
        'user_id' => $member->id,
        'sent_by_user_id' => $admin->id,
        'direction' => MemberMessage::DIRECTION_ADMIN_TO_MEMBER,
        'parent_id' => $thread->id,
        'subject' => 'Smoke question',
        'body' => 'Unread by member',
    ]);

    Storage::disk('local')->put("licenses/{$member->id}/licence.pdf", '%PDF-1.4 licence');
    $firearm = UserFirearm::create([
        'user_id' => $member->id,
        'firearm_type' => 'rifle',
        'action' => 'bolt_action',
        'make' => 'Tikka',
        'model' => 'T3x',
        'serial_number' => 'SMK123',
        'license_number' => 'LIC-1',
        'license_expiry_date' => now()->addMonths(4),
        'license_document_path' => "licenses/{$member->id}/licence.pdf",
        'is_active' => true,
    ]);

    $certificateType = CertificateType::create([
        'slug' => 'membership-certificate',
        'name' => 'Membership Certificate',
        'template' => 'documents.certificates.good-standing',
        'validity_months' => 12,
        'is_active' => true,
        'sort_order' => 1,
    ]);
    Storage::disk('local')->put('certificates/smoke.pdf', '%PDF-1.4 certificate');
    $certificate = Certificate::create([
        'user_id' => $member->id,
        'membership_id' => $membership->id,
        'certificate_type_id' => $certificateType->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'file_path' => 'certificates/smoke.pdf',
    ]);

    Storage::disk('local')->put('member-documents/id.pdf', '%PDF-1.4 id');
    $document = MemberDocument::create([
        'user_id' => $member->id,
        'document_type_id' => DocumentType::create(['slug' => 'identity-document', 'name' => 'ID', 'is_active' => true, 'sort_order' => 1])->id,
        'file_path' => 'member-documents/id.pdf',
        'original_filename' => 'id.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 11,
        'status' => 'pending',
        'uploaded_at' => now(),
        'expires_at' => now()->addYears(2),
    ]);

    $endorsement = EndorsementRequest::create([
        'user_id' => $member->id,
        'request_type' => EndorsementRequest::TYPE_NEW,
        'endorsement_type' => EndorsementRequest::ENDORSEMENT_TYPE_DEDICATED_STATUS,
        'purpose' => EndorsementRequest::PURPOSE_SECTION_16,
        'status' => EndorsementRequest::STATUS_SUBMITTED,
        'submitted_at' => now()->subDay(),
        'letter_reference' => 'END-SMOKE-0001',
    ]);
    EndorsementFirearm::create([
        'endorsement_request_id' => $endorsement->id,
        'user_firearm_id' => $firearm->id,
        'firearm_category' => 'rifle',
        'action_type' => 'bolt_action',
        'make' => 'Tikka',
        'model' => 'T3x',
        'serial_number' => 'SMK123',
        'calibre_manual' => '.308 Win',
    ]);

    $activity = ShootingActivity::create([
        'user_id' => $member->id,
        'activity_date' => now()->subWeek(),
        'location' => 'Smoke Range',
        'status' => 'pending',
    ]);

    $knowledgeTest = KnowledgeTest::create([
        'name' => 'Smoke Knowledge Test',
        'slug' => 'smoke-knowledge-test',
        'description' => 'Smoke',
        'dedicated_type' => 'both',
        'is_active' => true,
    ]);
    KnowledgeTestQuestion::create([
        'knowledge_test_id' => $knowledgeTest->id,
        'question_type' => 'multiple_choice',
        'question_text' => 'Pick A',
        'options' => ['A', 'B'],
        'correct_answer' => 'A',
        'points' => 1,
        'sort_order' => 1,
        'is_active' => true,
    ]);
    $attempt = KnowledgeTestAttempt::create([
        'user_id' => $member->id,
        'knowledge_test_id' => $knowledgeTest->id,
        'started_at' => now()->subHour(),
        'submitted_at' => now()->subMinutes(30),
        'auto_score' => 1,
        'total_score' => 1,
        'passed' => true,
    ]);

    $category = LearningCategory::create(['name' => 'Smoke Category', 'slug' => 'smoke-category', 'dedicated_type' => 'both', 'sort_order' => 1]);
    $article = LearningArticle::create([
        'learning_category_id' => $category->id,
        'title' => 'Smoke Article',
        'slug' => 'smoke-article',
        'content' => '<p>Smoke</p>',
        'dedicated_type' => 'both',
        'sort_order' => 1,
        'is_published' => true,
    ]);
    LearningArticlePage::create(['learning_article_id' => $article->id, 'title' => 'Page 1', 'content' => '<p>One</p>', 'page_number' => 1]);

    $load = LoadData::create(['user_id' => $member->id, 'user_firearm_id' => $firearm->id, 'name' => 'Smoke Load', 'status' => 'development']);
    $ladder = LadderTest::create([
        'user_id' => $member->id,
        'load_data_id' => $load->id,
        'user_firearm_id' => $firearm->id,
        'name' => 'Smoke Ladder',
        'calibre' => '.308 Win',
        'test_type' => 'powder_charge',
        'start_charge' => 40,
        'end_charge' => 42,
        'increment' => 0.5,
        'rounds_per_step' => 3,
    ]);

    $bullet = Bullet::create([
        'manufacturer' => 'Smoke',
        'brand_line' => 'Line',
        'bullet_label' => 'Smoke 168gr',
        'caliber_label' => '.308',
        'weight_gr' => 168,
        'diameter_in' => 0.308,
        'diameter_mm' => 7.823,
        'construction' => 'cup_and_core',
        'intended_use' => 'match',
        'source_url' => 'https://example.test',
        'status' => 'active',
        'last_verified_at' => now(),
    ]);

    $club = AffiliatedClub::create([
        'name' => 'Smoke Club',
        'slug' => 'smoke-club',
        'dedicated_type' => 'sport',
        'initial_fee' => 300,
        'renewal_fee' => 250,
        'requires_competency' => true,
        'required_activities_per_year' => 2,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    return [
        'users' => compact('member', 'admin', 'owner', 'developer'),
        'params' => [
            'activity' => [$activity->getRouteKey()],
            'article' => [$article->getRouteKey()],
            'attempt' => [$attempt->getRouteKey()],
            'bullet' => [$bullet->getRouteKey()],
            'category' => [$category->getRouteKey()],
            'certificate' => [$certificate->getRouteKey()],
            'club' => [$club->getRouteKey()],
            'document' => [$document->getRouteKey()],
            'firearm' => [$firearm->getRouteKey()],
            'load' => [$load->getRouteKey()],
            'membership' => [$membership->getRouteKey()],
            'message' => [$thread->getRouteKey()],
            'qr_code' => [$certificate->fresh()->qr_code],
            'reference' => [$endorsement->letter_reference],
            'request' => [$endorsement->getRouteKey()],
            'knowledge_test' => [$knowledgeTest->getRouteKey()],
            'ladder_test' => [$ladder->getRouteKey()],
            'token' => ['smoke-token'],
            'type' => ['endorsement', 'good-standing', 'dedicated-status', 'welcome', 'membership-card'],
            'user' => [$member->getRouteKey()],
            'version' => [$terms->getRouteKey()],
        ],
    ];
}

/**
 * Expand a route URI into concrete URLs using the seeded route keys.
 */
function smokeUrls(string $uri, array $params): array
{
    $urls = [$uri];
    preg_match_all('/\{(\w+)\??\}/', $uri, $matches);

    foreach ($matches[1] as $name) {
        $key = $name === 'test'
            ? (str_starts_with($uri, 'load-data/') ? 'ladder_test' : 'knowledge_test')
            : $name;

        if (! isset($params[$key])) {
            throw new RuntimeException("No smoke value for route parameter {{$name}} in {$uri}");
        }

        $expanded = [];
        foreach ($urls as $url) {
            foreach ($params[$key] as $value) {
                $expanded[] = preg_replace('/\{'.$name.'\??\}/', (string) $value, $url, 1);
            }
        }
        $urls = $expanded;
    }

    return array_map(fn ($url) => '/'.ltrim($url, '/'), $urls);
}

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    Http::fake();
    $this->smoke = smokeSeed();
});

test('every GET page renders without a server error', function (string $role) {
    $user = $this->smoke['users'][$role] ?? null;
    $failures = [];
    $report = [];
    $visited = 0;
    $redirector = app('redirect');

    foreach (Route::getRoutes() as $route) {
        if (! in_array('GET', $route->methods(), true)) {
            continue;
        }
        $uri = $route->uri();
        if (collect(SMOKE_SKIPPED_URI_PREFIXES)->contains(fn ($prefix) => str_starts_with($uri, $prefix))) {
            continue;
        }

        foreach (smokeUrls($uri, $this->smoke['params']) as $url) {
            // Each production request runs in a fresh process. A Livewire page that aborts in mount() never
            // restores Laravel's redirector, so reset that and Livewire's static state between requests.
            app('livewire')->flushState();
            app()->instance('redirect', $redirector);
            app('auth')->forgetGuards();
            if ($user) {
                $this->actingAs($user->fresh());
            }

            $response = $this->get($url);
            $status = $response->getStatusCode();
            $visited++;
            $report[$url] = $status;

            if ($status >= 500) {
                $e = $response->exception;

                // Production runs MySQL; pages using MySQL-only SQL functions are covered by running this test there.
                if ($e && DB::getDriverName() === 'sqlite' && str_contains($e->getMessage(), 'no such function')) {
                    continue;
                }

                $failures[] = sprintf(
                    '%s -> %d %s',
                    $url,
                    $status,
                    $e ? get_class($e).': '.$e->getMessage().' @ '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $e->getFile()).':'.$e->getLine() : ''
                );
            }
        }
    }

    if ($reportDir = env('SMOKE_REPORT_DIR')) {
        file_put_contents("{$reportDir}/smoke-{$role}.json", json_encode($report, JSON_PRETTY_PRINT));
    }

    expect($visited)->toBeGreaterThan(100);
    expect($failures)->toBe([], implode(PHP_EOL, $failures));
})->with(['guest', 'member', 'admin', 'owner', 'developer']);
