<?php

declare(strict_types=1);

namespace CoffeeLab\Controller;

use CoffeeLab\Application\Coffee\CoffeeNotFound;
use CoffeeLab\Application\Coffee\CreateCoffee;
use CoffeeLab\Application\Coffee\GetCoffee;
use CoffeeLab\Application\Coffee\ListCoffees;
use CoffeeLab\Domain\Coffee\Coffee;
use CoffeeLab\Domain\Coffee\CoffeeId;
use InvalidArgumentException;
use JsonException;
use stdClass;
use CoffeeLab\Http\JsonResponse;

final readonly class CoffeeController
{
    public function __construct(
        private CreateCoffee $createCoffee,
        private GetCoffee $getCoffee,
        private ListCoffees $listCoffees,
    ) {
    }

    public function create(string $requestBody): void
    {
        try {
            $payload = json_decode($requestBody, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            JsonResponse::send(400, [
                'status' => 'error',
                'message' => 'Malformed JSON body.',
            ]);
            return;
        }

        if (
            !$payload instanceof stdClass
            || !isset($payload->name, $payload->origin, $payload->roastLevel)
            || !is_string($payload->name)
            || !is_string($payload->origin)
            || !is_string($payload->roastLevel)
        ) {
            $this->invalidCoffeeData();
            return;
        }

        try {
            $coffee = $this->createCoffee->execute(
                $payload->name,
                $payload->origin,
                $payload->roastLevel,
            );
        } catch (InvalidArgumentException) {
            $this->invalidCoffeeData();
            return;
        }

        JsonResponse::send(201, $this->serializeCoffee($coffee));
    }

    public function index(): void
    {
        $coffees = array_map(
            fn (Coffee $coffee): array => $this->serializeCoffee($coffee),
            $this->listCoffees->execute(),
        );

        JsonResponse::send(200, $coffees);
    }

    public function show(string $id): void
    {
        try {
            $coffee = $this->getCoffee->execute($id);
        } catch (InvalidArgumentException) {
            JsonResponse::send(400, [
                'status' => 'error',
                'message' => 'Invalid coffee id.',
            ]);
            return;
        } catch (CoffeeNotFound) {
            JsonResponse::send(404, [
                'status' => 'error',
                'message' => 'Coffee not found.',
            ]);
            return;
        }

        JsonResponse::send(200, $this->serializeCoffee($coffee));
    }

    /**
     * @return array{id: string, name: string, origin: string, roastLevel: string, createdAt: string}
     */
    private function serializeCoffee(Coffee $coffee): array
    {
        return [
            'id' => (string) $coffee->id,
            'name' => $coffee->name,
            'origin' => $coffee->origin,
            'roastLevel' => $coffee->roastLevel->value,
            'createdAt' => $coffee->createdAt->format(DATE_ATOM),
        ];
    }

    private function invalidCoffeeData(): void
    {
        JsonResponse::send(422, [
            'status' => 'error',
            'message' => 'Invalid coffee data.',
        ]);
    }
}
