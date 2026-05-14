<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('email', 'admin@example.com')->first();
Auth::login($admin);
echo "Admin glpi_user_id: " . ($admin->glpi_user_id ?? 'null') . "\n";

$controller = app(App\Http\Controllers\AiController::class);
request()->merge([]);

try {
    $response = $controller->urgentTicketsList();
    $data = $response->getData();
    $tickets = $data['tickets'] ?? [];
    echo "Tickets count: " . count($tickets) . "\n";
    foreach ($tickets as $t) {
        echo "  #{$t['id']} [{$t['status']}] priority={$t['priority']} {$t['title']}\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
