<?php

namespace App\Models;

use App\Concerns\SyncsApproval;
use App\Contracts\Approvable;
use App\Enums\ApprovalDecision;
use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use App\Support\Approvals\Checks\CalibreNotDuplicate;
use App\Support\Approvals\EvidenceBlock;
use App\Support\Approvals\PreflightCheck;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibreRequest extends Model implements Approvable
{
    use SyncsApproval;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const CATEGORIES = ['handgun', 'rifle', 'shotgun', 'muzzleloader', 'historic'];

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'ignition_type',
        'saps_code',
        'aliases',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_notes',
        'calibre_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'aliases' => 'array',
    ];

    /**
     * Get the user who submitted the request.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the calibre created from this request.
     */
    public function calibre(): BelongsTo
    {
        return $this->belongsTo(FirearmCalibre::class, 'calibre_id');
    }

    // ===== Actions =====

    /**
     * Approve the request, creating the calibre (or linking an existing one
     * with the same normalized name).
     *
     * @param  array{name?: string, category?: string, ignition_type?: string, saps_code?: ?string, admin_notes?: ?string}  $overrides
     */
    public function approveAndCreateCalibre(User $admin, array $overrides = []): FirearmCalibre
    {
        $name = trim($overrides['name'] ?? $this->name);
        $category = $overrides['category'] ?? $this->category;
        $category = in_array($category, self::CATEGORIES, true) ? $category : 'rifle';
        $ignition = $overrides['ignition_type'] ?? $this->ignition_type ?? 'centerfire';
        $sapsCode = array_key_exists('saps_code', $overrides) ? ($overrides['saps_code'] ?: null) : $this->saps_code;

        $calibre = FirearmCalibre::where('normalized_name', FirearmCalibre::normalize($name))->first()
            ?? FirearmCalibre::create([
                'name' => $name,
                'normalized_name' => FirearmCalibre::normalize($name),
                'category' => $category,
                'ignition' => $ignition,
                'saps_code' => $sapsCode,
                'is_active' => true,
                'is_obsolete' => false,
                'is_wildcat' => false,
            ]);

        $this->update([
            'name' => $name,
            'category' => $category,
            'ignition_type' => $ignition,
            'saps_code' => $sapsCode,
            'status' => self::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => array_key_exists('admin_notes', $overrides) ? ($overrides['admin_notes'] ?: null) : $this->admin_notes,
            'calibre_id' => $calibre->id,
        ]);

        return $calibre;
    }

    public function rejectRequest(User $admin, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => $reason,
        ]);
    }

    public function exactMatch(): ?FirearmCalibre
    {
        return FirearmCalibre::where('normalized_name', FirearmCalibre::normalize((string) $this->name))->first();
    }

    /**
     * Likely matches by name, aliases and SAPS code.
     */
    public function likelyMatches(int $limit = 5): Collection
    {
        $terms = array_filter(array_merge([$this->name, $this->saps_code], (array) ($this->aliases ?? [])));

        if ($terms === []) {
            return new Collection;
        }

        return FirearmCalibre::query()
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere(fn ($inner) => $inner->search((string) $term));
                }
            })
            ->limit($limit)
            ->get();
    }

    // ===== Approvable =====

    public function approvalType(): ApprovalType
    {
        return ApprovalType::Calibre;
    }

    public function approvalStateFromModel(): ?ApprovalState
    {
        return $this->status === self::STATUS_PENDING && $this->user_id ? ApprovalState::WaitingOnAdmin : null;
    }

    public function approvalOutcomeFromModel(): ApprovalState
    {
        return match ($this->status) {
            self::STATUS_APPROVED => ApprovalState::Approved,
            self::STATUS_REJECTED => ApprovalState::Declined,
            default => ApprovalState::Withdrawn,
        };
    }

    public function approvalSubmittedAt(): ?CarbonInterface
    {
        return $this->created_at;
    }

    public function summaryLine(): string
    {
        return ($this->user?->name ?? 'Unknown member').' — new calibre '.$this->name;
    }

    public function detailLine(): string
    {
        return implode(' · ', array_filter([
            $this->category_label.' / '.$this->ignition_type_label,
            $this->saps_code ? 'SAPS '.$this->saps_code : null,
        ]));
    }

    public function detailWarning(): ?string
    {
        return $this->exactMatch() ? 'Already in the calibre list' : null;
    }

    public function evidence(): EvidenceBlock
    {
        return new EvidenceBlock(
            view: 'components.admin.evidence.calibre',
            title: 'Calibre request',
            data: [
                'request' => $this,
                'exactMatch' => $this->exactMatch(),
                'matches' => $this->likelyMatches(),
            ],
        );
    }

    public function preflightChecks(): array
    {
        return [
            $this->name && strlen(trim($this->name)) >= 2
                ? PreflightCheck::pass('calibre_named', 'Calibre named', $this->name)
                : PreflightCheck::fail('calibre_named', 'Calibre named', 'No usable calibre name'),
            CalibreNotDuplicate::check($this),
        ];
    }

    public function canBeApprovedInline(): bool
    {
        return false;
    }

    public function approvalConsequence(): string
    {
        return $this->exactMatch()
            ? 'Approving links the request to the existing calibre and writes an audit entry against your name.'
            : 'Approving adds '.$this->name.' to the calibre list for every member and writes an audit entry against your name.';
    }

    public function approveFromQueue(User $actor, ?string $note = null): void
    {
        $this->approveAndCreateCalibre($actor, $note ? ['admin_notes' => $note] : []);
    }

    public function declineFromQueue(User $actor, string $reason): void
    {
        $this->rejectRequest($actor, $reason);
    }

    public function requestChangesFromQueue(User $actor, array $items, ?string $note = null): void
    {
        $this->update(['admin_notes' => trim(implode('; ', $items).($note ? " — {$note}" : ''))]);
    }

    public function notifiesMemberOnDecision(ApprovalDecision $decision): bool
    {
        return false;
    }

    public function supersedesApprovableOf(Model $older): bool
    {
        return $older instanceof self;
    }

    // ===== Scopes =====

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    // ===== Accessors =====

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            self::STATUS_PENDING => ['color' => 'amber', 'text' => 'Pending'],
            self::STATUS_APPROVED => ['color' => 'green', 'text' => 'Approved'],
            self::STATUS_REJECTED => ['color' => 'red', 'text' => 'Rejected'],
            default => ['color' => 'zinc', 'text' => ucfirst($this->status)],
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'handgun' => 'Handgun',
            'rifle' => 'Rifle',
            'shotgun' => 'Shotgun',
            'muzzleloader' => 'Muzzleloader',
            'historic' => 'Historic',
            'other' => 'Other',
            default => ucfirst($this->category ?? 'Unknown'),
        };
    }

    public function getIgnitionTypeLabelAttribute(): string
    {
        return match ($this->ignition_type) {
            'rimfire' => 'Rimfire',
            'centerfire' => 'Centerfire',
            default => ucfirst($this->ignition_type ?? 'Unknown'),
        };
    }
}
