<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulletSource extends Model
{
    protected $fillable = [
        'bullet_id',
        'source_type',
        'source_url',
        'captured_at',
        'raw_excerpt',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function bullet(): BelongsTo
    {
        return $this->belongsTo(Bullet::class);
    }

    public static function sourceTypes(): array
    {
        return [
            'product_page' => 'Product Page',
            'bc_table' => 'BC Table',
            'catalog_pdf' => 'Catalog PDF',
        ];
    }
}
