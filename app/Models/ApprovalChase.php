<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One automatic reminder sent to a member about an approval that is waiting
 * on them. At most one per stage per waiting spell.
 */
class ApprovalChase extends Model
{
    /** Days waiting on the member at which a reminder goes out. */
    public const STAGES = [7, 14, 21];

    protected $fillable = [
        'approval_id',
        'stage',
        'cycle_started_at',
        'sent_at',
        'email_log_id',
    ];

    protected function casts(): array
    {
        return [
            'stage' => 'integer',
            'cycle_started_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(Approval::class);
    }

    public function emailLog(): BelongsTo
    {
        return $this->belongsTo(EmailLog::class);
    }

    /**
     * The highest stage reached after $days waiting, or null before the first.
     */
    public static function dueStage(int $days): ?int
    {
        $due = null;

        foreach (self::STAGES as $stage) {
            if ($days >= $stage) {
                $due = $stage;
            }
        }

        return $due;
    }
}
