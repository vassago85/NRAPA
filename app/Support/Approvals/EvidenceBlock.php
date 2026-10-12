<?php

namespace App\Support\Approvals;

/**
 * What the review workspace renders in its middle pane for one approvable:
 * a per-type Blade partial plus the data it needs.
 */
final readonly class EvidenceBlock
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $view,
        public string $title,
        public array $data = [],
        public ?string $badge = null,
        public ?string $badgeTone = null,
    ) {}
}
