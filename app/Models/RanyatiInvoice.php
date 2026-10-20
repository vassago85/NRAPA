<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RanyatiInvoice extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CREATED = 'created';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'period',
        'hosting_fee',
        'member_fee',
        'new_members',
        'renewals',
        'total',
        'invoice_ninja_id',
        'invoice_number',
        'status',
        'error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'hosting_fee' => 'decimal:2',
            'member_fee' => 'decimal:2',
            'new_members' => 'integer',
            'renewals' => 'integer',
            'total' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }

    public function memberCount(): int
    {
        return $this->new_members + $this->renewals;
    }
}
