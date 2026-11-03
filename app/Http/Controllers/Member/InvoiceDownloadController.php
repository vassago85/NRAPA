<?php

namespace App\Http\Controllers\Member;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Membership;
use App\Services\Billing\InvoiceIssuer;
use App\Services\Billing\InvoiceNotIssuableException;
use App\Services\Billing\InvoiceRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Member-facing invoice download.
 *
 * Available as soon as a billable membership needs payment (new application
 * or renewal), and again after payment for a reprint of the same number.
 * Admin downloads stay on Admin\InvoiceDownloadController.
 */
class InvoiceDownloadController extends Controller
{
    public function __construct(
        private readonly InvoiceIssuer $issuer,
        private readonly InvoiceRenderer $renderer,
    ) {}

    public function __invoke(Request $request, Membership $membership): StreamedResponse
    {
        if ((int) $membership->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        try {
            $invoice = $this->issuer->forMembership($membership, $request->user());
        } catch (InvoiceNotIssuableException $e) {
            abort(422, $e->getMessage());
        }

        $disk = StorageHelper::getPrivateDisk();

        if (! $invoice->pdf_path || ! Storage::disk($disk)->exists($invoice->pdf_path)) {
            $this->renderer->render($invoice->refresh());
            $invoice->refresh();
        }

        AuditLog::log(
            'invoice.downloaded',
            $invoice,
            null,
            ['number' => $invoice->number, 'via' => 'member'],
            $request->user(),
        );

        return Storage::disk($disk)->download(
            $invoice->pdf_path,
            $invoice->number.'.pdf',
            [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, max-age=0, no-store',
            ],
        );
    }
}
