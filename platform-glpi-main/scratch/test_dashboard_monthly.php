<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$admin = App\Models\User::where('email', 'admin@example.com')->first();
Auth::login($admin);
$c = app(App\Http\Controllers\AdminController::class);
request()->merge([]);
$r = $c->dashboard();
$d = $r->getData();
$m = $d['ticketsByMonth'] ?? [];
echo 'Count: ' . count($m) . "\n";
foreach ($m as $row) {
    echo "  month={$row['month']} count={$row['count']}\n";
}
