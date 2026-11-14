<?php

declare(strict_types=1);

namespace CoffeeLab\Http;

final class JsonResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public static function send(int $statusCode, array $data): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
    }
}
