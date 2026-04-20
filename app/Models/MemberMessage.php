<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberMessage extends Model
{
    use HasFactory;

    public const DIRECTION_ADMIN_TO_MEMBER = 'admin_to_member';
    public const DIRECTION_MEMBER_TO_ADMIN = 'member_to_admin';

    protected $fillable = [
        'user_id',
        'sent_by_user_id',
        'direction',
        'parent_id',
        'subject',
        'body',
        'email_sent_at',
        'read_at',
    ];

    protected $casts = [
        'email_sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * The member this conversation is with.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The user who actually sent this specific message (admin or the member themselves).
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    /**
     * Convenience: is this a message the admin sent to the member?
     */
    public function isFromAdmin(): bool
    {
        return $this->direction === self::DIRECTION_ADMIN_TO_MEMBER;
    }

    /**
     * Convenience: was this sent by the member to the admins?
     */
    public function isFromMember(): bool
    {
        return $this->direction === self::DIRECTION_MEMBER_TO_ADMIN;
    }

    public function markRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Messages the admin team hasn't processed yet (from members, unread).
     */
    public function scopeUnreadByAdmins($query)
    {
        return $query->where('direction', self::DIRECTION_MEMBER_TO_ADMIN)
            ->whereNull('read_at');
    }

    /**
     * Unread for this specific member (admin messages to them they haven't opened).
     */
    public function scopeUnreadByMember($query)
    {
        return $query->where('direction', self::DIRECTION_ADMIN_TO_MEMBER)
            ->whereNull('read_at');
    }

    /**
     * Roots of threads (no parent).
     */
    public function scopeThreadRoots($query)
    {
        return $query->whereNull('parent_id');
    }
}
