<?php

namespace App\Services;

class ErrorLogReport
{
    /**
     * @param  list<array{fingerprint: string, level: string, count: int, sample: string}>  $groups
     */
    public function __construct(
        public readonly int $total,
        public readonly int $uniqueCount,
        public readonly array $groups,
    ) {}

    public function hasErrors(): bool
    {
        return $this->total > 0;
    }

    public function highestLevel(): string
    {
        $rank = ['ERROR' => 1, 'CRITICAL' => 2, 'ALERT' => 3, 'EMERGENCY' => 4];
        $best = 'ERROR';

        foreach ($this->groups as $group) {
            $level = strtoupper($group['level']);
            if (($rank[$level] ?? 0) > ($rank[$best] ?? 0)) {
                $best = $level;
            }
        }

        return $best;
    }

    public function digest(int $hours, int $limit = 8): string
    {
        $lines = [
            "{$this->total} error(s) in the last {$hours}h ({$this->uniqueCount} unique)",
            '',
        ];

        foreach (array_slice($this->groups, 0, $limit) as $group) {
            $level = strtoupper($group['level']);
            $lines[] = "[{$level}] {$group['fingerprint']} ×{$group['count']}";
        }

        if ($this->uniqueCount > $limit) {
            $remaining = $this->uniqueCount - $limit;
            $lines[] = "... and {$remaining} more unique error(s)";
        }

        return implode("\n", $lines);
    }
}
