<?php

use App\Models\ConfigurationChangeRequest;
use App\Models\DocumentType;
use App\Models\MembershipType;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => User::ROLE_ADMIN,
        'admin_type' => User::ADMIN_TYPE_STANDARD,
        'is_admin' => true,
    ]);

    $this->documentType = DocumentType::create([
        'slug' => 'id-document',
        'name' => 'ID Document',
        'archive_months' => 12,
        'is_active' => true,
    ]);

    $this->membershipType = MembershipType::create([
        'slug' => 'standard',
        'name' => 'Standard Membership',
        'duration_type' => 'annual',
        'duration_months' => 12,
        'requires_renewal' => true,
        'pricing_model' => 'annual',
        'price' => 500.00,
        'is_active' => true,
    ]);
});

test('a standard admin updates a document type directly without an approval request', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.index')
        ->call('editDocumentType', $this->documentType->id)
        ->set('documentTypeName', 'Identity Document')
        ->call('saveDocumentType')
        ->assertHasNoErrors();

    expect($this->documentType->fresh()->name)->toBe('Identity Document')
        ->and(ConfigurationChangeRequest::count())->toBe(0);
});

test('a standard admin toggles a document type active state directly', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.index')
        ->call('toggleDocumentTypeActive', $this->documentType->id);

    expect($this->documentType->fresh()->is_active)->toBeFalse();
});

test('a standard admin saves document requirements directly without an approval request', function () {
    $this->actingAs($this->admin);

    Livewire::test('pages::admin.settings.index')
        ->call('configureDocumentRequirements', $this->membershipType->id)
        ->call('toggleDocumentRequirement', $this->documentType->id)
        ->call('saveDocumentRequirements')
        ->assertHasNoErrors();

    expect($this->membershipType->documentTypes()->pluck('document_types.id')->all())
        ->toBe([$this->documentType->id])
        ->and(ConfigurationChangeRequest::count())->toBe(0);
});
