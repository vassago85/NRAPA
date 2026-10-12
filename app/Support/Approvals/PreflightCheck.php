<?php

namespace App\Support\Approvals;

/**
 * One line of the "Before approving" checklist. Pass/fail with a reason an
 * admin can act on, never a sentence they must verify by hand.
 */
final readonly class PreflightCheck
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $passed,
        public string $reason,
    ) {}

    public static function pass(string $key, string $label, string $reason): self
    {
        return new self($key, $label, true, $reason);
    }

    public static function fail(string $key, string $label, string $reason): self
    {
        return new self($key, $label, false, $reason);
    }

    /**
     * @return array{key: string, label: string, passed: bool, reason: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'passed' => $this->passed,
            'reason' => $this->reason,
        ];
    }

    /**
     * @param  iterable<PreflightCheck>  $checks
     */
    public static function allPass(iterable $checks): bool
    {
        foreach ($checks as $check) {
            if (! $check->passed) {
                return false;
            }
        }

        return true;
    }
}
