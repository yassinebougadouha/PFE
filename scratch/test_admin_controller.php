<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app(App\Http\Controllers\AdminController::class);
request()->merge(['all' => '1']);

try {
    $response = $controller->tickets();
    echo 'View: ' . $response->getName() . "\n";
    $data = $response->getData();
    echo 'TotalAll: ' . ($data['totalAll'] ?? 'N/A') . "\n";
    echo 'TotalPending: ' . ($data['totalPending'] ?? 'N/A') . "\n";
    echo 'TotalProgress: ' . ($data['totalProgress'] ?? 'N/A') . "\n";
    echo 'TotalResolved: ' . ($data['totalResolved'] ?? 'N/A') . "\n";
    $tickets = $data['tickets'] ?? null;
    if ($tickets) {
        echo 'Tickets count: ' . $tickets->count() . "\n";
        foreach ($tickets as $t) {
            echo "  #{$t->id} [{$t->sync_status}] {$t->title}\n";
        }
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
