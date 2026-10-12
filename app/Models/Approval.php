<?php

namespace App\Models;

use App\Contracts\Approvable;
use App\Enums\ApprovalState;
use App\Enums\ApprovalType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;

/**
 * One row per approvable record (payment, document, membership, activity,
 * endorsement, calibre request). The queue reads this table only; it never
 * asks the concrete models what is pending.
 *
 * @property ApprovalType $type
 * @property ApprovalState $state
 */
class Approval extends Model
{
    public const SLA_HOURS = 48;

    public const CACHE_KEY_SIDEBAR = 'approvals_waiting_on_admin_total';

    protected $fillable = [
        'approvable_type',
        'approvable_id',
        'user_id',
        'type',
        'state',
        'submitted_at',
        'decided_at',
        'decided_by',
        'decision_note',
        'assigned_to',
        'requires_second_approval',
        'first_approved_by',
        'first_approved_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => ApprovalType::class,
            'state' => ApprovalState::class,
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
            'first_approved_at' => 'datetime',
            'requires_second_approval' => 'boolean',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Approval $approval) {
            if ($approval->wasChanged('state') || $approval->wasRecentlyCreated) {
                self::flushCountCaches();
            }
        });

        static::deleted(fn () => self::flushCountCaches());
    }

    public static function flushCountCaches(): void
    {
        Cache::forget(self::CACHE_KEY_SIDEBAR);
        Cache::forget('sidebar_pending_total');
    }

    // ===== Relationships =====

    /**
     * @return MorphTo<Model&Approvable, $this>
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function firstApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_approved_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function chases(): HasMany
    {
        return $this->hasMany(ApprovalChase::class);
    }

    // ===== Scopes =====

    public function scopeOfType(Builder $query, ApprovalType|string $type): Builder
    {
        return $query->where('type', $type instanceof ApprovalType ? $type->value : $type);
    }

    public function scopeInState(Builder $query, ApprovalState $state): Builder
    {
        return $query->where('state', $state->value);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('state', ApprovalState::openValues());
    }

    public function scopeWaitingOnAdmin(Builder $query): Builder
    {
        return $query->where('state', ApprovalState::WaitingOnAdmin->value);
    }

    public function scopeWaitingOnMember(Builder $query): Builder
    {
        return $query->where('state', ApprovalState::WaitingOnMember->value);
    }

    public function scopeForApprovable(Builder $query, Model $model): Builder
    {
        return $query->where('approvable_type', $model->getMorphClass())
            ->where('approvable_id', $model->getKey());
    }

    // ===== Age / SLA =====

    public function getWaitingHoursAttribute(): int
    {
        $end = $this->state === ApprovalState::WaitingOnAdmin || ! $this->decided_at
            ? now()
            : $this->decided_at;

        return (int) floor($this->submitted_at->diffInMinutes($end, true) / 60);
    }

    /**
     * neutral under 24h, amber 24-72h, red over 72h.
     */
    public function ageBand(): string
    {
        $hours = $this->waiting_hours;

        return match (true) {
            $hours > 72 => 'red',
            $hours >= 24 => 'amber',
            default => 'neutral',
        };
    }

    public function ageTextClasses(): string
    {
        return match ($this->ageBand()) {
            'red' => 'text-red-800 dark:text-red-300',
            'amber' => 'text-amber-700 dark:text-amber-300',
            default => 'text-zinc-600 dark:text-zinc-400',
        };
    }

    public function ageLabel(): string
    {
        $hours = $this->waiting_hours;

        if ($hours < 1) {
            return 'just now';
        }

        if ($hours < 48) {
            return $hours.' '.str('hour')->plural($hours);
        }

        $days = intdiv($hours, 24);

        return $days.' '.str('day')->plural($days);
    }

    /**
     * Start of the current waiting-on-member spell.
     */
    public function waitingSince(): ?\Carbon\CarbonInterface
    {
        $since = $this->metaValue('waiting_since');

        return $since ? \Illuminate\Support\Carbon::parse($since) : ($this->updated_at ?? $this->submitted_at);
    }

    /**
     * "2 emails, last chased 3 days ago". Needs withCount('chases') and
     * withMax('chases', 'sent_at') on the query.
     */
    public function chaseSummary(): string
    {
        $count = (int) ($this->chases_count ?? 0);

        if ($count === 0) {
            return 'Not chased yet';
        }

        $last = $this->chases_max_sent_at ? \Illuminate\Support\Carbon::parse($this->chases_max_sent_at)->diffForHumans() : null;

        return $count.' '.str('email')->plural($count).($last ? ', last chased '.$last : '');
    }

    public function breachesSla(): bool
    {
        return $this->state === ApprovalState::WaitingOnAdmin
            && $this->waiting_hours >= self::SLA_HOURS;
    }

    // ===== Helpers =====

    public function isOpen(): bool
    {
        return $this->state->isOpen();
    }

    public function awaitsSecondSignature(): bool
    {
        return $this->requires_second_approval
            && $this->first_approved_by !== null
            && $this->state === ApprovalState::WaitingOnAdmin;
    }

    /**
     * Why $actor cannot approve this item right now, or null when they can.
     */
    public function approvalBlockedReason(User $actor): ?string
    {
        return match (true) {
            ! $this->state->isOpen() => 'Already '.strtolower($this->state->label()).($this->decider ? ' by '.$this->decider->name : '').($this->decided_at ? ' '.$this->decided_at->diffForHumans() : '').'.',
            $this->state === ApprovalState::WaitingOnMember => 'Waiting on the member.',
            (int) $this->user_id === (int) $actor->id => 'You cannot decide your own submission.',
            $this->requires_second_approval && (int) $this->first_approved_by === (int) $actor->id => 'You gave the first signature. A different admin must give the second.',
            default => null,
        };
    }

    public function reviewUrl(): string
    {
        return route('admin.review.show', $this);
    }

    /**
     * The record's own detail page, for screens outside the review workspace.
     */
    public function detailUrl(): ?string
    {
        $approvable = $this->approvable;

        if (! $approvable) {
            return null;
        }

        return match ($this->type) {
            ApprovalType::Payment => $approvable->membership ? route('admin.approvals.show', $approvable->membership) : null,
            ApprovalType::Membership => route('admin.approvals.show', $approvable),
            ApprovalType::Document => route('admin.documents.show', $approvable),
            ApprovalType::Activity => route('admin.activities.show', $approvable),
            ApprovalType::Endorsement => route('admin.endorsements.show', $approvable),
            ApprovalType::Calibre => route('admin.calibre-requests.index'),
        };
    }

    public function lastDecision(): ?string
    {
        return $this->meta['last_decision'] ?? null;
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->meta ?? [], $key, $default);
    }

    public function withMeta(array $values): static
    {
        $this->meta = array_replace($this->meta ?? [], $values);

        return $this;
    }
}
