<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$glpi = app(App\Services\GlpiService::class);
try {
    echo "initSession...\n";
    $glpi->initSession();
    echo "getAllItems Ticket...\n";
    $tic = microtime(true);
    $tickets = $glpi->getAllItems('Ticket', ['range' => '0-3']);
    echo "  took " . round((microtime(true) - $tic) * 1000) . "ms\n";
    echo "getAllItems Ticket_User...\n";
    $tic = microtime(true);
    $tu = $glpi->getAllItems('Ticket_User', ['range' => '0-3']);
    echo "  took " . round((microtime(true) - $tic) * 1000) . "ms\n";
    echo "Ticket_User count: " . count($tu) . "\n";
    $glpi->killSession();
    echo "Done\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
