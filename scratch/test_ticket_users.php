<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$glpi = app(App\Services\GlpiService::class);
$glpi->initSession();
$tu = $glpi->getAllItems('Ticket_User', ['range' => '0-5']);
$glpi->killSession();
foreach ($tu as $t) {
    echo "tickets_id={$t['tickets_id']} users_id={$t['users_id']} type={$t['type']}\n";
}
