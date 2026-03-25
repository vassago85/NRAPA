<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SageNetworkService
{
    protected function env(): string
    {
        return config('sage.environment', 'sandbox');
    }

    protected function baseUrl(): string
    {
        return config("sage.{$this->env()}.api_base");
    }

    protected function apiKey(): ?string
    {
        return SystemSetting::get('sage_api_key');
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withHeaders(['Api-Key' => $this->apiKey()])
            ->acceptJson()
            ->timeout(30);
    }

    public static function isEnabled(): bool
    {
        $config = SystemSetting::getSageConfig();

        return $config['enabled'] && ! empty($config['api_key']);
    }

    /**
     * Verify the API key works by calling the Status endpoint.
     */
    public function testConnection(): array
    {
        try {
            $response = $this->http()->get('/api/v1/Status');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => "HTTP {$response->status()}: {$response->body()}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a persistent API key using a bearer token from OAuth.
     */
    public function createApiKey(string $bearerToken, string $name = 'NRAPA Integration'): ?string
    {
        try {
            $response = Http::baseUrl($this->baseUrl())
                ->withToken($bearerToken)
                ->acceptJson()
                ->timeout(30)
                ->post('/api/v1/ApiKeys', [
                    'name' => $name,
                    'expiresDate' => null,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['apiKey'] ?? null;
            }

            Log::error('Sage: failed to create API key', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Sage: exception creating API key', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Create or update a Company (Customer) in Sage for the given user.
     * Uses erpKey for deduplication.
     */
    public function upsertCompany(User $user): ?string
    {
        if ($user->sage_company_id) {
            return $user->sage_company_id;
        }

        $existing = $this->findCompanyByErpKey("nrapa-user-{$user->id}");
        if ($existing) {
            $companyId = $existing['companyId'];
            $user->update(['sage_company_id' => $companyId]);

            return $companyId;
        }

        try {
            $response = $this->http()->post('/api/v1/Companies', [
                $this->mapUserToCompany($user),
            ]);

            if ($response->successful()) {
                $companies = $response->json();
                $companyId = $companies[0]['companyId'] ?? null;

                if ($companyId) {
                    $user->update(['sage_company_id' => $companyId]);
                    $this->createContact($companyId, $user);
                }

                return $companyId;
            }

            Log::error('Sage: failed to create company', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Sage: exception creating company', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function findCompanyByErpKey(string $key): ?array
    {
        try {
            $response = $this->http()->get('/api/v1/Companies/query', [
                'filter' => "erpKey eq '{$key}'",
                'pageSize' => 1,
            ]);

            if ($response->successful()) {
                $records = $response->json('records') ?? [];

                return count($records) > 0 ? $records[0] : null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Sage: exception querying company', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Create an AR Invoice in Sage for the given membership.
     */
    public function createInvoice(Membership $membership): ?array
    {
        $user = $membership->user;
        $companyId = $user->sage_company_id;

        if (! $companyId) {
            Log::warning('Sage: cannot create invoice without sage_company_id', [
                'membership_id' => $membership->id,
            ]);

            return null;
        }

        try {
            $response = $this->http()->post('/api/v1/Invoices', [
                $this->mapMembershipToInvoice($membership, $companyId),
            ]);

            if ($response->successful()) {
                $invoices = $response->json();
                $invoice = $invoices[0] ?? null;

                if ($invoice) {
                    $membership->update([
                        'sage_invoice_id' => $invoice['invoiceId'] ?? null,
                        'sage_invoice_number' => $invoice['referenceCode'] ?? $membership->payment_reference,
                    ]);
                }

                return $invoice;
            }

            Log::error('Sage: failed to create invoice', [
                'membership_id' => $membership->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Sage: exception creating invoice', [
                'membership_id' => $membership->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send (deliver) an e-invoice via Sage's Document Flow Service.
     */
    public function sendInvoice(string $invoiceId): bool
    {
        try {
            $response = $this->http()->post("/api/v1/Invoices/{$invoiceId}/send");

            if ($response->status() === 202 || $response->successful()) {
                return true;
            }

            Log::error('Sage: failed to send invoice', [
                'invoice_id' => $invoiceId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Sage: exception sending invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function createContact(string $companyId, User $user): void
    {
        try {
            $nameParts = explode(' ', $user->name, 2);

            $this->http()->post('/api/v1/Contacts', [
                [
                    'companyId' => $companyId,
                    'contactName' => $user->name,
                    'firstName' => $nameParts[0] ?? '',
                    'lastName' => $nameParts[1] ?? '',
                    'emailAddress' => $user->email,
                    'phone' => $user->phone ?? '',
                    'isPrimary' => true,
                    'isActive' => true,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('Sage: failed to create contact', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function mapUserToCompany(User $user): array
    {
        return [
            'companyName' => $user->name,
            'companyType' => 'Customer',
            'defaultCurrencyCode' => 'ZAR',
            'isActive' => true,
            'emailAddress' => $user->email,
            'phoneNumber' => $user->phone ?? '',
        ];
    }

    protected function mapMembershipToInvoice(Membership $membership, string $companyId): array
    {
        $typeName = $membership->type?->name ?? 'Membership';
        $amount = $membership->amount_due;

        return [
            'customerId' => $companyId,
            'invoiceTypeCode' => 'AR Invoice',
            'invoiceStatusCode' => 'Open',
            'currencyCode' => 'ZAR',
            'referenceCode' => $membership->payment_reference,
            'invoiceDate' => ($membership->activated_at ?? now())->toDateString(),
            'totalAmount' => $amount,
            'outstandingBalanceAmount' => $amount,
            'lines' => [
                [
                    'description' => "NRAPA {$typeName} — {$membership->membership_number}",
                    'unitPrice' => $amount,
                    'quantity' => 1.0,
                    'totalAmount' => $amount,
                    'lineNumber' => '1',
                ],
            ],
        ];
    }
}
