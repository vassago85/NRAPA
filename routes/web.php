<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Member Portal Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::livewire('dashboard', 'pages::member.dashboard')->name('dashboard');

    // Membership Package Selection (for new users)
    Route::livewire('membership/select-package', 'pages::member.membership.select-package')->name('membership.select-package');
    Route::livewire('membership/payment/{membership}', 'pages::member.membership.payment')->name('membership.payment');

    // Membership
    Route::livewire('membership', 'pages::member.membership.index')->name('membership.index');
    Route::livewire('membership/apply', 'pages::member.membership.apply')->name('membership.apply');
    Route::livewire('membership/{membership}', 'pages::member.membership.show')->name('membership.show');

    // Certificates
    Route::livewire('certificates', 'pages::member.certificates.index')->name('certificates.index');
    Route::livewire('certificates/{certificate}', 'pages::member.certificates.show')->name('certificates.show');

    // Knowledge Test
    Route::livewire('knowledge-test', 'pages::member.knowledge-test.index')->name('knowledge-test.index');
    Route::livewire('knowledge-test/{test}/take', 'pages::member.knowledge-test.take')->name('knowledge-test.take');
    Route::livewire('knowledge-test/{attempt}/results', 'pages::member.knowledge-test.results')->name('knowledge-test.results');

    // Activities
    Route::livewire('activities', 'pages::member.activities.index')->name('activities.index');
    Route::livewire('activities/submit', 'pages::member.activities.submit')->name('activities.submit');
    Route::livewire('activities/{activity}', 'pages::member.activities.show')->name('activities.show');
    Route::livewire('activities/{activity}/edit', 'pages::member.activities.edit')->name('activities.edit');

    // Virtual Armoury (My Firearms)
    Route::livewire('armoury', 'pages::member.armoury.index')->name('armoury.index');
    Route::livewire('armoury/add', 'pages::member.armoury.create')->name('armoury.create');
    Route::livewire('armoury/{firearm}', 'pages::member.armoury.show')->name('armoury.show');
    Route::livewire('armoury/{firearm}/edit', 'pages::member.armoury.edit')->name('armoury.edit');

    // Load Data (Reloading)
    Route::livewire('load-data', 'pages::member.load-data.index')->name('load-data.index');
    Route::livewire('load-data/create', 'pages::member.load-data.create')->name('load-data.create');
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
        
        $disk = config('filesystems.disks.r2.key') ? 'r2' : 's3';
        
        if (!\Illuminate\Support\Facades\Storage::disk($disk)->exists($document->file_path)) {
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
            'Content-Disposition' => 'inline; filename="' . $document->original_filename . '"',
        ]);
    })->name('documents.preview');

    // Learning Center
    Route::livewire('learning', 'pages::member.learning.index')->name('learning.index');
    Route::livewire('learning/category/{category}', 'pages::member.learning.category')->name('learning.category');
    Route::livewire('learning/{article}', 'pages::member.learning.show')->name('learning.show');
});

// Owner Routes (Owners and Developers can access)
Route::middleware(['auth', 'verified', 'owner'])->prefix('owner')->name('owner.')->group(function () {
    Route::livewire('dashboard', 'pages::owner.dashboard')->name('dashboard');
    Route::livewire('admins', 'pages::owner.admins.index')->name('admins.index');
    Route::livewire('admins/create', 'pages::owner.admins.create')->name('admins.create');

    // Owner Settings (Bank, Email, Storage, etc.)
    Route::livewire('settings', 'pages::owner.settings.index')->name('settings.index');
    Route::livewire('settings/email', 'pages::owner.settings.email')->name('settings.email');
    Route::livewire('settings/storage', 'pages::owner.settings.storage')->name('settings.storage');
    Route::livewire('settings/approvals', 'pages::owner.settings.approvals')->name('settings.approvals');
});

// Developer Routes (Developer only)
Route::middleware(['auth', 'verified', 'developer'])->prefix('developer')->name('developer.')->group(function () {
    Route::livewire('dashboard', 'pages::developer.dashboard')->name('dashboard');
    Route::livewire('owners', 'pages::developer.owners.index')->name('owners.index');
    Route::livewire('owners/nominate', 'pages::developer.owners.create')->name('owners.create');
});

// Admin Routes
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('members', 'pages::admin.members.index')->name('members.index');
    Route::livewire('members/{user}', 'pages::admin.members.show')->name('members.show');

    Route::livewire('approvals', 'pages::admin.approvals.index')->name('approvals.index');
    Route::livewire('approvals/{membership}', 'pages::admin.approvals.show')->name('approvals.show');

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

    // Email Logs
    Route::livewire('email-logs', 'pages::admin.email-logs.index')->name('email-logs.index');

    // Document Verification
    Route::livewire('documents', 'pages::admin.documents.index')->name('documents.index');
    Route::livewire('documents/{document}', 'pages::admin.documents.show')->name('documents.show');

    // Learning Center Management
    Route::livewire('learning', 'pages::admin.learning.index')->name('learning.index');
    Route::livewire('learning/{article}/pages', 'pages::admin.learning.pages')->name('learning.pages');
});

// Public Certificate Verification
Route::get('verify/{qr_code}', function ($qr_code) {
    return view('pages.verify', ['qr_code' => $qr_code]);
})->name('certificates.verify');

require __DIR__.'/settings.php';
