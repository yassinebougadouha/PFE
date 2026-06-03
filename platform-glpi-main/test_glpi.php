<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$glpi = app(\App\Services\GlpiService::class);
$users = $glpi->getAllItems('User', ['range' => '0-1']);
print_r($users);
