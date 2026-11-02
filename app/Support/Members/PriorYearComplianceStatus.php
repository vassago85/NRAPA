<?php

declare(strict_types=1);

namespace App\Support\Members;

use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Readonly DTO returned by {@see PriorYearComplianceResolver}. Keeps the
 * UI code free of calculation logic so the approval screen can speak in
 * terms of "verified by admin X" vs "imported first cycle" vs "2 of 2
 * approved activities for YYYY" without reaching back into the resolver.
 */
final class PriorYearComplianceStatus
{
    public function __construct(
        public readonly bool $applicable,
        public readonly ?int $priorYear = null,
        public readonly int $required = 0,
        public readonly int $priorCount = 0,
        public readonly bool $isManuallyVerified = false,
        public readonly bool $isImportedFirstCycle = false,
        public readonly bool $isActivityCompliant = false,
        public readonly ?CarbonInterface $verifiedAt = null,
        public readonly ?User $verifiedBy = null,
        public readonly ?string $verifiedNote = null,
    ) {}

    public static function notApplicable(): self
    {
        return new self(applicable: false);
    }

    /**
     * The overall "can this renewal be approved" verdict.
     */
    public function isReady(): bool
    {
        if (! $this->applicable) {
            return true;
        }

        return $this->isManuallyVerified
            || $this->isImportedFirstCycle
            || $this->isActivityCompliant;
    }

    /**
     * A short reason string suitable for an admin UI badge.
     */
    public function reason(): string
    {
        if (! $this->applicable) {
            return 'Not applicable';
        }
        if ($this->isManuallyVerified) {
            $name = $this->verifiedBy?->name;
            return $name
                ? "Verified by {$name}"
                : 'Verified by admin';
        }
        if ($this->isImportedFirstCycle) {
            return 'Imported first cycle — auto-pass available';
        }
        if ($this->isActivityCompliant) {
            return "{$this->priorCount} of {$this->required} approved activities for {$this->priorYear}";
        }

        return "Only {$this->priorCount} of {$this->required} approved activities for {$this->priorYear} — admin must verify or reject";
    }

    /**
     * UI badge colour hint. Maps to Tailwind semantic classes at the view.
     */
    public function tone(): string
    {
        if (! $this->applicable) {
            return 'neutral';
        }
        if ($this->isManuallyVerified || $this->isActivityCompliant) {
            return 'success';
        }
        if ($this->isImportedFirstCycle) {
            return 'warning';
        }

        return 'danger';
    }
}
