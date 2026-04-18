<?php

declare(strict_types=1);

namespace App\Enum;

enum MaterialConsumptionTargetType: string
{
    case LOCATION       = 'location';
    case INVENTORY_ITEM = 'inventory_item';
}// end enum
