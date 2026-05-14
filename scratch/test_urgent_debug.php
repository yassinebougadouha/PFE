<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('email', 'admin@example.com')->first();
if (!$admin) { echo "Admin not found\n"; exit(1); }
Auth::login($admin);
echo "Admin glpi_user_id: " . ($admin->glpi_user_id ?? 'null') . "\n";

$glpi = app(App\Services\GlpiService::class);
try {
    $glpi->initSession();
    $tickets = $glpi->getTransformedTickets(['range' => '0-9999', 'order' => 'DESC']);
    $glpi->killSession();
} catch (Exception $e) {
    echo "GLPI error: " . $e->getMessage() . "\n";
    exit(1);
}

$myTickets = collect($tickets)->filter(fn($t) => (int)$t->assigned_to === (int)$admin->glpi_user_id);
echo "My tickets: " . $myTickets->count() . "\n";

$urgent = $myTickets
    ->filter(fn($t) => in_array($t->sync_status, ['pending', 'in_progress']))
    ->filter(function ($t) {
        $p = (int)$t->priority;
        $created = Carbon\Carbon::parse($t->created_at);
        return $p >= 4 || ($p >= 3 && $created->lte(now()->subHours(20)));
    });

echo "Urgent tickets count: " . $urgent->count() . "\n";
foreach ($urgent as $t) {
    echo "  #{$t->glpi_id} priority={$t->priority} status={$t->sync_status} assigned_to={$t->assigned_to} {$t->title}\n";
}

// Also show a few non-urgent ones to compare
echo "\nSample assigned (first 10):\n";
$myTickets->take(10)->each(function ($t) {
    echo "  #{$t->glpi_id} priority={$t->priority} status={$t->sync_status} assigned_to={$t->assigned_to} {$t->title}\n";
});
