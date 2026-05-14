<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('email', 'admin@example.com')->first();
Auth::login($admin);

// Manual GLPI fetch — exactly like it's done in the controller
$adminGlpiId = (int)$admin->glpi_user_id;
$allTickets  = [];

$glpi = app(App\Services\GlpiService::class);
$glpi->initSession();
$rawTickets = $glpi->getAllItems('Ticket', ['range' => '0-9999', 'order' => 'DESC']);

$ticketAssignees = [];
try {
    $tuList = $glpi->getAllItems('Ticket_User', ['range' => '0-9999']);
    foreach ($tuList as $tu) {
        if (isset($tu['tickets_id'], $tu['users_id'], $tu['type']) && (int)$tu['type'] === 2) {
            $ticketAssignees[(int)$tu['tickets_id']] = (int)$tu['users_id'];
        }
    }
} catch (\Exception $e2) {
    echo 'TU fetch failed: ' . $e2->getMessage() . "\n";
}
$glpi->killSession();

echo 'Raw: ' . count($rawTickets) . ' TU: ' . count($ticketAssignees) . "\n";

$statusMap = [1 => 'pending', 2 => 'in_progress', 3 => 'in_progress', 4 => 'in_progress', 5 => 'resolved', 6 => 'closed'];
foreach ($rawTickets as $t) {
    $tid = (int)($t['id'] ?? 0);
    $obj = new \stdClass();
    $obj->id = $tid;
    $obj->glpi_id = $tid;
    $obj->title = $t['name'] ?? '';
    $obj->sync_status = $statusMap[(int)($t['status'] ?? 1)] ?? 'pending';
    $obj->priority = (int)($t['priority'] ?? 3);
    $obj->assigned_to = $ticketAssignees[$tid] ?? (int)($t['users_id_lastupdater'] ?? 0);
    $obj->created_at = \Carbon\Carbon::parse($t['date_creation'] ?? $t['date'] ?? now());
    $allTickets[] = $obj;
}

echo 'Total: ' . count($allTickets) . "\n";

$filtered = collect($allTickets)
    ->filter(fn($t) => (int)$t->assigned_to === $adminGlpiId)
    ->filter(fn($t) => in_array($t->sync_status, ['pending', 'in_progress']))
    ->filter(function ($t) {
        $p = (int)$t->priority;
        $created = \Carbon\Carbon::parse($t->created_at);
        return $p >= 4 || ($p >= 3 && $created->lte(now()->subHours(20)));
    });

echo 'Urgent: ' . $filtered->count() . "\n";
foreach ($filtered as $t) {
    echo "  #{$t->id} p={$t->priority} a={$t->assigned_to} {$t->title}\n";
}
