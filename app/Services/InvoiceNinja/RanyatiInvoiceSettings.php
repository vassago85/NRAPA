<?php

namespace App\Services\InvoiceNinja;

use App\Models\SystemSetting;

class RanyatiInvoiceSettings
{
    public const DEFAULT_URL = 'https://invoice.charsley.co.za';

    /**
     * National Rifle and Pistol Association in Invoice Ninja (client 0001).
     */
    public const DEFAULT_CLIENT_ID = 'VolejRejNm';

    public const DEFAULT_HOSTING_FEE = 1500;

    public const DEFAULT_MEMBER_FEE = 100;

    public const PAYMENT_TERMS_DAYS = 30;

    public function enabled(): bool
    {
        $setting = SystemSetting::query()->where('key', 'invoice_ninja_enabled')->first();

        if (! $setting) {
            return false;
        }

        return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
    }

    public function apiUrl(): string
    {
        $url = rtrim((string) SystemSetting::get('invoice_ninja_api_url', self::DEFAULT_URL), '/');
        $url = preg_replace('#/api/v1$#', '', $url) ?? $url;

        return $url !== '' ? $url : self::DEFAULT_URL;
    }

    public function apiToken(): string
    {
        return trim((string) SystemSetting::get('invoice_ninja_api_token', ''));
    }

    public function clientId(): string
    {
        return trim((string) SystemSetting::get('invoice_ninja_client_id', self::DEFAULT_CLIENT_ID));
    }

    public function hostingFee(): float
    {
        return round((float) SystemSetting::get('invoice_ninja_hosting_fee', self::DEFAULT_HOSTING_FEE), 2);
    }

    public function memberFee(): float
    {
        return round((float) SystemSetting::get('invoice_ninja_member_fee', self::DEFAULT_MEMBER_FEE), 2);
    }

    public function isConfigured(): bool
    {
        return $this->apiUrl() !== ''
            && $this->apiToken() !== ''
            && $this->clientId() !== '';
    }
}
