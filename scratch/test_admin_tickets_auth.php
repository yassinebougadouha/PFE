<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Find the admin user
$admin = App\Models\User::where('email', 'admin@example.com')->first();
if (!$admin) {
    echo "Admin not found\n";
    exit(1);
}
echo "Admin glpi_user_id: " . ($admin->glpi_user_id ?? 'null') . "\n";

// Auth as admin
Auth::login($admin);

$controller = app(App\Http\Controllers\AdminController::class);
request()->merge(['all' => '1']);

try {
    $response = $controller->tickets();
    $data = $response->getData();
    echo "TotalAll: " . ($data['totalAll'] ?? 'N/A') . "\n";
    echo "TotalPending: " . ($data['totalPending'] ?? 'N/A') . "\n";
    echo "TotalProgress: " . ($data['totalProgress'] ?? 'N/A') . "\n";
    $tickets = $data['tickets'] ?? null;
    if ($tickets) {
        echo "Tickets count: " . $tickets->count() . "\n";
        foreach ($tickets as $t) {
            echo "  #{$t->id} [{$t->sync_status}] assigned_to={$t->assigned_to} {$t->title}\n";
        }
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
