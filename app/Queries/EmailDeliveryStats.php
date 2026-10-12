<?php

namespace App\Queries;

use App\Models\EmailLog;

/**
 * Email log counts by send status and delivery status, from one grouped query.
 * Rows sent before delivery tracking existed have no delivery status ("unknown").
 */
class EmailDeliveryStats
{
    public const DELIVERY_CHIPS = ['delivered', 'opened', 'clicked', 'bounced', 'complained', 'failed', 'sent', 'unknown'];

    /**
     * @return array{total: int, by_status: array<string, int>, by_delivery: array<string, int>}
     */
    public function summary(): array
    {
        $rows = EmailLog::query()
            ->selectRaw("status, coalesce(delivery_status, 'unknown') as delivery, count(*) as aggregate")
            ->groupBy('status', 'delivery')
            ->get();

        $byStatus = [];
        $byDelivery = array_fill_keys(self::DELIVERY_CHIPS, 0);

        foreach ($rows as $row) {
            $byStatus[$row->status] = ($byStatus[$row->status] ?? 0) + (int) $row->aggregate;
            $byDelivery[$row->delivery] = ($byDelivery[$row->delivery] ?? 0) + (int) $row->aggregate;
        }

        return [
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'by_delivery' => $byDelivery,
        ];
    }
}
