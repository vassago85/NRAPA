<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\MembershipType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixImportedMemberships extends Command
{
    protected $signature = 'nrapa:fix-imported-memberships
                            {--type=standard-annual : Default membership type slug to assign}
                            {--dry-run : Preview changes without applying them}';

    protected $description = 'Assign and activate memberships for imported users who have no membership record';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $typeSlug = $this->option('type');

        $membershipType = MembershipType::where('slug', $typeSlug)->first();
        if (! $membershipType) {
            $this->error("Membership type '{$typeSlug}' not found.");
            $this->line('Available types:');
            MembershipType::where('is_active', true)->orderBy('name')->each(function ($t) {
                $this->line("  {$t->slug}  —  {$t->name}");
            });

            return Command::FAILURE;
        }

        $usersWithoutMembership = User::where('role', User::ROLE_MEMBER)
            ->whereDoesntHave('memberships')
            ->get();

        if ($usersWithoutMembership->isEmpty()) {
            $this->info('All members already have a membership record. Nothing to fix.');

            return Command::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$usersWithoutMembership->count()} member(s) without a membership.");
        $this->table(
            ['Name', 'Email', 'Registered'],
            $usersWithoutMembership->map(fn ($u) => [$u->name, $u->email, $u->created_at->format('d M Y')])->toArray()
        );

        if ($dryRun) {
            $this->warn("Dry run complete. Re-run without --dry-run to apply changes.");

            return Command::SUCCESS;
        }

        if (! $this->confirm("Assign '{$membershipType->name}' and activate all {$usersWithoutMembership->count()} member(s)?")) {
            $this->info('Cancelled.');

            return Command::SUCCESS;
        }

        $fixed = 0;
        foreach ($usersWithoutMembership as $user) {
            $expiresAt = null;
            if ($membershipType->requires_renewal && $membershipType->duration_months) {
                $expiresAt = now()->addMonths($membershipType->duration_months);
            }

            Membership::create([
                'uuid' => Str::uuid(),
                'user_id' => $user->id,
                'membership_type_id' => $membershipType->id,
                'membership_number' => $user->formatted_member_number,
                'status' => 'active',
                'applied_at' => $user->created_at,
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'activated_at' => now(),
                'expires_at' => $expiresAt,
                'source' => 'import',
            ]);

            $fixed++;
            $this->line("  ✓ {$user->name} ({$user->email})");
        }

        $this->info("Done. {$fixed} membership(s) created and activated.");

        return Command::SUCCESS;
    }
}
