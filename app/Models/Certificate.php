<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Certificate extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'membership_id',
        'certificate_type_id',
        'certificate_number',
        'issued_at',
        'issued_by',
        'valid_from',
        'valid_until',
        'revoked_at',
        'revoked_by',
        'revocation_reason',
        'file_path',
        'qr_code',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Certificate $certificate) {
            if (empty($certificate->uuid)) {
                $certificate->uuid = (string) Str::uuid();
            }
            if (empty($certificate->certificate_number)) {
                $certificate->certificate_number = static::generateCertificateNumber();
            }
            if (empty($certificate->qr_code)) {
                $certificate->qr_code = static::generateQrCode();
            }
            if (empty($certificate->issued_at)) {
                $certificate->issued_at = now();
            }
            if (empty($certificate->valid_from)) {
                $certificate->valid_from = now()->toDateString();
            }

            // Calculate valid_until based on certificate type (only if not already set)
            if (empty($certificate->valid_until) && $certificate->certificate_type_id) {
                try {
                    $certificateType = CertificateType::find($certificate->certificate_type_id);
                    if ($certificateType) {
                        // Ensure we have a Carbon instance for date calculations
                        $validFrom = $certificate->valid_from 
                            ? Carbon::parse($certificate->valid_from)
                            : Carbon::now();
                        
                        $calculated = $certificateType->calculateValidUntilDate($validFrom);
                        if ($calculated) {
                            // Convert to date string for storage
                            $certificate->valid_until = $calculated instanceof Carbon 
                                ? $calculated->format('Y-m-d') 
                                : (is_string($calculated) ? $calculated : $calculated->format('Y-m-d'));
                        }
                    }
                } catch (\Exception $e) {
                    // If calculation fails, leave valid_until as null (indefinite)
                    // This prevents errors during test member generation
                    \Log::warning('Failed to calculate certificate valid_until', [
                        'certificate_type_id' => $certificate->certificate_type_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Generate a unique certificate number.
     */
    public static function generateCertificateNumber(): string
    {
        $year = now()->format('Y');
        $prefix = 'CERT';

        do {
            $number = $prefix . '-' . $year . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (static::where('certificate_number', $number)->exists());

        return $number;
    }

    /**
     * Generate a unique QR code.
     */
    public static function generateQrCode(): string
    {
        do {
            $code = Str::random(32);
        } while (static::where('qr_code', $code)->exists());

        return $code;
    }

    /**
     * Get the user that owns the certificate.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the membership associated with the certificate.
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    /**
     * Get the certificate type.
     */
    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class);
    }

    /**
     * Get the admin who issued the certificate.
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Get the admin who revoked the certificate.
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    // ===== Status Checks =====

    /**
     * Check if the certificate is valid.
     */
    public function isValid(): bool
    {
        // Not revoked
        if ($this->revoked_at !== null) {
            return false;
        }

        // Within validity period
        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        // Validity has started
        if ($this->valid_from->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the certificate is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if the certificate is expired.
     */
    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    // ===== Actions =====

    /**
     * Revoke the certificate.
     */
    public function revoke(User $admin, string $reason): void
    {
        $this->update([
            'revoked_at' => now(),
            'revoked_by' => $admin->id,
            'revocation_reason' => $reason,
        ]);
    }

    /**
     * Get the verification URL for the QR code.
     */
    public function getVerificationUrl(): string
    {
        return route('certificates.verify', ['qr_code' => $this->qr_code]);
    }

    // ===== Scopes =====

    /**
     * Scope to only valid certificates.
     */
    public function scopeValid($query)
    {
        return $query->whereNull('revoked_at')
            ->where('valid_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>', now());
            });
    }

    /**
     * Scope to only revoked certificates.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }
}
