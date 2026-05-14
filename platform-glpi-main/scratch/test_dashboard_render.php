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
    $html = $response->render();
    echo "HTML length: " . strlen($html) . "\n";
    echo "SUCCESS\n";
} catch (Exception $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n";
}
