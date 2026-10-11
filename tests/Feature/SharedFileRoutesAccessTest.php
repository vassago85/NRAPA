<?php

use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\DocumentType;
use App\Models\MemberDocument;
use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Certificate, member-document and endorsement-letter files are served from a single
 * set of member routes. The owning member and staff may open them; other members may not.
 */
beforeEach(function () {
    Storage::fake('local');

    $type = MembershipType::create([
        'slug' => 'shared-files-annual',
        'name' => 'Annual',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500,
        'is_active' => true,
    ]);

    $makeMember = function () use ($type) {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER, 'email_verified_at' => now()]);
        Membership::create([
            'user_id' => $user->id,
            'membership_type_id' => $type->id,
            'status' => 'active',
            'applied_at' => now(),
            'activated_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        return $user;
    };

    $this->owner = $makeMember();
    $this->otherMember = $makeMember();
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()]);

    Storage::disk('local')->put('certificates/test.pdf', '%PDF-1.4 certificate');
    $this->certificate = Certificate::create([
        'user_id' => $this->owner->id,
        'membership_id' => $this->owner->activeMembership->id,
        'certificate_type_id' => CertificateType::create([
            'slug' => 'membership-certificate',
            'name' => 'Membership Certificate',
            'template' => 'documents.certificates.good-standing',
            'validity_months' => 12,
            'is_active' => true,
            'sort_order' => 1,
        ])->id,
        'valid_from' => now(),
        'valid_until' => now()->addYear(),
        'file_path' => 'certificates/test.pdf',
    ]);

    Storage::disk('local')->put('member-documents/id.pdf', '%PDF-1.4 id');
    $this->document = MemberDocument::create([
        'user_id' => $this->owner->id,
        'document_type_id' => DocumentType::create([
            'slug' => 'identity-document',
            'name' => 'ID',
            'is_active' => true,
            'sort_order' => 1,
        ])->id,
        'file_path' => 'member-documents/id.pdf',
        'original_filename' => 'id.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 23,
        'status' => 'pending',
        'uploaded_at' => now(),
    ]);
});

test('certificate download is served to the owner and to staff only', function () {
    $this->actingAs($this->owner)
        ->get(route('certificates.download', $this->certificate))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $this->actingAs($this->admin)
        ->get(route('certificates.download', $this->certificate))
        ->assertOk();

    $this->actingAs($this->otherMember)
        ->get(route('certificates.download', $this->certificate))
        ->assertForbidden();
});

test('certificate preview is forbidden to other members', function () {
    $this->actingAs($this->otherMember)
        ->get(route('certificates.preview', $this->certificate))
        ->assertForbidden();
});

test('wallet passes are only offered for membership cards', function () {
    $this->actingAs($this->owner)
        ->get(route('certificates.wallet.apple', $this->certificate))
        ->assertNotFound();
});

test('member document preview is streamed to the owner and to staff only', function () {
    $this->actingAs($this->owner)
        ->get(route('documents.preview', $this->document))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $this->actingAs($this->admin)
        ->get(route('documents.preview', $this->document))
        ->assertOk();

    $this->actingAs($this->otherMember)
        ->get(route('documents.preview', $this->document))
        ->assertForbidden();
});
