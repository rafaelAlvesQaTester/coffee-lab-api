<?php

declare(strict_types=1);

namespace CoffeeLab\Controller;

use CoffeeLab\Http\JsonResponse;

final class HealthController
{
    public function __invoke(): void
    {
        JsonResponse::send(200, [
            'status' => 'ok',
            'service' => 'coffee-lab-api',
        ]);
    }
}
