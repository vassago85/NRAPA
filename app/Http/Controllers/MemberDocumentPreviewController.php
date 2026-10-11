<?php

namespace App\Http\Controllers;

use App\Helpers\StorageHelper;
use App\Models\MemberDocument;
use Illuminate\Support\Facades\Storage;

/**
 * Streams member uploads through the app (avoids R2 signed-URL issues) for the owning member and staff.
 */
class MemberDocumentPreviewController extends Controller
{
    public function __invoke(MemberDocument $document)
    {
        $user = auth()->user();

        if ($document->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        $storage = Storage::disk(StorageHelper::getPrivateDisk());

        if (! $storage->exists($document->file_path)) {
            abort(404);
        }

        return response()->stream(function () use ($storage, $document) {
            $stream = $storage->readStream($document->file_path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $document->mime_type,
            'Content-Disposition' => 'inline; filename="'.$document->original_filename.'"',
        ]);
    }
}
