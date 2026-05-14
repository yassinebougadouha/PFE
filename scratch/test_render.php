<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test GlpiService directly
$glpi = app(App\Services\GlpiService::class);
try {
    $glpi->initSession();
    $tickets = $glpi->getTransformedTickets(['range' => '0-3']);
    $glpi->killSession();
    foreach ($tickets as $t) {
        echo "Ticket #{$t->id}: created_at=" . get_class($t->created_at) . " -> " . $t->created_at->format('d/m/Y') . "\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
