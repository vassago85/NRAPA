<?php

namespace App\Http\Controllers;

use App\Helpers\StorageHelper;
use App\Models\UserFirearm;
use Illuminate\Support\Facades\Storage;

/**
 * Streams a Virtual Safe licence document to the firearm's owner and to staff (admin and above).
 */
class FirearmLicenceController extends Controller
{
    public function __invoke(UserFirearm $firearm)
    {
        $user = auth()->user();

        if ($firearm->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        $storage = Storage::disk(StorageHelper::getPrivateDisk());
        $path = $firearm->license_document_path;

        if (! $path || ! $storage->exists($path)) {
            abort(404, 'No licence document uploaded.');
        }

        $filename = 'licence-'.($firearm->license_number ?: $firearm->uuid).'.'.pathinfo($path, PATHINFO_EXTENSION);

        return response()->stream(function () use ($storage, $path) {
            $stream = $storage->readStream($path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $storage->mimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.str_replace(['"', '/', '\\'], '-', $filename).'"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
