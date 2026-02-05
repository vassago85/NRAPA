<?php

// Quick script to reset 2FA login counters for local development
// Run: php reset_2fa_counter.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

$users = User::whereIn('role', ['admin', 'owner', 'developer'])->get();

foreach ($users as $user) {
    $user->update(['logins_without_2fa' => 0]);
    echo "Reset 2FA counter for: {$user->email}\n";
}

echo "Done! All admin users have been reset.\n";
