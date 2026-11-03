<?php

namespace App\Services\Billing;

use App\Helpers\DocumentHelper;
use App\Helpers\StorageHelper;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;

/**
 * PDF renderer for Invoice rows.
 *
 * Deliberately kept separate from PdfDocumentRenderer so this one can stay
 * focused on the invoice template, cache the output path back onto the
 * Invoice row, and short-circuit on later downloads. The underlying
 * strategy (Gotenberg first, DomPDF fallback) mirrors what the certificate
 * pipeline already does so we have one PDF stack to debug in production.
 */
class InvoiceRenderer
{
    protected string $disk;

    protected string $gotenbergUrl;

    public function __construct()
    {
        $this->disk = StorageHelper::getPrivateDisk();
        $this->gotenbergUrl = env('GOTENBERG_URL', 'http://gotenberg:3000');
    }

    /**
     * Render the invoice to the private disk if we haven't already, and
     * return the resulting path. Idempotent — second call just returns the
     * cached path.
     */
    public function render(Invoice $invoice): string
    {
        if ($invoice->pdf_path && Storage::disk($this->disk)->exists($invoice->pdf_path)) {
            return $invoice->pdf_path;
        }

        $path = sprintf(
            'invoices/%s/%s.pdf',
            $invoice->issued_at ? $invoice->issued_at->format('Y') : date('Y'),
            $invoice->number,
        );

        $html = view('documents.invoices.standard', [
            'invoice' => $invoice,
            'logo_url' => DocumentHelper::getLogoUrl(),
        ])->render();

        if (! $this->renderWithGotenberg($html, $path)) {
            $this->renderWithDomPdf($invoice, $path);
        }

        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    private function renderWithGotenberg(string $html, string $outputPath): bool
    {
        try {
            $response = Http::timeout(25)
                ->asMultipart()
                ->post($this->gotenbergUrl.'/forms/chromium/convert/html', [
                    ['name' => 'files', 'contents' => $html, 'filename' => 'index.html'],
                    ['name' => 'printBackground', 'contents' => 'true'],
                    ['name' => 'marginTop', 'contents' => '0.4'],
                    ['name' => 'marginBottom', 'contents' => '0.4'],
                    ['name' => 'marginLeft', 'contents' => '0.4'],
                    ['name' => 'marginRight', 'contents' => '0.4'],
                    ['name' => 'preferCssPageSize', 'contents' => 'true'],
                ]);

            if (! $response->successful() || strlen($response->body()) < 100) {
                Log::warning('Invoice Gotenberg render failed', [
                    'status' => $response->status(),
                    'body_prefix' => substr($response->body(), 0, 200),
                ]);

                return false;
            }

            Storage::disk($this->disk)->put($outputPath, $response->body());

            return true;
        } catch (\Throwable $e) {
            Log::warning('Invoice Gotenberg unavailable, falling back to DomPDF', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function renderWithDomPdf(Invoice $invoice, string $outputPath): void
    {
        Pdf::view('documents.invoices.standard', [
            'invoice' => $invoice,
            'logo_url' => DocumentHelper::getLogoUrl(),
        ])
            ->driver('dompdf')
            ->format('a4')
            ->disk($this->disk)
            ->save($outputPath);
    }
}
