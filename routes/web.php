<?php

use App\Helpers\StorageHelper;
use App\Http\Controllers\CertificateFileController;
use App\Http\Controllers\DocumentPreviewController;
use App\Http\Controllers\EmailPreferencesController;
use App\Http\Controllers\EndorsementLetterController;
use App\Http\Controllers\FirearmLicenceController;
use App\Http\Controllers\MemberDocumentPreviewController;
use App\Http\Controllers\SageOAuthController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Webhooks\MailgunWebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::post('/webhooks/mailgun', MailgunWebhookController::class)
    ->middleware('throttle:300,1')
    ->name('webhooks.mailgun');
Route::view('/terms-and-conditions', 'pages.terms')->name('terms-and-conditions');
Route::view('/privacy-policy', 'pages.privacy')->name('privacy-policy');

/*
 * Email unsubscribe (signed, no auth required).
 *
 * These routes are hit from the footer of non-essential email and from the
 * RFC 8058 List-Unsubscribe-Post header. They opt the user out of reminders,
 * chases, bulk admin messages, and newsletters only — transactional mail is
 * never suppressed.
 */
Route::get('/email/unsubscribe/{uuid}', [EmailPreferencesController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('email.unsubscribe');
Route::get('/email/resubscribe/{uuid}', [EmailPreferencesController::class, 'resubscribe'])
    ->middleware('signed')
    ->name('email.resubscribe');
Route::post('/email/list-unsubscribe/{uuid}', [EmailPreferencesController::class, 'listUnsubscribePost'])
    ->middleware(['signed', 'throttle:60,1'])
    ->name('email.list_unsubscribe');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Machine-readable agent/LLM pages (plain text/markdown summaries of public info)
// These expose only information that already appears on the public site.
Route::get('/llms.txt', function () {
    return response()
        ->view('agent.llms', [], 200)
        ->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('agent.llms');

Route::get('/about.md', function () {
    return response()
        ->view('agent.about-md', [], 200)
        ->header('Content-Type', 'text/markdown; charset=UTF-8');
})->name('agent.about-md');

Route::get('/dedicated-status.md', function () {
    return response()
        ->view('agent.dedicated-status-md', [], 200)
        ->header('Content-Type', 'text/markdown; charset=UTF-8');
})->name('agent.dedicated-status-md');

Route::get('/firearm-motivations.md', function () {
    return response()
        ->view('agent.firearm-motivations-md', [], 200)
        ->header('Content-Type', 'text/markdown; charset=UTF-8');
})->name('agent.firearm-motivations-md');

// RFC 9727 API Catalog. NRAPA does not expose a public agent-callable API;
// the catalog points agents to the public information surfaces instead.
Route::get('/.well-known/api-catalog', function () {
    $base = rtrim(config('app.url'), '/');

    $body = [
        'linkset' => [
            [
                'anchor' => $base.'/',
                'service-desc' => [
                    [
                        'href' => $base.'/llms.txt',
                        'type' => 'text/plain',
                        'title' => 'NRAPA public information overview for AI agents',
                    ],
                    [
                        'href' => $base.'/sitemap.xml',
                        'type' => 'application/xml',
                        'title' => 'NRAPA public sitemap',
                    ],
                ],
                'describedby' => [
                    [
                        'href' => $base.'/about.md',
                        'type' => 'text/markdown',
                        'title' => 'About NRAPA (markdown)',
                    ],
                    [
                        'href' => $base.'/dedicated-status.md',
                        'type' => 'text/markdown',
                        'title' => 'Dedicated sport shooter & hunter status (markdown)',
                    ],
                    [
                        'href' => $base.'/firearm-motivations.md',
                        'type' => 'text/markdown',
                        'title' => 'Firearm licence motivations (markdown)',
                    ],
                ],
            ],
        ],
        'comment' => 'NRAPA is a public information site, not an agent-callable API platform. '
            .'There is no public REST/GraphQL/MCP/OAuth surface. Public information is provided '
            .'as HTML pages, an XML sitemap, and plain markdown summaries. See /llms.txt for usage rules.',
    ];

    return response()
        ->json($body, 200, [
            'Cache-Control' => 'public, max-age=3600',
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        ->header('Content-Type', 'application/linkset+json; charset=UTF-8');
})->name('agent.api-catalog');

// Info / Resources pages (public). Each slug maps to pages/info/{slug}.blade.php.
Route::prefix('info')->name('info.')->group(function () {
    Route::view('/', 'pages.info.index')->name('index');

    foreach ([
        'about',
        'firearm-licence-process',
        'minimum-requirements',
        'dedicated-procedure',
        'shooting-exercises',
        'dedicated-sport-shooter-south-africa',
        'dedicated-hunter-south-africa',
        'how-to-get-dedicated-status-south-africa',
        'endorsements',
        'membership-benefits',
        'faq',
        'prs-rifle-equipment-selection',
        'transfer-your-membership',
        'dedicated-hunter-vs-sport-shooter',
        'dedicated-status-faq',
    ] as $page) {
        Route::view($page, "pages.info.{$page}")->name($page);
    }
});

// Site Guides (how-to pages). The hub and "How to sign up" are public;
// the member-action guides require the user to be logged in.
Route::prefix('guides')->name('guides.')->group(function () {
    Route::view('/', 'pages.guides.index')->name('index');
    Route::view('/sign-up', 'pages.guides.sign-up')->name('sign-up');

    Route::middleware(['auth'])->group(function () {
        Route::view('/upload-proof-of-payment', 'pages.guides.upload-proof-of-payment')->name('upload-proof-of-payment');
        Route::view('/submit-activities', 'pages.guides.submit-activities')->name('submit-activities');
        Route::view('/request-endorsement', 'pages.guides.request-endorsement')->name('request-endorsement');
    });
});

// Custom email verification route - works WITHOUT authentication
// This allows users to verify their email from any device/browser (e.g. phone)
// without needing to be logged in on that device.
Route::get('/email/confirm/{id}/{hash}', function (int $id, string $hash) {
    $user = \App\Models\User::findOrFail($id);

    // Verify the hash matches the user's email
    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Invalid verification link.');
    }

    // Mark email as verified if not already
    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new \Illuminate\Auth\Events\Verified($user));
    }

    // If the user is logged in on this device, redirect to dashboard
    if (auth()->check() && auth()->id() === $user->id) {
        return redirect()->route('dashboard')->with('success', 'Email address verified successfully.');
    }

    // Otherwise show a simple confirmation page (cross-device scenario)
    return view('pages.auth.email-confirmed');
})->middleware(['signed', 'throttle:6,1'])->name('verification.confirm');

// Dev/Test Quick Login Routes (only available in non-production)
if (app()->environment('local', 'development', 'testing')) {
    Route::get('/dev/login/{role}', function (string $role) {
        $validRoles = ['developer', 'owner', 'admin', 'member'];

        if (! in_array($role, $validRoles)) {
            abort(404);
        }

        // Find or create a test user for this role
        $user = \App\Models\User::where('email', "test-{$role}@nrapa.dev")->first();

        if (! $user) {
            $user = \App\Models\User::create([
                'name' => ucfirst($role).' Test User',
                'email' => "test-{$role}@nrapa.dev",
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'role' => $role,
                'id_number' => '000000000000'.array_search($role, $validRoles),
                'phone' => '0000000000',
                'date_of_birth' => now()->subYears(30),
                'physical_address' => 'Test Address',
                'postal_address' => 'Test Address',
            ]);

            // Create an active membership for the member test user
            if ($role === 'member') {
                $membershipType = \App\Models\MembershipType::where('is_active', true)->first();
                if ($membershipType) {
                    \App\Models\UserMembership::create([
                        'user_id' => $user->id,
                        'membership_type_id' => $membershipType->id,
                        'status' => 'active',
                        'starts_at' => now(),
                        'expires_at' => now()->addYear(),
                        'amount_paid' => $membershipType->price,
                    ]);
                }
            }
        }

        auth()->login($user);

        return redirect()->route($user->dashboardRoute());
    })->name('dev.login');
}

// Developer impersonation route (login as any user) - works in all environments but requires developer role
Route::middleware(['auth'])->group(function () {
    Route::get('/dev/impersonate/{user}', function (\App\Models\User $user) {
        // Only developers can impersonate
        if (! auth()->user()->isDeveloper()) {
            abort(403, 'Only developers can impersonate users.');
        }

        // Store original user ID in session to allow returning
        session(['impersonating_from' => auth()->id()]);

        auth()->login($user);

        return redirect()->route($user->dashboardRoute());
    })->name('dev.impersonate');

    Route::get('/dev/stop-impersonating', function () {
        $originalUserId = session('impersonating_from');

        if ($originalUserId) {
            $originalUser = \App\Models\User::find($originalUserId);
            if ($originalUser) {
                session()->forget('impersonating_from');
                auth()->login($originalUser);

                return redirect()->route('developer.dashboard')->with('success', 'Returned to your account.');
            }
        }

        return redirect()->route('dashboard');
    })->name('dev.stop-impersonating');

    // Toggle "View as Member" mode (for admin/owner/dev)
    Route::post('/toggle-member-view', function () {
        $user = auth()->user();

        // Only allow admin/owner/dev to toggle member view
        if (! $user->hasRoleLevel(\App\Models\User::ROLE_ADMIN)) {
            abort(403);
        }

        $nowViewingAsMember = ! session('view_as_member', false);
        session(['view_as_member' => $nowViewingAsMember]);

        if ($nowViewingAsMember) {
            return redirect()->route('dashboard')->with('success', 'Now viewing as member');
        }

        return redirect()->route($user->dashboardRoute())->with('success', "Back to {$user->role} view");
    })->name('toggle-member-view');
});

// Member Portal Routes - Available to all authenticated users (including free members)
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard - Always accessible
    // Terms & Conditions acceptance (must be accessible without terms middleware)
    Route::livewire('terms', 'pages::member.terms.accept')->name('terms.accept');

    Route::livewire('dashboard', 'pages::member.dashboard')->name('dashboard');

    // Messages inbox (available to all verified users, including expired members)
    Route::livewire('messages', 'pages::member.messages.index')->name('messages.index');
    Route::livewire('messages/new', 'pages::member.messages.create')->name('messages.create');
    Route::livewire('messages/{message}', 'pages::member.messages.show')->name('messages.show');

    // Membership - Always accessible (so free members can choose/pay for packages)
    Route::livewire('membership/select-package', 'pages::member.membership.select-package')->name('membership.select-package');
    Route::livewire('membership/payment/{membership}', 'pages::member.membership.payment')->name('membership.payment');
    Route::livewire('membership', 'pages::member.membership.index')->name('membership.index');
    Route::livewire('membership/apply', 'pages::member.membership.apply')->name('membership.apply');
    Route::livewire('membership/club-apply/{club}', 'pages::member.membership.club-apply')->name('membership.club-apply');
    Route::livewire('membership/{membership}', 'pages::member.membership.show')->name('membership.show');
});

// Member Portal Routes - Requires ACTIVE membership (paid members only).
// Staff bypass both membership.required and terms.accepted, so the file routes
// below (certificates, documents, endorsement letters) also serve admin/owner/developer.
Route::middleware(['auth', 'verified', 'membership.required', 'terms.accepted'])->group(function () {
    // Digital Membership Card (mobile-optimized)
    Route::livewire('card', 'pages::member.card')->name('card');

    // Certificates (members only - requires active membership)
    Route::livewire('certificates', 'pages::member.certificates.index')->name('certificates.index');
    Route::livewire('certificates/{certificate}', 'pages::member.certificates.show')->name('certificates.show');
    Route::get('certificates/{certificate}/wallet/apple', [CertificateFileController::class, 'appleWallet'])->name('certificates.wallet.apple');
    Route::get('certificates/{certificate}/wallet/google', [CertificateFileController::class, 'googleWallet'])->name('certificates.wallet.google');
    Route::get('certificates/{certificate}/download', [CertificateFileController::class, 'download'])->name('certificates.download');
    Route::get('certificates/{certificate}/preview', [CertificateFileController::class, 'preview'])->name('certificates.preview');

    // Knowledge Test
    Route::livewire('knowledge-test', 'pages::member.knowledge-test.index')->name('knowledge-test.index');
    Route::livewire('knowledge-test/{test}/take', 'pages::member.knowledge-test.take')->name('knowledge-test.take');
    Route::livewire('knowledge-test/{attempt}/results', 'pages::member.knowledge-test.results')->name('knowledge-test.results');

    // Activities
    Route::livewire('activities', 'pages::member.activities.index')->name('activities.index');
    Route::livewire('activities/submit', 'pages::member.activities.submit')->name('activities.submit');
    Route::livewire('activities/{activity}', 'pages::member.activities.show')->name('activities.show');
    Route::livewire('activities/{activity}/edit', 'pages::member.activities.edit')->name('activities.edit');

    // Virtual Safe (My Firearms)
    Route::livewire('armoury', 'pages::member.armoury.index')->name('armoury.index');
    Route::livewire('armoury/add', 'pages::member.armoury.create')->name('armoury.create');
    Route::livewire('armoury/{firearm}', 'pages::member.armoury.show')->name('armoury.show');
    Route::livewire('armoury/{firearm}/edit', 'pages::member.armoury.edit')->name('armoury.edit');
    Route::get('armoury/{firearm}/licence', FirearmLicenceController::class)->name('armoury.licence');

    // Virtual Loading Bench (Reloading)
    Route::livewire('load-data', 'pages::member.load-data.index')->name('load-data.index');
    Route::livewire('load-data/create', 'pages::member.load-data.create')->name('load-data.create');
    Route::livewire('load-data/inventory', 'pages::member.load-data.inventory')->name('load-data.inventory');
    Route::livewire('load-data/ladder-tests', 'pages::member.load-data.ladder-test.index')->name('ladder-test.index');
    Route::livewire('load-data/ladder-tests/create', 'pages::member.load-data.ladder-test.create')->name('ladder-test.create');
    Route::livewire('load-data/ladder-tests/{test}', 'pages::member.load-data.ladder-test.show')->name('ladder-test.show');
    Route::get('load-data/ladder-tests/{test}/labels', function (\App\Models\LadderTest $test) {
        if ($test->user_id !== auth()->id()) {
            abort(403);
        }
        $test->load('steps');

        if ($test->steps->isEmpty()) {
            abort(404, 'No steps found for this ladder test. Add steps before printing labels.');
        }

        $filename = 'ladder-labels-'.str_replace(' ', '-', strtolower($test->name)).'.pdf';
        $viewData = ['test' => $test, 'steps' => $test->steps];

        // Try Browsershot first, then DomPDF fallback
        $pdfContent = null;
        try {
            $pdfContent = \Spatie\LaravelPdf\Facades\Pdf::view('documents.ladder-test-label', $viewData)
                ->format('a4')
                ->portrait()
                ->base64();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Browsershot label PDF failed, trying DomPDF', [
                'test_id' => $test->id,
                'error' => $e->getMessage(),
            ]);
            try {
                $pdfContent = \Spatie\LaravelPdf\Facades\Pdf::view('documents.ladder-test-label', $viewData)
                    ->driver('dompdf')
                    ->format('a4')
                    ->portrait()
                    ->base64();
            } catch (\Throwable $e2) {
                \Illuminate\Support\Facades\Log::error('Both PDF engines failed for labels', [
                    'test_id' => $test->id,
                    'browsershot_error' => $e->getMessage(),
                    'dompdf_error' => $e2->getMessage(),
                ]);
                abort(500, 'PDF generation failed. Please contact support.');
            }
        }

        return response(base64_decode($pdfContent), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    })->name('ladder-test.labels');
    Route::livewire('load-data/{load}', 'pages::member.load-data.show')->name('load-data.show');
    Route::livewire('load-data/{load}/edit', 'pages::member.load-data.edit')->name('load-data.edit');

    // Documents
    Route::livewire('documents', 'pages::member.documents.index')->name('documents.index');
    Route::livewire('documents/{document}', 'pages::member.documents.show')->name('documents.show');
    Route::get('documents/{document}/preview', MemberDocumentPreviewController::class)->name('documents.preview');

    // Learning Center
    Route::livewire('learning', 'pages::member.learning.index')->name('learning.index');
    Route::livewire('learning/category/{category}', 'pages::member.learning.category')->name('learning.category');
    Route::livewire('learning/{article}', 'pages::member.learning.show')->name('learning.show');

    // Endorsement Letters
    Route::livewire('endorsements', 'pages::member.endorsements.index')->name('member.endorsements.index');
    Route::livewire('endorsements/create', 'pages::member.endorsements.create')->name('member.endorsements.create');
    // Self-defence supporting-letter variant (registered before the {request} catch-all)
    Route::livewire('endorsements/self-defence', 'pages::member.endorsements.self-defence')->name('member.endorsements.self-defence');
    Route::livewire('endorsements/{request}/edit', 'pages::member.endorsements.create')->name('member.endorsements.edit');
    Route::livewire('endorsements/{request}', 'pages::member.endorsements.show')->name('member.endorsements.show');
    Route::get('endorsements/{request}/preview', [EndorsementLetterController::class, 'preview'])->name('member.endorsements.preview');
    Route::get('endorsements/{request}/letter', [EndorsementLetterController::class, 'letter'])->name('member.endorsements.letter');
});

