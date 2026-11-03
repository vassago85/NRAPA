<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Membership;
use App\Services\Billing\InvoiceIssuer;
use App\Services\Billing\InvoiceNotIssuableException;
use App\Services\Billing\InvoiceRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin-only invoice downloads for Phase 1.
 *
 * Two entry points share the same final step (render-if-missing + stream):
 *
 *  - download($invoice)          — invoice already exists (admin clicked its
 *                                   row on Billing or re-downloaded it).
 *  - downloadForMembership($m)   — invoice may not exist yet; mint lazily via
 *                                   InvoiceIssuer so admins can download
 *                                   straight from the Payments Received list
 *                                   without first running the backfill.
 *
 * Member-facing downloads are a later phase; the route group this controller
 * sits in is already behind the `admin` middleware.
 */
class InvoiceDownloadController extends Controller
{
    public function __construct(
        private readonly InvoiceIssuer $issuer,
        private readonly InvoiceRenderer $renderer,
    ) {}

    public function download(Request $request, Invoice $invoice): StreamedResponse
    {
        return $this->streamInvoice($request, $invoice);
    }

    public function downloadForMembership(Request $request, Membership $membership): StreamedResponse
    {
        try {
            $invoice = $this->issuer->forMembership($membership, $request->user());
        } catch (InvoiceNotIssuableException $e) {
            abort(422, $e->getMessage());
        }

        return $this->streamInvoice($request, $invoice);
    }

    private function streamInvoice(Request $request, Invoice $invoice): StreamedResponse
    {
        $disk = StorageHelper::getPrivateDisk();

        if (! $invoice->pdf_path || ! Storage::disk($disk)->exists($invoice->pdf_path)) {
            $this->renderer->render($invoice->refresh());
            $invoice->refresh();
        }

        AuditLog::log(
            'invoice.downloaded',
            $invoice,
            null,
            ['number' => $invoice->number],
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
