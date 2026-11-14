<?php

declare(strict_types=1);

namespace CoffeeLab\Domain\Coffee;

enum RoastLevel: string
{
    case Light = 'light';
    case Medium = 'medium';
    case Dark = 'dark';
}
