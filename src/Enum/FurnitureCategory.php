<?php

declare(strict_types=1);

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum FurnitureCategory: string implements TranslatableInterface
{
    case DESK = 'desk';
    case CHAIR = 'chair';
    case ARMCHAIR = 'armchair';
    case BED = 'bed';
    case CABINET = 'cabinet';
    case NIGHTSTAND = 'nightstand';
    case OTHER = 'other';

    /**
     * {@inheritDoc}
     */
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(
            "category.{$this->value}",
            domain: 'furniture',
            locale: $locale
        );
    }// end trans()

    /**
     * Get the Bootstrap icon class for this furniture category.
     *
     * @return string The icon CSS class corresponding to the category.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::DESK => 'bi-table',
            self::CHAIR => 'bi-chair',
            self::ARMCHAIR => 'bi-armchair',
            self::BED => 'bi-bed',
            self::CABINET => 'bi-archive',
            self::NIGHTSTAND => 'bi-lamp',
            self::OTHER => 'bi-box',
        };
    }// end getIcon()

    /**
     * Get the color associated with the furniture category.
     *
     * @return string The color name for this category.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::DESK => 'primary',
            self::CHAIR => 'success',
            self::ARMCHAIR => 'info',
            self::BED => 'telegram',
            self::CABINET => 'indigo',
            self::NIGHTSTAND => 'pink',
            self::OTHER => 'dark',
        };
    }// end getColor()

    /**
     * Get the Bootstrap badge class for this furniture category.
     *
     * @return string The badge CSS class corresponding to the category color.
     */
    public function getBadgeClass(): string
    {
        return sprintf('badge badge-%s', $this->getColor());
    }// end getBadgeClass()

    /**
     * Determine whether this furniture category has specifications.
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
            self::DESK => [
                'width',
                'depth',
            ],
            self::CHAIR => ['width'],
            self::ARMCHAIR => ['width'],
            self::BED => ['width'],
            self::CABINET => [
                'width',
                'depth',
            ],
            self::NIGHTSTAND => [
                'width',
                'depth',
            ],
            default => [],
        };
    }// end getRequiredSpecifications()

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
     * Converts the FurnitureCategory enum to an associative array.
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
