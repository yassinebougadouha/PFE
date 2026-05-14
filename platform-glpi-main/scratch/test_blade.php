<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$glpi = app(App\Services\GlpiService::class);
try {
    $glpi->initSession();
    $tickets = $glpi->getTransformedTickets(['range' => '0-5']);
    $glpi->killSession();
    echo "Fetched " . count($tickets) . " tickets from GLPI\n";
    foreach ($tickets as $t) {
        echo "  #{$t->id} [{$t->sync_status}] " . substr($t->title, 0, 50) . "\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
