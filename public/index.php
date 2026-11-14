<?php

declare(strict_types=1);

use CoffeeLab\Controller\HealthController;
use CoffeeLab\Controller\CoffeeController;
use CoffeeLab\Application\Coffee\CreateCoffee;
use CoffeeLab\Application\Coffee\GetCoffee;
use CoffeeLab\Application\Coffee\ListCoffees;
use CoffeeLab\Http\JsonResponse;
use CoffeeLab\Infrastructure\Persistence\InMemoryCoffeeRepository;

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

if ($path === '/coffees' || (is_string($path) && str_starts_with($path, '/coffees/'))) {
    $repository = new InMemoryCoffeeRepository();
    $coffeeController = new CoffeeController(
        new CreateCoffee($repository),
        new GetCoffee($repository),
        new ListCoffees($repository),
    );

    if ($path === '/coffees' && $method === 'POST') {
        $requestBody = file_get_contents('php://input');
        $coffeeController->create($requestBody === false ? '' : $requestBody);
        return;
    }

    if ($path === '/coffees' && $method === 'GET') {
        $coffeeController->index();
        return;
    }

    if (is_string($path) && preg_match('/\A\/coffees\/([^\/]+)\z/', $path, $matches) === 1 && $method === 'GET') {
        $coffeeController->show($matches[1]);
        return;
    }
}

JsonResponse::send(404, [
    'status' => 'error',
    'message' => 'Not Found',
]);
