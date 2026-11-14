<?php

declare(strict_types=1);

use CoffeeLab\Controller\HealthController;
use CoffeeLab\Controller\CoffeeController;
use CoffeeLab\Application\Coffee\CreateCoffee;
use CoffeeLab\Application\Coffee\GetCoffee;
use CoffeeLab\Application\Coffee\ListCoffees;
use CoffeeLab\Http\JsonResponse;
use CoffeeLab\Http\RequestLogger;
use CoffeeLab\Infrastructure\Persistence\PdoCoffeeRepository;
use CoffeeLab\Infrastructure\Persistence\PdoConnectionFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'] ?? '';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$correlationId = RequestLogger::correlationId(
    isset($_SERVER['HTTP_X_REQUEST_ID']) && is_string($_SERVER['HTTP_X_REQUEST_ID'])
        ? $_SERVER['HTTP_X_REQUEST_ID']
        : null,
);
header('X-Request-Id: ' . $correlationId);

$requestLogger = new RequestLogger();
$startedAt = hrtime(true);
$errorType = null;

try {
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
        $connection = PdoConnectionFactory::fromEnvironment();
        $repository = new PdoCoffeeRepository($connection);
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
} catch (\Throwable $exception) {
    $errorType = $exception::class;
    JsonResponse::send(500, [
        'status' => 'error',
        'message' => 'Internal Server Error',
    ]);
} finally {
    $statusCode = http_response_code();
    $requestLogger->log(
        RequestLogger::normalizeMethod(is_string($method) ? $method : null),
        RequestLogger::normalizeRoute(is_string($path) ? $path : null),
        is_int($statusCode) ? $statusCode : 200,
        (hrtime(true) - $startedAt) / 1_000_000,
        $correlationId,
        $errorType,
    );
}
