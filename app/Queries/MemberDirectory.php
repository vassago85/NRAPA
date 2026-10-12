<?php

namespace App\Queries;

use App\Support\Membership\MembershipStats;
use Illuminate\Database\Eloquent\Builder;

/**
 * The members index query. The list, its bulk actions and its exports all
 * select through here so "select all matching" acts on exactly what is shown.
 */
class MemberDirectory
{
    public const PAGE_SIZES = [20, 50, 100];

    public static function query(string $segment = 'all', string $search = ''): Builder
    {
        $segment = in_array($segment, MembershipStats::SEGMENTS, true) ? $segment : 'all';
        $search = trim($search);

        return MembershipStats::applySegment(MembershipStats::countableUsers(), $segment)
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $q) => $q
                ->where('users.name', 'like', "%{$search}%")
                ->orWhere('users.email', 'like', "%{$search}%")
                ->orWhere('users.id_number', 'like', "%{$search}%")
                ->orWhereHas('memberships', fn (Builder $mq) => $mq
                    ->where('membership_number', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%"))));
    }
}