// User Deletion Requests (Owners, Developers, and Super Admins can review)
Route::middleware(['auth', 'verified', 'review.deletions'])->prefix('owner')->name('owner.')->group(function () {
    Route::livewire('deletion-requests', 'pages::owner.deletion-requests.index')->name('deletion-requests.index');
});

// Owner Routes (Owners and Developers can access)
Route::middleware(['auth', 'verified', 'owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::livewire('dashboard', 'pages::owner.dashboard')->name('dashboard');
    Route::livewire('admins', 'pages::owner.admins.index')->name('admins.index');

    // Owner Settings (Bank, Email, Storage, etc.)
    Route::livewire('settings', 'pages::owner.settings.index')->name('settings.index');
    Route::livewire('settings/email', 'pages::owner.settings.email')->name('settings.email');
    Route::livewire('settings/storage', 'pages::owner.settings.storage')->name('settings.storage');
    Route::livewire('settings/approvals', 'pages::owner.settings.approvals')->name('settings.approvals');
    Route::livewire('settings/documents', 'pages::owner.settings.documents')->name('settings.documents');
    Route::livewire('settings/backup', 'pages::owner.settings.backup')->name('settings.backup');
    Route::livewire('settings/sage', 'pages::owner.settings.sage')->name('settings.sage');
    Route::livewire('settings/invoice-ninja', 'pages::owner.settings.invoice-ninja')->name('settings.invoice-ninja');

    // Sage OAuth flow (redirect initiates from owner panel)
    Route::get('sage/redirect', [SageOAuthController::class, 'redirect'])->name('sage.redirect');
});

