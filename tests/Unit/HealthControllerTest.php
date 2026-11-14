<?php

declare(strict_types=1);

namespace CoffeeLab\Tests\Unit;

use CoffeeLab\Controller\HealthController;
use PHPUnit\Framework\TestCase;

final class HealthControllerTest extends TestCase
{
    public function testReturnsOkStatusAndServiceName(): void
    {
        http_response_code(200);
        ob_start();

        try {
            (new HealthController())();
            $responseBody = ob_get_contents();
        } finally {
            ob_end_clean();
        }

        self::assertSame(200, http_response_code());
        self::assertSame(
            [
                'status' => 'ok',
                'service' => 'coffee-lab-api',
            ],
            json_decode((string) $responseBody, true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
