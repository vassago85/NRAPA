<?php

use App\Models\Approval;
use App\Models\DocumentType;
use App\Models\MemberDocument;
use App\Models\ShootingActivity;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\ApprovalFixtures;

beforeEach(function () {
    Storage::fake('local');
    ApprovalFixtures::stubDocumentRendering();
    $this->admin = ApprovalFixtures::admin();
});

function evidenceDocument(string $filename, string $mime, string $path): array
{
    $member = ApprovalFixtures::member();
    ApprovalFixtures::activeMembership($member);

    Storage::disk('local')->put($path, 'file-bytes');

    $document = MemberDocument::create([
        'user_id' => $member->id,
        'document_type_id' => DocumentType::firstOrCreate(
            ['slug' => 'activity-evidence'],
            ['name' => 'Activity Evidence', 'is_active' => true, 'sort_order' => 100],
        )->id,
        'file_path' => $path,
        'original_filename' => $filename,
        'mime_type' => $mime,
        'file_size' => 10,
        'status' => 'pending',
        'uploaded_at' => now(),
    ]);

    $activity = ShootingActivity::create([
        'user_id' => $member->id,
        'activity_date' => now()->subWeek(),
        'location' => 'Farm Steinfurt',
        'status' => 'pending',
        'evidence_document_id' => $document->id,
    ]);

    return [$document, Approval::forApprovable($activity)->firstOrFail()];
}

test('a jpeg stored with a generic type is shown in the review pane and served as an image', function () {
    [$document, $approval] = evidenceDocument(
        'Bosvark Kudu 2026.jpg',
        'application/octet-stream',
        'member-documents/kudu.jpg',
    );

    Livewire::actingAs($this->admin)
        ->test('pages::admin.review.index', ['approval' => $approval])
        ->assertSee('Bosvark Kudu 2026.jpg')
        ->assertSeeHtml('data-evidence-kind="image"');

    $this->actingAs($this->admin)
        ->get(route('documents.preview', $document))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('Content-Disposition', 'inline; filename="Bosvark Kudu 2026.jpg"');
});

test('a pdf is still shown inline in the review pane', function () {
    [$document, $approval] = evidenceDocument(
        'range-card.pdf',
        'application/pdf',
        'member-documents/range-card.pdf',
    );

    Livewire::actingAs($this->admin)
        ->test('pages::admin.review.index', ['approval' => $approval])
        ->assertSeeHtml('data-evidence-kind="pdf"');

    $this->actingAs($this->admin)
        ->get(route('documents.preview', $document))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

test('a png and a gif are served as images', function () {
    [$png] = evidenceDocument('score.png', 'application/octet-stream', 'member-documents/score.png');
    [$gif] = evidenceDocument('target.gif', 'image/gif', 'member-documents/target.gif');

    $this->actingAs($this->admin)
        ->get(route('documents.preview', $png))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');

    $this->actingAs($this->admin)
        ->get(route('documents.preview', $gif))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/gif');
});
