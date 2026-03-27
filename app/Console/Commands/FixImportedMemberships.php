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
                            {--type= : Membership type slug to assign to ALL members (skips per-member prompt)}
                            {--dry-run : Preview changes without applying them}';

    protected $description = 'Assign and activate memberships for imported users who have no membership record';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $bulkTypeSlug = $this->option('type');

        $types = MembershipType::where('is_active', true)->orderBy('name')->get();
        if ($types->isEmpty()) {
            $this->error('No active membership types found.');

            return Command::FAILURE;
        }

        $typeChoices = $types->mapWithKeys(fn ($t) => [$t->slug => $t->name])->toArray();

        if ($bulkTypeSlug) {
            $bulkType = $types->firstWhere('slug', $bulkTypeSlug);
            if (! $bulkType) {
                $this->error("Membership type '{$bulkTypeSlug}' not found.");
                $this->line('Available types:');
                foreach ($typeChoices as $slug => $name) {
                    $this->line("  {$slug}  —  {$name}");
                }

                return Command::FAILURE;
            }
        }

        $usersWithoutMembership = User::where('role', User::ROLE_MEMBER)
            ->whereDoesntHave('memberships')
            ->get();

        if ($usersWithoutMembership->isEmpty()) {
            $this->info('All members already have a membership record. Nothing to fix.');

            return Command::SUCCESS;
        }

        $prefix = $dryRun ? '[DRY RUN] ' : '';
        $this->info("{$prefix}Found {$usersWithoutMembership->count()} member(s) without a membership.");
        $this->newLine();

        $this->table(
            ['#', 'Name', 'Email', 'Registered'],
            $usersWithoutMembership->values()->map(fn ($u, $i) => [$i + 1, $u->name, $u->email, $u->created_at->format('d M Y')])->toArray()
        );

        if ($dryRun) {
            $this->warn('Dry run complete. Re-run without --dry-run to apply changes.');

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->line('Available membership types:');
        foreach ($typeChoices as $slug => $name) {
            $this->line("  <info>{$slug}</info>  —  {$name}");
        }
        $this->newLine();

        $fixed = 0;
        $skipped = 0;

        foreach ($usersWithoutMembership as $user) {
            $this->line("━━━ <comment>{$user->name}</comment> ({$user->email}) ━━━");

            if ($bulkTypeSlug) {
                $membershipType = $bulkType;
                $this->line("  Type: {$membershipType->name} (from --type flag)");
            } else {
                $chosenSlug = $this->anticipate(
                    "  Membership type for {$user->name}? (type slug or 'skip')",
                    array_merge(array_keys($typeChoices), ['skip'])
                );

                if (strtolower($chosenSlug) === 'skip') {
                    $this->warn("  Skipped {$user->name}");
                    $skipped++;

                    continue;
                }

                $membershipType = $types->firstWhere('slug', $chosenSlug);
                if (! $membershipType) {
                    $this->error("  Unknown type '{$chosenSlug}' — skipping.");
                    $skipped++;

                    continue;
                }
            }

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
                'activated_at' => now(),
                'expires_at' => $expiresAt,
                'source' => 'import',
            ]);

            $fixed++;
            $this->info("  ✓ Assigned '{$membershipType->name}' to {$user->name}");
        }

        $this->newLine();
        $this->info("Done. {$fixed} membership(s) created. {$skipped} skipped.");

        return Command::SUCCESS;
    }
}
