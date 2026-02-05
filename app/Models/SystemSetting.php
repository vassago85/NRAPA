<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    protected function casts(): array
    {
        // Don't cast value as array by default - handle it in get() method based on type
        return [];
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("system_setting.{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        // Handle different types
        $value = $setting->value;
        
        return match ($setting->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value, // string type or default - return as-is
        };
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $type = 'string', ?string $group = null, ?string $description = null): static
    {
        // Convert value to appropriate format based on type
        $storedValue = match ($type) {
            'json', 'array' => is_array($value) ? json_encode($value) : $value,
            default => (string) $value, // Store as string for string, boolean, integer types
        };

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("system_setting.{$key}");

        return $setting;
    }

    /**
     * Get all settings in a group.
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get bank account settings.
     */
    public static function getBankAccount(): array
    {
        return [
            'bank_name' => static::get('bank_name', ''),
            'account_name' => static::get('bank_account_name', ''),
            'account_number' => static::get('bank_account_number', ''),
            'branch_code' => static::get('bank_branch_code', ''),
            'account_type' => static::get('bank_account_type', ''),
            'reference_prefix' => static::get('bank_reference_prefix', 'NRAPA'),
        ];
    }

    /**
     * Get email/SMTP settings.
     */
    public static function getEmailSettings(): array
    {
        return [
            'mail_mailer' => static::get('mail_mailer', 'smtp'),
            'mail_host' => static::get('mail_host', ''),
            'mail_port' => static::get('mail_port', 587),
            'mail_username' => static::get('mail_username', ''),
            'mail_password' => static::get('mail_password', ''),
            'mail_encryption' => static::get('mail_encryption', 'tls'),
            'mail_from_address' => static::get('mail_from_address', ''),
            'mail_from_name' => static::get('mail_from_name', 'NRAPA'),
        ];
    }
}
