<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('email', 'admin@example.com')->first();
Auth::login($admin);

$controller = app(App\Http\Controllers\AdminController::class);
request()->merge([]);

try {
    $response = $controller->dashboard();
    echo "View: " . $response->getName() . "\n";
    $data = $response->getData();
    echo "totalTickets: " . ($data['totalTickets'] ?? 'N/A') . "\n";
    echo "openTickets: " . ($data['openTickets'] ?? 'N/A') . "\n";
    echo "urgentTickets: " . count($data['urgentTickets'] ?? []) . "\n";
    echo "recentTickets: " . count($data['recentTickets'] ?? []) . "\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
