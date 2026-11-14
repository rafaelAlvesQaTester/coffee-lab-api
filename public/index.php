<?php

declare(strict_types=1);

use CoffeeLab\Controller\HealthController;
use CoffeeLab\Http\JsonResponse;

require dirname(__DIR__) . '/vendor/autoload.php';

set_exception_handler(static function (\Throwable $exception): void {
    error_log(sprintf('Unhandled application exception: %s', $exception::class));
    JsonResponse::send(500, [
        'status' => 'error',
        'message' => 'Internal Server Error',
    ]);
});

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path === '/health' && $method === 'GET') {
    (new HealthController())();
    return;
}

if ($path === '/health') {
    header('Allow: GET');
    JsonResponse::send(405, [
        'status' => 'error',
        'message' => 'Method Not Allowed',
    ]);
    return;
}

JsonResponse::send(404, [
    'status' => 'error',
    'message' => 'Not Found',
]);
