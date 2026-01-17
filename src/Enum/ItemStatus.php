<?php

declare(strict_types=1);

namespace App\Enum;

enum ItemStatus: string
{
    case AVAILABLE = 'available';
    case IN_USE = 'in_use';
    case UNDER_REPAIR = 'under_repair';
    case WRITTEN_OFF = 'written_off';
    case LOST = 'lost';
    case RESERVED = 'reserved';
    case ON_MAINTENANCE = 'on_maintenance';
    case NEW = 'new';
    case BROKEN = 'broken';
    case FOR_PARTS = 'for_parts';

    /**
     * Returns the color code associated with the item status.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::IN_USE => 'primary',
            self::UNDER_REPAIR => 'warning',
            self::WRITTEN_OFF => 'danger',
            self::LOST => 'danger',
            self::RESERVED => 'info',
            self::ON_MAINTENANCE => 'info',
            self::NEW => 'success',
            self::BROKEN => 'danger',
            self::FOR_PARTS => 'warning',
        };
    }// end getColor()

    /**
     * Returns the icon class associated with the item status.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::AVAILABLE => '',
            self::IN_USE => 'bi-check-circle',
            self::UNDER_REPAIR => 'bi-tools',
            self::WRITTEN_OFF => 'bi-trash',
            self::LOST => 'bi-exclamation-circle',
            self::RESERVED => 'bi-lock',
            self::ON_MAINTENANCE => 'bi-gear',
            self::NEW => 'bi-star',
            self::BROKEN => 'bi-exclamation-triangle',
            self::FOR_PARTS => 'bi-cpu',
        };
    }// end getIcon()

    /**
     * Determines if the item status is available for use or reservation.
     */
    public function isAvailable(): bool
    {
        return $this === self::AVAILABLE || $this === self::RESERVED;
    }// end isAvailable()

    /**
     * Determines if the item status means it is in use or unavailable for new assignment.
     */
    public function isInUse(): bool
    {
        return $this === self::IN_USE || $this === self::UNDER_REPAIR || $this === self::ON_MAINTENANCE;
    }// end isInUse()

    /**
     * Returns a list of active item statuses.
     *
     * @return array<ItemStatus>
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::AVAILABLE,
            self::IN_USE,
            self::UNDER_REPAIR,
            self::RESERVED,
            self::ON_MAINTENANCE,
        ];
    }// end getActiveStatuses()

    /**
     * Returns a list of inactive item statuses.
     *
     * @return array<ItemStatus>
     */
    public static function getInactiveStatuses(): array
    {
        return [
            self::WRITTEN_OFF,
            self::LOST,
        ];
    }// end getInactiveStatuses()
}// end enum
