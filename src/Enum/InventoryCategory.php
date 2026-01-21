<?php

declare(strict_types=1);

namespace App\Enum;

enum InventoryCategory: string
{
    case COMPUTER = 'computer';
    case MONITOR = 'monitor';
    case PRINTER = 'printer';
    case SPEAKERS = 'speakers';
    case HEADSET = 'headset';
    case PHONE = 'phone';
    case NETWORK = 'network';
    case WEBCAM = 'webcam';
    case UPS = 'ups';
    case TABLET = 'tablet';
    case OTHER = 'other';

    /**
     * Get the Bootstrap icon class for this inventory category.
     *
     * @return string The icon CSS class corresponding to the category.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::COMPUTER => 'bi-cpu',
            self::MONITOR => 'bi-display',
            self::PRINTER => 'bi-printer',
            self::SPEAKERS => 'bi-speaker',
            self::HEADSET => 'bi-headset',
            self::PHONE => 'bi-phone',
            self::NETWORK => 'bi-router',
            self::WEBCAM => 'bi-camera-video',
            self::UPS => 'bi-lightning-charge',
            self::TABLET => 'bi-tablet',
            self::OTHER => 'bi-box',
        };
    }// end getIcon()

    /**
     * Get the color associated with the inventory category.
     *
     * @return string The color name for this category.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::COMPUTER => 'primary',
            self::MONITOR => 'info',
            self::PRINTER => 'warning',
            self::SPEAKERS => 'success',
            self::HEADSET => 'purple',
            self::PHONE => 'telegram',
            self::NETWORK => 'indigo',
            self::WEBCAM => 'pink',
            self::UPS => 'orange',
            self::TABLET => 'teal',
            self::OTHER => 'dark',
        };
    }// end getColor()

    /**
     * Get the Bootstrap badge class for this inventory category.
     *
     * @return string The badge CSS class corresponding to the category color.
     */
    public function getBadgeClass(): string
    {
        return sprintf('badge bg-%s', $this->getColor());
    }// end getBadgeClass()

    /**
     * Determine whether this inventory category has specifications.
     *
     * @return bool True if the category has specifications, false otherwise.
     */
    public function hasSpecifications(): bool
    {
        return !empty($this->getRequiredSpecifications());
    }// end hasSpecifications()

    /**
     * Get required specification keys for this category.
     *
     * @return array<string>
     */
    public function getRequiredSpecifications(): array
    {
        return match ($this) {
            self::COMPUTER => [
                'processor',
                'ram',
                'storage',
            ],
            self::MONITOR => ['size'],
            self::PRINTER => ['type'],
            self::NETWORK => ['type'],
            default => [],
        };
    }// end getRequiredSpecifications()

    /**
     * Get allowed specification keys for this category.
     *
     * @return array<string>
     */
    public function getAllowedSpecifications(): array
    {
        return match ($this) {
            self::COMPUTER => [
                'processor',
                'ram',
                'storage',
                'graphics',
                'motherboard',
                'psu',
                'os',
                'other',
            ],
            self::MONITOR => [
                'size',
                'ports',
                'other',
            ],
            self::PRINTER => [
                'type',
                'paper_format',
                'duplex',
                'network',
                'other',
            ],
            self::NETWORK => [
                'type',
                'ports',
                'speed',
                'wifi_standard',
                'poe',
                'management',
                'other',
            ],
            self::WEBCAM => [
                'microphone',
                'connection',
                'other',
            ],
            self::UPS => [
                'capacity',
                'runtime',
                'output_power',
                'battery_type',
                'management',
                'other',
            ],
            self::TABLET => [
                'screen_size',
                'operating_system',
                'storage',
                'ram',
                'processor',
                'camera',
                'battery_capacity',
                'connectivity',
                'other',
            ],
            default => [],
        };// end match
    }// end getAllowedSpecifications()

    /**
     * Get the values of all enum cases.
     *
     * @return array<int, int|string> Returns an array of values for each enum case.
     */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }// end getValues()

    /**
     * Converts the InventoryCategory enum to an associative array.
     *
     * @return array{
     *     value: int|string,
     *     label: string,
     *     icon: string,
     *     color: string,
     *     has_specifications: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'value'              => $this->value,
            'icon'               => $this->getIcon(),
            'color'              => $this->getColor(),
            'has_specifications' => $this->hasSpecifications(),
        ];
    }// end toArray()
}// end enum
