<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailLog extends Model
{
    /**
     * Forward-only delivery progression. A status only moves to a higher rank.
     */
    public const DELIVERY_RANK = [
        'queued' => 0,
        'sent' => 1,
        'delivered' => 2,
        'opened' => 3,
        'clicked' => 4,
    ];

    /**
     * Once reached, nothing overwrites these (a late "delivered" never hides a bounce).
     */
    public const FINAL_DELIVERY_STATUSES = ['failed', 'bounced', 'complained', 'unsubscribed'];

    public const DELIVERY_STATUSES = ['queued', 'sent', 'delivered', 'opened', 'clicked', 'failed', 'bounced', 'complained', 'unsubscribed'];

    protected $fillable = [
        'uuid',
        'user_id',
        'to_email',
        'to_name',
        'from_email',
        'from_name',
        'subject',
        'body',
        'mailable_class',
        'status',
        'error_message',
        'metadata',
        'sent_at',
        'provider_message_id',
        'delivery_status',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'bounce_code',
        'bounce_reason',
        'events',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'events' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'bounced_at' => 'datetime',
        ];
    }

    public static function canAdvanceDelivery(?string $from, string $to): bool
    {
        if ($from !== null && in_array($from, self::FINAL_DELIVERY_STATUSES, true)) {
            return false;
        }

        if (in_array($to, self::FINAL_DELIVERY_STATUSES, true)) {
            return true;
        }

        return (self::DELIVERY_RANK[$to] ?? -1) > (self::DELIVERY_RANK[$from] ?? -1);
    }

    /**
     * Mailgun reports message ids without the angle brackets Symfony keeps.
     */
    public static function normalizeMessageId(?string $id): ?string
    {
        $id = $id === null ? null : trim($id, " \t\n\r\0\x0B<>");

        return $id === '' ? null : $id;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (EmailLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Log an email.
     */
    public static function log(
        string $toEmail,
        string $subject,
        string $mailableClass,
        ?string $toName = null,
        ?int $userId = null,
        ?string $body = null,
        array $metadata = [],
        string $status = 'sent',
        ?string $errorMessage = null
    ): static {
        return static::create([
            'user_id' => $userId,
            'to_email' => $toEmail,
            'to_name' => $toName,
            'from_email' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'subject' => $subject,
            'body' => $body,
            'mailable_class' => $mailableClass,
            'status' => $status,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'sent_at' => $status === 'sent' ? now() : null,
        ]);
    }
}
