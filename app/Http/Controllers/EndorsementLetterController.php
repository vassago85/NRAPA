<?php

namespace App\Http\Controllers;

use App\Contracts\DocumentRenderer;
use App\Helpers\DocumentHelper;
use App\Helpers\StorageHelper;
use App\Models\EndorsementRequest;
use Illuminate\Support\Facades\Storage;

/**
 * Serves endorsement letters to the requesting member and to staff (admin and above).
 */
class EndorsementLetterController extends Controller
{
    private const TEMPLATE = 'documents.letters.endorsement';

    public function preview(EndorsementRequest $request)
    {
        $this->authorizeAccess($request);

        $request->loadMissing([
            'user',
            'user.activeMembership',
            'firearm',
            'firearm.firearmCalibre',
            'firearm.firearmMake',
            'firearm.firearmModel',
            'components',
        ]);

        return view(self::TEMPLATE, [
            'request' => $request,
            'user' => $request->user,
            'firearm' => $request->firearm,
            'membership' => $request->user->activeMembership,
            'logo_url' => DocumentHelper::getLogoUrl(),
        ]);
    }

    /**
     * Streams the cached PDF, rendering it first when it is missing from storage.
     */
    public function letter(EndorsementRequest $request, DocumentRenderer $renderer)
    {
        $this->authorizeAccess($request);

        if (! $request->isIssued() && ! $request->isApproved()) {
            abort(404, 'Endorsement letter not found.');
        }

        $storage = Storage::disk(StorageHelper::getPrivateDisk());
        $filePath = $request->letter_file_path;

        if (! $filePath || ! $storage->exists($filePath)) {
            $filePath = $renderer->renderEndorsementLetter($request, self::TEMPLATE);
            $request->update(['letter_file_path' => $filePath]);
        }

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
            // Letters are regenerated after admin corrections (e.g. serial numbers),
            // so browsers and proxies must never serve a stale copy.
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function authorizeAccess(EndorsementRequest $request): void
    {
        $user = auth()->user();

        if ($request->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }
    }
}
