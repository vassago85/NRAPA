<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailLog extends Model
{
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
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
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
