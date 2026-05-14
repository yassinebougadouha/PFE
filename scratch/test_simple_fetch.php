<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$glpi = app(App\Services\GlpiService::class);
$glpi->initSession();
$tickets = $glpi->getTransformedTickets(['range' => '0-5']);
$glpi->killSession();
echo "Tickets: " . count($tickets) . "\n";
foreach ($tickets as $t) {
    echo "  #{$t->id} p={$t->priority} a={$t->assigned_to} s={$t->sync_status} {$t->title}\n";
}
