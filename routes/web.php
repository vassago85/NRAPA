<?php

use App\Http\Controllers\SageOAuthController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/terms-and-conditions', function () {
    return view('pages.terms');
})->name('terms-and-conditions');

Route::get('/privacy-policy', function () {
    return view('pages.privacy');
})->name('privacy-policy');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// Info / Resources pages (public)
Route::prefix('info')->name('info.')->group(function () {
    Route::get('/', fn () => view('pages.info.index'))->name('index');
    Route::get('/about', fn () => view('pages.info.about'))->name('about');
    Route::get('/firearm-licence-process', fn () => view('pages.info.firearm-licence-process'))->name('firearm-licence-process');
    Route::get('/minimum-requirements', fn () => view('pages.info.minimum-requirements'))->name('minimum-requirements');
    Route::get('/dedicated-procedure', fn () => view('pages.info.dedicated-procedure'))->name('dedicated-procedure');
    Route::get('/shooting-exercises', fn () => view('pages.info.shooting-exercises'))->name('shooting-exercises');
    Route::get('/dedicated-sport-shooter-south-africa', fn () => view('pages.info.dedicated-sport-shooter-south-africa'))->name('dedicated-sport-shooter-south-africa');
    Route::get('/dedicated-hunter-south-africa', fn () => view('pages.info.dedicated-hunter-south-africa'))->name('dedicated-hunter-south-africa');
    Route::get('/how-to-get-dedicated-status-south-africa', fn () => view('pages.info.how-to-get-dedicated-status-south-africa'))->name('how-to-get-dedicated-status-south-africa');
    Route::get('/endorsements', fn () => view('pages.info.endorsements'))->name('endorsements');
    Route::get('/membership-benefits', fn () => view('pages.info.membership-benefits'))->name('membership-benefits');
    Route::get('/faq', fn () => view('pages.info.faq'))->name('faq');
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

        // Redirect based on role
        return match ($role) {
            'developer' => redirect()->route('developer.dashboard'),
            'owner' => redirect()->route('owner.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('dashboard'),
        };
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

        // Redirect based on user's role
        return match ($user->role) {
            'developer' => redirect()->route('developer.dashboard'),
            'owner' => redirect()->route('owner.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            default => redirect()->route('dashboard'),
        };
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

        $currentView = session('view_as_member', false);
        session(['view_as_member' => ! $currentView]);

        // Redirect to appropriate dashboard
        if (! $currentView) {
            // Now viewing as member
            return redirect()->route('dashboard')->with('success', 'Now viewing as member');
        } else {
            // Back to admin view
            if ($user->isDeveloper()) {
                return redirect()->route('developer.dashboard')->with('success', 'Back to developer view');
            } elseif ($user->isOwner()) {
                return redirect()->route('owner.dashboard')->with('success', 'Back to owner view');
            } else {
                return redirect()->route('admin.dashboard')->with('success', 'Back to admin view');
            }
        }
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

// Member Portal Routes - Requires ACTIVE membership (paid members only)
Route::middleware(['auth', 'verified', 'membership.required', 'terms.accepted'])->group(function () {
    // Digital Membership Card (mobile-optimized)
    Route::livewire('card', 'pages::member.card')->name('card');

    // Certificates (members only - requires active membership)
    Route::livewire('certificates', 'pages::member.certificates.index')->name('certificates.index');
    Route::livewire('certificates/{certificate}', 'pages::member.certificates.show')->name('certificates.show');

    // Certificate wallet pass downloads (member)
    Route::get('certificates/{certificate}/wallet/apple', function (\App\Models\Certificate $certificate) {
        $user = auth()->user();

        // Members can only download their own certificates
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        // Only membership cards support wallet passes
        if ($certificate->certificateType->slug !== 'membership-card') {
            abort(404, 'Wallet passes are only available for membership cards.');
        }

        $walletService = app(\App\Services\WalletPassService::class);
        $filePath = $walletService->generateAppleWalletPass($certificate);

        if (! $filePath) {
            abort(503, 'Apple Wallet pass generation is not yet configured. Please contact support.');
        }

        $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';

        return Storage::disk($disk)->download($filePath, 'nrapa-membership-card.pkpass', [
            'Content-Type' => 'application/vnd.apple.pkpass',
        ]);
    })->name('certificates.wallet.apple');

    Route::get('certificates/{certificate}/wallet/google', function (\App\Models\Certificate $certificate) {
        $user = auth()->user();

        // Members can only download their own certificates
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        // Only membership cards support wallet passes
        if ($certificate->certificateType->slug !== 'membership-card') {
            abort(404, 'Wallet passes are only available for membership cards.');
        }

        $walletService = app(\App\Services\WalletPassService::class);
        $saveUrl = $walletService->generateGoogleWalletPass($certificate);

        if (! $saveUrl) {
            abort(503, 'Google Wallet pass generation is not yet configured. Please contact support.');
        }

        return redirect($saveUrl);
    })->name('certificates.wallet.google');

    // Certificate PDF download (member)
    Route::get('certificates/{certificate}/download', function (\App\Models\Certificate $certificate) {
        $user = auth()->user();

        // Members can only download their own certificates
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';

        if (! $certificate->file_path || ! \Illuminate\Support\Facades\Storage::disk($disk)->exists($certificate->file_path)) {
            $issueService = app(\App\Services\CertificateIssueService::class);
            $filePath = $issueService->regenerateDocument($certificate);
            if (! $filePath) {
                abort(404, 'Certificate file could not be generated. Please contact support.');
            }
            $certificate->refresh();
        }

        $filename = 'nrapa-'.str_replace(' ', '-', strtolower($certificate->certificateType->name)).'-'.$certificate->certificate_number.'.pdf';

        return \Illuminate\Support\Facades\Storage::disk($disk)->download($certificate->file_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    })->name('certificates.download');

    // Certificate preview (renders the document template)
    Route::get('certificates/{certificate}/preview', function (\App\Models\Certificate $certificate) {
        $user = auth()->user();

        // Members can only view their own certificates
        if ($certificate->user_id !== $user->id) {
            abort(403);
        }

        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Map template based on certificate type slug or template name
        $template = $certificate->certificateType->template ?? 'documents.certificates.good-standing';

        // Map old template names to new document templates
        $templateMap = [
            'certificates.membership' => 'documents.membership-card',
            'certificates.dedicated' => 'documents.certificates.dedicated-status',
            'certificates.endorsement' => 'documents.letters.endorsement',
            'certificates.confirmation' => 'documents.certificates.good-standing',
            'documents.paid-up' => 'documents.certificates.good-standing',
            'documents.dedicated-hunter' => 'documents.certificates.dedicated-status',
            'documents.dedicated-sport' => 'documents.certificates.dedicated-status',
            'documents.welcome-letter' => 'documents.letters.welcome',
        ];

        // Check if template needs mapping
        if (isset($templateMap[$template])) {
            $template = $templateMap[$template];
        }

        // Also check by slug for more specific mapping
        $slug = $certificate->certificateType->slug ?? '';
        if ($slug === 'dedicated-hunter-certificate' || $slug === 'dedicated-hunter') {
            $template = 'documents.certificates.dedicated-status';
        } elseif ($slug === 'dedicated-sport-certificate' || $slug === 'dedicated-sport' || $slug === 'dedicated-sport-shooter') {
            $template = 'documents.certificates.dedicated-status';
        } elseif ($slug === 'membership-card') {
            $template = 'documents.membership-card';
        } elseif ($slug === 'membership-certificate' || $slug === 'paid-up-certificate' || $slug === 'good-standing-certificate') {
            $template = 'documents.certificates.good-standing';
        } elseif ($slug === 'welcome-letter') {
            $template = 'documents.letters.welcome';
        }

        return view($template, [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ]);
    })->name('certificates.preview');

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
    Route::livewire('documents/upload', 'pages::member.documents.upload')->name('documents.upload');
    Route::livewire('documents/{document}', 'pages::member.documents.show')->name('documents.show');

    // Document preview proxy (streams file through Laravel to bypass R2 signed URL issues)
    Route::get('documents/{document}/preview', function (\App\Models\MemberDocument $document) {
        // Ensure user can only view their own documents
        if ($document->user_id !== auth()->id()) {
            abort(403);
        }

        // Use local storage for local/development/testing environments
        $disk = app()->environment(['local', 'development', 'testing'])
            ? 'local'
            : (config('filesystems.disks.r2.key') ? 'r2' : 's3');

        if (! \Illuminate\Support\Facades\Storage::disk($disk)->exists($document->file_path)) {
            abort(404);
        }

        return response()->stream(function () use ($disk, $document) {
            $stream = \Illuminate\Support\Facades\Storage::disk($disk)->readStream($document->file_path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$document->original_filename.'"',
        ]);
    })->name('documents.preview');

    // Learning Center
    Route::livewire('learning', 'pages::member.learning.index')->name('learning.index');
    Route::livewire('learning/category/{category}', 'pages::member.learning.category')->name('learning.category');
    Route::livewire('learning/{article}', 'pages::member.learning.show')->name('learning.show');

    // Endorsement Letters
    Route::livewire('endorsements', 'pages::member.endorsements.index')->name('member.endorsements.index');
    Route::livewire('endorsements/create', 'pages::member.endorsements.create')->name('member.endorsements.create');
    Route::livewire('endorsements/{request}/edit', 'pages::member.endorsements.create')->name('member.endorsements.edit');
    Route::livewire('endorsements/{request}', 'pages::member.endorsements.show')->name('member.endorsements.show');

    // Member endorsement letter preview (renders template)
    Route::get('endorsements/{request}/preview', function (\App\Models\EndorsementRequest $request) {
        // Ensure user can only view their own endorsements
        if ($request->user_id !== auth()->id()) {
            abort(403);
        }

        $request->loadMissing([
            'user',
            'user.activeMembership',
            'firearm',
            'firearm.firearmCalibre',
            'firearm.firearmMake',
            'firearm.firearmModel',
            'components',
        ]);

        return view('documents.letters.endorsement', [
            'request' => $request,
            'user' => $request->user,
            'firearm' => $request->firearm,
            'membership' => $request->user->activeMembership,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ]);
    })->name('member.endorsements.preview');

    // Member endorsement letter view/download (always regenerates to use latest renderer)
    Route::get('endorsements/{request}/letter', function (\App\Models\EndorsementRequest $request) {
        if ($request->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $request->isIssued() && ! $request->isApproved()) {
            abort(404, 'Endorsement letter not found.');
        }

        $disk = app()->environment(['local', 'development', 'testing'])
            ? 'local'
            : (config('filesystems.disks.r2.key') ? 'r2' : (config('filesystems.disks.s3.key') ? 's3' : 'local'));
        $storage = \Illuminate\Support\Facades\Storage::disk($disk);

        $renderer = app(\App\Contracts\DocumentRenderer::class);
        $filePath = $renderer->renderEndorsementLetter($request, 'documents.letters.endorsement');
        $request->update(['letter_file_path' => $filePath]);

        $filename = 'endorsement-letter-'.($request->letter_reference ?? $request->uuid).'.pdf';

        return response()->stream(function () use ($storage, $filePath) {
            $stream = $storage->readStream($filePath);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    })->name('member.endorsements.letter');
});

// Owner Routes (Owners and Developers can access)
Route::middleware(['auth', 'verified', 'owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::livewire('dashboard', 'pages::owner.dashboard')->name('dashboard');
    Route::livewire('admins', 'pages::owner.admins.index')->name('admins.index');

    // User Deletion Requests
    Route::livewire('deletion-requests', 'pages::owner.deletion-requests.index')->name('deletion-requests.index');

    // Owner Settings (Bank, Email, Storage, etc.)
    Route::livewire('settings', 'pages::owner.settings.index')->name('settings.index');
    Route::livewire('settings/email', 'pages::owner.settings.email')->name('settings.email');
    Route::livewire('settings/storage', 'pages::owner.settings.storage')->name('settings.storage');
    Route::livewire('settings/approvals', 'pages::owner.settings.approvals')->name('settings.approvals');
    Route::livewire('settings/documents', 'pages::owner.settings.documents')->name('settings.documents');
    Route::livewire('settings/backup', 'pages::owner.settings.backup')->name('settings.backup');
    Route::livewire('settings/sage', 'pages::owner.settings.sage')->name('settings.sage');

    // Sage OAuth flow (redirect initiates from owner panel)
    Route::get('sage/redirect', [SageOAuthController::class, 'redirect'])->name('sage.redirect');
});

// Sage OAuth callback (outside owner prefix so the redirect URI is /sage/callback)
Route::middleware(['auth', 'verified', 'owner'])->get('sage/callback', [SageOAuthController::class, 'callback'])->name('sage.callback');

// Developer Routes (Developer only)
Route::middleware(['auth', 'verified', 'developer'])->prefix('developer')->name('developer.')->group(function () {
    Route::livewire('dashboard', 'pages::developer.dashboard')->name('dashboard');

    // Test FAR Numbers Route
    Route::get('test/far-numbers', function () {
        try {
            $results = [
                'database_status' => 'unknown',
                'settings_in_db' => [],
                'helper_method' => [],
                'direct_get' => [],
                'cache_status' => 'unknown',
            ];

            // Check database
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                    $results['database_status'] = 'table_exists';

                    $sportSetting = \Illuminate\Support\Facades\DB::table('system_settings')
                        ->where('key', 'far_sport_number')
                        ->first();
                    $huntingSetting = \Illuminate\Support\Facades\DB::table('system_settings')
                        ->where('key', 'far_hunting_number')
                        ->first();

                    $results['settings_in_db'] = [
                        'far_sport_number' => $sportSetting ? [
                            'exists' => true,
                            'value' => $sportSetting->value,
                            'type' => $sportSetting->type,
                        ] : ['exists' => false],
                        'far_hunting_number' => $huntingSetting ? [
                            'exists' => true,
                            'value' => $huntingSetting->value,
                            'type' => $huntingSetting->type,
                        ] : ['exists' => false],
                    ];
                } else {
                    $results['database_status'] = 'table_not_exists';
                }
            } catch (\Exception $e) {
                $results['database_status'] = 'error: '.$e->getMessage();
            }

            // Test helper method
            try {
                $farNumbers = \App\Helpers\DocumentDataHelper::getFarNumbers();
                $results['helper_method'] = [
                    'success' => true,
                    'sport' => $farNumbers['sport'] ?? 'NULL',
                    'hunting' => $farNumbers['hunting'] ?? 'NULL',
                ];
            } catch (\Exception $e) {
                $results['helper_method'] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }

            // Test direct SystemSetting::get()
            try {
                $results['direct_get'] = [
                    'far_sport_number' => \App\Models\SystemSetting::get('far_sport_number', 'NOT_FOUND'),
                    'far_hunting_number' => \App\Models\SystemSetting::get('far_hunting_number', 'NOT_FOUND'),
                ];
            } catch (\Exception $e) {
                $results['direct_get'] = ['error' => $e->getMessage()];
            }

            // Check cache
            try {
                $sportCached = \Illuminate\Support\Facades\Cache::get('system_setting.far_sport_number');
                $huntingCached = \Illuminate\Support\Facades\Cache::get('system_setting.far_hunting_number');
                $results['cache_status'] = [
                    'far_sport_number' => $sportCached ? 'cached' : 'not_cached',
                    'far_hunting_number' => $huntingCached ? 'cached' : 'not_cached',
                ];
            } catch (\Exception $e) {
                $results['cache_status'] = 'error: '.$e->getMessage();
            }

            return response()->json($results, 200, [], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    })->name('test.far-numbers');

    // Fix Endorsement Status Enum Route
    Route::get('fix/endorsement-status-enum', function () {
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            return response()->json(['error' => 'This fix is only for SQLite databases'], 400);
        }

        try {
            $tableName = 'endorsement_requests';

            if (! \Illuminate\Support\Facades\Schema::hasTable($tableName)) {
                return response()->json(['error' => "Table {$tableName} does not exist!"], 400);
            }

            // Check current constraint by trying to read schema
            $schema = \Illuminate\Support\Facades\DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$tableName}'");
            $currentSql = $schema[0]->sql ?? '';

            if (strpos($currentSql, "'approved'") !== false) {
                return response()->json(['message' => 'Status "approved" is already allowed in the enum constraint.'], 200);
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($tableName) {
                // Create new table with updated constraint
                \Illuminate\Support\Facades\DB::statement("
                    CREATE TABLE IF NOT EXISTS {$tableName}_new (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        uuid TEXT NOT NULL UNIQUE,
                        user_id INTEGER NOT NULL,
                        request_type TEXT NOT NULL DEFAULT 'new' CHECK(request_type IN ('new', 'renewal')),
                        status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft', 'submitted', 'under_review', 'pending_documents', 'approved', 'issued', 'rejected', 'cancelled')),
                        purpose TEXT CHECK(purpose IN ('section_16_application', 'status_confirmation', 'licence_renewal', 'additional_firearm', 'other')),
                        purpose_other_text TEXT,
                        declaration_accepted_at TIMESTAMP,
                        declaration_text TEXT,
                        submitted_at TIMESTAMP,
                        reviewed_at TIMESTAMP,
                        issued_at TIMESTAMP,
                        rejected_at TIMESTAMP,
                        cancelled_at TIMESTAMP,
                        reviewer_id INTEGER,
                        issued_by INTEGER,
                        member_notes TEXT,
                        admin_notes TEXT,
                        rejection_reason TEXT,
                        letter_reference TEXT UNIQUE,
                        letter_file_path TEXT,
                        created_at TIMESTAMP,
                        updated_at TIMESTAMP,
                        deleted_at TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
                        FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL
                    )
                ");

                // Copy data
                \Illuminate\Support\Facades\DB::statement("INSERT INTO {$tableName}_new SELECT * FROM {$tableName}");

                // Drop old table
                \Illuminate\Support\Facades\DB::statement("DROP TABLE {$tableName}");

                // Rename new table
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE {$tableName}_new RENAME TO {$tableName}");

                // Recreate indexes
                \Illuminate\Support\Facades\DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_user_status ON {$tableName}(user_id, status)");
                \Illuminate\Support\Facades\DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_status_created ON {$tableName}(status, created_at)");
                \Illuminate\Support\Facades\DB::statement("CREATE INDEX IF NOT EXISTS idx_endorsement_requests_request_type ON {$tableName}(request_type)");
            });

            return response()->json([
                'success' => true,
                'message' => 'Successfully updated status enum to include "approved"!',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fix status enum: '.$e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    })->name('fix.endorsement-status-enum');

    Route::livewire('owners', 'pages::developer.owners.index')->name('owners.index');
    Route::livewire('owners/nominate', 'pages::developer.owners.create')->name('owners.create');

    // Certificates (developer management page with table, filters, bulk actions)
    Route::livewire('certificates', 'pages::developer.certificates')->name('certificates.index');
    Route::livewire('certificates/{certificate}', 'pages::member.certificates.show')->name('certificates.show');

    // Certificate wallet pass downloads (developer)
    Route::get('certificates/{certificate}/wallet/apple', function (\App\Models\Certificate $certificate) {
        // Only membership cards support wallet passes
        if ($certificate->certificateType->slug !== 'membership-card') {
            abort(404, 'Wallet passes are only available for membership cards.');
        }

        $walletService = app(\App\Services\WalletPassService::class);
        $filePath = $walletService->generateAppleWalletPass($certificate);

        if (! $filePath) {
            abort(503, 'Apple Wallet pass generation is not yet configured. Please contact support.');
        }

        $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';

        return \Illuminate\Support\Facades\Storage::disk($disk)->download($filePath, 'nrapa-membership-card.pkpass', [
            'Content-Type' => 'application/vnd.apple.pkpass',
        ]);
    })->name('certificates.wallet.apple');

    Route::get('certificates/{certificate}/wallet/google', function (\App\Models\Certificate $certificate) {
        // Only membership cards support wallet passes
        if ($certificate->certificateType->slug !== 'membership-card') {
            abort(404, 'Wallet passes are only available for membership cards.');
        }

        $walletService = app(\App\Services\WalletPassService::class);
        $saveUrl = $walletService->generateGoogleWalletPass($certificate);

        if (! $saveUrl) {
            abort(503, 'Google Wallet pass generation is not yet configured. Please contact support.');
        }

        return redirect($saveUrl);
    })->name('certificates.wallet.google');

    // Certificate PDF download (developer)
    Route::get('certificates/{certificate}/download', function (\App\Models\Certificate $certificate) {
        $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';

        if (! $certificate->file_path || ! \Illuminate\Support\Facades\Storage::disk($disk)->exists($certificate->file_path)) {
            $issueService = app(\App\Services\CertificateIssueService::class);
            $filePath = $issueService->regenerateDocument($certificate);
            if (! $filePath) {
                abort(404, 'Certificate file could not be generated.');
            }
            $certificate->refresh();
        }

        $filename = 'nrapa-'.str_replace(' ', '-', strtolower($certificate->certificateType->name)).'-'.$certificate->certificate_number.'.pdf';

        return \Illuminate\Support\Facades\Storage::disk($disk)->download($certificate->file_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    })->name('certificates.download');

    // Certificate preview (renders the document template)
    Route::get('certificates/{certificate}/preview', function (\App\Models\Certificate $certificate) {
        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Map template based on certificate type slug or template name
        $template = $certificate->certificateType->template ?? 'documents.certificates.good-standing';

        // Map old template names to new document templates
        $templateMap = [
            'certificates.membership' => 'documents.membership-card',
            'certificates.dedicated' => 'documents.certificates.dedicated-status',
            'certificates.endorsement' => 'documents.letters.endorsement',
            'certificates.confirmation' => 'documents.certificates.good-standing',
            'documents.paid-up' => 'documents.certificates.good-standing',
            'documents.dedicated-hunter' => 'documents.certificates.dedicated-status',
            'documents.dedicated-sport' => 'documents.certificates.dedicated-status',
        ];

        // Check if template needs mapping
        if (isset($templateMap[$template])) {
            $template = $templateMap[$template];
        }

        // Also check by slug for more specific mapping
        $slug = $certificate->certificateType->slug ?? '';
        if ($slug === 'dedicated-hunter-certificate' || $slug === 'dedicated-hunter') {
            $template = 'documents.certificates.dedicated-status';
        } elseif ($slug === 'dedicated-sport-certificate' || $slug === 'dedicated-sport' || $slug === 'dedicated-sport-shooter') {
            $template = 'documents.certificates.dedicated-status';
        } elseif ($slug === 'membership-card') {
            $template = 'documents.membership-card';
        } elseif ($slug === 'membership-certificate' || $slug === 'paid-up-certificate' || $slug === 'good-standing-certificate') {
            $template = 'documents.certificates.good-standing';
        }

        return view($template, [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ]);
    })->name('certificates.preview');

    Route::get('document-preview', [\App\Http\Controllers\DocumentPreviewController::class, 'index'])->name('document-preview.index');
    Route::get('document-preview/{type}', [\App\Http\Controllers\DocumentPreviewController::class, 'show'])->name('document-preview.show');
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

    // Certificate wallet pass downloads (admin)
    Route::get('certificates/{certificate}/wallet/apple', function (\App\Models\Certificate $certificate) {
        // Only membership cards support wallet passes
        if ($certificate->certificateType->slug !== 'membership-card') {
            abort(404, 'Wallet passes are only available for membership cards.');
        }

        $walletService = app(\App\Services\WalletPassService::class);
        $filePath = $walletService->generateAppleWalletPass($certificate);

        if (! $filePath) {
            abort(503, 'Apple Wallet pass generation is not yet configured. Please contact support.');
        }

        $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';

        return \Illuminate\Support\Facades\Storage::disk($disk)->download($filePath, 'nrapa-membership-card.pkpass', [
            'Content-Type' => 'application/vnd.apple.pkpass',
        ]);
    })->name('certificates.wallet.apple');

    Route::get('certificates/{certificate}/wallet/google', function (\App\Models\Certificate $certificate) {
        // Only membership cards support wallet passes
        if ($certificate->certificateType->slug !== 'membership-card') {
            abort(404, 'Wallet passes are only available for membership cards.');
        }

        $walletService = app(\App\Services\WalletPassService::class);
        $saveUrl = $walletService->generateGoogleWalletPass($certificate);

        if (! $saveUrl) {
            abort(503, 'Google Wallet pass generation is not yet configured. Please contact support.');
        }

        return redirect($saveUrl);
    })->name('certificates.wallet.google');

    // Certificate PDF download (admin)
    Route::get('certificates/{certificate}/download', function (\App\Models\Certificate $certificate) {
        $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';

        if (! $certificate->file_path || ! \Illuminate\Support\Facades\Storage::disk($disk)->exists($certificate->file_path)) {
            $issueService = app(\App\Services\CertificateIssueService::class);
            $filePath = $issueService->regenerateDocument($certificate);
            if (! $filePath) {
                abort(404, 'Certificate file could not be generated.');
            }
            $certificate->refresh();
        }

        $filename = 'nrapa-'.str_replace(' ', '-', strtolower($certificate->certificateType->name)).'-'.$certificate->certificate_number.'.pdf';

        return \Illuminate\Support\Facades\Storage::disk($disk)->download($certificate->file_path, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    })->name('certificates.download');

    // Certificate preview (renders the document template)
    Route::get('certificates/{certificate}/preview', function (\App\Models\Certificate $certificate) {
        $certificate->loadMissing(['user', 'membership.type', 'certificateType']);

        // Map template based on certificate type slug or template name
        $template = $certificate->certificateType->template ?? 'documents.certificates.good-standing';

        // Map old template names to new document templates
        $templateMap = [
            'certificates.membership' => 'documents.membership-card',
            'certificates.dedicated' => 'documents.certificates.dedicated-status',
            'certificates.endorsement' => 'documents.letters.endorsement',
            'certificates.confirmation' => 'documents.certificates.good-standing',
            'documents.paid-up' => 'documents.certificates.good-standing',
            'documents.dedicated-hunter' => 'documents.certificates.dedicated-status',
            'documents.dedicated-sport' => 'documents.certificates.dedicated-status',
        ];

        // Check if template needs mapping
        if (isset($templateMap[$template])) {
            $template = $templateMap[$template];
        }

        // Also check by slug for more specific mapping
        $slug = $certificate->certificateType->slug ?? '';
        if ($slug === 'dedicated-hunter-certificate' || $slug === 'dedicated-hunter') {
            $template = 'documents.certificates.dedicated-status';
        } elseif ($slug === 'dedicated-sport-certificate' || $slug === 'dedicated-sport' || $slug === 'dedicated-sport-shooter') {
            $template = 'documents.certificates.dedicated-status';
        } elseif ($slug === 'membership-card') {
            $template = 'documents.membership-card';
        } elseif ($slug === 'membership-certificate' || $slug === 'paid-up-certificate' || $slug === 'good-standing-certificate') {
            $template = 'documents.certificates.good-standing';
        }

        return view($template, [
            'certificate' => $certificate,
            'user' => $certificate->user,
            'membership' => $certificate->membership,
            'certificateType' => $certificate->certificateType,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ]);
    })->name('certificates.preview');

    // Membership Types Management
    Route::livewire('membership-types', 'pages::admin.membership-types.index')->name('membership-types.index');

    // Affiliated Clubs Management
    Route::livewire('affiliated-clubs', 'pages::admin.affiliated-clubs.index')->name('affiliated-clubs.index');

    Route::livewire('approvals', 'pages::admin.approvals.index')->name('approvals.index');
    Route::livewire('approvals/{membership}', 'pages::admin.approvals.show')->name('approvals.show');

    // Proof of payment proxy (streams from R2 through the app to avoid signed-URL issues)
    Route::get('approvals/{membership}/proof-of-payment', function (\App\Models\Membership $membership) {
        if (! $membership->proof_of_payment_path) {
            abort(404, 'No proof of payment uploaded.');
        }

        $disk = app()->environment(['local', 'development', 'testing']) ? 'local' : 'r2';

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

    // Admin document preview proxy (streams file through Laravel)
    Route::get('documents/{document}/preview', function (\App\Models\MemberDocument $document) {
        // Use local storage for local/development/testing environments
        $disk = app()->environment(['local', 'development', 'testing'])
            ? 'local'
            : (config('filesystems.disks.r2.key') ? 'r2' : 's3');

        if (! \Illuminate\Support\Facades\Storage::disk($disk)->exists($document->file_path)) {
            abort(404);
        }

        return response()->stream(function () use ($disk, $document) {
            $stream = \Illuminate\Support\Facades\Storage::disk($disk)->readStream($document->file_path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$document->original_filename.'"',
        ]);
    })->name('documents.preview');

    // Learning Center Management
    Route::livewire('learning', 'pages::admin.learning.index')->name('learning.index');
    Route::livewire('learning/{article}/pages', 'pages::admin.learning.pages')->name('learning.pages');

    // Endorsement Requests Management
    Route::livewire('endorsements', 'pages::admin.endorsements.index')->name('endorsements.index');
    Route::livewire('endorsements/create', 'pages::admin.endorsements.create')->name('endorsements.create');
    Route::livewire('endorsements/{request}', 'pages::admin.endorsements.show')->name('endorsements.show');

    // Admin endorsement letter preview (renders template)
    Route::get('endorsements/{request}/preview', function (\App\Models\EndorsementRequest $request) {
        $request->loadMissing([
            'user',
            'user.activeMembership',
            'firearm',
            'firearm.firearmCalibre',
            'firearm.firearmMake',
            'firearm.firearmModel',
            'components',
        ]);

        return view('documents.letters.endorsement', [
            'request' => $request,
            'user' => $request->user,
            'firearm' => $request->firearm,
            'membership' => $request->user->activeMembership,
            'logo_url' => \App\Helpers\DocumentHelper::getLogoUrl(),
        ]);
    })->name('endorsements.preview'); // Note: This becomes 'admin.endorsements.preview' due to group prefix

    // Admin endorsement letter download (always regenerates to use latest renderer)
    Route::get('endorsements/{request}/download', function (\App\Models\EndorsementRequest $request) {
        if (! $request->isIssued()) {
            abort(404, 'Endorsement letter not found.');
        }

        $disk = app()->environment(['local', 'development', 'testing'])
            ? 'local'
            : (config('filesystems.disks.r2.key') ? 'r2' : (config('filesystems.disks.s3.key') ? 's3' : 'local'));
        $storage = \Illuminate\Support\Facades\Storage::disk($disk);

        $renderer = app(\App\Contracts\DocumentRenderer::class);
        $filePath = $renderer->renderEndorsementLetter($request, 'documents.letters.endorsement');
        $request->update(['letter_file_path' => $filePath]);

        $filename = 'endorsement-letter-'.($request->letter_reference ?? $request->uuid).'.pdf';

        return response()->stream(function () use ($storage, $filePath) {
            $stream = $storage->readStream($filePath);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    })->name('endorsements.download');

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

// Temporary test route for FirearmSearchPanel component
if (app()->environment('local', 'development', 'testing')) {
    Route::get('/test-firearm-panel', function () {
        return view('test-firearm-panel');
    })->name('test.firearm-panel');
}
