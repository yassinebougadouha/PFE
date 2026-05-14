<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$glpi = app(App\Services\GlpiService::class);
try {
    $glpi->initSession();
    echo "Session initialized\n";
    $tickets = $glpi->getTransformedTickets(['range' => '0-5']);
    echo "Fetched " . count($tickets) . " tickets\n";
    foreach ($tickets as $t) {
        echo "  #{$t->id} assigned_to={$t->assigned_to} status={$t->sync_status} priority={$t->priority} {$t->title}\n";
    }
    $glpi->killSession();
    echo "Done\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