// Sage OAuth callback (outside owner prefix so the redirect URI is /sage/callback)
Route::middleware(['auth', 'verified', 'owner'])->get('sage/callback', [SageOAuthController::class, 'callback'])->name('sage.callback');

// Developer Routes (Developer only)
Route::middleware(['auth', 'verified', 'developer'])->prefix('developer')->name('developer.')->group(function () {
    Route::livewire('dashboard', 'pages::developer.dashboard')->name('dashboard');

    Route::livewire('owners', 'pages::developer.owners.index')->name('owners.index');
    Route::livewire('owners/nominate', 'pages::developer.owners.create')->name('owners.create');

    // Certificates (developer management page with table, filters, bulk actions)
    Route::livewire('certificates', 'pages::developer.certificates')->name('certificates.index');
    Route::livewire('certificates/{certificate}', 'pages::member.certificates.show')->name('certificates.show');

    Route::get('document-preview', [DocumentPreviewController::class, 'index'])->name('document-preview.index');
    Route::get('document-preview/{type}', [DocumentPreviewController::class, 'show'])->name('document-preview.show');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('dashboard', 'pages::admin.dashboard')->name('dashboard');

    // Admin inbox (threads with members, incoming member enquiries)
    Route::livewire('messages', 'pages::admin.messages.index')->name('messages.index');
    Route::livewire('messages/{message}', 'pages::admin.messages.show')->name('messages.show');

    Route::livewire('members', 'pages::admin.members.index')->name('members.index');
    Route::livewire('members/create', 'pages::admin.members.create')->name('members.create');
    Route::livewire('members/import-failures', 'pages::admin.members.import-failures')->name('members.import-failures');
    Route::get('members/download-template', function () {
        $importer = new \App\Services\ExcelMemberImporter();
        $tempPath = storage_path('app/temp/member_import_template.xlsx');
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($tempPath));
        $importer->generateTemplate($tempPath);
        return response()->download($tempPath, 'member_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    })->name('members.download-template');
    Route::livewire('members/{user}', 'pages::admin.members.show')->name('members.show');

    // Certificates (admin can view all certificates)
    Route::livewire('certificates', 'pages::member.certificates.index')->name('certificates.index');
    Route::livewire('certificates/{certificate}', 'pages::member.certificates.show')->name('certificates.show');

    // Membership Types Management
    Route::livewire('membership-types', 'pages::admin.membership-types.index')->name('membership-types.index');

    // Affiliated Clubs Management
    Route::livewire('affiliated-clubs', 'pages::admin.affiliated-clubs.index')->name('affiliated-clubs.index');

    Route::livewire('review', 'pages::admin.review.index')->name('review.index');
    Route::livewire('review/{approval}', 'pages::admin.review.index')->name('review.show');

    Route::livewire('approvals', 'pages::admin.approvals.index')->name('approvals.index');
    Route::livewire('approvals/{membership}', 'pages::admin.approvals.show')->name('approvals.show');

    // Proof of payment proxy (streams from R2 through the app to avoid signed-URL issues)
    Route::get('approvals/{membership}/proof-of-payment', function (\App\Models\Membership $membership) {
        if (! $membership->proof_of_payment_path) {
            abort(404, 'No proof of payment uploaded.');
        }

        $disk = StorageHelper::getPrivateDisk();

        if (! \Illuminate\Support\Facades\Storage::disk($disk)->exists($membership->proof_of_payment_path)) {
            abort(404, 'Proof of payment file not found.');
        }

        $ext = strtolower(pathinfo($membership->proof_of_payment_path, PATHINFO_EXTENSION));
        $contentType = match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };

        return response(\Illuminate\Support\Facades\Storage::disk($disk)->get($membership->proof_of_payment_path))
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'private, max-age=300');
    })->name('approvals.proof-of-payment');

    Route::livewire('settings', 'pages::admin.settings.index')->name('settings.index');

    // Knowledge Tests
    Route::livewire('knowledge-tests', 'pages::admin.knowledge-tests.index')->name('knowledge-tests.index');
    Route::livewire('knowledge-tests/create', 'pages::admin.knowledge-tests.create')->name('knowledge-tests.create');
    Route::livewire('knowledge-tests/{test}/edit', 'pages::admin.knowledge-tests.edit')->name('knowledge-tests.edit');
    Route::livewire('knowledge-tests/{test}/questions', 'pages::admin.knowledge-tests.questions')->name('knowledge-tests.questions');
    Route::livewire('knowledge-tests/marking', 'pages::admin.knowledge-tests.marking')->name('knowledge-tests.marking');
    Route::livewire('knowledge-tests/marking/{attempt}', 'pages::admin.knowledge-tests.mark-attempt')->name('knowledge-tests.mark-attempt');

    // Activity Verification
    Route::livewire('activities', 'pages::admin.activities.index')->name('activities.index');
    Route::livewire('activities/{activity}', 'pages::admin.activities.show')->name('activities.show');

    // Activity Configuration
    Route::livewire('activity-config', 'pages::admin.activity-config.index')->name('activity-config.index');

    // Firearm Settings
    Route::livewire('firearm-settings', 'pages::admin.firearm-settings.index')->name('firearm-settings.index');

    // Firearm Reference Data Management
    Route::livewire('firearm-reference', 'pages::admin.firearm-reference.index')->name('firearm-reference.index');

    // Terms & Conditions Management
    Route::livewire('settings/terms', 'pages::admin.settings.terms')->name('settings.terms');
    Route::get('settings/terms/{version}/preview', function (\App\Models\TermsVersion $version) {
        return view('pages.admin.settings.terms-preview', [
            'version' => $version,
            'html' => $version->getHtmlContent(),
        ]);
    })->name('settings.terms.preview');

    // Calibre Requests
    Route::livewire('calibre-requests', 'pages::admin.calibre-requests.index')->name('calibre-requests.index');

    // Email Logs
    Route::livewire('email-logs', 'pages::admin.email-logs.index')->name('email-logs.index');

    // Billing Reports
    Route::livewire('billing', 'pages::admin.billing.index')->name('billing.index');

    // Membership Reports
    Route::livewire('reports', 'pages::admin.reports.index')->name('reports.index');

    // Document Verification
    Route::livewire('documents', 'pages::admin.documents.index')->name('documents.index');
    Route::livewire('documents/{document}', 'pages::admin.documents.show')->name('documents.show');

    // Learning Center Management
    Route::livewire('learning', 'pages::admin.learning.index')->name('learning.index');
    Route::livewire('learning/{article}/pages', 'pages::admin.learning.pages')->name('learning.pages');

    // Endorsement Requests Management
    Route::livewire('endorsements', 'pages::admin.endorsements.index')->name('endorsements.index');
    Route::livewire('endorsements/create', 'pages::admin.endorsements.create')->name('endorsements.create');
    Route::livewire('endorsements/{request}', 'pages::admin.endorsements.show')->name('endorsements.show');

    // Bullet Database
    Route::livewire('bullet-database', 'pages::admin.bullet-database.index')->name('bullet-database.index');
    Route::livewire('bullet-database/create', 'pages::admin.bullet-database.create')->name('bullet-database.create');
    Route::livewire('bullet-database/{bullet}/edit', 'pages::admin.bullet-database.edit')->name('bullet-database.edit');
    Route::livewire('bullet-database/import', 'pages::admin.bullet-database.import')->name('bullet-database.import');
});

