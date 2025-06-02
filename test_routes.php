<?php

// Quick test to check if the route exists
require_once __DIR__ . '/bootstrap/app.php';

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/reports', 'GET')
);

echo "Routes test completed\n";
echo "Status: " . $response->getStatusCode() . "\n";

// Try to check if route exists
try {
    $url = route('reports.export');
    echo "Route 'reports.export' exists: " . $url . "\n";
} catch (Exception $e) {
    echo "Route 'reports.export' error: " . $e->getMessage() . "\n";
}
