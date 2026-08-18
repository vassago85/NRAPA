<?php

use App\Models\DocumentType;
use App\Models\MemberDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * Cover the file-type contract for the member `/documents` upload page.
 *
 * The customer-facing bug we're guarding against here is "only PDF uploads
 * — I can't attach a photo of my ID". The Livewire component already lists
 * jpg/png in its `mimes:` rule and the `accept` attribute now leads with
 * images, but nothing was pinning the server-side contract in tests.
 */

beforeEach(function () {
    Storage::fake('local');

    $this->idType = DocumentType::create([
        'slug' => 'identity-document',
        'name' => 'ID',
        'description' => 'South African ID document',
        'expiry_months' => null,
        'archive_months' => 12,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->member = User::factory()->create([
        'role' => User::ROLE_MEMBER,
        'email_verified_at' => now(),
    ]);
});

function uploadIdDocument(User $member, DocumentType $type, UploadedFile $file)
{
    return Livewire::actingAs($member)
        ->test('pages::member.documents.index')
        ->set('selectedDocumentType', $type->id)
        ->set('idSurname', 'Smith')
        ->set('idNames', 'John William')
        ->set('idSex', 'male')
        // Any valid 13-digit SA ID triggers auto-populate for date_of_birth/sex.
        ->set('idNumber', '9001010000089')
        ->set('uploadFile', $file)
        ->call('uploadDocument');
}

test('member can upload a PNG photo as their ID document', function () {
    $file = UploadedFile::fake()->image('id.png', 800, 500);

    uploadIdDocument($this->member, $this->idType, $file)
        ->assertHasNoErrors();

    $doc = MemberDocument::where('user_id', $this->member->id)->firstOrFail();

    expect($doc->document_type_id)->toBe($this->idType->id);
    expect($doc->original_filename)->toBe('id.png');
    expect($doc->mime_type)->toStartWith('image/');
    expect($doc->status)->toBe('pending');

    Storage::disk('local')->assertExists($doc->file_path);
});

test('member can upload a JPG photo as their ID document', function () {
    $file = UploadedFile::fake()->image('id.jpg', 800, 500);

    uploadIdDocument($this->member, $this->idType, $file)
        ->assertHasNoErrors();

    $doc = MemberDocument::where('user_id', $this->member->id)->firstOrFail();

    expect($doc->original_filename)->toBe('id.jpg');
    expect($doc->mime_type)->toStartWith('image/');
});

test('member can still upload a PDF as their ID document', function () {
    $file = UploadedFile::fake()->create('id.pdf', 200, 'application/pdf');

    uploadIdDocument($this->member, $this->idType, $file)
        ->assertHasNoErrors();

    $doc = MemberDocument::where('user_id', $this->member->id)->firstOrFail();

    expect($doc->original_filename)->toBe('id.pdf');
    expect($doc->mime_type)->toBe('application/pdf');
});

test('member cannot upload a disallowed file type (docx) as an ID document', function () {
    $file = UploadedFile::fake()->create(
        'id.docx',
        200,
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    );

    uploadIdDocument($this->member, $this->idType, $file)
        ->assertHasErrors(['uploadFile']);

    expect(MemberDocument::where('user_id', $this->member->id)->count())->toBe(0);
});
