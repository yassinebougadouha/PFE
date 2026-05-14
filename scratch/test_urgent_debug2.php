<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('email', 'admin@example.com')->first();
Auth::login($admin);

$glpi = app(App\Services\GlpiService::class);
try {
    $glpi->initSession();
    $raw = $glpi->getAllItems('Ticket', ['range' => '0-9999', 'order' => 'DESC']);
    echo "Raw tickets: " . count($raw) . "\n";
    
    // Check Ticket_User
    $tu = $glpi->getAllItems('Ticket_User', ['range' => '0-9999']);
    $ticketAssignees = [];
    foreach ($tu as $a) {
        if (isset($a['tickets_id'], $a['users_id'], $a['type']) && (int)$a['type'] === 2) {
            $ticketAssignees[(int)$a['tickets_id']] = (int)$a['users_id'];
        }
    }
    echo "Ticket_User assignments (type=2): " . count($ticketAssignees) . "\n";
    
    $glpi->killSession();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

$adminGlpiId = (int)$admin->glpi_user_id;
echo "Admin GLPI ID: $adminGlpiId\n";

// Transform and filter
$statusMap = [1 => 'pending', 2 => 'in_progress', 3 => 'in_progress', 4 => 'in_progress', 5 => 'resolved', 6 => 'closed'];
$allTickets = [];
foreach ($raw as $t) {
    $tid = (int)($t['id'] ?? 0);
    $obj = new \stdClass();
    $obj->id = $tid;
    $obj->glpi_id = $tid;
    $obj->title = $t['name'] ?? '';
    $obj->sync_status = $statusMap[(int)($t['status'] ?? 1)] ?? 'pending';
    $obj->priority = (int)($t['priority'] ?? 3);
    $obj->user_id = $t['users_id_recipient'] ?? null;
    $obj->assigned_to = $ticketAssignees[$tid] ?? (int)($t['users_id_lastupdater'] ?? 0);
    $obj->created_at = \Carbon\Carbon::parse($t['date_creation'] ?? $t['date'] ?? now());
    $allTickets[] = $obj;
}

echo "Transformed tickets: " . count($allTickets) . "\n";

$myTickets = collect($allTickets)->filter(fn($t) => (int)$t->assigned_to === $adminGlpiId);
echo "My assigned tickets: " . $myTickets->count() . "\n";

$urgent = $myTickets
    ->filter(fn($t) => in_array($t->sync_status, ['pending', 'in_progress']))
    ->filter(function ($t) {
        $p = (int)$t->priority;
        $created = \Carbon\Carbon::parse($t->created_at);
        return $p >= 4 || ($p >= 3 && $created->lte(now()->subHours(20)));
    });
    
echo "Urgent tickets: " . $urgent->count() . "\n";
foreach ($urgent as $t) {
    echo "  #{$t->glpi_id} p={$t->priority} s={$t->sync_status} assigned_to={$t->assigned_to} {$t->title}\n";
}
