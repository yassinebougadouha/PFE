<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app(App\Http\Controllers\SuperAdminController::class);
request()->merge(['all' => '1']);

try {
    $response = $controller->tickets();
    echo 'View: ' . $response->getName() . "\n";
    $data = $response->getData();
    echo 'TotalTickets: ' . ($data['totalTickets'] ?? 'N/A') . "\n";
    echo 'OpenTickets: ' . ($data['openTickets'] ?? 'N/A') . "\n";
    echo 'InProgress: ' . ($data['inProgressTickets'] ?? 'N/A') . "\n";
    echo 'Closed: ' . ($data['closedTickets'] ?? 'N/A') . "\n";
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

echo "\n--- Testing showTicket ---\n";
try {
    $showResponse = $controller->showTicket(33);
    $showData = $showResponse->getData();
    $ticket = $showData['ticket'] ?? null;
    if ($ticket) {
        echo "Ticket #{$ticket->id}: {$ticket->title}\n";
        echo "  Status: {$ticket->sync_status}\n";
        echo "  Priority: {$ticket->priority}\n";
        echo "  GLPI Followups: " . count($showData['glpiFollowups'] ?? []) . "\n";
    }
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