// Public Certificate Verification
Route::get('verify/{qr_code}', function ($qr_code) {
    $verificationService = app(\App\Services\VerificationService::class);
    $result = $verificationService->verifyByQrCode($qr_code);

    return view('pages.verify', ['result' => $result, 'qr_code' => $qr_code]);
})->name('certificates.verify');

// Public Endorsement Verification
Route::get('verify/endorsement/{reference}', function ($reference) {
    $request = \App\Models\EndorsementRequest::where('letter_reference', $reference)
        ->orWhere('uuid', $reference)
        ->first();

    if (! $request) {
        return view('pages.verify-endorsement', [
            'request' => null,
            'reference' => $reference,
            'error' => 'Endorsement not found',
            'member_info' => null,
        ]);
    }

    $request->load(['user', 'user.activeMembership', 'firearm', 'firearm.firearmCalibre', 'firearm.firearmMake', 'firearm.firearmModel', 'components']);

    $memberInfo = app(\App\Services\VerificationService::class)->memberPublicDisplay(
        $request->user,
        $request->user?->activeMembership
    );

    return view('pages.verify-endorsement', [
        'request' => $request,
        'reference' => $reference,
        'error' => null,
        'member_info' => $memberInfo,
    ]);
})->name('endorsements.verify');

require __DIR__.'/settings.php';
